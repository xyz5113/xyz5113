# -*- coding: utf-8 -*-
p = r'C:\Users\Administrator\Doubao\chats\2026-09-26\new-chat\在线音乐播放器（宝塔部署）\index.html'
s = open(p, encoding='utf-8').read()

def rep(old, new, n=1):
    global s
    c = s.count(old)
    if c != n:
        raise SystemExit('MISMATCH count=%d for: %r' % (c, old[:70]))
    s = s.replace(old, new)

# 1. 新增 findTrack：按 url 从已加载专辑里找回完整歌曲对象（含 lrc/cover）
rep('function albumIdxOf(s){for(let i=0;i<albums.length;i++){if(albums[i].tracks.some(t=>t.url===s.url))return i;}return 0;}',
    'function findTrack(url){if(!url)return null;for(const a of albums){for(const t of a.tracks){if(t.url===url)return t;}}return null;}\n'
    'function albumIdxOf(s){for(let i=0;i<albums.length;i++){if(albums[i].tracks.some(t=>t.url===s.url))return i;}return 0;}')

# 2. updatePlayUI 里把歌曲补全为完整对象（播放列表来源的歌才有歌词/封面）
rep('  const s=curSong();\n  if(s){',
    '  const s=findTrack(curSong()?.url)||curSong();\n  if(s){')

# 3. updateLyrics 同样补全，滚动高亮时也能用上歌词
rep('  const s=curSong();if(!s)return;\n  const lines=parseLrc(s.lrc);',
    '  const s=findTrack(curSong()?.url)||curSong();if(!s)return;\n  const lines=parseLrc(s.lrc);')

open(p, 'w', encoding='utf-8').write(s)
print('OK index.html new len', len(s))
