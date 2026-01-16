<?php
// 1. إعدادات الأخطاء (للمساعدة في التصحيح أثناء العمل)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. تضمين ملفات الإعداد والاتصال
// بناءً على هيكلية مجلداتك، نحتاج للعودة خطوتين للخلف

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

// 3. التحقق من تسجيل الدخول - تصحيح مسار التوجيه
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../sign_in.html"); 
    exit();
}

// 4. معالجة إرسال النموذج (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $cat_id = $_POST['category'];
    $title = $_POST['title'];
    $content = $_POST['content'];

    try {
        $sql = "INSERT INTO Forum_Posts (user_id, cat_id, title, content) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $cat_id, $title, $content]);
        
        header("Location: forum.php");
        exit();
    } catch (PDOException $e) {
        $error = "Posting Error: " . $e->getMessage();
    }
}

// 5. جلب الأقسام لعرضها في القائمة
$categories = $pdo->query("SELECT * FROM Forum_Categories")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Topic - EduPathAI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f8fb; font-family: 'Arial', sans-serif; }
        .form-card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 40px; margin-top: 50px; }
        .btn-submit { background-color: #ff8b1f; color: white; border-radius: 40px; font-weight: bold; border: 2px solid black; }
        .btn-submit:hover { background-color: #ff7700; color: white; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background-color: #1b3a5e;">
    <div class="container">
        <a href="forum.php" class="text-decoration-none fw-bold" style="color: #ff8b1f;">
           ←  Back to Forum Page
        </a> 
        <a class="navbar-brand fw-bold" href="../../index.html">EduPathAI</a>
         <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-outline-light btn-sm px-3 rounded-pill" href="../../sign_in.html">Logout</a>
                    </li>
                <?php endif; ?>
              
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card form-card">
                <h2 class="text-center fw-bold mb-4" style="color: #1b3a5e;">Create New Topic</h2>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="create_post.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Category:</label>
                        <select name="category" class="form-select" required>
                            <option value="">-- Choose a Category --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['cat_id']; ?>"><?php echo $cat['cat_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Subject Title:</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Question about Computer Science Major" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Topic Details:</label>
                        <textarea name="content" class="form-control" rows="6" placeholder="Describe your topic or question here..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="forum.php" class="btn btn-outline-secondary px-4 rounded-pill">Cancel</a>
                        <button type="submit" class="btn btn-submit px-5">Post Topic →</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>