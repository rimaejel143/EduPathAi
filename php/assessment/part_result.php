<?php 
session_start();

require_once __DIR__ . '/../config/db.php';
header("Content-Type: application/json");

$user_id = $_SESSION["user_id"] ?? 0;
$part = intval($_GET["part"] ?? 0);

if(!$user_id || !$part){
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT trait_data, total_score
    FROM assessment_part_results
    WHERE user_id=? AND part_number=?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$user_id, $part]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(["success" => false, "message" => "No result"]);
    exit;
}

$traits = json_decode($row["trait_data"], true);
$total_score = intval($row["total_score"]);

// --- حساب max_score (مهم جداً للـ Progress Bars)
$max_score = count($traits) * 5 * 5;

echo json_encode([
    "success" => true,
    "traits" => $traits,
    "total_score" => $total_score,
    "max_score" => $max_score
]);
