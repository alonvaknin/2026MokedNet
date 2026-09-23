<?php
declare(strict_types=1);
use Core\View;
use Core\Holidays;
/** @var array $users */
/** @var array $grid */
/** @var array $list */
/** @var int $days */
/** @var string $month */
/** @var string $monthLabel */
/** @var string $prevMonth */
/** @var string $nextMonth */
/** @var int $markedCount */
$base = rtrim(CFG['app']['url'], '/');
$DAYS  = ['א','ב','ג','ד','ה','ו','ש'];
$today = date('Y-m-d');
// שמות מלאים לטבלה הרחבה; $ABS הוא הקיצור לתאי הרשת הצרים
$FULL = ['regular'=>'רגיל','vacation'=>'חופש','reserve'=>'מילואים',
         'sick'=>'מחלה','duplicate_delete'=>'מחיקת כפולים',
         'duplicate_in'=>'כניסה כפולה','duplicate_out'=>'יציאה כפולה','other'=>'אחר'];
$ABS  = ['vacation'=>'חופ׳','reserve'=>'מיל׳','sick'=>'מחל׳',
         'duplicate_delete'=>'כפל׳','duplicate_in'=>'כנ׳ כפ׳','duplicate_out'=>'יצ׳ כפ׳',
         'other'=>'אחר'];
?>
<div class="page-head" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div class="page-title" style="margin-bottom:0;"><i class="bi bi-calendar3" style="margin-left:8px;"></i>ניהול דיווח שעות</div>
  <div class="hours-nav">
    <a class="btn btn-ghost btn-sm" href="<?= View::e($base) ?>/hours/manage?month=<?= View::e($prevMonth) ?>">▶</a>
    <span class="hours-month"><?= View::e($monthLabel) ?></span>
    <a class="btn btn-ghost btn-sm" href="<?= View::e($base) ?>/hours/manage?month=<?= View::e($nextMonth) ?>">◀</a>
    <span class="hm-hint"><i class="bi bi-hand-index"></i> גרור על תאים לבחירה מרובה</span>
  </div>
</div>

<div class="hm-scroll">
<table class="hm-grid">
  <thead>
    <tr>
      <th class="hm-name">עובד</th>
      <?php for ($d = 1; $d <= $days; $d++):
          $date = sprintf('%s-%02d', $month, $d);
          $dt   = Holidays::dayType($date);
          $hol  = Holidays::get($date);
      ?>
        <th class="hm-d hm-day-<?= View::e($dt) ?><?= $hol ? ' hm-has-hol' : '' ?><?= $date === $today ? ' hm-today' : '' ?>"
            title="<?= View::e($hol['n'] ?? Holidays::label($dt)) ?><?= $date === $today ? ' · היום' : '' ?>">
          <span class="hm-hn<?= $hol ? '' : ' hm-hn-e' ?>"><?= View::e($hol['n'] ?? '') ?></span>
          <span class="hm-dn"><?= (int)$d ?></span>
          <span class="hm-dw"><?= View::e($DAYS[(int)date('w', strtotime($date))]) ?></span>
        </th>
      <?php endfor; ?>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td class="hm-name"><?= View::e((string)$u['full_name']) ?></td>
      <?php for ($d = 1; $d <= $days; $d++):
          $date = sprintf('%s-%02d', $month, $d);
          $dt   = Holidays::dayType($date);
          $rows = $grid[(int)$u['id']][$date] ?? [];
      ?>
        <td class="hm-cell hm-day-<?= View::e($dt) ?><?= $date === $today ? ' hm-today-c' : '' ?>"
            data-user="<?= (int)$u['id'] ?>" data-date="<?= View::e($date) ?>">
          <?php foreach ($rows as $r):
              $done = $r['status'] === 'filled';
              $cls  = $r['entry_type'] !== 'regular' ? 'hm-abs' : ($done ? 'hm-ok' : 'hm-wait');
              if ($r['entry_type'] !== 'regular') {
                  $txt = $ABS[$r['entry_type']] ?? '—';
              } else {
                  $txt = (substr((string)$r['time_in'], 0, 5) ?: '?') . '-' .
                         (substr((string)$r['time_out'], 0, 5) ?: '?');
              }
          ?>
            <span class="hm-chip <?= View::e($cls) ?>"
                  title="<?= View::e($txt) ?><?= $r['note'] ? ' · ' . $r['note'] : '' ?>">
              <?= View::e($txt) ?>
            </span>
          <?php endforeach; ?>
        </td>
      <?php endfor; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- ═══ טבלת הדרישות המרכזת ═══ -->
<?php
// "פתוחות" = ממתינות והושלמו יחד, כל עוד לא נסגרו. סגורות בלשונית נפרדת.
$openList = array_values(array_filter($list, fn($r) => empty($r['exported_at'])));
$closedL  = array_values(array_filter($list, fn($r) => !empty($r['exported_at'])));
$pending  = array_values(array_filter($openList, fn($r) => $r['status'] === 'requested'));
$marked   = array_values(array_filter($list,
    fn($r) => (int)$r['marked_for_export'] === 1 && empty($r['exported_at'])));
