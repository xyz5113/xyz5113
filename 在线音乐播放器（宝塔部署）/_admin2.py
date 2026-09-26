# -*- coding: utf-8 -*-
p = r'C:\Users\Administrator\Doubao\chats\2026-09-26\new-chat\在线音乐播放器（宝塔部署）\admin.php'
s = open(p, encoding='utf-8').read()

def rep(old, new, n=1):
    global s
    c = s.count(old)
    if c != n:
        raise SystemExit('MISMATCH count=%d for: %r' % (c, old[:70]))
    s = s.replace(old, new)

# 1. upload_cover 先删旧封面
rep("        if(!in_array($ext,['jpg','jpeg','png']))throw new Exception('封面仅支持 jpg/png');\n        move_uploaded_file($_FILES['cover']['tmp_name'],$d.'/cover.'.$ext); $msg='专辑封面已更新'; $cur=$album;",
    "        if(!in_array($ext,['jpg','jpeg','png']))throw new Exception('封面仅支持 jpg/png');\n        foreach($imgExt as $ee){ $oc=$d.'/cover.'.$ee; if(is_file($oc))unlink($oc); }\n        move_uploaded_file($_FILES['cover']['tmp_name'],$d.'/cover.'.$ext); $msg='专辑封面已更换'; $cur=$album;")

# 2. CSS: hdimg + noimg + chgcover
rep(".headrow img{width:92px;height:92px;border-radius:12px;object-fit:cover;background:var(--surface3)}",
    ".headrow img{width:92px;height:92px;border-radius:12px;object-fit:cover;background:var(--surface3)}\n.headrow .hdimg{display:flex;flex-direction:column;gap:9px;align-items:center}\n.headrow .noimg{width:92px;height:92px;border-radius:12px;background:var(--surface3);display:grid;place-items:center;color:var(--faint)}\n.chgcover label{cursor:pointer}")

# 3. headrow: 封面下直接放更换按钮
rep('''      <div class="headrow">
        <?php if($curCover): ?><img src="music/<?php echo $isLoose?'':urlencode($cur).'/'; ?><?php echo e($curCover); ?>" alt=""><?php else: ?><img src="" alt=""><?php endif; ?>
        <div class="info">
          <h2><?php echo e($cur); ?></h2>
          <div class="sub"><?php echo count($curTracks); ?> 首歌</div>
        </div>
      </div>''',
    '''      <div class="headrow">
        <div class="hdimg">
          <?php if($curCover): ?><img src="music/<?php echo $isLoose?'':urlencode($cur).'/'; ?><?php echo e($curCover); ?>" alt=""><?php else: ?><div class="noimg"><svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M4 5h16v14H4z" stroke="currentColor" stroke-width="1.4"/><circle cx="9" cy="9" r="2" stroke="currentColor" stroke-width="1.4"/><path d="M4 17l4-4 4 4 3-3 5 5" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg></div><?php endif; ?>
          <form class="chgcover" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_cover">
            <input type="hidden" name="album" value="<?php echo e($cur); ?>">
            <label class="btn small">更换封面<input type="file" name="cover" accept=".jpg,.jpeg,.png" hidden onchange="this.form.submit()"></label>
          </form>
        </div>
        <div class="info">
          <h2><?php echo e($cur); ?></h2>
          <div class="sub"><?php echo count($curTracks); ?> 首歌</div>
        </div>
      </div>''')

open(p, 'w', encoding='utf-8').write(s)
print('OK admin.php new len', len(s))
