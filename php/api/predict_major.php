<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$scores = $data["scores"] ?? [];

if (count($scores) != 3) {
    echo json_encode(["success" => false, "message" => "Invalid scores"]);
    exit;
}

// Run the Python predictor from the ai directory so relative paths (model.pkl) resolve
$aiDir = __DIR__ . '/../ai';

// Sanitize args
$args = array_map('intval', $scores);

// Run python from the ai directory to ensure model.pkl relative loads succeed.
$cwd = getcwd();
chdir($aiDir);

$output = null;
$lastErr = null;
// Try common python executable names (python, python3)
$pyCmds = ["python", "python3"];
foreach ($pyCmds as $py) {
    $cmd = $py . ' predict_new.py ' . escapeshellarg($args[0]) . ' ' . escapeshellarg($args[1]) . ' ' . escapeshellarg($args[2]) . ' 2>&1';
    $out = shell_exec($cmd);
    if ($out !== null && trim($out) !== '') {
        $output = $out;
        break;
    }
    $lastErr = $out;
}

// restore cwd
chdir($cwd);

if (!$output) {
    echo json_encode(["success" => false, "message" => "AI offline", "details" => $lastErr ?? "No output from python"]);
    exit;
}

// Extract the last JSON object printed by the predictor (robust against extra prints)
$decoded = null;
$jsonCandidate = null;

// Try to find balanced JSON objects in the output using a recursive regex; fall back to simple braces match.
if (preg_match_all('/\{(?:[^{}]|(?R))*\}/s', $output, $matches)) {
    $candidates = $matches[0];
    // pick the last candidate (most likely the final JSON)
    $jsonCandidate = end($candidates);
} else {
    // fallback: take substring between first '{' and last '}' if present
    $first = strpos($output, '{');
    $last = strrpos($output, '}');
    if ($first !== false && $last !== false && $last > $first) {
        $jsonCandidate = substr($output, $first, $last - $first + 1);
    }
}

if ($jsonCandidate !== null) {
    $decoded = json_decode($jsonCandidate, true);
}

if ($decoded === null) {
    // nothing parseable found — return a clean error with raw details
    echo json_encode(["success" => false, "message" => "AI returned invalid JSON", "details" => trim($output)]);
    exit;
}

// If predictor returned the expected keys, normalize into a standard response
if (is_array($decoded) && isset($decoded['major']) && isset($decoded['major_id']) && isset($decoded['confidence'])) {
    $resp = [
        "success" => true,
        "major" => $decoded['major'],
        "major_id" => is_numeric($decoded['major_id']) ? intval($decoded['major_id']) : $decoded['major_id'],
        "confidence" => is_numeric($decoded['confidence']) ? floatval($decoded['confidence']) : $decoded['confidence']
    ];
    echo json_encode($resp);
    exit;
}

// Fallback: return decoded content (ensure it's JSON-encoded once)
echo json_encode($decoded);
