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
checkUserAuth();
$debug[] = 'auth_passed';

require_once dirname(__FILE__) . '/config.php';
$debug[] = 'config_loaded';
$debug[] = 'conn=' . ($conn ? 'ok' : 'null');

if (session_id() === '') { session_start(); }
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$debug[] = 'user_id=' . $user_id;

// Full SQL as in list.php
$sql = "SELECT id, code, target_url, label, status, scan_count, created_at, updated_at, user_id, expires_at, max_scans, group_name, qr_tag, qr_tag_font_size, qr_logo FROM qr_dynamic WHERE user_id = " . $user_id . " ORDER BY id DESC";
$debug[] = 'sql_built';

$result = odbc_exec($conn, $sql);
if (!$result) {
    $debug[] = 'query_failed=' . odbc_errormsg();
} else {
    $debug[] = 'query_ok';
    $count = 0;
    while ($row = odbc_fetch_array($result)) {
        $count++;
    }
    odbc_free_result($result);
    $debug[] = 'rows=' . $count;
}

echo json_encode(array('ok' => true, 'debug' => $debug));
odbc_close($conn);
