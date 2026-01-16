<?php


require_once __DIR__ . '/../auth/check_session.php';
require_once __DIR__ . '/../config/db.php';

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
    <title>Admin Panel - EduPathAI</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    />

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
          <span class="navbar-text me-3 text-white">Admin Panel</span>
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

    <div class="d-flex admin-layout">
      <div class="sidebar">
        <ul class="nav flex-column">
          <li class="nav-item active">
            <a class="nav-link" href="#">
              <i class="fas fa-home me-2"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="questions_weights.php">
              <i class="fas fa-question-circle me-2"></i> Questions & Weights
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="user_reports.php">
              <i class="fas fa-chart-bar me-2"></i> User Reports
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="manage_forum.php">
              <i class="fas fa-comments me-2"></i> Manage Forum
            </a>
          </li>
          <li>
            <a href="manage_majors.php" class="nav-link">
    <i class="fas fa-graduation-cap me-2"></i> Manage Majors
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

      <main class="main-content p-4">
        <div class="container-fluid">
          <!-- KPI Cards -->
          <div class="row mb-4 kpi-cards-row">
            <div class="col-md-3">
              <div class="card kpi-card">
                <div class="card-body">
                  <i class="fas fa-users kpi-icon"></i>
                  <h6 class="card-subtitle mb-2 text-muted">Total Users</h6>
                  <h3 class="card-title" id="total-users">0</h3>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card kpi-card">
                <div class="card-body">
                  <i class="fas fa-check-circle kpi-icon"></i>
                  <h6 class="card-subtitle mb-2 text-muted">Completed Tests</h6>
                  <h3 class="card-title" id="completed-tests">0</h3>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card kpi-card">
                <div class="card-body">
                  <i class="fas fa-percent kpi-icon"></i>
                  <h6 class="card-subtitle mb-2 text-muted">Average Score</h6>
                  <h3 class="card-title" id="average-score">0%</h3>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card kpi-card">
                <div class="card-body">
                  <i class="fas fa-plus-circle kpi-icon"></i>
                  <h6 class="card-subtitle mb-2 text-muted">
                    Test Participants (Last 7 Days)
                  </h6>
                  <h3 class="card-title" id="new-users">0</h3>
                </div>
              </div>
            </div>
          <?php
// جلب إحصائيات سريعة من قاعدة البيانات
$total_posts = $pdo->query("SELECT COUNT(*) FROM Forum_Posts")->fetchColumn();
$total_comments = $pdo->query("SELECT COUNT(*) FROM Forum_Comments")->fetchColumn();
$total_majors = $pdo->query("SELECT COUNT(*) FROM Majors")->fetchColumn(); // السطر الجديد لجلب عدد التخصصات
$total_users = $pdo->query("SELECT COUNT(*) FROM Users")->fetchColumn();
?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 p-3 mb-3" style="border-radius: 12px; background-color: #0eaff4ff; color: white;">
            <div class="d-flex align-items-center">
                <div class="icon-box bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                    <i class="fas fa-graduation-cap text-primary fs-4"></i>
                </div>
                <div>
                    <small class="text-muted fw-bold">Available Majors</small>
                    <h3 class="fw-bold mb-0" style="color: #1b3a5e;"><?php echo $total_majors; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 p-3 mb-3" style="border-radius: 12px; background-color: #28a745; color: white;">
            <div class="d-flex align-items-center">
                <div class="icon-box bg-white bg-opacity-25 p-3 rounded-circle me-3">
                    <i class="fas fa-comments text-white fs-4"></i>
                </div>
                <div>
                    <small class="fw-bold opacity-75">Forum Topics</small>
                    <h3 class="fw-bold mb-0"><?php echo $total_posts; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 p-3 mb-3" style="border-radius: 12px; background-color: #ffc107; color: #1b3a5e;">
            <div class="d-flex align-items-center">
                <div class="icon-box bg-dark bg-opacity-10 p-3 rounded-circle me-3">
                    <i class="fas fa-reply-all fs-4"></i>
                </div>
                <div>
                    <small class="fw-bold opacity-75">Total Replies</small>
                    <h3 class="fw-bold mb-0"><?php echo $total_comments; ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

          <!-- Chart Section -->
          <div class="card chart-card mb-4">
            <div class="card-body">
              <h4 class="card-title mb-4">
                Student Major Interest Distribution
              </h4>
              <div class="row">
                <div
                  class="col-md-5 d-flex align-items-center justify-content-center"
                >
                  <div class="pie-chart-mock"></div>
                </div>
                <div class="col-md-7 legend-container">
                  <ul class="list-unstyled" id="major-distribution">
                    <!-- سيتم تعبئته بالجافاسكريبت -->
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Tests Table -->
          <div class="card table-card">
            <div class="card-body">
              <h4 class="card-title mb-3">Recent Test Completions</h4>
              <button id="sortDateBtn" class="btn btn-sm btn-outline-primary mb-2">
  Sort: Newest → Oldest
