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
    'sick' => 'מחלה', 'duplicate_delete' => 'למחוק דיווחים כפולים',
    'duplicate_in' => 'כניסה כפולה', 'duplicate_out' => 'יציאה כפולה',
    'other' => 'אחר',
];
$DAYS = ['א׳','ב׳','ג׳','ד׳','ה׳','ו׳','ש׳'];
?>
<?php
// שורות פתוחות ושורות שהושלמו מוצגות בנפרד: ההושלמו נעולות
// לעריכה ויושבות בתוך אזור מקופל, כדי שהנציג יראה קודם מה נותר לו.
$openRows = array_values(array_filter($rows, fn($r) => $r['status'] !== 'filled'));
$doneRows = array_values(array_filter($rows, fn($r) => $r['status'] === 'filled'));

/** מרנדר שורת דיווח הניתנת לעריכה */
$renderOpen = function (array $r) use ($TYPES, $DAYS) {
    $dt      = Holidays::dayType($r['work_date']);
    $hol     = Holidays::get($r['work_date']);
    $ts      = strtotime($r['work_date']);
    // ננעלים רק שדות שכבר מולאו בידי המנהל
    $lockIn  = !empty($r['time_in'])  && (int)$r['created_by'] !== (int)$r['user_id'];
    $lockOut = !empty($r['time_out']) && (int)$r['created_by'] !== (int)$r['user_id'];
    $reqIn   = in_array($r['requires'], ['both','in'],  true);
    $reqOut  = in_array($r['requires'], ['both','out'], true);
    ?>
    <tr class="ht-row ht-day-<?= View::e($dt) ?>" data-id="<?= (int)$r['id'] ?>">
      <td><?= View::e(date('d/m', $ts)) ?></td>
      <td title="<?= View::e($hol['n'] ?? Holidays::label($dt)) ?>">
        <span class="ht-dw"><?= View::e($DAYS[(int)date('w', $ts)]) ?></span>
        <?php if ($hol): ?>
          <span class="ht-tag ht-tag-<?= View::e($hol['t']) ?>"><?= View::e($hol['n']) ?></span>
        <?php elseif ($dt === 'fri' || $dt === 'sat'): ?>
          <span class="ht-tag ht-tag-<?= View::e($dt) ?>"><?= View::e(Holidays::label($dt)) ?></span>
        <?php endif; ?>
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
        <span class="ht-tw">
          <input type="text" class="ht-time ht-in<?= $reqIn ? ' ht-req' : ' ht-opt' ?>"
                 inputmode="numeric" maxlength="5" placeholder="--:--"
                 value="<?= View::e(substr((string)$r['time_in'], 0, 5)) ?>"
                 <?= $lockIn ? 'readonly' : '' ?>>
          <?php if (!$lockIn): ?>
            <button type="button" class="ht-tbtn" tabindex="-1"
                    title="בחירת שעה"><i class="bi bi-clock"></i></button>
          <?php endif; ?>
        </span>
      </td>
      <td>
        <span class="ht-tw">
          <input type="text" class="ht-time ht-out<?= $reqOut ? ' ht-req' : ' ht-opt' ?>"
                 inputmode="numeric" maxlength="5" placeholder="--:--"
                 value="<?= View::e(substr((string)$r['time_out'], 0, 5)) ?>"
                 <?= $lockOut ? 'readonly' : '' ?>>
          <?php if (!$lockOut): ?>
            <button type="button" class="ht-tbtn" tabindex="-1"
                    title="בחירת שעה"><i class="bi bi-clock"></i></button>
          <?php endif; ?>
        </span>
      </td>
      <td><input type="text" class="ht-note" maxlength="500"
                 value="<?= View::e((string)$r['note']) ?>"></td>
      <td>
        <?php
          $need = ['both' => 'דרושות כניסה ויציאה',
                   'in'   => 'דרושה שעת כניסה',
                   'out'  => 'דרושה שעת יציאה'][$r['requires']] ?? '';
        ?>
        <div class="ht-actions">
          <span class="ht-need"><i class="bi bi-exclamation-circle"></i> <?= View::e($need) ?></span>
          <button type="button" class="ht-save" onclick="hoursSaveRow(<?= (int)$r['id'] ?>)">שמור</button>
        </div>
      </td>
    </tr>
    <?php
};

