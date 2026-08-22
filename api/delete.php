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

// Get user info from session
if (session_id() === '') {
    session_start();
}
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$user_role = isset($_SESSION['user_role']) ? (int)$_SESSION['user_role'] : 0;

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$code = isset($input['code']) ? trim($input['code']) : '';
if (!$code) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('error' => 'Missing code'));
    odbc_close($conn);
    exit;
}

$escaped = str_replace("'", "''", $code);

// Check ownership for non-admin users
if ($user_role !== 1) {
    $check_sql = "SELECT user_id FROM qr_dynamic WHERE code = '" . $escaped . "'";
    $check_res = odbc_exec($conn, $check_sql);
    if (!$check_res) {
        echo json_encode(array('success' => false, 'error' => 'Code not found'));
        odbc_close($conn);
        exit;
    }
    $check_row = odbc_fetch_array($check_res);
    odbc_free_result($check_res);
    if (!$check_row || (int)$check_row['user_id'] !== $user_id) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(array('success' => false, 'error' => '无权删除此空码'));
        odbc_close($conn);
        exit;
    }
}

$result = odbc_exec($conn, "DELETE FROM qr_dynamic WHERE code='{$escaped}'");

if ($result) {
    odbc_free_result($result);
    // Check affected rows
    $row_count = odbc_exec($conn, "SELECT @@ROWCOUNT AS cnt");
    if ($row_count) {
        $rc = odbc_fetch_array($row_count);
        $affected = (int)$rc['cnt'];
        odbc_free_result($row_count);
    } else {
        $affected = -1;
    }
    echo json_encode(array('success' => $affected > 0 || $affected === -1, 'message' => $affected > 0 ? 'Deleted' : 'Not found'));
} else {
    $error = odbc_error($conn);
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array('error' => 'Delete failed: ' . $error));
}

odbc_close($conn);
?>
