<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Use POST"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$full_name = trim($data['full_name'] ?? '');
$email     = trim($data['email'] ?? '');
$password  = $data['password'] ?? '';

if (!$full_name || !$email || !$password) {
    echo json_encode(["success" => false, "message" => "All fields required"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email"]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(["success" => false, "message" => "Password too short"]);
    exit;
}

try {
    // check email exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => false, "message" => "Email already exists"]);
        exit;
    }

    // insert user
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, password, user_type, created_at)
        VALUES (?, ?, ?, 'student', NOW())
    ");
    $stmt->execute([$full_name, $email, $hashed]);

    // create verification token
    $token = bin2hex(random_bytes(16));

    $stmt = $pdo->prepare("
  INSERT INTO email_verifications (email, token, expires_at)
  VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY))
  ON DUPLICATE KEY UPDATE
    token = VALUES(token),
    expires_at = VALUES(expires_at),
    verified_at = NULL
");
$stmt->execute([$email, $token]);


    require_once __DIR__ . '/send_email.php';

    $verifyLink = "http://localhost/SeniorEducation/SeniorEducation/php/auth/verify_email.php?token=$token";

    $subject = "Verify your EduPathAI account";
    $body = "
        <p>Hi $full_name,</p>
        <p>Please verify your email:</p>
        <a href='$verifyLink'>Verify Email</a>
    ";

    send_email($email, $subject, $body);

    echo json_encode([
        "success" => true,
        "message" => "Account created. Check your email."
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Registration failed",
        "error" => $e->getMessage()
    ]);
}