/** מרנדר שורה שהושלמה — טקסט בלבד, ללא שדות ניתנים לעריכה */
$renderDone = function (array $r) use ($TYPES, $DAYS) {
    $dt  = Holidays::dayType($r['work_date']);
    $hol = Holidays::get($r['work_date']);
    $ts  = strtotime($r['work_date']);
    ?>
    <tr class="ht-row ht-done ht-day-<?= View::e($dt) ?>" data-id="<?= (int)$r['id'] ?>">
      <td><?= View::e(date('d/m', $ts)) ?></td>
      <td title="<?= View::e($hol['n'] ?? Holidays::label($dt)) ?>">
        <span class="ht-dw"><?= View::e($DAYS[(int)date('w', $ts)]) ?></span>
        <?php if ($hol): ?>
          <span class="ht-tag ht-tag-<?= View::e($hol['t']) ?>"><?= View::e($hol['n']) ?></span>
        <?php elseif ($dt === 'fri' || $dt === 'sat'): ?>
          <span class="ht-tag ht-tag-<?= View::e($dt) ?>"><?= View::e(Holidays::label($dt)) ?></span>
        <?php endif; ?>
      </td>
      <td><?= View::e($TYPES[$r['entry_type']] ?? $r['entry_type']) ?></td>
      <td class="ht-v"><?= View::e(substr((string)$r['time_in'], 0, 5) ?: '—') ?></td>
      <td class="ht-v"><?= View::e(substr((string)$r['time_out'], 0, 5) ?: '—') ?></td>
      <td class="ht-n" title="<?= View::e((string)$r['note']) ?>"><?= View::e((string)$r['note']) ?></td>
      <td><span class="ht-ok"><i class="bi bi-check-lg"></i> הושלם</span></td>
    </tr>
    <?php
};
?>

<table class="hours-table" data-context="<?= View::e($context) ?>">
  <thead>
    <tr>
      <th>תאריך</th><th>יום</th><th>סוג</th><th>כניסה</th><th>יציאה</th><th>הערה</th><th></th>
    </tr>
  </thead>
  <tbody>
  <?php if (!$openRows): ?>
    <tr><td colspan="7" class="ht-empty">
      <?= $doneRows ? '🎉 כל השעות עודכנו — אין שורות פתוחות' : 'אין שורות לדיווח' ?>
    </td></tr>
  <?php endif; ?>
  <?php foreach ($openRows as $r) $renderOpen($r); ?>
  </tbody>
</table>

<?php if ($doneRows): ?>
  <details class="ht-arch">
    <summary>
      <i class="bi bi-check2-circle"></i>
      דיווחים שהושלמו
      <span class="ht-arch-c"><?= count($doneRows) ?></span>
      <span class="ht-arch-h">— נעולים לעריכה</span>
    </summary>
    <table class="hours-table ht-arch-t">
      <thead>
        <tr>
          <th>תאריך</th><th>יום</th><th>סוג</th><th>כניסה</th><th>יציאה</th><th>הערה</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($doneRows as $r) $renderDone($r); ?>
      </tbody>
    </table>
  </details>
<?php endif; ?>

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
        if (typeof hoursRefreshBadge === 'function') hoursRefreshBadge();
        /* שורה שהושלמה עוברת לאזור הנעול — מרעננים כדי שתעבור לשם */
        if (d.status === 'filled') {
            tr.classList.add('ht-done');
            btn.disabled = true;
            if (tr.closest('.hm-body')) { hoursOpenModal(); }
            else { setTimeout(function () { location.reload(); }, 600); }
        }
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
    if (!e.target.classList || !e.target.classList.contains('ht-time')) return;
    var d = e.target.value.replace(/\D/g, '').slice(0, 4);
    e.target.value = d.length > 2 ? d.slice(0, 2) + ':' + d.slice(2) : d;
    e.target.classList.remove('ht-bad');
});

/* ביציאה מהשדה — משלים לפורמט מלא ומסמן שגיאה */
document.addEventListener('blur', function (e) {
    if (!e.target.classList || !e.target.classList.contains('ht-time')) return;
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
        if (off) { f.value = ''; f.classList.remove('ht-bad'); }
        var b = f.parentNode && f.parentNode.querySelector('.ht-tbtn');
        if (b) b.disabled = off;
    });
    /* סיבת היעדרות סוגרת את השורה — אין יותר דרישת שעות להציג */
    var need = tr.querySelector('.ht-need');
    if (need) need.style.display = off ? 'none' : '';
});
}

/* ── בורר שעה: תיבת שעות ותיבת דקות, כל אחת נגללת ונבחרת בנפרד ──
   מוגן בשמירה משלו כדי שייטען גם כשהרכיב כבר נטען בעמוד אחר. */
