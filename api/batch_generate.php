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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }

$count     = isset($input['count']) ? (int)$input['count'] : 1;
$group_name = isset($input['group_name']) ? trim($input['group_name']) : '';
$label     = isset($input['label']) ? trim($input['label']) : '';
$status    = isset($input['status']) ? (int)$input['status'] : 1;
$expires_at = isset($input['expires_at']) && $input['expires_at'] !== '' ? trim($input['expires_at']) : null;
$max_scans = isset($input['max_scans']) && $input['max_scans'] !== '' ? (int)$input['max_scans'] : null;

if ($count < 1) $count = 1;
if ($count > 100) $count = 100;

$base_url = getBaseUrl();

$inserted = array();
for ($i = 0; $i < $count; $i++) {
    $code = substr(md5(uniqid(mt_rand(), true)), 0, 12);

    $seq = $i + 1;
    $auto_label = '';
    if ($group_name !== '') {
        if ($label !== '') {
            $auto_label = $label . $seq;
        } else {
            $auto_label = $group_name . '-' . $seq;
        }
    } elseif ($label !== '') {
        $auto_label = $label . $seq;
    }

    $exp_val = $expires_at ? "'" . sql_escape($expires_at) . "'" : "NULL";
    $scan_val = $max_scans !== null ? $max_scans : "NULL";
    $sql = "INSERT INTO qr_dynamic (code, target_url, label, group_name, status, user_id, expires_at, max_scans) " .
           "VALUES ('" . sql_escape($code) . "', '', '" . sql_escape($auto_label) . "', '" . sql_escape($group_name) . "', " . $status . ", " . $user_id . ", " . $exp_val . ", " . $scan_val . ")";
    $result = odbc_exec($conn, $sql);
    if ($result) {
        odbc_free_result($result);
        $inserted[] = array(
            'code' => $code,
            'label' => $auto_label,
            'status' => $status,
            'group_name' => $group_name,
            'redirect_url' => $base_url . '/r.php?id=' . $code
        );
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(array('error' => 'Insert failed: ' . odbc_error($conn), 'code' => $code));
        odbc_close($conn);
        exit;
    }
}

echo json_encode(array('success' => true, 'count' => $count, 'data' => $inserted));
odbc_close($conn);
?>
