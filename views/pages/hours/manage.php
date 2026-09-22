<?php
declare(strict_types=1);
use Core\View;
use Core\Holidays;
/** @var array $users */
/** @var array $grid */
/** @var int $days */
/** @var string $month */
/** @var string $monthLabel */
/** @var string $prevMonth */
/** @var string $nextMonth */
/** @var int $markedCount */
$base = rtrim(CFG['app']['url'], '/');
$DAYS = ['א','ב','ג','ד','ה','ו','ש'];
$ABS  = ['vacation'=>'חופ׳','reserve'=>'מיל׳','sick'=>'מחל׳',
         'duplicate_delete'=>'כפל׳','other'=>'אחר'];
?>
<div class="page-head" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div class="page-title" style="margin-bottom:0;"><i class="bi bi-calendar3" style="margin-left:8px;"></i>ניהול דיווח שעות</div>
  <div class="hours-nav">
    <a class="btn btn-ghost btn-sm" href="<?= View::e($base) ?>/hours/manage?month=<?= View::e($prevMonth) ?>">▶</a>
    <span class="hours-month"><?= View::e($monthLabel) ?></span>
    <a class="btn btn-ghost btn-sm" href="<?= View::e($base) ?>/hours/manage?month=<?= View::e($nextMonth) ?>">◀</a>
  </div>
</div>

<div class="hm-bar">
  <span>נבחרו <b id="hm-marked"><?= (int)$markedCount ?></b> שורות לדיווח</span>
  <button type="button" class="btn btn-primary" onclick="hmExport()">הורד XLS</button>
  <span class="hm-hint">גרור על תאים לבחירה מרובה</span>
</div>

<?php
// חגים ומועדים בחודש המוצג — מוצגים כרצועה מעל הלוח
$monthHols = [];
for ($d = 1; $d <= $days; $d++) {
    $date = sprintf('%s-%02d', $month, $d);
    $h    = Holidays::get($date);
    if (!$h) continue;
    $monthHols[] = ['d' => $d, 'date' => $date, 'n' => $h['n'], 't' => $h['t']];
}
?>
<?php if ($monthHols): ?>
<div class="hm-hols">
  <span class="hm-hols-lbl">חגים ומועדים בחודש:</span>
  <?php foreach ($monthHols as $h): ?>
    <span class="hm-hol hm-hol-<?= View::e($h['t']) ?>">
      <b><?= (int)$h['d'] ?></b> <?= View::e($h['n']) ?>
    </span>
  <?php endforeach; ?>
</div>
<?php endif; ?>

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
        <th class="hm-d hm-day-<?= View::e($dt) ?>"
            title="<?= View::e($hol['n'] ?? Holidays::label($dt)) ?>">
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
        <td class="hm-cell hm-day-<?= View::e($dt) ?>"
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
            <span class="hm-chip <?= View::e($cls) ?>"><?= View::e($txt) ?></span>
          <?php endforeach; ?>
        </td>
      <?php endfor; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- מודל התא -->
