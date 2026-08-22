<?php
// Password hashing with salt (compatible with PHP 5.2+)
define('PASS_SALT', 'qrforge_salt_2026x!');

function hashPassword($password) {
    return hash('sha256', PASS_SALT . $password);
}

// Check if user is logged in (any role)
function checkUserAuth() {
    if (session_id() === '') {
        session_start();
    }
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'Not authenticated', 'code' => 'AUTH_REQUIRED'));
        exit;
    }
}

// SQL string escaping helper for ODBC (shared by all API files)
function sql_escape($val) {
    return str_replace("'", "''", (string)$val);
}

// Build the public base URL of the site
function getBaseUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    return $protocol . '://' . $host;
}

// Check if user is admin (role=1)
function checkAdminAuth() {
    if (session_id() === '') {
        session_start();
    }
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'Not authenticated', 'code' => 'AUTH_REQUIRED'));
        exit;
    }
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 1) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'Admin access required', 'code' => 'ADMIN_REQUIRED'));
        exit;
    }
}
?>
