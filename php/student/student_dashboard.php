<?php


require_once __DIR__ . '/../auth/check_session.php';
require_once __DIR__ . '/../config/db.php';

/* allow students only */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
  header("Location: /SeniorEducation/SeniorEducation/sign_in.html");
  exit;
}

$studentName = $_SESSION['full_name'] ?? 'Student';

/* get profile image from DB */
$stmt = $pdo->prepare("SELECT profile_image FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$imgFile = $stmt->fetchColumn() ?: 'default_profile.png';

$profileImage = '/SeniorEducation/SeniorEducation/imgs/' . $imgFile;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Dashboard - EduPathAI</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

  <style>
    :root {
      --primary-blue-dark: #1b3a5e;
      --primary-blue-light: #4e7ea7;
      --accent-yellow: #fdc80a;
    }

    .navbar-dark { background-color: var(--primary-blue-dark); }

    .sidebar {
      width: 250px;
      background-color: #f8f9fa;
      border-right: 1px solid #ddd;
      padding-top: 20px;
    }

    .sidebar .nav-link {
      color: var(--primary-blue-dark);
      padding: 10px 15px;
      border-left: 3px solid transparent;
    }

    .sidebar .nav-link:hover {
      background-color: #e9ecef;
    }

    .sidebar .active .nav-link {
      border-left-color: var(--accent-yellow);
      font-weight: bold;
    }

    .main-content {
      flex-grow: 1;
      padding: 20px;
      background: #fff;
    }

    .kpi-card {
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,.05);
    }

    .kpi-icon {
      float: right;
      font-size: 2rem;
      color: var(--primary-blue-light);
      opacity: .6;
    }

    .pie-chart {
      width: 250px;
      height: 250px;
      border-radius: 50%;
      background: #eee;
      margin: auto;
    }

    .legend-dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      display: inline-block;
      margin-right: 8px;
    }
  </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/SeniorEducation/SeniorEducation/index.html">
      <i class="fas fa-brain text-warning me-2"></i>
      <strong>Edu<span class="text-warning">PathAI</span></strong>
    </a>

    <div class="d-flex align-items-center gap-3 text-white">
      <span><?= htmlspecialchars($studentName) ?></span>

      <!-- PROFILE IMAGE -->
      <a href="/SeniorEducation/SeniorEducation/php/profile/my_profile.php"
         title="My Profile">
        <img src="<?= $profileImage ?>"
             width="40" height="40"
             class="rounded-circle border border-2 border-light"
             style="object-fit:cover">
      </a>

      <!-- LOGOUT -->
      <a href="/SeniorEducation/SeniorEducation/php/auth/logout.php"
         class="btn btn-sm btn-outline-light">
        Logout
      </a>
    </div>
  </div>
</nav>

<div class="d-flex" style="height:calc(100vh - 56px)">

  <!-- SIDEBAR -->
  <div class="sidebar">
    <ul class="nav flex-column">
      <li class="nav-item active">
        <a class="nav-link">
          <i class="fas fa-home me-2"></i> Dashboard
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="../profile/my_profile.php">
          <i class="fas fa-user me-2"></i> My Profile
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="/SeniorEducation/SeniorEducation/test.html">
          <i class="fas fa-file-alt me-2"></i> My Tests
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="/SeniorEducation/SeniorEducation/php/forum/forum.php">
          <i class="fas fa-file-alt me-2"></i> Forum
        </a>
      </li>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <main class="main-content">

    <!-- KPI -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card p-3 kpi-card">
          <i class="fas fa-file-alt kpi-icon"></i>
          <small>Total Tests</small>
          <h3 id="total-tests">0</h3>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3 kpi-card">
          <i class="fas fa-percent kpi-icon"></i>
          <small>Average Score</small>
          <h3 id="average-score">0%</h3>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3 kpi-card">
          <i class="fas fa-graduation-cap kpi-icon"></i>
          <small>Latest Major</small>
          <h3 id="latest-major">-</h3>
        </div>
      </div>
    </div>

    <!-- CHART -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="mb-3">My Major Results Distribution</h5>
        <div class="row">
          <div class="col-md-5">
            <div id="pie" class="pie-chart"></div>
          </div>
          <div class="col-md-7">
            <ul id="major-legend" class="list-unstyled"></ul>
          </div>
        </div>
      </div>
    </div>

    <!-- TABLE -->
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">My Test Results</h5>
        <table class="table table-striped table-sm">
          <thead>
            <tr>
              <th>Date</th>
              <th>Suggested Major</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="results-table"></tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
async function loadStudentDashboard() {
  const res = await fetch('student_stats.php');
  const data = await res.json();
  if (!data.success) return;

  document.getElementById('total-tests').textContent = data.total_tests;
  document.getElementById('average-score').textContent = data.average_score + '%';
  document.getElementById('latest-major').textContent = data.latest_major ?? '-';

  const tbody = document.getElementById('results-table');
  tbody.innerHTML = '';
  data.reports.forEach(r => {
    tbody.innerHTML += `
      <tr>
        <td>${r.created_at ? new Date(r.created_at).toLocaleDateString() : '-'}</td>
        <td>${r.major_name ?? '-'}</td>
        <td>
          <a href="student_view_result.php?assessment_id=${r.student_assessment_id}"
             class="btn btn-sm btn-primary">View</a>
        </td>
      </tr>`;
  });
  renderMajorDistribution(data.distribution);
}

document.addEventListener('DOMContentLoaded', loadStudentDashboard);
</script>
<script>
function renderMajorDistribution(distribution) {
  const pie = document.getElementById('pie');
  const legend = document.getElementById('major-legend');

  pie.innerHTML = '';
  legend.innerHTML = '';

  if (!distribution || distribution.length === 0) {
    pie.style.background = '#eee';
    return;
  }

  let total = distribution.reduce((sum, d) => sum + Number(d.total), 0);
  let start = 0;

  const colors = ['#4e7ea7', '#fdc80a', '#6c757d', '#198754', '#dc3545'];

  let gradients = distribution.map((d, i) => {
    let percent = (d.total / total) * 100;
    let from = start;
    let to = start + percent;
    start = to;
    return `${colors[i % colors.length]} ${from}% ${to}%`;
  });

  pie.style.background = `conic-gradient(${gradients.join(',')})`;

  distribution.forEach((d, i) => {
    legend.innerHTML += `
      <li class="mb-2">
        <span class="legend-dot" style="background:${colors[i % colors.length]}"></span>
        ${d.major_name} (${d.total})
      </li>`;
  });
}
</script>

</body>
</html>
