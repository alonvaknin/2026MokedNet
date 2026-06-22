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
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Rubik', Arial, sans-serif;
  height: 100vh; width: 100vw;
  overflow: hidden;
  display: flex; flex-direction: column;
  position: relative; color: #fff;
}

/* ── Background crossfade ────────────────────────────────────────── */
#bg-layer { position: fixed; inset: 0; z-index: 0; }

.bg-slide {
  position: absolute; inset: 0;
  opacity: 0;
  transition: opacity 5s ease-in-out;
}
.bg-slide.active { opacity: 1; }

.bg-slide:nth-child(1) { background: linear-gradient(145deg, #0f2027 0%, #203a43 50%, #2c5364 100%); }
.bg-slide:nth-child(2) { background: linear-gradient(145deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); }
.bg-slide:nth-child(3) { background: linear-gradient(145deg, #03045e 0%, #023e8a 50%, #0077b6 100%); }
.bg-slide:nth-child(4) { background: linear-gradient(145deg, #0d1b2a 0%, #1b263b 50%, #415a77 100%); }
.bg-slide:nth-child(5) { background: linear-gradient(145deg, #10002b 0%, #3c096c 50%, #5a189a 100%); }

/* ── Floating orbs ───────────────────────────────────────────────── */
.orb {
  position: fixed; border-radius: 50%;
  filter: blur(90px); opacity: 0.22; z-index: 0;
}
.orb-1 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, #00b4d8, transparent 70%);
  top: -150px; left: -150px;
  animation: orbFloat1 25s ease-in-out infinite alternate;
}
.orb-2 {
  width: 450px; height: 450px;
  background: radial-gradient(circle, #7209b7, transparent 70%);
  bottom: 5%; right: -120px;
  animation: orbFloat2 30s ease-in-out infinite alternate;
}
.orb-3 {
  width: 380px; height: 380px;
  background: radial-gradient(circle, #4361ee, transparent 70%);
  top: 35%; left: 25%;
  animation: orbFloat3 20s ease-in-out infinite alternate;
}
.orb-4 {
  width: 300px; height: 300px;
  background: radial-gradient(circle, #f72585, transparent 70%);
  bottom: 20%; left: 10%;
  animation: orbFloat1 28s ease-in-out infinite alternate-reverse;
}

@keyframes orbFloat1 {
  from { transform: translate(0,0) scale(1); }
  to   { transform: translate(60px,-80px) scale(1.2); }
}
@keyframes orbFloat2 {
  from { transform: translate(0,0) scale(1.1); }
  to   { transform: translate(-50px,70px) scale(0.9); }
}
@keyframes orbFloat3 {
  from { transform: translate(0,0) scale(1); }
  to   { transform: translate(-40px,-50px) scale(1.15); }
}

/* ── Glass panels ────────────────────────────────────────────────── */
.glass {
  background: rgba(255,255,255,0.07);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 20px;
}

/* ── App layout ──────────────────────────────────────────────────── */
#app {
  position: relative; z-index: 1;
  display: flex; flex-direction: column;
  height: 100vh;
  padding: 14px 18px 10px;
  gap: 10px;
}

/* ── Header ──────────────────────────────────────────────────────── */
#header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 22px;
  flex-shrink: 0;
}

#logo-area { display: flex; align-items: center; gap: 10px; }
#logo-area .logo-icon {
  font-size: 34px; color: #00b4d8;
  filter: drop-shadow(0 0 8px rgba(0,180,216,0.6));
}
#logo-area .logo-text {
  font-size: 24px; font-weight: 700; letter-spacing: 1px;
  background: linear-gradient(90deg, #00b4d8, #90e0ef);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

#clock-area { text-align: center; }
#clock {
  font-size: 66px; font-weight: 800;
  letter-spacing: 4px; line-height: 1;
  text-shadow: 0 0 30px rgba(0,180,216,0.5);
}
#clock-date {
  font-size: 16px; font-weight: 400;
  color: rgba(255,255,255,0.65); margin-top: 2px;
}
#clock-date-hebrew {
  font-size: 14px; color: rgba(255,255,255,0.4);
  margin-top: 1px;
}

#today-badge {
  padding: 8px 18px; border-radius: 50px;
  background: rgba(0,180,216,0.2);
  border: 1px solid rgba(0,180,216,0.4);
  font-size: 18px; font-weight: 600;
  color: #90e0ef; text-align: center;
}
#today-badge .badge-label {
  font-size: 12px; color: rgba(255,255,255,0.5);
  display: block; margin-bottom: 2px;
}

/* ── Main content ────────────────────────────────────────────────── */
#main-content {
  flex: 1; display: flex; flex-direction: column;
  gap: 10px; overflow: hidden; min-height: 0;
}

/* Schedule card */
#schedule-card { padding: 16px 22px; flex-shrink: 0; }

.section-title {
  font-size: 13px; font-weight: 500;
  color: rgba(255,255,255,0.45);
  margin-bottom: 10px; letter-spacing: 1.5px;
  text-transform: uppercase;
  display: flex; align-items: center; gap: 7px;
}

#duty-display { display: flex; align-items: center; gap: 20px; }
#duty-icon {
  font-size: 58px; flex-shrink: 0;
  animation: iconPulse 3s ease-in-out infinite;
}
@keyframes iconPulse {
  0%,100% { transform: scale(1); }
  50%      { transform: scale(1.08); }
}
#duty-info { flex: 1; }
#duty-name {
  font-size: 48px; font-weight: 800; line-height: 1.1;
  background: linear-gradient(90deg, #fff, #90e0ef);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
#duty-dept {
  font-size: 20px; font-weight: 500;
  color: rgba(255,255,255,0.6); margin-top: 4px;
}
#duty-week { font-size: 14px; color: rgba(255,255,255,0.38); margin-top: 3px; }
#duty-status {
  display: none; margin-top: 8px;
  padding: 3px 12px; border-radius: 20px;
  font-size: 13px; font-weight: 600;
  background: rgba(0,180,216,0.25);
  border: 1px solid rgba(0,180,216,0.5);
  color: #90e0ef;
}
#no-duty {
  font-size: 24px; color: rgba(255,255,255,0.4);
  text-align: center; padding: 16px 0; display: none;
}

