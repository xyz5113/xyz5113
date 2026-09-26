# -*- coding: utf-8 -*-
p = r'C:\Users\Administrator\Doubao\chats\2026-09-26\new-chat\在线音乐播放器（宝塔部署）\index.html'
s = open(p, encoding='utf-8').read()

def rep(old, new, n=1):
    global s
    c = s.count(old)
    if c != n:
        raise SystemExit('MISMATCH count=%d for: %r' % (c, old[:70]))
    s = s.replace(old, new)

# A. HTML: plays line
rep('<div class="durline"><span data-i18n="duration"></span> <span id="pDur">0:00</span></div>',
    '<div class="durline"><span data-i18n="duration"></span> <span id="pDur">0:00</span></div>\n'
    '          <div class="playsline"><span data-i18n="playsPre"></span> <span id="pPlays">0</span> <span data-i18n="playsPost"></span></div>')

# B. CSS: playsline
rep('.pinfo .durline{color:var(--faint);font-size:12px;margin-top:8px;font-variant-numeric:tabular-nums}',
    '.pinfo .durline{color:var(--faint);font-size:12px;margin-top:8px;font-variant-numeric:tabular-nums}\n'
    '.pinfo .playsline{color:var(--faint);font-size:12px;margin-top:6px;font-variant-numeric:tabular-nums}\n'
    '.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}\n'
    '.filters select{background:var(--surface2);border:1px solid var(--border);border-radius:9px;padding:8px 12px;color:var(--text);font-size:13px;outline:none;cursor:pointer;color-scheme:dark;font-family:inherit}\n'
    '.filters select:focus{border-color:rgba(224,164,88,.5)}')

# C. HTML: filters bar after sec-head in library
rep('''<div class="sec-head">
      <h1 data-i18n="albums">全部专辑</h1>
      <span class="count" id="countText">0 张</span>
    </div>
    <div class="grid" id="albumGrid"></div>''',
    '''<div class="sec-head">
      <h1 data-i18n="albums">全部专辑</h1>
      <span class="count" id="countText">0 张</span>
    </div>
    <div class="filters">
      <select id="fArtist"><option value="" data-i18n="allArtist">全部歌手</option></select>
      <select id="fAlbum"><option value="" data-i18n="allAlbum">全部专辑</option></select>
      <select id="fYear"><option value="" data-i18n="allYear">全部年份</option></select>
    </div>
    <div class="grid" id="albumGrid"></div>''')

# D. I18N keys
rep("albumPrefix:'专辑 · ',uDay:'天',uHour:'小时',uMin:'分',uSec:'秒',runSince:'自 2026-09-26 建站以来，已运行'},",
    "albumPrefix:'专辑 · ',uDay:'天',uHour:'小时',uMin:'分',uSec:'秒',runSince:'自 2026-09-26 建站以来，已运行',playsPre:'已播放',playsPost:'次',allArtist:'全部歌手',allAlbum:'全部专辑',allYear:'全部年份',noMatch:'没有符合条件的专辑'},")
rep("albumPrefix:'Album · ',uDay:'d',uHour:'h',uMin:'m',uSec:'s',runSince:'Running since 2026-09-26,'}",
    "albumPrefix:'Album · ',uDay:'d',uHour:'h',uMin:'m',uSec:'s',runSince:'Running since 2026-09-26,',playsPre:'Played',playsPost:'times',allArtist:'All Artists',allAlbum:'All Albums',allYear:'All Years',noMatch:'No matching albums'}")

# E. recordPlay function after curSong
rep("function curSong(){return queue[curTrack]||null;}",
    "function curSong(){return queue[curTrack]||null;}\n"
    "function recordPlay(url){try{fetch('record.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'url='+encodeURIComponent(url)}).catch(()=>{});}catch(e){}}")

# F. playQueueAt report
rep("  pushRecent(s);\n  audio.src=s.url;audio.play().catch(()=>toast(t('playFail')));updatePlayUI();}",
    "  pushRecent(s);\n  recordPlay(s.url);\n  audio.src=s.url;audio.play().catch(()=>toast(t('playFail')));updatePlayUI();}")

# G. updatePlayUI plays
rep("    $('pTitle').textContent=s.name;$('pArtist').textContent=s.artist;",
    "    $('pTitle').textContent=s.name;$('pArtist').textContent=s.artist;\n    $('pPlays').textContent=(s.playCount||0);")

