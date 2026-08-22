<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once dirname(__FILE__) . '/auth.php';
checkUserAuth();
require_once dirname(__FILE__) . '/config.php';

if (session_id() === '') { session_start(); }
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get profile info
    $sql = "SELECT id, username, role, email, created_at FROM qr_users WHERE id = " . $user_id;
    $result = odbc_exec($conn, $sql);
    if ($result) {
        $row = odbc_fetch_array($result);
        odbc_free_result($result);
        if ($row) {
            echo json_encode(array(
                'success' => true,
                'data' => array(
                    'id' => (int)$row['id'],
                    'username' => $row['username'],
                    'role' => (int)$row['role'],
                    'email' => $row['email'],
                    'created_at' => (string)$row['created_at']
                )
            ));
        } else {
            echo json_encode(array('success' => false, 'error' => 'User not found'));
        }
    } else {
        echo json_encode(array('success' => false, 'error' => 'Query failed'));
    }
} elseif ($method === 'POST') {
    // Update profile email
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) { $input = $_POST; }

    if (isset($input['email'])) {
        $sql = "UPDATE qr_users SET email='" . sql_escape(trim($input['email'])) . "', updated_at=GETDATE() WHERE id=" . $user_id;
        odbc_exec($conn, $sql);
        echo json_encode(array('success' => true, 'message' => 'Email updated'));
    } else {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(array('error' => 'No fields to update'));
    }
}
odbc_close($conn);
?>