$REQ     = ['both' => 'כניסה ויציאה', 'in' => 'כניסה', 'out' => 'יציאה'];
?>
<div class="hl-wrap">
  <div class="hl-head">
    <h2>דרישות ודיווחים — <?= View::e($monthLabel) ?></h2>
    <div class="hl-exp">
      <span id="hm-marked" hidden><?= (int)$markedCount ?></span>
      <span class="hl-exp-n">נבחרו <b id="hl-marked"><?= (int)$markedCount ?></b> לייצוא</span>
      <button type="button" class="hl-close-b" onclick="hlCloseSelected()"
              title="סגירת השורות המסומנות ללא הורדת קובץ">
        <i class="bi bi-lock"></i> סגור מסומנות
      </button>
      <button type="button" class="hl-exp-b" onclick="hmExport()">
        <i class="bi bi-file-earmark-excel"></i> הורד וסגור
      </button>
    </div>
    <div class="hl-tabs">
      <button type="button" class="hl-tab on" data-f="open">
        פתוחות <span class="hl-c"><?= count($openList) ?></span>
      </button>
      <button type="button" class="hl-tab" data-f="pending">
        ממתינות לנציג <span class="hl-c"><?= count($pending) ?></span>
      </button>
      <button type="button" class="hl-tab" data-f="marked">
        לדיווח <span class="hl-c"><?= count($marked) ?></span>
      </button>
      <button type="button" class="hl-tab" data-f="closed">
        סגורות <span class="hl-c"><?= count($closedL) ?></span>
      </button>
      <button type="button" class="hl-tab" data-f="all">
        הכל <span class="hl-c"><?= count($list) ?></span>
      </button>
    </div>
  </div>

  <div class="hl-scroll">
  <table class="hl-table">
    <thead>
      <tr>
        <th class="hl-th-chk" title="סימון שורות לייצוא XLS">
          <label class="hl-chk-l" title="סמן/נקה את כל השורות המוצגות">
            <input type="checkbox" id="hl-all" class="hl-chk">
            <span>לדיווח</span>
          </label>
        </th>
        <th>עובד</th><th>תאריך</th><th>יום</th><th>סוג</th>
        <th>כניסה</th><th>יציאה</th><th>נדרש</th><th>הערה</th><th>סטטוס</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$list): ?>
      <tr><td colspan="11" class="hl-empty">אין דיווחים בחודש זה</td></tr>
    <?php endif; ?>
    <tr id="hl-none" style="display:none">
      <td colspan="11" class="hl-empty">אין שורות בתצוגה זו</td>
    </tr>
    <?php foreach ($list as $r):
        $ts   = strtotime($r['work_date']);
        $dt   = Holidays::dayType($r['work_date']);
        $hol  = Holidays::get($r['work_date']);
        $done = $r['status'] === 'filled';
        $mk     = (int)$r['marked_for_export'] === 1;
        $closed = !empty($r['exported_at']);
        $flags = ($closed ? 'closed' : 'open')
               . ($done ? ' filled' : ' pending')
               . ($mk && !$closed ? ' marked' : '');
    ?>
      <tr class="hl-row hl-day-<?= View::e($dt) ?><?= $closed ? ' hl-closed' : '' ?>"
          data-flags="<?= View::e($flags) ?>"
          data-id="<?= (int)$r['id'] ?>"
          data-user="<?= (int)$r['user_id'] ?>" data-date="<?= View::e($r['work_date']) ?>">
        <td class="hl-chk-td">
          <label class="hl-chk-l" title="סימון לדיווח">
            <input type="checkbox" class="hl-chk" <?= $mk ? 'checked' : '' ?>
                   <?= $closed ? 'disabled' : '' ?>>
          </label>
        </td>
        <td class="hl-name"><?= View::e((string)$r['full_name']) ?></td>
        <td class="hl-mono"><?= View::e(date('d/m', $ts)) ?></td>
        <td>
          <?= View::e($DAYS[(int)date('w', $ts)]) ?>
          <?php if ($hol): ?>
            <span class="hl-hol"><?= View::e($hol['n']) ?></span>
          <?php endif; ?>
        </td>
        <td><?= View::e($FULL[$r['entry_type']] ?? $r['entry_type']) ?></td>
        <td class="hl-mono"><?= View::e(substr((string)$r['time_in'], 0, 5) ?: '—') ?></td>
        <td class="hl-mono"><?= View::e(substr((string)$r['time_out'], 0, 5) ?: '—') ?></td>
        <td class="hl-req"><?= View::e($REQ[$r['requires']] ?? '') ?></td>
        <td class="hl-note" title="<?= View::e((string)$r['note']) ?>"><?= View::e((string)$r['note']) ?></td>
        <td>
          <?php if ($closed): ?>
            <span class="hl-st cl"><i class="bi bi-lock-fill"></i> נסגר</span>
          <?php else: ?>
            <span class="hl-st <?= $done ? 'ok' : 'wait' ?>"><?= $done ? 'הושלם' : 'ממתין' ?></span>
            <?php if ($mk): ?><span class="hl-st mk">לדיווח</span><?php endif; ?>
          <?php endif; ?>
        </td>
        <td class="hl-act">
          <?php if ($closed): ?>
            <button type="button" class="hl-rb" title="פתיחה מחדש"
                    onclick="hlReopen(<?= (int)$r['id'] ?>,event)"><i class="bi bi-unlock"></i></button>
          <?php else: ?>
            <button type="button" class="hl-cb" title="סגירת השורה"
                    onclick="hlClose(<?= (int)$r['id'] ?>,event)"><i class="bi bi-lock"></i></button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<script>
/* ── סגירת שורות ──
   שורה סגורה (exported_at מלא) נעולה לעריכה בשרת ובממשק, ואינה
   נכללת בייצוא הבא. הורדת הקובץ סוגרת את המסומנות אוטומטית. */
function hlPostIds(url, ids) {
    return fetch(window.__V2_BASE + url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.__CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: ids })
    }).then(function (r) { return r.json(); });
}

function hlClose(id, ev) {
    if (ev) ev.stopPropagation();
    if (!confirm('לסגור את השורה? לא יהיה ניתן לערוך אותה עד לפתיחה מחדש.')) return;
    hlPostIds('/hours/close', [id]).then(function (d) {
        if (d.error) { showToast(d.error, 'error'); return; }
        showToast('השורה נסגרה', 'success');
        location.reload();
    }).catch(function () { showToast('שגיאת רשת', 'error'); });
}

function hlReopen(id, ev) {
    if (ev) ev.stopPropagation();
    hlPostIds('/hours/reopen', [id]).then(function (d) {
        if (d.error) { showToast(d.error, 'error'); return; }
        showToast('השורה נפתחה מחדש', 'success');
        location.reload();
    }).catch(function () { showToast('שגיאת רשת', 'error'); });
}

function hlCloseSelected() {
    var ids = [].map.call(
        document.querySelectorAll('.hl-row:not(.hl-closed) .hl-chk:checked'),
        function (c) { return parseInt(c.closest('.hl-row').dataset.id, 10); });
    if (!ids.length) { showToast('לא נבחרו שורות לסגירה', 'warning'); return; }
    if (!confirm('לסגור ' + ids.length + ' שורות? לא יהיה ניתן לערוך אותן עד לפתיחה מחדש.')) return;
    hlPostIds('/hours/close', ids).then(function (d) {
        if (d.error) { showToast(d.error, 'error'); return; }
        showToast('נסגרו ' + d.closed + ' שורות', 'success');
        location.reload();
    }).catch(function () { showToast('שגיאת רשת', 'error'); });
}

/* שני מוני "לדיווח" (בפס העליון ובטבלה) מתעדכנים יחד */
function hlSetMarked(n) {
    ['hm-marked', 'hl-marked'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.textContent = n;
    });
}

/* סינון הטבלה המרכזת + סימון לדיווח ישירות ממנה */
(function () {
    var tabs = document.querySelectorAll('.hl-tab');
    var rows = document.querySelectorAll('.hl-row');

    function apply(f) {
        var shown = 0;
        rows.forEach(function (tr) {
            var fl = ' ' + (tr.dataset.flags || '') + ' ';
            var show = f === 'all'
                || (f === 'open'    && fl.indexOf(' open ')    !== -1)
                || (f === 'pending' && fl.indexOf(' pending ') !== -1
                                    && fl.indexOf(' open ')    !== -1)
                || (f === 'marked'  && fl.indexOf(' marked ')  !== -1)
                || (f === 'closed'  && fl.indexOf(' closed ')  !== -1);
            tr.style.display = show ? '' : 'none';
            if (show) shown++;
        });
        var e = document.getElementById('hl-none');
        if (e) e.style.display = shown ? 'none' : '';
    }

    tabs.forEach(function (b) {
        b.addEventListener('click', function () {
            tabs.forEach(function (x) { x.classList.remove('on'); });
            b.classList.add('on');
            apply(b.dataset.f);
        });
    });
    apply('open');

    /* צ'קבוקס "לדיווח" בשורות בלבד — הצ'קבוקס בכותרת (#hl-all) מטופל בנפרד */
    document.querySelectorAll('.hl-row .hl-chk').forEach(function (chk) {
        chk.addEventListener('change', function () {
            var tr = chk.closest('.hl-row');
            if (!tr) return;
            hmPost('/hours/mark', { id: parseInt(tr.dataset.id, 10), on: chk.checked })
              .then(function (res) {
                  if (res.error) { showToast(res.error, 'error'); chk.checked = !chk.checked; return; }
                  if (res.marked !== undefined) hlSetMarked(res.marked);
                  var fl = (tr.dataset.flags || '').replace(' marked', '');
                  tr.dataset.flags = fl + (chk.checked ? ' marked' : '');
              })
              .catch(function () { showToast('שגיאת רשת', 'error'); chk.checked = !chk.checked; });
        });
    });

    /* סמן/נקה הכל — פועל רק על השורות הגלויות בלשונית הנוכחית */
    var all = document.getElementById('hl-all');
    if (all) all.addEventListener('change', function () {
        var vis = [].filter.call(document.querySelectorAll('.hl-row'), function (tr) {
            return tr.style.display !== 'none';
        });
        vis.forEach(function (tr) {
            var chk = tr.querySelector('.hl-chk');
            if (chk && chk.checked !== all.checked) { chk.checked = all.checked;
                chk.dispatchEvent(new Event('change')); }
        });
    });

    /* לחיצה על שורה פותחת את מודל התא של אותו עובד/תאריך */
    document.querySelectorAll('.hl-row').forEach(function (tr) {
        tr.addEventListener('click', function (e) {
            /* כל תא הסימון והתא של הפעולות אינם פותחים את המודל */
            if (e.target.closest('.hl-chk-td') || e.target.closest('.hl-act')) return;
            if (tr.classList.contains('hl-closed')) {
                showToast('השורה נסגרה — יש לפתוח אותה מחדש כדי לערוך', 'warning');
                return;
            }
            hmOpenCell(parseInt(tr.dataset.user, 10), tr.dataset.date);
        });
    });
})();
</script>

