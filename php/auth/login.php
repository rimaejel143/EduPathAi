<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Use POST"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Email and password required"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT user_id, full_name, email, password, user_type
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(["success" => false, "message" => "Invalid email or password"]);
        exit;
    }

    // ================= CREATE SESSION =================
    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['user_type'] = $user['user_type'];

    // ⭐ المهم للـ Student Dashboard
    if ($user['user_type'] === 'student') {
        $_SESSION['student_id']   = $user['user_id'];
        $_SESSION['student_name'] = $user['full_name'];
    }

    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "user_type" => $user['user_type']
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "DB error"]);
}