</button>

              <div class="table-responsive">
                <table class="table table-striped table-sm admin-table">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Completion Date</th>
                      <th>Best Match</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody id="recent-tests-body">
                    <!-- سيتم تعبئته بالجافاسكريبت -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

   <script>
    let sortDesc = true; 
async function loadDashboardData() {
  try {
    const res = await fetch('admin_stats.php', {
      credentials: 'same-origin'
    });
    const stats = await res.json();
    console.log(stats); // مهم للـ debug

   if (stats.success) {
  document.getElementById('total-users').textContent = stats.total_students;
  document.getElementById('completed-tests').textContent = stats.completed_tests;
document.getElementById('average-score').textContent =
  stats.average_score.toFixed(2) + '%';
  document.getElementById('new-users').textContent = stats.new_users_this_week;

}


    const reportsRes = await fetch('fetch_reports.php', {
      credentials: 'same-origin'
    });
    const reportsData = await reportsRes.json();

if (reportsData.success) {
  const tableBody = document.getElementById('recent-tests-body');
  tableBody.innerHTML = '';

  // 🔹 نسخة من الداتا
  const reports = [...reportsData.reports];

  // 🔹 SORT BY DATE
  reports.sort((a, b) => {
    const da = new Date(a.created_at || 0);
    const db = new Date(b.created_at || 0);
    return sortDesc ? db - da : da - db;
  });

  let lastKey = null;

  reports.forEach(r => {
    const currentKey = `${r.full_name}|${r.major_name}`;

    // تخطي التكرار المتتالي
    if (currentKey === lastKey) return;
    lastKey = currentKey;

    tableBody.innerHTML += `
      <tr>
        <td>${r.full_name}</td>
        <td>${r.email}</td>
        <td>
          ${r.created_at && r.created_at !== '0000-00-00 00:00:00'
            ? new Date(r.created_at).toLocaleDateString()
            : '—'}
        </td>
        <td>${r.major_name}</td>
        <td>
          <a href="admin_view_report.php?assessment_id=${r.student_assessment_id}"
             class="btn btn-sm view-report-btn">
            View
          </a>
        </td>
      </tr>
    `;
  });
}


  } catch (e) {
    console.error(e);
  }
}
async function loadMajorDistribution() {
  try {
    const res = await fetch('admin_major_distribution.php', {
      credentials: 'same-origin'
    });
    const result = await res.json();

    if (!result.success) return;

    const data = result.data;
    const pie = document.querySelector('.pie-chart-mock');
    const legend = document.getElementById('major-distribution');

    legend.innerHTML = '';

    // 1) total students with results
    const total = data.reduce((sum, item) => sum + Number(item.total), 0);
    if (total === 0) return;

    // 2) prepare colors (نفس ألوان التصميم)
    const colors = ['#3f51b5','#8bc34a','#ffeb3b','#9e9e9e','#673ab7','#ff9800'];

    let currentDeg = 0;
    const gradients = [];

    data.forEach((item, index) => {
      const percent = ((item.total / total) * 100).toFixed(1);
      const deg = (percent / 100) * 360;

      const from = currentDeg;
      const to = currentDeg + deg;
      currentDeg += deg;

      // slice
      gradients.push(
        `${colors[index % colors.length]} ${from}deg ${to}deg`
      );

      // legend
      legend.innerHTML += `
        <li class="legend-item">
          <span class="legend-dot" style="background:${colors[index % colors.length]}"></span>
          ${item.major_name} – ${percent}%
        </li>
      `;
    });

    // 3) apply real pie chart
    pie.style.backgroundImage = `conic-gradient(${gradients.join(',')})`;

  } catch (e) {
    console.error(e);
  }
}


document.addEventListener('DOMContentLoaded', () => {
  loadDashboardData();
  loadMajorDistribution();
});

document.getElementById('sortDateBtn').addEventListener('click', () => {
  sortDesc = !sortDesc;

  document.getElementById('sortDateBtn').textContent =
    sortDesc ? 'Sort: Newest → Oldest' : 'Sort: Oldest → Newest';

  loadDashboardData(); // إعادة رسم الجدول
});

</script>


  </body>
</html>