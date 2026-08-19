<?php
$base = rtrim(CFG['app']['url'], '/');
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>שילוט דיגיטלי – מוקד</title>
<link href="https://fonts.googleapis.com/css2?family=Assistant:wght@300;400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root {
  --accent: #007aff;      /* single HIG-style accent (system blue) */
  --accent-soft: rgba(0,122,255,0.10);
  --accent-border: rgba(0,122,255,0.24);
  --warn: #c2410c;        /* semantic — used only for the guidance callout */
  --warn-soft: rgba(255,159,10,0.12);
  --warn-border: rgba(255,159,10,0.30);
  --danger: #d70015;

  --bg:     #f2f2f5;
  --bg2:    rgba(255,255,255,0.66);   /* translucent card surface — lets blobs show through */
  --bg3:    rgba(255,255,255,0.55);
  --border: rgba(0,0,0,0.07);
  --text:   #1c1c1e;
  --text2:  rgba(28,28,30,0.65);
  --text3:  rgba(28,28,30,0.42);
  --font:   'Assistant', sans-serif;
  --radius: 18px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  width: 100%; height: 100%;
  overflow: hidden;
  font-family: var(--font);
  background: var(--bg);
  color: var(--text);
}

/* ── Ambient animated background ── */
/* soft, blurred color blobs drifting slowly. Cards are translucent + backdrop-blurred
   so the color glow reads through them (frosted-glass, HIG-style), never behind opaque fills. */
#bg-layer {
  position: fixed; inset: 0; z-index: 0;
  background: var(--bg);
  overflow: hidden;
  animation: hueDrift 60s linear infinite;
}
#bg-layer .blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(70px);
  opacity: 0.8;
  will-change: transform;
}
#bg-layer .blob-1 {
  width: 48vw; height: 48vw;
  top: -16vw; right: -12vw;
  background: radial-gradient(circle, rgba(0,122,255,0.58) 0%, transparent 72%);
  animation: drift1 22s ease-in-out infinite;
}
#bg-layer .blob-2 {
  width: 42vw; height: 42vw;
  bottom: -18vw; left: -10vw;
  background: radial-gradient(circle, rgba(191,90,242,0.46) 0%, transparent 72%);
  animation: drift2 26s ease-in-out infinite;
}
#bg-layer .blob-3 {
  width: 36vw; height: 36vw;
  bottom: 8vh; right: 20vw;
  background: radial-gradient(circle, rgba(52,199,89,0.42) 0%, transparent 72%);
  animation: drift3 30s ease-in-out infinite;
}
#bg-layer .blob-4 {
  width: 30vw; height: 30vw;
  top: 20vh; left: 22vw;
  background: radial-gradient(circle, rgba(255,159,10,0.34) 0%, transparent 72%);
  animation: drift4 24s ease-in-out infinite;
}
@keyframes drift1 {
  0%   { transform: translate(0, 0) scale(1); }
  33%  { transform: translate(-8vw, 6vh) scale(1.15); }
  66%  { transform: translate(-3vw, 10vh) scale(0.95); }
  100% { transform: translate(0, 0) scale(1); }
}
@keyframes drift2 {
  0%   { transform: translate(0, 0) scale(1); }
  33%  { transform: translate(7vw, -8vh) scale(1.12); }
  66%  { transform: translate(3vw, -3vh) scale(0.9); }
  100% { transform: translate(0, 0) scale(1); }
}
@keyframes drift3 {
  0%   { transform: translate(0, 0) scale(1); }
  33%  { transform: translate(-6vw, -6vh) scale(0.88); }
  66%  { transform: translate(4vw, 4vh) scale(1.1); }
  100% { transform: translate(0, 0) scale(1); }
}
@keyframes drift4 {
  0%   { transform: translate(0, 0) scale(1); }
  33%  { transform: translate(6vw, 5vh) scale(1.18); }
  66%  { transform: translate(-5vw, -4vh) scale(0.9); }
  100% { transform: translate(0, 0) scale(1); }
}
@keyframes hueDrift {
  0%   { filter: hue-rotate(0deg); }
  100% { filter: hue-rotate(360deg); }
}
@media (prefers-reduced-motion: reduce) {
  #bg-layer, #bg-layer .blob { animation: none; }
}

