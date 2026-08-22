<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once dirname(__FILE__) . '/auth.php';
checkUserAuth();

require_once dirname(__FILE__) . '/config.php';

if (session_id() === '') { session_start(); }
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// NOTE: qr_logo removed from SELECT to avoid PHP 5.2 memory crash on large base64 data
// CONVERT dates to ISO format for consistent JSON output regardless of ODBC locale
$sql = "SELECT id, code, target_url, label, status, scan_count, "
     . "CONVERT(NVARCHAR(19), created_at, 120) AS created_at, "
     . "CONVERT(NVARCHAR(19), updated_at, 120) AS updated_at, "
     . "user_id, "
     . "CONVERT(NVARCHAR(19), expires_at, 120) AS expires_at, "
     . "max_scans, group_name, qr_tag, qr_tag_font_size "
     . "FROM qr_dynamic WHERE user_id = " . $user_id . " ORDER BY id DESC";

$result = odbc_exec($conn, $sql);
if (!$result) {
    $err = odbc_errormsg();
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array('success' => false, 'error' => 'Query failed: ' . $err));
    odbc_close($conn);
    exit;
}

$rows = array();
$base_url = getBaseUrl();

while ($row = odbc_fetch_array($result)) {
    $created = isset($row['created_at']) ? (string)$row['created_at'] : '';
    $updated = isset($row['updated_at']) ? (string)$row['updated_at'] : '';
    $expires = isset($row['expires_at']) ? (string)$row['expires_at'] : '';
    $rows[] = array(
        'id' => (int)$row['id'],
        'code' => $row['code'],
        'target_url' => $row['target_url'],
        'label' => $row['label'],
        'status' => (int)$row['status'],
        'scan_count' => (int)$row['scan_count'],
        'created_at' => $created,
        'updated_at' => $updated,
        'redirect_url' => $base_url . '/r.php?id=' . $row['code'],
        'user_id' => (int)$row['user_id'],
        'expires_at' => $expires,
        'max_scans' => $row['max_scans'] !== null ? (int)$row['max_scans'] : null,
        'group_name' => isset($row['group_name']) ? $row['group_name'] : null,
        'qr_tag' => isset($row['qr_tag']) ? $row['qr_tag'] : null,
        'qr_tag_font_size' => isset($row['qr_tag_font_size']) ? (int)$row['qr_tag_font_size'] : 14,
        'qr_logo' => null
    );
}
odbc_free_result($result);

echo json_encode(array('success' => true, 'data' => $rows));
odbc_close($conn);
