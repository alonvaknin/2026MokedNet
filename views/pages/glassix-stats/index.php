<div class="gs-head">
  <div>
    <div class="page-title">
      <i class="bi bi-headset" style="color:var(--accent);"></i> גלאסיקס פתוח לפי נציג
    </div>
    <div class="gs-sub" id="gs-summary">טוען...</div>
  </div>
  <div class="gs-head-left">
    <div class="gs-stamp" id="gs-stamp" hidden>
      <i class="bi bi-clock-history"></i>
      <span>
        <span class="gs-stamp-main" id="gs-stamp-main"></span>
        <span class="gs-stamp-rel" id="gs-stamp-rel"></span>
      </span>
    </div>
    <button class="gs-refresh-btn is-muted" id="gs-refresh-btn" onclick="gsRefresh()" type="button" disabled>
      <i class="bi bi-arrow-clockwise"></i> עדכן נתונים
    </button>
    <span class="gs-next" id="gs-next" hidden></span>
  </div>
</div>

<div class="gs-updating" id="gs-updating" hidden>
  <span class="gs-spinner"></span>
  <span class="gs-updating-text">מעדכן נתונים מ-Glassix</span>
  <span class="gs-updating-sub">בינתיים מוצגים הנתונים האחרונים שנשמרו</span>
</div>

<div id="gs-errors"></div>

<div id="gs-grid" class="gs-grid">
  <p class="gs-state">טוען...</p>
</div>

<style>
.gs-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}

.gs-sub {
  font-size: 13px;
  color: var(--text3);
  margin-top: 4px;
}

.gs-head-left {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.gs-stamp {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  background: var(--bg2);
  border: 1px solid var(--border2);
  border-radius: var(--radius-sm, 8px);
  padding: 8px 14px;
  white-space: nowrap;
}
.gs-stamp i { color: var(--accent); font-size: 17px; }

.gs-stamp-main {
  display: block;
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  font-variant-numeric: tabular-nums;
}

.gs-stamp-rel {
  display: block;
  font-size: 12px;
  color: var(--text3);
  margin-top: 1px;
}

.gs-stamp.is-stale { border-color: var(--accent); }

.gs-refresh-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-family: inherit;
  font-size: 14px;
  font-weight: 600;
  color: #fff;
  background: var(--accent);
  border: 0;
  border-radius: var(--radius-sm, 8px);
  padding: 9px 16px;
  cursor: pointer;
}
.gs-refresh-btn:hover:not(:disabled) { background: var(--accent-hover, var(--accent)); }
.gs-refresh-btn:focus-visible { outline: 2px solid var(--text); outline-offset: 2px; }

/* מושתק — עדיין גלוי, אך ברור שאי אפשר ללחוץ */
.gs-refresh-btn.is-muted {
  background: transparent;
  color: var(--text3);
  border: 1px solid var(--border2);
  cursor: default;
}

.gs-next {
  font-size: 12px;
  color: var(--text3);
  white-space: nowrap;
}

.gs-updating {
  position: relative;
  display: flex;
  align-items: center;
  gap: 11px;
  flex-wrap: wrap;
  background: var(--accent-dim);
  border: 1px solid var(--accent);
  border-radius: var(--radius-sm, 8px);
  padding: 13px 16px;
  margin-bottom: 16px;
  overflow: hidden;
}

/* חייב לבוא אחרי הכללים למעלה — display מפורש מבטל את התכונה hidden */
.gs-updating[hidden],
.gs-stamp[hidden],
.gs-refresh-btn[hidden],
.gs-next[hidden] { display: none; }

/* פס התקדמות אינסופי בראש הבאנר */
.gs-updating::after {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  height: 2px;
  width: 40%;
  background: var(--accent);
  animation: gs-sweep 1.4s ease-in-out infinite;
}

.gs-spinner {
  width: 17px;
  height: 17px;
  border-radius: 50%;
  border: 2px solid var(--accent);
  border-top-color: transparent;
  animation: gs-spin .7s linear infinite;
  flex-shrink: 0;
}

.gs-updating-text {
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
}

.gs-updating-sub {
  font-size: 13px;
  color: var(--text3);
}

@keyframes gs-spin { to { transform: rotate(360deg); } }

@keyframes gs-sweep {
  0%   { transform: translateX(0);     }
  50%  { transform: translateX(-150%); }
  100% { transform: translateX(0);     }
}

