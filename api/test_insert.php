<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__) . '/config.php';

$conn = odbc_connect('DRIVER={SQL Server};SERVER=127.0.0.1,1433;DATABASE=msfreepZXySzbis', 'msfreepZXySzbis', 'Yg8aa9lr!');
if (!$conn) {
    echo json_encode(array('status' => 'error', 'msg' => 'ODBC connect failed'));
    exit;
}

// Try a test INSERT with the new columns
$code = 'test_' . time();
$sql = "INSERT INTO qr_dynamic (code, target_url, label, status, user_id, qr_tag, qr_tag_font_size, qr_logo) VALUES ('" . $code . "', 'https://example.com', 'Test Label', 1, 2, 'Test Tag', 14, '')";

$result = @odbc_exec($conn, $sql);
if ($result) {
    odbc_free_result($result);
    echo json_encode(array('status' => 'ok', 'code' => $code, 'msg' => 'Insert succeeded'));
    
    // Delete the test row
    odbc_exec($conn, "DELETE FROM qr_dynamic WHERE code='" . $code . "'");
} else {
    $err = odbc_errormsg($conn);
    echo json_encode(array('status' => 'error', 'msg' => $err, 'sql' => $sql));
}

odbc_close($conn);
?>
