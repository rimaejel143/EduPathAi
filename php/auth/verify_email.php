<?php
require_once __DIR__ . '/../config/db.php';

$token = $_GET['token'] ?? '';
$token = trim($token);

if (!$token) {
  die("Missing token.");
}

try {
  // 1) تأكد من وجود التوكن وعدم انتهاء الصلاحية وعدم التحقق سابقًا
  $stmt = $pdo->prepare("
    SELECT id, email, expires_at, verified_at
    FROM email_verifications
    WHERE token = ?
    LIMIT 1
  ");
  $stmt->execute([$token]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    die("Invalid token.");
  }

  if (!empty($row['verified_at'])) {
    die("Email already verified.");
  }

  if (strtotime($row['expires_at']) < time()) {
    die("Token expired.");
  }

  // 2) علّم التحقق داخل email_verifications
  $stmt = $pdo->prepare("
    UPDATE email_verifications
    SET verified_at = NOW()
    WHERE id = ?
  ");
  $stmt->execute([$row['id']]);

  // 3) (اختياري ولكن مُهم) حدّث users إذا عندك عمود is_verified
  // إذا ما عندك العمود، اتركيه كما هو.
  // جرّبي هذا فقط إذا أنت عاملة العمود:
  // ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0;

  $checkCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_verified'")->fetch();
  if ($checkCol) {
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
    $stmt->execute([$row['email']]);
  }

  echo "Email verified successfully. You can now sign in.";

} catch (Exception $e) {
  die("Verification failed: " . htmlspecialchars($e->getMessage()));
}


