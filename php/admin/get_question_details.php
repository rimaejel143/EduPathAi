<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/admin_auth_check.php';

header('Content-Type: application/json');
ensure_admin();

if (!isset($_GET['question_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing question_id'
    ]);
    exit;
}

$question_id = (int) $_GET['question_id'];

try {
    /* ===============================
       1. Get question basic info
    =============================== */
    $stmt = $pdo->prepare("
        SELECT question_id, question_text, part_id
        FROM questions
        WHERE question_id = ?
    ");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {
        echo json_encode([
            'success' => false,
            'message' => 'Question not found'
        ]);
        exit;
    }

    $part_id = (int) $question['part_id'];

    /* ===============================
       2. Get TRAIT weights (Part 1)
    =============================== */
    $traitWeights = [];
    if ($part_id === 1) {
        $stmt = $pdo->prepare("
            SELECT trait_code, weight
            FROM question_trait_weights
            WHERE question_id = ?
        ");
        $stmt->execute([$question_id]);
        $traitWeights = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ===============================
       3. Get MAJOR weights (optional)
    =============================== */
    $majorWeights = [];
    $stmt = $pdo->prepare("
        SELECT major_code, weight
        FROM question_major_weights
        WHERE question_id = ?
    ");
    $stmt->execute([$question_id]);
    $majorWeights = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ===============================
       4. Response
    =============================== */
    echo json_encode([
        'success' => true,
        'question' => $question,
        'trait_weights' => $traitWeights,
        'major_weights' => $majorWeights
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
}

