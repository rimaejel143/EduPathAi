<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/admin_auth_check.php';

header("Content-Type: application/json");

ensure_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Use POST method"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$site_name  = trim($data['site_name'] ?? '');
$admin_email = trim($data['admin_email'] ?? '');
$allow_ai = intval($data['allow_ai_predictions'] ?? 1);
$theme_color = trim($data['theme_color'] ?? '#1b3a5e');

if (!$site_name || !$admin_email) {
    echo json_encode([
        "success" => false,
        "message" => "Required fields missing"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE settings 
        SET site_name=?, admin_email=?, allow_ai_predictions=?, theme_color=?, updated_at=NOW()
        WHERE id = 1
    ");

    $stmt->execute([
        $site_name,
        $admin_email,
        $allow_ai,
        $theme_color
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Settings updated successfully"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error updating settings",
        "error" => $e->getMessage()
    ]);
}

