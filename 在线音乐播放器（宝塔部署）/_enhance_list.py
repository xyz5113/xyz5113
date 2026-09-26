# -*- coding: utf-8 -*-
p = r'C:\Users\Administrator\Doubao\chats\2026-09-26\new-chat\在线音乐播放器（宝塔部署）\list.php'
s = open(p, encoding='utf-8').read()

def rep(old, new, n=1):
    global s
    c = s.count(old)
    if c != n:
        raise SystemExit('MISMATCH count=%d for: %r' % (c, old[:70]))
    s = s.replace(old, new)

# A. stats 读取
rep("""$audioExt = ['mp3','m4a','aac','wav','flac','ogg','opus','wma'];
$imgExt   = ['jpg','jpeg','png','webp','gif'];""",
    """$audioExt = ['mp3','m4a','aac','wav','flac','ogg','opus','wma'];
$imgExt   = ['jpg','jpeg','png','webp','gif'];

$statsFile = __DIR__ . '/stats/playcount.json';
$playCount = (is_file($statsFile)) ? json_decode(file_get_contents($statsFile), true) : [];
if (!is_array($playCount)) $playCount = [];""")

# B. read_audio_tags 加 year
rep("function read_audio_tags($file) {\n    $tags = ['artist' => '', 'album' => ''];",
    "function read_audio_tags($file) {\n    $tags = ['artist' => '', 'album' => '', 'year' => ''];")
rep("                if ($txt !== '') {\n                    if ($fid==='TPE1') $tags['artist'] = $txt;\n                    if ($fid==='TALB') $tags['album']  = $txt;\n                }",
    "                if ($txt !== '') {\n                    if ($fid==='TPE1') $tags['artist'] = $txt;\n                    if ($fid==='TALB') $tags['album']  = $txt;\n                    if ($fid==='TYER' || $fid==='TDRC') { $tags['year'] = preg_replace('/[^0-9]/','',substr($txt,0,4)); }\n                }")
rep("                        if ($key === 'ARTIST' && $tags['artist'] === '') $tags['artist'] = $val;\n                        if ($key === 'ALBUM'  && $tags['album']  === '') $tags['album']  = $val;",
    "                        if ($key === 'ARTIST' && $tags['artist'] === '') $tags['artist'] = $val;\n                        if ($key === 'ALBUM'  && $tags['album']  === '') $tags['album']  = $val;\n                        if ($key === 'DATE' || $key === 'YEAR') { if ($tags['year']==='') $tags['year'] = preg_replace('/[^0-9]/','',substr($val,0,4)); }")

# C. scan_folder 输出 year
rep("$out[] = ['name' => $title, 'artist' => $artist, 'album' => $album, 'file' => rawurlencode($f), 'cover' => $cover, 'lrc' => $lrc];",
    "$out[] = ['name' => $title, 'artist' => $artist, 'album' => $album, 'year' => $tags['year'], 'file' => rawurlencode($f), 'cover' => $cover, 'lrc' => $lrc];")

# D. with_prefix 加 playCount + year
rep("""function with_prefix($tracks, $prefix) {
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
}""",
    """function with_prefix($tracks, $prefix) {
    global $playCount;
    $res = [];
    foreach ($tracks as $t) {
        $u = $prefix . $t['file'];
        $res[] = [
            'name'   => $t['name'],
            'artist' => $t['artist'],
            'year'   => isset($t['year']) ? $t['year'] : '',
            'url'    => $u,
            'cover'  => $t['cover'] ? $prefix . $t['cover'] : null,
            'lrc'    => $t['lrc'],
            'playCount' => isset($playCount[$u]) ? (int)$playCount[$u] : 0,
        ];
    }
    return $res;
}""")

open(p, 'w', encoding='utf-8').write(s)
print('OK list.php new len', len(s))
