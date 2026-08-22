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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }

$old_password = isset($input['old_password']) ? $input['old_password'] : '';
$new_password = isset($input['new_password']) ? $input['new_password'] : '';

if (!$old_password || !$new_password) {
    echo json_encode(array('success' => false, 'error' => '请填写原密码和新密码'));
    odbc_close($conn); exit;
}
if (strlen($new_password) < 6) {
    echo json_encode(array('success' => false, 'error' => '新密码至少6个字符'));
    odbc_close($conn); exit;
}

// Verify old password
$hash = hashPassword($old_password);
$sql = "SELECT password_hash FROM qr_users WHERE id = " . $user_id;
$result = odbc_exec($conn, $sql);
if (!$result) {
    echo json_encode(array('success' => false, 'error' => '查询失败'));
    odbc_close($conn); exit;
}
$row = odbc_fetch_array($result);
odbc_free_result($result);
if (!$row || $row['password_hash'] !== $hash) {
    echo json_encode(array('success' => false, 'error' => '原密码错误'));
    odbc_close($conn); exit;
}

// Update password
$new_hash = hashPassword($new_password);
$update_sql = "UPDATE qr_users SET password_hash='" . sql_escape($new_hash) . "', updated_at=GETDATE(), failed_count=0 WHERE id=" . $user_id;
$res = odbc_exec($conn, $update_sql);
if ($res) { odbc_free_result($res); }

echo json_encode(array('success' => true, 'message' => '密码修改成功'));
odbc_close($conn);
?>
