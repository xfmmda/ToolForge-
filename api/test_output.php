<?php
echo 'step0_';
flush();
ob_flush();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
echo 'step1_';
flush();
ob_flush();

require_once dirname(__FILE__) . '/config.php';
echo 'step2_';
flush();
ob_flush();

$result = odbc_exec($conn, "SELECT TOP 1 id, code FROM qr_dynamic");
if ($result) {
    $row = odbc_fetch_array($result);
    echo 'step3_' . $row['code'];
} else {
    echo 'step3_err';
}
odbc_close($conn);
