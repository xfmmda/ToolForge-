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

// Test progressively larger SELECT
$tests = array(
    '1' => 'id, code, target_url, label',
    '2' => 'id, code, target_url, label, status, scan_count',
    '3' => 'id, code, target_url, label, status, scan_count, created_at, updated_at',
    '4' => 'id, code, target_url, label, status, scan_count, created_at, updated_at, user_id',
    '5' => 'id, code, target_url, label, status, scan_count, created_at, updated_at, user_id, expires_at, max_scans',
);

foreach ($tests as $key => $cols) {
    $sql = "SELECT $cols FROM qr_dynamic WHERE user_id = 1 ORDER BY id DESC";
    $result = @odbc_exec($conn, $sql);
    if ($result) {
        $count = 0;
        while (odbc_fetch_array($result)) $count++;
        odbc_free_result($result);
        $debug[$key] = "ok: $count rows";
    } else {
        $debug[$key] = 'FAILED: ' . odbc_errormsg();
    }
}

echo json_encode(array('ok' => true, 'debug' => $debug));
odbc_close($conn);
