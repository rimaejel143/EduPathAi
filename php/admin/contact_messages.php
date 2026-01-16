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
  <meta charset="UTF-8">
  <title>Admin - Contact Messages</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

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

    body{background:#f8f9fa}

    .admin-navbar{
      background:var(--primary-blue-dark);
    }

    .sidebar{
      width:250px;
      background:#fff;
      border-right:1px solid #ddd;
      min-height:100vh;
    }

    .sidebar .nav-link{
      color:var(--primary-blue-dark);
      padding:12px 15px;
    }

    .sidebar .nav-link.active{
      background:#e9ecef;
      font-weight:bold;
      border-left:4px solid var(--accent-yellow);
    }

    .admin-table th{
      background:#f1f1f1;
      color:var(--primary-blue-dark);
    }
  </style>
</head>

<body>

<!-- NAVBAR -->
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
        <a class="nav-link active" href="contact_messages_admin.php">
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

  <!-- CONTENT -->
  <main class="flex-grow-1 p-4 bg-white">
    <h3 class="mb-4">Contact Messages</h3>

    <!-- SEARCH -->
    <input
      id="searchInput"
      type="text"
      class="form-control mb-3 w-50"
      placeholder="Search by name, email, subject..."
    >

    <!-- TABLE -->
    <div class="table-responsive">
      <table class="table table-striped admin-table">
        <thead>
          <tr>
            <th>Full Name</th>
            <th>Email</th>
            <th>Subject</th>
            <th>Message</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody id="messagesBody">
          <!-- JS -->
        </tbody>
      </table>
    </div>
  </main>
</div>

<script>
async function loadMessages(){
  const res = await fetch('fetch_contact_messages.php', {cache:'no-store'});
  const data = await res.json();

  if(!data.success){
    alert('Failed to load messages');
    return;
  }

  const tbody = document.getElementById('messagesBody');
  tbody.innerHTML = '';

  data.messages.forEach(m => {
    tbody.innerHTML += `
      <tr data-search="${(m.full_name+m.email+m.subject+m.message).toLowerCase()}">
        <td>${m.full_name}</td>
        <td>${m.email}</td>
        <td>${m.subject}</td>
        <td style="max-width:350px">${m.message}</td>
        <td>${new Date(m.created_at).toLocaleDateString()}</td>
      </tr>
    `;
  });
}

// SEARCH
document.getElementById('searchInput').addEventListener('input', e=>{
  const v = e.target.value.toLowerCase();
  document.querySelectorAll('#messagesBody tr').forEach(tr=>{
    tr.style.display = tr.dataset.search.includes(v) ? '' : 'none';
  });
});

document.addEventListener('DOMContentLoaded', loadMessages);
</script>

</body>
</html>

