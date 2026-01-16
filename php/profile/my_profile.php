<?php
require_once __DIR__ . '/../auth/check_session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: /SeniorEducation/SeniorEducation/sign_in.html");
  exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
  SELECT full_name, email, profile_image
  FROM users
  WHERE user_id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* profile image */
$imgFile = $user['profile_image'] ?: 'default_profile.png';
$profileImage = file_exists(__DIR__ . '/../../imgs/' . $imgFile)
  ? '/SeniorEducation/SeniorEducation/imgs/' . $imgFile
  : '/SeniorEducation/SeniorEducation/imgs/default_profile.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container mt-5" style="max-width:720px">
  <div class="card shadow-sm">
    <div class="card-body">

      <h3 class="mb-4">My Profile</h3>

      <!-- PROFILE IMAGE -->
      <div class="text-center mb-4">
        <img src="<?= $profileImage ?>" width="130" height="130"
             class="rounded-circle mb-3 border">

        <form action="upload_avatar.php" method="POST" enctype="multipart/form-data">
          <input type="file" name="profile_image"
                 class="form-control form-control-sm mb-2" accept="image/*" required>
          <button class="btn btn-outline-primary btn-sm">Change Photo</button>
        </form>
      </div>

      <!-- UPDATE PROFILE -->
      <form action="update_profile.php" method="POST" class="mb-4">
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control"
                 value="<?= htmlspecialchars($user['full_name']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control"
                 value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <button class="btn btn-primary">Update Profile</button>
      </form>

      <hr>

      <!-- CHANGE PASSWORD -->
      <form action="change_password.php" method="POST">
        <h5 class="mb-3">Change Password</h5>

        <input type="password" name="current_password"
               class="form-control mb-2" placeholder="Current password" required>

        <input type="password" name="new_password"
               class="form-control mb-2" placeholder="New password" required>

        <button class="btn btn-warning">Change Password</button>
      </form>

     <?php
$backUrl = '/SeniorEducation/SeniorEducation/index.html';

if (isset($_SESSION['user_type'])) {
  if ($_SESSION['user_type'] === 'admin') {
    $backUrl = '/SeniorEducation/SeniorEducation/php/admin/admin_dashboard.php';
  } elseif ($_SESSION['user_type'] === 'student') {
    $backUrl = '/SeniorEducation/SeniorEducation/php/student/student_dashboard.php';
  }
}
?>

<a href="<?= $backUrl ?>" class="btn btn-link mt-4">
  ← Back to Dashboard
</a>

    </div>
  </div>
</div>
</body>
</html>

