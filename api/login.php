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

define('MAX_FAILED_ATTEMPTS', 5);
define('LOCKED_FLAG_DATE', '9999-12-31 23:59:59'); // Permanent lock flag

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (!$username || !$password) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('success' => false, 'error' => '请输入账号和密码'));
    odbc_close($conn);
    exit;
}

// Step 1: Check if account exists and is locked
$escaped = str_replace("'", "''", $username);
$check_sql = "SELECT id, username, role, password_hash, failed_count, locked_until FROM qr_users WHERE username='" . $escaped . "'";
$check_result = odbc_exec($conn, $check_sql);

if (!$check_result) {
    echo json_encode(array('success' => false, 'error' => '数据库查询失败'));
    odbc_close($conn);
    exit;
}

$user_row = odbc_fetch_array($check_result);
odbc_free_result($check_result);

// Account doesn't exist — return generic error (don't reveal whether username exists)
if (!$user_row) {
    echo json_encode(array('success' => false, 'error' => '账号或密码错误'));
    odbc_close($conn);
    exit;
}

$user_id = (int)$user_row['id'];
$failed_count = isset($user_row['failed_count']) ? (int)$user_row['failed_count'] : 0;
$locked_until = isset($user_row['locked_until']) ? $user_row['locked_until'] : null;

// Step 2: Check if account is locked
$is_locked = false;
if ($locked_until !== null && $locked_until !== '') {
    // Compare locked_until with current time
    $now_sql = "SELECT GETDATE() AS now_time";
    $now_res = odbc_exec($conn, $now_sql);
    $now_row = odbc_fetch_array($now_res);
    odbc_free_result($now_res);
    $current_time = isset($now_row['now_time']) ? $now_row['now_time'] : '';

    if ($current_time !== '' && $locked_until >= $current_time) {
        $is_locked = true;
    }
}

if ($is_locked) {
    echo json_encode(array(
        'success' => false,
        'error' => '账号已被锁定，请联系作者解锁',
        'code' => 'ACCOUNT_LOCKED'
    ));
    odbc_close($conn);
    exit;
}

// Step 3: Verify password
$hash = hashPassword($password);
if ($user_row['password_hash'] !== $hash) {
    // Password wrong — increment failed_count
    $new_count = $failed_count + 1;

    if ($new_count >= MAX_FAILED_ATTEMPTS) {
        // Lock the account permanently
        $lock_sql = "UPDATE qr_users SET failed_count=" . $new_count . ", locked_until='" . LOCKED_FLAG_DATE . "', updated_at=GETDATE() WHERE id=" . $user_id;
        odbc_exec($conn, $lock_sql);
        echo json_encode(array(
            'success' => false,
            'error' => '密码错误次数过多，账号已被锁定，请联系作者解锁',
            'code' => 'ACCOUNT_LOCKED'
        ));
    } else {
        // Just increment count
        $inc_sql = "UPDATE qr_users SET failed_count=" . $new_count . ", updated_at=GETDATE() WHERE id=" . $user_id;
        odbc_exec($conn, $inc_sql);
        $remaining = MAX_FAILED_ATTEMPTS - $new_count;
        echo json_encode(array(
            'success' => false,
            'error' => '账号或密码错误（还剩 ' . $remaining . ' 次尝试机会）'
        ));
    }
    odbc_close($conn);
    exit;
}

// Step 4: Password correct — reset failed_count and locked_until, set session
$reset_sql = "UPDATE qr_users SET failed_count=0, locked_until=NULL, updated_at=GETDATE() WHERE id=" . $user_id;
odbc_exec($conn, $reset_sql);

if (session_id() === '') {
    session_start();
}
$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = (int)$user_row['id'];
$_SESSION['user_name'] = $user_row['username'];
$_SESSION['user_role'] = (int)$user_row['role'];

echo json_encode(array(
    'success' => true,
    'message' => '登录成功',
    'user' => array(
        'id' => (int)$user_row['id'],
        'username' => $user_row['username'],
        'role' => (int)$user_row['role']
    )
));

odbc_close($conn);
?>