/* Guidance card */
#guidance-card { padding: 14px 22px; flex-shrink: 0; }
#guidance-text {
  font-size: 22px; font-weight: 600;
  color: #ffd166; line-height: 1.4;
}
#guidance-empty {
  font-size: 16px; color: rgba(255,255,255,0.3); display: none;
}

/* ── News card ───────────────────────────────────────────────────── */
#news-card {
  flex: 1; min-height: 0;
  padding: 12px 18px;
  display: flex; flex-direction: column;
}
#news-card .section-title { margin-bottom: 8px; }
#news-card .section-title i { color: #e63946; }

/* Timer bar under the title */
#news-timer-wrap {
  height: 3px; border-radius: 3px;
  background: rgba(255,255,255,0.08);
  overflow: hidden; margin-bottom: 10px; flex-shrink: 0;
}
#news-timer-bar {
  height: 100%;
  background: linear-gradient(90deg, #e63946, #ff6b6b);
  width: 100%;
  transform-origin: left;
  border-radius: 3px;
}

#news-list { flex: 1; min-height: 0; overflow: hidden; position: relative; }

/* slide-in animation for new batch */
#news-track {
  display: flex; flex-direction: column; gap: 7px;
}
#news-track.slide-out {
  animation: slideOut 0.4s ease-in forwards;
}
#news-track.slide-in {
  animation: slideIn 0.4s ease-out forwards;
}
@keyframes slideOut {
  from { opacity:1; transform: translateY(0); }
  to   { opacity:0; transform: translateY(-20px); }
}
@keyframes slideIn {
  from { opacity:0; transform: translateY(20px); }
  to   { opacity:1; transform: translateY(0); }
}

.news-item {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 8px 12px; border-radius: 10px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.07);
  flex-shrink: 0;
}
.news-item .news-time {
  font-size: 11px; padding: 2px 9px; border-radius: 20px;
  background: rgba(230,57,70,0.22);
  border: 1px solid rgba(230,57,70,0.35);
  color: #e87c85; white-space: nowrap;
  flex-shrink: 0; margin-top: 1px;
}
.news-item .news-title {
  font-size: 14px; font-weight: 500;
  color: rgba(255,255,255,0.82); line-height: 1.35;
}

