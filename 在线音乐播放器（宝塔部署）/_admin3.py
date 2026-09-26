# -*- coding: utf-8 -*-
p = r'C:\Users\Administrator\Doubao\chats\2026-09-26\new-chat\在线音乐播放器（宝塔部署）\admin.php'
s = open(p, encoding='utf-8').read()

def rep(old, new, n=1):
    global s
    c = s.count(old)
    if c != n:
        raise SystemExit('MISMATCH count=%d for: %r' % (c, old[:70]))
    s = s.replace(old, new)

# 1. 在线 IP 面板，放在删除专辑按钮之前
rep('''      <?php if(!$isLoose): ?>''',
    '''      <!-- 当前在线用户 -->
      <div class="panel">
        <h3><span class="dot"></span>当前在线（<span id="olCount">0</span> 人）</h3>
        <div id="olList"></div>
      </div>

      <?php if(!$isLoose): ?>''')

# 2. 在线列表 JS，</body> 前
rep('<?php endif; ?>\n</body>',
    '''<?php endif; ?>
<script>
function $2(id){return document.getElementById(id);}
function olLoad(){
  fetch('online.php').then(r=>r.json()).then(d=>{if(!d||!d.online)return;
    $2('olCount').textContent=d.count;
    const l=$2('olList');
    if(!d.online.length){l.innerHTML='<div class="empty">暂无在线用户</div>';return;}
    l.innerHTML=d.online.map(u=>{
      const tm=new Date(u.t*1000);
      const hh=String(tm.getHours()).padStart(2,'0')+':'+String(tm.getMinutes()).padStart(2,'0')+':'+String(tm.getSeconds()).padStart(2,'0');
      return '<div class="row"><div class="nm"><div class="f">'+u.ip+'</div><div class="m">'+u.id+' · '+(u.pg||'')+'</div></div><div class="ops" style="grid-template-columns:auto"><span style="color:var(--faint);font-size:12px">'+hh+'</span></div></div>';
    }).join('');
  }).catch(()=>{});
}
olLoad();setInterval(olLoad,15000);
</script>
</body>''')

open(p, 'w', encoding='utf-8').write(s)
print('OK admin.php new len', len(s))
