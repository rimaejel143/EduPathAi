<?php
require_once __DIR__ . '/../auth/check_session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
  die("Unauthorized");
}

$userId = $_SESSION['user_id'];

if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== 0) {
  die("No image selected");
}

/* allowed image types */
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
if (!in_array($_FILES['profile_image']['type'], $allowedTypes)) {
  die("Invalid image type");
}

/* upload directory */
$targetDir = __DIR__ . '/../../imgs/';
if (!is_dir($targetDir)) {
  mkdir($targetDir, 0777, true);
}

/* safe filename */
$extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
$fileName  = 'profile_' . $userId . '_' . time() . '.' . $extension;
$targetFile = $targetDir . $fileName;

/* move file */
if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
  die("Upload failed");
}

/* save filename in DB */
$stmt = $pdo->prepare("
  UPDATE users SET profile_image = ? WHERE user_id = ?
");
$stmt->execute([$fileName, $userId]);

header("Location: my_profile.php");
exit;
