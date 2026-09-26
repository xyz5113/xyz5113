<?php
/**
 * B&B 播放器 - 播放量累加接口
 * 前端播放歌曲时 POST url 到这里，把播放次数 +1 存进 stats/playcount.json
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store');
$file = __DIR__ . '/stats/playcount.json';
$dir  = __DIR__ . '/stats';
if (!is_dir($dir)) @mkdir($dir, 0777, true);

$data = (is_file($file)) ? json_decode(file_get_contents($file), true) : [];
if (!is_array($data)) $data = [];

$url = isset($_POST['url']) ? (string)$_POST['url'] : '';
$safe = ($url !== '' && strpos($url, '..') === false && strpos($url, '\\') === false);
if ($safe) {
    $data[$url] = (isset($data[$url]) ? (int)$data[$url] : 0) + 1;
    $fp = fopen($file, 'c+');
    if ($fp) {
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0); rewind($fp);
            fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
    echo json_encode(['ok' => true, 'count' => $data[$url]]);
} else {
    echo json_encode(['ok' => false]);
}
