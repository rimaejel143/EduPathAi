<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

header("Content-Type: application/json");

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Use POST method"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$full_name = trim($data['full_name'] ?? '');
$email     = trim($data['email'] ?? '');
$password  = $data['password'] ?? '';

if (!$full_name || !$email || !$password) {
    echo json_encode([
        "success" => false,
        "message" => "All fields are required"
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email"]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(["success" => false, "message" => "Weak password"]);
    exit;
}

try {
    // Check email exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Email already exists"
        ]);
        exit;
    }

    // Insert user
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, password, user_type, created_at) 
        VALUES (?, ?, ?, 'student', NOW())
    ");

    $stmt->execute([$full_name, $email, $hashed]);
    $user_id = $pdo->lastInsertId();

    // Start session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['user_type'] = "student";

    echo json_encode([
        "success" => true,
        "message" => "Account created successfully",
        "user_id" => $user_id,
        "full_name" => $full_name,
        "email" => $email,
        "user_type" => "student"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false, 
        "message" => "Registration failed",
        "error" => $e->getMessage()
    ]);
}
