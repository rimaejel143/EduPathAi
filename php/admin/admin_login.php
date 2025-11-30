<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

header("Content-Type: application/json");

// Only POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Use POST method"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";

if (!$email || !$password) {
    echo json_encode([
        "success" => false,
        "message" => "Email and password required"
    ]);
    exit;
}

try {
    // Check admin
    $stmt = $pdo->prepare("
        SELECT user_id, full_name, email, password, user_type
        FROM users
        WHERE email = ? AND user_type = 'admin'
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if (!$admin) {
        echo json_encode([
            "success" => false,
            "message" => "Admin not found"
        ]);
        exit;
    }

    if (!password_verify($password, $admin["password"])) {
        echo json_encode([
            "success" => false,
            "message" => "Incorrect password"
        ]);
        exit;
    }

    // Start admin session
    $_SESSION["admin_id"] = $admin["user_id"];
    $_SESSION["admin_name"] = $admin["full_name"];
    $_SESSION["admin_email"] = $admin["email"];

    echo json_encode([
        "success" => true,
        "message" => "Admin login successful",
        "admin_name" => $admin["full_name"]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "System error",
        "error" => $e->getMessage()
    ]);
}

