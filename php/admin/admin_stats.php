<?php
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json");

try {
    // Count users
    $total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type='student'")->fetchColumn();

    // Count part results
    $completed_parts = $pdo->query("
        SELECT COUNT(DISTINCT user_id)
        FROM part_result
        WHERE part_number IN (1,2,3)
        GROUP BY user_id
        HAVING COUNT(*) = 3
    ")->rowCount();

    // Final results
    $final_results = $pdo->query("SELECT COUNT(*) FROM final_result")->fetchColumn();

    echo json_encode([
        "success" => true,
        "total_students" => $total_users,
        "completed_all_parts" => $completed_parts,
        "final_results" => $final_results
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error"]);
}
