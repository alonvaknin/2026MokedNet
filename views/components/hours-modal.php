<?php declare(strict_types=1); ?>
<div id="hours-modal" class="hm-overlay" onclick="if(event.target===this)hoursCloseModal()">
  <div class="hm-box">
    <div class="hm-head">
      <h2>שעות לעדכון</h2>
      <button type="button" class="hm-x" onclick="hoursCloseModal()" title="סגירה">✕</button>
    </div>
    <div class="hm-body" id="hours-modal-body"></div>
  </div>
</div>

<style>
/* ── המודל ── */
.hm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9000;
  align-items:flex-start;justify-content:center;padding-top:60px}
.hm-overlay.open{display:flex}
.hm-box{background:var(--bg2,#12121a);border:1px solid var(--border,#2a2a3a);border-radius:14px;
  width:min(920px,94vw);max-height:80vh;overflow:auto;padding:20px;direction:rtl;
  font-family:var(--font);box-shadow:0 24px 70px rgba(0,0,0,.6)}
.hm-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.hm-head h2{margin:0;font-size:17px;font-weight:700;color:var(--text)}
.hm-x{background:none;border:0;color:var(--text3);font-size:18px;cursor:pointer;line-height:1}
.hm-x:hover{color:var(--accent)}
#hours-bell{position:relative;display:none}
#hours-bell.on{display:grid}
#hours-badge{position:absolute;top:-5px;left:-5px;background:#ef4444;color:#fff;border-radius:9px;
  font-size:10px;font-weight:700;min-width:16px;height:16px;line-height:16px;text-align:center;
  padding:0 4px;font-family:var(--font)}

