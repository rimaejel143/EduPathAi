<?php
session_start();
require_once __DIR__ . '/../config/db.php';


$user_id = $_SESSION["user_id"] ?? 0;

if (!$user_id) {
    echo json_encode(["success"=>false,"message"=>"User not logged in"]);
    exit;
}

// إنشاء اختبار جديد
$stmt = $pdo->prepare("
    INSERT INTO student_assessment (user_id, assessment_id, start_time) 
    VALUES (?, 1, NOW())
");
$stmt->execute([$user_id]);

$assessment_id = $pdo->lastInsertId();

// حفظ ID داخل SESSION + LocalStorage
$_SESSION["last_assessment_id"] = $assessment_id;

echo json_encode([
    "success"=>true,
    "assessment_id"=>$assessment_id
]);
