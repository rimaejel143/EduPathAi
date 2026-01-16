<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/admin_auth_check.php';

header("Content-Type: application/json");
ensure_admin();

try {

    // 1. Total students
    $totalStudents = $pdo
        ->query("SELECT COUNT(*) FROM users WHERE user_type = 'student'")
        ->fetchColumn();

    // 2. Completed tests
    $completedTests = $pdo
        ->query("SELECT COUNT(DISTINCT student_assessment_id) FROM final_result")
        ->fetchColumn();

    // 3. Average score
    $avgScore = $pdo
        ->query("SELECT AVG(total_score) FROM assessment_part_results")
        ->fetchColumn();

   // ✅ 4. Users who completed tests this week
$newUsersThisWeek = $pdo->query("
    SELECT COUNT(DISTINCT sa.user_id)
    FROM final_result fr
    INNER JOIN student_assessment sa
        ON sa.student_assessment_id = fr.student_assessment_id
    WHERE fr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetchColumn();


    echo json_encode([
        'success' => true,
        'total_students'       => (int)$totalStudents,
        'completed_tests'      => (int)$completedTests,
        'average_score'        => round((float)$avgScore, 2),
        'new_users_this_week'  => (int)$newUsersThisWeek
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Stats error',
        'error'   => $e->getMessage()
    ]);
}
