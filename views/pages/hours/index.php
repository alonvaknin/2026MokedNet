<?php
declare(strict_types=1);
use Core\View;
/** @var array $rows */
/** @var string $month */
/** @var string $monthLabel */
/** @var string $prevMonth */
/** @var string $nextMonth */
$base = rtrim(CFG['app']['url'], '/');
?>
<div class="page-head" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div class="page-title" style="margin-bottom:0;"><i class="bi bi-clock-history" style="margin-left:8px;"></i>דיווח שעות</div>
  <div class="hours-nav">
    <a class="btn btn-ghost btn-sm" href="<?= View::e($base) ?>/hours?month=<?= View::e($prevMonth) ?>">▶</a>
    <span class="hours-month"><?= View::e($monthLabel) ?></span>
    <a class="btn btn-ghost btn-sm" href="<?= View::e($base) ?>/hours?month=<?= View::e($nextMonth) ?>">◀</a>
  </div>
</div>

<?php View::component('hours-table', ['rows' => $rows, 'context' => 'page']); ?>

<div class="hours-add">
  <div class="hd-wrap">
    <button type="button" class="hd-inpw" id="ha-open">
      <i class="bi bi-calendar3"></i>
      <span id="ha-label"><?= View::e(date('d/m/Y')) ?></span>
    </button>
    <input type="hidden" id="ha-date" value="<?= View::e(date('Y-m-d')) ?>">
    <div class="hd-panel" id="ha-panel"></div>
  </div>
  <button type="button" class="btn btn-primary" onclick="hoursAddOwn()">+ הוסף שורה</button>
</div>

<script>
/* ── בורר תאריך: חודש נוכחי בלבד, בשפת ווידג'ט היומן ── */
var HD_HOL = <?= json_encode(\Core\Holidays::all(), JSON_UNESCAPED_UNICODE) ?>;
var HD_M = ['ינואר','פברואר','מרץ','אפריל','מאי','יוני','יולי','אוגוסט',
            'ספטמבר','אוקטובר','נובמבר','דצמבר'];
var HD_D = ['א׳','ב׳','ג׳','ד׳','ה׳','ו׳','ש׳'];

function hdKey(d) {
    return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) +
           '-' + ('0' + d.getDate()).slice(-2);
}

