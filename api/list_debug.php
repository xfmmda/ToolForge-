<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Start session
session_start();
$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'admin';
$_SESSION['user_role'] = 1;

// Test ODBC
require_once dirname(__FILE__) . '/config.php';

if (!$conn) {
    echo json_encode(array('error' => 'ODBC connection is null'));
    exit;
}

// Test simple query
$test = odbc_exec($conn, "SELECT 1 AS val");
if (!$test) {
    echo json_encode(array('error' => 'Simple query failed: ' . odbc_errormsg()));
    odbc_close($conn);
    exit;
}
$row = odbc_fetch_array($test);
odbc_free_result($test);

// Test list query
$sql = "SELECT TOP 3 id, code, target_url, label, status FROM qr_dynamic WHERE user_id = 1 ORDER BY id DESC";
$result = odbc_exec($conn, $sql);
if (!$result) {
    $err = odbc_errormsg();
    echo json_encode(array('error' => 'List query failed: ' . $err, 'sql' => $sql));
    odbc_close($conn);
    exit;
}

$rows = array();
while ($row = odbc_fetch_array($result)) {
    $rows[] = $row;
}
odbc_free_result($result);

echo json_encode(array(
    'success' => true,
    'simple_query' => 'OK',
    'row_count' => count($rows),
    'data' => $rows
));

odbc_close($conn);