/* bg-slide/orb — keep DOM, hide visually (legacy JS compat) */
.bg-slide, .orb { display: none !important; }

/* ── App grid — fills viewport, scales with it ── */
#app {
  position: relative; z-index: 1;
  display: grid;
  grid-template-rows: auto auto 1fr auto;
  width: 100vw; height: 100vh;
  padding: clamp(6px, 1vh, 14px) clamp(8px, 1.2vw, 18px);
  gap: clamp(5px, 0.8vh, 10px);
}

/* ── Card base ── */
.card {
  background: var(--bg2);
  backdrop-filter: blur(28px) saturate(160%);
  -webkit-backdrop-filter: blur(28px) saturate(160%);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  position: relative;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}

.section-label {
  font-size: clamp(9px, 0.9vw, 11px);
  font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase;
  color: var(--text3);
  display: flex; align-items: center; gap: 6px;
  margin-bottom: clamp(4px, 0.6vh, 8px);
}
.section-label i { color: var(--text3); font-size: 1em; }

/* ── HEADER ── */
/* explicit left/center/right slots — independent of DOM order or RTL flow */
#header {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  grid-template-areas: "left center right";
  align-items: center;
  padding: clamp(8px, 1vh, 14px) clamp(14px, 2vw, 28px);
  gap: 10px;
}
/* right slot — Gregorian date badge, mirrors the left Hebrew badge */

#logo-area { grid-area: left; display: flex; align-items: center; gap: clamp(6px, 0.8vw, 12px); }
.logo-pill {
  display: flex; align-items: center; gap: 8px;
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: 50px;
  padding: clamp(5px,0.6vh,9px) clamp(12px,1.4vw,20px);
}
.logo-pill i { font-size: clamp(16px, 1.9vw, 24px); color: var(--accent); }
.logo-pill span {
  font-size: clamp(13px, 1.5vw, 19px); font-weight: 700; letter-spacing: 0.2px;
  color: var(--text);
}

/* clock */
#clock-area { grid-area: center; text-align: center; }
#clock {
  font-family: 'Outfit', var(--font);
  font-size: clamp(72px, 10.5vw, 148px);
  font-weight: 800; letter-spacing: 2px; line-height: 1;
  color: var(--text);
  font-variant-numeric: tabular-nums;
  font-feature-settings: "tnum" 1;
}
/* date badges — left (Hebrew) + right (Gregorian), identical bold styling
   with a slow-drifting accent-tinted gradient behind them */
@keyframes badgeGlow {
  0%   { background-position: 0% 50%; }
  50%  { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
#hebrew-date-badge,
#clock-date {
  background: linear-gradient(120deg, var(--accent-soft), var(--bg3) 45%, var(--accent-soft) 100%);
  background-size: 220% 220%;
  animation: badgeGlow 8s ease-in-out infinite;
  border: 1px solid var(--accent-border);
  border-radius: 16px;
  padding: clamp(10px,1.2vh,16px) clamp(18px,2vw,32px);
  text-align: center;
  display: flex; flex-direction: column; gap: 4px;
  box-shadow: 0 2px 10px rgba(0,122,255,0.08);
}
#hebrew-date-badge { grid-area: left; justify-self: start; }
#clock-date { grid-area: right; justify-self: end; justify-content: center; }

#day-name {
  font-size: clamp(22px, 2.6vw, 32px); font-weight: 800; color: var(--text);
}
#clock-date-hebrew,
#clock-date {
  font-size: clamp(20px, 2.2vw, 28px); font-weight: 800; color: var(--accent);
}

/* ── DUTY BAR ── */
#duty-bar {
  display: flex; align-items: stretch; gap: clamp(10px, 1.2vw, 20px);
  padding: clamp(8px, 1vh, 14px) clamp(14px, 2vw, 28px);
}

#duty-section { flex: 1; min-width: 0; display: flex; align-items: center; gap: clamp(10px,1.2vw,20px); }

