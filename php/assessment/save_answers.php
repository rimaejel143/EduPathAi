<?php
file_put_contents("log.txt", "HIT SAVE_ANSWERS\n", FILE_APPEND);

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

file_put_contents(__DIR__ . "/log.txt", "save_answers METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Use POST"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $_SESSION['user_id'] ?? 0;
$part = intval($data["part"] ?? 0);
$answers = $data["answers"] ?? [];

if (!$user_id || !$part || empty($answers)) {
    echo json_encode([
        "success" => false,
        "message" => "Missing data"
    ]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        SELECT student_assessment_id 
        FROM student_assessment
        WHERE user_id = ? 
        ORDER BY start_time DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $assessment = $stmt->fetch();

    if (!$assessment) {
        echo json_encode(["success" => false, "message" => "No active assessment"]);
        exit;
    }

    $student_assessment_id = $assessment["student_assessment_id"];

    foreach ($answers as $ans) {
        $question_id = intval($ans["question_id"]);
        $selected = intval($ans["selected"]);

        // 🔥 FIX HERE!!!
        $stmt = $pdo->prepare("
            SELECT selected_answer_id FROM selectedanswers
            WHERE user_id=? AND question_id=? AND student_assessment_id=?
        ");
        $stmt->execute([$user_id, $question_id, $student_assessment_id]);

        if ($stmt->rowCount() > 0) {

            $pdo->prepare("
                UPDATE selectedanswers 
                SET selected_option=?, updated_at=NOW()
                WHERE user_id=? AND question_id=? AND student_assessment_id=?
            ")->execute([$selected, $user_id, $question_id, $student_assessment_id]);

        } else {

            $pdo->prepare("
                INSERT INTO selectedanswers (user_id, student_assessment_id, question_id, selected_option, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ")->execute([$user_id, $student_assessment_id, $question_id, $selected]);
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Answers saved successfully",
        "assessment_id" => $student_assessment_id,
        "part" => $part
    ]);

} catch(Exception $e){
   echo json_encode([
      "success"=>false,
      "message"=>"DB error",
      "error"=>$e->getMessage()
   ]);
}
?>
