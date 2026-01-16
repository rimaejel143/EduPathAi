<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Only logged-in students may use the forum
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT category_id, name, description, created_at FROM forum_categories ORDER BY sort_order ASC, created_at DESC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'category_id' => (int)$r['category_id'],
            'name' => htmlspecialchars($r['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'description' => htmlspecialchars($r['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'created_at' => $r['created_at']
        ];
    }

    echo json_encode(['success' => true, 'categories' => $out]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
