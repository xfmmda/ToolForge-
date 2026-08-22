<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(array('error' => 'POST only'));
    exit;
}

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/auth.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$username = isset($input['username']) ? trim($input['username']) : '';
$new_password = isset($input['new_password']) ? $input['new_password'] : '';
$verify_code = isset($input['verify_code']) ? trim($input['verify_code']) : '';

if (!$username || !$new_password) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('success' => false, 'error' => '请输入账号和新密码'));
    odbc_close($conn);
    exit;
}

if (strlen($new_password) < 6) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('success' => false, 'error' => '密码长度至少6个字符'));
    odbc_close($conn);
    exit;
}

// Find user
$escaped = str_replace("'", "''", $username);
$find = odbc_exec($conn, "SELECT id, username, reset_token FROM qr_users WHERE username='" . $escaped . "'");
if (!$find) {
    echo json_encode(array('success' => false, 'error' => '查询失败'));
    odbc_close($conn);
    exit;
}

$row = odbc_fetch_array($find);
odbc_free_result($find);

if (!$row) {
    echo json_encode(array('success' => false, 'error' => '账号不存在'));
    odbc_close($conn);
    exit;
}

// If verify_code is provided, check it
if ($verify_code) {
    $escaped_code = str_replace("'", "''", $verify_code);
    if (!isset($row['reset_token']) || $row['reset_token'] !== $verify_code) {
        echo json_encode(array('success' => false, 'error' => '验证码不正确'));
        odbc_close($conn);
        exit;
    }
}

// Update password
$new_hash = hashPassword($new_password);
$update = odbc_exec($conn, "UPDATE qr_users SET password_hash='" . $new_hash . "', reset_token=NULL, updated_at=GETDATE() WHERE username='" . $escaped . "'");

if ($update) {
    odbc_free_result($update);
    echo json_encode(array('success' => true, 'message' => '密码重置成功，请重新登录'));
} else {
    echo json_encode(array('success' => false, 'error' => '密码重置失败'));
}

odbc_close($conn);
?>
