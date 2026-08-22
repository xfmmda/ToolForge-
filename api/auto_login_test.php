<?php
// Quick test: login + list in one request
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once dirname(__FILE__) . '/config.php';

// Get first user from DB and auto-login
$sql = "SELECT TOP 1 id, username, role FROM qr_users ORDER BY id";
$result = odbc_exec($conn, $sql);
$user = odbc_fetch_array($result);
odbc_free_result($result);

if (!$user) {
    echo json_encode(array('error' => 'No users found'));
    odbc_close($conn);
    exit;
}

// Auto-login
if (session_id() === '') {
    session_start();
}
$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['user_name'] = $user['username'];
$_SESSION['user_role'] = (int)$user['role'];

// Test list
$list_sql = "SELECT COUNT(*) AS cnt FROM qr_dynamic WHERE user_id = " . (int)$user['id'];
$list_result = odbc_exec($conn, $list_sql);
$list_row = odbc_fetch_array($list_result);
odbc_free_result($list_result);

echo json_encode(array(
    'user' => $user,
    'session_id' => session_id(),
    'session_name' => session_name(),
    'user_logged_in' => $_SESSION['user_logged_in'],
    'code_count' => (int)$list_row['cnt']
));

odbc_close($conn);
