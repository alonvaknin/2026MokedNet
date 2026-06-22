<?php
use Core\View;
use Core\Auth;
$base    = rtrim(CFG['app']['url'], '/');
$csrf    = $_SESSION['csrf_token'] ?? '';
$canEdit = Auth::can('canManageDuty');
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div class="page-title" style="margin-bottom:0;"><i class="bi bi-person-lines-fill" style="margin-left:8px;"></i>ניהול תורנות שבועית</div>
  <?php if ($canEdit): ?>
  <div style="display:flex;gap:8px;">
    <button class="btn btn-ghost" onclick="dutyOpenManualModal()"><i class="bi bi-pencil-fill"></i> שיבוץ ידני</button>
    <button class="btn btn-ghost" onclick="dutyOpenMultiAutoModal()"><i class="bi bi-magic"></i> שיבוץ אוטומטי מרובה</button>
    <button class="btn btn-primary" onclick="dutyAutoAssign()"><i class="bi bi-magic"></i> שבוע הבא</button>
  </div>
  <?php endif; ?>
</div>

<!-- Tabs -->
<div style="display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:20px;">
  <button class="duty-tab active" data-tab="schedule" onclick="dutySetTab('schedule')"><i class="bi bi-calendar-week"></i> תורנויות</button>
  <button class="duty-tab" data-tab="reps" onclick="dutySetTab('reps')"><i class="bi bi-people-fill"></i> נציגים</button>
  <button class="duty-tab" data-tab="guidance" onclick="dutySetTab('guidance')"><i class="bi bi-journal-text"></i> הנחיות יומיות</button>
</div>

<!-- ── Tab: תורנויות ── -->
<div id="duty-tab-schedule" class="duty-tab-panel">
  <div id="duty-schedule-content">
    <div style="text-align:center;padding:40px;color:var(--text3);"><i class="bi bi-hourglass-split" style="font-size:24px;display:block;margin-bottom:8px;"></i>טוען...</div>
  </div>
</div>

<!-- ── Tab: נציגים ── -->
<div id="duty-tab-reps" class="duty-tab-panel" style="display:none;">
  <?php if ($canEdit): ?>
  <div style="margin-bottom:14px;">
    <button class="btn btn-primary btn-sm" onclick="dutyOpenRepModal(null)"><i class="bi bi-plus-circle"></i> הוסף נציג</button>
  </div>
  <?php endif; ?>
  <div id="duty-reps-content">
    <div style="text-align:center;padding:40px;color:var(--text3);"><i class="bi bi-hourglass-split" style="font-size:24px;display:block;margin-bottom:8px;"></i>טוען...</div>
  </div>
</div>

<!-- ── Tab: הנחיות יומיות ── -->
<div id="duty-tab-guidance" class="duty-tab-panel" style="display:none;">
  <div id="duty-guidance-content">
    <div style="text-align:center;padding:40px;color:var(--text3);"><i class="bi bi-hourglass-split" style="font-size:24px;display:block;margin-bottom:8px;"></i>טוען...</div>
  </div>
</div>

<!-- ── Modal: שיבוץ אוטומטי מרובה ── -->
<div id="duty-multi-auto-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:380px;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);">
      <div style="font-size:15px;font-weight:700;">שיבוץ אוטומטי מרובה</div>
      <button type="button" onclick="dutyCloseMultiAutoModal()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
    </div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
      <div>
        <label class="duty-label">כמה שבועות קדימה לשבץ?</label>
        <input type="number" id="duty-multi-weeks" class="duty-input" value="4" min="1" max="52" style="width:100px;">
      </div>
      <div id="duty-multi-log" style="display:none;font-size:13px;background:var(--bg3);border-radius:8px;padding:12px;max-height:200px;overflow-y:auto;line-height:1.8;"></div>
      <div id="duty-multi-err" style="display:none;color:var(--danger);font-size:13px;padding:8px 12px;background:rgba(239,68,68,.1);border-radius:6px;"></div>
      <div style="display:flex;gap:8px;">
        <button id="duty-multi-btn" class="btn btn-primary" style="flex:1;" onclick="dutyRunMultiAuto()"><i class="bi bi-magic"></i> בצע שיבוץ</button>
        <button class="btn btn-ghost" onclick="dutyCloseMultiAutoModal()">ביטול</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal: שיבוץ ידני ── -->
