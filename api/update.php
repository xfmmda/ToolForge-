<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(array('error' => 'POST only'));
    exit;
}

require_once dirname(__FILE__) . '/auth.php';
checkUserAuth();
require_once dirname(__FILE__) . '/config.php';

if (session_id() === '') { session_start(); }
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$user_role = isset($_SESSION['user_role']) ? (int)$_SESSION['user_role'] : 0;

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }

$code = isset($input['code']) ? trim($input['code']) : '';
if (!$code) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('error' => 'Missing code'));
    odbc_close($conn); exit;
}

$escaped_code = sql_escape($code);

// Check ownership for non-admin
if ($user_role !== 1) {
    $check_sql = "SELECT user_id FROM qr_dynamic WHERE code = '" . $escaped_code . "'";
    $check_res = odbc_exec($conn, $check_sql);
    if (!$check_res) {
        echo json_encode(array('success' => false, 'error' => 'Code not found'));
        odbc_close($conn); exit;
    }
    $check_row = odbc_fetch_array($check_res);
    odbc_free_result($check_res);
    if (!$check_row || (int)$check_row['user_id'] !== $user_id) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(array('success' => false, 'error' => '无权修改此空码'));
        odbc_close($conn); exit;
    }
}

$fields = array();
if (array_key_exists('target_url', $input)) $fields[] = "target_url='" . sql_escape(trim($input['target_url'])) . "'";
if (array_key_exists('label', $input)) $fields[] = "label='" . sql_escape(trim($input['label'])) . "'";
if (array_key_exists('status', $input)) $fields[] = "status=" . (int)$input['status'];
if (array_key_exists('expires_at', $input)) {
    $v = trim($input['expires_at']);
    $fields[] = $v ? "expires_at='" . sql_escape($v) . "'" : "expires_at=NULL";
}
if (array_key_exists('max_scans', $input)) {
    $v = $input['max_scans'];
    $fields[] = ($v !== '' && $v !== null) ? "max_scans=" . (int)$v : "max_scans=NULL";
}
if (array_key_exists('group_name', $input)) {
    $v = trim($input['group_name']);
    $fields[] = $v ? "group_name='" . sql_escape($v) . "'" : "group_name=NULL";
}
if (array_key_exists('qr_tag', $input)) {
    $v = trim($input['qr_tag']);
    $fields[] = $v ? "qr_tag='" . sql_escape($v) . "'" : "qr_tag=NULL";
}
if (array_key_exists('qr_tag_font_size', $input)) {
    $fields[] = "qr_tag_font_size=" . (int)$input['qr_tag_font_size'];
}
if (array_key_exists('qr_logo', $input)) {
    $v = trim($input['qr_logo']);
    $fields[] = $v ? "qr_logo='" . sql_escape($v) . "'" : "qr_logo=NULL";
}

if (empty($fields)) {
    echo json_encode(array('success' => true, 'message' => 'No fields to update'));
    odbc_close($conn); exit;
}
// Try to update updated_at only if column exists (older tables may not have it)
$check_col = odbc_exec($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='qr_dynamic' AND COLUMN_NAME='updated_at'");
$has_updated_at = odbc_fetch_array($check_col) ? true : false;
if ($check_col) odbc_free_result($check_col);
if ($has_updated_at) $fields[] = "updated_at=GETDATE()";

$sql = "UPDATE qr_dynamic SET " . implode(', ', $fields) . " WHERE code='" . $escaped_code . "'";
$result = odbc_exec($conn, $sql);

if ($result) {
    odbc_free_result($result);
    echo json_encode(array('success' => true, 'message' => 'Updated'));
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array('error' => 'Update failed: ' . odbc_error($conn)));
}
odbc_close($conn);
?>
