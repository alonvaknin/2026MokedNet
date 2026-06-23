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
.bg-slide { position: absolute; inset: 0; opacity: 0; transition: opacity 5s ease-in-out; }
.bg-slide.active { opacity: 1; }
.bg-slide:nth-child(1) { background: linear-gradient(145deg, #0f2027 0%, #203a43 50%, #2c5364 100%); }
.bg-slide:nth-child(2) { background: linear-gradient(145deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); }
.bg-slide:nth-child(3) { background: linear-gradient(145deg, #03045e 0%, #023e8a 50%, #0077b6 100%); }
.bg-slide:nth-child(4) { background: linear-gradient(145deg, #0d1b2a 0%, #1b263b 50%, #415a77 100%); }
.bg-slide:nth-child(5) { background: linear-gradient(145deg, #10002b 0%, #3c096c 50%, #5a189a 100%); }

/* ── Floating orbs ───────────────────────────────────────────────── */
.orb { position: fixed; border-radius: 50%; filter: blur(90px); opacity: 0.22; z-index: 0; }
.orb-1 { width: 500px; height: 500px; background: radial-gradient(circle, #00b4d8, transparent 70%); top: -150px; left: -150px; animation: orbFloat1 25s ease-in-out infinite alternate; }
.orb-2 { width: 450px; height: 450px; background: radial-gradient(circle, #7209b7, transparent 70%); bottom: 5%; right: -120px; animation: orbFloat2 30s ease-in-out infinite alternate; }
.orb-3 { width: 380px; height: 380px; background: radial-gradient(circle, #4361ee, transparent 70%); top: 35%; left: 25%; animation: orbFloat3 20s ease-in-out infinite alternate; }
.orb-4 { width: 300px; height: 300px; background: radial-gradient(circle, #f72585, transparent 70%); bottom: 20%; left: 10%; animation: orbFloat1 28s ease-in-out infinite alternate-reverse; }
@keyframes orbFloat1 { from { transform: translate(0,0) scale(1); } to { transform: translate(60px,-80px) scale(1.2); } }
@keyframes orbFloat2 { from { transform: translate(0,0) scale(1.1); } to { transform: translate(-50px,70px) scale(0.9); } }
@keyframes orbFloat3 { from { transform: translate(0,0) scale(1); } to { transform: translate(-40px,-50px) scale(1.15); } }

/* ── Glass ───────────────────────────────────────────────────────── */
.glass {
  background: rgba(255,255,255,0.07);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 18px;
}

/* ── Root layout: header / duty-bar / body / footer ─────────────── */
#app {
  position: relative; z-index: 1;
  display: grid;
  grid-template-rows: auto auto 1fr auto;
  height: 100vh;
  padding: 12px 16px 10px;
  gap: 10px;
}

/* ── Header ──────────────────────────────────────────────────────── */
#header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 10px 28px;
}

#logo-area { display: flex; align-items: center; gap: 10px; }
#logo-area .logo-icon { font-size: 30px; color: #00b4d8; filter: drop-shadow(0 0 8px rgba(0,180,216,0.6)); }
#logo-area .logo-text {
  font-size: 22px; font-weight: 700; letter-spacing: 1px;
  background: linear-gradient(90deg, #00b4d8, #90e0ef);
  -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}

#clock-area { text-align: center; }
#clock { font-size: 96px; font-weight: 800; letter-spacing: 4px; line-height: 1; text-shadow: 0 0 30px rgba(0,180,216,0.5); }
#clock-date { font-size: 15px; color: rgba(255,255,255,0.65); margin-top: 3px; }
#clock-date-hebrew { font-size: 13px; color: rgba(255,255,255,0.4); margin-top: 2px; }

#today-badge {
  padding: 10px 22px; border-radius: 50px;
  background: rgba(0,180,216,0.2); border: 1px solid rgba(0,180,216,0.4);
  font-size: 20px; font-weight: 600; color: #90e0ef; text-align: center;
}
#today-badge .badge-label { font-size: 11px; color: rgba(255,255,255,0.5); display: block; margin-bottom: 2px; }

/* ── Duty bar (full width below header) ─────────────────────────── */
#duty-bar {
  padding: 10px 28px;
  display: flex; align-items: center; gap: 20px;
}

.section-title {
  font-size: 11px; font-weight: 500; color: rgba(255,255,255,0.45);
  margin-bottom: 6px; letter-spacing: 1.5px; text-transform: uppercase;
  display: flex; align-items: center; gap: 6px;
}

#duty-display { display: flex; align-items: center; gap: 18px; flex: 1; }
#duty-icon { font-size: 52px; flex-shrink: 0; animation: iconPulse 3s ease-in-out infinite; }
@keyframes iconPulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.08); } }

#duty-info { flex: 1; min-width: 0; }
#duty-name {
  font-size: 34px; font-weight: 800; line-height: 1.05;
  background: linear-gradient(90deg, #fff, #90e0ef);
  -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
#duty-dept { font-size: 15px; font-weight: 500; color: rgba(255,255,255,0.6); margin-top: 2px; }
#duty-week { font-size: 12px; color: rgba(255,255,255,0.38); margin-top: 2px; }
#duty-status {
  display: none; margin-top: 6px; padding: 2px 12px; border-radius: 20px;
  font-size: 12px; font-weight: 600;
  background: rgba(0,180,216,0.25); border: 1px solid rgba(0,180,216,0.5); color: #90e0ef;
}
#no-duty { font-size: 20px; color: rgba(255,255,255,0.4); padding: 8px 0; display: none; }

/* guidance inline in duty bar */
#guidance-inline {
  flex-shrink: 0; max-width: 320px;
  border-right: 2px solid rgba(247,127,0,0.4);
  padding-right: 18px;
}
#guidance-inline .section-title { margin-bottom: 4px; }
#guidance-text { font-size: 16px; font-weight: 600; color: #ffd166; line-height: 1.45; }
#guidance-empty { font-size: 13px; color: rgba(255,255,255,0.3); display: none; }

/* ── Body (news + weather side by side) ─────────────────────────── */
#body {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 10px;
  min-height: 0;
}

/* ── News card ───────────────────────────────────────────────────── */
#news-card {
  min-height: 0; padding: 12px 16px;
  display: flex; flex-direction: column;
}
#news-card .section-title { margin-bottom: 6px; }
#news-card .section-title i { color: #e63946; }

#news-timer-wrap {
  height: 3px; border-radius: 3px; background: rgba(255,255,255,0.08);
  overflow: hidden; margin-bottom: 8px; flex-shrink: 0;
}
#news-timer-bar {
  height: 100%; background: linear-gradient(90deg, #e63946, #ff6b6b);
  width: 100%; transform-origin: left; border-radius: 3px;
}
#news-list { flex: 1; min-height: 0; overflow: hidden; }
#news-track { display: flex; flex-direction: column; gap: 6px; }
#news-track.slide-out { animation: slideOut 0.4s ease-in forwards; }
#news-track.slide-in  { animation: slideIn  0.4s ease-out forwards; }
@keyframes slideOut { from { opacity:1; transform: translateY(0); } to { opacity:0; transform: translateY(-20px); } }
@keyframes slideIn  { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: translateY(0); } }

.news-item {
  display: flex; align-items: center; gap: 10px;
  padding: 7px 10px; border-radius: 10px;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.07);
  flex-shrink: 0; overflow: hidden;
}
.news-item img {
  width: 72px; height: 48px; border-radius: 7px;
  object-fit: cover; flex-shrink: 0;
  background: rgba(255,255,255,0.05);
}
.news-item-text { flex: 1; min-width: 0; }
.news-item .news-time {
  font-size: 10px; padding: 1px 7px; border-radius: 20px;
  background: rgba(230,57,70,0.22); border: 1px solid rgba(230,57,70,0.35);
  color: #e87c85; white-space: nowrap; display: inline-block; margin-bottom: 3px;
}
.news-item .news-title {
  font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.85); line-height: 1.35;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* ── Right col: weather + fact ───────────────────────────────────── */
#right-col {
  display: flex; flex-direction: column; gap: 10px; min-height: 0;
}

#weather-card {
  flex: 1; min-height: 0; padding: 10px 14px;
  display: flex; flex-direction: column; overflow: hidden;
}
#weather-wrap {
  flex: 1; display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.weatherwidget-io { display: block; transform: scale(0.85); transform-origin: top center; }

#fact-card {
  flex-shrink: 0; padding: 10px 14px;
  display: flex; align-items: flex-start; gap: 10px;
}
#fact-icon { font-size: 22px; flex-shrink: 0; margin-top: 1px; animation: factSpin 6s ease-in-out infinite; }
@keyframes factSpin { 0%,100% { transform: rotate(-5deg) scale(1); } 50% { transform: rotate(5deg) scale(1.1); } }
#fact-body { flex: 1; min-width: 0; }
#fact-label {
  font-size: 10px; font-weight: 600; letter-spacing: 1.5px;
  color: rgba(255,255,255,0.35); text-transform: uppercase; margin-bottom: 3px;
  display: flex; align-items: center; gap: 6px;
}
#fact-label .fact-source-link { color: rgba(255,255,255,0.2); font-weight: 400; font-size: 9px; text-decoration: none; }
#fact-title { font-size: 12px; font-weight: 700; color: #a8dadc; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
#fact-text {
  font-size: 11px; color: rgba(255,255,255,0.72); line-height: 1.5;
  display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; line-clamp: 2; overflow: hidden;
}
#fact-timer-wrap { height: 2px; border-radius: 2px; background: rgba(255,255,255,0.08); overflow: hidden; margin-top: 5px; }
#fact-timer-bar { height: 100%; background: linear-gradient(90deg, #a8dadc, #48cae4); transform-origin: left; border-radius: 2px; }
#fact-loading { font-size: 11px; color: rgba(255,255,255,0.3); display: flex; align-items: center; gap: 6px; }
#fact-loading .spinner { width: 12px; height: 12px; border-width: 2px; border-top-color: #a8dadc; }

