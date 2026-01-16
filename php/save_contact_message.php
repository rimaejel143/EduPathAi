<?php
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

if (
  empty($_POST['full_name']) ||
  empty($_POST['email']) ||
  empty($_POST['message'])
) {
  echo json_encode([
    'success' => false,
    'message' => 'Missing required fields'
  ]);
  exit;
}

$full_name = trim($_POST['full_name']);
$email     = trim($_POST['email']);
$subject   = trim($_POST['subject'] ?? '');
$message   = trim($_POST['message']);

try {
  $stmt = $pdo->prepare("
    INSERT INTO contact_messages
      (full_name, email, subject, message, created_at)
    VALUES (?, ?, ?, ?, NOW())
  ");
  $stmt->execute([$full_name, $email, $subject, $message]);

  echo json_encode(['success' => true]);

} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Database error'
  ]);
}