/* ── Fact card ───────────────────────────────────────────────────── */
#fact-card {
  padding: 12px 18px;
  flex-shrink: 0;
  display: flex; align-items: flex-start; gap: 12px;
}
#fact-icon {
  font-size: 26px; flex-shrink: 0; margin-top: 1px;
  animation: factSpin 6s ease-in-out infinite;
}
@keyframes factSpin {
  0%,100% { transform: rotate(-5deg) scale(1); }
  50%      { transform: rotate(5deg) scale(1.1); }
}
#fact-body { flex: 1; min-width: 0; }
#fact-label {
  font-size: 11px; font-weight: 600; letter-spacing: 1.5px;
  color: rgba(255,255,255,0.35); text-transform: uppercase; margin-bottom: 4px;
  display: flex; align-items: center; gap: 6px;
}
#fact-label .fact-source-link {
  color: rgba(255,255,255,0.2); font-weight: 400; font-size: 10px;
  text-decoration: none;
}
#fact-label .fact-source-link:hover { color: rgba(255,255,255,0.5); }
#fact-title {
  font-size: 15px; font-weight: 700;
  color: #a8dadc; margin-bottom: 3px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
#fact-text {
  font-size: 13px; font-weight: 400;
  color: rgba(255,255,255,0.72); line-height: 1.5;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}
#fact-timer-wrap {
  height: 2px; border-radius: 2px;
  background: rgba(255,255,255,0.08);
  overflow: hidden; margin-top: 8px;
}
#fact-timer-bar {
  height: 100%;
  background: linear-gradient(90deg, #a8dadc, #48cae4);
  transform-origin: left; border-radius: 2px;
}
#fact-loading {
  font-size: 12px; color: rgba(255,255,255,0.3); display: flex; align-items: center; gap: 6px;
}
#fact-loading .spinner { width: 14px; height: 14px; border-width: 2px; border-top-color: #a8dadc; }

/* ── Footer ──────────────────────────────────────────────────────── */
#footer {
  padding: 7px 22px;
  display: flex; justify-content: space-between; align-items: center;
  flex-shrink: 0;
  border-top: 1px solid rgba(255,255,255,0.07);
}
#footer .footer-brand { font-size: 12px; color: rgba(255,255,255,0.25); letter-spacing: 1px; }
#footer .footer-update { font-size: 12px; color: rgba(255,255,255,0.22); }
#update-time { color: rgba(255,255,255,0.4); }

