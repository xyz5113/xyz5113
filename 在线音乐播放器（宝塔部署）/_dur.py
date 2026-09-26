# -*- coding: utf-8 -*-
p = r'C:\Users\Administrator\Doubao\chats\2026-09-26\new-chat\在线音乐播放器（宝塔部署）\index.html'
s = open(p, encoding='utf-8').read()

def rep(old, new, n=1):
    global s
    c = s.count(old)
    if c != n:
        raise SystemExit('MISMATCH count=%d for: %r' % (c, old[:70]))
    s = s.replace(old, new)

# 音频元数据加载完成后，同时刷新播放页时长（避免卡在 0:00）
rep('audio.onloadedmetadata=()=>{updateProgress();};',
    'audio.onloadedmetadata=()=>{updateProgress();if(curSong())updatePlayUI();};')

open(p, 'w', encoding='utf-8').write(s)
print('OK index.html new len', len(s))