if (!window.hoursTimePicker) {
window.hoursTimePicker = (function () {
    var pop = null, target = null, hEl = null, mEl = null;

    function build() {
        var el = document.createElement('div');
        el.className = 'ht-pop';

        var h = '<div class="ht-pop-hd">בחירת שעה</div>' +
                '<div class="ht-pop-live"><span id="ht-pv-h">--</span>:' +
                '<span id="ht-pv-m">--</span></div>' +
                '<div class="ht-pop-cols">';

        h += '<div class="ht-pop-col"><div class="ht-pop-lbl">שעה</div>' +
             '<div class="ht-pop-list" data-k="h">';
        for (var i = 0; i < 24; i++) {
            var v = ('0' + i).slice(-2);
            h += '<button type="button" class="ht-pop-i" data-h="' + v + '">' + v + '</button>';
        }
        h += '</div></div>';

        h += '<div class="ht-pop-col"><div class="ht-pop-lbl">דקות</div>' +
             '<div class="ht-pop-list" data-k="m">';
        for (var m = 0; m < 60; m++) {
            var v2 = ('0' + m).slice(-2);
            h += '<button type="button" class="ht-pop-i" data-m="' + v2 + '">' + v2 + '</button>';
        }
        h += '</div></div></div>';

        el.innerHTML = h;
        document.body.appendChild(el);

        hEl = el.querySelector('.ht-pop-list[data-k="h"]');
        mEl = el.querySelector('.ht-pop-list[data-k="m"]');

        /* מונע איבוד פוקוס מהשדה בזמן לחיצה בתוך הפופאובר */
        el.addEventListener('mousedown', function (ev) { ev.preventDefault(); });

        el.addEventListener('click', function (ev) {
            var b = ev.target.closest('.ht-pop-i');
            if (!b || !target) return;
            ev.stopPropagation();

            var cur = split(target.value);
            if (b.dataset.h !== undefined) cur[0] = b.dataset.h;
            if (b.dataset.m !== undefined) cur[1] = b.dataset.m;

            target.value = cur[0] + ':' + cur[1];
            target.classList.remove('ht-bad');
            mark(false);
        });
        return el;
    }

    /* ערך נוכחי של השדה, עם ברירות מחדל סבירות */
    function split(v) {
        var p = String(v || '').split(':');
        var hh = (p[0] || '').replace(/\D/g, '').slice(0, 2);
        var mm = (p[1] || '').replace(/\D/g, '').slice(0, 2);
        if (hh === '' || +hh > 23) hh = '09';
        if (mm === '' || +mm > 59) mm = '00';
        return [('0' + +hh).slice(-2), ('0' + +mm).slice(-2)];
    }

    function centre(list, item) {
        if (!list || !item) return;
        list.scrollTop = item.offsetTop - list.offsetTop
                       - (list.clientHeight / 2) + (item.offsetHeight / 2);
    }

    function mark(doScroll) {
        if (!pop || !target) return;
        var cur = split(target.value);
        var hasVal = !!String(target.value || '').trim();

        document.getElementById('ht-pv-h').textContent = hasVal ? cur[0] : '--';
        document.getElementById('ht-pv-m').textContent = hasVal ? cur[1] : '--';

        var selH = null, selM = null;
        pop.querySelectorAll('.ht-pop-i').forEach(function (b) {
            var on = (b.dataset.h !== undefined && b.dataset.h === cur[0]) ||
                     (b.dataset.m !== undefined && b.dataset.m === cur[1]);
            b.classList.toggle('ht-pop-on', on);
            if (on && b.dataset.h !== undefined) selH = b;
            if (on && b.dataset.m !== undefined) selM = b;
        });

        if (doScroll) {
            /* ממרכז את הפריט הנבחר; מחושב יחסית לרשימה עצמה ולא
               לאב הקדמון הממוקם, כדי שהמרכוז יהיה נכון בפתיחה הראשונה. */
            centre(hEl, selH);
            centre(mEl, selM);
        }
    }

    function open(input) {
        if (!pop) pop = build();
        target = input;

        /* מציגים תחילה מחוץ למסך: בלחיצה הראשונה הפופאובר עדיין
           display:none, ולכן offsetHeight היה 0 והמיקום/הגלילה יצאו שגויים. */
        pop.style.top = '-9999px';
        pop.style.left = '-9999px';
        pop.classList.add('open');

        var r = input.getBoundingClientRect();
        var ph = pop.offsetHeight, pw = pop.offsetWidth;   /* נמדד אחרי ההצגה */
        var top = r.bottom + 6, left = r.left;
        if (top + ph > window.innerHeight - 8)
            top = Math.max(8, r.top - ph - 6);
        if (left + pw > window.innerWidth - 8)
            left = Math.max(8, window.innerWidth - pw - 8);
        pop.style.top = top + 'px';
        pop.style.left = left + 'px';

        mark(true);
    }

    function close() { if (pop) pop.classList.remove('open'); target = null; }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.ht-tbtn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            var inp = btn.parentNode.querySelector('.ht-time');
            if (inp && !inp.readOnly && !inp.disabled) {
                (target === inp && pop && pop.classList.contains('open')) ? close() : open(inp);
            }
            return;
        }
        if (pop && !e.target.closest('.ht-pop')) close();
    });

    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    /* גלילה בתוך רשימות הבורר עצמו לא אמורה לסגור אותו —
       רק גלילה של העמוד שמתחתיו. */
    window.addEventListener('scroll', function (e) {
        if (pop && e.target && e.target.closest &&
            e.target.closest('.ht-pop')) return;
        close();
    }, true);

    return { open: open, close: close };
})();
}
</script>
