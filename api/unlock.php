<?php
require_once dirname(__FILE__) . '/auth.php';
checkAdminAuth();
require_once dirname(__FILE__) . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// Read input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    // Try form data
    $data = $_POST;
}

$username = isset($data['username']) ? trim($data['username']) : '';
$action = isset($data['action']) ? trim($data['action']) : 'unlock';

if ($username === '') {
    echo json_encode(array('success' => false, 'error' => 'Username required'));
    odbc_close($conn);
    exit;
}

// Cannot operate on self
if (session_id() === '') {
    session_start();
}
if (isset($_SESSION['user_name']) && $_SESSION['user_name'] === $username) {
    echo json_encode(array('success' => false, 'error' => '不能操作自己的账号'));
    odbc_close($conn);
    exit;
}

$escaped = str_replace("'", "''", $username);

if ($action === 'unlock') {
    // Reset failed_count and locked_until
    $sql = "UPDATE qr_users SET failed_count = 0, locked_until = NULL WHERE username = '" . $escaped . "'";
    $stmt = odbc_exec($conn, $sql);
    if (!$stmt) {
        echo json_encode(array('success' => false, 'error' => 'Unlock failed: ' . odbc_errormsg()));
        odbc_close($conn);
        exit;
    }
    odbc_free_result($stmt);

    // Check affected rows via @@ROWCOUNT
    $rc_res = odbc_exec($conn, "SELECT @@ROWCOUNT AS cnt");
    $rc_row = odbc_fetch_array($rc_res);
    $affected = (int)$rc_row['cnt'];
    odbc_free_result($rc_res);
    odbc_close($conn);

    if ($affected > 0) {
        echo json_encode(array('success' => true, 'message' => '账号 ' . $username . ' 已解锁'));
    } else {
        echo json_encode(array('success' => false, 'error' => '账号不存在'));
    }
} elseif ($action === 'delete') {
    // Delete user account (only non-admin)
    $sql = "DELETE FROM qr_users WHERE username = '" . $escaped . "' AND role != 1";
    $stmt = odbc_exec($conn, $sql);
    if (!$stmt) {
        echo json_encode(array('success' => false, 'error' => 'Delete failed: ' . odbc_errormsg()));
        odbc_close($conn);
        exit;
    }
    odbc_free_result($stmt);

    $rc_res = odbc_exec($conn, "SELECT @@ROWCOUNT AS cnt");
    $rc_row = odbc_fetch_array($rc_res);
    $affected = (int)$rc_row['cnt'];
    odbc_free_result($rc_res);
    odbc_close($conn);

    if ($affected > 0) {
        echo json_encode(array('success' => true, 'message' => '账号 ' . $username . ' 已删除'));
    } else {
        echo json_encode(array('success' => false, 'error' => '无法删除管理员账号或账号不存在'));
    }
} elseif ($action === 'lock') {
    // Manually lock an account (only non-admin)
    $sql = "UPDATE qr_users SET failed_count = 5, locked_until = '9999-12-31 00:00:00' WHERE username = '" . $escaped . "' AND role != 1";
    $stmt = odbc_exec($conn, $sql);
    if (!$stmt) {
        echo json_encode(array('success' => false, 'error' => 'Lock failed: ' . odbc_errormsg()));
        odbc_close($conn);
        exit;
    }
    odbc_free_result($stmt);

    $rc_res = odbc_exec($conn, "SELECT @@ROWCOUNT AS cnt");
    $rc_row = odbc_fetch_array($rc_res);
    $affected = (int)$rc_row['cnt'];
    odbc_free_result($rc_res);
    odbc_close($conn);

    if ($affected > 0) {
        echo json_encode(array('success' => true, 'message' => '账号 ' . $username . ' 已锁定'));
    } else {
        echo json_encode(array('success' => false, 'error' => '无法锁定管理员账号或账号不存在'));
    }
} elseif ($action === 'reset_password') {
    // Reset user password to a new value
    $new_password = isset($data['new_password']) ? trim($data['new_password']) : '';
    if ($new_password === '' || strlen($new_password) < 6) {
        echo json_encode(array('success' => false, 'error' => '新密码至少6个字符'));
        odbc_close($conn);
        exit;
    }
    $hashed = hashPassword($new_password);
    $escaped_hash = str_replace("'", "''", $hashed);
    $sql = "UPDATE qr_users SET password_hash = '" . $escaped_hash . "', failed_count = 0, locked_until = NULL WHERE username = '" . $escaped . "'";
    $stmt = odbc_exec($conn, $sql);
    if (!$stmt) {
        echo json_encode(array('success' => false, 'error' => 'Reset failed: ' . odbc_errormsg()));
        odbc_close($conn);
        exit;
    }
    odbc_free_result($stmt);
    odbc_close($conn);
    echo json_encode(array('success' => true, 'message' => '账号 ' . $username . ' 密码已重置'));
} else {
    echo json_encode(array('success' => false, 'error' => 'Unknown action'));
    odbc_close($conn);
}
?>
