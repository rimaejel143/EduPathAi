<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$post_id = (int)($input['post_id'] ?? 0);
$body = trim($input['body'] ?? '');

if ($post_id <= 0 || $body === '') {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO forum_replies (post_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([ $post_id, $_SESSION['user_id'], $body ]);
    $replyId = (int)$pdo->lastInsertId();

    echo json_encode(['success' => true, 'reply_id' => $replyId]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
