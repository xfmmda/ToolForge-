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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }
// Also support GET for simple queries
if (empty($input) && $_SERVER['REQUEST_METHOD'] === 'GET') { $input = $_GET; }

$code = isset($input['code']) ? trim($input['code']) : '';

if (!$code) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('success' => false, 'error' => '请输入短码'));
    odbc_close($conn); exit;
}

$escaped_code = sql_escape($code);

// Fetch the code info
$sql = "SELECT id, code, target_url, label, status, scan_count, created_at, updated_at, user_id, expires_at, max_scans, group_name, qr_tag, qr_tag_font_size FROM qr_dynamic WHERE code = '" . $escaped_code . "'";
$result = odbc_exec($conn, $sql);

if (!$result) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array('success' => false, 'error' => '查询失败'));
    odbc_close($conn); exit;
}

$row = odbc_fetch_array($result);
odbc_free_result($result);

if (!$row) {
    echo json_encode(array('success' => false, 'error' => '短码不存在，请检查是否正确', 'code_type' => 'not_found'));
    odbc_close($conn); exit;
}

$owner_id = (int)$row['user_id'];

// Check ownership for non-admin users
if ($user_role !== 1 && $owner_id !== $user_id) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(array(
        'success' => false,
        'error' => '⛔ 越权：此空码不属于您的账号，无法修改',
        'code_type' => 'unauthorized',
        'owner_hint' => '该空码由其他用户创建'
    ));
    odbc_close($conn); exit;
}

// Check expiration
$expired = false;
if (!empty($row['expires_at']) && strtotime($row['expires_at']) < time()) {
    $expired = true;
}

// Build response
$base_url = getBaseUrl();
$created = isset($row['created_at']) ? (string)$row['created_at'] : '';
$updated = isset($row['updated_at']) ? (string)$row['updated_at'] : '';
$expires = isset($row['expires_at']) ? (string)$row['expires_at'] : '';

echo json_encode(array(
    'success' => true,
    'data' => array(
        'id' => (int)$row['id'],
        'code' => $row['code'],
        'target_url' => $row['target_url'],
        'label' => $row['label'],
        'status' => (int)$row['status'],
        'scan_count' => (int)$row['scan_count'],
        'created_at' => $created,
        'updated_at' => $updated,
        'user_id' => $owner_id,
        'expires_at' => $expires,
        'max_scans' => $row['max_scans'] !== null ? (int)$row['max_scans'] : null,
        'group_name' => isset($row['group_name']) ? $row['group_name'] : null,
        'qr_tag' => isset($row['qr_tag']) ? $row['qr_tag'] : null,
        'qr_tag_font_size' => isset($row['qr_tag_font_size']) ? (int)$row['qr_tag_font_size'] : 14,
        'redirect_url' => $base_url . '/r.php?id=' . $row['code'],
        'expired' => $expired,
        'maxed_out' => ($row['max_scans'] !== null && (int)$row['scan_count'] >= (int)$row['max_scans'])
    )
));
odbc_close($conn);
?>
