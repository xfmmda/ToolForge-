<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$debug = array();
$debug[] = 'step0';

require_once dirname(__FILE__) . '/auth.php';
$debug[] = 'auth_loaded';

checkUserAuth();  // <-- This is the test
$debug[] = 'auth_passed';

require_once dirname(__FILE__) . '/config.php';
$debug[] = 'config_loaded';
$debug[] = 'conn=' . ($conn ? 'ok' : 'null');

$result = odbc_exec($conn, "SELECT TOP 1 id, code, label FROM qr_dynamic WHERE user_id = 1");
if ($result) {
    $row = odbc_fetch_array($result);
    odbc_free_result($result);
    $debug[] = 'row=' . $row['code'];
} else {
    $debug[] = 'query_err=' . odbc_errormsg();
}

echo json_encode(array('ok' => true, 'debug' => $debug));
odbc_close($conn);
