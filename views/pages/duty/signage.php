<?php
$base = rtrim(CFG['app']['url'], '/');
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>שילוט דיגיטלי – מוקד</title>
<link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root {
  --c1: #ff6b6b;   /* coral */
  --c2: #ffd93d;   /* yellow */
  --c3: #6bcb77;   /* mint */
  --c4: #4d96ff;   /* sky blue */
  --c5: #ff9f43;   /* orange */
  --c6: #a29bfe;   /* lavender */
  --bg:    #0d1117;
  --bg2:   #161b22;
  --bg3:   #1c2230;
  --border: rgba(255,255,255,0.08);
  --text:   #e8eaf0;
  --text2:  rgba(232,234,240,0.65);
  --text3:  rgba(232,234,240,0.35);
  --font:   'Rubik', sans-serif;
  --radius: 16px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  width: 100%; height: 100%;
  overflow: hidden;
  font-family: var(--font);
  background: var(--bg);
  color: var(--text);
}

/* ── animated gradient bg ── */
#bg-layer {
  position: fixed; inset: 0; z-index: 0;
  background: var(--bg);
}
#bg-layer::before {
  content: '';
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 55% 50% at 15% 20%, rgba(77,150,255,0.12) 0%, transparent 70%),
    radial-gradient(ellipse 45% 40% at 85% 80%, rgba(255,107,107,0.10) 0%, transparent 70%),
    radial-gradient(ellipse 50% 45% at 50% 50%, rgba(107,203,119,0.07) 0%, transparent 70%);
  animation: bgShift 20s ease-in-out infinite alternate;
}
@keyframes bgShift {
  0%   { opacity: 1; }
  50%  { opacity: 0.6; }
  100% { opacity: 1; }
}

/* bg-slide/orb — keep DOM, hide visually */
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
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  position: relative;
}
/* top color accent bar */
.card-accent {
  height: 3px;
  background: linear-gradient(90deg, var(--c4), var(--c3), var(--c2), var(--c1));
  background-size: 300% 100%;
  animation: accentMove 6s linear infinite;
}
@keyframes accentMove { 0%{background-position:0%} 100%{background-position:300%} }

.section-label {
  font-size: clamp(9px, 0.9vw, 11px);
  font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase;
  color: var(--text3);
  display: flex; align-items: center; gap: 6px;
  margin-bottom: clamp(4px, 0.6vh, 8px);
}

/* ── HEADER ── */
#header {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  padding: clamp(8px, 1vh, 14px) clamp(14px, 2vw, 28px);
  gap: 10px;
}

/* logo */
#logo-area {
  display: flex; align-items: center; gap: clamp(6px, 0.8vw, 12px);
}
.logo-pill {
  display: flex; align-items: center; gap: 8px;
  background: linear-gradient(135deg, rgba(77,150,255,0.2), rgba(162,155,254,0.2));
  border: 1px solid rgba(77,150,255,0.3);
  border-radius: 50px;
  padding: clamp(5px,0.6vh,9px) clamp(12px,1.4vw,20px);
}
.logo-pill i { font-size: clamp(18px, 2.2vw, 28px); color: var(--c4); }
.logo-pill span {
  font-size: clamp(14px, 1.6vw, 22px); font-weight: 800; letter-spacing: 1px;
  background: linear-gradient(90deg, var(--c4), var(--c6));
  -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}

/* clock */
#clock-area { text-align: center; }
#clock {
  font-size: clamp(52px, 7.5vw, 100px);
  font-weight: 800; letter-spacing: 3px; line-height: 1;
  background: linear-gradient(135deg, #fff 30%, rgba(255,255,255,0.7));
  -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
#clock-date {
  font-size: clamp(11px, 1.1vw, 15px); color: var(--text2); margin-top: 3px;
}
#clock-date-hebrew {
  font-size: clamp(10px, 0.9vw, 13px); color: var(--text3); margin-top: 2px;
}

