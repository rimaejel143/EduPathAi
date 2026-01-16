<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/admin_auth_check.php';

header("Content-Type: application/json");

ensure_admin();

try {
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch();

    echo json_encode([
        "success" => true,
        "settings" => $settings
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Unable to load settings"
    ]);
}

