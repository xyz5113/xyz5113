# -*- coding: utf-8 -*-
import io, os
base = r'C:\Users\Administrator\Doubao\chats\2026-09-26\new-chat\在线音乐播放器（宝塔部署）'
for name in ['list.php', 'record.php', 'admin.php']:
    p = os.path.join(base, name)
    raw = open(p, 'rb').read()
    bom = raw.startswith(b'\xef\xbb\xbf')
    s = raw.decode('utf-8')
    # strip comments/strings roughly for brace balance
    import re
    body = re.sub(r"//[^\n]*", "", s)
    body = re.sub(r"/\*.*?\*/", "", body, flags=re.S)
    # remove string literals
    body = re.sub(r"'(\\.|[^'\\])*'", "''", body)
    body = re.sub(r'"(\\.|[^"\\])*"', '""', body)
    opens = body.count('{') + body.count('(') + body.count('[')
    closes = body.count('}') + body.count(')') + body.count(']')
    tag = s.lstrip().startswith('<?php')
    print(name, '| BOM:', bom, '| starts_php:', tag, '| braces open/close:', opens, closes, '| balanced:', opens == closes, '| len:', len(s))
