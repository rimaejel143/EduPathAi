<?php
require_once __DIR__ . '/admin_auth_check.php';
require_once __DIR__ . '/../config/db.php';

ensure_admin();

$assessment_id = $_GET['assessment_id'] ?? null;
if (!$assessment_id) {
    die('Invalid report');
}

/**
 * 1) Get user + final result
 */
$stmt = $pdo->prepare("
    SELECT 
        u.full_name,
        u.email,
        fr.created_at,
        m.major_name,
        fr.confidence
    FROM final_result fr
    JOIN student_assessment sa ON sa.student_assessment_id = fr.student_assessment_id
    JOIN users u ON u.user_id = sa.user_id
    LEFT JOIN majors m ON m.major_id = fr.major_id
    WHERE fr.student_assessment_id = ?
    LIMIT 1
");
$stmt->execute([$assessment_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    die('Report not found');
}

/**
 * 2) Get part scores
 */
$stmt = $pdo->prepare("
    SELECT part_number, total_score
    FROM assessment_part_results
    WHERE student_assessment_id = ?
    ORDER BY part_number
");
$stmt->execute([$assessment_id]);
$scores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// mapping
$logic      = $scores[1] ?? 0;
$creativity = $scores[2] ?? 0;
$social     = $scores[3] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Mini Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container mt-5">

  <div class="card shadow">
    <div class="card-body">

      <h4 class="mb-4">Student Mini Report</h4>

      <p><strong>Name:</strong> <?= htmlspecialchars($report['full_name']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($report['email']) ?></p>
      <p><strong>Date:</strong> <?= date('Y-m-d', strtotime($report['created_at'])) ?></p>

      <hr>

      <p><strong>Predicted Major:</strong> <?= htmlspecialchars($report['major_name']) ?></p>
      <p><strong>Confidence:</strong> <?= number_format($report['confidence'], 2) ?>%</p>

      <hr>

      <h6>Skill Breakdown</h6>
      <ul>
        <li>Logic: <?= $logic ?>%</li>
        <li>Creativity: <?= $creativity ?>%</li>
        <li>Social: <?= $social ?>%</li>
      </ul>

      <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back</a>

    </div>
  </div>

</div>
</body>
</html>

