<?php
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data['email'] ?? '');
$code = trim($data['code'] ?? '');
$new_password = $data['new_password'] ?? '';

if (!$email || !$code || !$new_password) {
    echo json_encode(["success" => false, "message" => "All fields required"]);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(["success" => false, "message" => "Weak password"]);
    exit;
}

try {
    // Validate code
    $stmt = $pdo->prepare("
        SELECT * FROM password_reset_tokens
        WHERE email=? AND token=? AND used=0 AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$email, $code]);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(["success" => false, "message" => "Invalid or expired code"]);
        exit;
    }

    // Update password
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);

    $pdo->prepare("UPDATE users SET password=? WHERE email=?")
        ->execute([$hashed, $email]);

    // Mark token as used
    $pdo->prepare("UPDATE password_reset_tokens SET used=1 WHERE token=?")
        ->execute([$code]);

    echo json_encode([
        "success" => true,
        "message" => "Password reset successfully"
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error", "error" => $e->getMessage()]);
}
