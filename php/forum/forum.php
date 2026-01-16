<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

try {
    $query = "SELECT p.*, u.full_name, c.cat_name 
              FROM Forum_Posts p
              JOIN Users u ON p.user_id = u.user_id
              JOIN Forum_Categories c ON p.cat_id = c.cat_id
              ORDER BY p.created_at DESC";
    
    $stmt = $pdo->query($query);
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    die("  error  : " . $e->getMessage());
}

$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>EduPathAI - Forum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background-color: #1b3a5e;">
    <div class="container">
        <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-outline-light btn-sm px-3 rounded-pill" href="../../sign_in.html">Logout</a>
                    </li>
                <?php endif; ?>
        <a class="navbar-brand fw-bold" href="../../index.html">EduPathAI</a>
        
        <a href="../student/student_dashboard.php" class="text-decoration-none fw-bold" style="color: #ff8b1f;">
             Back to Student Dashboard ←
        </a>       
    </div>
</nav>
<div class="container mt-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        
        <?php if($is_logged_in): ?>
            <a href="create_post.php" class="btn btn-warning"> Create New Topic +</a>
        <?php endif; ?>
        <h2 class="fw-bold"> Discussion Forum</h2>
    </div>

    <?php foreach($posts as $post): ?>
    <div class="card mb-4 shadow-sm p-4 border-0" style="border-radius: 20px; transition: 0.3s;">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h4 class="fw-bold mb-0" style="color: #1b3a5e;">
                <?php echo htmlspecialchars($post['title']); ?>
            </h4>
            <span class="badge rounded-pill px-3 py-2" style="background-color: #e9f1ff; color: #1b3a5e; font-size: 0.85rem;">
                <?php echo $post['cat_name']; ?>
            </span>
        </div>

        <p class="text-secondary mt-2" style="font-size: 0.95rem; line-height: 1.6;">
            <?php echo substr(htmlspecialchars($post['content']), 0, 150); ?>...
        </p>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <small class="text-muted">
                by: <span class="fw-bold text-dark"><?php echo htmlspecialchars($post['full_name']); ?></span>
            </small>
            <a href="view_post.php?id=<?php echo $post['post_id']; ?>" 
               class="btn btn-sm px-4 py-2" 
               style="border: 2px solid #1b3a5e; color: #1b3a5e; border-radius: 25px; font-weight: bold; transition: 0.3s;">
                View Details →
            </a>
        </div>
    </div>
<?php endforeach; ?>
</div>
</body>
</html>