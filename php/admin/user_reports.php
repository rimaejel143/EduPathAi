<?php
require_once __DIR__ . '/admin_auth_check.php';
require_once __DIR__ . '/../config/db.php';
ensure_admin();
/* allow students only */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
  header("Location: /SeniorEducation/SeniorEducation/sign_in.html");
  exit;
}

$adminName = $_SESSION['full_name'] ?? 'Admin';

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
  <title>EduPathAI - User Reports</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

<style>
      :root {
        --primary-blue-dark: #1b3a5e;
        --primary-blue-light: #4e7ea7;
        --accent-yellow: #fdc80a;
        --text-color: #333;
      }

      .text-warning {
        --bs-text-opacity: 1;
        color: var(--accent-yellow) !important;
      }

      .admin-navbar {
        background-color: var(--primary-blue-dark);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }

      .admin-navbar .navbar-brand {
        display: flex;
        align-items: center;
        text-decoration: none;
      }

      .admin-navbar .navbar-brand .logo-icon {
        color: var(--accent-yellow) !important;
      }

      .admin-layout {
        height: calc(100vh - 56px);
      }

      .sidebar {
        width: 250px;
        min-width: 250px;
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
        color: var(--primary-blue-light);
        background-color: #e9ecef;
      }
      .sidebar .nav-item.active .nav-link {
        color: var(--primary-blue-dark);
        background-color: #e9ecef;
        border-left-color: var(--accent-yellow);
        font-weight: bold;
      }

      .main-content {
        flex-grow: 1;
        overflow-y: auto;
        background-color: #fff;
      }

      .kpi-card {
        border: none;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        height: 100%;
      }
      .kpi-icon {
        float: right;
        font-size: 2rem;
        color: var(--primary-blue-light);
        opacity: 0.6;
      }
      .kpi-card .card-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary-blue-dark);
      }

      .chart-card {
        border: none;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
      }

      .pie-chart-mock {
        height: 250px;
        background-color: #eee;
        border-radius: 50%;
        width: 250px;
        margin: 0 auto;
        background-image: conic-gradient(
          #3f51b5 0deg 25%,
          #8bc34a 25% 43%,
          #ffeb3b 43% 58%,
          #9e9e9e 58% 72%,
          #673ab7 72% 85%,
          #ff9800 85% 95%
        );
        border: 5px solid #fff;
      }

      .legend-item {
        line-height: 2.2;
        font-size: 1rem;
        color: var(--text-color);
      }
      .legend-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 10px;
      }

      .legend-dot.blue { background-color: #3f51b5; }
      .legend-dot.green { background-color: #8bc34a; }
      .legend-dot.yellow { background-color: #ffeb3b; }
      .legend-dot.gray { background-color: #9e9e9e; }
      .legend-dot.purple { background-color: #673ab7; }
      .legend-dot.orange { background-color: #ff9800; }

      .admin-table th {
        background-color: #f1f1f1;
        color: var(--primary-blue-dark);
        font-weight: 600;
      }
      .view-report-btn {
        background-color: var(--primary-blue-dark);
        color: white;
        border: none;
        transition: background-color 0.3s;
      }
      .view-report-btn:hover {
        background-color: var(--primary-blue-light);
      }
    </style>
</head>

<body>

<nav class="navbar navbar-dark admin-navbar">
  <div class="container-fluid">
    <a class="navbar-brand" href="/SeniorEducation/SeniorEducation/index.html">
       <i class="fas fa-brain me-2 fs-4 logo-icon"></i>
      <strong>Edu<span style="color:#fdc80a">PathAI</span></strong>
           
    </a>

    <div class="d-flex align-items-center">
      <span class="text-white me-3">Admin Panel</span>

     <a href="/SeniorEducation/SeniorEducation/php/profile/my_profile.php" class="me-2">
  <img src="<?= $profileImage ?>"
       width="35"
       height="35"
       class="rounded-circle border border-light"
       style="object-fit:cover">
</a>

      <a class="btn btn-sm btn-outline-light" href="admin_logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="d-flex">
  <!-- SIDEBAR -->
  <div class="sidebar">
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link" href="admin_dashboard.php">
          <i class="fas fa-home me-2"></i> Dashboard
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="questions_weights.php">
          <i class="fas fa-question-circle me-2"></i> Questions & Weights
        </a>
      </li>

      <li class="nav-item active">
        <a class="nav-link" href="user_reports.php">
          <i class="fas fa-chart-bar me-2"></i> User Reports
        </a>
      </li>
<li class="nav-item">
            <a class="nav-link" href="manage_forum.php">
              <i class="fas fa-comments me-2"></i> Manage Forum
            </a>
          </li>
           <li class="nav-item ">
            <a class="nav-link" href="manage_majors.php">
              <i class="fas fa-graduation-cap me-2"></i> Manage majors
            </a>
          </li>
      <li class="nav-item">
        <a class="nav-link" href="contact_messages.php">
          <i class="fas fa-envelope me-2"></i> Contact Messages
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="settings.php">
          <i class="fas fa-cog me-2"></i> Settings
        </a>
      </li>
    </ul>
  </div>

  <!-- CONTENT -->
  <div class="container py-5 flex-grow-1">
    <h2 class="text-center mb-4">All Student Final Results</h2>

    <!-- SEARCH -->
    <div class="mb-3 w-50">
      <input id="searchInput" class="form-control"
             placeholder="Search by name, user ID, assessment ID, major or date">
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover text-center align-middle">
        <thead>
          <tr>
            <th>User ID</th>
            <th>Full Name</th>
            <th>Assessment ID</th>
            <th>Major</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody id="reports-table-body">
          <tr>
            <td colspan="6">Loading reports...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
fetch("fetch_reports.php", { credentials: 'same-origin' })
  .then(res => res.json())
  .then(data => {
    const tbody = document.getElementById("reports-table-body");
    tbody.innerHTML = "";

    if (data.success && data.reports.length) {
      data.reports.forEach(r => {
        tbody.innerHTML += `
          <tr>
            <td>${r.user_id}</td>
            <td>${r.full_name}</td>
            <td>${r.student_assessment_id}</td>
            <td>${r.major_name}</td>
            <td>${new Date(r.created_at).toLocaleDateString()}</td>
            <td>
              <a href="admin_view_report.php?assessment_id=${r.student_assessment_id}"
                 class="btn btn-sm btn-primary">
                View
              </a>
            </td>
          </tr>
        `;
      });
    } else {
      tbody.innerHTML = `<tr><td colspan="6">No reports found</td></tr>`;
    }
  });

// SEARCH FILTER
document.getElementById("searchInput").addEventListener("input", function () {
  const v = this.value.toLowerCase();
  document.querySelectorAll("#reports-table-body tr").forEach(tr => {
    tr.style.display = tr.innerText.toLowerCase().includes(v) ? "" : "none";
  });
});
</script>

</body>
</html>