function hdRender() {
    var sel = document.getElementById('ha-date').value;
    var base = sel ? new Date(sel + 'T00:00:00') : new Date();
    var y = base.getFullYear(), m = base.getMonth();
    var today = new Date(); today.setHours(0, 0, 0, 0);
    var tk = hdKey(today);

    var h = '<div class="hd-tb">' +
            '<button type="button" class="hd-nb" id="hd-prev">›</button>' +
            '<span class="hd-tit">' + HD_M[m] + ' ' + y + '</span>' +
            '<button type="button" class="hd-nb" id="hd-next">‹</button></div>';

    h += '<div class="hd-dhrow">';
    for (var i = 0; i < 7; i++)
        h += '<div class="hd-dh' + (i === 5 ? ' f' : i === 6 ? ' s' : '') + '">' + HD_D[i] + '</div>';
    h += '</div><div class="hd-grid">';

    var first = new Date(y, m, 1).getDay(), dim = new Date(y, m + 1, 0).getDate();
    for (var e = 0; e < first; e++) h += '<div class="hd-d emp"></div>';

    for (var day = 1; day <= dim; day++) {
        var d = new Date(y, m, day), k = hdKey(d), dw = d.getDay(), cls = 'hd-d';
        var hol = HD_HOL[k];
        if (dw === 6) cls += ' sat'; else if (dw === 5) cls += ' fri';
        if (hol) cls += (hol.t === 'h' || hol.t === 'i') ? ' hol'
                      : hol.t === 'e' ? ' erev' : hol.t === 'c' ? ' chol' : '';
        if (k === tk) cls += ' today';
        if (k === sel) cls += ' sel';
        if (k > tk) cls += ' off';               /* תאריך עתידי חסום */
        h += '<button type="button" class="' + cls + '" data-d="' + k + '"' +
             (hol ? ' title="' + hol.n.replace(/"/g, '&quot;') + '"' : '') +
             '>' + day + '</button>';
    }
    h += '</div>';

    var p = document.getElementById('ha-panel');
    p.innerHTML = h;

    /* ניווט חודשים — קדימה חסום מעבר לחודש הנוכחי */
    var nextBtn = document.getElementById('hd-next');
    if (y > today.getFullYear() || (y === today.getFullYear() && m >= today.getMonth()))
        nextBtn.disabled = true;

    document.getElementById('hd-prev').onclick = function (ev) {
        ev.stopPropagation(); hdShift(y, m - 1);
    };
    nextBtn.onclick = function (ev) { ev.stopPropagation(); hdShift(y, m + 1); };

    p.querySelectorAll('.hd-d[data-d]').forEach(function (b) {
        b.onclick = function (ev) {
            ev.stopPropagation();
            hdSet(b.dataset.d);
            document.querySelector('.hd-wrap').classList.remove('open');
        };
    });
}

/* מעבר חודש בלי לשנות את הבחירה בפועל */
function hdShift(y, m) {
    var d = new Date(y, m, 1);
    var cur = document.getElementById('ha-date').value;
    document.getElementById('ha-date').dataset.view = hdKey(d);
    var keep = cur;
    document.getElementById('ha-date').value = hdKey(d);
    hdRender();
    document.getElementById('ha-date').value = keep;
    /* מסמן מחדש את היום הנבחר אם הוא בחודש המוצג */
    var s = document.querySelector('.hd-d[data-d="' + keep + '"]');
    if (s) s.classList.add('sel');
}

function hdSet(k) {
    document.getElementById('ha-date').value = k;
    var p = k.split('-');
    document.getElementById('ha-label').textContent = p[2] + '/' + p[1] + '/' + p[0];
    hdRender();
}

document.getElementById('ha-open').addEventListener('click', function (e) {
    e.stopPropagation();
    var w = document.querySelector('.hd-wrap');
    w.classList.toggle('open');
    if (w.classList.contains('open')) hdRender();
});

document.addEventListener('click', function (e) {
    if (!e.target.closest('.hd-wrap'))
        document.querySelector('.hd-wrap').classList.remove('open');
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') document.querySelector('.hd-wrap').classList.remove('open');
});

function hoursAddOwn() {
    var d = document.getElementById('ha-date').value;
    if (!d) { showToast('בחר תאריך', 'warning'); return; }
    fetch(window.__V2_BASE + '/hours/entry/add', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.__CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({ work_date: d })
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (res.error) { showToast(res.error, 'error'); return; }
        location.reload();
    })
    .catch(function () { showToast('שגיאת רשת', 'error'); });
}
</script>

<style>
.hours-table{width:100%;border-collapse:collapse;margin-top:16px}
.hours-table th,.hours-table td{padding:8px 10px;text-align:right;border-bottom:1px solid var(--border,#2a2a3a)}
.hours-table th{color:var(--text3);font-weight:600;font-size:13px}
.hours-table input,.hours-table select{background:var(--bg2,#1a1a24);color:var(--text,#e6e6f0);
  border:1px solid var(--border,#2a2a3a);border-radius:6px;padding:5px 8px;font-family:inherit}
.hours-table input[readonly]{opacity:.5;cursor:not-allowed}
.hours-table input:disabled,.hours-table select:disabled{opacity:.35}
.ht-req{border-color:var(--accent,#7c5cff)!important}

/* ── שדות שעה — הקלדה חופשית, מונוספייס, ברורים ── */
.ht-time{width:76px;text-align:center;font-size:15px;font-weight:600;letter-spacing:.5px;
  font-family:'SF Mono',Consolas,'Courier New',monospace;direction:ltr;
  padding:7px 6px;border-radius:8px;transition:border-color .15s,box-shadow .15s,background .15s}
.ht-time:hover:not([readonly]):not(:disabled){border-color:var(--text3,#6b7280)}
.ht-time:focus{outline:none;border-color:var(--accent,#7c5cff);
  box-shadow:0 0 0 3px rgba(124,92,255,.18);background:var(--bg,#12121a)}
.ht-time::placeholder{color:var(--text3,#6b7280);font-weight:400;letter-spacing:1px}
.ht-time.ht-req{background:rgba(124,92,255,.07)}
.ht-time.ht-bad{border-color:#ef4444!important;box-shadow:0 0 0 3px rgba(239,68,68,.18)}
.ht-time[readonly]{background:rgba(255,255,255,.03);border-style:dashed}
.ht-day-fri,.ht-day-sat{background:rgba(255,255,255,.03)}
.ht-day-hol{background:rgba(245,158,11,.10)}
.ht-day-erev{background:rgba(251,191,36,.06)}
.ht-day-chol{background:rgba(217,119,6,.06)}
.ht-done{opacity:.65}
.ht-empty{text-align:center;color:var(--text3);padding:24px}
.ht-save{background:var(--accent,#7c5cff);color:#fff;border:0;border-radius:6px;
  padding:6px 14px;cursor:pointer;font-family:inherit}
.ht-save:disabled{opacity:.5;cursor:wait}
.hours-nav{display:flex;align-items:center;gap:12px}
.hours-month{font-weight:600;min-width:120px;text-align:center}
.hours-add{margin-top:20px;display:flex;gap:10px;align-items:center}

/* ── בורר תאריך — משכפל את נראות #cal-panel של ווידג'ט היומן ── */
.hd-wrap{position:relative}
.hd-inpw{display:flex;align-items:center;gap:6px;background:var(--bg4);
  border:1px solid var(--border);border-radius:7px;padding:8px 11px;
  color:var(--text);font-family:var(--font);font-size:13px;font-weight:600;
  cursor:pointer;transition:all .13s}
.hd-inpw:hover{background:var(--accent-dim);border-color:rgba(91,141,238,.4)}
.hd-inpw i{color:var(--text3);font-size:12px;transition:color .13s}
.hd-inpw:hover i,.hd-wrap.open .hd-inpw i{color:var(--accent)}
.hd-wrap.open .hd-inpw{border-color:var(--accent);background:var(--accent-dim)}

.hd-panel{position:absolute;top:calc(100% + 8px);right:0;z-index:409;display:none;
  background:var(--bg2);border:1px solid var(--border2);border-radius:14px;
  box-shadow:0 20px 60px rgba(0,0,0,.6);direction:rtl;font-family:var(--font);
  padding:14px 14px 16px}
.hd-wrap.open .hd-panel{display:block}
.hd-tb{display:flex;align-items:center;gap:6px;margin-bottom:11px}
.hd-nb{width:34px;height:34px;border:1px solid var(--border);border-radius:9px;
  background:var(--bg4);color:var(--text2);cursor:pointer;display:flex;
  align-items:center;justify-content:center;font-size:15px;transition:all .13s}
.hd-nb:hover{background:var(--accent-dim);color:var(--accent)}
.hd-nb:active{transform:scale(.9)}
.hd-nb:disabled{opacity:.3;cursor:default;pointer-events:none}
.hd-tit{font-size:14px;font-weight:700;color:var(--text);flex:1;text-align:center;
  white-space:nowrap}
.hd-dhrow,.hd-grid{display:grid;grid-template-columns:repeat(7,42px);gap:3px}
.hd-dh{font-size:11px;font-weight:700;color:var(--text3);text-align:center;padding:4px 0}
.hd-dh.f{color:#f59e0b;opacity:.85}.hd-dh.s{color:#ef4444;opacity:.85}
.hd-d{width:42px;height:42px;display:flex;align-items:center;justify-content:center;
  font-size:15px;font-weight:500;border-radius:8px;cursor:pointer;
  border:1px solid transparent;color:var(--text2);position:relative;
  background:none;font-family:var(--font);user-select:none;
  transition:background .1s,color .1s}
.hd-d:hover{background:var(--accent-dim);color:var(--accent);border-color:rgba(91,141,238,.2)}
.hd-d.emp{visibility:hidden;pointer-events:none}
.hd-d.fri{color:#f59e0b}
.hd-d.sat{color:#ef4444;opacity:.6}
.hd-d.sat:hover{opacity:1}
.hd-d.hol{color:#f59e0b;font-weight:600;border-color:rgba(245,158,11,.35)}
.hd-d.erev{color:#fbbf24;border-style:dashed;border-color:rgba(251,191,36,.4)}
.hd-d.chol{color:#d97706}
.hd-d.off{opacity:.25;cursor:default;pointer-events:none}
.hd-d.today{box-shadow:inset 0 0 0 1px var(--accent)}
.hd-d.sel{background:var(--accent)!important;color:#fff!important;font-weight:700;
  box-shadow:0 2px 8px rgba(91,141,238,.4)}

/* ── בורר שעה — נראות #cal-panel ── */
.ht-tw{position:relative;display:inline-flex;align-items:center}
.ht-tbtn{position:absolute;left:4px;top:50%;transform:translateY(-50%);
  background:none;border:0;padding:2px 3px;line-height:1;cursor:pointer;
  color:var(--text3);font-size:11px;border-radius:4px;transition:color .13s,background .13s}
.ht-tbtn:hover{color:var(--accent);background:var(--accent-dim)}
.ht-tbtn:disabled{opacity:.25;pointer-events:none}
.ht-tw .ht-time{padding-left:22px}

.ht-pop{position:fixed;z-index:9500;display:none;background:var(--bg2);
  border:1px solid var(--border2);border-radius:14px;
  box-shadow:0 20px 60px rgba(0,0,0,.6);padding:11px;direction:rtl;
  font-family:var(--font)}
.ht-pop.open{display:block}
.ht-pop-hd{font-size:11px;font-weight:700;color:var(--text3);text-align:center;
  margin-bottom:6px}
.ht-pop-live{font-size:19px;font-weight:800;letter-spacing:1px;text-align:center;
  color:var(--accent);direction:ltr;margin-bottom:9px;
  font-family:'SF Mono',Consolas,monospace}
/* row-reverse: שעה משמאל ודקות מימין, בהתאמה לתצוגת ה-HH:MM שמעל */
.ht-pop-cols{display:flex;flex-direction:row-reverse;gap:9px}
.ht-pop-col{display:flex;flex-direction:column}
.ht-pop-lbl{font-size:10px;font-weight:700;color:var(--text3);text-align:center;
  padding:3px 0;margin-bottom:4px;background:var(--bg3);border-radius:5px}
.ht-pop-list{display:flex;flex-direction:column;gap:3px;width:62px;
  height:198px;overflow-y:auto;scrollbar-width:thin;
  scrollbar-color:var(--border2) transparent;padding:0 3px}
.ht-pop-list::-webkit-scrollbar{width:4px}
.ht-pop-list::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px}
.ht-pop-list::-webkit-scrollbar-track{background:transparent}
.ht-pop-i{height:30px;flex-shrink:0;display:flex;align-items:center;
  justify-content:center;font-size:14px;font-weight:600;border-radius:7px;
  cursor:pointer;border:1px solid transparent;color:var(--text2);
  background:var(--bg4);font-family:'SF Mono',Consolas,monospace;direction:ltr;
  transition:background .1s,color .1s}
.ht-pop-i:hover{background:var(--accent-dim);color:var(--accent);
  border-color:rgba(91,141,238,.25)}
.ht-pop-i.ht-pop-on{background:var(--accent);color:#fff;font-weight:800;
  box-shadow:0 2px 8px rgba(91,141,238,.45)}

/* ── דיווחים שהושלמו: נעולים, בתוך אזור מקופל ── */
.ht-arch{margin-top:18px;border:1px solid var(--border,#2a2a3a);border-radius:10px;
  background:var(--bg2,#1a1a24);overflow:hidden}
.ht-arch>summary{display:flex;align-items:center;gap:8px;cursor:pointer;
  padding:11px 14px;font-size:13px;font-weight:700;color:var(--text2);
  list-style:none;user-select:none;transition:background .13s}
.ht-arch>summary::-webkit-details-marker{display:none}
.ht-arch>summary:hover{background:rgba(34,197,94,.08);color:#86efac}
.ht-arch>summary i{color:#22c55e;font-size:15px}
.ht-arch[open]>summary{border-bottom:1px solid var(--border,#2a2a3a)}
.ht-arch-c{background:rgba(34,197,94,.22);color:#86efac;font-size:11px;
  font-weight:800;border-radius:10px;padding:1px 8px;min-width:20px;text-align:center}
.ht-arch-h{font-size:11px;font-weight:400;color:var(--text3)}
.ht-arch-t{margin:0}
.ht-arch-t th{font-size:10px;padding:6px 10px}
.ht-arch-t td{padding:7px 10px;font-size:13px}

/* ערכים בשורה נעולה — טקסט בלבד, לא שדות */
.ht-v{font-family:'SF Mono',Consolas,monospace;direction:ltr;text-align:center;
  font-weight:600;color:var(--text2)}
.ht-n{max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
  font-size:12px;color:var(--text3)}
.ht-ok{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;
  background:rgba(34,197,94,.18);color:#86efac;border-radius:20px;padding:3px 11px;
  white-space:nowrap}
.hours-table tr.ht-done{opacity:.8}
.hours-table tr.ht-done:hover{opacity:1}

/* ── תגית סוג יום בטבלת הנציג ── */
.ht-dw{font-weight:700}
.ht-tag{display:inline-block;font-size:9px;font-weight:800;border-radius:4px;
  padding:2px 6px;margin-inline-start:5px;white-space:nowrap;vertical-align:middle}
.ht-tag-h,.ht-tag-i{background:#4a3a08;color:#ffd97a}
.ht-tag-e{background:#3d3110;color:#fde68a}
.ht-tag-c{background:#402d0c;color:#f0b429}
.ht-tag-r{background:#33285c;color:#c4b5fd}
.ht-tag-fri{background:#4a3a08;color:#fcd34d}
.ht-tag-sat{background:#4a1616;color:#fca5a5}

/* ── שדה נדרש מודגש; שדה שאינו נדרש מושבת ומאופר ── */
.ht-time.ht-opt,.ht-time:disabled{opacity:.38;border-style:dashed;
  cursor:not-allowed;background:transparent}
.ht-tw:has(.ht-time:disabled) .ht-tbtn{display:none}
.ht-time.ht-req{border-color:var(--accent)!important;
  background:rgba(124,92,255,.10);box-shadow:0 0 0 1px rgba(124,92,255,.25)}

/* ── מה נדרש בשורה ── */
.ht-actions{display:flex;align-items:center;gap:9px;justify-content:flex-end}
.ht-need{display:inline-flex;align-items:center;gap:4px;font-size:10px;
  font-weight:700;color:#fcd34d;background:#4a3105;border-radius:4px;
  padding:3px 8px;white-space:nowrap}
.ht-need i{font-size:11px}

/* הסרת שורה שהנציג הוסיף */
.ht-del{width:32px;height:32px;flex-shrink:0;display:inline-flex;
  align-items:center;justify-content:center;border-radius:7px;cursor:pointer;
  background:transparent;border:1px solid rgba(239,68,68,.28);color:#e07a7a;
  font-size:13px;transition:background .13s,color .13s,border-color .13s}
.ht-del:hover{background:rgba(239,68,68,.16);border-color:rgba(239,68,68,.55);
  color:#fca5a5}
</style>
