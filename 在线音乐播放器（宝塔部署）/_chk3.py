# -*- coding: utf-8 -*-
import re
p = r'C:\Users\Administrator\Doubao\chats\2026-09-26\new-chat\在线音乐播放器（宝塔部署）\admin.php'
raw = open(p, 'rb').read()
s = raw.decode('utf-8')
b = re.sub(r"//[^\n]*", "", s)
b = re.sub(r"/\*.*?\*/", "", b, flags=re.S)
b = re.sub(r"'(\\.|[^'\\])*'", "''", b)
b = re.sub(r'"(\\.|[^"\\])*"', '""', b)
print('BOM:', raw.startswith(b'\xef\xbb\xbf'), '| starts_php:', s.lstrip().startswith('<?php'))
print('balanced:', b.count('{')+b.count('(')+b.count('[') == b.count('}')+b.count(')')+b.count(']'))
print('chgcover present:', 'chgcover' in s, '| delete old cover:', 'foreach($imgExt as $ee){ $oc=$d' in s)