<div id="hm-modal" class="hm-overlay" onclick="if(event.target===this)hmClose()">
  <div class="hm-box">
    <div class="hm-head"><h2 id="hm-title">—</h2>
      <button type="button" class="hm-x" onclick="hmClose()">✕</button></div>
    <div id="hm-rows"></div>
    <hr>
    <h3>הוסף דרישה</h3>
    <div class="hm-form">
      <label>דרוש:
        <select id="hm-req">
          <option value="both">כניסה ויציאה</option>
          <option value="in">כניסה בלבד</option>
          <option value="out">יציאה בלבד</option>
        </select>
      </label>
      <label>כניסה:
        <span class="ht-tw"><input type="text" id="hm-in" class="ht-time" inputmode="numeric" maxlength="5" placeholder="--:--"><button type="button" class="ht-tbtn" tabindex="-1" title="בחירת שעה"><i class="bi bi-clock"></i></button></span>
      </label>
      <label>יציאה:
        <span class="ht-tw"><input type="text" id="hm-out" class="ht-time" inputmode="numeric" maxlength="5" placeholder="--:--"><button type="button" class="ht-tbtn" tabindex="-1" title="בחירת שעה"><i class="bi bi-clock"></i></button></span>
      </label>
      <label>הערה: <input type="text" id="hm-note" maxlength="500"></label>
      <button type="button" class="btn btn-primary" onclick="hmAddRequest()">הוסף</button>
    </div>
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
                 duplicate_delete:'למחוק דיווחים כפולים', other:'אחר' };

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
if (!window.hoursTimePicker) {
window.hoursTimePicker = (function () {
    var pop = null, target = null;

    function build() {
        var el = document.createElement('div');
        el.className = 'ht-pop';
        var h = '<div class="ht-pop-hd">בחר שעה</div><div class="ht-pop-cols">';
        h += '<div class="ht-pop-col"><div class="ht-pop-lbl">שעה</div><div class="ht-pop-list">';
        for (var i = 0; i < 24; i++) {
            var v = ('0' + i).slice(-2);
            h += '<button type="button" class="ht-pop-i" data-h="' + v + '">' + v + '</button>';
        }
        h += '</div></div><div class="ht-pop-col"><div class="ht-pop-lbl">דקות</div><div class="ht-pop-list">';
        [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55].forEach(function (m) {
            var v = ('0' + m).slice(-2);
            h += '<button type="button" class="ht-pop-i" data-m="' + v + '">' + v + '</button>';
        });
        h += '</div></div></div>';
        el.innerHTML = h;
        document.body.appendChild(el);

        el.addEventListener('mousedown', function (ev) { ev.preventDefault(); });
        el.addEventListener('click', function (ev) {
            var b = ev.target.closest('.ht-pop-i');
            if (!b || !target) return;
            var cur = (target.value || '').split(':');
            var hh = cur[0] || '09', mm = cur[1] || '00';
            if (b.dataset.h !== undefined) hh = b.dataset.h;
            if (b.dataset.m !== undefined) mm = b.dataset.m;
            target.value = hh + ':' + mm;
            target.classList.remove('ht-bad');
            mark();
            if (b.dataset.m !== undefined) { close(); target.focus(); }
        });
        return el;
    }

    function mark() {
        if (!pop || !target) return;
        var cur = (target.value || '').split(':');
        pop.querySelectorAll('.ht-pop-i').forEach(function (b) {
            var on = (b.dataset.h !== undefined && b.dataset.h === cur[0]) ||
                     (b.dataset.m !== undefined && b.dataset.m === cur[1]);
            b.classList.toggle('ht-pop-on', !!on);
        });
        var sel = pop.querySelector('.ht-pop-on');
        if (sel) sel.scrollIntoView({ block: 'nearest' });
    }

    function open(input) {
        if (!pop) pop = build();
        target = input;
        pop.classList.add('open');
        var r = input.getBoundingClientRect();
        var top = r.bottom + 6, left = r.left;
        if (top + pop.offsetHeight > window.innerHeight - 8)
            top = Math.max(8, r.top - pop.offsetHeight - 6);
        if (left + pop.offsetWidth > window.innerWidth - 8)
            left = Math.max(8, window.innerWidth - pop.offsetWidth - 8);
        pop.style.top = top + 'px';
        pop.style.left = left + 'px';
        mark();
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
    window.addEventListener('scroll', close, true);

    return { open: open, close: close };
})();
}

function hmOpenCell(uid, date) {
    HM_CTX.user = uid; HM_CTX.date = date;
    document.getElementById('hm-title').textContent = date;
    document.getElementById('hm-modal').classList.add('open');
    hmLoadRows();
}

function hmLoadRows() {
    var box = document.getElementById('hm-rows');
    box.innerHTML = '<p class="hm-none">טוען…</p>';
    fetch(window.__V2_BASE + '/api/hours/cell?user_id=' + encodeURIComponent(HM_CTX.user) +
          '&date=' + encodeURIComponent(HM_CTX.date))
      .then(function (r) { return r.json(); })
      .then(function (d) {
          if (!d.rows || !d.rows.length) {
              box.innerHTML = '<p class="hm-none">אין שורות ליום זה</p>';
              return;
          }
          box.innerHTML = d.rows.map(function (r) {
              return '<div class="hm-r" data-id="' + parseInt(r.id, 10) + '">' +
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
                '<label class="r-mark"><input type="checkbox" class="r-chk"' +
                   (String(r.marked_for_export) === '1' ? ' checked' : '') + '> לדיווח</label>' +
                '<button type="button" onclick="hmSaveRow(' + parseInt(r.id, 10) + ')">שמור</button>' +
                '<button type="button" class="r-del" onclick="hmDelRow(' + parseInt(r.id, 10) + ')">🗑</button>' +
                '</div>';
          }).join('');

          box.querySelectorAll('.r-chk').forEach(function (chk) {
              chk.addEventListener('change', function () {
                  var id = parseInt(chk.closest('.hm-r').dataset.id, 10);
                  hmPost('/hours/mark', { id: id, on: chk.checked }).then(function (res) {
                      if (res.marked !== undefined)
                          document.getElementById('hm-marked').textContent = res.marked;
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
    }).catch(function () { showToast('שגיאת רשת', 'error'); });
}

function hmDelRow(id) {
    if (!confirm('למחוק את השורה?')) return;
    hmPost('/hours/entry/' + id + '/delete', {}).then(function (d) {
        if (d.error) { showToast(d.error, 'error'); return; }
        showToast('נמחק', 'success');
        hmLoadRows();
    }).catch(function () { showToast('שגיאת רשת', 'error'); });
}

function hmAddRequest() {
    var inEl = document.getElementById('hm-in'), outEl = document.getElementById('hm-out');
    var tin = hmTime(inEl), tout = hmTime(outEl);
    if (tin === null || tout === null) { showToast('שעה לא תקינה — פורמט HH:MM', 'error'); return; }

    var body = {
        requires: document.getElementById('hm-req').value,
        time_in:  tin,
        time_out: tout,
        note:     document.getElementById('hm-note').value
    };

    /* בחירה מרובה → bulk */
    if (HM_CTX.sel.length > 1) {
        body.cells = HM_CTX.sel;
        hmPost('/hours/request/bulk', body).then(function (d) {
            if (d.error) { showToast(d.error, 'error'); return; }
            showToast('נוצרו ' + d.created + ' דרישות', 'success');
            location.reload();
        }).catch(function () { showToast('שגיאת רשת', 'error'); });
        return;
    }

    if (!HM_CTX.user || !HM_CTX.date) { showToast('לא נבחר תא', 'warning'); return; }
    body.user_id = HM_CTX.user; body.work_date = HM_CTX.date;
    hmPost('/hours/request/add', body).then(function (d) {
        if (d.error) { showToast(d.error, 'error'); return; }
        showToast('נוספה דרישה', 'success');
        location.reload();
    }).catch(function () { showToast('שגיאת רשת', 'error'); });
}

function hmClose() {
    document.getElementById('hm-modal').classList.remove('open');
    hmClearSel();
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
        document.getElementById('hm-modal').classList.add('open');
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
    location.href = window.__V2_BASE + '/hours/export';
}
</script>

<style>
.hours-nav{display:flex;align-items:center;gap:12px}
.hours-month{font-weight:600;min-width:120px;text-align:center}
.hm-bar{display:flex;align-items:center;gap:14px;margin:14px 0;padding:10px 14px;
  background:var(--bg2,#1a1a24);border-radius:8px}
.hm-hint{color:var(--text3);font-size:12px;margin-inline-start:auto}
.hm-scroll{overflow-x:auto;border:1px solid var(--border,#2a2a3a);border-radius:8px}
.hm-grid{border-collapse:collapse;font-size:12px}
.hm-grid th,.hm-grid td{border:1px solid var(--border,#2a2a3a);padding:2px 4px;text-align:center}
.hm-name{position:sticky;right:0;background:var(--bg,#12121a);text-align:right!important;
  min-width:130px;white-space:nowrap;z-index:2}
.hm-d{min-width:46px}
.hm-dn{display:block;font-weight:700}
.hm-dw{display:block;font-size:10px;color:var(--text3)}
.hm-cell{min-width:46px;height:34px;cursor:pointer;vertical-align:top;user-select:none}
.hm-cell:hover{outline:1px solid var(--accent,#7c5cff)}
.hm-cell.sel{background:rgba(124,92,255,.25)!important}
.hm-chip{display:block;font-size:10px;border-radius:3px;padding:1px 2px;margin:1px 0;white-space:nowrap}
.hm-ok{background:rgba(34,197,94,.20);color:#86efac}
.hm-wait{background:rgba(245,158,11,.20);color:#fcd34d}
.hm-abs{background:rgba(59,130,246,.20);color:#93c5fd}
/* שישי/שבת — ימי מנוחה, מעומעמים כדי שלא יתחרו על תשומת הלב */
.hm-day-fri,.hm-day-sat{background:rgba(0,0,0,.28)}
th.hm-day-fri,th.hm-day-sat{opacity:.45}
td.hm-cell.hm-day-fri,td.hm-cell.hm-day-sat{opacity:.5}
td.hm-cell.hm-day-fri:hover,td.hm-cell.hm-day-sat:hover{opacity:1}
.hm-day-sat{background:rgba(0,0,0,.38)}

.hm-day-hol{background:rgba(245,158,11,.12)}
.hm-day-erev{background:rgba(251,191,36,.07)}
.hm-day-chol{background:rgba(217,119,6,.07)}

/* רצועת החגים שמעל הלוח */
.hm-hols{display:flex;flex-wrap:wrap;align-items:center;gap:7px;
  margin:0 0 12px;padding:9px 13px;background:var(--bg2,#1a1a24);
  border:1px solid var(--border,#2a2a3a);border-radius:8px}
.hm-hols-lbl{font-size:11px;font-weight:700;color:var(--text3);
  margin-inline-end:2px}
.hm-hol{display:inline-flex;align-items:center;gap:5px;font-size:11px;
  font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap;
  border:1px solid transparent}
.hm-hol b{font-size:12px;font-weight:800;opacity:.85}
.hm-hol-h,.hm-hol-i{background:rgba(245,158,11,.14);color:#f59e0b;
  border-color:rgba(245,158,11,.32)}
.hm-hol-e{background:rgba(251,191,36,.10);color:#fbbf24;
  border-color:rgba(251,191,36,.30);border-style:dashed}
.hm-hol-c{background:rgba(217,119,6,.12);color:#d97706;
  border-color:rgba(217,119,6,.28)}
.hm-hol-r{background:rgba(139,92,246,.12);color:#a78bfa;
  border-color:rgba(139,92,246,.28)}

/* מודל */
.hm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9000;
  align-items:flex-start;justify-content:center;padding-top:60px}
.hm-overlay.open{display:flex}
.hm-box{background:var(--bg,#12121a);border:1px solid var(--border,#2a2a3a);border-radius:12px;
  width:min(920px,94vw);max-height:80vh;overflow:auto;padding:20px}
.hm-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.hm-head h2{margin:0;font-size:18px}
.hm-x{background:none;border:0;color:var(--text3);font-size:20px;cursor:pointer}
.hm-r{display:flex;gap:6px;align-items:center;margin-bottom:6px;flex-wrap:wrap}
.hm-r input[type=text].r-note{flex:1;min-width:120px}
.hm-form{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
.hm-form label{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--text3)}
.hm-none{color:var(--text3);font-size:13px}
.r-del{background:none;border:0;cursor:pointer;font-size:14px}
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
  box-shadow:0 20px 60px rgba(0,0,0,.6);padding:9px;direction:rtl;
  font-family:var(--font)}
.ht-pop.open{display:block}
.ht-pop-hd{font-size:11px;font-weight:700;color:var(--text2);text-align:center;
  margin-bottom:7px}
.ht-pop-cols{display:flex;gap:7px}
.ht-pop-col{display:flex;flex-direction:column;min-width:0}
.ht-pop-lbl{font-size:9px;font-weight:700;color:var(--text3);text-align:center;
  padding:2px 0;margin-bottom:3px}
.ht-pop-list{display:grid;grid-template-columns:repeat(3,32px);gap:2px;
  max-height:172px;overflow-y:auto;scrollbar-width:thin;
  scrollbar-color:var(--border2) transparent;padding-left:2px}
.ht-pop-list::-webkit-scrollbar{width:3px}
.ht-pop-list::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}
.ht-pop-i{width:32px;height:28px;display:flex;align-items:center;
  justify-content:center;font-size:12px;font-weight:500;border-radius:6px;
  cursor:pointer;border:1px solid transparent;color:var(--text2);
  background:var(--bg4);font-family:var(--font);
  transition:background .1s,color .1s}
.ht-pop-i:hover{background:var(--accent-dim);color:var(--accent);
  border-color:rgba(91,141,238,.2)}
.ht-pop-i.ht-pop-on{background:var(--accent);color:#fff;font-weight:700;
  box-shadow:0 2px 8px rgba(91,141,238,.4)}
</style>
