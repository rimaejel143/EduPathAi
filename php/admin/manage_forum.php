<?php


require_once __DIR__ . '/../auth/check_session.php';
require_once __DIR__ . '/../config/db.php';

// 1. معالجة طلب الحذف
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM Forum_Posts WHERE post_id = ?");
    $stmt->execute([$delete_id]);
    header("Location: manage_forum.php?msg=Post Deleted Successfully");
    exit();
}

// 2. جلب جميع المنشورات
$stmt = $pdo->query("SELECT p.post_id, p.title, p.created_at, u.full_name 
                     FROM Forum_Posts p 
                     JOIN Users u ON p.user_id = u.user_id 
                     ORDER BY p.created_at DESC");
$posts = $stmt->fetchAll();

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
    <title>Admin - Manage Forum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
 
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    /></head>
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
<body>
    <nav class="navbar navbar-dark admin-navbar">
      <div class="container-fluid">
         <a class="navbar-brand" href="/SeniorEducation/SeniorEducation/index.html">
      <i class="fas fa-brain me-2"></i>
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
          <li class="nav-item active">
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

<div class="d-flex">
    <div class="main-content p-5 w-100">
        <h2 class="text-center mb-4 fw-bold" style="color: #1b3a5e;">Forum Content Management</h2>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success"><?php echo $_GET['msg']; ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0" style="border-radius: 15px;">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead style="background-color: #f1f4f9;">
                        <tr>
                            <th class="p-3">Post ID</th>
                            <th class="p-3">Title</th>
                            <th class="p-3">Author</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                        <tr>
                            <td class="p-3 text-muted">#<?php echo $post['post_id']; ?></td>
                            <td class="p-3 fw-bold"><?php echo htmlspecialchars($post['title']); ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($post['full_name']); ?></td>
                            <td class="p-3"><?php echo $post['created_at']; ?></td>
                            <td class="p-3">
                                <a href="admin_view_post.php?id=<?php echo $post['post_id']; ?>" 
       class="btn btn-sm btn-info text-white px-3 rounded-pill me-1">
       View & Reply
    </a>
                                <a href="manage_forum.php?delete_id=<?php echo $post['post_id']; ?>" 
                                   class="btn btn-sm btn-danger px-3 rounded-pill" 
                                   onclick="return confirm('Are you sure you want to delete this post?')">
                                   Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
