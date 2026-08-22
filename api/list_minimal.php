<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Test 1: Basic output
echo '{"test":"1_basic_output"}'; exit;

// Should never reach here
echo '{"test":"2_unreachable"}';
