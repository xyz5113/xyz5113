# -*- coding: utf-8 -*-
import re, os
base = r'C:\Users\Administrator\Doubao\chats\2026-09-26\new-chat\在线音乐播放器（宝塔部署）'
for name in ['online.php', 'admin.php']:
    raw = open(os.path.join(base, name), 'rb').read()
    s = raw.decode('utf-8')
    b = re.sub(r"//[^\n]*", "", s)
    b = re.sub(r"/\*.*?\*/", "", b, flags=re.S)
    b = re.sub(r"'(\\.|[^'\\])*'", "''", b)
    b = re.sub(r'"(\\.|[^"\\])*"', '""', b)
    print(name, '| BOM:', raw.startswith(b'\xef\xbb\xbf'),
          '| starts_php:', s.lstrip().startswith('<?php'),
          '| balanced:', b.count('{')+b.count('(')+b.count('[') == b.count('}')+b.count(')')+b.count(']'))
