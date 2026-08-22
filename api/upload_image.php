<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once dirname(__FILE__) . '/auth.php';
checkUserAuth();

require_once dirname(__FILE__) . '/config.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['data'])) {
    echo json_encode(array('success' => false, 'error' => '未收到图片数据'));
    odbc_close($conn);
    exit;
}

$data = $input['data'];
// Check base64 data size (roughly 33% larger than binary)
// 10MB binary ~ 13.3MB base64
if (strlen($data) > 15 * 1024 * 1024) {
    echo json_encode(array('success' => false, 'error' => '图片数据过大，请压缩后重试（最大10MB）'));
    odbc_close($conn);
    exit;
}

// Extract base64 data
if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $data, $m)) {
    $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
    $b64 = $m[2];
} else {
    // Assume raw base64
    $ext = 'jpg';
    $b64 = $data;
}

$binary = base64_decode($b64, true);
if ($binary === false) {
    echo json_encode(array('success' => false, 'error' => '图片数据解码失败'));
    odbc_close($conn);
    exit;
}

// Save to uploads directory
$upload_dir = dirname(__FILE__) . '/../uploads/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}
if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
    echo json_encode(array('success' => false, 'error' => '服务器存储目录不可写'));
    odbc_close($conn);
    exit;
}

$filename = 'img_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
$filepath = $upload_dir . $filename;

if (file_put_contents($filepath, $binary) === false) {
    echo json_encode(array('success' => false, 'error' => '图片保存失败'));
    odbc_close($conn);
    exit;
}

$base_url = getBaseUrl();
$url = $base_url . '/uploads/' . $filename;

odbc_close($conn);

echo json_encode(array('success' => true, 'url' => $url));
?>