/* בזמן רענון — התוכן הקיים נשאר קריא אך מעומעם */
.gs-grid.is-updating {
  opacity: .6;
  transition: opacity .2s ease;
}

@media (prefers-reduced-motion: reduce) {
  .gs-spinner { animation: none; border-top-color: var(--accent); }
  .gs-updating::after { animation: none; width: 100%; }
  .gs-grid.is-updating { transition: none; }
}

.gs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
  gap: 16px;
  align-items: start;
  direction: rtl;
}

.gs-card {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-top: 3px solid var(--gs-hue);
  border-radius: var(--radius, 10px);
  box-shadow: var(--shadow);
  overflow: hidden;
}

.gs-card-head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 15px 16px;
  border-bottom: 1px solid var(--border);
  background: var(--gs-head);
}

.gs-dept-id {
  flex: 1 1 auto;
  min-width: 0;
}

.gs-dept-name {
  display: block;
  font-size: 19px;
  font-weight: 700;
  color: var(--gs-hue);
  letter-spacing: -0.01em;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.gs-dept-meta {
  display: block;
  font-size: 12px;
  color: var(--text3);
  margin-top: 2px;
}

.gs-dept-total {
  font-size: 28px;
  font-weight: 800;
  line-height: 1;
  color: var(--gs-hue);
  font-variant-numeric: tabular-nums;
  flex-shrink: 0;
}

.gs-row {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
}
.gs-row + .gs-row { border-top: 1px solid var(--border); }

/* פס העומס — שכבת רקע בלבד, מחוץ לזרימת ה-flex */
.gs-row-fill {
  position: absolute;
  top: 0;
  bottom: 0;
  right: 0;
  background: var(--gs-tint);
  pointer-events: none;
  z-index: 0;
}

.gs-name {
  flex: 0 1 auto;
  min-width: 0; /* בלי זה flex item לא מתכווץ ו-ellipsis לא עובד */
  position: relative;
  z-index: 1;
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  text-align: right;
  direction: rtl;
}

.gs-count {
  position: relative;
  z-index: 1;
  margin-inline-start: auto; /* דוחף את המונה לקצה שמאל */
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
  font-variant-numeric: tabular-nums;
  flex-shrink: 0;
  background: var(--gs-tint);
  border-radius: 999px;
  padding: 2px 11px;
  min-width: 34px;
  text-align: center;
}

.gs-state {
  grid-column: 1 / -1;
  padding: 30px 0;
  text-align: center;
  color: var(--text3);
  font-size: 14px;
}

.gs-card-empty {
  padding: 16px;
  text-align: center;
  color: var(--text3);
  font-size: 13px;
}

.gs-note {
  padding: 9px 16px;
  font-size: 12px;
  color: var(--text3);
  border-top: 1px solid var(--border);
}

.gs-error {
  font-size: 13.5px;
  color: #e5484d;
  background: rgba(229, 72, 77, .1);
  border: 1px solid rgba(229, 72, 77, .25);
  border-radius: var(--radius-sm, 6px);
  padding: 10px 13px;
  margin-bottom: 14px;
}

/* מצב צפוי (צינון) — מידע, לא שגיאה */
.gs-notice {
  font-size: 13.5px;
  color: var(--text2);
  background: var(--bg3);
  border: 1px solid var(--border2);
  border-radius: var(--radius-sm, 6px);
  padding: 10px 13px;
  margin-bottom: 14px;
}
</style>

<script>
const GS_HUES = {
  service: '#5b8dee',
  support: '#10b981',
  sales:   '#f59e0b',
};
const GS_HUE_FALLBACK = '#8b5cf6';

function gsHexA(hex, a) {
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  return `rgba(${r},${g},${b},${a})`;
}

function gsEsc(s) {
  const d = document.createElement('div');
  d.textContent = s ?? '';
  return d.innerHTML;
}

let gsBusy = false;
let gsStampAt = null;
let gsTtlMs = 600000; // מתעדכן מהשרת
let gsBlockedUntil = 0; // סוף הצינון מהשרת (0 = אין צינון)

function gsRelative(from) {
  const sec = Math.round((Date.now() - from.getTime()) / 1000);
  if (sec < 60) return 'לפני פחות מדקה';
  const min = Math.round(sec / 60);
  if (min < 60) return `לפני ${min} דקות`;
  const hr = Math.round(min / 60);
  if (hr < 24) return `לפני ${hr} שעות`;
  const day = Math.round(hr / 24);
  return day === 1 ? 'לפני יום' : `לפני ${day} ימים`;
}

function gsPaintStamp() {
  const stamp = document.getElementById('gs-stamp');
  const btn = document.getElementById('gs-refresh-btn');
  const next = document.getElementById('gs-next');

  if (!gsStampAt) {
    stamp.hidden = true;
    btn.hidden = true;
    next.hidden = true;
    return;
  }

  const time = gsStampAt.toLocaleTimeString('he-IL', { hour: '2-digit', minute: '2-digit' });
  const sameDay = gsStampAt.toDateString() === new Date().toDateString();
  const date = gsStampAt.toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit' });

  document.getElementById('gs-stamp-main').textContent = sameDay ? time : `${date} ${time}`;
  document.getElementById('gs-stamp-rel').textContent = `עודכן ${gsRelative(gsStampAt)}`;

  // ניתן לעדכן רק אחרי שפג תוקף המטמון (זמן העדכון + TTL),
  // ואם רענון נכשל — רק אחרי שהחסימה הזמנית נגמרה
  const readyAt = Math.max(gsStampAt.getTime() + gsTtlMs, gsBlockedUntil);
  const waitMs = readyAt - Date.now();
  const canRefresh = waitMs <= 0;

  stamp.classList.toggle('is-stale', canRefresh);
  stamp.hidden = false;

  // הכפתור תמיד מוצג — פעיל כשאפשר לעדכן, מושתק עם ספירה לאחור עד אז
  btn.hidden = false;
  btn.disabled = !canRefresh;
  btn.classList.toggle('is-muted', !canRefresh);

  if (canRefresh) {
    btn.title = '';
    next.hidden = true;
  } else {
    const mins = Math.max(1, Math.ceil(waitMs / 60000));
    btn.title = `ניתן לעדכן בעוד ${mins} דק׳`;
    next.textContent = `ניתן לעדכן בעוד ${mins} דק׳`;
    next.hidden = false;
  }
}

setInterval(() => { if (gsStampAt) gsPaintStamp(); }, 15000);

function gsSetUpdating(on) {
  document.getElementById('gs-updating').hidden = !on;
  document.getElementById('gs-grid').classList.toggle('is-updating', on);
  document.getElementById('gs-refresh-btn').disabled = on;
}

/**
 * טוען נתונים. ברירת מחדל: מגיש מהמטמון (מהיר) ואם הנתונים ישנים —
 * מרענן ברקע בלי להעלים את מה שכבר מוצג.
 */
async function gsLoad({ force = false, background = false } = {}) {
  if (gsBusy) return;
  gsBusy = true;

  const grid = document.getElementById('gs-grid');
  const errBox = document.getElementById('gs-errors');

  if (background) {
    gsSetUpdating(true);
  } else {
    grid.innerHTML = '<p class="gs-state">טוען...</p>';
    errBox.innerHTML = '';
  }

  let json = null;

  try {
    const url = `${window.__V2_BASE}/api/glassix/agent-stats${force ? '?refresh=1' : ''}`;
    const res = await fetch(url);
    json = await res.json();
  } catch (e) {
    console.error('[glassix-stats] fetch', e);
    if (!background) {
      grid.innerHTML = '<p class="gs-state">אין חיבור לשרת. נסו לרענן.</p>';
      document.getElementById('gs-summary').textContent = '';
    }
  }

  try {
    if (json && json.ok) {
      gsRender(json);
    } else if (json && !background) {
      grid.innerHTML = '<p class="gs-state">לא ניתן לטעון את הנתונים. נסו לרענן.</p>';
      document.getElementById('gs-summary').textContent = '';
    }
  } catch (e) {
    console.error('[glassix-stats] render', e);
    if (!background) {
      grid.innerHTML = `<p class="gs-state">שגיאה בהצגת הנתונים: ${gsEsc(e.message || e)}</p>`;
    }
  }

  gsBusy = false;
  gsSetUpdating(false);

  // הגיע זמן העדכון בכניסה לעמוד — מעדכנים מעצמנו, בלי להמתין ללחיצה
  if (!force && json && json.ok && gsCanRefreshNow()) {
    await gsLoad({ force: true, background: true });
  }
}

function gsCanRefreshNow() {
  if (!gsStampAt) return false;
  if (gsBlockedUntil > Date.now()) return false;
  return Date.now() >= gsStampAt.getTime() + gsTtlMs;
}

function gsRefresh() {
  // אין טעם לשלוח בקשה שהשרת ידחה
  if (!gsCanRefreshNow()) {
    gsPaintStamp();
    return;
  }
  gsLoad({ force: true, background: true });
}

function gsRender(json) {
  const grid = document.getElementById('gs-grid');
  const summary = document.getElementById('gs-summary');
  const errBox = document.getElementById('gs-errors');

  const depts = json.depts || [];
  const grand = depts.reduce((s, d) => s + d.total, 0);

  summary.textContent = grand
    ? `${grand} טיקטים פתוחים ב-30 הימים האחרונים`
    : 'אין טיקטים פתוחים ב-30 הימים האחרונים';

  const t = json.cached_at ? new Date(json.cached_at) : null;
  gsStampAt = (t && !isNaN(t)) ? t : null;
  if (json.ttl) gsTtlMs = json.ttl * 1000;

  // צינון מהשרת (אחרי rate limit) — חוסם את הכפתור עד שהוא נגמר.
  // כשהשרת מדווח שאין צינון, החסימה מתאפסת
  const cd = json.cooldown_until ? new Date(json.cooldown_until) : null;
  gsBlockedUntil = (cd && !isNaN(cd)) ? cd.getTime() : 0;
  gsPaintStamp();

  errBox.innerHTML = '';
  if (json.cooldown_until) {
    errBox.innerHTML = '<div class="gs-notice">חריגה ממכסת הבקשות ל-Glassix. מוצגים הנתונים האחרונים שנשמרו, והעדכון יתאפשר בהמשך.</div>';
  } else if (json.refresh_error) {
    errBox.innerHTML = '<div class="gs-error">העדכון נכשל. מוצגים הנתונים האחרונים שנשמרו.</div>';
  } else if (json.partial_fetch && (json.errors || []).length) {
    const failed = json.errors.map(e => gsEsc(e.dept)).join(', ');
    errBox.innerHTML = `<div class="gs-notice">חלק מהמחלקות לא נטענו (${failed}). הנתונים המוצגים חלקיים.</div>`;
  } else if (json.errors && json.errors.length) {
    errBox.innerHTML = json.errors.map(e => {
      const msg = typeof e.error === 'string' ? e.error : 'לא ניתן לטעון את המחלקה';
      return `<div class="gs-error">${gsEsc(e.dept)} — ${gsEsc(msg)}</div>`;
    }).join('');
  }

  if (!depts.length) {
    const rateLimited = (json.errors || []).some(e =>
      typeof e.error === 'string' && /rate limit/i.test(e.error));
    grid.innerHTML = rateLimited
      ? `<p class="gs-state">חריגה ממכסת הבקשות ל-Glassix. הנתונים יתעדכנו אוטומטית בהמשך — נסו שוב בעוד מספר דקות.</p>`
      : '<p class="gs-state">אין נתונים להצגה.</p>';
    return;
  }

  grid.innerHTML = depts.map(d => {
      const hue = GS_HUES[d.slug] || GS_HUE_FALLBACK;
      const tint = gsHexA(hue, .16);
      const head = gsHexA(hue, .1);
      const agents = d.agents || [];
      const max = agents.length ? agents[0].count : 0;

      const rows = agents.length
        ? agents.map(a => {
            const pct = max ? (a.count / max) * 100 : 0;
            return `<div class="gs-row">
              <span class="gs-row-fill" style="width:${pct.toFixed(1)}%"></span>
              <span class="gs-name" title="${gsEsc(a.agent)}">${gsEsc(a.agent)}</span>
              <span class="gs-count">${a.count}</span>
            </div>`;
          }).join('')
        : '<div class="gs-card-empty">אין טיקטים פתוחים במחלקה זו.</div>';

      const note = d.partial
        ? '<div class="gs-note">הוצגו התוצאות עד למכסת השליפה. ייתכן שיש טיקטים נוספים.</div>'
        : '';

      return `<div class="gs-card" style="--gs-hue:${hue};--gs-tint:${tint};--gs-head:${head}">
        <div class="gs-card-head">
          <span class="gs-dept-id">
            <span class="gs-dept-name">${gsEsc(d.label)}</span>
            <span class="gs-dept-meta">${agents.length} נציגים</span>
          </span>
          <span class="gs-dept-total">${d.total}</span>
        </div>
        ${rows}
        ${note}
      </div>`;
    }).join('');
}

gsLoad();
</script>
