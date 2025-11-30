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

    // 3) Call AI Major Predictor
    $url = "http://localhost/SeniorEducation/php/api/predict_major.php";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["scores" => $scores]));
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

    $ai = json_decode($response, true);
    if ($ai === null) {
        echo json_encode(["success" => false, "message" => "AI failed", "details" => $response]);
        exit;
    }

    if (!isset($ai['success']) || $ai['success'] !== true) {
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
    echo json_encode([
        "success" => true,
        "major" => $major,
        "confidence" => $confidence,
        "scores" => $scores
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "System error", "error" => $e->getMessage()]);
}
            
