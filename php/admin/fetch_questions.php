<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/admin_auth_check.php';

header("Content-Type: application/json");
ensure_admin();

$stmt = $pdo->query("
  SELECT question_id, part_id, question_text
  FROM questions
  ORDER BY part_id, question_id
");

echo json_encode([
  'success' => true,
  'questions' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);

