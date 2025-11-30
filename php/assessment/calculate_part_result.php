<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

header("Content-Type: application/json");

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$user_id = $_SESSION["user_id"] ?? 0;
$part = intval($_GET["part"] ?? 0);

if (!$user_id || $part < 1 || $part > 3) {
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

/* -------------------------------------------------
   1) GET ACTIVE student_assessment_id
---------------------------------------------------*/
$ast = $pdo->prepare("
    SELECT student_assessment_id 
    FROM student_assessment
    WHERE user_id = ?
    ORDER BY start_time DESC
    LIMIT 1
");
$ast->execute([$user_id]);
$assessment = $ast->fetch();

if (!$assessment) {
    echo json_encode(["success" => false, "message" => "No active assessment"]);
    exit;
}

$student_assessment_id = $assessment["student_assessment_id"];

/* -------------------------------------------------
   2) GET ANSWERS
---------------------------------------------------*/
$stmt = $pdo->prepare("
    SELECT sa.question_id, sa.selected_option
    FROM selectedanswers sa
    JOIN questions q ON sa.question_id = q.question_id
    WHERE sa.user_id=? 
      AND sa.student_assessment_id=? 
      AND q.part_id=?
");
$stmt->execute([$user_id, $student_assessment_id, $part]);
$answers = $stmt->fetchAll();

if (!$answers) {
    echo json_encode(["success" => false, "message" => "No answers found"]);
    exit;
}

// Special handling for Part 3: each trait has exactly 2 questions,
// For Part 3 we use a custom 3-category mapping (no major/weight logic)
if ($part === 3) {
    // Define categories and their question IDs
    $categories = [
        "Analytical Ability" => [51, 56, 59],
        "Communication Ability" => [52, 57],
        "Creative Ability" => [55, 60]
    ];

    // Build a lookup of answers by question_id
    $answersByQ = [];
    foreach ($answers as $a) {
        $answersByQ[intval($a['question_id'])] = intval($a['selected_option']);
    }

    $trait_scores = [];
    $max_scores = [];

    foreach ($categories as $cat => $qids) {
        $score = 0;
        foreach ($qids as $qid) {
            $score += $answersByQ[$qid] ?? 0;
        }
        $trait_scores[$cat] = $score;
        // max per question is 5
        $max_scores[$cat] = count($qids) * 5;
    }

    $total_score = array_sum($trait_scores);
    $total_max = array_sum($max_scores);

    // Save result for part 3 (store trait_scores as trait_data)
    $stmt = $pdo->prepare("\n        INSERT INTO assessment_part_results \n            (student_assessment_id, user_id, part_number, part_title, trait_data, total_score, created_at)\n        VALUES \n            (?, ?, ?, ?, ?, ?, NOW())\n        ON DUPLICATE KEY UPDATE \n            trait_data = VALUES(trait_data),\n            total_score = VALUES(total_score),\n            created_at = NOW()\n    ");

    $stmt->execute([
        $student_assessment_id,
        $user_id,
        $part,
        "Part $part Result",
        json_encode($trait_scores),
        $total_score
    ]);

    echo json_encode([
        "success" => true,
        "traits" => $trait_scores,
        "total_score" => $total_score,
        "max_score" => $total_max
    ]);

    exit;
}

/* -------------------------------------------------
   3) CALCULATE MAJOR SCORES
---------------------------------------------------*/
$major_scores = [];

foreach ($answers as $ans) {
    $qid = $ans["question_id"];
    $selected = $ans["selected_option"];

    $tstmt = $pdo->prepare("
        SELECT trait_code, weight 
        FROM question_trait_weights 
        WHERE question_id = ?
    ");
    $tstmt->execute([$qid]);
    $traitRow = $tstmt->fetch();

    if (!$traitRow) continue;

    $major = $traitRow["trait_code"];
    $weight = $traitRow["weight"];

    if (!isset($major_scores[$major])) $major_scores[$major] = 0;

    $major_scores[$major] += ($selected * $weight);
}

/* -------------------------------------------------
   4) MAP MAJORS → 4 PERSONALITY TRAITS
---------------------------------------------------*/

$trait_mapping = [
    "Communication" => ["BUS", "MKT"],
    "Attention to Detail" => ["LT", "ART"],
    "Stress Management" => ["PSY", "MED"],
    "Leadership"        => ["LAW", "SWE"]
];

$final_traits = [
    "Communication" => 0,
    "Attention to Detail" => 0,
    "Stress Management" => 0,
    "Leadership" => 0
];

foreach ($major_scores as $major => $value) {
    foreach ($trait_mapping as $trait => $mapList) {
        if (in_array($major, $mapList)) {
            $final_traits[$trait] += $value;
        }
    }
}

$total_trait_score = array_sum($final_traits);

/* -------------------------------------------------
   5) SAVE RESULT
---------------------------------------------------*/
$stmt = $pdo->prepare("
    INSERT INTO assessment_part_results 
        (student_assessment_id, user_id, part_number, part_title, trait_data, total_score, created_at)
    VALUES 
        (?, ?, ?, ?, ?, ?, NOW())
");

$stmt->execute([
    $student_assessment_id,
    $user_id,
    $part,
    "Part $part Result",
    json_encode($final_traits),
    $total_trait_score
]);

echo json_encode([
    "success" => true,
    "message" => "Part result calculated",
    "traits" => $final_traits,
    "total_score" => $total_trait_score
]);
?>
