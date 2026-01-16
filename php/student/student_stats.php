<?php
require_once __DIR__ . '/../auth/check_session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

/* تأكيد إنه طالب */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
  echo json_encode(['success' => false]);
  exit;
}

$studentId = (int)$_SESSION['user_id'];

/* ================= TOTAL TESTS ================= */
$stmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM student_assessment
  WHERE user_id = ?
");
$stmt->execute([$studentId]);
$totalTests = (int)$stmt->fetchColumn();

/* ================= AVERAGE SCORE =================
   Average confidence from final_result for this student
*/
$stmt = $pdo->prepare("
  SELECT AVG(fr.confidence)
  FROM final_result fr
  JOIN student_assessment sa
    ON sa.student_assessment_id = fr.student_assessment_id
  WHERE sa.user_id = ?
");
$stmt->execute([$studentId]);
$avgScore = round((float)$stmt->fetchColumn(), 2);

/* ================= LATEST MAJOR ================= */
$stmt = $pdo->prepare("
  SELECT m.major_name
  FROM final_result fr
  JOIN student_assessment sa
    ON sa.student_assessment_id = fr.student_assessment_id
  JOIN majors m
    ON m.major_id = fr.major_id
  WHERE sa.user_id = ?
  ORDER BY fr.created_at DESC
  LIMIT 1
");
$stmt->execute([$studentId]);
$latestMajor = $stmt->fetchColumn();

/* ================= RESULTS TABLE ================= */
$stmt = $pdo->prepare("
  SELECT
    sa.student_assessment_id AS student_assessment_id,
    fr.created_at,
    m.major_name
  FROM student_assessment sa
  LEFT JOIN final_result fr
    ON fr.student_assessment_id = sa.student_assessment_id
  LEFT JOIN majors m
    ON m.major_id = fr.major_id
  WHERE sa.user_id = ?
  ORDER BY fr.created_at DESC, sa.student_assessment_id DESC
");
$stmt->execute([$studentId]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= MAJOR DISTRIBUTION ================= */
$stmt = $pdo->prepare("
  SELECT
    m.major_name,
    COUNT(*) AS total
  FROM final_result fr
  JOIN student_assessment sa
    ON sa.student_assessment_id = fr.student_assessment_id
  JOIN majors m
    ON m.major_id = fr.major_id
  WHERE sa.user_id = ?
  GROUP BY m.major_name
  ORDER BY total DESC
");
$stmt->execute([$studentId]);
$distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= RESPONSE ================= */
echo json_encode([
  'success' => true,
  'total_tests' => $totalTests,
  'average_score' => $avgScore,
  'latest_major' => $latestMajor ?: '-',
  'reports' => $reports,
  'distribution' => $distribution
]);
