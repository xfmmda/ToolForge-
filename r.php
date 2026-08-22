<?php
define('QR_REDIRECT_MODE', true);
require_once dirname(__FILE__) . '/api/config.php';

$code = isset($_GET['id']) ? trim($_GET['id']) : '';

if (!$code || !preg_match('/^[a-fA-F0-9]{12}$/', $code)) {
    header('Location: /');
    exit;
}

$escaped = str_replace("'", "''", $code);
$result = odbc_exec($conn, "SELECT * FROM qr_dynamic WHERE code='{$escaped}' AND status=1");
$row = $result ? odbc_fetch_array($result) : null;

if ($row && !empty($row['target_url'])) {
    // ── 检查有效期 ──
    $now = time();
    $exp = null;
    if (!empty($row['expires_at'])) {
        // datetime-local format: 2026-06-26T13:00
        $exp = strtotime($row['expires_at']);
        if ($exp !== false && $exp < $now) {
            if ($result) odbc_free_result($result);
            odbc_close($conn);
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>空码已过期</title>
<style>body{display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f0f2f5;font-family:-apple-system,sans-serif;color:#333}
.box{text-align:center;padding:48px;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.08);margin:16px}
h1{color:#ff4747;font-size:20px;margin:0 0 12px}p{margin:0 0 8px;color:#888;font-size:14px}
.code{font-family:monospace;background:#f5f5f5;padding:4px 12px;border-radius:6px;display:inline-block;margin-top:16px}</style></head><body><div class="box">
<h1>⏰ 空码已过期</h1>
<p>此动态二维码已超过有效期限</p><p>有效期至：' . htmlspecialchars($row['expires_at']) . '</p>
<div class="code">' . htmlspecialchars($code) . '</div></body></html>';
            exit;
        }
    }
    // ── 检查扫码次数限制 ──
    if ($row['max_scans'] !== null && $row['max_scans'] !== '' && (int)$row['max_scans'] > 0) {
        if ((int)$row['scan_count'] >= (int)$row['max_scans']) {
            if ($result) odbc_free_result($result);
            odbc_close($conn);
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>扫码次数已达上限</title>
<style>body{display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f0f2f5;font-family:-apple-system,sans-serif;color:#333}
.box{text-align:center;padding:48px;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.08);margin:16px}
h1{color:#ff4747;font-size:20px;margin:0 0 12px}p{margin:0 0 8px;color:#888;font-size:14px}
.code{font-family:monospace;background:#f5f5f5;padding:4px 12px;border-radius:6px;display:inline-block;margin-top:16px}</style></head><body><div class="box">
<h1>🚫 扫码次数已达上限</h1>
<p>此二维码已达到最大扫描次数</p><p>已扫 ' . (int)$row['scan_count'] . ' 次 / 限 ' . (int)$row['max_scans'] . ' 次</p>
<div class="code">' . htmlspecialchars($code) . '</div></body></html>';
            exit;
        }
    }

    // 扫描计数 +1
    odbc_exec($conn, "UPDATE qr_dynamic SET scan_count=scan_count+1 WHERE id=" . (int)$row['id']);
    if ($result) odbc_free_result($result);
    odbc_close($conn);

    $target_url = $row['target_url'];
    $label = isset($row['label']) ? htmlspecialchars($row['label']) : '';

    // ── 文本模式 ──
    if (strpos($target_url, 'text:') === 0) {
        $html = urldecode(substr($target_url, 5));
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        if ($label) echo '<title>' . $label . '</title>';
        echo '<style>body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;line-height:1.7;color:#222;background:#fff;padding:20px 16px;max-width:800px;margin:0 auto;word-break:break-word}body *{max-width:100%}img{max-width:100%;height:auto}pre{background:#f5f5f5;padding:12px;border-radius:8px;overflow:auto;font-size:13px}code{background:#f0f0f0;padding:2px 6px;border-radius:4px;font-size:13px}blockquote{border-left:3px solid #00d4ff;margin-left:0;padding-left:16px;color:#666}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f5f5f5}</style></head><body>' . $html . '</body></html>';
    }
    // ── Markdown 模式 ──
    elseif (strpos($target_url, 'markdown:') === 0) {
        $md = urldecode(substr($target_url, 9));
        $escaped_md = htmlspecialchars($md, ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        if ($label) echo '<title>' . $label . '</title>';
        echo '<style>body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;line-height:1.7;color:#222;background:#fff;padding:20px 16px;max-width:800px;margin:0 auto;word-break:break-word}#md-out{max-width:100%}#md-out img{max-width:100%;height:auto}#md-out pre{background:#f5f5f5;padding:12px;border-radius:8px;overflow:auto;font-size:13px}#md-out code{background:#f0f0f0;padding:2px 6px;border-radius:4px;font-size:13px}#md-out blockquote{border-left:3px solid #00d4ff;margin-left:0;padding-left:16px;color:#666}#md-out table{border-collapse:collapse;width:100%}#md-out td,#md-out th{border:1px solid #ddd;padding:8px;text-align:left}#md-out th{background:#f5f5f5}#md-out h1{font-size:28px}#md-out h2{font-size:22px}#md-out h3{font-size:18px}</style></head><body><div id="md-out"></div>
<script>
(function(){
  var md=' . json_encode($md) . ';
  if(typeof marked!=="undefined"){
    document.getElementById("md-out").innerHTML=marked.parse(md);
  }else{
    var s=document.createElement("script");
    s.src="https://cdn.jsdelivr.net/npm/marked@4/marked.min.js";
    s.onload=function(){document.getElementById("md-out").innerHTML=marked.parse(md)};
    s.onerror=function(){document.getElementById("md-out").innerHTML="<pre>"+document.createTextNode(md).textContent+"</pre>"};
    document.head.appendChild(s);
  }
})();
</script></body></html>';
    }
    // ── 图片模式 ──
    elseif (strpos($target_url, 'data:image') === 0 || strpos($target_url, 'image:') === 0) {
        $img = (strpos($target_url, 'image:') === 0) ? urldecode(substr($target_url, 6)) : $target_url;
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        if ($label) echo '<title>' . $label . '</title>';
        echo '<style>body{margin:0;background:#f0f2f5;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}img{max-width:100%;max-height:90vh;box-shadow:0 4px 24px rgba(0,0,0,0.12);border-radius:8px;background:#fff}</style></head><body><img src="' . htmlspecialchars($img, ENT_QUOTES) . '" alt=""></body></html>';
    }
    // ── 普通URL跳转 ──
    else {
        header('Location: ' . $target_url);
    }
} else if ($row) {
    // 空码未配置目标
    if ($result) odbc_free_result($result);
    odbc_close($conn);

    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>空码未配置</title>
<style>body{display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f0f2f5;font-family:-apple-system,sans-serif;color:#333}
.box{text-align:center;padding:48px;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.08);margin:16px}
h1{color:#00d4ff;font-size:20px;margin:0 0 12px}
p{margin:0 0 8px;color:#888;font-size:14px}
.code{font-family:monospace;background:#f5f5f5;padding:4px 12px;border-radius:6px;display:inline-block;margin-top:16px}</style></head>
<body><div class="box">
<h1>空码暂未配置目标</h1>
<p>此动态二维码尚未设置跳转地址</p><p>请到 ToolForge 管理后台配置</p>
<div class="code">' . htmlspecialchars($code) . '</div>
</body></html>';
} else {
    // 空码不存在
    if ($result) odbc_free_result($result);
    odbc_close($conn);

    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>空码不存在</title>
<style>body{display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f0f2f5;font-family:-apple-system,sans-serif;color:#333}
.box{text-align:center;padding:48px;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.08)}p{margin:0;color:#888}</style></head>
<body><div class="box"><p>该空码不存在或已被删除</p></div></html>';
}
?>