<div id="duty-manual-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:420px;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);">
      <div style="font-size:15px;font-weight:700;">שיבוץ ידני</div>
      <button type="button" onclick="dutyCloseManualModal()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
    </div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
      <div>
        <label class="duty-label">תאריך תחילת שבוע (יום א׳)</label>
        <input type="date" id="duty-manual-date" class="duty-input">
      </div>
      <div>
        <label class="duty-label">נציג תורן</label>
        <select id="duty-manual-rep" class="duty-input"></select>
      </div>
      <div id="duty-manual-err" style="display:none;color:var(--danger);font-size:13px;padding:8px 12px;background:rgba(239,68,68,.1);border-radius:6px;"></div>
      <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" style="flex:1;" onclick="dutySaveManual()"><i class="bi bi-check-lg"></i> שמור</button>
        <button class="btn btn-ghost" onclick="dutyCloseManualModal()">ביטול</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal: עריכת תורנות קיימת ── -->
<div id="duty-sched-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:420px;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);">
      <div id="duty-sched-modal-title" style="font-size:15px;font-weight:700;"></div>
      <button type="button" onclick="dutyCloseSchedModal()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
    </div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
      <input type="hidden" id="duty-sched-id">
      <div>
        <label class="duty-label">נציג</label>
        <select id="duty-sched-rep" class="duty-input"></select>
      </div>
      <div>
        <label class="duty-label">סטטוס</label>
        <select id="duty-sched-status" class="duty-input">
          <option value="active">פעיל</option>
          <option value="missed">לא הגיע</option>
          <option value="replaced">הוחלף</option>
        </select>
      </div>
      <div>
        <label class="duty-label">הערה</label>
        <textarea id="duty-sched-notes" class="duty-input" rows="2"></textarea>
      </div>
      <div id="duty-sched-err" style="display:none;color:var(--danger);font-size:13px;padding:8px 12px;background:rgba(239,68,68,.1);border-radius:6px;"></div>
      <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" style="flex:1;" onclick="dutySaveSched()"><i class="bi bi-check-lg"></i> שמור</button>
        <button class="btn btn-ghost" onclick="dutyCloseSchedModal()">ביטול</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal: נציג ── -->
<div id="duty-rep-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:460px;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);">
      <div id="duty-rep-modal-title" style="font-size:15px;font-weight:700;"></div>
      <button type="button" onclick="dutyCloseRepModal()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
    </div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
      <input type="hidden" id="duty-rep-id">
      <div>
        <label class="duty-label">שם נציג *</label>
        <input type="text" id="duty-rep-name" class="duty-input">
      </div>
      <div>
        <label class="duty-label">מחלקה *</label>
        <select id="duty-rep-dept" class="duty-input">
          <option value="">בחר מחלקה</option>
          <option value="שירות לקוחות">שירות לקוחות</option>
          <option value="תמיכה טכנית">תמיכה טכנית</option>
          <option value="אינטרנט ותוכן">אינטרנט ותוכן</option>
        </select>
      </div>
      <div>
        <label class="duty-label">משתמש מערכת <span style="color:var(--text3);font-size:11px;">(ריק = נציג חיצוני)</span></label>
        <select id="duty-rep-user" class="duty-input">
          <option value="">— נציג חיצוני —</option>
        </select>
      </div>
      <div id="duty-rep-err" style="display:none;color:var(--danger);font-size:13px;padding:8px 12px;background:rgba(239,68,68,.1);border-radius:6px;"></div>
      <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" style="flex:1;" onclick="dutySaveRep()"><i class="bi bi-check-lg"></i> שמור</button>
        <button class="btn btn-ghost" onclick="dutyCloseRepModal()">ביטול</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal: הנחיה יומית ── -->
