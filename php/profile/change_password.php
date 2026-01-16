<?php
require_once __DIR__ . '/../auth/check_session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
  die("Unauthorized");
}

$userId = $_SESSION['user_id'];

/* get old password hash */
$stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$hash = $stmt->fetchColumn();

if (!$hash || !password_verify($_POST['current_password'], $hash)) {
  die("Wrong current password");
}

$newHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
  UPDATE users SET password = ? WHERE user_id = ?
");
$stmt->execute([$newHash, $userId]);

header("Location: my_profile.php");
exit;
