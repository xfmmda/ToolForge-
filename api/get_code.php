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
$user_role = isset($_SESSION['user_role']) ? (int)$_SESSION['user_role'] : 0;

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
if (!$code) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('success' => false, 'error' => 'Missing code'));
    odbc_close($conn); exit;
}

$escaped_code = sql_escape($code);
$sql = "SELECT id, code, target_url, label, status, scan_count, "
    . "CONVERT(NVARCHAR(19), created_at, 120) AS created_at, "
    . "CONVERT(NVARCHAR(19), updated_at, 120) AS updated_at, "
    . "user_id, "
    . "CONVERT(NVARCHAR(19), expires_at, 120) AS expires_at, "
    . "max_scans, group_name, qr_tag, qr_tag_font_size, qr_logo "
    . "FROM qr_dynamic WHERE code = '" . $escaped_code . "'";
$result = odbc_exec($conn, $sql);
if (!$result) {
    echo json_encode(array('success' => false, 'error' => odbc_errormsg($conn)));
    odbc_close($conn); exit;
}
$row = odbc_fetch_array($result);
odbc_free_result($result);

if (!$row) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(array('success' => false, 'error' => 'Code not found'));
    odbc_close($conn); exit;
}

// Check ownership for non-admin
if ($user_role !== 1 && (int)$row['user_id'] !== $user_id) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(array('success' => false, 'error' => '无权访问'));
    odbc_close($conn); exit;
}

$created = isset($row['created_at']) ? (string)$row['created_at'] : '';
$updated = isset($row['updated_at']) ? (string)$row['updated_at'] : '';
$expires = isset($row['expires_at']) ? (string)$row['expires_at'] : '';

// Truncate qr_logo if very large (avoid JSON memory issue)
$qr_logo = isset($row['qr_logo']) ? $row['qr_logo'] : null;
if ($qr_logo && strlen($qr_logo) > 2000000) {
    $qr_logo = null; // Too large, skip
}

echo json_encode(array('success' => true, 'data' => array(
    'id' => (int)$row['id'],
    'code' => $row['code'],
    'target_url' => $row['target_url'],
    'label' => $row['label'],
    'status' => (int)$row['status'],
    'scan_count' => (int)$row['scan_count'],
    'created_at' => $created,
    'updated_at' => $updated,
    'user_id' => (int)$row['user_id'],
    'expires_at' => $expires,
    'max_scans' => $row['max_scans'] !== null ? (int)$row['max_scans'] : null,
    'group_name' => isset($row['group_name']) ? $row['group_name'] : null,
    'qr_tag' => isset($row['qr_tag']) ? $row['qr_tag'] : null,
    'qr_tag_font_size' => isset($row['qr_tag_font_size']) ? (int)$row['qr_tag_font_size'] : 14,
    'qr_logo' => $qr_logo
)));

odbc_close($conn);
?>
