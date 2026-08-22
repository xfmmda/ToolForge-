<?php
require_once dirname(__FILE__) . '/auth.php';
checkAdminAuth();
require_once dirname(__FILE__) . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT id, username, role, email, failed_count, locked_until, created_at FROM qr_users ORDER BY id ASC";
$stmt = odbc_exec($conn, $sql);
if (!$stmt) {
    echo json_encode(array('success' => false, 'error' => 'Query failed: ' . odbc_errormsg()));
    exit;
}

$rows = array();
while ($row = odbc_fetch_array($stmt)) {
    // Check if account is locked
    $is_locked = false;
    if ($row['locked_until'] !== null && $row['locked_until'] !== '') {
        $locked_time = strtotime($row['locked_until']);
        if ($locked_time > time()) {
            $is_locked = true;
        }
    }
    if ((int)$row['failed_count'] >= 5) {
        $is_locked = true;
    }
    $rows[] = array(
        'id' => (int)$row['id'],
        'username' => $row['username'],
        'role' => (int)$row['role'],
        'role_name' => ((int)$row['role'] === 1) ? '管理员' : '普通用户',
        'email' => ($row['email'] !== null) ? $row['email'] : '',
        'failed_count' => (int)$row['failed_count'],
        'locked_until' => ($row['locked_until'] !== null) ? $row['locked_until'] : '',
        'is_locked' => $is_locked,
        'created_at' => $row['created_at']
    );
}

odbc_free_result($stmt);
odbc_close($conn);

echo json_encode(array('success' => true, 'data' => $rows));
?>
