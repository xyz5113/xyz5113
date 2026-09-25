# -*- coding: utf-8 -*-
import io
p = r"C:\Users\Administrator\Doubao\chats\2026-09-26\new-chat\在线音乐播放器（宝塔部署）\list.php"
s = io.open(p, encoding="utf-8").read()
print({
    "brace_ok": s.count("{") == s.count("}"),
    "paren_ok": s.count("(") == s.count(")"),
    "bom": s.startswith("\ufeff"),
    "collect_lrcs": s.count("collect_lrcs"),
    "lrcMap_refs": s.count("lrcMap"),
})
