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
    $cmd = $py . ' predict.py ' . escapeshellarg($args[0]) . ' ' . escapeshellarg($args[1]) . ' ' . escapeshellarg($args[2]) . ' 2>&1';
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

// Ensure output is valid JSON
$decoded = json_decode($output, true);
if ($decoded === null) {
    echo json_encode(["success" => false, "message" => "AI returned invalid JSON", "details" => $output]);
    exit;
}

echo json_encode($decoded);
