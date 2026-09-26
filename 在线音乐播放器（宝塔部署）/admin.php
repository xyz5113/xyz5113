<?php
/**
 * B&B 音乐播放器 - 管理后台
 * 部署：上传到网站根目录（与 index.html 同级），访问 你的域名/admin.php
 * 改密码：把下面 $ADMIN_PASS 的值改掉即可。
 */
session_start();
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Shanghai');
define('MROOT', __DIR__ . '/music');
$ADMIN_PASS = 'admin123';

$audioExt = ['mp3','m4a','aac','wav','flac','ogg','opus'];
$imgExt   = ['jpg','jpeg','png'];

function clean($s){ return str_replace(['\\','/','..'],'',trim($s)); }
function ext_ok($n,$allow){ return in_array(strtolower(pathinfo($n,PATHINFO_EXTENSION)), $allow); }
function fsize($bytes){ if($bytes>=1048576) return round($bytes/1048576,1).' MB'; return round($bytes/1024).' KB'; }
function e($s){ return htmlspecialchars($s??'', ENT_QUOTES, 'UTF-8'); }

$msg=''; $err='';
$cur = isset($_GET['open']) ? clean($_GET['open']) : '';

if(isset($_POST['action'])){
  $a=$_POST['action'];
  if($a==='login'){
    if(isset($_POST['pass']) && $_POST['pass']===$ADMIN_PASS){ $_SESSION['adm']=1; }
    else { $err='密码错误'; }
  } elseif($a==='logout'){ unset($_SESSION['adm']); }
  elseif(!empty($_SESSION['adm'])){
    try{
      if($a==='mkdir'){
        $n=clean($_POST['name']); if($n==='')throw new Exception('专辑名不能为空');
        if(is_dir(MROOT.'/'.$n))throw new Exception('已存在同名专辑');
        mkdir(MROOT.'/'.$n,0777,true); $msg='专辑「'.$n.'」已创建'; $cur=$n;
      }
      elseif($a==='upload'){
        $album=clean($_POST['album']); $new=clean($_POST['newalbum']);
        if($album==='__new__'){ if($new==='')throw new Exception('请填写新专辑名'); $album=$new; $d=MROOT.'/'.$album; if(!is_dir($d))mkdir($d,0777,true); }
        else { $d=MROOT.'/'.$album; if(!is_dir($d))throw new Exception('专辑不存在'); }
        $n=0;
        if(!empty($_FILES['files'])){ $fs=$_FILES['files']; $cnt=count($fs['name']);
          for($i=0;$i<$cnt;$i++){ if($fs['error'][$i]!==UPLOAD_ERR_OK)continue;
            $nm=clean($fs['name'][$i]); if($nm===''||!ext_ok($nm,$audioExt))continue;
            if(move_uploaded_file($fs['tmp_name'][$i],$d.'/'.$nm))$n++; } }
        if($n===0)throw new Exception('没有上传到有效音频');
        $msg='已上传 '.$n.' 首歌到「'.$album.'」'; $cur=$album;
      }
      elseif($a==='upload_cover'){
        $album=clean($_POST['album']); $d=MROOT.'/'.$album; if(!is_dir($d))throw new Exception('专辑不存在');
        if(empty($_FILES['cover'])||$_FILES['cover']['error']!==UPLOAD_ERR_OK)throw new Exception('请选择封面图片');
        $ext=strtolower(pathinfo($_FILES['cover']['name'],PATHINFO_EXTENSION));
        if(!in_array($ext,['jpg','jpeg','png']))throw new Exception('封面仅支持 jpg/png');
        foreach($imgExt as $ee){ $oc=$d.'/cover.'.$ee; if(is_file($oc))unlink($oc); }
        move_uploaded_file($_FILES['cover']['tmp_name'],$d.'/cover.'.$ext); $msg='专辑封面已更换'; $cur=$album;
      }
      elseif($a==='upload_lrc'){
        $album=clean($_POST['album']); $song=clean($_POST['song']); $d=MROOT.'/'.$album;
        if(!is_dir($d))throw new Exception('专辑不存在'); if($song==='')throw new Exception('请填写歌词对应的歌名');
        if(empty($_FILES['lrc'])||$_FILES['lrc']['error']!==UPLOAD_ERR_OK)throw new Exception('请选择 .lrc 歌词文件');
        if(strtolower(pathinfo($_FILES['lrc']['name'],PATHINFO_EXTENSION))!=='lrc')throw new Exception('仅支持 .lrc 文件');
        move_uploaded_file($_FILES['lrc']['tmp_name'],$d.'/'.$song.'.lrc'); $msg='歌词已上传'; $cur=$album;
      }
      elseif($a==='rename'){
        $album=clean($_POST['album']); $old=clean($_POST['old']); $nn=clean($_POST['newname']);
        $d=MROOT.'/'.$album; if(!is_dir($d))throw new Exception('专辑不存在');
        if($old===''||$nn===''||$nn===$old)throw new Exception('名称无效');
        $ext=strtolower(pathinfo($old,PATHINFO_EXTENSION));
        if(!ext_ok($old,$audioExt))throw new Exception('禁止操作该文件');
        $src=$d.'/'.$old; if(!is_file($src))throw new Exception('文件不存在');
        $dst=$d.'/'.$nn.'.'.$ext; if(is_file($dst))throw new Exception('目标文件已存在');
        rename($src,$dst);
        $bo=pathinfo($old,PATHINFO_FILENAME);
        foreach(array_unique([$imgExt[0],$imgExt[1],$imgExt[2],'lrc']) as $ee){ $o=$d.'/'.$bo.'.'.$ee; if(is_file($o))rename($o,$d.'/'.$nn.'.'.$ee); }
        $msg='已重命名'; $cur=$album;
      }
      elseif($a==='delete_track'){
        $album=clean($_POST['album']); $file=clean($_POST['file']); $d=MROOT.'/'.$album;
        if(!is_dir($d)||!ext_ok($file,$audioExt))throw new Exception('禁止删除');
        $base=pathinfo($file,PATHINFO_FILENAME);
        $targets=array_unique([$d.'/'.$file,$d.'/'.$base.'.jpg',$d.'/'.$base.'.jpeg',$d.'/'.$base.'.png',$d.'/'.$base.'.lrc']);
        foreach($targets as $fp){ if(is_file($fp))unlink($fp); }
        $msg='已删除'; $cur=$album;
      }
      elseif($a==='delete_album'){
        $album=clean($_POST['album']); $d=MROOT.'/'.$album; if(!is_dir($d))throw new Exception('专辑不存在');
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
        foreach($it as $f){ $f->isDir()?rmdir($f->getPathname()):unlink($f->getPathname()); }
        rmdir($d); $msg='专辑已删除'; $cur='';
      }
    }catch(Exception $ex){ $err=$ex->getMessage(); }
  }
}

