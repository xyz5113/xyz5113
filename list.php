<?php
/**
 * 在线音乐播放器 - 播放列表生成器（按专辑分组）
 *
 * 目录结构（在网站根目录的 music 文件夹下）：
 *   music/
 *     专辑A/
 *       cover.jpg              （可选）专辑封面
 *       歌手 - 歌名1.mp3
 *       歌手 - 歌名1.jpg        （可选）单曲封面
 *       歌手 - 歌名1.lrc        （可选）歌词
 *     专辑B/
 *       ...
 *   （music 根目录下直接放的音频，会自动归到「未分专辑」）
 *
 * 文件名建议：歌手 - 歌名.mp3 （会按“-”自动拆成歌手和歌名）
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$root     = __DIR__ . '/music';
$lrcDir   = $root . '/lrc';            // 可选的集中歌词文件夹 music/lrc/<歌名>.lrc
$audioExt = ['mp3','m4a','aac','wav','flac','ogg','opus','wma'];
$imgExt   = ['jpg','jpeg','png','webp','gif'];

/** 把标签文本按编码转成 UTF-8（兼容中文 GBK） */
function decode_text($enc, $data) {
    if ($enc === 1) {
        if (substr($data,0,2)=="\xFF\xFE") return @mb_convert_encoding(substr($data,2),'UTF-8','UTF-16LE');
        if (substr($data,0,2)=="\xFE\xFF") return @mb_convert_encoding(substr($data,2),'UTF-8','UTF-16BE');
        return @mb_convert_encoding($data,'UTF-8','UTF-16');
    }
    if ($enc === 2) return @mb_convert_encoding($data,'UTF-8','UTF-16BE');
    // enc 0 / 3：先当 UTF-8，不行再按 GBK（中文 mp3 常见）
    if (function_exists('mb_check_encoding') && mb_check_encoding($data,'UTF-8')) return $data;
    $gb = @mb_convert_encoding($data,'UTF-8','GBK');
    return ($gb !== false && $gb !== $data) ? $gb : $data;
}

/** 读取音频文件的歌手(艺术家)/唱片集标签，支持 MP3(ID3v2) 与 FLAC(Vorbis comment) */
function read_audio_tags($file) {
    $tags = ['artist' => '', 'album' => ''];
    $data = @file_get_contents($file, false, null, 0, 1048576);
    if ($data === false) return $tags;

    if (substr($data,0,3) === 'ID3') {
        // ---- MP3 ID3v2 ----
        $size = 0;
        for ($i=6;$i<10;$i++) $size = ($size<<7) | (ord($data[$i]) & 0x7f);
        $end = min(strlen($data), 10 + $size);
        $off = 10;
        while ($off + 10 <= $end) {
            $fid = substr($data,$off,4);
            $fsize = 0;
            for ($i=$off+4;$i<$off+8;$i++) $fsize = ($fsize<<7) | (ord($data[$i]) & 0x7f);
            $body = substr($data,$off+10,$fsize);
            if (($fid==='TPE1' || $fid==='TALB') && $body !== '') {
                $enc = ord($body[0]);
                $txt = trim(decode_text($enc, substr($body,1)));
                if ($txt !== '') {
                    if ($fid==='TPE1') $tags['artist'] = $txt;
                    if ($fid==='TALB') $tags['album']  = $txt;
                }
            }
            $off += 10 + $fsize;
        }
    } elseif (substr($data,0,4) === 'fLaC') {
        // ---- FLAC Vorbis comment ----
        $off = 4; $last = false;
        while ($off + 4 <= strlen($data) && !$last) {
            $b = ord($data[$off]);
            $last = ($b & 0x80) ? true : false;
            $type = $b & 0x7f;
            $len = (ord($data[$off+1])<<16)|(ord($data[$off+2])<<8)|ord($data[$off+3]);
            $body = substr($data,$off+4,$len);
            if ($type === 4) {
                $pos = 0;
                if ($pos+4 > strlen($body)) break;
                $vlen = unpack('V', substr($body,$pos,4))[1]; $pos += 4 + $vlen;
                if ($pos+4 > strlen($body)) break;
                $cnt = unpack('V', substr($body,$pos,4))[1]; $pos += 4;
                for ($k=0;$k<$cnt && $pos+4 <= strlen($body);$k++){
                    $clen = unpack('V', substr($body,$pos,4))[1]; $pos += 4;
                    $kv = substr($body,$pos,$clen); $pos += $clen;
                    $eq = strpos($kv,'=');
                    if ($eq !== false) {
                        $key = strtoupper(trim(substr($kv,0,$eq)));
                        $val = trim(substr($kv,$eq+1));
                        if ($key === 'ARTIST' && $tags['artist'] === '') $tags['artist'] = $val;
                        if ($key === 'ALBUM'  && $tags['album']  === '') $tags['album']  = $val;
                    }
                }
                break;
            }
            $off += 4 + $len;
        }
    }
    return $tags;
}

