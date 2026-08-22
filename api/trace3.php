<?php
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once dirname(__FILE__) . '/config.php';

$columns_to_test = array(
    'status' => 'int_col',
    'scan_count' => 'int_col',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'user_id' => 'int_col',
    'expires_at' => 'datetime_null',
    'max_scans' => 'int_null',
    'group_name' => 'string_null',
    'qr_tag' => 'string_null',
    'qr_tag_font_size' => 'int_null',
    'qr_logo' => 'text_null',  // suspect!
);

foreach ($columns_to_test as $col => $type) {
    $sql = "SELECT id, code, $col FROM qr_dynamic WHERE user_id = 1 ORDER BY id DESC";
    $result = @odbc_exec($conn, $sql);
    if (!$result) {
        echo "$col: SQL_ERROR\n";
        continue;
    }
    $count = 0;
    $last_row_data = '';
    while ($row = odbc_fetch_array($result)) {
        $count++;
        if ($col == 'qr_logo') {
            $val = isset($row[$col]) ? $row[$col] : null;
            $last_row_data = 'len=' . ($val ? strlen($val) : 0);
        }
    }
    odbc_free_result($result);
    $extra = $last_row_data ? " ($last_row_data)" : '';
    echo "$col: ok ({$count} rows)$extra\n";
}

echo "all_done";
odbc_close($conn);
