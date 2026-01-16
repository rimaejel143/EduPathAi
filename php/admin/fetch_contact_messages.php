<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

try {
  $stmt = $pdo->query("
    SELECT full_name, email, subject, message, created_at
    FROM contact_messages
    ORDER BY created_at DESC
  ");

  echo json_encode([
    'success' => true,
    'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)
  ]);

} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Database error'
  ]);
}