.duty-avatar {
  width: clamp(44px, 5vw, 68px); height: clamp(44px, 5vw, 68px);
  border-radius: 50%;
  background: var(--accent-soft);
  border: 1px solid var(--accent-border);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.duty-avatar i { font-size: clamp(20px, 2.3vw, 30px); color: var(--accent); }

#duty-info { flex: 1; min-width: 0; }
#duty-name {
  font-size: clamp(20px, 2.8vw, 38px); font-weight: 700; line-height: 1.05;
  color: var(--text);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
#duty-dept {
  font-size: clamp(12px, 1.2vw, 17px); font-weight: 500;
  color: var(--accent); margin-top: 3px;
}
#duty-week { font-size: clamp(10px, 0.9vw, 13px); color: var(--text3); margin-top: 2px; }
#duty-status {
  display: none; margin-top: 4px; padding: 2px 10px; border-radius: 20px;
  font-size: clamp(10px,0.9vw,12px); font-weight: 600;
  background: var(--accent-soft); border: 1px solid var(--accent-border); color: var(--accent);
}
#no-duty {
  font-size: clamp(14px, 1.4vw, 20px); color: var(--text3); padding: 8px 0; display: none;
}
#loading { display: flex; align-items: center; gap: 10px; color: var(--text3); font-size: clamp(12px,1.2vw,15px); }

/* guidance */
#guidance-inline {
  flex-shrink: 0;
  width: clamp(200px, 22vw, 340px);
  background: var(--warn-soft);
  border: 1px solid var(--warn-border);
  border-radius: 14px;
  padding: clamp(8px,1vh,14px) clamp(12px,1.2vw,18px);
  display: flex; flex-direction: column; justify-content: center;
}
#guidance-text {
  font-size: clamp(12px, 1.3vw, 17px); font-weight: 600;
  color: var(--warn); line-height: 1.5;
}
#guidance-empty { font-size: clamp(11px,0.9vw,13px); color: var(--text3); display: none; }

/* ── BODY ── */
#body {
  display: grid;
  grid-template-columns: 1fr clamp(220px, 22vw, 320px);
  gap: clamp(5px, 0.8vh, 10px);
  min-height: 0;
}

/* ── NEWS ── */
#news-card {
  min-height: 0; display: flex; flex-direction: column;
  padding: clamp(8px,1vh,14px) clamp(10px,1.2vw,16px);
}

#news-timer-wrap {
  height: 3px; border-radius: 3px;
  background: rgba(0,0,0,0.06);
  overflow: hidden; margin-bottom: clamp(5px,0.7vh,9px); flex-shrink: 0;
}
#news-timer-bar {
  height: 100%;
  background: var(--accent);
  width: 100%; transform-origin: right; border-radius: 3px;
}

#news-list { flex: 1; min-height: 0; overflow: hidden; }
#news-track { display: flex; flex-direction: column; gap: clamp(4px, 0.55vh, 7px); }
#news-track.slide-out { animation: slideOut 0.3s ease-in forwards; }
#news-track.slide-in  { animation: slideIn  0.35s ease-out forwards; }
@keyframes slideOut { from{opacity:1;transform:translateY(0)} to{opacity:0;transform:translateY(-14px)} }
@keyframes slideIn  { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }

