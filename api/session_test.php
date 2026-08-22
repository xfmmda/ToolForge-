<?php
header('Content-Type: application/json; charset=utf-8');

$save_path = session_save_path();
$use_cookies = ini_get('session.use_cookies');
$use_only_cookies = ini_get('session.use_only_cookies');
$result = array(
    'php_version' => phpversion(),
    'session_save_path' => $save_path,
    'session.use_cookies' => $use_cookies,
    'session.use_only_cookies' => $use_only_cookies
);

if (session_id() === '') {
    $started = @session_start();
    $result['session_start'] = $started ? 'ok' : 'fail';
} else {
    $result['session_start'] = 'already';
}
$result['session_id'] = session_id();

// Test login API connectivity
require_once dirname(__FILE__) . '/config.php';
$test_conn = @odbc_connect('Driver={SQL Server};Server=127.0.0.1,1433;Database=msfreepZXySzbis', 'msfreepZXySzbis', 'Yg8aa9lr!');
$result['db_connect'] = $test_conn ? 'ok' : 'fail';
if ($test_conn) odbc_close($test_conn);

echo json_encode($result);
?>
