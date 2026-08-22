<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== DEBUG START ===\n";

require_once dirname(__FILE__) . '/config.php';

echo "DB conn = ";
if ($conn) { echo "OK\n"; } else { echo "FAIL\n"; }

$u = odbc_exec($conn, "SELECT TOP 5 id, username FROM qr_users ORDER BY id");
echo "\nUsers:\n";
$uid = 0;
$uname = '';
if ($u) {
    while ($r = odbc_fetch_array($u)) {
        echo $r['id'] . " = " . $r['username'] . "\n";
        $uid = (int)$r['id'];
        $uname = $r['username'];
    }
    odbc_free_result($u);
} else {
    echo "ERR: " . odbc_error($conn) . "\n";
}

$q = odbc_exec($conn, "SELECT COUNT(*) AS c FROM qr_dynamic WHERE user_id=" . $uid);
echo "\nCodes for " . $uname . "(id=" . $uid . "):\n";
if ($q) {
    $r = odbc_fetch_array($q);
    echo "count = " . $r['c'] . "\n";
    odbc_free_result($q);
} else {
    echo "SQL ERR: " . odbc_error($conn) . "\n";
}

$d = odbc_exec($conn, "SELECT TOP 2 code, label, status FROM qr_dynamic WHERE user_id=" . $uid . " ORDER BY id DESC");
if ($d) {
    echo "\nSample:\n";
    while ($x = odbc_fetch_array($d)) {
        echo "code=" . $x['code'] . " label=" . $x['label'] . " status=" . $x['status'] . "\n";
    }
    odbc_free_result($d);
}
odbc_close($conn);
echo "\n=== DEBUG END ===\n";
?>
