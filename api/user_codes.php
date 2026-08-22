<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once dirname(__FILE__) . '/auth.php';
checkAdminAuth();

require_once dirname(__FILE__) . '/config.php';

$target_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($target_user_id <= 0) {
    echo json_encode(array('success' => false, 'error' => 'Missing user_id'));
    odbc_close($conn);
    exit;
}

$base_url = getBaseUrl();

// Note: qr_logo is binary data that cannot be JSON-encoded in PHP 5.5
// Removed from SELECT to avoid json_encode failure
$sql = "SELECT id, code, target_url, label, status, scan_count, "
    . "CONVERT(NVARCHAR(19), created_at, 120) AS created_at, "
    . "CONVERT(NVARCHAR(19), updated_at, 120) AS updated_at, "
    . "CONVERT(NVARCHAR(19), expires_at, 120) AS expires_at, "
    . "max_scans, group_name, qr_tag, qr_tag_font_size "
    . "FROM qr_dynamic WHERE user_id = " . $target_user_id . " ORDER BY id DESC";
$result = odbc_exec($conn, $sql);

if (!$result) {
    $err = odbc_error($conn);
    echo json_encode(array('success' => false, 'error' => 'SQL query failed: ' . ($err ? $err : 'unknown')));
    odbc_close($conn);
    exit;
}

function cleanUtf8($val) {
    if ($val === null) return null;
    $s = (string)$val;
    // Remove control chars that break JSON
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s);
    // Re-encode to ensure valid UTF-8
    $s = iconv('UTF-8', 'UTF-8//IGNORE', $s);
    return $s;
}

$rows = array();
while ($row = odbc_fetch_array($result)) {
    $rows[] = array(
        'id'                => (int)$row['id'],
        'code'              => cleanUtf8($row['code']),
        'target_url'        => cleanUtf8($row['target_url']),
        'label'             => cleanUtf8($row['label']),
        'status'            => (int)$row['status'],
        'scan_count'        => (int)$row['scan_count'],
        'created_at'        => cleanUtf8($row['created_at']),
        'updated_at'        => cleanUtf8($row['updated_at']),
        'expires_at'        => isset($row['expires_at']) ? cleanUtf8($row['expires_at']) : '',
        'max_scans'         => $row['max_scans'] !== null ? (int)$row['max_scans'] : null,
        'group_name'        => isset($row['group_name']) ? cleanUtf8($row['group_name']) : null,
        'qr_tag'            => isset($row['qr_tag']) ? cleanUtf8($row['qr_tag']) : null,
        'qr_tag_font_size'  => isset($row['qr_tag_font_size']) ? (int)$row['qr_tag_font_size'] : 14,
        'redirect_url'      => $base_url . '/r.php?id=' . $row['code']
    );
}
odbc_free_result($result);
odbc_close($conn);

$json = json_encode(array('success' => true, 'data' => $rows));
if ($json === false) {
    echo json_encode(array('success' => false, 'error' => 'JSON encode failed: ' . json_last_error_msg()));
} else {
    echo $json;
}
?>