<div id="duty-guid-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:480px;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);">
      <div id="duty-guid-title" style="font-size:15px;font-weight:700;"></div>
      <button type="button" onclick="dutyCloseGuidModal()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
    </div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
      <input type="hidden" id="duty-guid-day">
      <div>
        <label class="duty-label">הנחיות</label>
        <textarea id="duty-guid-text" class="duty-input" rows="5" style="resize:vertical;"></textarea>
      </div>
      <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" style="flex:1;" onclick="dutySaveGuidance()"><i class="bi bi-check-lg"></i> שמור</button>
        <button class="btn btn-ghost" onclick="dutyCloseGuidModal()">ביטול</button>
      </div>
    </div>
  </div>
</div>

<style>
.duty-tab{background:none;border:none;border-bottom:2px solid transparent;padding:10px 18px;font-size:14px;font-weight:600;font-family:var(--font);color:var(--text2);cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:color .13s,border-color .13s;}
.duty-tab:hover{color:var(--text);}
.duty-tab.active{color:var(--accent);border-bottom-color:var(--accent);}
.duty-label{display:block;font-size:12px;color:var(--text2);margin-bottom:5px;font-weight:500;}
.duty-input{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--text);font-size:14px;font-family:var(--font);outline:none;transition:border-color .15s;box-sizing:border-box;}
.duty-input:focus{border-color:var(--accent);}
.dept-chip{display:inline-block;padding:2px 9px;border-radius:12px;font-size:11px;font-weight:600;}
.dept-service{background:rgba(239,68,68,.12);color:#ef4444;}
.dept-support{background:rgba(91,141,238,.12);color:var(--accent);}
.dept-internet{background:rgba(34,197,94,.12);color:#22c55e;}
</style>

<script>
const DUTY_BASE    = '<?= $base ?>';
const DUTY_CSRF    = '<?= View::e($csrf) ?>';
const DUTY_CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;

const DEPT_INFO = {
  'שירות לקוחות': { cls:'dept-service',  icon:'bi-headset' },
  'תמיכה טכנית':  { cls:'dept-support',  icon:'bi-tools'   },
  'אינטרנט ותוכן':{ cls:'dept-internet', icon:'bi-wifi'    },
};
const STATUS_LABELS = { active:'פעיל', missed:'לא הגיע', replaced:'הוחלף' };
const STATUS_COLORS = { active:'var(--success)', missed:'var(--danger)', replaced:'var(--warning)' };
const DAYS_HE = { Sunday:'ראשון', Monday:'שני', Tuesday:'שלישי', Wednesday:'רביעי', Thursday:'חמישי', Friday:'שישי', Saturday:'שבת' };

function dutyEsc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function formatDate(str){ if(!str)return''; const[y,m,d]=str.split('-'); return`${d}/${m}/${y}`; }
function getSundayOf(date){ const d=new Date(date); d.setDate(d.getDate()-d.getDay()); return d.toISOString().split('T')[0]; }

// ── Tabs ──────────────────────────────────────────────────────────
function dutySetTab(tab) {
  document.querySelectorAll('.duty-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
  document.querySelectorAll('.duty-tab-panel').forEach(p => p.style.display = 'none');
  document.getElementById('duty-tab-' + tab).style.display = '';
  if (tab === 'schedule') dutyLoadSchedule();
  if (tab === 'reps')     dutyLoadReps();
  if (tab === 'guidance') dutyLoadGuidance();
}

// ── Schedule ──────────────────────────────────────────────────────
let _dutyRepsCache = null;

async function dutyLoadSchedule() {
  const el = document.getElementById('duty-schedule-content');
  try {
    const weeks = await fetch(DUTY_BASE + '/api/duty/schedule').then(r => r.json());
    if (!weeks.length) {
      el.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text3);">
        <i class="bi bi-calendar-x" style="font-size:32px;display:block;margin-bottom:10px;opacity:.35;"></i>
        אין תורנויות עדיין${DUTY_CAN_EDIT ? '<br><small>לחץ "שיבוץ אוטומטי" או "שיבוץ ידני" להוספה</small>' : ''}
      </div>`;
      return;
    }
    const todaySunday = getSundayOf(new Date());
    el.innerHTML = `<div class="card" style="padding:0;overflow:hidden;">
      <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <thead><tr style="background:var(--bg3);font-size:12px;font-weight:700;color:var(--text3);">
          <th style="padding:10px 16px;text-align:center;">שבוע</th>
          <th style="padding:10px 16px;text-align:center;">מחלקה</th>
          <th style="padding:10px 16px;text-align:center;">תורן</th>
          <th style="padding:10px 16px;text-align:center;">סטטוס</th>
          ${DUTY_CAN_EDIT ? '<th style="padding:10px 16px;width:60px;"></th>' : ''}
        </tr></thead>
        <tbody>${weeks.map(w => {
          const isCurrent = w.week_start === todaySunday;
          const isPast    = w.week_start < todaySunday;
          const dc = DEPT_INFO[w.department] || { cls:'', icon:'' };
          const statusColor = STATUS_COLORS[w.status] || 'var(--text3)';
          const rowBg = isCurrent ? 'rgba(91,141,238,.06)' : '';
          return `<tr style="border-bottom:1px solid var(--border);${rowBg ? 'background:'+rowBg+';' : ''}${isPast ? 'opacity:.5;' : ''}transition:background .12s;" onmouseenter="this.style.background='var(--bg3)'" onmouseleave="this.style.background='${rowBg}'">
            <td style="padding:12px 16px;text-align:center;">
              <span style="font-weight:600;">${formatDate(w.week_start)}</span>
              ${isCurrent ? '<span style="margin-right:8px;font-size:11px;background:rgba(91,141,238,.15);color:var(--accent);border:1px solid rgba(91,141,238,.3);border-radius:10px;padding:1px 8px;">השבוע</span>' : ''}
              ${isPast    ? '<span style="margin-right:8px;font-size:11px;color:var(--text3);"><i class="bi bi-lock-fill"></i></span>' : ''}
            </td>
            <td style="padding:12px 16px;text-align:center;"><span class="dept-chip ${dc.cls}"><i class="bi ${dc.icon}"></i> ${dutyEsc(w.department)}</span></td>
            <td style="padding:12px 16px;text-align:center;font-weight:${isPast ? '400' : '700'};color:${isPast ? 'var(--text2)' : 'var(--text)'};">${dutyEsc(w.rep_name)}</td>
            <td style="padding:12px 16px;text-align:center;"><span style="color:${statusColor};font-size:13px;font-weight:600;">${STATUS_LABELS[w.status]||w.status}</span>${w.notes ? `<div style="font-size:11px;color:var(--text3);margin-top:2px;">${dutyEsc(w.notes)}</div>` : ''}</td>
            ${DUTY_CAN_EDIT ? `<td style="padding:12px 16px;display:flex;gap:6px;align-items:center;justify-content:center;">
              ${!isPast ? `<button type="button" onclick="dutyOpenSchedModal(${w.id})" style="background:none;border:none;color:var(--text3);cursor:pointer;font-size:14px;" title="ערוך"><i class="bi bi-pencil-fill"></i></button>` : ''}
              ${w.week_start > todaySunday ? `<button type="button" onclick="dutyDeleteSched(${w.id},'${w.week_start}')" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:14px;opacity:.7;" title="מחק"><i class="bi bi-trash3-fill"></i></button>` : ''}
            </td>` : ''}
          </tr>`;
        }).join('')}</tbody>
      </table>
    </div>`;
  } catch(e) {
    el.innerHTML = '<div style="color:var(--danger);padding:20px;">שגיאה בטעינה</div>';
  }
}

async function dutyAutoAssign() {
  try {
    const res  = await fetch(DUTY_BASE + '/api/duty/schedule/auto', {
      method: 'POST', headers: {'X-CSRF-Token': DUTY_CSRF},
      body: new URLSearchParams({_csrf: DUTY_CSRF})
    });
    const data = await res.json();
    if (data.ok) {
      v2Toast(`שובץ: ${data.rep} (${data.dept}) לשבוע ${formatDate(data.week)} ✓`);
      dutyLoadSchedule();
    } else {
      alert(data.message || 'שגיאה');
    }
  } catch(e) { alert('שגיאה'); }
}

// ── Multi-auto modal ──────────────────────────────────────────────
function dutyOpenMultiAutoModal() {
  document.getElementById('duty-multi-weeks').value = '4';
  document.getElementById('duty-multi-log').style.display = 'none';
  document.getElementById('duty-multi-log').innerHTML = '';
  document.getElementById('duty-multi-err').style.display = 'none';
  document.getElementById('duty-multi-btn').disabled = false;
  document.getElementById('duty-multi-btn').innerHTML = '<i class="bi bi-magic"></i> בצע שיבוץ';
  document.getElementById('duty-multi-auto-modal').style.display = 'flex';
}
function dutyCloseMultiAutoModal() { document.getElementById('duty-multi-auto-modal').style.display = 'none'; }

async function dutyRunMultiAuto() {
  const weeks = parseInt(document.getElementById('duty-multi-weeks').value, 10);
  const errEl = document.getElementById('duty-multi-err');
  const logEl = document.getElementById('duty-multi-log');
  const btn   = document.getElementById('duty-multi-btn');
  if (!weeks || weeks < 1 || weeks > 52) { errEl.textContent = 'יש להזין מספר בין 1 ל-52'; errEl.style.display = 'block'; return; }
  errEl.style.display = 'none';
  logEl.style.display = 'block';
  logEl.innerHTML = '';
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> מבצע...';

  let success = 0;
  for (let i = 0; i < weeks; i++) {
    try {
      const res  = await fetch(DUTY_BASE + '/api/duty/schedule/auto', {
        method: 'POST', headers: {'X-CSRF-Token': DUTY_CSRF},
        body: new URLSearchParams({_csrf: DUTY_CSRF})
      });
      const data = await res.json();
      if (data.ok) {
        logEl.innerHTML += `<div style="color:var(--success,#22c55e);">✓ ${formatDate(data.week)} — ${dutyEsc(data.dept)} — ${dutyEsc(data.rep)}</div>`;
        success++;
      } else if (data.skipped) {
        logEl.innerHTML += `<div style="color:var(--text3);">— ${formatDate(data.week)}: כבר משובץ, דולג</div>`;
      } else {
        logEl.innerHTML += `<div style="color:var(--danger);">✗ שבוע ${i+1}: ${dutyEsc(data.message||'שגיאה')}</div>`;
        break;
      }
    } catch(e) {
      logEl.innerHTML += `<div style="color:var(--danger);">✗ שגיאת רשת</div>`;
      break;
    }
  }

  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-check-lg"></i> סגור';
  btn.onclick = () => { dutyCloseMultiAutoModal(); dutyLoadSchedule(); };
  if (success > 0) v2Toast(`שובצו ${success} שבועות ✓`);
}

// ── Manual modal ──────────────────────────────────────────────────
async function dutyOpenManualModal() {
  const reps = await dutyGetReps();
  const sel  = document.getElementById('duty-manual-rep');
  sel.innerHTML = '<option value="">בחר נציג</option>' +
    reps.map(r => `<option value="${r.id}">[${dutyEsc(r.department)}] ${dutyEsc(r.name)}</option>`).join('');
  // ברירת מחדל: יום ראשון הבא
  const day = new Date().getDay();
  const daysUntil = day === 0 ? 7 : 7 - day;
  const nextSun = new Date(); nextSun.setDate(nextSun.getDate() + daysUntil);
  document.getElementById('duty-manual-date').value = nextSun.toISOString().split('T')[0];
  document.getElementById('duty-manual-err').style.display = 'none';
  document.getElementById('duty-manual-modal').style.display = 'flex';
}

function dutyCloseManualModal() { document.getElementById('duty-manual-modal').style.display = 'none'; }

async function dutySaveManual() {
  const date  = document.getElementById('duty-manual-date').value;
  const repId = document.getElementById('duty-manual-rep').value;
  const errEl = document.getElementById('duty-manual-err');
  if (!date || !repId) { errEl.textContent = 'יש לבחור תאריך ונציג'; errEl.style.display = 'block'; return; }
  try {
    const res  = await fetch(DUTY_BASE + '/api/duty/schedule/manual', {
      method: 'POST', headers: {'X-CSRF-Token': DUTY_CSRF},
      body: new URLSearchParams({_csrf: DUTY_CSRF, week_start: date, representative_id: repId})
    });
    const data = await res.json();
    if (data.ok) { dutyCloseManualModal(); dutyLoadSchedule(); v2Toast('תורנות נשמרה ✓'); }
    else { errEl.textContent = data.error || 'שגיאה'; errEl.style.display = 'block'; }
  } catch(e) { errEl.textContent = 'שגיאת רשת'; errEl.style.display = 'block'; }
}

async function dutyDeleteSched(id, weekStart) {
  if (!confirm(`למחוק את התורנות של ${formatDate(weekStart)}?`)) return;
  const res  = await fetch(DUTY_BASE + '/api/duty/schedule/' + id + '/delete', {
    method: 'POST', headers: {'X-CSRF-Token': DUTY_CSRF},
    body: new URLSearchParams({_csrf: DUTY_CSRF})
  });
  const data = await res.json();
  if (data.ok) { dutyLoadSchedule(); v2Toast('תורנות נמחקה ✓'); }
  else alert(data.error || 'שגיאה');
}

// ── Edit existing schedule ─────────────────────────────────────────
async function dutyOpenSchedModal(id) {
  const weeks = await fetch(DUTY_BASE + '/api/duty/schedule').then(r => r.json());
  const w = weeks.find(x => x.id == id);
  if (!w) return;

  const reps = await dutyGetReps();
  const sel  = document.getElementById('duty-sched-rep');
  sel.innerHTML = reps.map(r => `<option value="${r.id}">[${dutyEsc(r.department)}] ${dutyEsc(r.name)}</option>`).join('');

  document.getElementById('duty-sched-id').value             = id;
  document.getElementById('duty-sched-rep').value            = w.rep_id;
  document.getElementById('duty-sched-status').value         = w.status || 'active';
  document.getElementById('duty-sched-notes').value          = w.notes || '';
  document.getElementById('duty-sched-modal-title').textContent = 'עריכת תורנות — ' + formatDate(w.week_start);
  document.getElementById('duty-sched-err').style.display    = 'none';
  document.getElementById('duty-sched-modal').style.display  = 'flex';
}

function dutyCloseSchedModal() { document.getElementById('duty-sched-modal').style.display = 'none'; }

async function dutySaveSched() {
  const id     = document.getElementById('duty-sched-id').value;
  const repId  = document.getElementById('duty-sched-rep').value;
  const status = document.getElementById('duty-sched-status').value;
  const notes  = document.getElementById('duty-sched-notes').value;
  const errEl  = document.getElementById('duty-sched-err');
  try {
    const res  = await fetch(DUTY_BASE + '/api/duty/schedule/' + id, {
      method: 'POST', headers: {'X-CSRF-Token': DUTY_CSRF},
      body: new URLSearchParams({_csrf: DUTY_CSRF, representative_id: repId, status, notes})
    });
    const data = await res.json();
    if (data.ok) { dutyCloseSchedModal(); dutyLoadSchedule(); v2Toast('תורנות עודכנה ✓'); }
    else { errEl.textContent = data.error || 'שגיאה'; errEl.style.display = 'block'; }
  } catch(e) { errEl.textContent = 'שגיאת רשת'; errEl.style.display = 'block'; }
}

// ── Reps ───────────────────────────────────────────────────────────
async function dutyGetReps() {
  if (!_dutyRepsCache) {
    const r = await fetch(DUTY_BASE + '/api/duty/reps');
    _dutyRepsCache = await r.json();
  }
  return _dutyRepsCache;
}

async function dutyLoadReps() {
  _dutyRepsCache = null;
  const el = document.getElementById('duty-reps-content');
  const reps = await dutyGetReps();
  if (!reps.length) {
    el.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3);">אין נציגים עדיין</div>';
    return;
  }
  const byDept = {};
  reps.forEach(r => { if (!byDept[r.department]) byDept[r.department] = []; byDept[r.department].push(r); });

  el.innerHTML = Object.entries(byDept).map(([dept, list]) => {
    const dc = DEPT_INFO[dept] || { cls:'', icon:'' };
    return `<div class="card" style="margin-bottom:16px;">
      <div class="card-header"><span class="dept-chip ${dc.cls}"><i class="bi ${dc.icon}"></i> ${dutyEsc(dept)}</span></div>
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead><tr style="color:var(--text3);font-size:11px;font-weight:700;border-bottom:1px solid var(--border);">
          <th style="padding:6px 10px;text-align:center;">#</th><th style="padding:6px 10px;text-align:center;">שם</th>
          <th style="padding:6px 10px;text-align:center;">סוג</th><th style="padding:6px 10px;text-align:center;">תורנויות</th>
          ${DUTY_CAN_EDIT ? '<th style="padding:6px 10px;"></th>' : ''}
        </tr></thead>
        <tbody>${list.map((r,i) => `<tr style="border-bottom:1px solid var(--border);">
          <td style="padding:8px 10px;text-align:center;color:var(--text3);">${i+1}</td>
          <td style="padding:8px 10px;text-align:center;font-weight:600;">${dutyEsc(r.name)}</td>
          <td style="padding:8px 10px;text-align:center;">${r.system_username && r.system_username.trim()
            ? `<span style="font-size:11px;background:var(--accent-dim);color:var(--accent);border-radius:8px;padding:1px 7px;">${dutyEsc(r.system_username)}</span>`
            : '<span style="font-size:11px;color:var(--text3);">חיצוני</span>'}</td>
          <td style="padding:8px 10px;text-align:center;font-weight:700;font-size:15px;">${r.total_duties}</td>
          ${DUTY_CAN_EDIT ? `<td style="padding:8px 10px;display:flex;gap:4px;justify-content:center;">
            <button type="button" class="btn btn-ghost" style="padding:3px 8px;font-size:12px;" onclick='dutyOpenRepModal(${JSON.stringify(r)})'><i class="bi bi-pencil-fill"></i></button>
            <button type="button" class="btn btn-ghost" style="padding:3px 8px;font-size:12px;color:var(--danger);" onclick="dutyDeleteRep(${r.id})"><i class="bi bi-trash3-fill"></i></button>
          </td>` : ''}
        </tr>`).join('')}</tbody>
      </table>
    </div>`;
  }).join('');
}

async function dutyOpenRepModal(rep) {
  const users = await fetch(DUTY_BASE + '/api/duty/users').then(r => r.json()).catch(() => []);
  const sel = document.getElementById('duty-rep-user');
  sel.innerHTML = '<option value="">— נציג חיצוני —</option>' +
    users.map(u => `<option value="${u.id}">${dutyEsc(u.full_name)}</option>`).join('');

  document.getElementById('duty-rep-id').value   = rep ? rep.id : '';
  document.getElementById('duty-rep-name').value = rep ? (rep.name || '') : '';
  document.getElementById('duty-rep-dept').value = rep ? (rep.department || '') : '';
  document.getElementById('duty-rep-user').value = rep ? (rep.user_id || '') : '';
  document.getElementById('duty-rep-err').style.display = 'none';
  document.getElementById('duty-rep-modal-title').textContent = rep ? 'עריכת נציג' : 'הוסף נציג';
  document.getElementById('duty-rep-modal').style.display = 'flex';

  // אם בחרו משתמש מערכת — מלא שם אוטומטית
  sel.onchange = function() {
    if (!this.value) return;
    const u = users.find(x => x.id == this.value);
    if (u && !document.getElementById('duty-rep-name').value)
      document.getElementById('duty-rep-name').value = u.full_name;
  };
}

function dutyCloseRepModal() { document.getElementById('duty-rep-modal').style.display = 'none'; }

async function dutySaveRep() {
  const id    = document.getElementById('duty-rep-id').value;
  const name  = document.getElementById('duty-rep-name').value.trim();
  const dept  = document.getElementById('duty-rep-dept').value;
  const uid   = document.getElementById('duty-rep-user').value;
  const errEl = document.getElementById('duty-rep-err');
  const url   = id ? DUTY_BASE + '/api/duty/reps/' + id : DUTY_BASE + '/api/duty/reps';
  try {
    const res  = await fetch(url, {
      method: 'POST', headers: {'X-CSRF-Token': DUTY_CSRF},
      body: new URLSearchParams({_csrf: DUTY_CSRF, name, department: dept, user_id: uid})
    });
    const data = await res.json();
    if (data.ok) { dutyCloseRepModal(); _dutyRepsCache = null; dutyLoadReps(); v2Toast(id ? 'נציג עודכן ✓' : 'נציג נוסף ✓'); }
    else { errEl.textContent = data.error || 'שגיאה'; errEl.style.display = 'block'; }
  } catch(e) { errEl.textContent = 'שגיאת רשת'; errEl.style.display = 'block'; }
}

async function dutyDeleteRep(id) {
  if (!confirm('למחוק נציג זה?')) return;
  await fetch(DUTY_BASE + '/api/duty/reps/' + id + '/delete', {
    method: 'POST', headers: {'X-CSRF-Token': DUTY_CSRF},
    body: new URLSearchParams({_csrf: DUTY_CSRF})
  });
  _dutyRepsCache = null;
  dutyLoadReps();
  v2Toast('נציג נמחק ✓');
}

// ── Guidance ───────────────────────────────────────────────────────
async function dutyLoadGuidance() {
  const el = document.getElementById('duty-guidance-content');
  try {
    const rows   = await fetch(DUTY_BASE + '/api/duty/guidance').then(r => r.json());
    const allDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'];
    const map    = {};
    rows.forEach(r => map[r.day_of_week] = r);
    el.innerHTML = `<div class="card" style="padding:0;overflow:hidden;">
      <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <thead><tr style="background:var(--bg3);font-size:11px;font-weight:700;color:var(--text3);">
          <th style="padding:10px 16px;width:100px;">יום</th>
          <th style="padding:10px 16px;">הנחיות</th>
          ${DUTY_CAN_EDIT ? '<th style="padding:10px 16px;width:60px;"></th>' : ''}
        </tr></thead>
        <tbody>${allDays.map(day => {
          const g = map[day];
          return `<tr style="border-bottom:1px solid var(--border);">
            <td style="padding:12px 16px;font-weight:700;">${DAYS_HE[day]||day}</td>
            <td style="padding:12px 16px;color:${g ? 'var(--text)' : 'var(--text3)'};">${g ? dutyEsc(g.guidance) : '—'}</td>
            ${DUTY_CAN_EDIT ? `<td style="padding:12px 16px;">
              <button style="background:none;border:none;color:var(--text3);cursor:pointer;font-size:14px;" onclick="dutyOpenGuidModal('${day}','${dutyEsc(g ? g.guidance : '')}')"><i class="bi bi-pencil-fill"></i></button>
            </td>` : ''}
          </tr>`;
        }).join('')}</tbody>
      </table>
    </div>`;
  } catch(e) { el.innerHTML = '<div style="color:var(--danger);padding:20px;">שגיאה בטעינה</div>'; }
}

function dutyOpenGuidModal(day, text) {
  document.getElementById('duty-guid-day').value  = day;
  document.getElementById('duty-guid-title').textContent = 'הנחיות ליום ' + (DAYS_HE[day]||day);
  document.getElementById('duty-guid-text').value = text;
  document.getElementById('duty-guid-modal').style.display = 'flex';
}
function dutyCloseGuidModal() { document.getElementById('duty-guid-modal').style.display = 'none'; }

async function dutySaveGuidance() {
  const day  = document.getElementById('duty-guid-day').value;
  const text = document.getElementById('duty-guid-text').value;
  const res  = await fetch(DUTY_BASE + '/api/duty/guidance', {
    method: 'POST', headers: {'X-CSRF-Token': DUTY_CSRF},
    body: new URLSearchParams({_csrf: DUTY_CSRF, day_of_week: day, guidance: text})
  });
  const data = await res.json();
  if (data.ok) { dutyCloseGuidModal(); dutyLoadGuidance(); v2Toast('הנחיות נשמרו ✓'); }
}

// ── Init ───────────────────────────────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    dutyCloseRepModal(); dutyCloseSchedModal();
    dutyCloseGuidModal(); dutyCloseManualModal();
  }
});
['duty-rep-modal','duty-sched-modal','duty-guid-modal','duty-manual-modal','duty-multi-auto-modal'].forEach(id => {
  document.getElementById(id).addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
  });
});

dutyLoadSchedule();
</script>