.news-item {
  display: flex; align-items: center; gap: clamp(8px, 0.9vw, 13px);
  padding: clamp(6px,0.7vh,10px) clamp(9px,1vw,13px);
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: 12px;
  flex-shrink: 0; overflow: hidden;
}
.news-item img {
  width: clamp(60px, 6.5vw, 88px);
  height: clamp(40px, 4.3vw, 58px);
  border-radius: 8px; object-fit: cover; flex-shrink: 0;
  background: var(--bg3);
}
.news-item-text { flex: 1; min-width: 0; }
.news-time-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: clamp(9px, 0.75vw, 11px); font-weight: 600;
  padding: 1px 8px; border-radius: 20px;
  background: var(--bg2); border: 1px solid var(--border);
  color: var(--text3); margin-bottom: clamp(3px, 0.4vh, 5px);
}
.news-title {
  font-size: clamp(13px, 1.35vw, 18px);
  font-weight: 500; color: var(--text); line-height: 1.35;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* ── RIGHT COL ── */
#right-col { display: flex; flex-direction: column; gap: clamp(5px,0.8vh,10px); min-height: 0; }

/* ── WEATHER — now fills the entire right column ── */
#weather-card {
  flex: 1; min-height: 0;
  padding: clamp(8px,1vh,14px) clamp(10px,1.2vw,16px);
  display: flex; flex-direction: column;
}
#weather-body {
  flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;
  gap: clamp(6px, 1vh, 12px);
}
#w-temp {
  font-size: clamp(48px, 6.5vw, 88px); font-weight: 700; line-height: 1;
  color: var(--text);
  font-variant-numeric: tabular-nums;
}
#w-icon { font-size: clamp(30px, 3.6vw, 50px); }
#w-desc { font-size: clamp(13px, 1.2vw, 17px); color: var(--text2); text-align: center; }
#w-details {
  display: flex; gap: clamp(10px, 1.2vw, 18px);
  font-size: clamp(11px, 1vw, 14px); color: var(--text3);
  flex-wrap: wrap; justify-content: center;
  margin-top: clamp(4px, 0.8vh, 10px);
}
#w-details span { display: flex; align-items: center; gap: 4px; }
#w-loading { color: var(--text3); font-size: clamp(11px,1vw,13px); }

/* ── FOOTER ── */
#footer {
  display: flex; justify-content: space-between; align-items: center;
  padding: clamp(3px,0.5vh,6px) clamp(10px,1.2vw,18px);
  border-top: 1px solid var(--border);
}
#footer .footer-brand { font-size: clamp(9px,0.8vw,12px); color: var(--text3); }
#footer .footer-update { font-size: clamp(9px,0.8vw,12px); color: var(--text3); }
#update-time { color: var(--text2); }

/* ── Spinner ── */
.spinner {
  width: clamp(14px,1.4vw,20px); height: clamp(14px,1.4vw,20px);
  border: 2px solid rgba(0,0,0,0.08);
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>

<div id="bg-layer">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
  <div class="blob blob-4"></div>
  <!-- keep DOM elements for JS compat -->
  <div class="bg-slide active"></div>
  <div class="bg-slide"></div><div class="bg-slide"></div>
  <div class="bg-slide"></div><div class="bg-slide"></div>
</div>
<div class="orb orb-1"></div><div class="orb orb-2"></div>
<div class="orb orb-3"></div><div class="orb orb-4"></div>

<div id="app">

  <!-- ── HEADER ── -->
  <div id="header" class="card">

    <div id="hebrew-date-badge">
      <span id="day-name">—</span>
      <span id="clock-date-hebrew">—</span>
    </div>

    <div id="clock-area">
      <div id="clock">00:00:00</div>
    </div>

    <div id="clock-date"></div>
  </div>

  <!-- ── DUTY BAR ── -->
  <div id="duty-bar" class="card">
    <div id="duty-section">
      <div class="duty-avatar" id="duty-avatar-wrap">
        <i class="bi bi-person-badge"></i>
      </div>
      <div id="duty-info" style="flex:1;min-width:0;">
        <div class="section-label">
          <i class="bi bi-calendar-week"></i>
          תורנות השבוע
        </div>
        <div id="loading"><div class="spinner"></div> טוען...</div>
        <div id="duty-display" style="display:none">
          <div id="duty-name">—</div>
          <div id="duty-dept"></div>
          <div id="duty-week"></div>
          <div id="duty-status"></div>
        </div>
        <div id="no-duty">אין תורנות מוגדרת לשבוע זה</div>
      </div>
    </div>

    <div id="guidance-inline">
      <div class="section-label">
        <i class="bi bi-lightning-charge-fill"></i>
        הנחיית היום
      </div>
      <div id="guidance-text"></div>
      <div id="guidance-empty">אין הנחיה מיוחדת להיום</div>
    </div>
  </div>

  <!-- ── BODY ── -->
  <div id="body">

    <!-- News -->
    <div id="news-card" class="card">
      <div class="section-label" style="margin-bottom:clamp(4px,0.5vh,7px)">
        <i class="bi bi-newspaper"></i>
        חדשות
        <span id="news-counter" style="color:var(--text3);font-weight:400"></span>
      </div>
      <div id="news-timer-wrap"><div id="news-timer-bar"></div></div>
      <div id="news-list"><div id="news-track"></div></div>
    </div>

    <!-- Right col -->
    <div id="right-col">

      <!-- Weather -->
      <div id="weather-card" class="card">
        <div class="section-label">
          <i class="bi bi-cloud-sun"></i>
          מזג אוויר · חדרה
        </div>
        <div id="weather-body">
          <div id="w-loading"><div class="spinner"></div> טוען...</div>
          <div id="w-icon" style="display:none"></div>
          <div id="w-temp" style="display:none"></div>
          <div id="w-desc" style="display:none"></div>
          <div id="w-details" style="display:none"></div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── FOOTER ── -->
  <div id="footer">
    <span class="footer-brand">שילוט דיגיטלי · מוקד נט <?= date('Y') ?></span>
    <span class="footer-update">עדכון אחרון: <span id="update-time">—</span></span>
  </div>

</div>

<script>
const BASE    = '<?= $base ?>';
const DAYS_HE = ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];
const DEPT_ICONS = {
  'אינטרנט ותוכן': { icon: 'bi-globe' },
  'תמיכה טכנית':   { icon: 'bi-tools' },
  'שירות לקוחות':  { icon: 'bi-headset' },
};
const NEWS_INTERVAL = 18;
const BG_INTERVAL   = 22;

