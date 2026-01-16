<?php
/**
 * SAVE PROGRESS ENDPOINT
 * 
 * Purpose:
 * - Saves all answered questions from a student to the database
 * - Stores progress metadata (last answered question) for quick resume
 * 
 * Expected POST payload:
 * {
 *   "part": 1,
 *   "current_question_number": 5,
 *   "answers": [
 *     {"question_id": 1, "selected_option": 4},
 *     {"question_id": 2, "selected_option": 3},
 *     ...
 *   ]
 * }
 * 
 * Database operations:
 * 1. UPSERT into selectedanswers (for AI scoring - do NOT break this)
 * 2. INSERT/UPDATE into student_test_progress (for quick resume)
 */

session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
  echo json_encode(['success' => false]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$userId = $_SESSION['user_id'];
$part = (int)($data['part'] ?? 0);
$currentQuestionNumber = (int)($data['current_question_number'] ?? 0);
$answers = $data['answers'] ?? [];

if (!$part || empty($answers)) {
  echo json_encode(['success' => false, 'message' => 'Missing part or answers data']);
  exit;
}

try {
  // Get the latest assessment for this user
  $stmt = $pdo->prepare("
    SELECT student_assessment_id 
    FROM student_assessment
    WHERE user_id = ? 
    ORDER BY start_time DESC
    LIMIT 1
  ");
  $stmt->execute([$userId]);
  $assessment = $stmt->fetch();

  if (!$assessment) {
    echo json_encode(['success' => false, 'message' => 'No active assessment']);
    exit;
  }

  $studentAssessmentId = $assessment['student_assessment_id'];

  // Save each answer to selectedanswers table (UPSERT)
  // This preserves data integrity with AI scoring logic
  $answerStmt = $pdo->prepare("
    INSERT INTO selectedanswers (user_id, student_assessment_id, question_id, selected_option, created_at, updated_at)
    VALUES (?, ?, ?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      selected_option = VALUES(selected_option),
      updated_at = NOW()
  ");

  foreach ($answers as $item) {
    $questionId = (int)($item['question_id'] ?? 0);
    $selectedOption = (int)($item['selected_option'] ?? -1);

    if ($questionId <= 0 || $selectedOption < 0) {
      continue; // Skip invalid entries
    }

    $answerStmt->execute([$userId, $studentAssessmentId, $questionId, $selectedOption]);
  }

  // Also save the last answered question number to student_test_progress for quick resume
  $progressStmt = $pdo->prepare("
    INSERT INTO student_test_progress (user_id, part, question_number, answer, updated_at)
    VALUES (?, ?, ?, 0, NOW())
    ON DUPLICATE KEY UPDATE
      question_number = VALUES(question_number),
      updated_at = NOW()
  ");
  $progressStmt->execute([$userId, $part, $currentQuestionNumber]);

  echo json_encode(['success' => true]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