/* ── Footer ──────────────────────────────────────────────────────── */
#footer {
  display: flex; justify-content: space-between; align-items: center;
  padding: 5px 16px;
  border-top: 1px solid rgba(255,255,255,0.07);
}
#footer .footer-brand { font-size: 11px; color: rgba(255,255,255,0.25); letter-spacing: 1px; }
#footer .footer-update { font-size: 11px; color: rgba(255,255,255,0.22); }
#update-time { color: rgba(255,255,255,0.4); }

/* Loading / spinner */
#loading { display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,0.4); font-size: 14px; }
.spinner { width: 20px; height: 20px; border: 3px solid rgba(255,255,255,0.1); border-top-color: #00b4d8; border-radius: 50%; animation: spin 0.8s linear infinite; }
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

  <!-- Header: logo + clock + day -->
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

  <!-- Duty bar: full width -->
  <div id="duty-bar" class="glass">
    <div style="flex:1;min-width:0">
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

    <!-- Guidance inline -->
    <div id="guidance-inline">
      <div class="section-title">
        <i class="bi bi-lightning-charge-fill" style="color:#f77f00"></i>
        הנחיית היום
      </div>
      <div id="guidance-text"></div>
      <div id="guidance-empty">אין הנחיה מיוחדת להיום</div>
    </div>
  </div>

  <!-- Body: news + right col -->
  <div id="body">

    <!-- News -->
    <div id="news-card" class="glass">
      <div class="section-title">
        <i class="bi bi-newspaper" style="color:#e63946"></i>
        חדשות · <span id="news-counter" style="color:rgba(255,255,255,0.35)"></span>
      </div>
      <div id="news-timer-wrap"><div id="news-timer-bar"></div></div>
      <div id="news-list"><div id="news-track"></div></div>
    </div>

    <!-- Right: weather + fact -->
    <div id="right-col">
      <div id="weather-card" class="glass">
        <div class="section-title">
          <i class="bi bi-cloud-sun" style="color:#48cae4"></i>
          מזג האוויר · חדרה
        </div>
        <div id="weather-wrap">
          <a class="weatherwidget-io"
             href="https://forecast7.com/he/32d4334d92/hadera/"
             data-label_1="חדרה"
             data-label_2="מזג אוויר"
             data-icons="Climacons Animated"
             data-theme="dark"
             data-basecolor="rgba(0,0,0,0)"
             data-textcolor="#ffffff"
             data-highcolor="#90e0ef"
             data-lowcolor="#a8dadc"
             data-suncolor="#ffd166"
             data-cloudcolor="#caf0f8"
             data-cloudfill="#caf0f8"
             data-raincolor="#4cc9f0"
             data-snowcolor="#caf0f8">חדרה מזג אוויר</a>
        </div>
      </div>

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
const NEWS_INTERVAL = 17;
const NEWS_PER_PAGE = 7;
const BG_INTERVAL   = 22;

// ── Background ────────────────────────────────────────────────────────
(function () {
  const slides = document.querySelectorAll('.bg-slide');
  let current = 0;
  setInterval(() => {
    slides[current].classList.remove('active');
    current = (current + 1) % slides.length;
    slides[current].classList.add('active');
  }, BG_INTERVAL * 1000);
})();

// ── Hebrew numerals ───────────────────────────────────────────────────
function toHebrewNumerals(n) {
  const ones    = ['','א','ב','ג','ד','ה','ו','ז','ח','ט'];
  const tens    = ['','י','כ','ל','מ','נ','ס','ע','פ','צ'];
  const hundreds= ['','ק','ר','ש','ת','תק','תר','תש','תת','תתק'];
  const special = { 15:'ט״ו', 16:'ט״ז' };
  if (special[n]) return special[n];
  const h = Math.floor(n / 100); n %= 100;
  const t = Math.floor(n / 10);  n %= 10;
  let r = (hundreds[h] || '') + (tens[t] || '') + (ones[n] || '');
  if (r.length === 1) return r + '׳';
  return r.slice(0, -1) + '״' + r.slice(-1);
}
function getHebrewDate() {
  try {
    const raw = new Date().toLocaleDateString('he-IL-u-ca-hebrew', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    return raw.replace(/\d+/g, m => toHebrewNumerals(parseInt(m, 10)));
  } catch { return ''; }
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
  const minKey = `${now.getDate()}-${now.getMinutes()}`;
  if (minKey !== lastHebrewUpdate) {
    document.getElementById('clock-date-hebrew').textContent = getHebrewDate();
    lastHebrewUpdate = minKey;
  }
}
tick();
setInterval(tick, 1000);

// ── Duty ──────────────────────────────────────────────────────────────
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
        `<i class="bi ${meta.icon}" style="color:${meta.color};filter:drop-shadow(0 0 14px ${meta.color}88);font-size:52px"></i>`;
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

// ── News (with images from enclosure) ────────────────────────────────
let newsItems = [], newsPage = 0, newsTimerRaf = null, newsTimerStart = null;

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
  const total = Math.ceil(newsItems.length / NEWS_PER_PAGE);
  const slice = newsItems.slice(newsPage * NEWS_PER_PAGE, (newsPage + 1) * NEWS_PER_PAGE);
  const track = document.getElementById('news-track');
  track.classList.remove('slide-in');
  track.classList.add('slide-out');
  setTimeout(() => {
    track.innerHTML = '';
    slice.forEach(item => {
      const div = document.createElement('div');
      div.className = 'news-item';
      const imgHtml = item.image
        ? `<img src="${item.image}" alt="" loading="lazy" onerror="this.style.display='none'">`
        : '';
      div.innerHTML = `
        ${imgHtml}
        <div class="news-item-text">
          <span class="news-time">${item.time}</span>
          <div class="news-title">${item.title}</div>
        </div>`;
      track.appendChild(div);
    });
    track.classList.remove('slide-out');
    track.classList.add('slide-in');
    document.getElementById('news-counter').textContent = `עמוד ${newsPage + 1} מתוך ${total}`;
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
    const progress = Math.min((now - newsTimerStart) / duration, 1);
    bar.style.transform = `scaleX(${1 - progress})`;
    if (progress < 1) newsTimerRaf = requestAnimationFrame(animate);
  }
  newsTimerRaf = requestAnimationFrame(animate);
}

async function loadNews() {
  try {
    const r    = await fetch('https://rss.walla.co.il/feed/22');
    const text = await r.text();
    const xml  = new DOMParser().parseFromString(text, 'text/xml');
    newsItems  = Array.from(xml.getElementsByTagName('item')).slice(0, 30).map(item => {
      // image from <enclosure url="...">
      const enc = item.getElementsByTagName('enclosure')[0];
      const image = enc ? enc.getAttribute('url') : null;
      return {
        title: item.getElementsByTagName('title')[0]?.textContent || '',
        time:  getRelativeTime(item.getElementsByTagName('pubDate')[0]?.textContent || ''),
        image,
      };
    });
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

// ── Facts ─────────────────────────────────────────────────────────────
const FACT_INTERVAL = 30;
const FACT_ICONS    = ['💡','🌍','🔭','🧬','⚡','🏛️','🎯','🦁','🌊','🧩','🎨','🚀'];
let factTimerRaf = null, factTimerStart = null;

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
    const extract = (data.extract || '').trim();
    const title   = (data.title  || '').trim();
    if (!extract || extract.length < 60 || title.includes('פירוש') || title.includes('ביטול')) return loadFact();
    let text = extract;
    if (text.length > 180) {
      const cut = text.lastIndexOf('.', 180);
      text = cut > 60 ? text.slice(0, cut + 1) : text.slice(0, 180) + '…';
    }
    document.getElementById('fact-icon').textContent = FACT_ICONS[Math.floor(Math.random() * FACT_ICONS.length)];
    document.getElementById('fact-loading').style.display = 'none';
    document.getElementById('fact-title').textContent   = title;
    document.getElementById('fact-title').style.display = '';
    document.getElementById('fact-text').textContent    = text;
    document.getElementById('fact-text').style.display  = '';
    document.getElementById('fact-timer-wrap').style.display = '';
    const link = document.getElementById('fact-source-link');
    link.textContent = '← ויקיפדיה';
    link.href = data.content_urls?.desktop?.page || '#';
    startFactTimer();
  } catch { /* silently skip */ }
}
loadFact();
setInterval(loadFact, FACT_INTERVAL * 1000);

// ── Weather widget ────────────────────────────────────────────────────
(function () {
  const s = document.createElement('script');
  s.async = true;
  s.src   = 'https://weatherwidget.io/js/widget.min.js';
  document.head.appendChild(s);
})();
</script>
</body>
</html>
