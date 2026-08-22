<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(array('error' => 'POST only'));
    exit;
}

require_once dirname(__FILE__) . '/auth.php';
checkUserAuth();
require_once dirname(__FILE__) . '/config.php';

if (session_id() === '') { session_start(); }
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$user_role = isset($_SESSION['user_role']) ? (int)$_SESSION['user_role'] : 0;

// Admin cannot delete own account through this endpoint
if ($user_role === 1) {
    echo json_encode(array('success' => false, 'error' => '管理员账号不能通过此方式注销'));
    odbc_close($conn); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }

$password = isset($input['password']) ? $input['password'] : '';
if (!$password) {
    echo json_encode(array('success' => false, 'error' => '请输入密码确认'));
    odbc_close($conn); exit;
}

// Verify password (hash before comparing)
$hash = hashPassword($password);
$escaped_hash = str_replace("'", "''", $hash);
$sql = "SELECT id FROM qr_users WHERE id = " . $user_id . " AND password_hash='" . $escaped_hash . "'";
$result = odbc_exec($conn, $sql);
if (!$result) {
    echo json_encode(array('success' => false, 'error' => '查询失败'));
    odbc_close($conn); exit;
}
$row = odbc_fetch_array($result);
odbc_free_result($result);
if (!$row) {
    echo json_encode(array('success' => false, 'error' => '密码错误'));
    odbc_close($conn); exit;
}

// Delete user's codes first
odbc_exec($conn, "DELETE FROM qr_dynamic WHERE user_id = " . $user_id);

// Delete user
odbc_exec($conn, "DELETE FROM qr_users WHERE id = " . $user_id);

// Destroy session
session_destroy();

echo json_encode(array('success' => true, 'message' => '账号已注销'));
odbc_close($conn);
?>
