<?php
function respond($success, $data = [], $status = 200) {
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'data' => $data
    ]);
    exit;
}
?>

