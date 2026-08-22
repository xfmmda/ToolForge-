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
$password = isset($input['password']) ? $input['password'] : '';
$email = isset($input['email']) ? trim($input['email']) : '';

// Validate
if (!$username || strlen($username) < 3 || strlen($username) > 20) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('success' => false, 'error' => '账号长度需3-20个字符'));
    odbc_close($conn);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]+$/u', $username)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('success' => false, 'error' => '账号只能包含字母、数字、下划线或中文'));
    odbc_close($conn);
    exit;
}

if (!$password || strlen($password) < 6) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('success' => false, 'error' => '密码长度至少6个字符'));
    odbc_close($conn);
    exit;
}

// Check if username already exists
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

// Insert new user (role=0 regular user)
$hash = hashPassword($password);
$escaped_email = str_replace("'", "''", $email);
$insert_sql = "INSERT INTO qr_users (username, password_hash, role, email) VALUES ('" . $escaped . "', '" . $hash . "', 0, '" . $escaped_email . "')";
$result = odbc_exec($conn, $insert_sql);

if ($result) {
    odbc_free_result($result);

    // Auto-login after registration
    $get = odbc_exec($conn, "SELECT id, username, role FROM qr_users WHERE username='" . $escaped . "'");
    if ($get) {
        $row = odbc_fetch_array($get);
        odbc_free_result($get);

        if (session_id() === '') {
            session_start();
        }
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = (int)$row['id'];
        $_SESSION['user_name'] = $row['username'];
        $_SESSION['user_role'] = (int)$row['role'];

        echo json_encode(array(
            'success' => true,
            'message' => '注册成功',
            'user' => array(
                'id' => (int)$row['id'],
                'username' => $row['username'],
                'role' => (int)$row['role']
            )
        ));
    } else {
        echo json_encode(array('success' => true, 'message' => '注册成功，请手动登录'));
    }
} else {
    $error = odbc_error($conn);
    echo json_encode(array('success' => false, 'error' => '注册失败: ' . $error));
}

odbc_close($conn);
?>
