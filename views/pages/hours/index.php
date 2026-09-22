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
  <input type="date" id="ha-date" value="<?= View::e(date('Y-m-d')) ?>">
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
</style>