// 收集专辑（目录）与根目录松散歌曲
$albums=[]; $loose=[];
if(is_dir(MROOT)){
  foreach(scandir(MROOT) as $it){ if($it==='.'||$it==='..'||$it==='lrc')continue; $p=MROOT.'/'.$it; if(is_dir($p)){ $albums[]=$it; } elseif(is_file($p)&&ext_ok($it,$audioExt)){ $loose[]=$it; } }
}
sort($albums,SORT_NATURAL|SORT_FLAG_CASE);
sort($loose,SORT_NATURAL|SORT_FLAG_CASE);
$allList=array_merge($loose?['未分专辑']:[],$albums);
if($cur==='')$cur=$allList[0]??'';
$isLoose = ($cur==='未分专辑');
$curDir = $isLoose ? MROOT : (MROOT.'/'.$cur);
$curTracks=[];
if($cur!=='' && is_dir($curDir)){ foreach(scandir($curDir) as $f){ if($f==='.'||$f==='..')continue; $p=$curDir.'/'.$f; if(is_file($p)&&ext_ok($f,$audioExt))$curTracks[]=$f; } sort($curTracks,SORT_NATURAL|SORT_FLAG_CASE); }
function cover_of($dir){ global $imgExt; foreach($imgExt as $e){ if(is_file($dir.'/cover.'.$e))return 'cover.'.$e; } return null; }
$curCover=($cur!==''&&is_dir($curDir))?cover_of($curDir):null;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>B&B 管理后台</title>
<style>
:root{--bg:#0b0b0d;--surface:#151519;--surface2:#1d1d23;--surface3:#26262e;--border:rgba(255,255,255,.07);--text:#ecebf0;--muted:#a09faa;--faint:#6f6e7a;--accent:#e0a458;--red:#e0566b;--radius:12px}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Noto Sans SC',system-ui,-apple-system,'Segoe UI','Microsoft YaHei',sans-serif;background:radial-gradient(1200px 600px at 82% -8%,rgba(224,164,88,.08),transparent 60%),linear-gradient(180deg,#17171d,#0c0c10);color:var(--text);min-height:100vh}
.wrap{max-width:1180px;margin:0 auto;padding:22px 22px 60px}
.top{display:flex;align-items:center;gap:14px;padding:16px 0;border-bottom:1px solid var(--border);margin-bottom:22px}
.top .logo{width:38px;height:38px;border-radius:10px;background:linear-gradient(140deg,#2a2a32,#18181d);border:1px solid var(--border);display:grid;place-items:center;color:var(--accent)}
.top h1{font-size:18px;font-weight:600;letter-spacing:.3px}
.top .sp{flex:1}
.top a,.top form{display:inline-flex}
.btn{padding:9px 16px;border-radius:9px;border:1px solid var(--border);background:var(--surface2);color:var(--text);cursor:pointer;font-size:14px;transition:.15s;text-decoration:none;font-family:inherit}
.btn:hover{background:var(--surface3)}
.btn.accent{background:var(--accent);color:#181410;border:none;font-weight:600}
.btn.accent:hover{filter:brightness(1.08)}
.btn.danger{border-color:rgba(224,86,107,.5);color:var(--red);background:transparent}
.btn.danger:hover{background:rgba(224,86,107,.12)}
.btn.small{padding:6px 11px;font-size:13px}
.layout{display:grid;grid-template-columns:230px 1fr;gap:20px}
.side{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px;height:max-content}
.side .ttl{font-size:11px;letter-spacing:2px;color:var(--faint);font-weight:600;margin:0 4px 10px}
.side a{display:block;padding:9px 12px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:14px;transition:.12s;margin-bottom:2px}
.side a:hover{color:var(--text);background:var(--surface2)}
.side a.on{color:var(--accent);background:rgba(224,164,88,.12);font-weight:600}
.side .new{display:flex;gap:6px;margin-top:12px}
.side .new input{flex:1;background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:8px 10px;color:var(--text);font-size:13px;outline:none}
.main{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:22px;min-height:420px}
.panel{border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:16px;background:var(--surface2)}
.panel h3{font-size:14px;font-weight:600;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.panel h3 .dot{width:8px;height:8px;border-radius:50%;background:var(--accent)}
.headrow{display:flex;align-items:center;gap:16px;margin-bottom:18px}
.headrow img{width:92px;height:92px;border-radius:12px;object-fit:cover;background:var(--surface3)}
.headrow .hdimg{display:flex;flex-direction:column;gap:9px;align-items:center}
.headrow .noimg{width:92px;height:92px;border-radius:12px;background:var(--surface3);display:grid;place-items:center;color:var(--faint)}
.chgcover label{cursor:pointer}
.headrow .info h2{font-size:22px;font-weight:600}
.headrow .info .sub{color:var(--faint);font-size:13px;margin-top:4px}
form.inline{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
form.inline label{font-size:13px;color:var(--muted)}
input[type=text],select,input[type=file]{background:var(--surface3);border:1px solid var(--border);border-radius:8px;padding:9px 11px;color:var(--text);font-size:13px;outline:none;font-family:inherit}
input[type=text]:focus,select:focus{border-color:rgba(224,164,88,.5)}
select{color-scheme:dark}
.row{display:grid;grid-template-columns:1fr auto auto;gap:10px;align-items:center;padding:10px 12px;border-radius:8px;transition:background .12s}
.row:nth-child(odd){background:rgba(255,255,255,.02)}
.row:hover{background:var(--surface3)}
.row .nm{min-width:0}
.row .nm .f{font-size:14px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.row .nm .m{font-size:12px;color:var(--faint);margin-top:2px}
.row .ops{display:flex;gap:6px;align-items:center}
.row input[type=text]{padding:5px 8px;font-size:13px;width:150px}
.msg{background:rgba(224,164,88,.14);color:var(--accent);padding:11px 16px;border-radius:9px;font-size:14px;margin-bottom:14px}
.err{background:rgba(224,86,107,.14);color:var(--red);padding:11px 16px;border-radius:9px;font-size:14px;margin-bottom:14px}
.empty{text-align:center;color:var(--faint);padding:50px 10px;font-size:14px}
/* 登录 */
.login{max-width:380px;margin:12vh auto;background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:34px 30px;box-shadow:0 24px 70px rgba(0,0,0,.5)}
.login .lg{width:52px;height:52px;border-radius:14px;background:linear-gradient(140deg,#2a2a32,#18181d);border:1px solid var(--border);display:grid;place-items:center;color:var(--accent);margin:0 auto 16px}
.login h1{text-align:center;font-size:18px;margin-bottom:6px}
.login p{text-align:center;color:var(--faint);font-size:13px;margin-bottom:22px}
.login form{display:flex;flex-direction:column;gap:12px}
.login input[type=password]{background:var(--surface3);border:1px solid var(--border);border-radius:10px;padding:13px 14px;color:var(--text);font-size:15px;outline:none}
@media(max-width:720px){.layout{grid-template-columns:1fr}.side a{display:inline-block;margin:2px}.row{grid-template-columns:1fr;grid-auto-rows:auto}.row .ops{flex-wrap:wrap}}
</style>
</head>
<body>
<?php if(empty($_SESSION['adm'])): ?>
<div class="login">
  <div class="lg"><svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M9 17V8l7-1.5V15" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="17" r="3" stroke="currentColor" stroke-width="1.5"/><circle cx="16" cy="15" r="3" stroke="currentColor" stroke-width="1.5"/></svg></div>
  <h1>B&amp;B 管理后台</h1>
  <p>输入密码进入管理</p>
  <?php if($err)echo '<div class="err">'.e($err).'</div>'; ?>
  <form method="post">
    <input type="hidden" name="action" value="login">
    <input type="password" name="pass" placeholder="管理密码" autofocus>
    <button class="btn accent" type="submit">登 录</button>
  </form>
</div>
<?php else: ?>
<div class="wrap">
  <div class="top">
    <span class="logo"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 17V8l7-1.5V15" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="17" r="3" stroke="currentColor" stroke-width="1.5"/><circle cx="16" cy="15" r="3" stroke="currentColor" stroke-width="1.5"/></svg></span>
    <h1>B&amp;B 管理后台</h1>
    <span class="sp"></span>
    <a class="btn small" href="index.html" target="_blank">查看站点</a>
    <form method="post" style="margin:0"><input type="hidden" name="action" value="logout"><button class="btn small" type="submit">退出</button></form>
  </div>
  <?php if($msg)echo '<div class="msg">'.e($msg).'</div>'; if($err)echo '<div class="err">'.e($err).'</div>'; ?>

  <div class="layout">
    <div class="side">
      <div class="ttl">专辑列表</div>
      <?php foreach($allList as $a): ?>
        <a class="<?php echo $a===$cur?'on':''; ?>" href="admin.php?open=<?php echo urlencode($a); ?>"><?php echo e($a); ?></a>
      <?php endforeach; if(!$allList)echo '<div class="empty">还没有专辑</div>'; ?>
      <form class="new" method="post"><input type="hidden" name="action" value="mkdir"><input type="text" name="name" placeholder="新专辑名"><button class="btn small accent" type="submit">新建</button></form>
    </div>

    <div class="main">
      <?php if($cur===''): ?>
        <div class="empty">请选择或新建一个专辑开始管理。</div>
      <?php else: ?>
      <div class="headrow">
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
      </div>

      <!-- 上传歌曲 -->
      <div class="panel">
        <h3><span class="dot"></span>上传歌曲</h3>
        <form class="inline" method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload">
          <select name="album">
            <option value="<?php echo e($cur); ?>"><?php echo e($cur); ?></option>
            <option value="__new__">+ 新建专辑上传</option>
            <?php foreach($albums as $a){ if($a!==$cur)echo '<option value="'.e($a).'">'.e($a).'</option>'; } ?>
          </select>
          <input type="text" name="newalbum" placeholder="新专辑名（选上面的+时填）">
          <input type="file" name="files[]" accept=".mp3,.flac,.wav,.m4a,.aac,.ogg,.opus" multiple>
          <button class="btn accent" type="submit">上传</button>
        </form>
      </div>

      <!-- 封面 / 歌词 -->
      <div class="panel">
        <h3><span class="dot"></span>上传封面 / 歌词</h3>
        <form class="inline" method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_cover">
          <input type="hidden" name="album" value="<?php echo e($cur); ?>">
          <label>专辑封面</label><input type="file" name="cover" accept=".jpg,.jpeg,.png"><button class="btn small" type="submit">上传封面</button>
        </form>
        <form class="inline" method="post" enctype="multipart/form-data" style="margin-top:10px">
          <input type="hidden" name="action" value="upload_lrc">
          <input type="hidden" name="album" value="<?php echo e($cur); ?>">
          <input type="text" name="song" placeholder="歌词对应的歌名">
          <input type="file" name="lrc" accept=".lrc"><button class="btn small" type="submit">上传歌词</button>
        </form>
      </div>

      <!-- 歌曲列表 -->
      <div class="panel">
        <h3><span class="dot"></span>歌曲列表</h3>
        <?php if(!$curTracks): ?>
          <div class="empty">这个专辑还没有歌曲，用上面「上传歌曲」添加。</div>
        <?php else: foreach($curTracks as $t): $tp=$curDir.'/'.$t; $base=pathinfo($t,PATHINFO_FILENAME); ?>
          <div class="row">
            <div class="nm">
              <div class="f"><?php echo e($base); ?></div>
              <div class="m"><?php echo e($t); ?> · <?php echo fsize(filesize($tp)); ?></div>
            </div>
            <div class="ops">
              <form method="post" style="margin:0;display:flex;gap:6px;align-items:center">
                <input type="hidden" name="action" value="rename">
                <input type="hidden" name="album" value="<?php echo e($cur); ?>">
                <input type="hidden" name="old" value="<?php echo e($t); ?>">
                <input type="text" name="newname" value="<?php echo e($base); ?>" placeholder="歌手 - 歌名">
                <button class="btn small" type="submit">改名</button>
              </form>
              <form method="post" style="margin:0" onsubmit="return confirm('确认删除这首歌？')">
                <input type="hidden" name="action" value="delete_track">
                <input type="hidden" name="album" value="<?php echo e($cur); ?>">
                <input type="hidden" name="file" value="<?php echo e($t); ?>">
                <button class="btn small danger" type="submit">删除</button>
              </form>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- 当前在线用户 -->
      <div class="panel">
        <h3><span class="dot"></span>当前在线（<span id="olCount">0</span> 人）</h3>
        <div id="olList"></div>
      </div>

      <?php if(!$isLoose): ?>
      <div style="text-align:right"><form method="post" onsubmit="return confirm('确认删除整个专辑「<?php echo e($cur); ?>」及其所有文件？')"><input type="hidden" name="action" value="delete_album"><input type="hidden" name="album" value="<?php echo e($cur); ?>"><button class="btn danger" type="submit">删除整个专辑</button></form></div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>
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
</body>
</html>
