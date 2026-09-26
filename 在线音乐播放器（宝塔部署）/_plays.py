# -*- coding: utf-8 -*-
p = r'C:\Users\Administrator\Doubao\chats\2026-09-26\new-chat\在线音乐播放器（宝塔部署）\index.html'
s = open(p, encoding='utf-8').read()

def rep(old, new, n=1):
    global s
    c = s.count(old)
    if c != n:
        raise SystemExit('MISMATCH count=%d for: %r' % (c, old[:60]))
    s = s.replace(old, new)

# CSS
rep('.trow .info .a{font-size:12px;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.trow .info .a{font-size:12px;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}\n'
    '.trow .info .a .plc{color:var(--accent);margin-left:7px;font-variant-numeric:tabular-nums;font-weight:500}')

# 三处歌曲行（专辑弹窗/搜索结果/播放列表）在歌手后显示播放量
rep("'<div class=\"info\"><div class=\"n\">'+esc(t.name)+'</div><div class=\"a\">'+esc(t.artist)+'</div></div>'",
    "'<div class=\"info\"><div class=\"n\">'+esc(t.name)+'</div><div class=\"a\">'+esc(t.artist)+(t.playCount?'<span class=\"plc\">▷'+t.playCount+'</span>':'')+'</div></div>'", n=3)

open(p, 'w', encoding='utf-8').write(s)
print('OK index.html new len', len(s))
