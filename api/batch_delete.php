<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

$codes = isset($input['codes']) ? $input['codes'] : array();
if (!is_array($codes) || empty($codes)) {
    echo json_encode(array('success' => false, 'error' => 'No codes provided'));
    odbc_close($conn); exit;
}

$deleted = 0;
$failed = 0;
foreach ($codes as $code) {
    $code = trim($code);
    if (!$code) continue;
    $escaped = sql_escape($code);

    // Check ownership for non-admin
    if ($user_role !== 1) {
        $chk = odbc_exec($conn, "SELECT user_id FROM qr_dynamic WHERE code='" . $escaped . "'");
        if ($chk) {
            $cr = odbc_fetch_array($chk);
            odbc_free_result($chk);
            if (!$cr || (int)$cr['user_id'] !== $user_id) { $failed++; continue; }
        } else { $failed++; continue; }
    }

    $res = odbc_exec($conn, "DELETE FROM qr_dynamic WHERE code='" . $escaped . "'");
    if ($res) { odbc_free_result($res); $deleted++; }
    else { $failed++; }
}

echo json_encode(array('success' => true, 'deleted' => $deleted, 'failed' => $failed));
odbc_close($conn);
?>
