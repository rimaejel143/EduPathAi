<?php

require_once __DIR__ . '/../auth/check_session.php';
require_once __DIR__ . '/../config/db.php';

// 2. الحصول على ID المنشور من الرابط
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id == 0) {
    die("Invalid Post ID.");
}

// 3. معالجة إرسال رد الأدمن
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $comment_text = "[Admin Official]: " . $_POST['comment_text'];
    try {
        $stmt = $pdo->prepare("INSERT INTO Forum_Comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $_SESSION['user_id'], $comment_text]);
        header("Location: admin_view_post.php?id=$post_id&msg=Reply Posted Successfully");
        exit();
    } catch (PDOException $e) {
        $error = "Error posting reply: " . $e->getMessage();
    }
}

// 4. جلب بيانات المنشور (هنا يتم تعريف متغير $post)
$stmt = $pdo->prepare("SELECT p.*, u.full_name FROM Forum_Posts p 
                       JOIN Users u ON p.user_id = u.user_id 
                       WHERE p.post_id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

// التحقق إذا كان المنشور موجوداً
if (!$post) {
    die("Post not found in database.");
}

// 5. جلب التعليقات (هنا يتم تعريف متغير $comments)
$stmt = $pdo->prepare("SELECT fc.*, u.full_name FROM Forum_Comments fc 
                       JOIN Users u ON fc.user_id = u.user_id 
                       WHERE fc.post_id = ? ORDER BY fc.created_at ASC");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin View - <?php echo htmlspecialchars($post['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: #f8f9fc;">

<div class="container mt-5">
    <a href="manage_forum.php" class="btn btn-outline-secondary mb-4">← Back to Manage Forum</a>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm p-4 mb-4 border-0" style="border-radius: 15px;">
        <h3 class="fw-bold" style="color: #1b3a5e;"><?php echo htmlspecialchars($post['title']); ?></h3>
        <p class="text-muted">By: <b><?php echo htmlspecialchars($post['full_name']); ?></b> | Date: <?php echo $post['created_at']; ?></p>
        <hr>
        <p class="fs-5"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
    </div>

    <h5 class="fw-bold mb-3">All Comments (<?php echo count($comments); ?>):</h5>
    <?php foreach($comments as $comment): ?>
        <div class="p-3 mb-2 bg-white rounded shadow-sm border-start border-4 <?php echo (strpos($comment['comment_text'], '[Admin') !== false) ? 'border-primary' : 'border-warning'; ?>">
            <small class="fw-bold"><?php echo htmlspecialchars($comment['full_name']); ?>:</small>
            <p class="mb-0"><?php echo htmlspecialchars($comment['comment_text']); ?></p>
            <small class="text-muted" style="font-size: 0.75rem;"><?php echo $comment['created_at']; ?></small>
        </div>
    <?php endforeach; ?>

    <div class="mt-5 p-4 bg-light rounded shadow-sm border">
        <h5 class="fw-bold mb-3">Post an Official Admin Reply</h5>
        <form action="" method="POST">
            <textarea name="comment_text" class="form-control mb-3" rows="3" placeholder="Write your official response here..." required></textarea>
            <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm">Submit Official Reply</button>
        </form>
    </div>
</div>

</body>
</html>