/* day badge */
#today-badge {
  justify-self: end;
  background: linear-gradient(135deg, rgba(255,217,61,0.18), rgba(255,159,67,0.18));
  border: 1px solid rgba(255,217,61,0.3);
  border-radius: 14px;
  padding: clamp(6px,0.8vh,12px) clamp(14px,1.6vw,24px);
  text-align: center;
}
.badge-label {
  font-size: clamp(9px,0.8vw,11px); color: var(--text3); display: block;
  margin-bottom: 2px; letter-spacing: 1.5px; text-transform: uppercase;
}
#day-name {
  font-size: clamp(18px, 2.2vw, 28px); font-weight: 800; color: var(--c2);
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
  background: linear-gradient(135deg, var(--c4), var(--c6));
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 0 0 3px rgba(77,150,255,0.25), 0 4px 20px rgba(77,150,255,0.3);
}
.duty-avatar i { font-size: clamp(22px, 2.5vw, 34px); color: #fff; }

#duty-info { flex: 1; min-width: 0; }
#duty-name {
  font-size: clamp(20px, 2.8vw, 38px); font-weight: 800; line-height: 1.05;
  color: var(--text);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
#duty-dept {
  font-size: clamp(12px, 1.2vw, 17px); font-weight: 500;
  color: var(--c4); margin-top: 2px;
}
#duty-week { font-size: clamp(10px, 0.9vw, 13px); color: var(--text3); margin-top: 2px; }
#duty-status {
  display: none; margin-top: 4px; padding: 2px 10px; border-radius: 20px;
  font-size: clamp(10px,0.9vw,12px); font-weight: 600;
  background: rgba(77,150,255,0.2); border: 1px solid rgba(77,150,255,0.4); color: var(--c4);
}
#no-duty {
  font-size: clamp(14px, 1.4vw, 20px); color: var(--text3); padding: 8px 0; display: none;
}
#loading { display: flex; align-items: center; gap: 10px; color: var(--text3); font-size: clamp(12px,1.2vw,15px); }

/* guidance */
#guidance-inline {
  flex-shrink: 0;
  width: clamp(200px, 22vw, 340px);
  background: linear-gradient(135deg, rgba(255,159,67,0.1), rgba(255,107,107,0.08));
  border: 1px solid rgba(255,159,67,0.25);
  border-radius: 12px;
  padding: clamp(8px,1vh,14px) clamp(12px,1.2vw,18px);
  display: flex; flex-direction: column; justify-content: center;
}
#guidance-text {
  font-size: clamp(12px, 1.3vw, 17px); font-weight: 600;
  color: var(--c5); line-height: 1.5;
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
  background: rgba(255,255,255,0.06);
  overflow: hidden; margin-bottom: clamp(5px,0.7vh,9px); flex-shrink: 0;
}
#news-timer-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--c4), var(--c3));
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
  transition: background .15s;
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
  background: rgba(255,107,107,0.15); border: 1px solid rgba(255,107,107,0.3);
  color: var(--c1); margin-bottom: clamp(3px, 0.4vh, 5px);
}
.news-title {
  font-size: clamp(13px, 1.35vw, 18px);
  font-weight: 500; color: var(--text); line-height: 1.35;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* ── RIGHT COL ── */
#right-col { display: flex; flex-direction: column; gap: clamp(5px,0.8vh,10px); min-height: 0; }

/* ── WEATHER ── */
#weather-card {
  flex: 1; min-height: 0;
  padding: clamp(8px,1vh,14px) clamp(10px,1.2vw,16px);
  display: flex; flex-direction: column;
}
#weather-body {
  flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;
  gap: clamp(4px, 0.6vh, 8px);
}
#w-temp {
  font-size: clamp(40px, 5.5vw, 72px); font-weight: 800; line-height: 1;
  background: linear-gradient(135deg, var(--c2), var(--c5));
  -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
#w-icon { font-size: clamp(26px, 3vw, 42px); }
#w-desc { font-size: clamp(12px, 1.1vw, 15px); color: var(--text2); text-align: center; }
#w-details {
  display: flex; gap: clamp(8px, 1vw, 14px);
  font-size: clamp(10px, 0.9vw, 13px); color: var(--text3);
  flex-wrap: wrap; justify-content: center;
}
#w-details span { display: flex; align-items: center; gap: 4px; }
#w-loading { color: var(--text3); font-size: clamp(11px,1vw,13px); }

