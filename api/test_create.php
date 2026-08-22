<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__) . '/config.php';

// Test ODBC connection
$conn = odbc_connect('DRIVER={SQL Server};SERVER=127.0.0.1,1433;DATABASE=msfreepZXySzbis', 'msfreepZXySzbis', 'Yg8aa9lr!');
if (!$conn) {
    echo json_encode(array('status' => 'error', 'msg' => 'ODBC connect failed: ' . odbc_errormsg()));
    exit;
}

// Check columns exist
$result = odbc_columns($conn, 'msfreepZXySzbis', 'dbo', 'qr_dynamic', null);
$columns = array();
while ($row = odbc_fetch_array($result)) {
    $columns[] = $row['COLUMN_NAME'];
}
odbc_free_result($result);

echo json_encode(array(
    'status' => 'ok',
    'columns' => $columns,
    'has_qr_tag' => in_array('qr_tag', $columns),
    'has_qr_tag_font_size' => in_array('qr_tag_font_size', $columns),
    'has_qr_logo' => in_array('qr_logo', $columns)
));
odbc_close($conn);
?>
