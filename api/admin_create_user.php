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

// Admin check
if (session_id() === '') session_start();
if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(array('success' => false, 'error' => '需要管理员权限'));
    odbc_close($conn);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$role = isset($input['role']) && $input['role'] == 1 ? 1 : 0;

// Validate
if (!$username || strlen($username) < 3 || strlen($username) > 20) {
    echo json_encode(array('success' => false, 'error' => '账号长度需3-20个字符'));
    odbc_close($conn);
    exit;
}
if (!preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]+$/u', $username)) {
    echo json_encode(array('success' => false, 'error' => '账号只能包含字母、数字、下划线或中文'));
    odbc_close($conn);
    exit;
}
if (!$password || strlen($password) < 6) {
    echo json_encode(array('success' => false, 'error' => '密码长度至少6个字符'));
    odbc_close($conn);
    exit;
}

// Check duplicate
$escaped = str_replace("'", "''", $username);
$check = odbc_exec($conn, "SELECT id FROM qr_users WHERE username='" . $escaped . "'");
if ($check) {
    $existing = odbc_fetch_array($check);
    odbc_free_result($check);
    if ($existing) {
        echo json_encode(array('success' => false, 'error' => '该账号已被注册'));
        odbc_close($conn);
        exit;
    }
}

// Insert
$hash = hashPassword($password);
$escaped_email = str_replace("'", "''", $email);
$sql = "INSERT INTO qr_users (username, password_hash, role, email, failed_count) VALUES ('" . $escaped . "', '" . $hash . "', " . $role . ", '" . $escaped_email . "', 0)";
$result = odbc_exec($conn, $sql);

if ($result) {
    odbc_free_result($result);
    echo json_encode(array('success' => true, 'message' => '用户创建成功'));
} else {
    $err = odbc_error($conn);
    echo json_encode(array('success' => false, 'error' => '创建失败: ' . $err));
}

odbc_close($conn);
?>
