<?php
declare(strict_types=1);
use Core\View;
use Core\Holidays;

/** @var array $rows */
/** @var string $context */
$context = $context ?? 'page';
$rows    = $rows ?? [];
$TYPES = [
    'regular' => 'רגיל', 'vacation' => 'חופש', 'reserve' => 'מילואים',
    'sick' => 'מחלה', 'duplicate_delete' => 'למחוק דיווחים כפולים', 'other' => 'אחר',
];
$DAYS = ['א׳','ב׳','ג׳','ד׳','ה׳','ו׳','ש׳'];
?>
<table class="hours-table" data-context="<?= View::e($context) ?>">
  <thead>
    <tr>
      <th>תאריך</th><th>יום</th><th>סוג</th><th>כניסה</th><th>יציאה</th><th>הערה</th><th></th>
    </tr>
  </thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="7" class="ht-empty">אין שורות לדיווח</td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $r):
      $dt      = Holidays::dayType($r['work_date']);
      $hol     = Holidays::get($r['work_date']);
      $ts      = strtotime($r['work_date']);
      $done    = $r['status'] === 'filled';
      // ננעלים רק שדות שכבר מולאו בידי המנהל
      $lockIn  = !empty($r['time_in'])  && (int)$r['created_by'] !== (int)$r['user_id'] && !$done;
      $lockOut = !empty($r['time_out']) && (int)$r['created_by'] !== (int)$r['user_id'] && !$done;
      $reqIn   = in_array($r['requires'], ['both','in'],  true);
      $reqOut  = in_array($r['requires'], ['both','out'], true);
  ?>
    <tr class="ht-row ht-day-<?= View::e($dt) ?><?= $done ? ' ht-done' : '' ?>"
        data-id="<?= (int)$r['id'] ?>">
      <td><?= View::e(date('d/m', $ts)) ?></td>
      <td title="<?= View::e($hol['n'] ?? Holidays::label($dt)) ?>">
        <?= View::e($DAYS[(int)date('w', $ts)]) ?>
      </td>
      <td>
        <select class="ht-type">
          <?php foreach ($TYPES as $k => $lbl): ?>
            <option value="<?= View::e($k) ?>" <?= $r['entry_type'] === $k ? 'selected' : '' ?>>
              <?= View::e($lbl) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </td>
      <td>
        <input type="text" class="ht-time ht-in<?= $reqIn ? ' ht-req' : '' ?>"
               inputmode="numeric" maxlength="5" placeholder="--:--"
               value="<?= View::e(substr((string)$r['time_in'], 0, 5)) ?>"
               <?= $lockIn ? 'readonly' : '' ?>>
      </td>
      <td>
        <input type="text" class="ht-time ht-out<?= $reqOut ? ' ht-req' : '' ?>"
               inputmode="numeric" maxlength="5" placeholder="--:--"
               value="<?= View::e(substr((string)$r['time_out'], 0, 5)) ?>"
               <?= $lockOut ? 'readonly' : '' ?>>
      </td>
      <td><input type="text" class="ht-note" maxlength="500"
                 value="<?= View::e((string)$r['note']) ?>"></td>
      <td>
        <button type="button" class="ht-save" onclick="hoursSaveRow(<?= (int)$r['id'] ?>)">
          <?= $done ? '✓' : 'שמור' ?>
        </button>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<script>
/* נטען פעם אחת בלבד — הרכיב עשוי להופיע גם בעמוד וגם במודל */
if (!window.hoursSaveRow) {
window.hoursSaveRow = function (id) {
    var tr = document.querySelector('.hours-table tr[data-id="' + id + '"]');
    if (!tr) return;
    var btn = tr.querySelector('.ht-save');

    /* חוסם שמירה של שעה לא תקינה, לפני פנייה לשרת */
    var bad = false;
    ['.ht-in', '.ht-out'].forEach(function (sel) {
        var f = tr.querySelector(sel);
        if (f.disabled) return;
        if (window.hoursNormalizeTime(f.value) === null) { f.classList.add('ht-bad'); bad = true; }
    });
    if (bad) { showToast('שעה לא תקינה — פורמט HH:MM', 'error'); return; }

    btn.disabled = true;

    fetch(window.__V2_BASE + '/hours/entry/' + id + '/save', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.__CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({
            entry_type: tr.querySelector('.ht-type').value,
            time_in:    window.hoursNormalizeTime(tr.querySelector('.ht-in').value) || '',
            time_out:   window.hoursNormalizeTime(tr.querySelector('.ht-out').value) || '',
            note:       tr.querySelector('.ht-note').value
        })
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        btn.disabled = false;
        if (d.error) { showToast(d.error, 'error'); return; }
        showToast('נשמר', 'success');
        if (d.status === 'filled') { tr.classList.add('ht-done'); btn.textContent = '✓'; }
        if (typeof hoursRefreshBadge === 'function') hoursRefreshBadge();
    })
    .catch(function () { btn.disabled = false; showToast('שגיאת רשת', 'error'); });
};

/* ── שדות שעה: הקלדה חופשית עם מסכה ── */

/* 900 → 09:00 · 1730 → 17:30 · 9 → 09:00 · ריק נשאר ריק */
window.hoursNormalizeTime = function (v) {
    var d = (v || '').replace(/\D/g, '');
    if (!d) return '';
    if (d.length === 1) d = '0' + d + '00';        /* 9    → 0900 */
    else if (d.length === 2) d = d + '00';         /* 17   → 1700 */
    else if (d.length === 3) d = '0' + d;          /* 930  → 0930 */
    d = d.slice(0, 4);
    var h = parseInt(d.slice(0, 2), 10), m = parseInt(d.slice(2), 10);
    if (isNaN(h) || isNaN(m) || h > 23 || m > 59) return null;   /* לא תקין */
    return ('0' + h).slice(-2) + ':' + ('0' + m).slice(-2);
};

/* מכניס נקודתיים תוך כדי הקלדה, בלי להפריע למחיקה */
document.addEventListener('input', function (e) {
    if (!e.target.classList.contains('ht-time')) return;
    var d = e.target.value.replace(/\D/g, '').slice(0, 4);
    e.target.value = d.length > 2 ? d.slice(0, 2) + ':' + d.slice(2) : d;
    e.target.classList.remove('ht-bad');
});

/* ביציאה מהשדה — משלים לפורמט מלא ומסמן שגיאה */
document.addEventListener('blur', function (e) {
    if (!e.target.classList.contains('ht-time')) return;
    var v = window.hoursNormalizeTime(e.target.value);
    if (v === null) { e.target.classList.add('ht-bad'); return; }
    e.target.classList.remove('ht-bad');
    e.target.value = v;
}, true);

/* בחירת סיבת היעדרות מנטרלת את שדות השעות */
document.addEventListener('change', function (e) {
    if (!e.target.classList || !e.target.classList.contains('ht-type')) return;
    var tr = e.target.closest('tr');
    if (!tr) return;
    var off = e.target.value !== 'regular';
    ['.ht-in', '.ht-out'].forEach(function (sel) {
        var f = tr.querySelector(sel);
        if (!f) return;
        f.disabled = off;
        if (off) f.value = '';
    });
});
}
</script>
