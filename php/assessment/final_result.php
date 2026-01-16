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
    // 1) Get latest assessment for user
    $stmt = $pdo->prepare("
        SELECT student_assessment_id 
        FROM student_assessment 
        WHERE user_id = ? 
        ORDER BY student_assessment_id DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $sa_row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sa_row) {
        echo json_encode(["success" => false, "message" => "No assessment found"]);
        exit;
    }

    $student_assessment_id = (int)$sa_row['student_assessment_id'];

    // 2) Fetch part scores
    $stmt = $pdo->prepare("
        SELECT part_number, total_score 
        FROM assessment_part_results 
        WHERE student_assessment_id = ?
        ORDER BY part_number ASC
    ");
    $stmt->execute([$student_assessment_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $score_map = [];
    foreach ($rows as $r) {
        $pn = (int)$r['part_number'];
        if ($pn >= 1 && $pn <= 3) {
            $score_map[$pn] = (float)$r['total_score'];
        }
    }

    if (count($score_map) < 3) {
        echo json_encode([
            "success" => false,
            "message" => "All 3 parts must be completed",
            "found_parts" => array_keys($score_map)
        ]);
        exit;
    }

    // Raw scores (DB values)
    $rawScores = [
        $score_map[1],
        $score_map[2],
        $score_map[3]
    ];

    // 🔹 NORMALIZATION (CRITICAL FIX)
    // Max possible scores per part (adjustable)
    $maxScores = [
        1 => 500,    // Logic
        2 => 1200,   // Analytical
        3 => 50      // Personality / Social
    ];

    $normScores = [
        round(($rawScores[0] / $maxScores[1]) * 100, 2),
        round(($rawScores[1] / $maxScores[2]) * 100, 2),
        round(($rawScores[2] / $maxScores[3]) * 100, 2)
    ];

    // Safety clamp (0–100)
    $normScores = array_map(fn($v) => max(0, min(100, $v)), $normScores);

    // Log payload
    $payload = ["scores" => $normScores];
    @file_put_contents(
        __DIR__ . "/log_predict_payload.txt",
        date("Y-m-d H:i:s") . " PAYLOAD: " . json_encode($payload) . "\n",
        FILE_APPEND
    );

    // 3) Call AI
    $url = "http://localhost/SeniorEducation/SeniorEducation/php/api/predict_major.php";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15
    ]);

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if (!$response) {
        echo json_encode([
            "success" => false,
            "message" => "AI failed",
            "details" => $curlErr
        ]);
        exit;
    }

    $ai = json_decode($response, true);

    if (!$ai || !isset($ai['major'])) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid AI response",
            "details" => $response
        ]);
        exit;
    }

    $major = $ai['major'];
    $major_id = $ai['major_id'] ?? null;
    $confidence = $ai['confidence'] ?? null;

    // 4) Save result
   $stmt = $pdo->prepare("
    INSERT INTO final_result (student_assessment_id, major_id, confidence, created_at)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        major_id = VALUES(major_id),
        confidence = VALUES(confidence),
        created_at = NOW()
");
$stmt->execute([
    $student_assessment_id,
    $major_id,
    $confidence
]);

    // 5) Return response
    // ===== CLEANUP: clear resume progress & answers for this completed assessment =====
    try {
        // Delete quick-resume rows for this user so they cannot resume this assessment
        $delProgress = $pdo->prepare("DELETE FROM student_test_progress WHERE user_id = ?");
        $delProgress->execute([$user_id]);

        // Delete selected answers for THIS assessment only
        $delAnswers = $pdo->prepare("DELETE FROM selectedanswers WHERE user_id = ? AND student_assessment_id = ?");
        $delAnswers->execute([$user_id, $student_assessment_id]);

        // Mark the student_assessment as completed (safe - catch if column absent)
        $upd = $pdo->prepare("UPDATE student_assessment SET completed_at = NOW() WHERE student_assessment_id = ?");
        $upd->execute([$student_assessment_id]);

        @file_put_contents(__DIR__ . "/log_final_cleanup.txt", date("Y-m-d H:i:s") . " CLEANUP: user=" . intval($user_id) . " assessment=" . intval($student_assessment_id) . "\n", FILE_APPEND);
    } catch (Exception $e) {
        // Log and continue — do NOT fail the user-facing result if cleanup has issues
        @file_put_contents(__DIR__ . "/log_final_cleanup.txt", date("Y-m-d H:i:s") . " CLEANUP ERROR: " . $e->getMessage() . " user=" . intval($user_id) . " assessment=" . intval($student_assessment_id) . "\n", FILE_APPEND);
    }

    echo json_encode([
        "success" => true,
        "major" => $major,
        "confidence" => $confidence,
        "scores" => $normScores,
        "raw_scores" => $rawScores
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "System error",
        "error" => $e->getMessage()
    ]);
}