/* ── הטבלה עצמה — הדאשבורד אינו טוען את ה-CSS של עמוד השעות ── */
.hm-body .hours-table{width:100%;border-collapse:collapse;margin-top:16px}
.hm-body .hours-table th,.hm-body .hours-table td{padding:8px 10px;text-align:right;
  border-bottom:1px solid var(--border,#2a2a3a)}
.hm-body .hours-table th{color:var(--text3);font-weight:600;font-size:13px}
.hm-body .hours-table input,.hm-body .hours-table select{background:var(--bg2,#1a1a24);
  color:var(--text,#e6e6f0);border:1px solid var(--border,#2a2a3a);border-radius:6px;
  padding:5px 8px;font-family:inherit}
.hm-body .hours-table input[readonly]{opacity:.5;cursor:not-allowed}
.hm-body .hours-table input:disabled,.hm-body .hours-table select:disabled{opacity:.35}
.hm-body .ht-req{border-color:var(--accent,#7c5cff)!important}
.hm-body .ht-day-fri,.hm-body .ht-day-sat{background:rgba(255,255,255,.03)}
.hm-body .ht-day-hol{background:rgba(245,158,11,.10)}
.hm-body .ht-day-erev{background:rgba(251,191,36,.06)}
.hm-body .ht-day-chol{background:rgba(217,119,6,.06)}
.hm-body .ht-done{opacity:.65}
.hm-body .ht-empty{text-align:center;color:var(--text3);padding:24px}
.hm-body .ht-save{background:var(--accent,#7c5cff);color:#fff;border:0;border-radius:6px;
  padding:6px 14px;cursor:pointer;font-family:inherit}
.hm-body .ht-save:disabled{opacity:.5;cursor:wait}

/* ── שדות שעה — זהים לעמוד /hours ── */
.hm-body .ht-time{width:76px;text-align:center;font-size:15px;font-weight:600;letter-spacing:.5px;
  font-family:'SF Mono',Consolas,'Courier New',monospace;direction:ltr;
  padding:7px 6px;border-radius:8px;transition:border-color .15s,box-shadow .15s,background .15s}
.hm-body .ht-time:hover:not([readonly]):not(:disabled){border-color:var(--text3,#6b7280)}
.hm-body .ht-time:focus{outline:none;border-color:var(--accent,#7c5cff);
  box-shadow:0 0 0 3px rgba(124,92,255,.18);background:var(--bg,#12121a)}
.hm-body .ht-time::placeholder{color:var(--text3,#6b7280);font-weight:400;letter-spacing:1px}
.hm-body .ht-time.ht-req{background:rgba(124,92,255,.07)}
.hm-body .ht-time.ht-bad{border-color:#ef4444!important;box-shadow:0 0 0 3px rgba(239,68,68,.18)}
.hm-body .ht-time[readonly]{background:rgba(255,255,255,.03);border-style:dashed}
.hm-body .ht-tw{position:relative;display:inline-flex;align-items:center}
.hm-body .ht-tbtn{position:absolute;left:4px;top:50%;transform:translateY(-50%);
  background:none;border:0;padding:2px 3px;line-height:1;cursor:pointer;
  color:var(--text3);font-size:11px;border-radius:4px;transition:color .13s,background .13s}
.hm-body .ht-tbtn:hover{color:var(--accent);background:var(--accent-dim)}
.hm-body .ht-tbtn:disabled{opacity:.25;pointer-events:none}
.hm-body .ht-tw .ht-time{padding-left:22px}

/* ── בורר השעה נבנה על document.body, ולכן ללא תחילית ── */
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

/* ── שדה נדרש מודגש, השני מאופר ── */
.ht-time.ht-opt{opacity:.45;border-style:dashed}
.ht-time.ht-opt:focus{opacity:1;border-style:solid}
.ht-time.ht-req{border-color:var(--accent)!important;
  background:rgba(124,92,255,.10);box-shadow:0 0 0 1px rgba(124,92,255,.25)}

/* ── מה נדרש בשורה ── */
.ht-actions{display:flex;align-items:center;gap:9px;justify-content:flex-end}
.ht-need{display:inline-flex;align-items:center;gap:4px;font-size:10px;
  font-weight:700;color:#fcd34d;background:#4a3105;border-radius:4px;
  padding:3px 8px;white-space:nowrap}
.ht-need i{font-size:11px}
</style>

<script>
/* ── הרכיב hours-table מוזרק דרך innerHTML, ולכן ה-<script> שבו אינו רץ.
      בעמודים שאינם /hours הגלובלים האלה אינם קיימים — מוגדרים כאן, זהים
      לחלוטין למקור ב-views/components/hours-table.php, כל אחד בשמירה משלו
      כדי שלא תהיה הגדרה כפולה בעמוד /hours. ── */
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

/* ── הפעמון והמודל ── */
if (!window.hoursRefreshBadge) {
window.hoursRefreshBadge = function () {
    fetch(window.__V2_BASE + '/api/hours/pending-count')
      .then(function (r) { return r.json(); })
      .then(function (d) {
          var bell = document.getElementById('hours-bell');
          if (!bell) return;
          if (d && d.count > 0) {
              bell.classList.add('on');
              var b = document.getElementById('hours-badge');
              if (b) b.textContent = d.count;
          } else {
              bell.classList.remove('on');
          }
      })
      .catch(function () { /* שקט — הפעמון פשוט נשאר מוסתר */ });
};

window.hoursOpenModal = function () {
    var box = document.getElementById('hours-modal-body');
    if (!box) return;
    box.innerHTML = '<div style="text-align:center;padding:30px;color:var(--text3)">טוען…</div>';
    document.getElementById('hours-modal').classList.add('open');

    fetch(window.__V2_BASE + '/api/hours/pending')
      .then(function (r) { return r.json(); })
      .then(function (d) {
          /* ה-<script> שבתוך ה-HTML המוזרק אינו רץ — הגלובלים הוגדרו כבר למעלה */
          box.innerHTML = (d && d.html) ? d.html : '';
      })
      .catch(function () {
          box.innerHTML = '<div style="text-align:center;padding:30px;color:#ef4444">שגיאת רשת</div>';
      });
};

window.hoursCloseModal = function () {
    var m = document.getElementById('hours-modal');
    if (m) m.classList.remove('open');
    hoursRefreshBadge();
};

document.addEventListener('DOMContentLoaded', function () { hoursRefreshBadge(); });
}
</script>