/* ── GIF CARD ── */
#fact-card {
  flex-shrink: 0;
  padding: 0;
  overflow: hidden;
  display: flex; flex-direction: column;
  height: clamp(130px, 18vh, 230px);
  max-height: clamp(130px, 18vh, 230px);
}
#gif-label {
  padding: clamp(5px,0.7vh,9px) clamp(10px,1.2vw,14px);
  font-size: clamp(9px,0.75vw,11px); font-weight: 700; letter-spacing: 1.5px;
  text-transform: uppercase; color: var(--text3);
  display: flex; align-items: center; gap: 6px;
  flex-shrink: 0;
}
#gif-wrap {
  flex: 1; min-height: 0;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden; position: relative;
  background: #000;
}
#gif-img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: none;
  transition: opacity 0.4s;
}
#gif-img.loaded { display: block; }
#gif-loading {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  color: var(--text3); font-size: clamp(11px,1vw,13px); gap: 8px;
}
#gif-timer-wrap { height: 3px; background: rgba(255,255,255,0.06); flex-shrink: 0; }
#gif-timer-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--c1), var(--c5), var(--c2));
  transform-origin: right;
}
/* keep old ids so nothing breaks */
#fact-loading, #fact-title, #fact-text, #fact-timer-wrap, #fact-timer-bar,
#fact-header, #fact-icon, #fact-category, #fact-source-link { display: none !important; }

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
  border: 2px solid rgba(255,255,255,0.08);
  border-top-color: var(--c4);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>

