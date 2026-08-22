<?php
$DB_HOST = '127.0.0.1';
$DB_PORT = 1433;
$DB_USER = 'msfreepZXySzbis';
$DB_PASS = 'Yg8aa9lr!';
$DB_NAME = 'msfreepZXySzbis';

$conn = odbc_connect(
    "Driver={SQL Server};Server={$DB_HOST},{$DB_PORT};Database={$DB_NAME}",
    $DB_USER,
    $DB_PASS
);

if (!$conn) {
    $error = odbc_errormsg();
    if (!defined('QR_REDIRECT_MODE')) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'DB connection failed: ' . $error));
    }
    exit;
}
?>
