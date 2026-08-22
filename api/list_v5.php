<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$debug = array();
$debug[] = 'start';

// Without checkUserAuth — just set session manually
session_start();
$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = 1;

require_once dirname(__FILE__) . '/config.php';
$debug[] = 'conn_ok';

// Full SQL
$sql = "SELECT id, code, target_url, label, status, scan_count, created_at, updated_at, user_id, expires_at, max_scans, group_name, qr_tag, qr_tag_font_size, qr_logo FROM qr_dynamic WHERE user_id = 1 ORDER BY id DESC";
$result = odbc_exec($conn, $sql);
$debug[] = 'query_done';

if (!$result) {
    $debug[] = 'query_failed';
} else {
    $rows = array();
    while ($row = odbc_fetch_array($result)) {
        $rows[] = array(
            'id' => (int)$row['id'],
            'code' => $row['code'],
            'label' => $row['label']
        );
    }
    odbc_free_result($result);
    $debug[] = 'fetched_' . count($rows);
    
    $json = json_encode(array('success' => true, 'data' => $rows, 'debug' => $debug));
    if ($json === false) {
        echo json_encode(array('error' => 'json_encode failed', 'debug' => $debug));
    } else {
        echo $json;
    }
}

odbc_close($conn);
