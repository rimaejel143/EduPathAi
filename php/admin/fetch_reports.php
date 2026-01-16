<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/admin_auth_check.php';

header("Content-Type: application/json");
ensure_admin();

try {

    $stmt = $pdo->query("
        SELECT 
            u.user_id,
            u.full_name,
            u.email,
            fr.student_assessment_id,
            fr.created_at,
            m.major_name
        FROM final_result fr

        INNER JOIN student_assessment sa
            ON sa.student_assessment_id = fr.student_assessment_id

        INNER JOIN users u
            ON u.user_id = sa.user_id
            AND u.user_type = 'student'

        LEFT JOIN majors m
            ON m.major_id = fr.major_id

        WHERE fr.student_assessment_id IN (
            SELECT MAX(fr2.student_assessment_id)
            FROM final_result fr2
            INNER JOIN student_assessment sa2
                ON sa2.student_assessment_id = fr2.student_assessment_id
            GROUP BY sa2.user_id, fr2.major_id
        )

        ORDER BY 
            (fr.created_at = '0000-00-00 00:00:00') ASC,
            fr.created_at DESC,
            fr.student_assessment_id DESC

        LIMIT 10
    ");

    echo json_encode([
        'success' => true,
        'reports' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Reports error',
        'error' => $e->getMessage()
    ]);
}
