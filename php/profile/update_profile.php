<?php
require_once __DIR__ . '/../auth/check_session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
  die("Unauthorized");
}

$userId = $_SESSION['user_id'];

/* basic validation */
$fullName = trim($_POST['full_name'] ?? '');
$email    = trim($_POST['email'] ?? '');

if ($fullName === '' || $email === '') {
  die("All fields are required");
}

/* update profile */
$stmt = $pdo->prepare("
  UPDATE users
  SET full_name = ?, email = ?
  WHERE user_id = ?
");
$stmt->execute([
  $fullName,
  $email,
  $userId
]);
$_SESSION['full_name'] = $fullName;
$_SESSION['email'] = $email;

header("Location: my_profile.php");
exit;
