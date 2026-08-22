<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__) . '/config.php';

$results = array();

// Add qr_tag column
$check = odbc_exec($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='qr_dynamic' AND COLUMN_NAME='qr_tag'");
$row = odbc_fetch_array($check);
odbc_free_result($check);
if (!$row) {
    $res = odbc_exec($conn, "ALTER TABLE qr_dynamic ADD qr_tag NVARCHAR(200) NULL");
    if ($res) { odbc_free_result($res); $results[] = 'Added qr_tag column'; }
    else { $results[] = 'qr_tag fail: ' . odbc_errormsg($conn); }
} else {
    $results[] = 'qr_tag already exists';
}

// Add qr_tag_font_size column
$check2 = odbc_exec($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='qr_dynamic' AND COLUMN_NAME='qr_tag_font_size'");
$row2 = odbc_fetch_array($check2);
odbc_free_result($check2);
if (!$row2) {
    $res2 = odbc_exec($conn, "ALTER TABLE qr_dynamic ADD qr_tag_font_size INT DEFAULT 14");
    if ($res2) { odbc_free_result($res2); $results[] = 'Added qr_tag_font_size column'; }
    else { $results[] = 'qr_tag_font_size fail: ' . odbc_errormsg($conn); }
} else {
    $results[] = 'qr_tag_font_size already exists';
}

// Add qr_logo column
$check3 = odbc_exec($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='qr_dynamic' AND COLUMN_NAME='qr_logo'");
$row3 = odbc_fetch_array($check3);
odbc_free_result($check3);
if (!$row3) {
    $res3 = odbc_exec($conn, "ALTER TABLE qr_dynamic ADD qr_logo NVARCHAR(MAX) NULL");
    if ($res3) { odbc_free_result($res3); $results[] = 'Added qr_logo column'; }
    else { $results[] = 'qr_logo fail: ' . odbc_errormsg($conn); }
} else {
    $results[] = 'qr_logo already exists';
}

odbc_close($conn);
echo json_encode(array('success' => true, 'results' => $results));
?>
