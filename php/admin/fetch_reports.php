<?php
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json");

try {

    // All students + total scores if available
    $stmt = $pdo->query("
        SELECT 
            u.user_id,
            u.full_name,
            u.email,
            u.created_at,
            
            pr1.score AS part1,
            pr2.score AS part2,
            pr3.score AS part3,

            fr.major AS final_major,
            fr.confidence AS final_confidence
        FROM users u

        LEFT JOIN part_result pr1 
            ON pr1.user_id = u.user_id AND pr1.part_number = 1

        LEFT JOIN part_result pr2 
            ON pr2.user_id = u.user_id AND pr2.part_number = 2

        LEFT JOIN part_result pr3 
            ON pr3.user_id = u.user_id AND pr3.part_number = 3

        LEFT JOIN final_result fr 
            ON fr.user_id = u.user_id

        WHERE u.user_type = 'student'
        ORDER BY u.created_at DESC
    ");

    echo json_encode([
        "success" => true,
        "reports" => $stmt->fetchAll()
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "DB error"
    ]);
}
