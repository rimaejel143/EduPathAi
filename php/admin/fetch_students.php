<?php
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json");

try {
    $stmt = $pdo->query("
        SELECT user_id, full_name, email, user_type, created_at
        FROM users
        WHERE user_type = 'student'
        ORDER BY created_at DESC
    ");

    echo json_encode([
        "success" => true,
        "students" => $stmt->fetchAll()
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error"
    ]);
}

