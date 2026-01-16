<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$out = [
    'success' => true,
    'note' => 'Debug endpoint: latest student_assessment and assessment_part_results',
    'timestamp' => date('c')
];

try {
    // Query 1: latest assessments
    $q1 = "SELECT student_assessment_id AS id, user_id, created_at FROM student_assessment ORDER BY created_at DESC LIMIT 50";
    $stmt = $pdo->prepare($q1);
    $stmt->execute();
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query 2: part results ordered by assessment desc then part asc
    $q2 = "SELECT student_assessment_id, part_number, total_score FROM assessment_part_results ORDER BY student_assessment_id DESC, part_number ASC LIMIT 500";
    $stmt2 = $pdo->prepare($q2);
    $stmt2->execute();
    $parts = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $out['assessments'] = $assessments;
    $out['parts'] = $parts;

    // Also write to a debug log for later inspection
    @file_put_contents(__DIR__ . "/log_debug_assessment.txt", date('Y-m-d H:i:s') . "\nASSESSMENTS: " . json_encode($assessments) . "\nPARTS: " . json_encode($parts) . "\n\n", FILE_APPEND);

    echo json_encode($out);
    exit;

} catch (Exception $e) {
    $err = ['success' => false, 'message' => 'DB error', 'error' => $e->getMessage()];
    @file_put_contents(__DIR__ . "/log_debug_assessment.txt", date('Y-m-d H:i:s') . " ERROR: " . json_encode($err) . "\n", FILE_APPEND);
    echo json_encode($err);
    exit;
}

?>
