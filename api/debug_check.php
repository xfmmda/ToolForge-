<?php
header('Content-Type: text/plain; charset=utf-8');
echo "PHP: " . PHP_VERSION . "\n";
echo "json_encode: " . (function_exists('json_encode') ? 'YES' : 'NO') . "\n";
echo "json_decode: " . (function_exists('json_decode') ? 'YES' : 'NO') . "\n";

require_once dirname(__FILE__) . '/config.php';

$r = odbc_exec($conn, "SELECT COUNT(*) AS cnt FROM qr_dynamic");
$row = odbc_fetch_array($r);
echo "Total records: " . $row['cnt'] . "\n";
odbc_free_result($r);

$r2 = odbc_exec($conn, "SELECT TOP 1 * FROM qr_dynamic");
if ($r2) {
    $row2 = odbc_fetch_array($r2);
    foreach ($row2 as $k => $v) {
        echo "  col: $k = " . (is_null($v) ? 'NULL' : substr((string)$v, 0, 60)) . "\n";
    }
    odbc_free_result($r2);
}

// Test simple INSERT
$test_ins = odbc_exec($conn, "INSERT INTO qr_dynamic (code, target_url, label, status, user_id) VALUES ('TESTDEBUG', 'https://example.com', 'test', 1, 1)");
echo "Simple insert: " . ($test_ins ? 'OK' : odbc_errormsg()) . "\n";
if ($test_ins) {
    odbc_free_result($test_ins);
    odbc_exec($conn, "DELETE FROM qr_dynamic WHERE code='TESTDEBUG'");
}

// Test full INSERT with all columns
$full_ins = odbc_exec($conn, "INSERT INTO qr_dynamic (code, target_url, label, status, user_id, qr_tag, qr_tag_font_size, qr_logo) VALUES ('TESTDEBUG2', 'https://example.com', 'test2', 1, 1, 'test_tag', 12, '')");
echo "Full insert: " . ($full_ins ? 'OK' : odbc_errormsg()) . "\n";
if ($full_ins) {
    odbc_free_result($full_ins);
    odbc_exec($conn, "DELETE FROM qr_dynamic WHERE code='TESTDEBUG2'");
}

odbc_close($conn);
?>
