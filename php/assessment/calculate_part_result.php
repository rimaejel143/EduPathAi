<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

header("Content-Type: application/json");

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
// 2 get answers for this part
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

if ($part === 3) {

    $categories = [
        "Analytical Ability" => [51, 56, 59],
        "Communication Ability" => [52, 57],
        "Creative Ability" => [55, 60]
    ];

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
        $max_scores[$cat] = count($qids) * 5;
    }

    $total_score = array_sum($trait_scores);
    $total_max = array_sum($max_scores);

    $stmt = $pdo->prepare("
        INSERT INTO assessment_part_results 
        (student_assessment_id, user_id, part_number, part_title, trait_data, total_score, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            trait_data = VALUES(trait_data),
            total_score = VALUES(total_score),
            created_at = NOW()
    ");

    $stmt->execute([
        $student_assessment_id,
        $user_id,
        $part,
        "Part $part Result",
        json_encode($trait_scores),
        $total_score
    ]);

 
    @file_put_contents(__DIR__ . "/log_calculate_part.txt", date("Y-m-d H:i:s") . " PART3_SAVE: assessment=" . intval($student_assessment_id) . " user=" . intval($user_id) . " part=" . intval($part) . " total_score=" . intval($total_score) . " trait_data=" . json_encode($trait_scores) . "\n", FILE_APPEND);

    echo json_encode([
        "success" => true,
        "traits" => $trait_scores,
        "total_score" => $total_score,
        "max_score" => $total_max
    ]);
    exit;
}
// 3 calculate major scores for parts 1 and 2
$major_scores = [];
$major_max_scores = [];

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

    if (!isset($major_scores[$major])) {
        $major_scores[$major] = 0;
        $major_max_scores[$major] = 0;
    }

    $major_scores[$major] += ($selected * $weight);
    $major_max_scores[$major] += (5 * $weight);
}


//    4 MAP → FINAL TRAITS + NORMALIZATION

if ($part == 2) {

    $final_traits = [
        "Work-Life Balance" => ["score" => 0, "max" => 0],
        "Time Management"   => ["score" => 0, "max" => 0],
        "Decision Making"   => ["score" => 0, "max" => 0],
        "Technical Skills"  => ["score" => 0, "max" => 0]
    ];

    // Trait -> Major mapping (نفس فكرة Part 1)
    $mapping = [
        "Work-Life Balance" => ["PSY"],
        "Time Management"   => ["LT"],
        "Decision Making"   => ["LAW"],
        "Technical Skills"  => ["SWE"]
    ];

    foreach ($answers as $ans) {
        $qid = $ans["question_id"];
        $selected = (int)$ans["selected_option"];

        $tstmt = $pdo->prepare("
            SELECT trait_code, weight
            FROM question_trait_weights
            WHERE question_id = ?
        ");
        $tstmt->execute([$qid]);
        $rows = $tstmt->fetchAll();

        foreach ($rows as $row) {
            foreach ($mapping as $trait => $codes) {
                if (in_array($row["trait_code"], $codes)) {
                    $final_traits[$trait]["score"] += $selected * $row["weight"];
                    $final_traits[$trait]["max"]   += 5 * $row["weight"];
                }
            }
        }
    }

    // Preserve raw scores for AI (sum of raw trait scores)
    $raw_scores = [];
    foreach ($final_traits as $trait => $data) {
        $raw_scores[$trait] = $data["score"];
    }
    $total_trait_score = array_sum($raw_scores);

    // keep percentages for UI only   
    $normalized = [];
    foreach ($final_traits as $trait => $data) {
        $normalized[$trait] = $data["max"] > 0
            ? round(($data["score"] / $data["max"]) * 100)
            : 0;
    }

    $final_traits = $normalized;
}



if ($part == 1) {

    $final_traits = [
        "Communication" => 0,
        "Attention to Detail" => 0,
        "Stress Management" => 0,
        "Leadership" => 0
    ];

    $final_max = $final_traits;

    $trait_mapping = [
        "Communication" => ["BUS", "MKT"],
        "Attention to Detail" => ["LT", "ART"],
        "Stress Management" => ["PSY", "MED"],
        "Leadership"        => ["LAW", "SWE"]
    ];

    foreach ($major_scores as $major => $value) {
        foreach ($trait_mapping as $trait => $codes) {
            if (in_array($major, $codes)) {
                $final_traits[$trait] += $value;
                $final_max[$trait] += $major_max_scores[$major];
            }
        }
    }

    // preserve raw totals for AI: capture raw trait sums before converting to percentages
    $raw_final = $final_traits; // these are raw weighted sums per trait
    $raw_total = array_sum($raw_final);

    foreach ($final_traits as $trait => $val) {
        if ($final_max[$trait] > 0) {
            $final_traits[$trait] = round(($val / $final_max[$trait]) * 100);
        } else {
            $final_traits[$trait] = 0;
        }
    }

    // keep $total_trait_score as the raw sum (compatible with AI inputs)
    $total_trait_score = $raw_total;

    //  Fix zero traits (baseline normalization) — operates on percentages for UI
    $nonZeroTraits = array_filter($final_traits, fn($v) => $v > 0);
    if (!empty($nonZeroTraits)) {
        $minBaseline = min($nonZeroTraits) * 0.4;
        foreach ($final_traits as $trait => $val) {
            if ($val == 0) {
                $final_traits[$trait] = round($minBaseline);
            }
        }
    }

}
// 5 save result
$stmt = $pdo->prepare("
    INSERT INTO assessment_part_results 
    (student_assessment_id, user_id, part_number, part_title, trait_data, total_score, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");

$stmt->execute([
    $student_assessment_id,
    $user_id,
    $part,
    "Part $part Result",
    json_encode($final_traits),
    $total_trait_score
]);

// Log computed part values (parts 1 & 2)
@file_put_contents(__DIR__ . "/log_calculate_part.txt", date("Y-m-d H:i:s") . " PART_SAVE: assessment=" . intval($student_assessment_id) . " user=" . intval($user_id) . " part=" . intval($part) . " total_score=" . floatval($total_trait_score) . " trait_data_pct=" . json_encode($final_traits) . "\n", FILE_APPEND);

echo json_encode([
    "success" => true,
    "traits" => $final_traits,
    "total_score" => $total_trait_score
]);
?>
