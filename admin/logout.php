<?php

/**
 * Admin Logout Handler
 * PT. Sarana Sentra Teknologi Utama - Admin Dashboard
 */

declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/auth.php';

$auth = new Auth();

// Verify CSRF token for security
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$auth->verifyCSRFToken($csrf_token)) {
        // Invalid CSRF token - possible CSRF attack
        http_response_code(403);
        die('Security token mismatch.');
    }
}

// Perform logout
$logout_success = $auth->logout();

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/', '', true, true);
}

// Redirect to login page with logout status
$redirect_url = 'login.php';
if ($logout_success) {
    $redirect_url .= '?logout=success';
} else {
    $redirect_url .= '?logout=error';
}

header("Location: {$redirect_url}");
exit;
