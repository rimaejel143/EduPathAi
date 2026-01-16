<?php
/**
 * LOAD PROGRESS ENDPOINT
 * 
 * Purpose:
 * - Fetches previously saved progress for a student resuming a test
 * - Returns: (1) last answered question number, (2) all saved answers as a map
 * 
 * Query parameters:
 * - part: The test part number (1, 2, or 3)
 * 
 * Response format:
 * {
 *   "success": true,
 *   "progress": {
 *     "part": 1,
 *     "last_question_number": 5,
 *     "answers": {
 *       "1": 4,    // question_id: selected_option
 *       "2": 3,
 *       "5": 2
 *     }
 *   }
 * }
 */

session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
  echo json_encode(['success' => false]);
  exit;
}

$userId = $_SESSION['user_id'];
$part = (int)($_GET['part'] ?? 1);

try {
  // Step 1: Get the latest assessment for this user
  // Each test session is a separate assessment record
  $assessmentStmt = $pdo->prepare("
  SELECT sa.student_assessment_id
  FROM student_assessment sa
  JOIN selectedanswers s
    ON s.student_assessment_id = sa.student_assessment_id
  WHERE sa.user_id = ?
  ORDER BY s.updated_at DESC
  LIMIT 1
");
$assessmentStmt->execute([$userId]);
$assessment = $assessmentStmt->fetch();

  if (!$assessment) {
    // No active assessment - return null (new test)
    echo json_encode([
      'success' => true,
      'progress' => null
    ]);
    exit;
  }

  $studentAssessmentId = $assessment['student_assessment_id'];

  // Step 2: Get the last answered question number from student_test_progress
  // This table stores progress metadata for quick resume
  $progressStmt = $pdo->prepare("
    SELECT question_number
    FROM student_test_progress
    WHERE user_id = ? AND part = ?
    ORDER BY updated_at DESC
    LIMIT 1
  ");
  $progressStmt->execute([$userId, $part]);
  $progressRow = $progressStmt->fetch(PDO::FETCH_ASSOC);

  $lastQuestionNumber = null;
  if ($progressRow) {
    $lastQuestionNumber = (int)$progressRow['question_number'];
  }

  // Step 3: Get ALL saved answers from selectedanswers for this assessment
  // This ensures we restore EVERY answer the user made, not just the last one
  $answersStmt = $pdo->prepare("
    SELECT question_id, selected_option
    FROM selectedanswers
    WHERE user_id = ? AND student_assessment_id = ?
    ORDER BY question_id ASC
  ");
  $answersStmt->execute([$userId, $studentAssessmentId]);
  $answersRows = $answersStmt->fetchAll(PDO::FETCH_ASSOC);

  // Convert to map: {question_id: selected_option} for easier frontend access
  // This allows the frontend to quickly find answers by question_id using: answers[qid]
  $answers = [];
  foreach ($answersRows as $row) {
    $answers[(int)$row['question_id']] = (int)$row['selected_option'];
  }

  // Step 4: Return progress data if any exists
  if ($lastQuestionNumber !== null || !empty($answers)) {
    echo json_encode([
      'success' => true,
      'progress' => [
        'part' => $part,
        'last_question_number' => $lastQuestionNumber,
        'answers' => $answers
      ]
    ]);
  } else {
    // No progress saved yet - return null
    echo json_encode([
      'success' => true,
      'progress' => null
    ]);
  }

} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>