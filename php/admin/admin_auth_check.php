<?php
// Include this at top of admin API endpoints and admin pages to require admin session.
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

function ensure_admin() {
    $is_admin = !empty($_SESSION['user_id']) && (($_SESSION['user_type'] ?? '') === 'admin');

    // Determine whether caller expects JSON (API) or an HTML page.
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $is_json_expected = (strpos($accept, 'application/json') !== false) || (strpos($accept, 'application/xml') !== false) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if (!$is_admin) {
        if ($is_json_expected) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Admin login required']);
            exit;
        } else {
            // For HTML pages, redirect to signin page.
            header('Location: /SeniorEducation/sign_in.html');
            exit;
        }
    }
}

