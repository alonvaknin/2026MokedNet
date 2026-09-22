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
  <label class="hd-inpw" for="ha-date">
    <i class="bi bi-calendar3"></i>
    <input type="date" id="ha-date" value="<?= View::e(date('Y-m-d')) ?>"
           max="<?= View::e(date('Y-m-d')) ?>">
  </label>
  <button type="button" class="btn btn-primary" onclick="hoursAddOwn()">+ הוסף שורה</button>
</div>

<script>
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

/* ── בורר תאריך — באותה שפה עיצובית של ווידג'ט היומן (.cal-inpw) ── */
.hd-inpw{display:flex;align-items:center;gap:6px;background:var(--bg4);
  border:1px solid var(--border);border-radius:7px;padding:0 9px;
  transition:border-color .13s,background .13s;cursor:pointer}
.hd-inpw:hover{background:var(--accent-dim)}
.hd-inpw:focus-within{border-color:var(--accent);background:var(--bg4)}
.hd-inpw i{color:var(--text3);font-size:12px;flex-shrink:0;transition:color .13s}
.hd-inpw:focus-within i,.hd-inpw:hover i{color:var(--accent)}
#ha-date{background:none;border:none;outline:none;color:var(--text);
  font-family:var(--font);font-size:13px;font-weight:600;padding:8px 0;
  direction:ltr;text-align:center;cursor:pointer;color-scheme:dark}
/* אייקון הלוח המובנה של הדפדפן — מוסתר לטובת האייקון שלנו */
#ha-date::-webkit-calendar-picker-indicator{opacity:0;position:absolute;
  inset:0;width:100%;height:100%;cursor:pointer}
.hd-inpw{position:relative}
</style>
