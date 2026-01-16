<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(["success" => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

echo json_encode([
    "success" => true,
    "full_name" => $user['full_name'],
    "email" => $user['email']
]);

