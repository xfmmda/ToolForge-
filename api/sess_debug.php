<?php
// Session debug — check if sessions are working
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$result = array(
    'php_version' => PHP_VERSION,
    'session_name' => session_name(),
    'session_save_path' => session_save_path(),
    'session_module_name' => '',
);

if (function_exists('session_module_name')) {
    $result['session_module_name'] = session_module_name();
}

// Try to start session
$session_started = false;
$write_success = false;
if (session_id() === '') {
    session_start();
}
$session_started = session_id() !== '';
$result['session_id'] = session_id();
$result['session_started'] = $session_started;

// Try to write something to session
$_SESSION['debug_test'] = 'hello_' . time();
$write_success = isset($_SESSION['debug_test']);
$result['session_writeable'] = $write_success;

// Check PHP session settings
$result['session_use_cookies'] = ini_get('session.use_cookies');
$result['session_use_only_cookies'] = ini_get('session.use_only_cookies');
$result['session_cookie_httponly'] = ini_get('session.cookie_httponly');
$result['session_cookie_samesite'] = ini_get('session.cookie_samesite');
$result['session_cookie_secure'] = ini_get('session.cookie_secure');
$result['session_cookie_path'] = ini_get('session.cookie_path');
$result['session_cookie_domain'] = ini_get('session.cookie_domain');

echo json_encode($result);
