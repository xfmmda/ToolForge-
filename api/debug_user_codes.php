<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== USER_CODES DEBUG ===\n\n";

require_once dirname(__FILE__) . '/config.php';

echo "1. DB Connection: ";
if ($conn !== null) {
    echo "OK\n";
} else {
    echo "NULL!\n";
}

// Check users
$users = odbc_exec($conn, "SELECT TOP 5 id, username FROM qr_users ORDER BY id ASC");
echo "\n2. Users:\n";
$test_uid = 0;
$test_name = '';
if ($users) {
    while ($row = odbc_fetch_array($users)) {
        echo "   id=" . $row['id'] . " username=" . $row['username'] . "\n";
        $test_uid = (int)$row['id'];
        $test_name = $row['username'];
    }
    odbc_free_result($users);
} else {
    $err = odbc_error($conn);
    echo "   ERROR: " . ($err ? $err : 'unknown') . "\n";
}

// Try query codes count
echo "\n3. Query codes for user_id=" . $test_uid . " (" . $test_name . "):\n";
$sql = "SELECT COUNT(*) AS cnt FROM qr_dynamic WHERE user_id = " . $test_uid;
$result = odbc_exec($conn, $sql);
if ($result) {
    $row = odbc_fetch_array($result);
    echo "   Count: " . $row['cnt'] . "\n";
    odbc_free_result($result);
} else {
    $err = odbc_error($conn);
    echo "   SQL ERROR: " . ($err ? $err : 'unknown') . "\n";
}

// Full data query with all columns that user_codes.php uses
echo "\n4. Sample data (columns used by user_codes.php):\n";
$sql2 = "SELECT TOP 3 id, code, target_url, label, status, scan_count, created_at, updated_at, expires_at, max_scans FROM qr_dynamic WHERE user_id = " . $test_uid . " ORDER BY id DESC";
$result2 = odbc_exec($conn, $sql2);
if ($result2) {
    while ($r = odbc_fetch_array($result2)) {
        $lbl = isset($r['label']) ? $r['label'] : '(no label col)';
        $exp = isset($r['expires_at']) ? $r['expires_at'] : '(no exp col)';
        $msc = isset($r['max_scans']) ? $r['max_scans'] : '(no max_scans col)';
        echo "   id=" . $r['id'] . " code=" . $r['code'] . " label=" . $lbl . " status=" . $r['status'] . " expires=" . $exp . " max_scans=" . $msc . "\n";
    }
    odbc_free_result($result2);
} else {
    $err = odbc_error($conn);
    echo "   ERROR: " . ($err ? $err : 'unknown') . "\n";
}

odbc_close($conn);
echo "\n=== END ===\n";
?>
