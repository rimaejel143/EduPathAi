<?php

session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

try {
    // 1) Find the latest student_assessment_id for this user
    // Some environments may not have a 'created_at' column on student_assessment;
    // order by the auto-incrementing id instead to get the latest assessment.
    $stmt = $pdo->prepare("SELECT student_assessment_id FROM student_assessment WHERE user_id = ? ORDER BY student_assessment_id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $sa_row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sa_row || empty($sa_row['student_assessment_id'])) {
        echo json_encode(["success" => false, "message" => "No assessment found for user"]);
        exit;
    }
    $student_assessment_id = intval($sa_row['student_assessment_id']);

    // 2) Fetch exactly 3 part totals for that assessment (ordered by part_number)
    $stmt = $pdo->prepare(
        "SELECT part_number, total_score FROM assessment_part_results WHERE student_assessment_id = ? ORDER BY part_number ASC"
    );
    $stmt->execute([$student_assessment_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) < 3) {
        echo json_encode(["success" => false, "message" => "All 3 part results must be completed first"]);
        exit;
    }

    // Build scores array in order [part1, part2, part3]
    $scores = [];
    foreach ($rows as $r) {
        $scores[] = intval($r['total_score']);
    }

    // Scores stored in `assessment_part_results` are normalized by calculate_part_result.php
    // so use them directly.
    $normScores = $scores;

    // 3) Call AI Major Predictor
    $url = "http://localhost/SeniorEducation/php/api/predict_major.php";

    // Call predictor with normalized scores (already stored normalized)
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["scores" => $normScores]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response === false || $response === null) {
        echo json_encode(["success" => false, "message" => "AI failed", "details" => $curlErr]);
        exit;
    }

    // Decode predictor response. Handle case where predictor returned a JSON string (double-encoded)
    $ai = json_decode($response, true);
    if ($ai === null) {
        echo json_encode(["success" => false, "message" => "AI failed", "details" => $response]);
        exit;
    }

    // If decoded value is a string (predictor double-encoded JSON), try to decode inner JSON
    if (is_string($ai)) {
        $inner = json_decode($ai, true);
        if ($inner !== null) {
            $ai = $inner;
        }
    }

    if (!is_array($ai) || !isset($ai['success']) || $ai['success'] !== true) {
        echo json_encode(["success" => false, "message" => "AI failed", "details" => $response]);
        exit;
    }

    // Accept major (human name) and major_id (if provided)
    $major = $ai['major'] ?? null;
    $confidence = $ai['confidence'] ?? null;
    $major_id = isset($ai['major_id']) ? (string)$ai['major_id'] : (string)($ai['major'] ?? '');

    // 4) Save final result using the correct schema (student_assessment_id, major_id, feedback, created_at)
    $stmt = $pdo->prepare(
        "INSERT INTO final_result (student_assessment_id, major_id, feedback, created_at)\n       
          VALUES (?, ?, ?, NOW())\n       
            ON DUPLICATE KEY UPDATE major_id = VALUES(major_id), feedback = VALUES(feedback), created_at = NOW()
            ");
    $major_id = $ai['major_id'] ?? null;
    $feedback = '';
    $stmt->execute([$student_assessment_id, $major_id, $feedback]);

    
    // 5) Return JSON response to frontend
    // --- Append this new labeled sample to the AI dataset.csv (safe, non-blocking)
    try {
        $csvPath = __DIR__ . '/../ai/dataset.csv';
        $csvDir = dirname($csvPath);
        if (!is_dir($csvDir)) {
            // attempt to create ai dir if missing
            @mkdir($csvDir, 0755, true);
        }
        $exists = file_exists($csvPath);
        $fp = @fopen($csvPath, 'a');
        if ($fp) {
            // acquire exclusive lock to avoid races
            if (flock($fp, LOCK_EX)) {
                // if file didn't exist, write header
                if (!$exists) {
                    fputcsv($fp, ['part1_score', 'part2_score', 'part3_score', 'major_name']);
                }
                // ensure we have three numeric scores
                // Append stored normalized scores to dataset so retraining remains in-range
                $p1 = isset($scores[0]) ? intval($scores[0]) : 0;
                $p2 = isset($scores[1]) ? intval($scores[1]) : 0;
                $p3 = isset($scores[2]) ? intval($scores[2]) : 0;
                $mname = is_string($major) ? $major : strval($major ?? '');
                fputcsv($fp, [$p1, $p2, $p3, $mname]);
                fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    } catch (Exception $e) {
        // don't break main flow if append fails; optionally log error
        if (function_exists('error_log')) {
            error_log('dataset append failed: ' . $e->getMessage());
        }
    }

    echo json_encode([
        "success" => true,
        "major" => $major,
        "confidence" => $confidence,
        // return stored scores (already normalized by calculate_part_result) and original raw
        "scores" => $scores,
        "raw_scores" => $scores
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "System error", "error" => $e->getMessage()]);
}
            
