<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once dirname(__FILE__) . '/auth.php';

if (session_id() === '') {
    session_start();
}

// Destroy session
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
session_destroy();

echo json_encode(array('success' => true, 'message' => 'Logged out'));
?>