<!-- מודל התא -->
<div id="hm-modal" class="hm-overlay" onclick="if(event.target===this)hmClose()">
  <div class="hm-box">
    <div class="hm-head">
      <h2 id="hm-title">—</h2>
      <button type="button" class="hm-x" onclick="hmClose()">✕</button>
    </div>

    <!-- אזור השורות — העיקר במודל -->
    <div class="hm-rows-wrap">
      <div class="hm-sec-hd">
        <i class="bi bi-list-check"></i> שורות לדיווח
        <span class="hm-sec-c" id="hm-rows-count"></span>
      </div>
      <div id="hm-rows"></div>
    </div>

    <!-- הוספת דרישה — מקופל, נפתח בלחיצה -->
    <details class="hm-add" open>
      <summary><i class="bi bi-plus-circle"></i> הוסף דרישות</summary>
      <div class="hm-add-body">
        <!-- כל שורת טיוטה עם שעות משלה — לעובד עם כמה משמרות באותו יום -->
        <div id="hm-drafts"></div>
        <button type="button" class="hm-more" onclick="hmAddDraft()">
          <i class="bi bi-plus-lg"></i> הוסף שורה נוספת
        </button>
        <div class="hm-add-foot">
          <div class="hm-req-hint" id="hm-req-hint"></div>
          <button type="button" class="btn btn-primary hm-add-btn" onclick="hmAddRequest()">
            <i class="bi bi-check-lg"></i> צור דרישות
          </button>
        </div>
      </div>
    </details>
  </div>
</div>

<script>
/* ── מסכת שדות שעה — זהה לזו שברכיב hours-table, מוגדרת כאן כי הרכיב
      אינו נטען בעמוד הניהול. ההגנה מונעת הגדרה כפולה אם שניהם ייטענו יחד. ── */
if (!window.hoursNormalizeTime) {

/* 900 → 09:00 · 1730 → 17:30 · 9 → 09:00 · ריק נשאר ריק · null = לא תקין */
window.hoursNormalizeTime = function (v) {
    var d = (v || '').replace(/\D/g, '');
    if (!d) return '';
    if (d.length === 1) d = '0' + d + '00';
    else if (d.length === 2) d = d + '00';
    else if (d.length === 3) d = '0' + d;
    d = d.slice(0, 4);
    var h = parseInt(d.slice(0, 2), 10), m = parseInt(d.slice(2), 10);
    if (isNaN(h) || isNaN(m) || h > 23 || m > 59) return null;
    return ('0' + h).slice(-2) + ':' + ('0' + m).slice(-2);
};

document.addEventListener('input', function (e) {
    if (!e.target.classList || !e.target.classList.contains('ht-time')) return;
    var d = e.target.value.replace(/\D/g, '').slice(0, 4);
    e.target.value = d.length > 2 ? d.slice(0, 2) + ':' + d.slice(2) : d;
    e.target.classList.remove('ht-bad');
});

document.addEventListener('blur', function (e) {
    if (!e.target.classList || !e.target.classList.contains('ht-time')) return;
    var v = window.hoursNormalizeTime(e.target.value);
    if (v === null) { e.target.classList.add('ht-bad'); return; }
    e.target.classList.remove('ht-bad');
    e.target.value = v;
}, true);

}

var HM_CTX = { user: 0, date: '', sel: [], dragging: false, moved: false };
var HM_TYPES = { regular:'רגיל', vacation:'חופש', reserve:'מילואים', sick:'מחלה',
                 duplicate_delete:'למחוק דיווחים כפולים',
                 duplicate_in:'כניסה כפולה', duplicate_out:'יציאה כפולה', other:'אחר' };

