<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/admin_auth_check.php';

header("Content-Type: application/json");
ensure_admin();

try {

    $stmt = $pdo->query("
        SELECT 
            m.major_name,
            COUNT(*) AS total
        FROM final_result fr
        JOIN majors m ON m.major_id = fr.major_id
        GROUP BY fr.major_id
    ");

    echo json_encode([
        "success" => true,
        "data" => $stmt->fetchAll()
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}