/* bg-slide compat */
(function () {
  const slides = document.querySelectorAll('.bg-slide');
  if (!slides.length) return;
  let c = 0;
  setInterval(() => {
    slides[c].classList.remove('active');
    c = (c + 1) % slides.length;
    slides[c].classList.add('active');
  }, BG_INTERVAL * 1000);
})();

/* ── Helpers ── */
function $(id) { return document.getElementById(id); }

/* ── Hebrew date ── */
function toHebrewNumerals(n) {
  const ones=['','א','ב','ג','ד','ה','ו','ז','ח','ט'];
  const tens=['','י','כ','ל','מ','נ','ס','ע','פ','צ'];
  const hundreds=['','ק','ר','ש','ת','תק','תר','תש','תת','תתק'];
  const special={15:'ט״ו',16:'ט״ז'};
  if(special[n]) return special[n];
  const h=Math.floor(n/100); n%=100;
  const t=Math.floor(n/10);  n%=10;
  let r=(hundreds[h]||'')+(tens[t]||'')+(ones[n]||'');
  if(r.length===1) return r+'׳';
  return r.slice(0,-1)+'״'+r.slice(-1);
}
function getHebrewDate() {
  try {
    const raw=new Date().toLocaleDateString('he-IL-u-ca-hebrew',{day:'numeric',month:'long',year:'numeric'});
    return raw.replace(/\d+/g,m=>toHebrewNumerals(parseInt(m,10)));
  } catch { return ''; }
}

/* ── Clock ── */
let lastHebKey='';
function tick() {
  const now=new Date();
  const hh=String(now.getHours()).padStart(2,'0');
  const mm=String(now.getMinutes()).padStart(2,'0');
  const ss=String(now.getSeconds()).padStart(2,'0');
  $('clock').textContent=`${hh}:${mm}:${ss}`;
  const dd=String(now.getDate()).padStart(2,'0');
  const mo=String(now.getMonth()+1).padStart(2,'0');
  $('clock-date').textContent=`${dd}/${mo}/${now.getFullYear()}`;
  $('day-name').textContent=DAYS_HE[now.getDay()];
  const k=`${now.getDate()}-${now.getMinutes()}`;
  if(k!==lastHebKey){ $('clock-date-hebrew').textContent=getHebrewDate(); lastHebKey=k; }
}
tick(); setInterval(tick,1000);

