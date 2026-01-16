<?php
require_once __DIR__ . '/admin_auth_check.php';
require_once __DIR__ . '/../config/db.php';
ensure_admin();
// total users
$totalUsers = $pdo->query("
  SELECT COUNT(*) FROM users
")->fetchColumn();

// total assessments
$totalAssessments = $pdo->query("
  SELECT COUNT(*) FROM final_result
")->fetchColumn();

// total contact messages
$totalMessages = $pdo->query("
  SELECT COUNT(*) FROM contact_messages
")->fetchColumn();

// admin email (static for now)
$adminEmail = 'admin@edupathai.com';
$lastLogin  = date('d/m/Y'); // demo
try {
    $pdo->query("SELECT 1");
    $db_status = "Connected";
    $db_color = "success";
} catch (Exception $e) {
    $db_status = "Disconnected";
    $db_color = "danger";
}

$server_time = date("Y-m-d H:i:s");
$php_version = phpversion();
$environment = "Local";
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
    <title>Admin - Settings</title>
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
      .setting-card {
        border: none;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
      }
      .save-settings-btn {
        background-color: var(--primary-blue-dark);
        color: white;
        padding: 10px 30px;
        font-size: 1.1rem;
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
</a>        <a class="btn btn-sm btn-outline-light" href="admin_logout.php">Logout</a>
        </div>
      </div>
    </nav>

    <div class="d-flex admin-layout">
      <div class="sidebar">
        <ul class="nav flex-column">
          <li class="nav-item ">
            <a class="nav-link" href="admin_dashboard.php">
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

          <li class="nav-item active">
            <a class="nav-link" href="settings.php">
              <i class="fas fa-cog me-2"></i> Settings
            </a>
          </li>
        </ul>
      </div>

      <main class="main-content p-4">
  <div class="container-fluid">
    <h2 class="mb-4">System Settings</h2>

    <div class="row">

      <!-- Admin Info -->
     <div class="col-md-6 mb-4">
  <div class="card p-4 shadow-sm">
    <h5 class="mb-3 text-primary">
      <i class="fas fa-user-shield me-2"></i> Admin Information
    </h5>

    <p><strong>Email:</strong> <?= $adminEmail ?></p>
    <p><strong>Role:</strong> System Administrator</p>
    <p><strong>Last Login:</strong> <?= $lastLogin ?></p>
  </div>
</div>

      <!-- System Info -->
     <div class="col-md-6 mb-4">
  <div class="card p-4 shadow-sm">
    <h5 class="mb-3 text-success">
      <i class="fas fa-database me-2"></i> System Information
    </h5>

    <p><strong>Website Name:</strong> EduPathAI</p>
    <p><strong>Total Users:</strong> <?= $totalUsers ?></p>
    <p><strong>Total Assessments:</strong> <?= $totalAssessments ?></p>
    <p><strong>Contact Messages:</strong> <?= $totalMessages ?></p>
  </div>
</div>


    <!-- Actions -->
    <div class="row mt-4">

  <!-- SYSTEM STATUS -->
  <div class="col-md-6 mb-4">
    <div class="card setting-card p-4">
      <h4 class="mb-3 text-primary">
        <i class="fas fa-server me-2"></i> System Status
      </h4>

      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span>Database Status</span>
          <span class="badge bg-<?= $db_color ?>">
            <?= $db_status ?>
          </span>
        </li>

        <li class="list-group-item d-flex justify-content-between">
          <span>Server Time</span>
          <span><?= $server_time ?></span>
        </li>

        <li class="list-group-item d-flex justify-content-between">
          <span>PHP Version</span>
          <span><?= $php_version ?></span>
        </li>

        <li class="list-group-item d-flex justify-content-between">
          <span>Environment</span>
          <span><?= $environment ?></span>
        </li>
      </ul>
    </div>
  </div>

  <!-- SYSTEM NOTES -->
  <div class="col-md-6 mb-4">
    <div class="card setting-card p-4">
      <h4 class="mb-3 text-success">
        <i class="fas fa-sticky-note me-2"></i> System Notes
      </h4>

      <p class="text-muted">
        This admin panel is designed to manage and monitor the EduPathAI system.
        Administrators can review user assessments, contact messages, and system
        data to ensure smooth operation and accurate academic recommendations.
      </p>

      <p class="text-muted mb-0">
        All configurations and reports shown here are for monitoring purposes
        and reflect real-time system data.
      </p>
    </div>
  </div>

</div>

</main>

    </div>

    <script>
      // Load settings on page load
      fetch("get_settings.php", { credentials: 'same-origin' })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            let s = data.settings;
            document.getElementById("site_name").value = s.site_name;
            document.getElementById("admin_email").value = s.admin_email;
            document.getElementById("theme_color").value = s.theme_color;
            document.getElementById("allow_ai").checked =
              s.allow_ai_predictions == 1;
          }
        });

      function saveSettings() {
        let payload = {
          site_name: document.getElementById("site_name").value,
          admin_email: document.getElementById("admin_email").value,
          theme_color: document.getElementById("theme_color").value,
          allow_ai_predictions: document.getElementById("allow_ai").checked
            ? 1
            : 0,
        };

        fetch(
          "update_settings.php",
          {
            method: "POST",
            body: JSON.stringify(payload),
          }
        )
          .then((res) => res.json())
          .then((data) => {
            alert(data.message);
          });
      }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
