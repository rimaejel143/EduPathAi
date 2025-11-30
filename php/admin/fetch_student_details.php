<?php
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json");

$user_id = intval($_GET["user_id"] ?? 0);

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Invalid user ID"]);
    exit;
}

try {
    // User info
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, created_at FROM users WHERE user_id=?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }

    // Part results
    $stmt = $pdo->prepare("
        SELECT part_number, score, calculated_at 
        FROM part_result 
        WHERE user_id=? 
        ORDER BY part_number ASC
    ");
    $stmt->execute([$user_id]);
    $parts = $stmt->fetchAll();

    // Final result
    $stmt = $pdo->prepare("
        SELECT major, confidence, created_at 
        FROM final_result 
        WHERE user_id=? 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $final = $stmt->fetch();

    echo json_encode([
        "success" => true,
        "user" => $user,
        "parts" => $parts,
        "final_result" => $final
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error"]);
}
