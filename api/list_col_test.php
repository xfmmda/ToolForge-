<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$debug = array();

require_once dirname(__FILE__) . '/auth.php';
checkUserAuth();
require_once dirname(__FILE__) . '/config.php';

// Test each suspect column individually
$suspect_cols = array('group_name', 'qr_tag', 'qr_tag_font_size', 'qr_logo', 'max_scans', 'expires_at');

foreach ($suspect_cols as $col) {
    $sql = "SELECT TOP 1 $col FROM qr_dynamic WHERE user_id = 1";
    $result = @odbc_exec($conn, $sql);
    if ($result) {
        $row = odbc_fetch_array($result);
        odbc_free_result($result);
        $debug[$col] = 'ok: ' . (isset($row[$col]) ? var_export($row[$col], true) : 'no_key');
    } else {
        $debug[$col] = 'FAILED: ' . odbc_errormsg();
    }
}

echo json_encode(array('ok' => true, 'debug' => $debug));
odbc_close($conn);
