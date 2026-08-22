<?php
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo "1_start\n";
require_once dirname(__FILE__) . '/config.php';
echo "2_config\n";

// Test with basic columns only
$sql = "SELECT id, code, target_url, label FROM qr_dynamic WHERE user_id = 1 ORDER BY id DESC";
echo "3_sql\n";

$result = odbc_exec($conn, $sql);
echo "4_query\n";

$count = 0;
while ($row = odbc_fetch_array($result)) {
    $count++;
    echo "5_row{$count}:" . $row['code'] . "\n";
}
odbc_free_result($result);
echo "6_done_{$count}_rows";
odbc_close($conn);
