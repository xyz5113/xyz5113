# -*- coding: utf-8 -*-
p = r'C:\Users\Administrator\Doubao\chats\2026-09-26\new-chat\在线音乐播放器（宝塔部署）\index.html'
s = open(p, encoding='utf-8').read()

def rep(old, new, n=1):
    global s
    c = s.count(old)
    if c != n:
        raise SystemExit('MISMATCH count=%d for: %r' % (c, old[:70]))
    s = s.replace(old, new)

# 1. 去播放页 playsline
rep('<div class="durline"><span data-i18n="duration"></span> <span id="pDur">0:00</span></div>\n          <div class="playsline"><span data-i18n="playsPre"></span> <span id="pPlays">0</span> <span data-i18n="playsPost"></span></div>',
    '<div class="durline"><span data-i18n="duration"></span> <span id="pDur">0:00</span></div>')

# 2. 去 CSS .playsline
rep('.pinfo .playsline{color:var(--faint);font-size:12px;margin-top:6px;font-variant-numeric:tabular-nums}\n.filters{',
    '.filters{')

# 3. 去 CSS .plc
rep('.trow .info .a{font-size:12px;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}\n.trow .info .a .plc{color:var(--accent);margin-left:7px;font-variant-numeric:tabular-nums;font-weight:500}',
    '.trow .info .a{font-size:12px;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}')

# 4. 去三处 plc span
rep("'<div class=\"info\"><div class=\"n\">'+esc(t.name)+'</div><div class=\"a\">'+esc(t.artist)+(t.playCount?'<span class=\"plc\">▷'+t.playCount+'</span>':'')+'</div></div>'",
    "'<div class=\"info\"><div class=\"n\">'+esc(t.name)+'</div><div class=\"a\">'+esc(t.artist)+'</div></div>'", n=3)

# 5. 去 recordPlay 定义
rep("function curSong(){return queue[curTrack]||null;}\nfunction recordPlay(url){try{fetch('record.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'url='+encodeURIComponent(url)}).catch(()=>{});}catch(e){}}",
    "function curSong(){return queue[curTrack]||null;}")

# 6. 去 recordPlay 调用
rep("  pushRecent(s);\n  recordPlay(s.url);\n  audio.src=s.url;", "  pushRecent(s);\n  audio.src=s.url;")

# 7. 去 updatePlayUI pPlays
rep("    $('pTitle').textContent=s.name;$('pArtist').textContent=s.artist;\n    $('pPlays').textContent=(s.playCount||0);",
    "    $('pTitle').textContent=s.name;$('pArtist').textContent=s.artist;")

# 8. I18N 键 online
rep("noMatch:'没有符合条件的专辑'},",
    "noMatch:'没有符合条件的专辑',onlinePre:'在线观看',onlinePost:'人'},")
rep("noMatch:'No matching albums'}",
    "noMatch:'No matching albums',onlinePre:'Online',onlinePost:''}")

# 9. site-footer 加在线人数
rep('<div class="site-footer"><span data-i18n="runSince"></span> <b id="uptime">…</b></div>',
    '<div class="site-footer"><span data-i18n="runSince"></span> <b id="uptime">…</b><span class="sep">·</span><span data-i18n="onlinePre"></span> <b id="onlineCount">0</b><span data-i18n="onlinePost"></span></div>')

# 10. CSS .sep
rep('.site-footer b{color:var(--muted);font-weight:500}',
    '.site-footer b{color:var(--muted);font-weight:500}\n.site-footer .sep{margin:0 4px;opacity:.5}')

# 11. 在线心跳 JS
rep("uptimeTick();setInterval(uptimeTick,60000);",
    "uptimeTick();setInterval(uptimeTick,60000);\n"
    "/* 在线人数心跳（每15秒，45秒无心跳算离线） */\n"
    "let onid=localStorage.getItem('onid')||('u'+Math.random().toString(36).slice(2)+Date.now().toString(36));\n"
    "localStorage.setItem('onid',onid);\n"
    "function beat(){\n"
    "  fetch('online.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+encodeURIComponent(onid)+'&page='+encodeURIComponent(location.pathname)})\n"
    "    .then(r=>r.json()).then(d=>{if(d&&d.count!=null)$('onlineCount').textContent=d.count;}).catch(()=>{});\n"
    "}\n"
    "beat();setInterval(beat,15000);")

open(p, 'w', encoding='utf-8').write(s)
print('OK index.html new len', len(s))
