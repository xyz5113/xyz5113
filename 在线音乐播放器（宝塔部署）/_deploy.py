# -*- coding: utf-8 -*-
p = r'C:\Users\Administrator\Doubao\chats\2026-09-26\new-chat\在线音乐播放器（宝塔部署）\部署说明.txt'
s = open(p, encoding='utf-8').read()

def rep(old, new, n=1):
    global s
    c = s.count(old)
    if c != n:
        raise SystemExit('MISMATCH count=%d for: %r' % (c, old[:70]))
    s = s.replace(old, new)

rep('''  1. index.html   —— 播放器页面（前端）
  2. list.php     —— 播放列表生成器（后端，自动扫描 music 文件夹）
  3. admin.php    —— 网页管理后台（可在线传歌/封面/歌词、改名、删除、建专辑）
  4. record.php   —— 播放量统计接口（每播一次自动 +1，无需手动管）
  5. assets/      —— 默认封面（不要删）
  6. music/       —— 存放音乐文件（你按“专辑”分文件夹传歌）
  7. stats/       —— 会自动创建，存放播放量数据，不用手动管

  （stats 目录无需上传，第一次有人播放时会自动生成；
    服务器上如果已有这个目录，确保 PHP 有写入权限即可。）''',
'''  1. index.html   —— 播放器页面（前端）
  2. list.php     —— 播放列表生成器（后端，自动扫描 music 文件夹）
  3. admin.php    —— 网页管理后台（传歌/封面/歌词、改名、删除、建专辑，含“当前在线 IP”）
  4. online.php   —— 在线人数 / 在线 IP 统计（新）
  5. record.php   —— 播放量接口（播放次数已去掉，本文件可不传，留着也无害）
  6. assets/      —— 默认封面（不要删）
  7. music/       —— 存放音乐文件（你按“专辑”分文件夹传歌）
  8. stats/       —— 会自动创建，存放在线人数数据，不用手动管

  （stats 目录无需上传，第一次有人打开页面时会自动生成；
    服务器上如果已有这个目录，确保 PHP 有写入权限即可。）''')

rep('''4. 进入该目录，把上面所有文件（index.html、list.php、admin.php、
   record.php、assets、music）全部上传进去（宝塔“文件管理器”拖拽即可）。''',
'''4. 进入该目录，把上面所有文件（index.html、list.php、admin.php、
   online.php、assets、music）全部上传进去（宝塔“文件管理器”拖拽即可）。''')

rep('''播放量：每首歌被播放一次会自动 +1，在播放页歌曲名下方显示“已播放 X 次”。
筛选：首页“全部歌手 / 全部专辑 / 全部年份”三个下拉可快速筛选。''',
'''在线人数：首页底部显示“在线观看 X 人”，每 15 秒自动刷新。
后台看在线 IP：登录 admin.php 后，“当前在线”面板会列出在线用户的
  IP、设备标识和最近活跃时间，每 15 秒自动刷新。
筛选：首页“全部歌手 / 全部专辑 / 全部年份”三个下拉可快速筛选。''')

open(p, 'w', encoding='utf-8').write(s)
print('OK deploy len', len(s))