/* Loading */
#loading {
  display: flex; align-items: center; justify-content: center;
  gap: 10px; color: rgba(255,255,255,0.4);
  font-size: 16px; padding: 16px;
}
.spinner {
  width: 22px; height: 22px;
  border: 3px solid rgba(255,255,255,0.1);
  border-top-color: #00b4d8; border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>

<div id="bg-layer">
  <div class="bg-slide active"></div>
  <div class="bg-slide"></div>
  <div class="bg-slide"></div>
  <div class="bg-slide"></div>
  <div class="bg-slide"></div>
</div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>
<div class="orb orb-4"></div>

<div id="app">

  <!-- Header -->
  <div id="header" class="glass">
    <div id="logo-area">
      <i class="bi bi-headset logo-icon"></i>
      <span class="logo-text">מוקד נט</span>
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

  <!-- Main -->
  <div id="main-content">

    <!-- Duty -->
    <div id="schedule-card" class="glass">
      <div class="section-title">
        <i class="bi bi-calendar-week" style="color:#00b4d8"></i>
        תורנות השבוע
      </div>
      <div id="loading"><div class="spinner"></div> טוען...</div>
      <div id="duty-display" style="display:none">
        <div id="duty-icon"><i class="bi bi-person-badge"></i></div>
        <div id="duty-info">
          <div id="duty-name">—</div>
          <div id="duty-dept"></div>
          <div id="duty-week"></div>
          <div id="duty-status"></div>
        </div>
      </div>
      <div id="no-duty">אין תורנות מוגדרת לשבוע זה</div>
    </div>

    <!-- Guidance -->
    <div id="guidance-card" class="glass">
      <div class="section-title">
        <i class="bi bi-lightning-charge-fill" style="color:#f77f00"></i>
        הנחיית היום
      </div>
      <div id="guidance-text"></div>
      <div id="guidance-empty">אין הנחיה מיוחדת להיום</div>
    </div>

    <!-- Fact -->
    <div id="fact-card" class="glass">
      <div id="fact-icon">💡</div>
      <div id="fact-body">
        <div id="fact-label">
          ידעת?
          <a id="fact-source-link" href="#" target="_blank" class="fact-source-link"></a>
        </div>
        <div id="fact-loading"><div class="spinner"></div> טוען...</div>
        <div id="fact-title" style="display:none"></div>
        <div id="fact-text" style="display:none"></div>
        <div id="fact-timer-wrap" style="display:none"><div id="fact-timer-bar"></div></div>
      </div>
    </div>

    <!-- News -->
    <div id="news-card" class="glass">
      <div class="section-title">
        <i class="bi bi-newspaper"></i>
        חדשות · <span id="news-counter" style="color:rgba(255,255,255,0.35)"></span>
      </div>
      <div id="news-timer-wrap">
        <div id="news-timer-bar"></div>
      </div>
      <div id="news-list">
        <div id="news-track"></div>
      </div>
    </div>

  </div>

  <!-- Footer -->
  <div id="footer">
    <span class="footer-brand">שילוט דיגיטלי · מוקד נט 2025</span>
    <span class="footer-update">עדכון אחרון: <span id="update-time">—</span></span>
  </div>

</div>

<script>
const BASE = '<?= $base ?>';
const DAYS_HE = ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];
const DEPT_ICONS = {
  'אינטרנט ותוכן': { icon: 'bi-globe',   color: '#00b4d8' },
  'תמיכה טכנית':   { icon: 'bi-tools',   color: '#4cc9f0' },
  'שירות לקוחות':  { icon: 'bi-headset', color: '#b084f7' },
};
const NEWS_INTERVAL = 17; // seconds per page
const NEWS_PER_PAGE = 7;
const BG_INTERVAL   = 22; // seconds per background

// ── Background crossfade ──────────────────────────────────────────────
(function () {
  const slides = document.querySelectorAll('.bg-slide');
  let current = 0;
  setInterval(() => {
    slides[current].classList.remove('active');
    current = (current + 1) % slides.length;
    slides[current].classList.add('active');
  }, BG_INTERVAL * 1000);
})();

// ── Hebrew numeral conversion ─────────────────────────────────────────
function toHebrewNumerals(n) {
  const ones   = ['','א','ב','ג','ד','ה','ו','ז','ח','ט'];
  const tens   = ['','י','כ','ל','מ','נ','ס','ע','פ','צ'];
  const hundreds = ['','ק','ר','ש','ת','תק','תר','תש','תת','תתק'];
  // special cases to avoid writing divine names
  const special = { 15:'ט״ו', 16:'ט״ז' };
  if (special[n]) return special[n];
  let result = '';
  const h = Math.floor(n / 100); n %= 100;
  const t = Math.floor(n / 10);  n %= 10;
  result = (hundreds[h] || '') + (tens[t] || '') + (ones[n] || '');
  // insert gershayim before last letter if >1 letter, geresh if 1 letter
  if (result.length === 1) return result + '׳';
  return result.slice(0, -1) + '״' + result.slice(-1);
}

// ── Hebrew date (Intl API + manual numeral fix) ───────────────────────
function getHebrewDate() {
  try {
    // Get the raw string — Intl gives arabic numerals for day/year
    const raw = new Date().toLocaleDateString('he-IL-u-ca-hebrew', {
      weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });
    // Replace every standalone arabic number with Hebrew numerals
    return raw.replace(/\d+/g, m => toHebrewNumerals(parseInt(m, 10)));
  } catch {
    return '';
  }
}

// ── Clock ─────────────────────────────────────────────────────────────
let lastHebrewUpdate = '';
function tick() {
  const now = new Date();
  const hh = String(now.getHours()).padStart(2,'0');
  const mm = String(now.getMinutes()).padStart(2,'0');
  const ss = String(now.getSeconds()).padStart(2,'0');
  document.getElementById('clock').textContent = `${hh}:${mm}:${ss}`;

  const dd = String(now.getDate()).padStart(2,'0');
  const mo = String(now.getMonth()+1).padStart(2,'0');
  document.getElementById('clock-date').textContent = `${dd}/${mo}/${now.getFullYear()}`;
  document.getElementById('day-name').textContent = DAYS_HE[now.getDay()];

  // Hebrew date updates at most once per minute
  const minKey = `${now.getDate()}-${now.getMinutes()}`;
  if (minKey !== lastHebrewUpdate) {
    const heb = getHebrewDate();
    document.getElementById('clock-date-hebrew').textContent = heb;
    lastHebrewUpdate = minKey;
  }
}
tick();
setInterval(tick, 1000);

