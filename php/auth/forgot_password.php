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
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            "success" => true,
            "message" => "If email exists, code will be sent"
        ]);
        exit;
    }

    // Generate 6-digit code
    $code = rand(100000, 999999);

    // Delete old codes
    $pdo->prepare("DELETE FROM password_reset_tokens WHERE email=?")
        ->execute([$email]);

    // Insert new code
    $stmt = $pdo->prepare("
        INSERT INTO password_reset_tokens (email, token, expires_at, used)
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), 0)
    ");
    $stmt->execute([$email, $code]);

    echo json_encode([
        "success" => true,
        "message" => "Reset code generated",
        "code" => $code   // لعدم وجود SMTP – فقط للـ debugging
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error", "error" => $e->getMessage()]);
}