# H. filters logic + filtered renderAlbums
rep("/* ===== 专辑网格 ===== */\nfunction renderAlbums(){\n  const g=$('albumGrid');\n  g.innerHTML='';\n  if(!albums.length){",
    "/* ===== 专辑网格 ===== */\n"
    "let fArtist='',fAlbum='',fYear='';\n"
    "function filteredAlbums(){\n"
    "  return albums.filter(a=>{\n"
    "    if(fAlbum && a.name!==fAlbum)return false;\n"
    "    if(!fArtist && !fYear)return true;\n"
    "    return a.tracks.some(t=>(!fArtist||t.artist===fArtist)&&(!fYear||t.year===fYear));\n"
    "  });\n"
    "}\n"
    "function buildFilters(){\n"
    "  const artists=new Set(),years=new Set();\n"
    "  albums.forEach(a=>a.tracks.forEach(t=>{if(t.artist&&t.artist!=='未知歌手')artists.add(t.artist);if(t.year)years.add(t.year);}));\n"
    "  const fa=$('fArtist'),falb=$('fAlbum'),fy=$('fYear');\n"
    "  fa.innerHTML='<option value=\"\">'+t('allArtist')+'</option>';\n"
    "  [...artists].sort().forEach(x=>fa.innerHTML+='<option>'+esc(x)+'</option>');\n"
    "  falb.innerHTML='<option value=\"\">'+t('allAlbum')+'</option>';\n"
    "  albums.forEach(a=>falb.innerHTML+='<option value=\"'+esc(a.name)+'\">'+esc(a.name)+'</option>');\n"
    "  fy.innerHTML='<option value=\"\">'+t('allYear')+'</option>';\n"
    "  [...years].sort().forEach(x=>fy.innerHTML+='<option>'+esc(x)+'</option>');\n"
    "}\n"
    "function renderAlbums(){\n  const g=$('albumGrid');\n  g.innerHTML='';\n  const list=filteredAlbums();\n"
    "  if(!albums.length){")

# I. empty when filtered no match
rep("    $('countText').textContent='0 '+t('albumUnit');\n    return;\n  }\n  const now = curSong() ? albumIdxOf(curSong()) : -1;\n  albums.forEach((a,i)=>{const card=document.createElement('div');card.className='album-card';",
    "    $('countText').textContent='0 '+t('albumUnit');\n    return;\n  }\n"
    "  if(!list.length){g.innerHTML='<div class=\"empty\"><h3>'+t('noMatch')+'</h3></div>';$('countText').textContent='0 '+t('albumUnit');return;}\n"
    "  const now = curSong() ? albumIdxOf(curSong()) : -1;\n"
    "  list.forEach((a,i)=>{const card=document.createElement('div');card.className='album-card';")

# J. countText list.length
rep("  $('countText').textContent=albums.length+' '+t('albumUnit');\n}\n\n/* ===== 专辑详情 ===== */",
    "  $('countText').textContent=list.length+' '+t('albumUnit');\n}\n\n/* ===== 专辑详情 ===== */")

# K. bind filters change
rep("document.addEventListener('keydown',e=>{if(e.code==='Space'&&!['INPUT','TEXTAREA'].includes(document.activeElement.tagName)){e.preventDefault();togglePlay();}});",
    "document.addEventListener('keydown',e=>{if(e.code==='Space'&&!['INPUT','TEXTAREA'].includes(document.activeElement.tagName)){e.preventDefault();togglePlay();}});\n"
    "['fArtist','fAlbum','fYear'].forEach(id=>$(id).addEventListener('change',e=>{if(id==='fArtist')fArtist=e.target.value;else if(id==='fAlbum')fAlbum=e.target.value;else fYear=e.target.value;renderAlbums();}));")

# L. applyLang also rebuild filters
rep("  renderAlbums();renderTracklist();renderSearchRows();renderPlaylist();\n  uptimeTick();",
    "  renderAlbums();renderTracklist();renderSearchRows();renderPlaylist();\n  buildFilters();\n  uptimeTick();")

# M. load builds filters after data
rep("  }catch(e){toast(t('loadFail'));}\n  renderAlbums();\n}",
    "  }catch(e){toast(t('loadFail'));}\n  buildFilters();\n  renderAlbums();\n}")

open(p, 'w', encoding='utf-8').write(s)
print('OK index.html new len', len(s))
