<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/auth.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$username = isset($input['username']) ? trim($input['username']) : '';

if (!$username) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('success' => false, 'error' => '请输入账号'));
    odbc_close($conn);
    exit;
}

// Find user and generate reset token
$escaped = str_replace("'", "''", $username);
$find = odbc_exec($conn, "SELECT id, username, email FROM qr_users WHERE username='" . $escaped . "'");
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

// Generate a 6-digit verification code
$token = sprintf('%06d', mt_rand(100000, 999999));
$escaped_token = str_replace("'", "''", $token);

$update = odbc_exec($conn, "UPDATE qr_users SET reset_token='" . $escaped_token . "', updated_at=GETDATE() WHERE id=" . (int)$row['id']);

if ($update) {
    odbc_free_result($update);
    echo json_encode(array(
        'success' => true,
        'message' => '验证码已生成',
        'verify_code' => $token,
        'username' => $row['username']
    ));
} else {
    echo json_encode(array('success' => false, 'error' => '生成验证码失败'));
}

odbc_close($conn);
?>
