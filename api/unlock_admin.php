<?php
header('Content-Type: application/json; charset=utf-8');

$DB_HOST = '127.0.0.1';
$DB_PORT = 1433;
$DB_USER = 'msfreepZXySzbis';
$DB_PASS = 'Yg8aa9lr!';
$DB_NAME = 'msfreepZXySzbis';

$conn = odbc_connect(
    "Driver={SQL Server};Server={$DB_HOST},{$DB_PORT};Database={$DB_NAME}",
    $DB_USER,
    $DB_PASS
);

if (!$conn) {
    echo json_encode(array('error' => 'DB connection failed: ' . odbc_errormsg()));
    exit;
}

$sql = "SELECT id, username, role, failed_count, locked_until FROM qr_users WHERE role=1";
$result = odbc_exec($conn, $sql);

$admins = array();
while ($row = odbc_fetch_array($result)) {
    $admins[] = array(
        'id' => (int)$row['id'],
        'username' => $row['username'],
        'failed_count' => (int)$row['failed_count'],
        'locked_until' => $row['locked_until']
    );
}
odbc_free_result($result);

$unlocked = array();
foreach ($admins as $admin) {
    $reset_sql = "UPDATE qr_users SET failed_count=0, locked_until=NULL, updated_at=GETDATE() WHERE id=" . (int)$admin['id'];
    odbc_exec($conn, $reset_sql);
    $unlocked[] = $admin['username'];
}

echo json_encode(array(
    'success' => true,
    'message' => 'Admin accounts unlocked',
    'admins_found' => $admins,
    'unlocked' => $unlocked
));

odbc_close($conn);
?>
