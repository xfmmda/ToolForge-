<?php
header('Content-Type: text/plain; charset=utf-8');
session_start();
echo "session_id: " . session_id() . "\n";
echo "user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
echo "username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'NOT SET') . "\n";
echo "role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NOT SET') . "\n";
?>