/* ── Duty ── */
function fmtDateRange(str) {
  if(!str) return '';
  const d=new Date(str), e=new Date(d); e.setDate(d.getDate()+6);
  const f=dt=>`${String(dt.getDate()).padStart(2,'0')}/${String(dt.getMonth()+1).padStart(2,'0')}/${dt.getFullYear()}`;
  return `${f(d)} – ${f(e)}`;
}
async function loadDuty() {
  try {
    const r=await fetch(BASE+'/api/duty/current');
    const data=await r.json();
    $('loading').style.display='none';
    if(data.schedule) {
      const s=data.schedule;
      const meta=DEPT_ICONS[s.department]||{icon:'bi-person-badge'};
      $('duty-avatar-wrap').innerHTML=`<i class="bi ${meta.icon}"></i>`;
      $('duty-name').textContent=s.rep_name||'—';
      $('duty-dept').textContent=s.department||'';
      $('duty-week').textContent=fmtDateRange(data.week_start);
      const st=$('duty-status');
      if(s.status&&s.status!=='active'){ st.textContent=s.status; st.style.display='inline-block'; }
      else st.style.display='none';
      $('duty-display').style.display='block';
    } else {
      $('no-duty').style.display='block';
    }
    const g=data.today_guidance;
    if(g&&g.trim()) {
      $('guidance-text').textContent=g; $('guidance-text').style.display='';
      $('guidance-empty').style.display='none';
    } else {
      $('guidance-text').style.display='none'; $('guidance-empty').style.display='block';
    }
    const now=new Date();
    $('update-time').textContent=`${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
  } catch {
    $('loading').innerHTML='<span style="color:var(--danger)">שגיאה בטעינת נתונים</span>';
  }
}
loadDuty(); setInterval(loadDuty,5*60*1000);

/* ── News ── */
let newsItems=[], newsPage=0, newsTimerRaf=null, newsTimerStart=null;
let NEWS_PER_PAGE=6; /* will be recalculated */

function calcNewsPerPage() {
  const list=$('news-list');
  if(!list) return;
  const h=list.getBoundingClientRect().height;
  /* each item ~55px on avg; clamp between 5 and 12 */
  NEWS_PER_PAGE=Math.max(5,Math.min(12,Math.floor(h/58)));
}

function getRelativeTime(dateStr) {
  const diff=Math.floor((Date.now()-new Date(dateStr))/60000);
  if(diff<1) return 'ממש עכשיו';
  if(diff===1) return 'לפני דקה';
  if(diff<60) return `לפני ${diff} דקות`;
  const h=Math.floor(diff/60);
  if(diff<1440) return h===1?'לפני שעה':`לפני ${h} שעות`;
  const d=Math.floor(diff/1440);
  return d===1?'אתמול':`לפני ${d} ימים`;
}

function renderNews() {
  if(!newsItems.length) return;
  calcNewsPerPage();
  const total=Math.ceil(newsItems.length/NEWS_PER_PAGE);
  const slice=newsItems.slice(newsPage*NEWS_PER_PAGE,(newsPage+1)*NEWS_PER_PAGE);
  const track=$('news-track');
  track.classList.remove('slide-in'); track.classList.add('slide-out');
  setTimeout(()=>{
    track.innerHTML='';
    slice.forEach(item=>{
      const div=document.createElement('div');
      div.className='news-item';
      const imgHtml=item.image
        ?`<img src="${item.image}" alt="" loading="lazy" onerror="this.style.display='none'">`:'';
      div.innerHTML=`${imgHtml}
        <div class="news-item-text">
          <span class="news-time-badge"><i class="bi bi-clock"></i>${item.time}</span>
          <div class="news-title">${item.title}</div>
        </div>`;
      track.appendChild(div);
    });
    track.classList.remove('slide-out'); track.classList.add('slide-in');
    $('news-counter').textContent=`${newsPage+1}/${total}`;
    newsPage=(newsPage+1)>=total?0:newsPage+1;
    startNewsTimer();
  },320);
}

function startNewsTimer() {
  const bar=$('news-timer-bar');
  if(newsTimerRaf) cancelAnimationFrame(newsTimerRaf);
  newsTimerStart=performance.now();
  const duration=NEWS_INTERVAL*1000;
  function animate(now){
    const p=Math.min((now-newsTimerStart)/duration,1);
    bar.style.transform=`scaleX(${1-p})`;
    if(p<1) newsTimerRaf=requestAnimationFrame(animate);
  }
  newsTimerRaf=requestAnimationFrame(animate);
}

/* pull the most relevant image for an RSS <item>, trying the common
   image-carrying fields in order of reliability before giving up */
function extractItemImage(item) {
  const enc = item.getElementsByTagName('enclosure')[0];
  if (enc) {
    const type = enc.getAttribute('type') || '';
    const url = enc.getAttribute('url');
    if (url && (!type || type.startsWith('image'))) return url;
  }
  const media = item.getElementsByTagNameNS('*','content')[0]
    || item.getElementsByTagNameNS('*','thumbnail')[0];
  if (media) {
    const url = media.getAttribute('url');
    if (url) return url;
  }
  const htmlFields = ['description','encoded'];
  for (const tag of htmlFields) {
    const el = item.getElementsByTagNameNS('*', tag)[0] || item.getElementsByTagName(tag)[0];
    if (!el || !el.textContent) continue;
    const m = el.textContent.match(/<img[^>]+src=["']([^"']+)["']/i);
    if (m) return m[1];
  }
  return null;
}

async function loadNews() {
  try {
    const r=await fetch('https://rss.walla.co.il/feed/22');
    const text=await r.text();
    const xml=new DOMParser().parseFromString(text,'text/xml');
    newsItems=Array.from(xml.getElementsByTagName('item')).slice(0,40).map(item=>{
      const image=extractItemImage(item);
      return {
        title:item.getElementsByTagName('title')[0]?.textContent||'',
        time:getRelativeTime(item.getElementsByTagName('pubDate')[0]?.textContent||''),
        image,
      };
    });
    newsPage=0; renderNews();
  } catch {
    $('news-track').innerHTML='<div style="color:var(--text3);padding:10px">לא ניתן לטעון חדשות</div>';
  }
}
loadNews();
setInterval(loadNews,2*60*1000);
setInterval(renderNews,NEWS_INTERVAL*1000);
window.addEventListener('resize',()=>{ calcNewsPerPage(); });

/* ── Weather (Open-Meteo — ללא API key) ── */
const WMO_CODES = {
  0:'☀️',1:'🌤️',2:'⛅',3:'☁️',
  45:'🌫️',48:'🌫️',
  51:'🌦️',53:'🌦️',55:'🌦️',
  61:'🌧️',63:'🌧️',65:'🌧️',
  71:'🌨️',73:'🌨️',75:'🌨️',
  80:'🌦️',81:'🌧️',82:'🌧️',
  95:'⛈️',96:'⛈️',99:'⛈️',
};
const WMO_DESC = {
  0:'שמיים בהירים',1:'בהיר בעיקר',2:'מעונן חלקית',3:'מעונן',
  45:'ערפל',48:'ערפל קפוא',
  51:'גשם קל',53:'גשם',55:'גשם חזק',
  61:'גשם קל',63:'גשם',65:'גשם חזק',
  71:'שלג קל',73:'שלג',75:'שלג כבד',
  80:'מקלחות גשם',81:'גשם',82:'גשם חזק',
  95:'סופת רעמים',96:'סופת רעמים',99:'סופת רעמים חזקה',
};
/* חדרה: 32.43°N, 34.92°E */
async function loadWeather() {
  try {
    const url='https://api.open-meteo.com/v1/forecast?latitude=32.43&longitude=34.92&current=temperature_2m,relative_humidity_2m,wind_speed_10m,weathercode&wind_speed_unit=kmh&timezone=Asia%2FJerusalem';
    const r=await fetch(url);
    const d=await r.json();
    const c=d.current;
    const code=c.weathercode??0;
    $('w-loading').style.display='none';
    $('w-icon').textContent=WMO_CODES[code]||'🌡️'; $('w-icon').style.display='block';
    $('w-temp').textContent=`${Math.round(c.temperature_2m)}°`; $('w-temp').style.display='block';
    $('w-desc').textContent=WMO_DESC[code]||''; $('w-desc').style.display='block';
    $('w-details').innerHTML=
      `<span><i class="bi bi-droplet"></i>${c.relative_humidity_2m}%</span>
       <span><i class="bi bi-wind"></i>${Math.round(c.wind_speed_10m)} קמ"ש</span>`;
    $('w-details').style.display='flex';
  } catch(e) {
    $('w-loading').textContent='אין נתוני מזג אוויר';
  }
}
loadWeather(); setInterval(loadWeather,10*60*1000);
</script>
</body>
</html>