/** 扫描一个文件夹内的所有音频，返回歌曲数组（file/cover 用文件名，未拼目录前缀） */
function scan_folder($dir) {
    global $audioExt, $imgExt, $lrcDir, $lrcMap;
    $out = [];
    if (!is_dir($dir)) return $out;
    $files = scandir($dir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $fp = $dir . '/' . $f;
        if (!is_file($fp)) continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, $audioExt)) continue;

        $base   = pathinfo($f, PATHINFO_FILENAME);
        $tags   = read_audio_tags($fp);
        $title  = $base;
        $artist = $tags['artist'];
        $album  = $tags['album'];
        if (!$artist) {
            if (preg_match('/^\s*(.+?)\s*[-–—]\s*(.+?)\s*$/u', $base, $m)) {
                $artist = trim($m[1]);
                $title  = trim($m[2]);
            } else {
                $artist = '未知歌手';
            }
        }
        $cover = null;
        foreach ($imgExt as $ie) {
            if (is_file($dir . '/' . $base . '.' . $ie)) { $cover = rawurlencode($base . '.' . $ie); break; }
        }
        $lrc = null;
        // 歌词：集中目录(可含子文件夹)按歌名匹配，忽略"-"后的歌手部分，再回退歌曲同目录
        $lk = preg_replace('/\s*[-–—].*$/u', '', trim($base));
        if (isset($lrcMap[$lk]) && is_file($lrcMap[$lk])) { $lrc = file_get_contents($lrcMap[$lk]); }
        elseif (isset($lrcMap[$base]) && is_file($lrcMap[$base])) { $lrc = file_get_contents($lrcMap[$base]); }
        elseif (is_file($dir . '/' . $base . '.lrc')) { $lrc = file_get_contents($dir . '/' . $base . '.lrc'); }

        $out[] = ['name' => $title, 'artist' => $artist, 'album' => $album, 'file' => rawurlencode($f), 'cover' => $cover, 'lrc' => $lrc];
    }
    usort($out, function ($a, $b) { return strnatcmp($a['file'], $b['file']); });
    return $out;
}

/** 递归收集歌词目录下所有 .lrc，返回 [歌名 => 文件路径]（含子文件夹） */
function collect_lrcs($dir) {
    $map = [];
    if (!is_dir($dir)) return $map;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'lrc') {
            $base = $file->getBasename('.lrc');
            // 键：去掉"-"及后面的歌手部分，例如"不多-李志" -> "不多"
            $key = preg_replace('/\s*[-–—].*$/u', '', trim($base));
            if (!isset($map[$key]))  $map[$key]  = $file->getPathname();
            if (!isset($map[$base])) $map[$base] = $file->getPathname();
        }
    }
    return $map;
}

/** 把歌曲列表加上目录前缀，拼出可访问 URL */
function with_prefix($tracks, $prefix) {
    $res = [];
    foreach ($tracks as $t) {
        $res[] = [
            'name'   => $t['name'],
            'artist' => $t['artist'],
            'url'    => $prefix . $t['file'],
            'cover'  => $t['cover'] ? $prefix . $t['cover'] : null,
            'lrc'    => $t['lrc'],
        ];
    }
    return $res;
}

/** 找专辑封面：优先 cover.jpg，否则用第一首有封面的歌 */
function album_cover($dir, $tracks, $prefix, $imgExt) {
    foreach ($imgExt as $ie) {
        if (is_file($dir . '/cover.' . $ie)) return $prefix . 'cover.' . $ie;
    }
    foreach ($tracks as $t) { if ($t['cover']) return $t['cover']; }
    return null;
}

$albums = [];
$lrcMap = collect_lrcs($lrcDir);   // 预先递归收集集中目录的所有歌词
if (is_dir($root)) {
    $items = scandir($root);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $p = $root . '/' . $item;
        if (is_dir($p)) {
            $tracks = scan_folder($p);
            if ($tracks) {
                $prefix  = 'music/' . rawurlencode($item) . '/';
                $albums[] = [
                    'name'   => $item,
                    'cover'  => album_cover($p, $tracks, $prefix, $imgExt),
                    'tracks' => with_prefix($tracks, $prefix),
                ];
            }
        }
    }
    // music 根目录直接放的歌 -> 未分专辑
    $loose = scan_folder($root);
    if ($loose) {
        $prefix = 'music/';
        array_unshift($albums, [
            'name'   => '未分专辑',
            'cover'  => album_cover($root, $loose, $prefix, $imgExt),
            'tracks' => with_prefix($loose, $prefix),
        ]);
    }
}

echo json_encode(['albums' => $albums], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
