<?php
/**
 * B&B 播放器 - 在线人数 / 在线 IP 心跳接口
 * 前端每 15 秒 POST {id,page} 一次；超过 45 秒没心跳视为离线。
 * 后台管理页用 GET 读取当前在线 IP 列表。
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store');
$life = 45;
$f   = __DIR__ . '/stats/online.json';
$dir = __DIR__ . '/stats';
if (!is_dir($dir)) @mkdir($dir, 0777, true);

$now  = time();
$data = (is_file($f)) ? json_decode(file_get_contents($f), true) : [];
if (!is_array($data)) $data = [];

foreach ($data as $k => $u) {
    if (!is_array($u) || ($now - (isset($u['t']) ? (int)$u['t'] : 0)) > $life) unset($data[$k]);
}

$id = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
if ($id !== '' && strlen($id) <= 64 && strpos($id, '..') === false) {
    $data[$id] = [
        't'  => $now,
        'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
        'pg' => isset($_POST['page']) ? substr((string)$_POST['page'], 0, 120) : '',
    ];
}

$fp = fopen($f, 'c+');
if ($fp) {
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0); rewind($fp);
        fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

$list = [];
foreach ($data as $k => $u) {
    $list[] = ['id' => $k, 'ip' => isset($u['ip']) ? $u['ip'] : '', 't' => isset($u['t']) ? (int)$u['t'] : 0, 'pg' => isset($u['pg']) ? $u['pg'] : ''];
}
usort($list, function ($a, $b) { return $b['t'] - $a['t']; });
echo json_encode(['count' => count($list), 'online' => $list], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