function hmEsc(s) {
    return String(s === null || s === undefined ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function hmPost(url, body) {
    return fetch(window.__V2_BASE + url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.__CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    }).then(function (r) { return r.json(); });
}

/* ── מודל תא בודד ── */
/* ── בורר שעה (משוכפל מרכיב הטבלה; מוגן מהגדרה כפולה) ── */
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

function hmOpenCell(uid, date) {
    HM_CTX.user = uid; HM_CTX.date = date;
    document.getElementById('hm-title').textContent = date;
    document.getElementById('hm-modal').classList.add('open');
    if (typeof hmResetDrafts === 'function') hmResetDrafts();
    hmLoadRows();
}

function hmLoadRows() {
    var box = document.getElementById('hm-rows');
    box.innerHTML = '<p class="hm-none">טוען…</p>';
    fetch(window.__V2_BASE + '/api/hours/cell?user_id=' + encodeURIComponent(HM_CTX.user) +
          '&date=' + encodeURIComponent(HM_CTX.date))
      .then(function (r) { return r.json(); })
      .then(function (d) {
          var cEl = document.getElementById('hm-rows-count');
          if (!d.rows || !d.rows.length) {
              if (cEl) cEl.textContent = '';
              box.innerHTML = '<p class="hm-none"><i class="bi bi-inbox"></i> ' +
                              'אין שורות ליום זה — ניתן להוסיף דרישה למטה</p>';
              return;
          }
          if (cEl) cEl.textContent = d.rows.length;
          box.innerHTML = d.rows.map(function (r, i) {
              return '<div class="hm-r" data-id="' + parseInt(r.id, 10) + '">' +
                '<span class="hm-rn">' + (i + 1) + '</span>' +
                '<select class="r-type">' + Object.keys(HM_TYPES).map(function (k) {
                    return '<option value="' + k + '"' + (r.entry_type === k ? ' selected' : '') + '>' +
                           hmEsc(HM_TYPES[k]) + '</option>'; }).join('') + '</select>' +
                '<span class="ht-tw"><input type="text" class="ht-time r-in" inputmode="numeric" maxlength="5" ' +
                  'placeholder="--:--" value="' + hmEsc((r.time_in || '').slice(0, 5)) + '"><button type="button" class="ht-tbtn" tabindex="-1" title="בחירת שעה"><i class="bi bi-clock"></i></button></span>' +
                '<span class="ht-tw"><input type="text" class="ht-time r-out" inputmode="numeric" maxlength="5" ' +
                  'placeholder="--:--" value="' + hmEsc((r.time_out || '').slice(0, 5)) + '"><button type="button" class="ht-tbtn" tabindex="-1" title="בחירת שעה"><i class="bi bi-clock"></i></button></span>' +
                '<select class="r-req">' +
                  ['both','in','out'].map(function (k) {
                      var lbl = { both:'שתיהן', in:'כניסה', out:'יציאה' }[k];
                      return '<option value="' + k + '"' + (r.requires === k ? ' selected' : '') + '>' +
                             lbl + '</option>'; }).join('') + '</select>' +
                '<input type="text" class="r-note" maxlength="500" value="' + hmEsc(r.note || '') +
                  '" placeholder="הערה">' +
                '<label class="r-mark" title="כלול בקובץ הייצוא"><input type="checkbox" class="r-chk"' +
                   (String(r.marked_for_export) === '1' ? ' checked' : '') + '><span>לדיווח</span></label>' +
                '<button type="button" class="r-save" onclick="hmSaveRow(' + parseInt(r.id, 10) + ')">' +
                  '<i class="bi bi-check-lg"></i> שמור</button>' +
                '<button type="button" class="r-del" title="מחיקת השורה" ' +
                  'onclick="hmDelRow(' + parseInt(r.id, 10) + ')"><i class="bi bi-trash3"></i></button>' +
                '</div>';
          }).join('');

          box.querySelectorAll('.r-chk').forEach(function (chk) {
              chk.addEventListener('change', function () {
                  var id = parseInt(chk.closest('.hm-r').dataset.id, 10);
                  hmPost('/hours/mark', { id: id, on: chk.checked }).then(function (res) {
                      if (res.marked !== undefined)
                          hlSetMarked(res.marked);
                  }).catch(function () { showToast('שגיאת רשת', 'error'); });
              });
          });
      })
      .catch(function () { box.innerHTML = '<p class="hm-none">שגיאה בטעינה</p>'; });
}

/* מחזיר HH:MM תקין, או null אם הערך שגוי (ואז מסמן את השדה) */
function hmTime(el) {
    var v = window.hoursNormalizeTime(el.value);
    if (v === null) { el.classList.add('ht-bad'); return null; }
    el.classList.remove('ht-bad');
    return v;
}

function hmSaveRow(id) {
    var r = document.querySelector('.hm-r[data-id="' + id + '"]');
    if (!r) return;
    var inEl = r.querySelector('.r-in'), outEl = r.querySelector('.r-out');
    var tin = hmTime(inEl), tout = hmTime(outEl);
    if (tin === null || tout === null) { showToast('שעה לא תקינה — פורמט HH:MM', 'error'); return; }

    hmPost('/hours/entry/' + id + '/update', {
        entry_type: r.querySelector('.r-type').value,
        time_in:    tin,
        time_out:   tout,
        requires:   r.querySelector('.r-req').value,
        note:       r.querySelector('.r-note').value
    }).then(function (d) {
        if (d.error) { showToast(d.error, 'error'); return; }
        showToast('נשמר', 'success');
        HM_CTX.dirty = true;
    }).catch(function () { showToast('שגיאת רשת', 'error'); });
}

function hmDelRow(id) {
    if (!confirm('למחוק את השורה?')) return;
    hmPost('/hours/entry/' + id + '/delete', {}).then(function (d) {
        if (d.error) { showToast(d.error, 'error'); return; }
        showToast('נמחק', 'success');
        HM_CTX.dirty = true;
        hmLoadRows();
    }).catch(function () { showToast('שגיאת רשת', 'error'); });
}

/* "דרוש" נגזר ממה שהמנהל מילא: מילא כניסה → דרושה יציאה,
   מילא יציאה → דרושה כניסה, לא מילא כלום → דרושות שתיהן. */
/* ── שורות טיוטה בטופס הדרישה ──
   כל שורה עומדת בפני עצמה: שעות, סימוני "דרוש" והערה משלה, כדי
   שאפשר יהיה ליצור כמה דרישות לאותו עובד ותאריך בפעולה אחת. */
var HM_DRAFT_SEQ = 0;

function hmDraftHtml(n) {
    return '<div class="hm-draft" data-d="d' + (++HM_DRAFT_SEQ) + '">' +
      '<span class="hm-draft-n">' + n + '</span>' +
      '<div class="hm-f-col">' +
        '<label class="hm-cb"><input type="checkbox" class="d-need-in"><span>דרושה כניסה</span></label>' +
        '<span class="ht-tw"><input type="text" class="ht-time d-in" inputmode="numeric" ' +
          'maxlength="5" placeholder="--:--">' +
          '<button type="button" class="ht-tbtn" tabindex="-1" title="בחירת שעה">' +
          '<i class="bi bi-clock"></i></button></span>' +
      '</div>' +
      '<div class="hm-f-col">' +
        '<label class="hm-cb"><input type="checkbox" class="d-need-out"><span>דרושה יציאה</span></label>' +
        '<span class="ht-tw"><input type="text" class="ht-time d-out" inputmode="numeric" ' +
          'maxlength="5" placeholder="--:--">' +
          '<button type="button" class="ht-tbtn" tabindex="-1" title="בחירת שעה">' +
          '<i class="bi bi-clock"></i></button></span>' +
      '</div>' +
      '<input type="text" class="d-note" maxlength="500" placeholder="הערה">' +
      '<button type="button" class="hm-draft-x" title="הסרת השורה" ' +
        'onclick="hmDelDraft(this)"><i class="bi bi-x-lg"></i></button>' +
    '</div>';
}

function hmRenumberDrafts() {
    var box = document.getElementById('hm-drafts');
    if (!box) return;
    var all = box.querySelectorAll('.hm-draft');
    [].forEach.call(all, function (d, i) {
        var n = d.querySelector('.hm-draft-n');
        if (n) n.textContent = i + 1;
        var x = d.querySelector('.hm-draft-x');
        if (x) x.style.visibility = all.length > 1 ? '' : 'hidden';   /* שורה יחידה לא מוסרת */
    });
}

function hmAddDraft() {
    var box = document.getElementById('hm-drafts');
    if (!box) return;
    box.insertAdjacentHTML('beforeend',
        hmDraftHtml(box.querySelectorAll('.hm-draft').length + 1));
    hmRenumberDrafts();
    hmReqHint();
}

function hmDelDraft(btn) {
    var box = document.getElementById('hm-drafts');
    var d = btn.closest('.hm-draft');
    if (!d || !box || box.querySelectorAll('.hm-draft').length <= 1) return;
    d.remove();
    hmRenumberDrafts();
    hmReqHint();
}

function hmResetDrafts() {
    var box = document.getElementById('hm-drafts');
    if (!box) return;
    HM_DRAFT_SEQ = 0;
    box.innerHTML = hmDraftHtml(1);
    hmRenumberDrafts();
    hmReqHint();
}

/* "דרוש" לשורה בודדת: סימון מפורש גובר, אחרת נגזר מהשעות שמולאו */
function hmDeriveReqFor(el, tin, tout) {
    var ci = el.querySelector('.d-need-in'), co = el.querySelector('.d-need-out');
    if ((ci && ci.checked) || (co && co.checked)) {
        if (ci.checked && co.checked) return 'both';
        return ci.checked ? 'in' : 'out';
    }
    if (tin && !tout) return 'out';
    if (tout && !tin) return 'in';
    return 'both';
}

function hmReqHint() {
    var el = document.getElementById('hm-req-hint');
    if (!el) return;

    if (HM_CTX.sel.length > 1) {
        el.innerHTML = '<i class="bi bi-info-circle"></i> בחירה מרובה (' +
            HM_CTX.sel.length + ' תאים) — כל נציג יתבקש להשלים ' +
            '<b>כניסה ויציאה</b>, ללא שעות מוקדמות';
        return;
    }

    var box = document.getElementById('hm-drafts');
    var rows = box ? box.querySelectorAll('.hm-draft') : [];
    if (!rows.length) { el.innerHTML = ''; return; }

    var LBL = { both: 'כניסה ויציאה', in: 'כניסה', out: 'יציאה' };
    var parts = [].map.call(rows, function (d, i) {
        var tin  = (d.querySelector('.d-in')  || {}).value || '';
        var tout = (d.querySelector('.d-out') || {}).value || '';
        var ci = d.querySelector('.d-need-in'), co = d.querySelector('.d-need-out');
        if (tin && tout && !(ci && ci.checked) && !(co && co.checked))
            return (i + 1) + ': דיווח מלא';
        return (i + 1) + ': ' + LBL[hmDeriveReqFor(d, tin, tout)];
    });

    el.innerHTML = '<i class="bi bi-info-circle"></i> ' +
        (rows.length > 1 ? 'ייווצרו ' + rows.length + ' שורות — ' : 'נדרש: ') +
        parts.join(' · ');
}

/* כל שינוי בטיוטות מרענן את ההסבר */
document.addEventListener('input',  function (e) {
    if (e.target.closest && e.target.closest('.hm-draft')) hmReqHint();
});
document.addEventListener('change', function (e) {
    if (e.target.closest && e.target.closest('.hm-draft')) hmReqHint();
});

function hmAddRequest() {
    /* בחירה מרובה → bulk. השרת אינו שומר שעות בנתיב הזה, ולכן
       נשלחת דרישה אחת "כניסה ויציאה" לכל תא שנבחר. */
    if (HM_CTX.sel.length > 1) {
        hmPost('/hours/request/bulk', {
            cells: HM_CTX.sel, requires: 'both',
            note: (document.querySelector('.hm-draft .d-note') || {}).value || ''
        }).then(function (d) {
            if (d.error) { showToast(d.error, 'error'); return; }
            showToast('נוצרו ' + d.created + ' דרישות', 'success');
            location.reload();
        }).catch(function () { showToast('שגיאת רשת', 'error'); });
        return;
    }

    if (!HM_CTX.user || !HM_CTX.date) { showToast('לא נבחר תא', 'warning'); return; }

    var box  = document.getElementById('hm-drafts');
    var rows = box ? [].slice.call(box.querySelectorAll('.hm-draft')) : [];
    if (!rows.length) { showToast('אין שורות להוספה', 'warning'); return; }

    /* אימות כל השורות לפני שליחה, כדי לא ליצור חלק מהן ואז להיכשל */
    var payload = [], bad = false;
    rows.forEach(function (d) {
        var inEl = d.querySelector('.d-in'), outEl = d.querySelector('.d-out');
        var tin = hmTime(inEl), tout = hmTime(outEl);
        if (tin === null || tout === null) { bad = true; return; }
        payload.push({
            user_id:   HM_CTX.user,
            work_date: HM_CTX.date,
            requires:  hmDeriveReqFor(d, tin, tout),
            time_in:   tin,
            time_out:  tout,
            note:      (d.querySelector('.d-note') || {}).value || ''
        });
    });
    if (bad) { showToast('שעה לא תקינה — פורמט HH:MM', 'error'); return; }

    /* נשלחות בזו אחר זו כדי שהשרת יקצה id נפרד לכל שורה */
    var made = 0, failed = null;
    function step(i) {
        if (i >= payload.length || failed) {
            if (failed) { showToast(failed, 'error'); return; }
            showToast(made > 1 ? 'נוצרו ' + made + ' שורות' : 'נוצרה דרישה', 'success');
            location.reload();
            return;
        }
        hmPost('/hours/request/add', payload[i]).then(function (d) {
            if (d.error) { failed = d.error; step(payload.length); return; }
            made++;
            step(i + 1);
        }).catch(function () { failed = 'שגיאת רשת'; step(payload.length); });
    }
    step(0);
}

function hmClose() {
    document.getElementById('hm-modal').classList.remove('open');
    hmClearSel();
    /* הרשת וטבלת הדרישות מרונדרות בשרת — רענון כדי שלא יוצגו נתונים ישנים */
    if (HM_CTX.dirty) location.reload();
}

/* ── בחירה מרובה בגרירה ──
   אין onclick על התא: הפתיחה נגזרת מ-mouseup בלבד, כדי שגרירה ולחיצה
   לא יתנגשו. תא יחיד (בלי גרירה) → מודל התא; יותר מאחד → מצב bulk. */
function hmClearSel() {
    HM_CTX.sel = [];
    document.querySelectorAll('.hm-cell.sel').forEach(function (c) { c.classList.remove('sel'); });
}

function hmAddSel(cell) {
    if (cell.classList.contains('sel')) return;
    cell.classList.add('sel');
    HM_CTX.sel.push({ user_id: parseInt(cell.dataset.user, 10), work_date: cell.dataset.date });
}

document.addEventListener('mousedown', function (e) {
    if (e.button !== 0) return;
    if (document.getElementById('hm-modal').classList.contains('open')) return;
    var c = e.target.closest ? e.target.closest('.hm-cell') : null;
    if (!c) return;
    e.preventDefault();                 /* מונע בחירת טקסט תוך כדי גרירה */
    HM_CTX.dragging = true;
    HM_CTX.moved    = false;
    hmClearSel();
    hmAddSel(c);
});

document.addEventListener('mouseover', function (e) {
    if (!HM_CTX.dragging) return;
    var c = e.target.closest ? e.target.closest('.hm-cell') : null;
    if (!c) return;
    if (!c.classList.contains('sel')) HM_CTX.moved = true;
    hmAddSel(c);
});

document.addEventListener('mouseup', function () {
    if (!HM_CTX.dragging) return;
    HM_CTX.dragging = false;

    if (HM_CTX.sel.length > 1) {
        document.getElementById('hm-title').textContent = 'נבחרו ' + HM_CTX.sel.length + ' תאים';
        document.getElementById('hm-rows').innerHTML =
            '<p class="hm-none">בחירה מרובה — ניתן להוסיף דרישה לכולם</p>';
        var c0 = document.getElementById('hm-rows-count');
        if (c0) c0.textContent = '';
        document.getElementById('hm-modal').classList.add('open');
        if (typeof hmResetDrafts === 'function') hmResetDrafts();
        return;
    }

    /* תא יחיד — לחיצה רגילה */
    var one = HM_CTX.sel[0];
    hmClearSel();
    if (one) hmOpenCell(one.user_id, one.work_date);
});

function hmExport() {
    var n = parseInt(document.getElementById('hm-marked').textContent, 10);
    if (!n) { showToast('לא נבחרו שורות לדיווח', 'warning'); return; }
    if (!confirm('להוריד ' + n + ' שורות ולסגור אותן? שורה סגורה אינה ניתנת לעריכה.')) return;
    location.href = window.__V2_BASE + '/hours/export';
    /* השרת סוגר את השורות בזמן ההורדה — מרעננים כדי שהמצב החדש יוצג */
    setTimeout(function () { location.reload(); }, 2500);
}
</script>

<style>
.hours-nav{display:flex;align-items:center;gap:12px}
.hours-month{font-weight:600;min-width:120px;text-align:center}
.hm-hint{display:inline-flex;align-items:center;gap:5px;color:var(--text3);
  font-size:12px;white-space:nowrap;margin-inline-start:14px}
.hm-hint i{font-size:13px;opacity:.8}
/* חלון בגודל קבוע: הטבלה גוללת בשני הצירים בתוכו, והכותרת
   נדבקת ביחס למכל הזה (top:0) ולא ביחס לעמוד — כך היא עובדת
   בלי תלות ב-overflow של body. */
.hm-scroll{overflow:auto;max-width:100%;
  height:clamp(420px, calc(100vh - 230px), 900px);
  border:1px solid var(--border,#2a2a3a);border-radius:8px;
  scrollbar-width:thin;scrollbar-color:var(--border2) var(--bg3,#15151f);
  overscroll-behavior:contain}
.hm-scroll::-webkit-scrollbar{height:10px;width:10px}
.hm-scroll::-webkit-scrollbar-track{background:var(--bg3,#15151f);border-radius:10px}
.hm-scroll::-webkit-scrollbar-thumb{background:var(--border2,#3a3a4a);border-radius:10px;
  border:2px solid var(--bg3,#15151f)}
.hm-scroll::-webkit-scrollbar-thumb:hover{background:var(--accent)}
/* separate ולא collapse: בתאים דביקים המסגרות נעלמות תחת collapse */
.hm-grid{border-collapse:separate;border-spacing:0;font-size:12px}
/* עם border-spacing:0 מסגרת מלאה בכל תא מוכפלת — לכן רק שני צדדים */
.hm-grid th,.hm-grid td{border-bottom:1px solid var(--border,#2a2a3a);
  border-left:1px solid var(--border,#2a2a3a);padding:2px 4px;text-align:center}
.hm-grid tr th:first-child,.hm-grid tr td:first-child{border-left:0}
.hm-grid thead th{border-top:1px solid var(--border,#2a2a3a)}
.hm-name{position:sticky;right:0;z-index:2;background:var(--bg,#12121a);
  text-align:right!important;min-width:130px;white-space:nowrap}
/* הכותרת נדבקת לראש החלון הגולל */
.hm-grid thead th{position:sticky;top:0;z-index:3;
  background:var(--bg,#12121a)}
.hm-d{min-width:58px;vertical-align:bottom;padding:3px 2px!important}

/* היום הנוכחי בלוח */
.hm-grid thead th.hm-today{background:var(--accent)!important;
  box-shadow:inset 0 -3px 0 #fff}
.hm-grid thead th.hm-today .hm-dn,.hm-grid thead th.hm-today .hm-dw,
.hm-grid thead th.hm-today .hm-hn{color:#fff!important;opacity:1}
td.hm-cell.hm-today-c{box-shadow:inset 0 0 0 2px var(--accent);
  background:rgba(91,141,238,.10)}

/* מספור השורות במודל התא */
.hm-rn{display:inline-flex;align-items:center;justify-content:center;
  width:24px;height:24px;flex-shrink:0;border-radius:50%;
  background:var(--bg4);border:1px solid var(--border2,#3a3a4a);
  font-size:11px;font-weight:800;color:var(--text3)}

/* כמות שורות ליצירה */
/* פינת "עובד" נדבקת בשני הצירים, ולכן גוברת על שאר הכותרת */
.hm-grid thead th.hm-name{z-index:6;top:0;right:0}
/* ══ כותרת הימים — עיצוב אחיד לכל סוגי הימים ══
   מספר היום תמיד באותו גודל, משקל וצבע בהיר, כך שהוא קריא
   בכל עמודה. סוג היום נמסר דרך רקע הכותרת ואות היום בלבד. */
.hm-grid thead th{background:#171a24}
.hm-dn{display:block;font-size:15px;font-weight:800;color:#f1f4fa;
  line-height:1.15;letter-spacing:.2px}
.hm-dw{display:block;font-size:10px;font-weight:700;color:#9aa4bb;opacity:1}

/* רקע הכותרת לפי סוג היום — אטום, כדי שהתוכן הנגלל לא ייראה מבעדו */
.hm-grid thead th.hm-day-fri{background:#12161f}
.hm-grid thead th.hm-day-sat{background:#0e1119}
.hm-grid thead th.hm-day-hol{background:#33280f}
.hm-grid thead th.hm-day-erev{background:#292110}
.hm-grid thead th.hm-day-chol{background:#2c2411}

/* אות היום נושאת את הצבע המבחין */
th.hm-day-fri .hm-dw{color:#fbbf24}
th.hm-day-sat .hm-dw{color:#f87171}
th.hm-day-hol .hm-dw,th.hm-day-erev .hm-dw,th.hm-day-chol .hm-dw{color:#fcd34d}

.hm-cell{min-width:58px;height:40px;cursor:pointer;vertical-align:top;user-select:none;
  padding:2px 3px!important}
.hm-cell:hover{outline:1px solid var(--accent,#7c5cff)}
.hm-cell.sel{background:rgba(124,92,255,.25)!important}
/* צ'יפ הדיווח בתא — גדול וברור מספיק לקריאה מהירה */
.hm-chip{display:block;font-size:11px;font-weight:700;letter-spacing:.2px;
  border-radius:5px;padding:3px 4px;margin:2px 0;white-space:nowrap;
  border-inline-start:3px solid transparent;
  font-family:'SF Mono',Consolas,monospace;direction:ltr}
.hm-ok{background:#14351f;color:#7ee2a8;border-inline-start-color:#22c55e}
.hm-wait{background:#4a3105;color:#ffd97a;border-inline-start-color:#f59e0b;
  animation:hmPulse 2.4s ease-in-out infinite}
.hm-abs{background:#152a4d;color:#9cc4fb;border-inline-start-color:#3b82f6;
  font-family:var(--font);direction:rtl}
@keyframes hmPulse{0%,100%{border-inline-start-color:#f59e0b}
  50%{border-inline-start-color:#fbbf24}}
/* שישי/שבת — ימי מנוחה, מעומעמים כדי שלא יתחרו על תשומת הלב */
.hm-day-fri,.hm-day-sat{background:rgba(0,0,0,.28)}
/* תאי שישי/שבת מובחנים ברקע הכהה בלבד. אין עליהם opacity:
   הוא היה מחליש גם צ'יפים שיושבים בתוכם, ואי אפשר לבטל זאת מהילד. */
.hm-day-sat{background:rgba(0,0,0,.38)}

.hm-day-hol{background:rgba(245,158,11,.12)}
.hm-day-erev{background:rgba(251,191,36,.07)}
.hm-day-chol{background:rgba(217,119,6,.07)}

/* שורת שם החג מוצגת רק כשיש חג בעמודה; קודם היא שמרה מקום
   בכל העמודות ויצרה פס ריק בראש הטבלה */
.hm-hn{font-size:8px;font-weight:700;line-height:1.15;
  padding:1px 1px 0;color:#fcd34d;max-height:20px;
  overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;
  -webkit-box-orient:vertical;word-break:break-word}
.hm-hn-e{display:none}
.hm-day-erev .hm-hn{color:#fde68a}
.hm-day-chol .hm-hn{color:#f0b429}
.hm-has-hol{border-bottom:2px solid rgba(245,158,11,.45)}

/* מודל */
.hm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9000;
  align-items:flex-start;justify-content:center;padding-top:60px}
.hm-overlay.open{display:flex}
.hm-box{background:var(--bg,#12121a);border:1px solid var(--border,#2a2a3a);border-radius:14px;
  width:min(920px,94vw);max-height:80vh;overflow:auto;padding:20px}
.hm-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.hm-head h2{margin:0;font-size:18px}
.hm-x{background:none;border:0;color:var(--text3);font-size:20px;cursor:pointer}
/* ── אזור השורות: העיקר במודל ── */
.hm-rows-wrap{background:var(--bg2,#1a1a24);border:1px solid var(--border,#2a2a3a);
  border-radius:10px;padding:12px 13px 13px;margin-bottom:16px}
.hm-sec-hd{display:flex;align-items:center;gap:7px;font-size:13px;font-weight:700;
  color:var(--text2);margin-bottom:11px}
.hm-sec-hd i{color:var(--accent);font-size:14px}
.hm-sec-c{background:var(--accent);color:#fff;font-size:11px;font-weight:800;
  border-radius:10px;padding:1px 8px;min-width:20px;text-align:center}
#hm-rows{max-height:46vh;overflow-y:auto;scrollbar-width:thin;
  scrollbar-color:var(--border2) transparent}
#hm-rows::-webkit-scrollbar{width:5px}
#hm-rows::-webkit-scrollbar-thumb{background:var(--border2);border-radius:5px}

.hm-r{display:flex;gap:8px;align-items:center;flex-wrap:wrap;
  background:var(--bg,#12121a);border:1px solid var(--border,#2a2a3a);
  border-radius:9px;padding:9px 11px;margin-bottom:8px;transition:border-color .13s}
.hm-r:last-child{margin-bottom:0}
.hm-r:hover{border-color:var(--border2,#3a3a4a)}
.hm-r select{background:var(--bg4);color:var(--text);border:1px solid var(--border);
  border-radius:7px;padding:7px 9px;font-family:var(--font);font-size:13px}

/* צ'קבוקס "לדיווח" — מודגש וברור */
.r-mark{display:inline-flex;align-items:center;gap:6px;cursor:pointer;
  background:var(--bg4);border:1px solid var(--border);border-radius:20px;
  padding:6px 13px 6px 11px;font-size:12px;font-weight:700;color:var(--text3);
  white-space:nowrap;transition:all .13s;user-select:none}
.r-mark:hover{border-color:rgba(124,92,255,.5);color:var(--text2)}
.r-mark input{width:17px;height:17px;cursor:pointer;accent-color:var(--accent);margin:0}
.r-mark:has(input:checked){background:#3b2a78;
  border-color:#7c5cff;color:#ddd6fe}

/* שמירה — הפעולה הראשית */
.r-save{display:inline-flex;align-items:center;gap:6px;background:var(--accent);
  color:#fff;border:0;border-radius:8px;padding:9px 18px;cursor:pointer;
  font-family:var(--font);font-size:13px;font-weight:700;white-space:nowrap;
  box-shadow:0 2px 8px rgba(91,141,238,.32);transition:all .13s}
.r-save:hover{filter:brightness(1.12);box-shadow:0 4px 14px rgba(91,141,238,.45)}
.r-save:active{transform:scale(.96)}
.r-save i{font-size:14px}

/* מחיקה — הרסני, ולכן מובחן ולא צועק */
.r-del{display:inline-flex;align-items:center;justify-content:center;
  width:38px;height:38px;background:rgba(239,68,68,.10);
  border:1px solid rgba(239,68,68,.30);border-radius:8px;cursor:pointer;
  color:#f87171;font-size:15px;transition:all .13s;flex-shrink:0}
.r-del:hover{background:rgba(239,68,68,.22);border-color:rgba(239,68,68,.6);
  color:#fca5a5}
.r-del:active{transform:scale(.92)}

/* ── הוספת דרישה — מקופל בתחתית ── */
.hm-add{border:1px solid var(--border,#2a2a3a);border-radius:10px;
  background:var(--bg2,#1a1a24);overflow:hidden}
.hm-add>summary{display:flex;align-items:center;gap:8px;cursor:pointer;
  padding:11px 13px;font-size:13px;font-weight:700;color:var(--text2);
  list-style:none;transition:background .13s;user-select:none}
.hm-add>summary::-webkit-details-marker{display:none}
.hm-add>summary:hover{background:var(--accent-dim);color:var(--accent)}
.hm-add>summary i{color:var(--accent);font-size:15px}
.hm-add[open]>summary{border-bottom:1px solid var(--border,#2a2a3a)}
.hm-add .hm-form{padding:13px}
.hm-add-body{padding:13px}

/* שורת טיוטה בטופס הדרישה */
.hm-draft{display:flex;align-items:flex-end;gap:9px;flex-wrap:wrap;
  background:var(--bg,#12121a);border:1px solid var(--border,#2a2a3a);
  border-radius:9px;padding:9px 11px;margin-bottom:8px}
.hm-draft-n{display:inline-flex;align-items:center;justify-content:center;
  width:24px;height:24px;flex-shrink:0;border-radius:50%;margin-bottom:4px;
  background:var(--bg4);border:1px solid var(--border2,#3a3a4a);
  font-size:11px;font-weight:800;color:var(--text3)}
.hm-draft .d-note{flex:1;min-width:130px;background:var(--bg4);color:var(--text);
  border:1px solid var(--border);border-radius:7px;padding:7px 9px;
  font-family:var(--font);font-size:13px;margin-bottom:1px}
.hm-draft-x{width:30px;height:30px;flex-shrink:0;margin-bottom:1px;
  display:inline-flex;align-items:center;justify-content:center;
  border:1px solid rgba(239,68,68,.30);background:rgba(239,68,68,.10);
  border-radius:7px;cursor:pointer;color:#f87171;font-size:12px;
  transition:all .13s}
.hm-draft-x:hover{background:rgba(239,68,68,.22);border-color:rgba(239,68,68,.6)}

.hm-more{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;
  border:1px dashed var(--border2,#3a3a4a);border-radius:8px;
  background:transparent;color:var(--text3);font-size:12px;font-weight:700;
  cursor:pointer;font-family:var(--font);transition:all .13s}
.hm-more:hover{border-color:var(--accent);color:var(--accent);
  background:var(--accent-dim)}

.hm-add-foot{display:flex;align-items:center;gap:12px;flex-wrap:wrap;
  margin-top:12px;padding-top:12px;border-top:1px solid var(--border,#2a2a3a)}
.hm-add-foot .hm-req-hint{flex:1;margin-top:0}
.hm-add-btn{display:inline-flex;align-items:center;gap:6px;font-weight:700;
  padding:9px 18px}
.hm-req-hint{flex-basis:100%;display:flex;align-items:center;gap:6px;
  font-size:12px;color:var(--text3);background:var(--bg4);border-radius:7px;
  padding:7px 11px;margin-top:2px}
.hm-req-hint i{color:var(--accent);font-size:13px}
.hm-req-hint b{color:var(--text2);font-weight:700}
.hm-r input[type=text].r-note{flex:1;min-width:120px}
.hm-form{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
.hm-form label{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--text3)}
.hm-none{display:flex;align-items:center;justify-content:center;gap:8px;
  color:var(--text3);font-size:13px;padding:22px 10px;text-align:center}
.hm-none i{font-size:17px;opacity:.7}
.hm-box input,.hm-box select{background:var(--bg2,#1a1a24);color:var(--text,#e6e6f0);
  border:1px solid var(--border,#2a2a3a);border-radius:6px;padding:5px 8px;font-family:inherit}

/* ── שדות שעה — הקלדה חופשית, זהה למסך הנציג ── */
.ht-time{width:76px;text-align:center;font-size:15px;font-weight:600;letter-spacing:.5px;
  font-family:'SF Mono',Consolas,'Courier New',monospace;direction:ltr;
  padding:7px 6px;border-radius:8px;transition:border-color .15s,box-shadow .15s,background .15s}
.ht-time:hover:not([readonly]):not(:disabled){border-color:var(--text3,#6b7280)}
.ht-time:focus{outline:none;border-color:var(--accent,#7c5cff);
  box-shadow:0 0 0 3px rgba(124,92,255,.18);background:var(--bg,#12121a)}
.ht-time::placeholder{color:var(--text3,#6b7280);font-weight:400;letter-spacing:1px}
.ht-time.ht-bad{border-color:#ef4444!important;box-shadow:0 0 0 3px rgba(239,68,68,.18)}

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

/* ── טבלת הדרישות המרכזת ── */
.hl-wrap{margin-top:22px;background:var(--bg2,#1a1a24);
  border:1px solid var(--border,#2a2a3a);border-radius:10px;overflow:hidden;
  max-width:100%}
/* הטבלה חרגה מרוחב המסך — נגללת בתוך המכל במקום לדחוף את העמוד */
.hl-scroll{overflow-x:auto;max-width:100%;scrollbar-width:thin;
  scrollbar-color:var(--border2) var(--bg3,#15151f)}
.hl-scroll::-webkit-scrollbar{height:10px}
.hl-scroll::-webkit-scrollbar-track{background:var(--bg3,#15151f);border-radius:10px}
.hl-scroll::-webkit-scrollbar-thumb{background:var(--border2,#3a3a4a);border-radius:10px;
  border:2px solid var(--bg3,#15151f)}
.hl-scroll::-webkit-scrollbar-thumb:hover{background:var(--accent)}

/* ווידג'ט היומן צף בפינה שמאל־תחתונה ומסתיר את סוף הטבלה */
.hl-wrap{margin-bottom:96px}
.hl-head{display:flex;align-items:center;gap:14px;flex-wrap:wrap;
  padding:12px 15px;border-bottom:1px solid var(--border,#2a2a3a)}
.hl-head h2{margin:0;font-size:15px;font-weight:700;color:var(--text)}
.hl-tabs{display:flex;gap:5px;margin-inline-start:auto}
/* ── כפתורים: מסגרת, הבלטה וצל — נבדלים ויזואלית מהתגיות ── */
/* לשוניות הסינון: רקע בהיר מהכרטיס שמאחוריהן וטקסט לבן כמעט מלא,
   כדי שהטקסט לא ייבלע ברקע הכהה */
.hl-tab{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;
  border:1px solid #454c66;border-radius:20px;background:#2b3145;
  color:#eef1f8;font-size:12.5px;font-weight:700;cursor:pointer;
  font-family:var(--font);transition:all .13s;
  box-shadow:0 1px 3px rgba(0,0,0,.35)}
.hl-tab:hover{background:#39415c;color:#fff;border-color:#6b7699;
  transform:translateY(-1px)}
.hl-tab:active{transform:translateY(0)}
.hl-tab.on{background:var(--accent);color:#fff;border-color:#9bb8f5;
  box-shadow:0 3px 12px rgba(91,141,238,.55)}
/* המונה: רקע בהיר על לשונית כהה, והפוך על הלשונית הפעילה */
.hl-c{font-size:11px;font-weight:800;background:#0f1320;color:#fff;
  border-radius:9px;padding:2px 8px;min-width:20px;text-align:center;
  line-height:1.3}
.hl-tab.on .hl-c{background:#fff;color:var(--accent)}

/* min-width מפעיל את הגלילה האופקית כשהמסך צר מדי לעמודות */
.hl-table{width:100%;min-width:940px;border-collapse:separate;
  border-spacing:0;font-size:13px}
.hl-table th{padding:8px 10px;text-align:right;font-size:11px;font-weight:700;
  color:#c3cadb;background:#232838;white-space:nowrap;
  /* top:0 ולא --header-h: .hl-scroll הוא אב גלילה (overflow-x:auto
     הופך גם את ציר ה-Y ל-auto), ולכן ההיצמדות נפתרת בתוכו —
     ערך גדול מאפס הותיר פס ריק בראש הטבלה. */
  position:sticky;top:0;z-index:4;
  border-bottom:1px solid var(--border,#2a2a3a)}
.hl-table td{padding:8px 10px;text-align:right;
  border-bottom:1px solid var(--border,#2a2a3a);color:var(--text2)}
.hl-row{cursor:pointer;transition:background .1s}
.hl-row:hover{background:var(--accent-dim)}
.hl-name{font-weight:600;color:var(--text);white-space:nowrap}
.hl-mono{font-family:'SF Mono',Consolas,monospace;direction:ltr;text-align:center}
.hl-req{font-size:11px;color:var(--text3);white-space:nowrap}
.hl-note{max-width:210px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
  font-size:12px;color:var(--text3)}
.hl-hol{display:inline-block;font-size:9px;font-weight:700;color:#f59e0b;
  background:rgba(245,158,11,.14);border-radius:4px;padding:1px 5px;
  margin-inline-start:4px}
/* ── תגיות: רקע אטום, שטוחות, ללא מסגרת — מידע בלבד, לא נלחצות ── */
.hl-st{display:inline-block;font-size:10px;font-weight:800;border-radius:4px;
  padding:3px 9px;white-space:nowrap;letter-spacing:.2px;
  border:0;cursor:default;line-height:1.4}
.hl-st.ok{background:#1b4332;color:#8ff0b4}
.hl-st.wait{background:#5a3a06;color:#ffd97a}
.hl-st.mk{background:#3b2a78;color:#d6ccff;margin-inline-start:4px}
.hl-day-fri,.hl-day-sat{opacity:.55}
.hl-day-fri:hover,.hl-day-sat:hover{opacity:1}
.hl-empty{text-align:center;color:var(--text3);padding:26px}
.hl-chk{width:17px;height:17px;cursor:pointer;accent-color:var(--accent);margin:0}
.hl-chk:disabled{cursor:not-allowed;opacity:.35}

/* התווית ממלאת את התא כולו, כך שלחיצה בכל מקום בו מסמנת ולא
   פותחת את מודל השורה */
.hl-chk-td{padding:0!important}
.hl-chk-l{display:flex;align-items:center;justify-content:center;gap:5px;
  width:100%;height:100%;min-height:38px;padding:4px 8px;cursor:pointer;
  border-radius:6px;transition:background .12s;user-select:none}
.hl-chk-l:hover{background:rgba(124,92,255,.14)}
.hl-chk-l:active{background:rgba(124,92,255,.22)}
.hl-chk-l:has(input:disabled){cursor:not-allowed}
.hl-chk-l:has(input:disabled):hover{background:transparent}
.hl-th-chk .hl-chk-l{min-height:0;flex-direction:column;gap:2px}

/* סגירת שורות */
.hl-close-b{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;
  border:1px solid #64748b;border-radius:8px;
  background:#475569;color:#fff;font-size:12px;font-weight:700;
  cursor:pointer;font-family:var(--font);white-space:nowrap;transition:all .13s;
  box-shadow:0 2px 8px rgba(0,0,0,.35)}
.hl-close-b:hover{background:#556378;border-color:#94a3b8;transform:translateY(-1px)}
.hl-close-b:active{transform:translateY(0)}
.hl-act{width:44px;text-align:center}
.hl-cb,.hl-rb{width:32px;height:32px;display:inline-flex;align-items:center;
  justify-content:center;border-radius:8px;cursor:pointer;font-size:14px;
  transition:all .13s;box-shadow:0 1px 3px rgba(0,0,0,.3)}
.hl-cb{border:1px solid #64748b;background:#3f4a5c;color:#e2e8f0}
.hl-cb:hover{background:#556378;border-color:#94a3b8;transform:translateY(-1px)}
.hl-rb{border:1px solid #7c5cff;background:#3b2a78;color:#ddd6fe}
.hl-rb:hover{background:#4c37a0;border-color:#a78bfa;transform:translateY(-1px)}
.hl-cb:active,.hl-rb:active{transform:translateY(0)}
.hl-st.cl{display:inline-flex;align-items:center;gap:4px;
  background:#37415a;color:#e2e8f0}
.hl-row.hl-closed{opacity:.6;cursor:default}
.hl-row.hl-closed:hover{opacity:.85;background:transparent}

/* צ'קבוקסים בטופס הדרישה */
.hm-f-col{display:flex;flex-direction:column;gap:5px}
.hm-cb{display:inline-flex;align-items:center;gap:6px;cursor:pointer;
  font-size:11px;font-weight:700;color:var(--text3);white-space:nowrap;
  user-select:none;transition:color .13s}
.hm-cb:hover{color:var(--text2)}
.hm-cb input{width:15px;height:15px;cursor:pointer;accent-color:var(--accent);margin:0}
.hm-cb:has(input:checked){color:var(--accent)}
.hl-th-chk{width:64px;text-align:center!important}
.hl-th-chk span{font-size:9px;font-weight:700;color:#c3cadb}
.hl-row td:first-child{text-align:center}

/* ייצוא — צמוד לטבלה, כדי שהקשר לצ'קבוקסים יהיה ברור */
.hl-exp{display:flex;align-items:center;gap:9px;margin-inline-start:auto}
/* display:flex על ההורה גובר על התכונה hidden, ולכן אלמנט מוסתר
   עדיין תופס מקום כפריט flex */
.hl-exp [hidden]{display:none!important}
.hl-exp-n{font-size:12px;color:#aab3c5;white-space:nowrap}
.hl-exp-n b{color:#fff;font-size:14px;font-weight:800;
  background:var(--accent);border-radius:5px;padding:1px 8px;
  margin-inline:2px;display:inline-block}
.hl-exp-b{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;
  border:1px solid #22c55e;border-radius:8px;
  background:#15803d;color:#fff;font-size:12px;font-weight:700;
  cursor:pointer;font-family:var(--font);white-space:nowrap;transition:all .13s;
  box-shadow:0 2px 8px rgba(34,197,94,.35)}
.hl-exp-b:hover{background:#16a34a;box-shadow:0 4px 14px rgba(34,197,94,.5);
  transform:translateY(-1px)}
.hl-exp-b:active{transform:translateY(0)}
.hl-tabs{margin-inline-start:0}
</style>
