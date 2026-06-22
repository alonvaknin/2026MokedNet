<!-- ════════════════════════════════════════
     Calendar Widget v5 – RTL Hebrew Helper
     ════════════════════════════════════════ -->
<style>
#cal-fab{position:fixed;bottom:24px;left:24px;width:42px;height:42px;background:var(--bg3);border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text3);font-size:17px;z-index:410;transition:all .2s;box-shadow:0 4px 14px rgba(0,0,0,.35);}
#cal-fab:hover{background:var(--accent-dim);color:var(--accent);border-color:rgba(91,141,238,.4);transform:scale(1.1);}
#cal-fab.cal-open{background:var(--accent);color:#fff;border-color:var(--accent);}

#cal-panel{position:fixed;bottom:76px;left:24px;background:var(--bg2);border:1px solid var(--border2);border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.6);z-index:409;display:none;flex-direction:column;direction:rtl;font-family:var(--font);max-width:calc(100vw - 48px);max-height:92vh;overflow-x:hidden;overflow-y:auto;}
#cal-panel.cal-show{display:flex;}

/* toolbar */
.cal-tb{display:flex;flex-direction:column;gap:5px;padding:9px 11px 7px;border-bottom:1px solid var(--border);background:var(--bg3);flex-shrink:0;}
.cal-trow{display:flex;align-items:center;gap:4px;}
.cal-nb{width:27px;height:27px;border:1px solid var(--border);border-radius:7px;background:var(--bg4);color:var(--text2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;transition:all .13s;flex-shrink:0;}
.cal-nb:hover{background:var(--accent-dim);color:var(--accent);}
.cal-nb:active{transform:scale(.9);}
#cal-lbl{font-size:12px;font-weight:700;color:var(--text);flex:1;text-align:center;padding:3px 4px;border-radius:5px;cursor:pointer;transition:background .13s;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
#cal-lbl:hover{background:var(--bg4);}
.cal-tdbtn{font-size:10px;font-weight:600;padding:3px 8px;border-radius:20px;border:1px solid var(--border);background:var(--bg4);color:var(--text2);cursor:pointer;transition:all .13s;white-space:nowrap;flex-shrink:0;}
.cal-tdbtn:hover{background:var(--accent-dim);color:var(--accent);}
.cal-inpw{display:flex;align-items:center;gap:4px;background:var(--bg4);border:1px solid var(--border);border-radius:7px;padding:0 7px;flex:1;min-width:0;}
.cal-inpw:focus-within{border-color:var(--accent);}
.cal-inpw i{color:var(--text3);font-size:10px;flex-shrink:0;}
#cal-inp{background:none;border:none;outline:none;color:var(--text);font-family:var(--font);font-size:11px;padding:5px 0;width:100%;min-width:0;direction:ltr;text-align:center;}
#cal-inp::placeholder{color:var(--text3);direction:rtl;text-align:right;}
.cal-bgroup{display:flex;gap:2px;flex-shrink:0;}
.cal-tbbtn{width:23px;height:23px;border:1px solid var(--border);border-radius:5px;background:var(--bg4);color:var(--text3);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;transition:all .13s;font-family:var(--font);}
.cal-tbbtn:hover,.cal-tbbtn.cal-on{background:var(--accent-dim);color:var(--accent);border-color:rgba(91,141,238,.4);}
.cal-holbtn{display:flex;align-items:center;gap:3px;padding:3px 9px;border:1px solid var(--border);border-radius:20px;background:var(--bg4);color:var(--text3);cursor:pointer;font-size:10px;font-weight:600;transition:all .13s;font-family:var(--font);white-space:nowrap;flex-shrink:0;}
.cal-holbtn:hover{background:rgba(245,158,11,.12);color:#f59e0b;border-color:rgba(245,158,11,.3);}
.cal-holbtn.cal-on{background:rgba(245,158,11,.15);color:#f59e0b;border-color:rgba(245,158,11,.4);}
.cal-sep{width:1px;height:16px;background:var(--border);flex-shrink:0;margin:0 1px;}

/* months container */
#cal-mw{display:flex;flex-shrink:0;overflow-x:auto;scrollbar-width:thin;scrollbar-color:var(--border2) transparent;}
#cal-mw::-webkit-scrollbar{height:3px;}
#cal-mw::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px;}
.cal-mon{padding:9px 9px 11px;flex-shrink:0;border-left:1px solid var(--border);}
.cal-mon:last-child{border-left:none;}
.cal-montit{font-size:11px;font-weight:700;color:var(--text2);text-align:center;margin-bottom:6px;}
.cal-dhrow,.cal-grid{display:grid;gap:2px;}
.cal-dh{font-size:9px;font-weight:700;color:var(--text3);text-align:center;padding:2px 0;}
.cal-dh.cal-fri{color:#f59e0b;opacity:.85;}.cal-dh.cal-sat{color:#ef4444;opacity:.85;}

/* SIZE VARIANTS — default M */
.cal-dhrow,.cal-grid{grid-template-columns:repeat(7,32px);}
.cal-day{width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:500;border-radius:6px;cursor:pointer;border:1px solid transparent;color:var(--text2);position:relative;user-select:none;-webkit-user-select:none;transition:background .1s,color .1s;}
/* S */
#cal-panel.cal-sz-s .cal-dhrow,#cal-panel.cal-sz-s .cal-grid{grid-template-columns:repeat(7,26px);}
#cal-panel.cal-sz-s .cal-day{width:26px;height:26px;font-size:10px;border-radius:5px;}
#cal-panel.cal-sz-s .cal-dh{font-size:8px;}
#cal-panel.cal-sz-s .cal-montit{font-size:10px;}
/* L */
#cal-panel.cal-sz-l .cal-dhrow,#cal-panel.cal-sz-l .cal-grid{grid-template-columns:repeat(7,40px);}
#cal-panel.cal-sz-l .cal-day{width:40px;height:40px;font-size:14px;border-radius:7px;}
#cal-panel.cal-sz-l .cal-dh{font-size:10px;}
#cal-panel.cal-sz-l .cal-montit{font-size:12px;}
#cal-panel.cal-sz-l .cal-nb{width:30px;height:30px;font-size:12px;}
#cal-panel.cal-sz-l .cal-tbbtn{width:26px;height:26px;font-size:10px;}

/* day states */
.cal-day:hover{background:var(--accent-dim);color:var(--accent);border-color:rgba(91,141,238,.2);}
.cal-day.cal-emp{visibility:hidden;pointer-events:none;}
.cal-day.cal-today{background:var(--accent)!important;color:#fff!important;font-weight:700;box-shadow:0 2px 8px rgba(91,141,238,.4);}
.cal-day.cal-today:hover{background:var(--accent-hover)!important;}
.cal-day.cal-fri{color:#f59e0b;}
.cal-day.cal-sat{color:#ef4444;opacity:.6;}
.cal-day.cal-sat:hover{opacity:1;}
.cal-day.cal-wday::after{content:'';position:absolute;bottom:3px;left:50%;transform:translateX(-50%);width:3px;height:3px;border-radius:50%;background:rgba(34,197,94,.45);}
.cal-day.cal-today::after{background:rgba(255,255,255,.5)!important;}
/* holidays */
.cal-day.hol-h{color:#f59e0b;font-weight:600;}
.cal-day.hol-c{color:#d97706;}
.cal-day.hol-i{color:#06b6d4;font-weight:600;}
.cal-day.hol-r{color:#8b5cf6;}
.cal-day.hol-h::before{content:'★';position:absolute;top:1px;right:1px;font-size:5px;color:#f59e0b;line-height:1;}
.cal-day.hol-c::before{content:'◐';position:absolute;top:1px;right:1px;font-size:5px;color:#d97706;line-height:1;}
.cal-day.hol-i::before{content:'✦';position:absolute;top:1px;right:1px;font-size:5px;color:#06b6d4;line-height:1;}
.cal-day.hol-r::before{content:'●';position:absolute;top:1px;right:1px;font-size:4px;color:#8b5cf6;line-height:1;}
#cal-panel.cal-sz-l .cal-day[class*="hol-"]::before{font-size:7px;}
/* range */
.cal-day.cal-inr{background:rgba(91,141,238,.13);color:var(--accent);border-color:rgba(91,141,238,.12);border-radius:0!important;}
.cal-day.cal-rs{border-radius:6px 0 0 6px!important;}
.cal-day.cal-re{border-radius:0 6px 6px 0!important;}
.cal-day.cal-rs.cal-re{border-radius:6px!important;}
.cal-day.cal-rs,.cal-day.cal-re{background:var(--accent)!important;color:#fff!important;border-color:var(--accent)!important;box-shadow:0 2px 6px rgba(91,141,238,.4);}
#cal-panel.cal-sz-l .cal-day.cal-rs{border-radius:7px 0 0 7px!important;}
#cal-panel.cal-sz-l .cal-day.cal-re{border-radius:0 7px 7px 0!important;}
#cal-panel.cal-sz-l .cal-day.cal-rs.cal-re{border-radius:7px!important;}

/* legend */
#cal-leg{display:flex;align-items:center;gap:7px;flex-wrap:wrap;padding:6px 11px;border-top:1px solid var(--border);background:var(--bg3);font-size:9px;color:var(--text3);flex-shrink:0;}
.cal-lgi{display:flex;align-items:center;gap:3px;}
.cal-lgd{width:7px;height:7px;border-radius:50%;flex-shrink:0;}

/* popup */
#cal-pop{position:fixed;z-index:420;background:var(--bg2);border:1px solid var(--border2);border-radius:12px;box-shadow:0 16px 48px rgba(0,0,0,.6);padding:13px 15px;min-width:255px;max-width:305px;direction:rtl;font-family:var(--font);max-height:80vh;overflow-y:auto;animation:cal-pop-in .15s ease;}
@keyframes cal-pop-in{from{opacity:0;transform:translateY(6px) scale(.97)}to{opacity:1;transform:none}}
.cp-x{position:absolute;top:8px;left:10px;background:none;border:none;color:var(--text3);font-size:15px;cursor:pointer;padding:2px 6px;line-height:1;border-radius:4px;transition:all .13s;}
.cp-x:hover{color:var(--text);background:var(--bg4);}
.cp-tit{font-size:13px;font-weight:700;color:var(--accent);margin-bottom:9px;padding-left:20px;line-height:1.4;}
.cp-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;font-size:11px;font-weight:700;margin-bottom:8px;}
.cp-row{display:flex;align-items:center;justify-content:space-between;padding:5px 8px;border-radius:6px;margin-bottom:3px;font-size:12px;}
.cp-row .ll{color:var(--text3);font-size:15px;}.cp-row .vv{font-weight:700;color:var(--text);font-size:16px;}
.cp-rw{background:rgba(34,197,94,.07);border:1px solid rgba(34,197,94,.15);}.cp-rw .ll{color:#4ade80;}.cp-rw .vv{color:#22c55e;}
.cp-rb{background:var(--bg3);border:1px solid var(--border);}
.cp-rh{background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.15);}.cp-rh .ll{color:#fbbf24;}.cp-rh .vv{color:#f59e0b;}
.cp-rc{background:rgba(217,119,6,.07);border:1px solid rgba(217,119,6,.15);}.cp-rc .ll{color:#fcd34d;}.cp-rc .vv{color:#d97706;}
.cp-rn{background:rgba(6,182,212,.07);border:1px solid rgba(6,182,212,.15);}.cp-rn .ll{color:#67e8f9;}.cp-rn .vv{color:#06b6d4;}
.cp-rf{background:rgba(245,158,11,.05);border:1px solid rgba(245,158,11,.1);}.cp-rf .ll{color:#fbbf24;}.cp-rf .vv{color:#f59e0b;}
.cp-rs{background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.12);}.cp-rs .ll{color:#fca5a5;}.cp-rs .vv{color:#ef4444;}
.cp-bk{font-size:14px;color:var(--text3);padding:4px 8px;background:var(--bg3);border-radius:5px;margin-bottom:5px;}
.cp-evs{margin-top:7px;padding-top:7px;border-top:1px solid var(--border);}
.cp-evhd{font-size:9px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;}
.cp-ev{display:flex;align-items:center;gap:6px;padding:3px 7px;border-radius:5px;margin-bottom:2px;font-size:11px;background:var(--bg3);border:1px solid var(--border);}
.cp-evd{width:6px;height:6px;border-radius:50%;flex-shrink:0;}
.cp-evdt{color:var(--text3);font-size:10px;flex-shrink:0;}
.cp-evn{color:var(--text);font-weight:600;}
</style>

<!-- FAB -->
<button id="cal-fab" onclick="calToggle()" title="לוח שנה — ימי עסקים">
  <i class="bi bi-calendar3"></i>
</button>

<!-- PANEL -->
<div id="cal-panel">
  <div class="cal-tb">
    <div class="cal-trow">
      <button class="cal-nb" onclick="calNavYear(-1)" title="שנה אחורה"><i class="bi bi-chevron-double-right"></i></button>
      <button class="cal-nb" onclick="calNavMonth(-1)" title="חודש אחורה"><i class="bi bi-chevron-right"></i></button>
      <span id="cal-lbl" onclick="calGoToday()"></span>
      <button class="cal-nb" onclick="calNavMonth(1)" title="חודש קדימה"><i class="bi bi-chevron-left"></i></button>
      <button class="cal-nb" onclick="calNavYear(1)" title="שנה קדימה"><i class="bi bi-chevron-double-left"></i></button>
      <button class="cal-tdbtn" onclick="calGoToday()">היום</button>
    </div>
    <div class="cal-trow">
      <div class="cal-inpw">
        <i class="bi bi-input-cursor-text"></i>
        <input type="text" id="cal-inp" placeholder="04052026 / 04.05.2026" maxlength="12" autocomplete="off" spellcheck="false">
      </div>
      <button class="cal-holbtn" id="cal-hbtn" onclick="calToggleHols()">✡ חגים</button>
      <div class="cal-sep"></div>
      <div class="cal-bgroup" id="cal-szbtns">
        <button class="cal-tbbtn" id="cal-wsS" onclick="calSetSz('s')">S</button>
        <button class="cal-tbbtn" id="cal-wsM" onclick="calSetSz('m')">M</button>
        <button class="cal-tbbtn" id="cal-wsL" onclick="calSetSz('l')">L</button>
      </div>
      <div class="cal-sep"></div>
      <div class="cal-bgroup">
        <button class="cal-tbbtn" id="cal-n1" onclick="calSetN(1)">1</button>
        <button class="cal-tbbtn" id="cal-n2" onclick="calSetN(2)">2</button>
        <button class="cal-tbbtn" id="cal-n3" onclick="calSetN(3)">3</button>
      </div>
    </div>
  </div>
  <div id="cal-mw"></div>
  <div id="cal-leg"></div>
</div>

<!-- POPUP -->
<div id="cal-pop" style="display:none;">
  <button class="cp-x" onclick="calClosePopup()">✕</button>
  <div class="cp-tit" id="cp-tit"></div>
  <div id="cp-body"></div>
</div>

<script>
/* ═══════════════════════════════════════
   HOLIDAY DATA  Israel 5784-5788
   t: h=חג  c=חוה״מ  i=עצמאות  r=זיכרון
   ═══════════════════════════════════════ */
var _CHOLS = {
  '2023-09-16':{n:'ראש השנה',t:'h'},'2023-09-17':{n:'ראש השנה',t:'h'},
  '2023-09-25':{n:'יום כיפור',t:'h'},
  '2023-09-30':{n:'סוכות',t:'h'},
  '2023-10-01':{n:'חוה״מ סוכות',t:'c'},'2023-10-02':{n:'חוה״מ סוכות',t:'c'},
  '2023-10-03':{n:'חוה״מ סוכות',t:'c'},'2023-10-04':{n:'חוה״מ סוכות',t:'c'},
  '2023-10-05':{n:'חוה״מ סוכות',t:'c'},
  '2023-10-07':{n:'שמיני עצרת',t:'h'},
  '2024-04-23':{n:'פסח',t:'h'},
  '2024-04-24':{n:'חוה״מ פסח',t:'c'},'2024-04-25':{n:'חוה״מ פסח',t:'c'},
  '2024-04-26':{n:'חוה״מ פסח',t:'c'},'2024-04-27':{n:'חוה״מ פסח',t:'c'},
  '2024-04-28':{n:'חוה״מ פסח',t:'c'},
  '2024-04-29':{n:'שביעי של פסח',t:'h'},
  '2024-05-06':{n:'יום השואה',t:'r'},
  '2024-05-13':{n:'יום הזיכרון',t:'r'},'2024-05-14':{n:'יום העצמאות',t:'i'},
  '2024-06-12':{n:'שבועות',t:'h'},
  '2024-10-03':{n:'ראש השנה',t:'h'},'2024-10-04':{n:'ראש השנה',t:'h'},
  '2024-10-12':{n:'יום כיפור',t:'h'},
  '2024-10-17':{n:'סוכות',t:'h'},
  '2024-10-18':{n:'חוה״מ סוכות',t:'c'},'2024-10-19':{n:'חוה״מ סוכות',t:'c'},
  '2024-10-20':{n:'חוה״מ סוכות',t:'c'},'2024-10-21':{n:'חוה״מ סוכות',t:'c'},
  '2024-10-22':{n:'חוה״מ סוכות',t:'c'},
  '2024-10-24':{n:'שמיני עצרת',t:'h'},
  '2025-04-13':{n:'פסח',t:'h'},
  '2025-04-14':{n:'חוה״מ פסח',t:'c'},'2025-04-15':{n:'חוה״מ פסח',t:'c'},
  '2025-04-16':{n:'חוה״מ פסח',t:'c'},'2025-04-17':{n:'חוה״מ פסח',t:'c'},
  '2025-04-18':{n:'חוה״מ פסח',t:'c'},
  '2025-04-19':{n:'שביעי של פסח',t:'h'},
  '2025-04-24':{n:'יום השואה',t:'r'},
  '2025-04-30':{n:'יום הזיכרון',t:'r'},'2025-05-01':{n:'יום העצמאות',t:'i'},
  '2025-06-02':{n:'שבועות',t:'h'},
  '2025-09-23':{n:'ראש השנה',t:'h'},'2025-09-24':{n:'ראש השנה',t:'h'},
  '2025-10-02':{n:'יום כיפור',t:'h'},
  '2025-10-07':{n:'סוכות',t:'h'},
  '2025-10-08':{n:'חוה״מ סוכות',t:'c'},'2025-10-09':{n:'חוה״מ סוכות',t:'c'},
  '2025-10-10':{n:'חוה״מ סוכות',t:'c'},'2025-10-11':{n:'חוה״מ סוכות',t:'c'},
  '2025-10-12':{n:'חוה״מ סוכות',t:'c'},
  '2025-10-14':{n:'שמיני עצרת',t:'h'},
  '2026-04-02':{n:'פסח',t:'h'},
  '2026-04-03':{n:'חוה״מ פסח',t:'c'},'2026-04-04':{n:'חוה״מ פסח',t:'c'},
  '2026-04-05':{n:'חוה״מ פסח',t:'c'},'2026-04-06':{n:'חוה״מ פסח',t:'c'},
  '2026-04-07':{n:'חוה״מ פסח',t:'c'},
  '2026-04-08':{n:'שביעי של פסח',t:'h'},
  '2026-04-14':{n:'יום השואה',t:'r'},
  '2026-04-20':{n:'יום הזיכרון',t:'r'},'2026-04-21':{n:'יום העצמאות',t:'i'},
  '2026-05-22':{n:'שבועות',t:'h'},
  '2026-09-12':{n:'ראש השנה',t:'h'},'2026-09-13':{n:'ראש השנה',t:'h'},
  '2026-09-21':{n:'יום כיפור',t:'h'},
  '2026-09-26':{n:'סוכות',t:'h'},
  '2026-09-27':{n:'חוה״מ סוכות',t:'c'},'2026-09-28':{n:'חוה״מ סוכות',t:'c'},
  '2026-09-29':{n:'חוה״מ סוכות',t:'c'},'2026-09-30':{n:'חוה״מ סוכות',t:'c'},
  '2026-10-01':{n:'חוה״מ סוכות',t:'c'},
  '2026-10-03':{n:'שמיני עצרת',t:'h'},
  '2027-04-21':{n:'פסח',t:'h'},
  '2027-04-22':{n:'חוה״מ פסח',t:'c'},'2027-04-23':{n:'חוה״מ פסח',t:'c'},
  '2027-04-24':{n:'חוה״מ פסח',t:'c'},'2027-04-25':{n:'חוה״מ פסח',t:'c'},
  '2027-04-26':{n:'חוה״מ פסח',t:'c'},
  '2027-04-27':{n:'שביעי של פסח',t:'h'},
  '2027-05-11':{n:'יום הזיכרון',t:'r'},'2027-05-12':{n:'יום העצמאות',t:'i'},
  '2027-06-10':{n:'שבועות',t:'h'},
  '2027-09-30':{n:'ראש השנה',t:'h'},'2027-10-01':{n:'ראש השנה',t:'h'},
  '2027-10-09':{n:'יום כיפור',t:'h'},
  '2027-10-14':{n:'סוכות',t:'h'},
  '2027-10-15':{n:'חוה״מ סוכות',t:'c'},'2027-10-16':{n:'חוה״מ סוכות',t:'c'},
  '2027-10-17':{n:'חוה״מ סוכות',t:'c'},'2027-10-18':{n:'חוה״מ סוכות',t:'c'},
  '2027-10-19':{n:'חוה״מ סוכות',t:'c'},
  '2027-10-21':{n:'שמיני עצרת',t:'h'}
};
var _CHCOL={h:'#f59e0b',c:'#d97706',i:'#06b6d4',r:'#8b5cf6'};
var _CHLBL={h:'חג',c:'חול המועד',i:'עצמאות',r:'זיכרון'};
var _CHM=['ינואר','פברואר','מרץ','אפריל','מאי','יוני','יולי','אוגוסט','ספטמבר','אוקטובר','נובמבר','דצמבר'];
var _CHD=['א׳','ב׳','ג׳','ד׳','ה׳','ו׳','ש׳'];
var _CHF=['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];
var _CSZW={s:206,m:254,l:318};

/* ═══ STATE ═══ */
var _CS={
  y:new Date().getFullYear(),
  m:new Date().getMonth(),
  n:Math.min(3,Math.max(1,parseInt(localStorage.getItem('cal_n')||'1'))),
  sz:localStorage.getItem('cal_sz')||'m',
  hols:localStorage.getItem('cal_h')==='1',
  rs:null, re:null,  /* range start/end timestamps */
  md:false           /* mousedown active for drag */
};

/* ═══ HELPERS ═══ */
function _cToday(){var d=new Date();d.setHours(0,0,0,0);return d;}
function _cDK(d){return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');}
function _cHol(d){return _CHOLS[_cDK(d)]||null;}
function _cCat(d){
  var dw=d.getDay();
  if(dw===6)return'sat';
  if(dw===5)return'fri';
  if(_CS.hols){var h=_cHol(d);if(h){if(h.t==='h'||h.t==='i')return'hol';if(h.t==='c')return'chol';}}
  return'work';
}
function _cBkd(n){
  var y=Math.floor(n/365),r=n-y*365,mo=Math.floor(r/30),d=r-mo*30,p=[];
  if(y)p.push(y+(y===1?' שנה':' שנים'));
  if(mo)p.push(mo+(mo===1?' חודש':' חודשים'));
  if(d||!p.length)p.push(d+(d===1?' יום':' ימים'));
  return p.join(' ו-');
}
function _cFmt(d){return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0')+'/'+d.getFullYear();}
function _cFmts(d){return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0');}
function _cE(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function _cStats(t0,t1){
  var s=new Date(Math.min(t0,t1));s.setHours(0,0,0,0);
  var e=new Date(Math.max(t0,t1));e.setHours(0,0,0,0);
  var tot=0,wk=0,fr=0,sa=0,hl=0,ch=0,na=0,evs=[];
  var c=new Date(s.getTime());
  while(c<=e){
    tot++;var ct=_cCat(c),h=_cHol(c);
    if(ct==='sat')sa++;else if(ct==='fri')fr++;
    else if(ct==='hol'){if(h&&h.t==='i')na++;else hl++;}
    else if(ct==='chol')ch++;else wk++;
    if(h&&_CS.hols)evs.push({d:new Date(c.getTime()),n:h.n,t:h.t});
    c.setDate(c.getDate()+1);
  }
  return{tot:tot,wk:wk,fr:fr,sa:sa,hl:hl,ch:ch,na:na,evs:evs};
}

/* ═══ RENDER ═══ */
function calRender(){
  var mw=document.getElementById('cal-mw'),panel=document.getElementById('cal-panel');
  if(!mw||!panel)return;
  panel.classList.remove('cal-sz-s','cal-sz-m','cal-sz-l');
  panel.classList.add('cal-sz-'+_CS.sz);
  panel.style.width=Math.min(_CS.n*_CSZW[_CS.sz]+4,window.innerWidth-48)+'px';
  var lbl=document.getElementById('cal-lbl');
  if(lbl){
    if(_CS.n===1){lbl.textContent=_CHM[_CS.m]+' '+_CS.y;}
    else{var ei=_CS.m+_CS.n-1,ey=_CS.y+Math.floor(ei/12),em=((ei%12)+12)%12;lbl.textContent=_CHM[_CS.m]+' '+_CS.y+' — '+_CHM[em]+' '+ey;}
  }
  ['s','m','l'].forEach(function(v){var b=document.getElementById('cal-ws'+v.toUpperCase());if(b)b.classList.toggle('cal-on',_CS.sz===v);});
  [1,2,3].forEach(function(v){var b=document.getElementById('cal-n'+v);if(b)b.classList.toggle('cal-on',_CS.n===v);});
  var hb=document.getElementById('cal-hbtn');if(hb)hb.classList.toggle('cal-on',_CS.hols);
  var html='';
  for(var i=0;i<_CS.n;i++)html+=_cMonHtml(_CS.y,_CS.m+i);
  mw.innerHTML=html;
  _cApplyRange();
  _cLegend();
}

function _cMonHtml(year,month){
  var d0=new Date(year,month,1),y=d0.getFullYear(),m=d0.getMonth(),t=_cToday();
  var html='<div class="cal-mon">';
  if(_CS.n>1)html+='<div class="cal-montit">'+_CHM[m]+' '+y+'</div>';
  html+='<div class="cal-dhrow">';
  _CHD.forEach(function(dn,i){html+='<div class="cal-dh'+(i===5?' cal-fri':i===6?' cal-sat':'')+'">'+dn+'</div>';});
  html+='</div><div class="cal-grid">';
  var fd=new Date(y,m,1).getDay();
  for(var e=0;e<fd;e++)html+='<div class="cal-day cal-emp"></div>';
  var dim=new Date(y,m+1,0).getDate();
  for(var day=1;day<=dim;day++){
    var c=new Date(y,m,day),dw=c.getDay(),isT=_cDK(c)===_cDK(t),hl=_cHol(c),ts=c.getTime();
    var cls='cal-day';
    if(dw===6)cls+=' cal-sat';else if(dw===5)cls+=' cal-fri';else cls+=' cal-wday';
    if(_CS.hols&&hl)cls+=' hol-'+hl.t;
    if(isT)cls+=' cal-today';
    var tt=hl?_cE(hl.n):'';
    /* ── onclick: click or end-of-drag ── */
    /* ── onmousedown: start drag        ── */
    /* ── onmouseenter: drag tracking    ── */
    html+='<div class="'+cls+'" data-ts="'+ts+'" title="'+tt+'"'
      +' onclick="calDayClick('+ts+',event)"'
      +' onmousedown="calDayMD('+ts+')"'
      +' onmouseenter="calDayME('+ts+')"'
      +'>'+day+'</div>';
  }
  return html+'</div></div>';
}

function _cApplyRange(){
  var s=_CS.rs===null?null:Math.min(_CS.rs,_CS.re!==null?_CS.re:_CS.rs);
  var e=_CS.rs===null?null:Math.max(_CS.rs,_CS.re!==null?_CS.re:_CS.rs);
  var cells=document.querySelectorAll('#cal-mw .cal-day[data-ts]');
  cells.forEach(function(cell){
    var ts=parseInt(cell.dataset.ts,10);
    cell.classList.remove('cal-inr','cal-rs','cal-re');
    if(s!==null&&ts>=s&&ts<=e){
      cell.classList.add('cal-inr');
      if(ts===s)cell.classList.add('cal-rs');
      if(ts===e)cell.classList.add('cal-re');
    }
  });
}

function _cLegend(){
  var el=document.getElementById('cal-leg');if(!el)return;
  var items=[{c:'var(--accent)',l:'היום'},{c:'rgba(34,197,94,.6)',l:'יום עסקים'},{c:'#f59e0b',l:'שישי'},{c:'#ef4444',l:'שבת'},{c:'rgba(91,141,238,.5)',l:'טווח'}];
  if(_CS.hols)items=items.concat([{c:'#f59e0b',l:'חג ★'},{c:'#d97706',l:'חוה״מ ◐'},{c:'#06b6d4',l:'עצמאות'},{c:'#8b5cf6',l:'זיכרון'}]);
  el.innerHTML=items.map(function(i){return'<div class="cal-lgi"><div class="cal-lgd" style="background:'+i.c+'"></div><span>'+i.l+'</span></div>';}).join('');
}

/* ═══ DAY EVENT HANDLERS (called from inline HTML) ═══ */

/* mousedown — start drag tracking */
function calDayMD(ts){
  _CS.md=true;
  _CS.rs=ts;
  _CS.re=ts;
  _cApplyRange();
}

/* mouseenter — extend drag range */
function calDayME(ts){
  if(!_CS.md)return;
  _CS.re=ts;
  _cApplyRange();
}

/* onclick — fired after mousedown+mouseup on same element, OR after drag */
function calDayClick(ts,event){
  event.stopPropagation();
  _CS.md=false;
  /* If drag extended range (rs !== re), show range popup.
     Otherwise single-day popup. */
  if(_CS.rs===null||_CS.rs===_CS.re){
    _CS.rs=ts;_CS.re=ts;
  }
  _cShowPopup(event.clientX,event.clientY);
}

/* mouseup on document — reset drag flag, show popup if dragged across cells */
document.addEventListener('mouseup',function(e){
  if(!_CS.md)return;
  _CS.md=false;
  if(_CS.rs!==null&&_CS.rs!==_CS.re){
    /* drag ended outside a cell (no onclick will fire) */
    _cShowPopup(e.clientX,e.clientY);
  }
});

/* ═══ NAVIGATION ═══ */
function calToggle(){
  var panel=document.getElementById('cal-panel'),fab=document.getElementById('cal-fab');
  if(panel.classList.contains('cal-show')){panel.classList.remove('cal-show');fab.classList.remove('cal-open');calClosePopup();}
  else{calRender();panel.classList.add('cal-show');fab.classList.add('cal-open');}
}
function calNavMonth(d){_CS.m+=d;if(_CS.m>11){_CS.m=0;_CS.y++;}else if(_CS.m<0){_CS.m=11;_CS.y--;}calRender();}
function calNavYear(d){_CS.y+=d;calRender();}
function calGoToday(){var t=_cToday();_CS.y=t.getFullYear();_CS.m=t.getMonth();calRender();}
function calSetN(n){_CS.n=n;localStorage.setItem('cal_n',String(n));calRender();}
function calSetSz(sz){_CS.sz=sz;localStorage.setItem('cal_sz',sz);calRender();}
function calToggleHols(){_CS.hols=!_CS.hols;localStorage.setItem('cal_h',_CS.hols?'1':'0');calRender();}

/* ═══ POPUP ═══ */
function _cShowPopup(cx,cy){
  if(_CS.rs===null)return;
  var s=new Date(Math.min(_CS.rs,_CS.re!==null?_CS.re:_CS.rs));s.setHours(0,0,0,0);
  var e=new Date(Math.max(_CS.rs,_CS.re!==null?_CS.re:_CS.rs));e.setHours(0,0,0,0);
  var te=document.getElementById('cp-tit'),be=document.getElementById('cp-body');
  if(!te||!be)return;
  if(s.getTime()===e.getTime())_cPopSingle(s,te,be);
  else _cPopRange(s,e,te,be);
  var pop=document.getElementById('cal-pop');
  if(!pop)return;
  pop.style.display='block';
  var vw=window.innerWidth,vh=window.innerHeight;
  var pw=pop.offsetWidth||270,ph=pop.offsetHeight||220;
  var x=cx+14,y=cy-Math.round(ph/2);
  if(x+pw>vw-8)x=cx-pw-14;if(x<8)x=8;if(y<8)y=8;if(y+ph>vh-8)y=vh-ph-8;
  pop.style.left=x+'px';pop.style.top=y+'px';
}

function _cPopSingle(d,te,be){
  var t=_cToday();
  te.textContent='יום '+_CHF[d.getDay()]+', '+d.getDate()+' ב'+_CHM[d.getMonth()]+' '+d.getFullYear();
  var diff=d-t,tot=Math.round(Math.abs(diff)/86400000),dir=diff===0?'today':diff>0?'fut':'past',hl=_cHol(d);
  var html='';
  if(dir==='today'){
    html+='<div class="cp-badge" style="background:var(--accent-dim);color:var(--accent);border:1px solid rgba(91,141,238,.3);">📍 היום</div>';
  }else{
    var ip=dir==='past',col=ip?'#22c55e':'var(--accent)',bg=ip?'rgba(34,197,94,.1)':'var(--accent-dim)',br=ip?'rgba(34,197,94,.3)':'rgba(91,141,238,.3)';
    html+='<div class="cp-badge" style="background:'+bg+';color:'+col+';border:1px solid '+br+';">'+(ip?'⬅️ לפני ':'➡️ בעוד ')+_cBkd(tot)+'</div>';
    html+='<div class="cp-row cp-rb"><span class="ll">📅 סה"כ ימים</span><span class="vv">'+tot.toLocaleString('he-IL')+'</span></div>';
    var bs=ip?d:t,be2=ip?t:d,biz=0,cc=new Date(bs.getTime());
    while(cc<be2){if(_cCat(cc)==='work')biz++;cc.setDate(cc.getDate()+1);}
    html+='<div class="cp-row cp-rw"><span class="ll">💼 ימי עסקים'+(_CS.hols?' (ללא חגים)':'')+'</span><span class="vv">'+biz.toLocaleString('he-IL')+'</span></div>';
    html+='<div class="cp-row cp-rs"><span class="ll">⛔ שישי+שבת'+(_CS.hols?'+חגים':'')+'</span><span class="vv">'+(tot-biz).toLocaleString('he-IL')+'</span></div>';
    if(tot>=7){var w=Math.floor(tot/7),r=tot%7;html+='<div class="cp-bk">'+w+' שבועות'+(r?' ו-'+r+' ימים':'')+'&nbsp;•&nbsp;'+_cBkd(tot)+'</div>';}
  }
  if(hl&&_CS.hols){var hc=_CHCOL[hl.t]||'var(--text3)';html+='<div class="cp-row" style="background:'+hc+'18;border:1px solid '+hc+'40;"><span class="ll" style="color:'+hc+';">'+(_CHLBL[hl.t]||'')+'</span><span class="vv" style="color:'+hc+';">'+_cE(hl.n)+'</span></div>';}
  var isW=_cCat(d)==='work';
  // html+='<div class="cp-row '+(isW?'cp-rw':'cp-rs')+'"><span class="ll">יום בשבוע</span><span class="vv">'+_CHF[d.getDay()]+' '+(isW?'✅':'❌')+'</span></div>';
  be.innerHTML=html;
}

function _cPopRange(s,e,te,be){
  te.textContent='טווח נבחר';
  var tot=Math.round((e-s)/86400000)+1,st=_cStats(s.getTime(),e.getTime()),w=Math.floor(tot/7),r=tot%7;
  var html='<div class="cp-badge" style="background:var(--accent-dim);color:var(--accent);border:1px solid rgba(91,141,238,.3);">📅 '+_cFmt(s)+' — '+_cFmt(e)+'</div>';
  html+='<div class="cp-bk">'+tot+' ימים'+(w>0?' ('+w+' שבועות'+(r?' ו-'+r+' ימים':'')+')':'')+'</div>';
  html+='<div class="cp-row cp-rw"><span class="ll">💼 ימי עסקים'+(_CS.hols?' ללא חגים':'')+'</span><span class="vv">'+st.wk+'</span></div>';
  if(_CS.hols&&st.ch>0)html+='<div class="cp-row cp-rc"><span class="ll">📿 חול המועד</span><span class="vv">'+st.ch+'</span></div>';
  if(_CS.hols&&st.hl>0)html+='<div class="cp-row cp-rh"><span class="ll">✡ ימי חג</span><span class="vv">'+st.hl+'</span></div>';
  if(_CS.hols&&st.na>0)html+='<div class="cp-row cp-rn"><span class="ll">✦ עצמאות</span><span class="vv">'+st.na+'</span></div>';
  if(st.fr>0)html+='<div class="cp-row cp-rf"><span class="ll">🟡 שישי</span><span class="vv">'+st.fr+'</span></div>';
  if(st.sa>0)html+='<div class="cp-row cp-rs"><span class="ll">🔴 שבת</span><span class="vv">'+st.sa+'</span></div>';
  if(st.evs.length>0){
    html+='<div class="cp-evs"><div class="cp-evhd">📌 אירועים בטווח</div>';
    st.evs.forEach(function(ev){var col=_CHCOL[ev.t]||'var(--text3)';html+='<div class="cp-ev"><div class="cp-evd" style="background:'+col+'"></div><span class="cp-evdt">'+_cFmts(ev.d)+'</span><span class="cp-evn">'+_cE(ev.n)+'</span></div>';});
    html+='</div>';
  }else if(!_CS.hols)html+='<div style="font-size:10px;color:var(--text3);text-align:center;margin-top:6px;">הפעל ✡ חגים לחישוב מועדים</div>';
  be.innerHTML=html;
}

function calClosePopup(){
  var p=document.getElementById('cal-pop');
  if(p)p.style.display='none';
  _CS.rs=null;_CS.re=null;_CS.md=false;
  _cApplyRange();
}

/* ═══ DATE INPUT ═══ */
document.getElementById('cal-inp').addEventListener('keydown',function(e){
  if(e.key!=='Enter')return;
  e.preventDefault();
  var d=_cParseDate(this.value.trim());
  if(d){
    _CS.rs=d.getTime();_CS.re=_CS.rs;
    _CS.y=d.getFullYear();_CS.m=d.getMonth();
    calRender();
    var p=document.getElementById('cal-panel'),r=p.getBoundingClientRect();
    _cShowPopup(r.left+r.width/2,r.top+r.height/2);
    this.value='';this.style.color='';
  }else{
    this.style.color='var(--danger)';
    var inp=this;setTimeout(function(){inp.style.color='';},600);
  }
});
document.getElementById('cal-inp').addEventListener('input',function(){
  this.style.color='';
  if(this.value.replace(/\D/g,'').length===8)this.style.color=_cParseDate(this.value)?'#22c55e':'var(--danger)';
});
function _cParseDate(str){
  var c=str.replace(/[.\\/\- ]/g,'');if(c.length!==8)return null;
  var dd=parseInt(c.slice(0,2),10),mm=parseInt(c.slice(2,4),10)-1,yy=parseInt(c.slice(4,8),10);
  if(dd<1||dd>31||mm<0||mm>11||yy<1900||yy>2100)return null;
  var d=new Date(yy,mm,dd);if(d.getDate()!==dd||d.getMonth()!==mm)return null;
  d.setHours(0,0,0,0);return d;
}

/* ═══ CLOSE ON OUTSIDE CLICK ═══ */
document.addEventListener('click',function(e){
  if(_CS.md)return;
  var panel=document.getElementById('cal-panel'),fab=document.getElementById('cal-fab'),pop=document.getElementById('cal-pop');
  if(panel&&panel.classList.contains('cal-show')&&fab&&!panel.contains(e.target)&&!fab.contains(e.target)&&pop&&!pop.contains(e.target)){
    panel.classList.remove('cal-show');fab.classList.remove('cal-open');calClosePopup();
  }
  if(pop&&pop.style.display!=='none'&&!pop.contains(e.target)){
    var mw=document.getElementById('cal-mw');
    if(!mw||!mw.contains(e.target))calClosePopup();
  }
});
document.addEventListener('keydown',function(e){
  if(e.key!=='Escape')return;
  var pop=document.getElementById('cal-pop');
  if(pop&&pop.style.display!=='none'){calClosePopup();return;}
  var panel=document.getElementById('cal-panel');
  if(panel&&panel.classList.contains('cal-show')){panel.classList.remove('cal-show');document.getElementById('cal-fab').classList.remove('cal-open');}
});
</script>
