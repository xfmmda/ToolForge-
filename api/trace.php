<?php
// Turn off output buffering to see partial output
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo "1_start\n"; flush();

require_once dirname(__FILE__) . '/auth.php';
echo "2_auth_loaded\n"; flush();

// Skip checkUserAuth to avoid 401
session_start();
$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = 1;
echo "3_session_set\n"; flush();

require_once dirname(__FILE__) . '/config.php';
echo "4_config_loaded\n"; flush();

$sql = "SELECT id, code, target_url, label, status, scan_count, created_at, updated_at, user_id, expires_at, max_scans, group_name, qr_tag, qr_tag_font_size, qr_logo FROM qr_dynamic WHERE user_id = 1 ORDER BY id DESC";
echo "5_sql_built\n"; flush();

$result = odbc_exec($conn, $sql);
echo "6_query_done\n"; flush();

if (!$result) {
    echo "7_query_failed: " . odbc_errormsg() . "\n";
} else {
    echo "7_query_ok\n"; flush();
    $count = 0;
    while ($row = odbc_fetch_array($result)) {
        $count++;
    }
    echo "8_fetched_{$count}_rows\n"; flush();
    odbc_free_result($result);
    echo "9_freed\n"; flush();
}

echo "10_done";
odbc_close($conn);
