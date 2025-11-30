<?php
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json");

$part = intval($_GET["part"] ?? 0);

if ($part < 1 || $part > 3) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid part number"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT question_id, question_text, option_a, option_b, option_c, option_d
        FROM questions
        WHERE part_number = ?
        ORDER BY question_id ASC
    ");
    $stmt->execute([$part]);
    $questions = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "part" => $part,
        "questions" => $questions
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error"]);
}

