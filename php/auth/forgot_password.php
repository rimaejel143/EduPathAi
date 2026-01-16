<?php
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');

if (!$email) {
    echo json_encode(["success" => false, "message" => "Email required"]);
    exit;
}

try {
    // Check email exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email=?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Email not found"]);
        exit;
    }

    // Generate 6 digit code
    $code = str_pad(random_int(0, 999999), 6, "0", STR_PAD_LEFT);

    // Remove old tokens for this email
    $pdo->prepare("DELETE FROM password_reset_tokens WHERE email=?")
        ->execute([$email]);

    // Insert new token
    $stmt = $pdo->prepare("
        INSERT INTO password_reset_tokens (email, token, expires_at, used, created_at)
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), 0, NOW())
    ");
    $stmt->execute([$email, $code]);

    echo json_encode([
        "success" => true,
        "message" => "Verification code generated",
        "code" => $code // for testing (like you want)
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error"]);
}
