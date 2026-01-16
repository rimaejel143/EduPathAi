<?php
// 1. تضمين الملفات الأساسية (تأكد من صحة المسارات لديك)

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';


// 2. جلب معرف المنشور
$post_id = $_GET['id'] ?? 0;

// 3. إضافة تعليق جديد (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $comment_text = $_POST['comment_text'];
    if (!empty($comment_text)) {
        $stmt = $pdo->prepare("INSERT INTO Forum_Comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $_SESSION['user_id'], $comment_text]);
        header("Location: view_post.php?id=$post_id");
        exit();
    }
}

// 4. جلب بيانات المنشور مع الكاتب والقسم
$stmt = $pdo->prepare("SELECT p.*, u.full_name, c.cat_name 
                       FROM Forum_Posts p 
                       JOIN Users u ON p.user_id = u.user_id 
                       JOIN Forum_Categories c ON p.cat_id = c.cat_id 
                       WHERE p.post_id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

// إذا لم يتم العثور على المنشور
if (!$post) {
    die("<div class='container mt-5 alert alert-danger text-center'>Post not found!</div>");
}

// 5. جلب التعليقات المرتبطة
$stmt = $pdo->prepare("SELECT fc.*, u.full_name 
                       FROM Forum_Comments fc 
                       JOIN Users u ON fc.user_id = u.user_id 
                       WHERE fc.post_id = ? 
                       ORDER BY fc.created_at ASC");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($post['title']); ?> - EduPathAI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f8fb; font-family: 'Arial', sans-serif; }
        .post-container { max-width: 900px; margin: 40px auto; }
        .main-card { border-radius: 20px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .category-badge { background-color: #e9f1ff; color: #1b3a5e; font-weight: bold; border-radius: 20px; padding: 5px 15px; }
        .comment-box { background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; border-left: 5px solid #ff8b1f; }
        .btn-reply { background-color: #ff8b1f; color: white; border-radius: 30px; border: 2px solid black; font-weight: bold; }
    </style>
</head>
<body>

<div class="container post-container">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="forum.php">Forum</a></li>
            <li class="breadcrumb-item active"><?php echo $post['cat_name']; ?></li>
        </ol>
    </nav>

    <div class="card main-card p-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="category-badge"><?php echo $post['cat_name']; ?></span>
            <small class="text-muted"><?php echo $post['created_at']; ?></small>
        </div>
        <h2 class="fw-bold mb-3" style="color: #1b3a5e;"><?php echo htmlspecialchars($post['title']); ?></h2>
        <p class="text-secondary mb-4">Posted by: <strong><?php echo htmlspecialchars($post['full_name']); ?></strong></p>
        <hr>
        <div class="post-body fs-5" style="line-height: 1.8;">
            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
        </div>
    </div>

    <h4 class="fw-bold mb-4">Comments (<?php echo count($comments); ?>)</h4>
    <div class="comments-list">
        <?php foreach ($comments as $comment): ?>
            <div class="comment-box shadow-sm">
                <div class="d-flex justify-content-between">
                    <h6 class="fw-bold"><?php echo htmlspecialchars($comment['full_name']); ?></h6>
                    <small class="text-muted"><?php echo $comment['created_at']; ?></small>
                </div>
                <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="card p-4 shadow-sm mt-5" style="border-radius: 20px;">
            <h5 class="fw-bold mb-3">Write a Reply</h5>
            <form action="" method="POST">
                <textarea name="comment_text" class="form-control mb-3" rows="4" placeholder="Share your thoughts..." required></textarea>
                <button type="submit" class="btn btn-reply px-5">Submit Reply</button>
            </form>
        </div>
    <?php else: ?>
        <div class="alert alert-warning mt-5">Please <a href="../../sign_in.html">Sign In</a> to join the discussion.</div>
    <?php endif; ?>
</div>

</body>
</html>