// ── Duty data ─────────────────────────────────────────────────────────
function fmtDateRange(str) {
  if (!str) return '';
  const d = new Date(str);
  const e = new Date(d); e.setDate(d.getDate()+6);
  const f = dt => `${String(dt.getDate()).padStart(2,'0')}/${String(dt.getMonth()+1).padStart(2,'0')}/${dt.getFullYear()}`;
  return `${f(d)} – ${f(e)}`;
}

async function loadDuty() {
  try {
    const r    = await fetch(BASE + '/api/duty/current');
    const data = await r.json();
    document.getElementById('loading').style.display = 'none';

    if (data.schedule) {
      const s    = data.schedule;
      const meta = DEPT_ICONS[s.department] || { icon: 'bi-person-badge', color: '#00b4d8' };
      document.getElementById('duty-icon').innerHTML =
        `<i class="bi ${meta.icon}" style="color:${meta.color};filter:drop-shadow(0 0 14px ${meta.color}88);font-size:58px"></i>`;
      document.getElementById('duty-name').textContent = s.rep_name || '—';
      document.getElementById('duty-dept').textContent = s.department || '';
      document.getElementById('duty-week').textContent = fmtDateRange(data.week_start);

      const statusEl = document.getElementById('duty-status');
      if (s.status && s.status !== 'active') {
        statusEl.textContent   = s.status;
        statusEl.style.display = 'inline-block';
      } else {
        statusEl.style.display = 'none';
      }
      document.getElementById('duty-display').style.display = 'flex';
    } else {
      document.getElementById('no-duty').style.display = 'block';
    }

    const g = data.today_guidance;
    if (g && g.trim()) {
      document.getElementById('guidance-text').textContent = g;
      document.getElementById('guidance-text').style.display = '';
      document.getElementById('guidance-empty').style.display = 'none';
    } else {
      document.getElementById('guidance-text').style.display  = 'none';
      document.getElementById('guidance-empty').style.display = 'block';
    }

    const now = new Date();
    document.getElementById('update-time').textContent =
      `${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
  } catch {
    document.getElementById('loading').innerHTML = '<span style="color:#e63946">שגיאה בטעינת נתונים</span>';
  }
}
loadDuty();
setInterval(loadDuty, 5 * 60 * 1000);

// ── News ──────────────────────────────────────────────────────────────
let newsItems = [];
let newsPage  = 0;
let newsTimerRaf = null;
let newsTimerStart = null;

function getRelativeTime(dateStr) {
  const diff = Math.floor((Date.now() - new Date(dateStr)) / 60000);
  if (diff < 1)    return 'ממש עכשיו';
  if (diff === 1)  return 'לפני דקה';
  if (diff < 60)   return `לפני ${diff} דקות`;
  const h = Math.floor(diff / 60);
  if (diff < 1440) return h === 1 ? 'לפני שעה' : `לפני ${h} שעות`;
  const d = Math.floor(diff / 1440);
  return d === 1 ? 'אתמול' : `לפני ${d} ימים`;
}

function renderNews() {
  if (!newsItems.length) return;
  const total  = Math.ceil(newsItems.length / NEWS_PER_PAGE);
  const start  = newsPage * NEWS_PER_PAGE;
  const slice  = newsItems.slice(start, start + NEWS_PER_PAGE);
  const track  = document.getElementById('news-track');

  // slide out
  track.classList.remove('slide-in');
  track.classList.add('slide-out');
  setTimeout(() => {
    track.innerHTML = '';
    slice.forEach(item => {
      const div = document.createElement('div');
      div.className = 'news-item';
      div.innerHTML = `<span class="news-time">${item.time}</span><span class="news-title">${item.title}</span>`;
      track.appendChild(div);
    });
    track.classList.remove('slide-out');
    track.classList.add('slide-in');

    document.getElementById('news-counter').textContent =
      `עמוד ${newsPage + 1} מתוך ${total}`;

    newsPage = (newsPage + 1) >= total ? 0 : newsPage + 1;
    startNewsTimer();
  }, 420);
}

function startNewsTimer() {
  const bar = document.getElementById('news-timer-bar');
  if (newsTimerRaf) cancelAnimationFrame(newsTimerRaf);
  newsTimerStart = performance.now();
  const duration = NEWS_INTERVAL * 1000;

  function animate(now) {
    const elapsed  = now - newsTimerStart;
    const progress = Math.min(elapsed / duration, 1);
    bar.style.transform = `scaleX(${1 - progress})`;
    if (progress < 1) {
      newsTimerRaf = requestAnimationFrame(animate);
    }
  }
  newsTimerRaf = requestAnimationFrame(animate);
}

async function loadNews() {
  try {
    const r    = await fetch('https://rss.walla.co.il/feed/22');
    const text = await r.text();
    const xml  = new DOMParser().parseFromString(text, 'text/xml');
    newsItems  = Array.from(xml.getElementsByTagName('item')).slice(0, 30).map(item => ({
      title: item.getElementsByTagName('title')[0]?.textContent || '',
      time:  getRelativeTime(item.getElementsByTagName('pubDate')[0]?.textContent || ''),
    }));
    newsPage = 0;
    renderNews();
  } catch {
    document.getElementById('news-track').innerHTML =
      '<div style="color:rgba(255,255,255,0.3);padding:10px;font-size:13px">לא ניתן לטעון חדשות</div>';
  }
}

loadNews();
setInterval(loadNews, 2 * 60 * 1000);
setInterval(renderNews, NEWS_INTERVAL * 1000);

// ── Facts (Wikipedia Hebrew) ──────────────────────────────────────────
const FACT_INTERVAL = 30; // seconds
const FACT_ICONS    = ['💡','🌍','🔭','🧬','⚡','🏛️','🎯','🦁','🌊','🧩','🎨','🚀'];
let factTimerRaf   = null;
let factTimerStart = null;

function pickFactIcon() {
  return FACT_ICONS[Math.floor(Math.random() * FACT_ICONS.length)];
}

function startFactTimer() {
  const bar = document.getElementById('fact-timer-bar');
  if (factTimerRaf) cancelAnimationFrame(factTimerRaf);
  factTimerStart = performance.now();
  const duration = FACT_INTERVAL * 1000;
  function animate(now) {
    const progress = Math.min((now - factTimerStart) / duration, 1);
    bar.style.transform = `scaleX(${1 - progress})`;
    if (progress < 1) factTimerRaf = requestAnimationFrame(animate);
  }
  factTimerRaf = requestAnimationFrame(animate);
}

async function loadFact() {
  try {
    const r    = await fetch('https://he.wikipedia.org/api/rest_v1/page/random/summary');
    const data = await r.json();

    // filter out stubs / disambiguation (extract too short or missing)
    const extract = (data.extract || '').trim();
    const title   = (data.title  || '').trim();
    if (!extract || extract.length < 60 || title.includes('פירוש') || title.includes('ביטול')) {
      // retry once with another random article
      return loadFact();
    }

    // trim to ~180 chars at sentence boundary
    let text = extract;
    if (text.length > 180) {
      const cut = text.lastIndexOf('.', 180);
      text = cut > 60 ? text.slice(0, cut + 1) : text.slice(0, 180) + '…';
    }

    document.getElementById('fact-icon').textContent = pickFactIcon();
    document.getElementById('fact-loading').style.display = 'none';
    document.getElementById('fact-title').textContent      = title;
    document.getElementById('fact-title').style.display    = '';
    document.getElementById('fact-text').textContent       = text;
    document.getElementById('fact-text').style.display     = '';
    document.getElementById('fact-timer-wrap').style.display = '';

    const link = document.getElementById('fact-source-link');
    link.textContent = '← ויקיפדיה';
    link.href = data.content_urls?.desktop?.page || '#';

    startFactTimer();
  } catch {
    // silently skip if Wikipedia unreachable
  }
}

loadFact();
setInterval(loadFact, FACT_INTERVAL * 1000);
</script>
</body>
</html>
