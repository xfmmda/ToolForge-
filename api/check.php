<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once dirname(__FILE__) . '/auth.php';

if (session_id() === '') {
    session_start();
}

$logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

$result = array(
    'success' => true,
    'logged_in' => $logged_in
);

if ($logged_in) {
    $result['user'] = array(
        'id' => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0,
        'username' => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '',
        'role' => isset($_SESSION['user_role']) ? (int)$_SESSION['user_role'] : 0
    );
}

echo json_encode($result);
?>
