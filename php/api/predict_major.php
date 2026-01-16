<?php
header("Content-Type: application/json");

// Read raw input and log for debugging
$raw_in = file_get_contents("php://input");
@file_put_contents(
    __DIR__ . "/../assessment/log_predict_incoming.txt",
    date("Y-m-d H:i:s") . " INCOMING_RAW: " . $raw_in . "\n",
    FILE_APPEND
);

$data = json_decode($raw_in, true);
$scores = $data["scores"] ?? [];

// Handle double-encoded scores
if (!is_array($scores) && is_string($scores)) {
    $maybe = json_decode($scores, true);
    if (is_array($maybe)) {
        $scores = $maybe;
    }
}

// Validate scores
if (!is_array($scores) || count($scores) !== 3) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid scores"
    ]);
    exit;
}

// Run the Python predictor from the ai directory
$aiDir = __DIR__ . '/../ai';

// Sanitize args
$args = array_map('intval', $scores);

// Change working directory so model.pkl loads correctly
$cwd = getcwd();
chdir($aiDir);

// --- IMPORTANT: use absolute python.exe path (no PATH dependency)
$python = '"C:\\Users\\rimae\\AppData\\Local\\Programs\\Python\\Python313\\python.exe"';

$cmd = $python . ' predict_new.py '
     . escapeshellarg($args[0]) . ' '
     . escapeshellarg($args[1]) . ' '
     . escapeshellarg($args[2])
     . ' < NUL 2>&1';

// Execute
set_time_limit(30);
$output = shell_exec($cmd);

// Restore working directory
chdir($cwd);

// If nothing came back
if ($output === null || trim($output) === '') {
    echo json_encode([
        "success" => false,
        "message" => "AI offline",
        "details" => "No output from python"
    ]);
    exit;
}

// Try to extract the last JSON object from output
$decoded = null;
$jsonCandidate = null;

if (preg_match_all('/\{(?:[^{}]|(?R))*\}/s', $output, $matches)) {
    $jsonCandidate = end($matches[0]);
} else {
    $first = strpos($output, '{');
    $last  = strrpos($output, '}');
    if ($first !== false && $last !== false && $last > $first) {
        $jsonCandidate = substr($output, $first, $last - $first + 1);
    }
}

if ($jsonCandidate !== null) {
    $decoded = json_decode($jsonCandidate, true);
}

if ($decoded === null) {
    echo json_encode([
        "success" => false,
        "message" => "AI returned invalid JSON",
        "details" => trim($output)
    ]);
    exit;
}

// Normalize expected AI response
if (
    is_array($decoded) &&
    isset($decoded['major']) &&
    isset($decoded['major_id']) &&
    isset($decoded['confidence'])
) {
    echo json_encode([
        "success"    => true,
        "major"      => $decoded['major'],
        "major_id"   => is_numeric($decoded['major_id'])
                        ? intval($decoded['major_id'])
                        : $decoded['major_id'],
        "confidence" => is_numeric($decoded['confidence'])
                        ? floatval($decoded['confidence'])
                        : $decoded['confidence']
    ]);
    exit;
}

// Fallback: return decoded content
echo json_encode($decoded);