<div id="bg-layer">
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
    <div class="card-accent" style="position:absolute;top:0;left:0;right:0;height:3px;"></div>

    <div id="logo-area">
      <div class="logo-pill">
        <i class="bi bi-headset"></i>
        <span>מוקד נט</span>
      </div>
    </div>

    <div id="clock-area">
      <div id="clock">00:00:00</div>
      <div id="clock-date"></div>
      <div id="clock-date-hebrew"></div>
    </div>

    <div id="today-badge">
      <span class="badge-label">היום</span>
      <span id="day-name">—</span>
    </div>
  </div>

  <!-- ── DUTY BAR ── -->
  <div id="duty-bar" class="card">
    <div id="duty-section">
      <div class="duty-avatar" id="duty-avatar-wrap">
        <i class="bi bi-person-badge"></i>
      </div>
      <div id="duty-info" style="flex:1;min-width:0;">
        <div class="section-label">
          <i class="bi bi-calendar-week" style="color:var(--c4)"></i>
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
        <i class="bi bi-lightning-charge-fill" style="color:var(--c5)"></i>
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
        <i class="bi bi-newspaper" style="color:var(--c1)"></i>
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
          <i class="bi bi-cloud-sun" style="color:var(--c2)"></i>
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

      <!-- GIF -->
      <div id="fact-card" class="card">
        <div id="gif-label">
          <i class="bi bi-emoji-laughing" style="color:var(--c2)"></i>
          GIF של הרגע 😄
        </div>
        <div id="gif-wrap">
          <div id="gif-loading"><div class="spinner"></div> טוען...</div>
          <img id="gif-img" src="" alt="funny cat gif">
        </div>
        <div id="gif-timer-wrap"><div id="gif-timer-bar"></div></div>
        <!-- compat stubs -->
        <div id="fact-loading" style="display:none"></div>
        <div id="fact-title" style="display:none"></div>
        <div id="fact-text" style="display:none"></div>
        <div id="fact-timer-wrap" style="display:none"><div id="fact-timer-bar"></div></div>
        <div id="fact-header" style="display:none"></div>
        <span id="fact-icon" style="display:none"></span>
        <span id="fact-category" style="display:none"></span>
        <a id="fact-source-link" style="display:none"></a>
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
  'אינטרנט ותוכן': { icon: 'bi-globe',   color: '#4d96ff' },
  'תמיכה טכנית':   { icon: 'bi-tools',   color: '#6bcb77' },
  'שירות לקוחות':  { icon: 'bi-headset', color: '#a29bfe' },
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
    const raw=new Date().toLocaleDateString('he-IL-u-ca-hebrew',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
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
      const meta=DEPT_ICONS[s.department]||{icon:'bi-person-badge',color:'#4d96ff'};
      $('duty-avatar-wrap').innerHTML=`<i class="bi ${meta.icon}" style="font-size:clamp(22px,2.5vw,34px);color:#fff"></i>`;
      $('duty-avatar-wrap').style.background=`linear-gradient(135deg,${meta.color},${meta.color}99)`;
      $('duty-avatar-wrap').style.boxShadow=`0 0 0 3px ${meta.color}33,0 4px 20px ${meta.color}44`;
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
    $('loading').innerHTML='<span style="color:var(--c1)">שגיאה בטעינת נתונים</span>';
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

async function loadNews() {
  try {
    const r=await fetch('https://rss.walla.co.il/feed/22');
    const text=await r.text();
    const xml=new DOMParser().parseFromString(text,'text/xml');
    newsItems=Array.from(xml.getElementsByTagName('item')).slice(0,40).map(item=>{
      const enc=item.getElementsByTagName('enclosure')[0];
      const image=enc?enc.getAttribute('url'):null;
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

/* ── GIF — cataas.com (ללא API key) ── */
const GIF_INTERVAL = 12;
let gifTimerRaf = null, gifTimerStart = null;

function startGifTimer() {
  const bar = $('gif-timer-bar');
  if (!bar) return;
  if (gifTimerRaf) cancelAnimationFrame(gifTimerRaf);
  gifTimerStart = performance.now();
  const dur = GIF_INTERVAL * 1000;
  function anim(now) {
    const p = Math.min((now - gifTimerStart) / dur, 1);
    bar.style.transform = `scaleX(${1 - p})`;
    if (p < 1) gifTimerRaf = requestAnimationFrame(anim);
  }
  gifTimerRaf = requestAnimationFrame(anim);
}

/* GIF URLs ישירים — ללא API key, מגוון קטגוריות */
const GIF_POOL = [
  /* חתולים */
  'https://cataas.com/cat/gif',
  /* כלבים — random.dog מחזיר gif ישיר */
  'https://random.dog/woof.json',
  /* פנדות — עדכני ב-giphy public */
  'https://media.giphy.com/media/ICOgUNjpvO0PC/giphy.gif',
  'https://media.giphy.com/media/11sBLVxNs7v6WA/giphy.gif',
  /* תינוקות מצחיקים */
  'https://media.giphy.com/media/3o7abKhOpu0NwenH3O/giphy.gif',
  /* חיות שונות */
  'https://media.giphy.com/media/MDJ9IbxxvDUQM/giphy.gif',
  'https://media.giphy.com/media/l3q2K5jinAlChoCLS/giphy.gif',
  /* כלבים נוספים */
  'https://media.giphy.com/media/mCRJDo24UvJMA/giphy.gif',
  /* קופים */
  'https://media.giphy.com/media/5i7umUqAOYYEw/giphy.gif',
  /* ציפורים */
  'https://media.giphy.com/media/3o7abAHdYvZdBNnGZq/giphy.gif',
];
let gifPoolIdx = 0;

async function resolveGifUrl() {
  const entry = GIF_POOL[gifPoolIdx % GIF_POOL.length];
  gifPoolIdx++;
  if (entry.includes('random.dog/woof.json')) {
    try {
      const d = await fetch(entry).then(r => r.json());
      if (d.url && d.url.match(/\.gif(\?|$)/i)) return d.url;
      return resolveGifUrl(); /* דלג אם mp4 */
    } catch { return resolveGifUrl(); }
  }
  if (entry.includes('cataas.com')) {
    return `${entry}?t=${Date.now()}`;
  }
  return entry;
}

async function loadGif() {
  const img = $('gif-img');
  const loading = $('gif-loading');
  if (!img) return;
  try {
    const url = await resolveGifUrl();
    if (!url) { setTimeout(loadGif, 1000); return; }
    const finalUrl = url.includes('?') ? url : url + `?t=${Date.now()}`;
    img.classList.remove('loaded');
    if (loading) loading.style.display = 'flex';
    const tmp = new Image();
    tmp.onload = () => {
      img.src = tmp.src;
      img.classList.add('loaded');
      if (loading) loading.style.display = 'none';
      startGifTimer();
    };
    tmp.onerror = () => { setTimeout(loadGif, 3000); };
    tmp.src = finalUrl;
  } catch { setTimeout(loadGif, 3000); }
}
loadGif();
setInterval(loadGif, GIF_INTERVAL * 1000);
</script>
</body>
</html>
