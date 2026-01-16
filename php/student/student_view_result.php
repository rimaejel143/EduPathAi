<?php
require_once __DIR__ . '/../auth/check_session.php';
require_once __DIR__ . '/../config/db.php';

/* تأكيد إنه طالب */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
  die("Unauthorized");
}

$studentId = $_SESSION['user_id'];
$assessmentId = $_GET['assessment_id'] ?? null;

if (!$assessmentId) {
  die("Invalid assessment");
}

/* ===== Security check: assessment belongs to student ===== */
$stmt = $pdo->prepare("
  SELECT student_assessment_id
  FROM student_assessment
  WHERE student_assessment_id = ? AND user_id = ?
");
$stmt->execute([$assessmentId, $studentId]);

if (!$stmt->fetch()) {
  die("Access denied");
}

/* ===== Final result with major name ===== */
$stmt = $pdo->prepare("
  SELECT 
    m.major_name,
    fr.confidence,
    fr.created_at
  FROM final_result fr
  JOIN majors m
    ON m.major_id = fr.major_id
  WHERE fr.student_assessment_id = ?
  ORDER BY fr.created_at DESC
  LIMIT 1
");
$stmt->execute([$assessmentId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Test Result</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
  <div class="card shadow-sm">
    <div class="card-body">
      <h3 class="mb-4">Assessment Result</h3>

      <?php if ($result): ?>
        <p>
          <strong>Suggested Major:</strong>
          <?= htmlspecialchars($result['major_name']) ?>
        </p>

        <p>
          <strong>Confidence:</strong>
          <?= round($result['confidence'], 2) ?>%
        </p>

        <p>
          <strong>Date:</strong>
          <?= $result['created_at']
            ? date('Y-m-d', strtotime($result['created_at']))
            : '-' ?>
        </p>
      <?php else: ?>
        <p>No result available for this test.</p>
      <?php endif; ?>

      <a href="student_dashboard.php" class="btn btn-secondary mt-3">
        Back to Dashboard
      </a>
    </div>
  </div>
</div>

</body>
</html>
