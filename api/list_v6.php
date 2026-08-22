<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$debug = array();
$debug[] = 'start';

require_once dirname(__FILE__) . '/auth.php';
$debug[] = 'auth_ok';
checkUserAuth();
$debug[] = 'logged_in';

require_once dirname(__FILE__) . '/config.php';
$debug[] = 'conn_ok';

$sql = "SELECT id, code, target_url, label, status, scan_count, created_at, updated_at, user_id, expires_at, max_scans, group_name, qr_tag, qr_tag_font_size, qr_logo FROM qr_dynamic WHERE user_id = 1 ORDER BY id DESC";
$result = odbc_exec($conn, $sql);
$debug[] = 'query_ok';

$rows = array();
while ($row = odbc_fetch_array($result)) {
    $rows[] = array(
        'id' => (int)$row['id'],
        'code' => $row['code'],
        'label' => $row['label']
    );
}
odbc_free_result($result);
$debug[] = 'rows=' . count($rows);

echo json_encode(array('success' => true, 'data' => $rows, 'debug' => $debug));
odbc_close($conn);
