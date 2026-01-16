<?php
require_once __DIR__ . '/../auth/check_session.php';
require_once __DIR__ . '/../config/db.php';

// 1. إضافة تخصص جديد
if (isset($_POST['add_major'])) {
    $major_name = $_POST['major_name'];
    $stmt = $pdo->prepare("INSERT INTO Majors (major_name) VALUES (?)");
    $stmt->execute([$major_name]);
    header("Location: manage_majors.php?msg=Major Added");
    exit();
}

// 2. حذف تخصص
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM Majors WHERE major_id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: manage_majors.php?msg=Major Deleted");
    exit();
}

// 3. تحديث تخصص (Update major)
if (isset($_POST['update_major'])) {
    $major_id = $_POST['major_id'];
    $major_name = $_POST['major_name'];
    $stmt = $pdo->prepare("UPDATE Majors SET major_name = ? WHERE major_id = ?");
    $stmt->execute([$major_name, $major_id]);
    header("Location: manage_majors.php?msg=Major Updated Successfully");
    exit();
}

/* جلب صورة البروفايل */
$stmt = $pdo->prepare("SELECT profile_image FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$imgFile = $stmt->fetchColumn() ?: 'default_profile.png';
$profileImage = '/SeniorEducation/SeniorEducation/imgs/' . $imgFile;

// 4. جلب كل التخصصات لعرضها
$majors = $pdo->query("SELECT * FROM Majors ORDER BY major_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Majors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        :root {
            --primary-blue-dark: #1b3a5e;
            --primary-blue-light: #4e7ea7;
            --accent-yellow: #fdc80a;
        }
        .admin-navbar { background-color: var(--primary-blue-dark); }
        .sidebar { width: 250px; background-color: #f8f9fa; border-right: 1px solid #ddd; padding-top: 20px; }
        .admin-layout { height: calc(100vh - 56px); }
        .main-content { flex-grow: 1; overflow-y: auto; padding: 20px; }
        .nav-link { color: var(--primary-blue-dark); padding: 10px 15px; }
        .active .nav-link { background-color: #e9ecef; border-left: 3px solid var(--accent-yellow); font-weight: bold; }
    </style>
</head>
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
          <li class="nav-item ">
            <a class="nav-link" href="manage_forum.php">
              <i class="fas fa-comments me-2"></i> Manage Forum
            </a>
          </li>
           <li class="nav-item active ">
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

    <div class="main-content">
        <h2 class="fw-bold mb-4">Manage University Majors</h2>

        <div class="card p-4 shadow-sm mb-5 border-0" style="border-radius: 15px;">
            <h5 class="fw-bold">Add New Major</h5>
            <form action="" method="POST" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="major_name" class="form-control" placeholder="Major Name..." required>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="add_major" class="btn btn-primary w-100">Add Major</button>
                </div>
            </form>
        </div>

        <div class="card shadow-sm border-0">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>Major Name</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($majors as $m): ?>
                    <tr>
                        <td>#<?= $m['major_id'] ?></td>
                        <td><?= htmlspecialchars($m['major_name']) ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-info me-2" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editModal<?= $m['major_id'] ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>

                            <a href="manage_majors.php?delete_id=<?= $m['major_id'] ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Delete this major?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>

                    <div class="modal fade" id="editModal<?= $m['major_id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="" method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Major</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="major_id" value="<?= $m['major_id'] ?>">
                                        <label class="form-label">Major Name:</label>
                                        <input type="text" name="major_name" class="form-control" value="<?= htmlspecialchars($m['major_name']) ?>" required>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="update_major" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>