<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/admin_auth_check.php';

header('Content-Type: application/json');
ensure_admin();

/* ================= VALIDATION ================= */
if (
    !isset($_POST['question_id']) ||
    !isset($_POST['question_text'])
) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing parameters'
    ]);
    exit;
}

$question_id = (int) $_POST['question_id'];
$question_text = trim($_POST['question_text']);

if ($question_id <= 0 || $question_text === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data'
    ]);
    exit;
}

/* ================= UPDATE ================= */
try {
    $stmt = $pdo->prepare("
        UPDATE questions
        SET question_text = ?
        WHERE question_id = ?
    ");
    $stmt->execute([$question_text, $question_id]);

    echo json_encode([
        'success' => true
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
}
