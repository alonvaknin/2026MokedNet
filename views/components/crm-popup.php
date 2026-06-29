<?php
/**
 * CRM Popup Component — v2
 * Include at bottom of main.php (already loads via layout).
 * Opens when ?caller=PHONE is present in the URL, or via window.CRM.open(phone).
 */
use Core\Auth;
$crmUser     = Auth::user();
$crmDept     = $crmUser['dept_name']  ?? '';
$crmFullName = $_SESSION['full_name'] ?? '';
$crmCanRec   = Auth::can('pbxRecording'); // permission gate for recordings
$crmBase     = rtrim(CFG['app']['url'], '/');

// Numbers to ignore (internal extensions)
$crmIgnore = [523122286,7407042384,7407042386,7407042387,7407042388,7407042389,
              7407042391,7407042392,7407042393,7407042395,7407042396,7407042397,
              7407042399,7407042402,7407042403];

$crmInitPhone = '';
if (isset($_GET['caller'])) {
    $raw = filter_var($_GET['caller'], FILTER_SANITIZE_NUMBER_INT);
    if (!in_array((int)$raw, $crmIgnore)) {
        $crmInitPhone = $raw;
    }
}
?>

<!-- ═══════════════════════════════════════════════════════════════ CRM POPUP -->
<div id="crm-overlay" class="crm-overlay" aria-hidden="true"></div>

<div id="crm-popup" class="crm-popup" role="dialog" aria-label="CRM נציג">

  <!-- ── Header ── -->
  <div class="crm-header">
    <div class="crm-header-right">
      <div class="crm-stopwatch" id="crm-timer" title="זמן שיחה">
        <i class="bi bi-stopwatch"></i>
        <span id="crm-timer-display">00:00</span>
      </div>
      <div class="crm-caller-block">
        <span class="crm-label">מתקשר</span>
        <span id="crm-phone-display" class="crm-phone-val">—</span>
        <button class="crm-icon-btn" id="crm-change-phone" title="החלף מספר">
          <i class="bi bi-arrow-repeat"></i>
        </button>
      </div>
      <div class="crm-caller-block" id="crm-name-block">
        <span class="crm-label">שם</span>
        <span id="crm-name-display" class="crm-name-val"></span>
        <button class="crm-icon-btn crm-name-edit-btn" id="crm-edit-name-btn" title="ערוך שם לקוח" style="width:24px;height:24px;font-size:11px;">
          <i class="bi bi-pencil"></i>
        </button>
      </div>
      <!-- inline name editor -->
      <div class="crm-caller-block" id="crm-name-edit-block" style="display:none">
        <input type="text" id="crm-name-input" class="crm-name-inline-input" placeholder="שם לקוח" maxlength="60">
        <button class="crm-pill-btn" id="crm-name-save-btn" style="padding:4px 10px;font-size:12px;">שמור</button>
        <button class="crm-icon-btn" id="crm-name-cancel-btn" style="width:24px;height:24px;"><i class="bi bi-x"></i></button>
      </div>
    </div>
    <!-- drag to move -->
    <div class="crm-drag-handle" id="crm-drag-handle" title="גרור להזזה">
      <i class="bi bi-grip-vertical"></i>
    </div>
    <div class="crm-header-actions">
      <button class="crm-action-pill crm-pill-wa"  id="crm-btn-wa"   title="שלח WhatsApp">
        <i class="bi bi-whatsapp"></i><span>WhatsApp</span>
      </button>
      <button class="crm-action-pill crm-pill-fmt" id="crm-btn-fmt"  title="פורמטר">
        <i class="bi bi-file-text"></i><span>פורמטר</span>
      </button>
      <button class="crm-action-pill crm-pill-note" id="crm-btn-note" title="הוסף תיעוד">
        <i class="bi bi-pencil-square"></i><span>תיעוד</span>
      </button>
      <div class="crm-size-btns">
        <button class="crm-size-btn" id="crm-size-s" onclick="CRM.setSize('s')" title="קטן">S</button>
        <button class="crm-size-btn" id="crm-size-m" onclick="CRM.setSize('m')" title="בינוני">M</button>
        <button class="crm-size-btn" id="crm-size-l" onclick="CRM.setSize('l')" title="גדול">L</button>
      </div>
      <button class="crm-icon-btn" id="crm-reset-pos" title="איפוס מיקום" onclick="CRM.resetPosition()">
        <i class="bi bi-arrows-fullscreen"></i>
      </button>
      <button class="crm-icon-btn crm-minimize" id="crm-minimize" title="מזעור">
        <i class="bi bi-dash-lg"></i>
      </button>
      <button class="crm-icon-btn crm-close-btn" id="crm-close" title="סגור">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
  </div>

  <!-- ── Phone replace bar (hidden by default) ── -->
  <div class="crm-phone-replace" id="crm-phone-replace" style="display:none">
    <i class="bi bi-telephone-fill" style="color:var(--accent)"></i>
    <input type="tel" id="crm-phone-input" placeholder="הזן מספר טלפון לחיפוש" dir="ltr" maxlength="15">
    <button class="crm-pill-btn" id="crm-phone-search-btn">חפש</button>
    <button class="crm-icon-btn" id="crm-phone-cancel"><i class="bi bi-x"></i></button>
  </div>

  <!-- ── Critical caller note (shown if flagged) ── -->
  <div class="crm-critical-banner" id="crm-critical-banner" style="display:none">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span id="crm-critical-text"></span>
  </div>

  <!-- ── Body ── -->
  <div class="crm-body">

    <!-- ░░ Customer pane (fixed left) ░░ -->
    <div class="crm-col-customer">
      <div class="crm-section-head">
        <i class="bi bi-person-lines-fill" style="color:#10b981"></i>
        <span>לקוח</span>
        <span class="crm-badge" id="crm-notes-count" style="display:none"></span>
        <button class="crm-icon-btn" title="הוסף תיעוד"
                style="margin-right:auto;width:24px;height:24px;font-size:11px;"
                onclick="CRM.openNoteModal()">
          <i class="bi bi-plus-lg"></i>
        </button>
      </div>
      <div class="crm-section-body" id="crm-customer-body">
        <div class="crm-empty-state"><i class="bi bi-person"></i><span>ממתין לחיפוש…</span></div>
      </div>
    </div>

    <!-- ░░ Search columns (expandable) ░░ -->
    <div class="crm-cols-search">

      <!-- קריאות שירות -->
      <div class="crm-col-search">
        <div class="crm-section-head">
          <i class="bi bi-tools" style="color:#8b5cf6;flex-shrink:0"></i>
          <span class="crm-col-label">קריאות שירות</span>
          <span class="crm-badge" id="crm-service-count" style="display:none"></span>
          <button id="crm-auto-open-case"
                  style="display:none;align-items:center;gap:4px;
                         background:rgba(91,141,238,.12);border:1px solid rgba(91,141,238,.3);
                         border-radius:20px;padding:2px 8px;font-size:10px;font-weight:600;
                         color:var(--accent);cursor:pointer;white-space:nowrap;"
                  onclick="CRM.openAutomationForPhone()">
            <i class="bi bi-lightning-charge-fill"></i> אוטומציה
          </button>
        </div>
        <div class="crm-section-body" id="crm-service-body">
          <div class="crm-empty-state"><i class="bi bi-search"></i><span>ממתין לחיפוש…</span></div>
        </div>
      </div>

      <!-- שיחות מרכזיה -->
      <div class="crm-col-search">
        <div class="crm-section-head">
          <i class="bi bi-clock-history" style="color:#06b6d4;flex-shrink:0"></i>
          <span class="crm-col-label">שיחות מרכזיה</span>
          <span class="crm-badge" id="crm-calls-count" style="display:none"></span>
          <div style="margin-right:auto;display:flex;align-items:center;gap:4px;flex-shrink:0;">
            <select id="crm-pbx-range" style="background:var(--bg4);border:1px solid var(--border);border-radius:5px;color:var(--text2);font-size:10px;padding:2px 4px;cursor:pointer;font-family:var(--font);">
              <option value="last1week" selected>שבוע</option>
              <option value="1MonthOld">חודש</option>
              <option value="halfYearOld">חצי שנה</option>
            </select>
            <select id="crm-pbx-source" style="background:var(--bg4);border:1px solid var(--border);border-radius:5px;color:var(--text2);font-size:10px;padding:2px 4px;cursor:pointer;font-family:var(--font);">
              <option value="branches" selected>מוקד+חנויות</option>
              <option value="moked">מוקד בלבד</option>
            </select>
            <button class="crm-pill-btn" id="crm-pbx-refresh" style="padding:2px 7px;font-size:10px;" title="רענן">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
          </div>
        </div>
        <div class="crm-section-body" id="crm-calls-body">
          <div class="crm-empty-state"><i class="bi bi-telephone-x"></i><span>ממתין לחיפוש…</span></div>
        </div>
      </div>

    </div><!-- /crm-cols-search -->

  </div><!-- /crm-body -->

  <!-- ── Resize handle ── -->
  <div class="crm-resize-handle" id="crm-resize-handle" title="גרור להרחבה">
    <i class="bi bi-grip-horizontal"></i>
  </div>

</div><!-- /crm-popup -->

<!-- ── Minimized bar (shown when minimized) ── -->
<div class="crm-mini-bar" id="crm-mini-bar" style="display:none">
  <i class="bi bi-headset" style="color:var(--accent)"></i>
  <span id="crm-mini-phone"></span>
  <span class="crm-mini-timer" id="crm-mini-timer">00:00</span>
  <button class="crm-pill-btn" id="crm-mini-restore">חזור ל-CRM</button>
  <button class="crm-icon-btn" id="crm-mini-close"><i class="bi bi-x-lg"></i></button>
</div>

<!-- ── Note modal ── -->
<div class="crm-modal-wrap" id="crm-note-modal" style="display:none" role="dialog" aria-label="תיעוד שיחה">
  <div class="crm-modal">
    <div class="crm-modal-head">
      <span><i class="bi bi-pencil-square"></i> תיעוד שיחה</span>
      <button class="crm-icon-btn" onclick="CRM.closeNoteModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="crm-modal-body">
      <div class="crm-form-row">
        <label>שם לקוח</label>
        <input type="text" id="crm-note-name" placeholder="שם מלא של הלקוח">
      </div>
      <div class="crm-form-row">
        <label>טלפון</label>
        <input type="tel" id="crm-note-phone" dir="ltr" readonly style="opacity:.7">
      </div>
      <div class="crm-form-row">
        <label>תיעוד / הערה</label>
        <textarea id="crm-note-text" rows="4" placeholder="מה חשוב לדעת על הלקוח הזה בהתקשרות הבאה?"></textarea>
      </div>
      <div class="crm-form-row crm-form-row-check">
        <label class="crm-check-label">
          <input type="checkbox" id="crm-note-critical">
          <span class="crm-check-box"></span>
          הודעה קריטית — תוצג אוטומטית בשיחה הבאה
        </label>
      </div>
      <div class="crm-form-row crm-form-row-check">
        <label class="crm-check-label">
          <input type="checkbox" id="crm-note-email">
          <span class="crm-check-box"></span>
          שלח תיעוד למייל שלי
        </label>
      </div>
      <div id="crm-note-msg" class="crm-form-msg" style="display:none"></div>
    </div>
    <div class="crm-modal-foot">
      <button class="crm-btn-primary" onclick="CRM.saveNote()">
        <i class="bi bi-check-lg"></i> שמור תיעוד
      </button>
      <button class="crm-btn-ghost" onclick="CRM.closeNoteModal()">ביטול</button>
    </div>
  </div>
</div>

<!-- ── WA modal ── -->
<div class="crm-modal-wrap" id="crm-wa-modal" style="display:none" role="dialog" aria-label="שליחת WhatsApp">
  <div class="crm-modal">
    <div class="crm-modal-head">
      <span><i class="bi bi-whatsapp" style="color:#25d366"></i> שליחת הודעת WhatsApp</span>
      <button class="crm-icon-btn" onclick="CRM.closeWaModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="crm-modal-body">
      <div class="crm-form-row">
        <label>שם לקוח</label>
        <input type="text" id="crm-wa-name" placeholder="שם מלא">
      </div>
      <div class="crm-form-row">
        <label>טלפון</label>
        <input type="tel" id="crm-wa-phone" dir="ltr" readonly style="opacity:.7">
      </div>
      <div class="crm-form-row">
        <label>מחלקה</label>
        <select id="crm-wa-dept">
          <option value="">— נא לבחור מחלקה —</option>
          <option value="support">תמיכה טכנית</option>
          <option value="sales">מכירות</option>
          <option value="service">שירות לקוחות</option>
        </select>
      </div>
      <div class="crm-form-row">
        <label>תוכן ההודעה - ניתן לשלוח רק הודעת תבנית כרגע</label>
        <textarea id="crm-wa-template" rows="3" readonly
                  style="opacity:.65;cursor:default;resize:none;font-size:13px;direction:rtl;">שלום 👋, לצורך התחלת התכתבות עם נציגנו *נא ללחוץ על הכפתור מטה* \ לשלוח לנו הודעה כלשהיא, אחרת לא נוכל לכתוב לכם. תודה</textarea>
      </div>
      <div class="crm-form-row">
        <label>הערה (אופציונלי)</label>
        <textarea id="crm-wa-note" rows="2" placeholder="הערה פנימית — מוצגת בתור 'פתק' בגלאסיקס לצורך מידע נוסף לפנייה - לא נשלחת ללקוח"></textarea>
      </div>
      <!-- שיוך לנציג — radio מעוצב -->
      <div id="crm-wa-assign-wrap">
        <div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:7px;">
          <i class="bi bi-person-check-fill" style="color:#25d366;margin-left:4px;"></i>
          שיוך לנציג בGlassix
        </div>
        <div style="display:flex;gap:8px;">
          <label class="crm-radio-card" id="crm-wa-assign-yes-lbl">
            <input type="radio" name="crm_wa_assign" id="crm-wa-assign-yes" value="yes"
                   style="position:absolute;opacity:0;width:0;height:0;">
            <i class="bi bi-person-check-fill" style="font-size:16px;"></i>
            <span>שייך אליי</span>
          </label>
          <label class="crm-radio-card" id="crm-wa-assign-no-lbl">
            <input type="radio" name="crm_wa_assign" id="crm-wa-assign-no" value="no"
                   style="position:absolute;opacity:0;width:0;height:0;">
            <i class="bi bi-person-dash-fill" style="font-size:16px;"></i>
            <span>אל תשייך</span>
          </label>
        </div>
      </div>
      <div id="crm-wa-msg" class="crm-form-msg" style="display:none"></div>
    </div>
    <div class="crm-modal-foot">
      <button class="crm-btn-primary crm-btn-wa-send" id="crm-wa-send-btn" onclick="CRM.sendWA()">
        <i class="bi bi-send-fill"></i> שלח הודעה
      </button>
      <button class="crm-btn-ghost" onclick="CRM.closeWaModal()">ביטול</button>
    </div>
  </div>
</div>

<!-- ── Service call detail modal ── -->
<div class="crm-modal-wrap" id="crm-sc-modal" style="display:none" role="dialog" aria-label="פרטי קריאת שירות">
  <div class="crm-modal" style="width:min(720px,96vw);">
    <div class="crm-modal-head">
      <span><i class="bi bi-tools" style="color:#8b5cf6"></i> <span id="crm-sc-title">פרטי קריאה</span></span>
      <button class="crm-icon-btn" onclick="CRM.closeScModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="crm-modal-body" id="crm-sc-body" style="gap:8px;max-height:75vh;overflow-y:auto;padding:16px;">
      <div class="crm-spinner"></div>
    </div>
    <div class="crm-modal-foot">
      <button class="crm-btn-ghost" onclick="CRM.closeScModal()">סגור</button>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════ STYLES -->
<style>
/* ════ CRM POPUP — unified styles ════ */

/* ── Keyframes ── */
@keyframes crmSlideIn  { from{opacity:0;transform:translate(-50%,-50%) scale(.95)} to{opacity:1;transform:translate(-50%,-50%) scale(1)} }
@keyframes crmFadeIn   { from{opacity:0} to{opacity:1} }
@keyframes crmSlideUp  { from{opacity:0;transform:translateX(-50%) translateY(8px)} to{opacity:1;transform:translateX(-50%) translateY(0)} }
@keyframes crmSpin     { to{transform:rotate(360deg)} }
@keyframes pbxspin     { to{transform:rotate(360deg)} }

/* ── Overlay ── */
.crm-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:900; animation:crmFadeIn .18s ease; }
.crm-overlay.active { display:block; }

/* ── Popup shell ── */
.crm-popup {
  display: none;
  position: fixed;
  top: 50%; left: 50%;
  transform: translate(-50%,-50%);
  width: min(1160px, 96vw);
  height: 580px;
  min-width: 680px;
  min-height: 420px;
  max-width: 98vw;
  max-height: 95vh;
  background: var(--bg2);
  border: 1px solid var(--border2);
  border-radius: 16px;
  box-shadow: 0 28px 72px rgba(0,0,0,.65), 0 4px 20px rgba(0,0,0,.4);
  z-index: 901;
  overflow: hidden;
  flex-direction: column;
  font-family: var(--font);
  direction: rtl;
  transition: box-shadow .2s;
}
.crm-popup.open { display:flex; animation:crmSlideIn .22s cubic-bezier(.4,0,.2,1) forwards; }
.crm-popup.crm-positioned { transform:none; animation:crmFadeIn .18s ease; }

/* ── Header ── */
.crm-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 14px;
  background: var(--bg3);
  border-bottom: 2px solid var(--border);
  flex-shrink: 0;
}
.crm-header-right { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
.crm-header-actions { display:flex; align-items:center; gap:6px; flex-shrink:0; }

.crm-stopwatch {
  display:flex; align-items:center; gap:5px;
  font-size:14px; font-weight:700; color:var(--accent);
  background:var(--accent-dim); border:1px solid rgba(91,141,238,.25);
  border-radius:8px; padding:4px 9px;
  font-variant-numeric:tabular-nums; letter-spacing:.04em;
}
.crm-caller-block { display:flex; align-items:center; gap:6px; }
.crm-label { font-size:10px; font-weight:700; color:var(--text3); text-transform:uppercase; letter-spacing:.07em; }
.crm-phone-val { font-size:15px; font-weight:700; color:var(--text); direction:ltr; letter-spacing:.04em; }
.crm-name-val  { font-size:14px; font-weight:700; color:#22c55e; }

/* inline name editor */
.crm-name-edit-btn { opacity:.4; transition:opacity .15s !important; }
.crm-name-edit-btn:hover { opacity:1; }
#crm-name-block:hover .crm-name-edit-btn { opacity:.8; }
.crm-name-inline-input {
  background:var(--bg4); border:1px solid var(--accent); border-radius:6px;
  color:var(--text); font-size:13px; padding:4px 9px; outline:none;
  font-family:var(--font); width:140px;
  transition:border-color .15s;
}

/* Action pills */
.crm-action-pill {
  display:flex; align-items:center; gap:5px;
  padding:6px 11px; border-radius:8px;
  border:1px solid var(--border2); background:var(--bg4);
  color:var(--text); font-size:12px; font-weight:600;
  cursor:pointer; transition:background .15s, transform .12s, box-shadow .15s;
  font-family:var(--font);
}
.crm-action-pill:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,.3); }
.crm-pill-wa:hover   { background:rgba(37,211,102,.15); border-color:rgba(37,211,102,.4); color:#25d366; }
.crm-pill-fmt:hover  { background:rgba(245,158,11,.12); border-color:rgba(245,158,11,.3); color:#f59e0b; }
.crm-pill-note:hover { background:var(--accent-dim); border-color:rgba(91,141,238,.4); color:var(--accent); }

.crm-icon-btn {
  width:30px; height:30px; display:grid; place-items:center;
  border-radius:7px; border:1px solid var(--border);
  background:transparent; color:var(--text2); cursor:pointer; font-size:13px;
  transition:background .14s, color .14s;
}
.crm-icon-btn:hover { background:var(--bg4); color:var(--text); }
.crm-close-btn:hover { background:rgba(239,68,68,.15); color:#ef4444; border-color:rgba(239,68,68,.3); }

.crm-size-btns { display:flex; align-items:center; gap:2px; background:var(--bg4); border:1px solid var(--border2); border-radius:7px; padding:2px; }
.crm-size-btn  { width:24px; height:20px; border:none; border-radius:4px; background:transparent; color:var(--text3); font-size:11px; font-weight:700; cursor:pointer; font-family:var(--font); transition:background .13s, color .13s; }
.crm-size-btn:hover  { background:var(--bg2); color:var(--text); }
.crm-size-btn.active { background:var(--accent); color:#fff; }

/* ── Phone replace bar ── */
.crm-phone-replace {
  display:flex; align-items:center; gap:10px; padding:9px 14px;
  background:var(--bg3); border-bottom:1px solid var(--border); flex-shrink:0;
}
.crm-phone-replace input {
  flex:1; background:var(--bg4); border:1px solid var(--border2); border-radius:6px;
  color:var(--text); font-size:14px; padding:6px 11px; outline:none; font-family:var(--font);
  transition:border-color .15s;
}
.crm-phone-replace input:focus { border-color:var(--accent); }

/* ── Critical banner ── */
.crm-critical-banner {
  display:flex; align-items:center; gap:10px; padding:9px 14px;
  background:rgba(239,68,68,.12); border-bottom:1px solid rgba(239,68,68,.3);
  color:#fca5a5; font-size:13px; font-weight:600; flex-shrink:0;
}
.crm-critical-banner i { color:#ef4444; font-size:15px; }

/* ══════════════════════════════════════════════════════════
   BODY — customer pane left + expandable search columns right
   ══════════════════════════════════════════════════════════ */
.crm-body {
  display: flex;
  flex: 1;
  overflow: hidden;
  min-height: 0;
}

/* ── Customer pane (fixed left) ── */
.crm-col-customer {
  display: flex;
  flex-direction: column;
  width: 210px;
  min-width: 210px;
  flex-shrink: 0;
  overflow: hidden;
  background: var(--bg2);
  /* visual separator — slightly different bg + right border */
  border-left: 2px solid var(--border2);
  position: relative;
}
/* subtle gradient separator on the right edge of customer pane */
.crm-col-customer::after {
  content:'';
  position:absolute;
  top:0; left:0;
  width:100%; height:100%;
  background: linear-gradient(to left, rgba(0,0,0,.04) 0%, transparent 18px);
  pointer-events:none;
}

/* ── Search columns area ── */
.crm-cols-search {
  display: flex;
  flex: 1;
  overflow: hidden;
  min-width: 0;
}

/* ── Each search column — tinted backgrounds ── */
.crm-col-search {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-height: 0;
  min-width: 0;
  flex: 1;
  transition: flex .32s cubic-bezier(.4,0,.2,1);
  border-right: 1px solid var(--border);
  position: relative;
}
.crm-col-search:last-child { border-right: none; }

/* Per-column subtle color tints */
.crm-col-search:nth-child(1) { background: rgba(139,92,246,.04); }   /* סגול — שירות */
.crm-col-search:nth-child(2) { background: rgba(37,211,102,.04); }   /* ירוק — WA */
.crm-col-search:nth-child(3) { background: rgba(6,182,212,.04); }    /* ציאן — מרכזיה */

/* header tints matching column */
.crm-col-search:nth-child(1) .crm-section-head { border-bottom-color: rgba(139,92,246,.25); background: rgba(139,92,246,.07); }
.crm-col-search:nth-child(2) .crm-section-head { border-bottom-color: rgba(37,211,102,.25); background: rgba(37,211,102,.07); }
.crm-col-search:nth-child(3) .crm-section-head { border-bottom-color: rgba(6,182,212,.25);  background: rgba(6,182,212,.07); }

/* border accent top */
.crm-col-search:nth-child(1)::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:rgba(139,92,246,.5); }
.crm-col-search:nth-child(2)::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:rgba(37,211,102,.5); }
.crm-col-search:nth-child(3)::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:rgba(6,182,212,.5); }

/* Hover expand */
.crm-cols-search:not(.crm-no-hover) .crm-col-search:hover { flex: 2.2; }

/* ── Section header ── */
.crm-section-head {
  display:flex; align-items:center; gap:7px;
  padding:9px 13px;
  background:var(--bg3); border-bottom:1px solid var(--border);
  font-size:12px; font-weight:700; color:var(--text2);
  flex-shrink:0; min-height:44px;
  white-space: nowrap; overflow: hidden;
}
/* when column is narrow: hide label text, show only icon */
.crm-col-search .crm-col-label {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  transition: opacity .25s, max-width .3s;
  max-width: 120px;
}
.crm-badge {
  background:var(--bg4); border:1px solid var(--border2); border-radius:10px;
  font-size:11px; font-weight:700; color:var(--text2); padding:1px 7px;
  flex-shrink: 0;
}
/* badge moved to auto in head */
.crm-section-head .crm-badge { margin-right: auto; }

.crm-section-body {
  flex:1; overflow-y:auto; padding:8px 10px;
  scrollbar-width:thin; scrollbar-color:var(--border2) transparent;
}
.crm-section-body::-webkit-scrollbar { width:3px; }
.crm-section-body::-webkit-scrollbar-thumb { background:var(--border2); border-radius:3px; }

/* ── Empty / Spinner ── */
.crm-empty-state {
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  gap:7px; padding:36px 16px; color:var(--text3); font-size:12px; text-align:center;
}
.crm-empty-state i { font-size:26px; }

.crm-spinner { display:flex; align-items:center; justify-content:center; padding:36px; }
.crm-spinner::after {
  content:''; width:26px; height:26px;
  border:3px solid var(--border2); border-top-color:var(--accent);
  border-radius:50%; animation:crmSpin .7s linear infinite;
}

/* ── Service call card ── */
.crm-service-card {
  background:var(--bg3); border:1px solid var(--border); border-right:3px solid #8b5cf6;
  border-radius:6px; padding:9px 11px; margin-bottom:7px; cursor:pointer;
  transition:background .14s, transform .12s, opacity .14s;
}
.crm-service-card:hover { background:var(--bg4); transform:translateX(-2px); }
/* closed: dimmed, muted border */
.crm-service-card.sc-closed {
  border-right-color: var(--border2);
  opacity: .72;
}
.crm-service-card.sc-closed:hover { opacity: 1; }
.crm-service-card .sc-top-row {
  display:flex; align-items:center; justify-content:space-between; gap:6px; margin-bottom:4px;
}
.crm-service-card .sc-ticket-num {
  font-size:13px; font-weight:800; color:var(--text); letter-spacing:.02em;
}
.crm-service-card.sc-closed .sc-ticket-num { color:var(--text3); }
.crm-service-card .sc-desc {
  font-size:12px; font-weight:500; color:var(--text2); margin-bottom:5px; line-height:1.4;
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}
.crm-service-card .sc-dates {
  display:flex; gap:8px; flex-wrap:wrap; align-items:center;
  font-size:10px; color:var(--text3); margin-bottom:5px;
}
.crm-service-card .sc-meta { display:flex; gap:5px; flex-wrap:wrap; }
.crm-service-card .sc-chip {
  font-size:10px; background:var(--bg4); border:1px solid var(--border2);
  border-radius:10px; padding:2px 7px; color:var(--text2);
}
.crm-service-card .sc-status-open   { border-color:rgba(34,197,94,.4); color:#22c55e; background:rgba(34,197,94,.08); }
.crm-service-card .sc-status-closed { border-color:rgba(100,116,139,.3); color:var(--text3); background:rgba(100,116,139,.06); }
.sc-auto-row { display:flex; gap:5px; margin-top:6px; flex-wrap:wrap; }
.sc-auto-btn {
  display:inline-flex; align-items:center; gap:4px;
  background:rgba(91,141,238,.07); border:1px solid rgba(91,141,238,.2);
  border-radius:14px; padding:2px 8px; font-size:10px; font-weight:600;
  color:var(--accent); cursor:pointer; font-family:var(--font);
  transition:background .15s, border-color .15s;
}
.sc-auto-btn:hover { background:rgba(91,141,238,.18); border-color:rgba(91,141,238,.5); }

/* ── PBX call table ── */
.crm-pbx-wrap { font-size:12px; }
.crm-pbx-wrap a { color:#2196f3; }
.pbx-recbtn { display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border:1px solid rgba(33,150,243,.35);border-radius:20px;background:rgba(33,150,243,.1);color:#2196f3;cursor:pointer;font-size:11px;font-weight:600;font-family:var(--font);transition:all .13s;white-space:nowrap; }
.pbx-recbtn:hover { background:rgba(33,150,243,.2); }
.pbx-spin { border-radius:50%;border:2.5px solid var(--border2);border-top-color:#2196f3;animation:pbxspin .7s linear infinite; }

/* ── Note cards ── */
.crm-note-card {
  background:var(--bg3); border:1px solid var(--border); border-right:3px solid #10b981;
  border-radius:6px; padding:8px 10px; margin-bottom:6px; font-size:12px;
}
.crm-note-card .nc-agent { font-size:10px; color:var(--text3); margin-bottom:3px; }
.crm-note-card .nc-text  { color:var(--text2); line-height:1.5; }
.crm-note-card .nc-crit  { display:inline-flex;align-items:center;gap:4px;font-size:10px;color:#ef4444;font-weight:700;background:rgba(239,68,68,.1);border-radius:4px;padding:1px 6px;margin-bottom:4px; }
.crm-note-card .nc-full-btn { display:inline-flex;align-items:center;gap:4px;font-size:10px;color:var(--accent);background:var(--accent-dim);border-radius:4px;padding:2px 7px;margin-top:5px;cursor:pointer;border:1px solid rgba(91,141,238,.25);font-family:var(--font);transition:background .13s; }
.crm-note-card .nc-full-btn:hover { background:rgba(91,141,238,.25); }

/* ── Mini bar ── */
.crm-mini-bar {
  position:fixed; bottom:16px; left:50%; transform:translateX(-50%);
  display:flex; align-items:center; gap:12px; padding:10px 18px;
  background:var(--bg3); border:1px solid var(--border2); border-radius:12px;
  box-shadow:0 8px 32px rgba(0,0,0,.5); z-index:899;
  font-family:var(--font); font-size:14px; color:var(--text);
  animation:crmSlideUp .2s ease;
}
.crm-mini-timer { font-variant-numeric:tabular-nums; color:var(--accent); font-weight:700; }

/* ── Drag / Resize handles ── */
.crm-drag-handle { display:flex;align-items:center;padding:0 8px;color:var(--text3);cursor:grab;font-size:16px;opacity:.45;transition:opacity .15s;user-select:none; }
.crm-drag-handle:hover { opacity:1; }
.crm-drag-handle:active { cursor:grabbing; }
.crm-resize-handle { display:flex;align-items:center;justify-content:center;height:16px;background:var(--bg3);border-top:1px solid var(--border);cursor:ns-resize;color:var(--text3);font-size:12px;flex-shrink:0;opacity:.55;transition:opacity .15s,background .15s;user-select:none; }
.crm-resize-handle:hover { opacity:1; background:var(--bg4); }

/* ── Modals ── */
.crm-modal-wrap { position:fixed;inset:0;background:rgba(0,0,0,.52);backdrop-filter:blur(3px);z-index:960;display:flex;align-items:center;justify-content:center; }
.crm-modal {
  background:var(--bg2); border:1px solid var(--border2); border-radius:14px;
  box-shadow:var(--shadow,0 20px 60px rgba(0,0,0,.6)); width:min(480px,94vw);
  display:flex; flex-direction:column; overflow:hidden;
  font-family:var(--font); direction:rtl; animation:crmSlideIn .2s ease;
}
.crm-modal-head { display:flex;align-items:center;justify-content:space-between;padding:13px 15px;background:var(--bg3);border-bottom:1px solid var(--border);font-size:14px;font-weight:700;color:var(--text);gap:10px; }
.crm-modal-body { padding:15px; display:flex; flex-direction:column; gap:11px; }
.crm-modal-foot { display:flex;gap:10px;padding:11px 15px;border-top:1px solid var(--border);background:var(--bg3); }

.crm-form-row { display:flex; flex-direction:column; gap:5px; }
.crm-form-row label { font-size:12px; font-weight:600; color:var(--text2); }
.crm-form-row input, .crm-form-row textarea, .crm-form-row select {
  background:var(--bg4); border:1px solid var(--border2); border-radius:6px;
  color:var(--text); font-size:13px; padding:7px 11px; outline:none;
  font-family:var(--font); resize:vertical; transition:border-color .15s;
}
.crm-form-row input:focus, .crm-form-row textarea:focus, .crm-form-row select:focus { border-color:var(--accent); }
.crm-form-row-check { flex-direction:row; align-items:center; }
.crm-check-label { display:flex;align-items:center;gap:9px;font-size:13px;color:var(--text2);cursor:pointer; }
.crm-check-label input[type=checkbox] { display:none; }
.crm-check-box { width:17px;height:17px;border:1px solid var(--border2);border-radius:4px;background:var(--bg4);display:grid;place-items:center;flex-shrink:0;transition:background .14s,border-color .14s; }
.crm-check-label input[type=checkbox]:checked + .crm-check-box { background:var(--accent); border-color:var(--accent); }
.crm-check-box::after { content:'';width:5px;height:9px;border:2px solid #fff;border-top:none;border-right:none;transform:rotate(-45deg) translate(1px,-1px);opacity:0; }
.crm-check-label input[type=checkbox]:checked + .crm-check-box::after { opacity:1; }
.crm-form-msg { padding:8px 11px; border-radius:6px; font-size:13px; font-weight:600; }
.crm-form-msg.success { background:rgba(34,197,94,.12);color:#22c55e;border:1px solid rgba(34,197,94,.3); }
.crm-form-msg.error   { background:rgba(239,68,68,.12);color:#ef4444;border:1px solid rgba(239,68,68,.3); }

.crm-btn-primary { display:flex;align-items:center;gap:6px;padding:9px 17px;background:var(--accent);border:none;border-radius:8px;color:#fff;font-size:14px;font-weight:700;cursor:pointer;font-family:var(--font);transition:background .14s,transform .12s; }
.crm-btn-primary:hover { background:#4a7cdd; transform:translateY(-1px); }
.crm-btn-wa-send { background:#25d366; }
.crm-btn-wa-send:hover { background:#1db954; }
.crm-btn-spinner { display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:crmSpin .7s linear infinite;vertical-align:middle; }
.crm-btn-ghost { padding:9px 13px;background:transparent;border:1px solid var(--border2);border-radius:8px;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font);transition:background .14s; }
.crm-btn-ghost:hover { background:var(--bg4); color:var(--text); }
.crm-pill-btn { padding:6px 13px;background:var(--accent);border:none;border-radius:7px;color:#fff;font-size:12px;font-weight:700;cursor:pointer;font-family:var(--font);transition:background .14s; }
.crm-pill-btn:hover { background:#4a7cdd; }

.crm-radio-card { flex:1;display:flex;align-items:center;justify-content:center;gap:7px;padding:9px 12px;background:var(--bg3);border:1px solid var(--border2);border-radius:8px;font-size:13px;font-weight:600;color:var(--text2);cursor:pointer;transition:background .14s,border-color .14s,color .14s;user-select:none;position:relative; }
.crm-radio-card:hover { background:var(--bg4); color:var(--text); }
.crm-radio-card.crm-radio-selected-yes { background:rgba(37,211,102,.12);border-color:rgba(37,211,102,.5);color:#25d366; }
.crm-radio-card.crm-radio-selected-no  { background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.4);color:#ef4444; }

/* ── Service call detail ── */
.crm-sc-grid { display:grid; gap:7px; }
.crm-sc-row  { display:flex;justify-content:space-between;align-items:baseline;gap:8px;font-size:13px; }
.crm-sc-label{ color:var(--text3);font-size:11px;font-weight:600;flex-shrink:0; }
.crm-sc-val  { color:var(--text);font-weight:600;text-align:left; }
</style>

<!-- ════════════════════════════════════════════════════════ SCRIPT -->
<script>
(function() {
'use strict';

const BASE       = window.__V2_BASE || '';
const CRM_IGNORE = <?= json_encode($crmIgnore) ?>;
const CRM_DEPT   = <?= json_encode($crmDept) ?>;
const CRM_AGENT  = <?= json_encode($crmFullName) ?>;
const CAN_REC    = <?= $crmCanRec ? 'true' : 'false' ?>;
const INIT_PHONE = <?= json_encode($crmInitPhone) ?>;

/* ── State ── */
let state = {
  phone:     '',
  name:      '',
  timerSec:  0,
  timerInt:  null,
  open:      false,
  minimized: false,
};

/* ── DOM refs ── */
const $ = id => document.getElementById(id);

/* ── Timer ── */
function startTimer() {
  clearInterval(state.timerInt);
  state.timerSec = 0;
  state.timerInt = setInterval(() => {
    state.timerSec++;
    const h = Math.floor(state.timerSec / 3600);
    const m = Math.floor((state.timerSec % 3600) / 60);
    const s = state.timerSec % 60;
    const fmt = h
      ? `${pad(h)}:${pad(m)}:${pad(s)}`
      : `${pad(m)}:${pad(s)}`;
    $('crm-timer-display').textContent = fmt;
    $('crm-mini-timer').textContent    = fmt;
  }, 1000);
}
function pad(n) { return String(n).padStart(2,'0'); }

/* ── Open / Close ── */
function open(phone) {
  phone = String(phone || '').replace(/\D/g,'');
  if (!phone) return;
  if (CRM_IGNORE.includes(parseInt(phone))) return;

  state.phone     = phone;
  state.open      = true;
  state.minimized = false;

  // Apply prefs BEFORE adding .open so position is set before first paint
  const el = $('crm-popup');
  const prefs = crmLoadPrefs();
  if (prefs.size) {
    ['s','m','l'].forEach(s => {
      const btn = $('crm-size-' + s);
      if (btn) btn.classList.toggle('active', s === prefs.size);
    });
  }
  if (prefs.w) el.style.width  = Math.min(Math.max(prefs.w, 760), window.innerWidth  - 32) + 'px';
  if (prefs.h) el.style.height = Math.min(Math.max(prefs.h, 400), window.innerHeight - 32) + 'px';
  if (prefs.x != null && prefs.y != null) {
    el.classList.add('crm-positioned');
    el.style.left = Math.min(Math.max(prefs.x, 0), window.innerWidth  - (prefs.w || 920)) + 'px';
    el.style.top  = Math.min(Math.max(prefs.y, 0), window.innerHeight - (prefs.h || 560)) + 'px';
  }
  if (prefs.pbxRange)  { const s=$('crm-pbx-range');  if(s) s.value = prefs.pbxRange;  }
  if (prefs.pbxSource) { const s=$('crm-pbx-source'); if(s) s.value = prefs.pbxSource; }

  $('crm-phone-display').textContent = phone;
  $('crm-mini-phone').textContent    = phone;
  $('crm-note-phone').value          = phone;
  $('crm-wa-phone').value            = phone;
  $('crm-name-display').textContent  = '—';
  $('crm-name-block').style.display  = 'flex';
  $('crm-name-edit-block').style.display = 'none';
  document.title = '📞 ' + phone + ' | CRM';

  el.classList.add('open');
  el.style.display = 'flex';
  $('crm-overlay').classList.add('active');
  $('crm-mini-bar').style.display = 'none';

  const autoBtn = $('crm-auto-open-case');
  if (autoBtn) autoBtn.style.display = 'inline-flex';

  startTimer();
  loadAll(phone);
}

function close() {
  clearInterval(state.timerInt);
  state.open = false;
  $('crm-popup').classList.remove('open');
  $('crm-popup').style.display = 'none';
  $('crm-overlay').classList.remove('active');
  $('crm-mini-bar').style.display = 'none';
  const autoBtn = $('crm-auto-open-case');
  if (autoBtn) autoBtn.style.display = 'none';
  resetSections();
}

function minimize() {
  if (!state.open) return;
  state.minimized = true;
  $('crm-popup').classList.remove('open');
  $('crm-popup').style.display = 'none';
  $('crm-overlay').classList.remove('active');
  $('crm-mini-bar').style.display = 'flex';
}

function restore() {
  state.minimized = false;
  $('crm-popup').classList.add('open');
  $('crm-popup').style.display = 'flex';
  $('crm-overlay').classList.add('active');
  $('crm-mini-bar').style.display = 'none';
}

/* ── Load data ── */
function loadAll(phone) {
  setLoading('crm-customer-body');
  setLoading('crm-service-body');
  setLoading('crm-calls-body');
  loadCustomer(phone);
  loadService(phone);
  loadCalls(phone);
}

async function loadCustomer(phone) {
  try {
    const r = await fetch(`${BASE}/api/crm/calls?phone=${encodeURIComponent(phone)}`);
    const d = await r.json();
    renderCustomerPanel(phone, d.caller_name || '', d.critical_note || '');
    // load notes separately
    loadNotes(phone);
  } catch(e) {
    $('crm-customer-body').innerHTML = err('שגיאה בטעינת נתוני לקוח');
  }
}

async function loadNotes(phone) {
  try {
    const r = await fetch(`${BASE}/api/crm/notes?phone=${encodeURIComponent(phone)}`);
    const d = await r.json();
    const notes = d.data || [];
    // If we don't have a name yet, pull the most recent customer_name from notes
    if (!state.name) {
      const withName = notes.find(n => n.customer_name);
      if (withName) {
        state.name = withName.customer_name;
        $('crm-name-display').textContent = withName.customer_name;
        $('crm-note-name').value = withName.customer_name;
        $('crm-wa-name').value   = withName.customer_name;
        document.title = '📞 ' + withName.customer_name + ' (' + phone + ') | CRM';
        // Update the top of the customer panel if it already rendered
        const topName = document.querySelector('#crm-customer-body > div:first-child > div:first-child');
        if (topName) topName.textContent = withName.customer_name;
      }
    }
    renderNotes(notes);
  } catch(e) {
    // no notes endpoint yet — show empty
    renderNotes([]);
  }
}

function renderCustomerPanel(phone, name, criticalNote) {
  if (name) {
    state.name = name;
    $('crm-name-display').textContent = name;
    $('crm-note-name').value = name;
    $('crm-wa-name').value   = name;
    document.title = '📞 ' + name + ' (' + phone + ') | CRM';
  }
  if (criticalNote) {
    $('crm-critical-text').textContent = criticalNote;
    $('crm-critical-banner').style.display = 'flex';
  }
  // Customer panel top info
  const el = $('crm-customer-body');
  el.innerHTML = `
    <div style="padding:10px 12px 6px;">
      <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:2px;">${e(name || phone)}</div>
      <div style="font-size:11px;color:var(--text3);direction:ltr;">${e(phone)}</div>
    </div>
    <div style="height:1px;background:var(--border);margin:0 12px 8px;"></div>
    <div id="crm-notes-list" style="padding:0 8px;"></div>`;
}

function renderNotes(notes) {
  var cnt = $('crm-notes-count');
  var el  = document.getElementById('crm-notes-list');
  if (!el) return;
  if (!notes.length) {
    el.innerHTML = '<div class="crm-empty-state" style="padding:20px 10px;"><i class="bi bi-journal-x"></i><span>אין תיעודים</span></div>';
    cnt.style.display = 'none';
    return;
  }
  cnt.textContent   = notes.length;
  cnt.style.display = 'inline';
  el.innerHTML = notes.map(function(n) {
    var preview = (n.note || '').slice(0, 80);
    var full    = (n.note || '').length > 80;
    var dataAttr = 'data-note=\'' + encodeURIComponent(JSON.stringify(n)) + '\'';
    return '<div class="crm-note-card">'
      + (n.is_critical ? '<div class="nc-crit"><i class="bi bi-exclamation-triangle-fill"></i> קריטי</div>' : '')
      + '<div class="nc-agent">' + e(n.agent_name || '') + ' · ' + e(n.created_at || '') + '</div>'
      + (n.customer_name ? '<div style="font-size:12px;font-weight:600;color:var(--text);margin-bottom:3px;">' + e(n.customer_name) + '</div>' : '')
      + (preview ? '<div class="nc-text">' + e(preview) + (full ? '…' : '') + '</div>' : '')
      + (full ? '<button class="nc-full-btn" ' + dataAttr + ' onclick="CRM.showFullNote(JSON.parse(decodeURIComponent(this.dataset.note)))"><i class="bi bi-eye"></i> הצג הכל</button>' : '')
      + '</div>';
  }).join('');
}

function setLoading(id) {
  $(id).innerHTML = '<div class="crm-spinner"></div>';
}

async function loadService(phone) {
  try {
    const r = await fetch(`${BASE}/api/crm/service?phone=${encodeURIComponent(phone)}`);
    const d = await r.json();
    renderService(d.data || []);
  } catch(e) {
    $('crm-service-body').innerHTML = err('שגיאה בטעינת קריאות שירות');
  }
}

async function loadCalls(phone) {
  const range = document.getElementById('crm-pbx-range')?.value || 'last1week';
  try {
    const rd = await fetch(`${BASE}/api/crm/calls?phone=${encodeURIComponent(phone)}&range=${encodeURIComponent(range)}`, {credentials:'include'});
    const dd = await rd.json();
    if (dd.caller_name) {
      state.name = dd.caller_name;
      $('crm-name-display').textContent = dd.caller_name;
      $('crm-note-name').value = dd.caller_name;
      $('crm-wa-name').value   = dd.caller_name;
    }
    if (dd.critical_note) {
      $('crm-critical-text').textContent = dd.critical_note;
      $('crm-critical-banner').style.display = 'flex';
    }
    renderPbxCalls(dd);
  } catch(e) {
    $('crm-calls-body').innerHTML = err('שגיאה בטעינת שיחות');
  }
}

/* ── Service items cache for automation buttons ── */
const _scItems  = [];

/* ── Time helpers ── */
function relativeTime(dateStr) {
  if (!dateStr) return '';
  let d;
  if (/^\d{2}\/\d{2}\/\d{4}/.test(dateStr)) {
    const parts = dateStr.split('/');
    d = new Date(`${parts[2].slice(0,4)}-${parts[1]}-${parts[0]}T${parts[2].slice(5)||'00:00'}`);
  } else {
    d = new Date(dateStr);
  }
  if (isNaN(d)) return dateStr;
  const diff = Math.floor((Date.now() - d) / 1000);
  if (diff < 60)        return 'עכשיו';
  if (diff < 3600)      return `לפני ${Math.floor(diff/60)} דק׳`;
  if (diff < 86400)     return `לפני ${Math.floor(diff/3600)} שע׳`;
  if (diff < 2*86400)   return 'אתמול';
  if (diff < 7*86400)   return `לפני ${Math.floor(diff/86400)} ימים`;
  if (diff < 30*86400)  return `לפני ${Math.floor(diff/7)} שבועות`;
  if (diff < 365*86400) return `לפני ${Math.floor(diff/30)} חודשים`;
  return `לפני ${Math.floor(diff/365)} שנים`;
}

function daysBetween(d1Str, d2Str) {
  const parse = s => {
    if (!s) return null;
    if (/^\d{2}\/\d{2}\/\d{4}/.test(s)) {
      const p = s.split('/');
      return new Date(`${p[2].slice(0,4)}-${p[1]}-${p[0]}`);
    }
    return new Date(s);
  };
  const a = parse(d1Str);
  const b = d2Str ? parse(d2Str) : new Date();
  if (!a || isNaN(a) || !b || isNaN(b)) return null;
  return Math.round(Math.abs(b - a) / 86400000);
}

/* ── Render service calls ── */
function renderService(items) {
  _scItems.length = 0; items.forEach(x => _scItems.push(x));
  const el  = $('crm-service-body');
  const cnt = $('crm-service-count');
  if (!items.length) {
    el.innerHTML = '<div class="crm-empty-state"><i class="bi bi-gear"></i><span>אין קריאות שירות</span></div>';
    cnt.style.display = 'none';
    return;
  }
  cnt.textContent   = items.length;
  cnt.style.display = 'inline';

  el.innerHTML = items.map((i, idx) => {
    const isOpen   = (i.status||'').includes('פתוח') || (i.status||'').toLowerCase()==='open';
    const isClosed = !isOpen;
    const statusCls = isOpen ? 'sc-status-open' : 'sc-status-closed';
    const openRel   = relativeTime(i.open_date);
    const days      = daysBetween(i.open_date, isClosed ? (i.close_date||i.updated_at||null) : null);
    const daysColor = (!isClosed && days !== null) ? (days > 14 ? '#ef4444' : days > 7 ? '#f59e0b' : 'var(--text3)') : 'var(--text3)';
    const daysLabel = days !== null
      ? (isClosed
        ? `<span style="font-size:10px;color:#10b981;"><i class="bi bi-check2"></i> נסגרה תוך ${days} ימים</span>`
        : `<span style="font-size:10px;color:${daysColor};"><i class="bi bi-hourglass-split"></i> ${days} ימים פתוחה</span>`)
      : '';

    return `<div class="crm-service-card${isClosed?' sc-closed':''}" onclick="CRM.openScModal(CRM._scItems[${idx}])">
      <div class="sc-top-row">
        <span class="sc-ticket-num">#${e(i.ticket_id)}</span>
        <span class="sc-chip ${statusCls}" style="font-weight:700;">
          ${isClosed
            ? '<i class="bi bi-check-circle-fill"></i>'
            : '<i class="bi bi-record-circle-fill" style="color:#22c55e;font-size:8px;vertical-align:middle;"></i>'}
          ${e(i.status||'—')}
        </span>
      </div>
      <div class="sc-desc">${e(i.description||i.subject||'—')}</div>
      <div class="sc-dates">
        <span><i class="bi bi-calendar3"></i> ${e(i.open_date)}</span>
        <span style="color:var(--text3);">${openRel}</span>
        ${daysLabel}
      </div>
      <div class="sc-meta">
        <span class="sc-chip">${e(i.dept||'—')}</span>
        ${i.agent ? `<span class="sc-chip"><i class="bi bi-person-fill"></i> ${e(i.agent)}</span>` : ''}
        <span class="sc-chip" style="margin-right:auto;color:var(--accent);border-color:rgba(91,141,238,.3)"><i class="bi bi-chevron-left"></i> פרטים</span>
      </div>
      <div class="sc-auto-row" onclick="event.stopPropagation()">
        <button class="sc-auto-btn" onclick="CRM.openAutomationForCase(CRM._scItems[${idx}],'notifyOnChangeTo')">
          <i class="bi bi-arrow-left-right"></i> מעקב סטטוס
        </button>
        <button class="sc-auto-btn" onclick="CRM.openAutomationForCase(CRM._scItems[${idx}],'techCare')">
          <i class="bi bi-tools"></i> מעקב טכנאי
        </button>
      </div>
    </div>`;
  }).join('');
}

/* ── Render PBX calls (HTML from API) ── */
function renderPbxCalls(data) {
  const el  = $('crm-calls-body');
  const cnt = $('crm-calls-count');
  const rows = (data && Array.isArray(data.data)) ? data.data : [];
  if (!rows.length) {
    el.innerHTML = '<div class="crm-empty-state"><i class="bi bi-telephone-x"></i><span>אין שיחות בטווח הנבחר</span></div>';
    cnt.style.display = 'none';
    return;
  }
  const E = s => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const dirLabel = d => {
    if(d==='in'||d==='inbound')  return '<span style="font-size:11px;font-weight:600;color:#22c55e;"><i class="bi bi-telephone-inbound-fill"></i> נכנסת</span>';
    if(d==='out'||d==='outbound')return '<span style="font-size:11px;font-weight:600;color:#5b8dee;"><i class="bi bi-telephone-outbound-fill"></i> יוצאת</span>';
    return `<span style="font-size:11px;color:var(--text3);">${E(d)}</span>`;
  };
  const statLabel = s => {
    if(s==='answer')             return '<span style="padding:2px 7px;border-radius:10px;font-size:11px;font-weight:600;background:rgba(34,197,94,.12);color:#22c55e;"><i class="bi bi-check-circle-fill"></i> ענה</span>';
    if(s==='ivr')                return '<span style="padding:2px 7px;border-radius:10px;font-size:11px;font-weight:600;background:rgba(245,158,11,.12);color:#f59e0b;"><i class="bi bi-hourglass-split"></i> המתנה</span>';
    if(s==='noanswer'||s==='no answer') return '<span style="padding:2px 7px;border-radius:10px;font-size:11px;font-weight:600;background:rgba(239,68,68,.12);color:#ef4444;"><i class="bi bi-x-circle-fill"></i> לא ענה</span>';
    if(s==='busy')               return '<span style="padding:2px 7px;border-radius:10px;font-size:11px;font-weight:600;background:rgba(239,68,68,.12);color:#ef4444;"><i class="bi bi-dash-circle-fill"></i> תפוס</span>';
    if(s==='cancel')             return '<span style="padding:2px 7px;border-radius:10px;font-size:11px;font-weight:600;background:rgba(239,68,68,.08);color:#ef4444;"><i class="bi bi-slash-circle-fill"></i> בוטל</span>';
    return `<span style="font-size:11px;color:var(--text3);">${E(s)}</span>`;
  };
  const fmtDuration = (dur, secs) => {
    if(!secs || secs < 1) return '<span style="color:var(--text3);font-size:11px;">—</span>';
    const h=Math.floor(secs/3600), m=Math.floor((secs%3600)/60), s=secs%60;
    let label = '';
    if(h) label += h + '<span style="font-size:10px;font-weight:500;opacity:.7;">שע׳</span> ';
    if(m||h) label += m + '<span style="font-size:10px;font-weight:500;opacity:.7;">ד׳</span> ';
    label += s + '<span style="font-size:10px;font-weight:500;opacity:.7;">ש׳</span>';
    const color = secs>=120?'#22c55e':secs>=30?'#f59e0b':'var(--text3)';
    const bg    = secs>=120?'rgba(34,197,94,.08)':secs>=30?'rgba(245,158,11,.08)':'transparent';
    return `<span style="font-size:20px;font-weight:800;color:${color};font-variant-numeric:tabular-nums;background:${bg};padding:2px 7px;border-radius:6px;display:inline-block;line-height:1.3;">${label}</span>`;
  };
  const TH = 'text-align:right;padding:5px 8px;background:var(--bg3);color:var(--text3);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:1;white-space:nowrap;';
  const TD = 'padding:6px 8px;border-bottom:1px solid var(--border);vertical-align:middle;color:var(--text2);font-size:11px;';
  let h = `<table style="width:100%;border-collapse:collapse;"><tr>
    <th style="${TH}">זמן</th>
    <th style="${TH}">כיוון</th>
    <th style="${TH}">נציג</th>
    <th style="${TH}">משך</th>
    <th style="${TH}">סטטוס</th>
    ${CAN_REC ? `<th style="${TH}">הקלטה</th>` : ''}
  </tr>`;
  rows.forEach(row => {
    const agentHtml = row.agent_name
      ? `<div style="font-weight:600;font-size:11px;">${E(row.agent_name)}</div>`
      : `<span style="color:var(--text3);font-size:11px;">${E(row.agent_line)||'—'}</span>`;
    const recHtml = row.uniqueid
      ? `<a href="https://app.mvoice.co.il/#/calls/cdrs/edit/?callid=${encodeURIComponent(row.uniqueid)}&customer=8113&cost_customer=scustomer&include_tax=1&archive=0&sort=start&descending=0&detail=leg" target="_blank" class="pbx-recbtn" style="font-size:10px;padding:2px 7px;text-decoration:none;"><i class="bi bi-play-circle-fill"></i> הקלטה</a>`
      : '<span style="font-size:11px;color:var(--text3);">—</span>';
    const rel = relativeTime(row.call_time);
    h += `<tr onmouseenter="this.querySelectorAll('td').forEach(t=>t.style.background='var(--bg3)')" onmouseleave="this.querySelectorAll('td').forEach(t=>t.style.background='')">
      <td style="${TD}">
        <div style="font-weight:600;font-size:11px;white-space:nowrap;">${E(row.call_time)}</div>
        <div style="font-size:10px;color:var(--text3);margin-top:1px;">${rel}</div>
      </td>
      <td style="${TD}">${dirLabel(row.direction)}</td>
      <td style="${TD}">${agentHtml}</td>
      <td style="${TD}">${fmtDuration(row.duration, row.duration_sec)}</td>
      <td style="${TD}">${statLabel(row.status)}</td>
      ${CAN_REC ? `<td style="${TD}" id="pbxrec-${E(row.uniqueid)}">${recHtml}</td>` : ''}
    </tr>`;
  });
  h += '</table>';
  el.innerHTML = h;
  cnt.textContent = rows.length;
  cnt.style.display = 'inline';
}

// טעינת הקלטה lazy — גם אם pbx-search לא טעון
if (typeof window.pbxLoadRec === 'undefined') {
  window.pbxLoadRec = function(btn, uniqueid) {
    var td = btn.closest('td'); if(!td) return;
    td.innerHTML='<span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--text3);"><div class="pbx-spin" style="width:13px;height:13px;"></div> טוען...</span>';
    fetch('/api/crm/calls/recording?uniqueid='+encodeURIComponent(uniqueid),{credentials:'include'})
    .then(r=>r.json())
    .then(d=>{
      td.innerHTML = d.ok && d.url
        ? '<audio src="'+d.url+'" controls style="height:28px;max-width:200px;border-radius:6px;outline:none;display:block;"></audio>'
        : '<span style="font-size:11px;color:var(--text3);">אין הקלטה</span>';
    })
    .catch(()=>{ td.innerHTML='<span style="font-size:11px;color:var(--danger);">שגיאה</span>'; });
  };
}

function resetSections() {
  const empty = `<div class="crm-empty-state"><i class="bi bi-search"></i><span>ממתין לחיפוש…</span></div>`;
  $('crm-customer-body').innerHTML  = empty;
  $('crm-service-body').innerHTML   = empty;
  $('crm-calls-body').innerHTML     = empty;
  $('crm-notes-count').style.display    = 'none';
  $('crm-service-count').style.display  = 'none';
  $('crm-calls-count').style.display    = 'none';
  $('crm-critical-banner').style.display = 'none';
  // name block: show placeholder
  $('crm-name-display').textContent = '—';
  $('crm-name-block').style.display = 'flex';
  $('crm-name-edit-block').style.display = 'none';
}

/* ── Inline name edit ── */
function openNameEdit() {
  $('crm-name-input').value = state.name || '';
  $('crm-name-block').style.display      = 'none';
  $('crm-name-edit-block').style.display = 'flex';
  $('crm-name-input').focus();
  $('crm-name-input').select();
}
function cancelNameEdit() {
  $('crm-name-edit-block').style.display = 'none';
  $('crm-name-block').style.display      = 'flex';
}
async function saveNameInline() {
  const name  = $('crm-name-input').value.trim();
  const phone = state.phone;
  if (!name || !phone) { cancelNameEdit(); return; }

  // Optimistic update
  state.name = name;
  $('crm-name-display').textContent = name;
  cancelNameEdit();
  $('crm-note-name').value = name;
  $('crm-wa-name').value   = name;
  document.title = '📞 ' + name + ' (' + phone + ') | CRM';

  try {
    await fetch(`${BASE}/api/crm/note`, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ phone, name, note: '', critical: false, email: false })
    });
  } catch(ex) {}
}

/* ── Phone replace ── */
function togglePhoneReplace() {
  const bar = $('crm-phone-replace');
  const vis = bar.style.display !== 'none';
  bar.style.display = vis ? 'none' : 'flex';
  if (!vis) $('crm-phone-input').focus();
}
function cancelPhoneReplace() {
  $('crm-phone-replace').style.display = 'none';
  $('crm-phone-input').value = '';
}
function searchNewPhone() {
  const phone = $('crm-phone-input').value.replace(/\D/g,'');
  if (!phone || phone.length < 7) return;
  state.phone = phone;
  $('crm-phone-display').textContent = phone;
  $('crm-mini-phone').textContent    = phone;
  $('crm-note-phone').value          = phone;
  $('crm-wa-phone').value            = phone;
  cancelPhoneReplace();
  loadAll(phone);
}

/* ── Note modal ── */
function openNoteModal() {
  $('crm-note-msg').style.display = 'none';
  $('crm-note-modal').style.display = 'flex';
}
function closeNoteModal() {
  $('crm-note-modal').style.display = 'none';
}
async function saveNote() {
  const name     = $('crm-note-name').value.trim();
  const phone    = $('crm-note-phone').value.trim();
  const note     = $('crm-note-text').value.trim();
  const critical = $('crm-note-critical').checked;
  const email    = $('crm-note-email').checked;
  const msgEl    = $('crm-note-msg');

  if (!note && !name) {
    showMsg(msgEl, 'יש להזין שם או תיעוד', 'error');
    return;
  }

  try {
    const r = await fetch(`${BASE}/api/crm/note`, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ phone, name, note, critical, email })
    });
    const d = await r.json();
    if (d.ok) {
      showMsg(msgEl, 'נשמר בהצלחה ✓', 'success');
      // Update display name
      if (name) {
        state.name = name;
        $('crm-name-display').textContent = name;
        $('crm-name-block').style.display = 'flex';
      }
      setTimeout(() => closeNoteModal(), 1200);
    } else {
      showMsg(msgEl, d.error || 'שגיאה בשמירה', 'error');
    }
  } catch(e) {
    showMsg(msgEl, 'שגיאת רשת', 'error');
  }
}

/* ── WhatsApp modal ── */

// Map dept names → select values
const CRM_DEPT_MAP = {
  'תמיכה טכנית': 'support',
  'מכירות':       'sales',
  'שירות לקוחות': 'service',
};

function _waAssignUpdateCards() {
  const yes = document.getElementById('crm-wa-assign-yes');
  const no  = document.getElementById('crm-wa-assign-no');
  const yLbl = $('crm-wa-assign-yes-lbl');
  const nLbl = $('crm-wa-assign-no-lbl');
  if (!yes || !no) return;
  yLbl.classList.toggle('crm-radio-selected-yes', yes.checked);
  nLbl.classList.toggle('crm-radio-selected-no',  no.checked);
}

document.addEventListener('change', e => {
  if (e.target.name === 'crm_wa_assign') _waAssignUpdateCards();
});

function openWaModal() {
  const msgEl = $('crm-wa-msg');
  msgEl.style.display = 'none';
  msgEl.className = 'crm-form-msg';
  // Reset send button
  const btn = $('crm-wa-send-btn');
  if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send-fill"></i> שלח הודעה'; }
  // Reset dept — user must choose
  const deptSel = $('crm-wa-dept');
  if (deptSel) deptSel.value = '';
  // Reset assign radios — user must choose
  ['crm-wa-assign-yes','crm-wa-assign-no'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.checked = false;
  });
  _waAssignUpdateCards();
  $('crm-wa-modal').style.display = 'flex';
}
function closeWaModal() {
  $('crm-wa-modal').style.display = 'none';
}
async function sendWA() {
  const name   = $('crm-wa-name').value.trim();
  const phone  = $('crm-wa-phone').value.trim();
  const dept   = $('crm-wa-dept').value;
  const note   = $('crm-wa-note').value.trim();
  const assignRadio = document.querySelector('input[name="crm_wa_assign"]:checked');
  const assign = assignRadio ? assignRadio.value === 'yes' : null;
  const msgEl  = $('crm-wa-msg');
  const btn    = $('crm-wa-send-btn');

  if (!phone)         { showMsg(msgEl, 'אין מספר טלפון', 'error'); return; }
  if (!dept)          { showMsg(msgEl, 'נא לבחור מחלקה', 'error'); return; }
  if (assign === null){ showMsg(msgEl, 'נא לבחור אם לשייך את הטיקט אליך', 'error'); return; }

  // Show loader, block button
  btn.disabled = true;
  btn.innerHTML = '<span class="crm-btn-spinner"></span> שולח...';

  try {
    const r = await fetch(`${BASE}/api/crm/wa`, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ phone, name, dept, note, assign })
    });
    const d = await r.json();
    if (d.ok) {
      const ticketLink = d.ticket_url
        ? ` <a href="${e(d.ticket_url)}" target="_blank" rel="noopener"
               style="color:#25d366;text-decoration:underline;font-weight:700;">
             פתח טיקט <i class="bi bi-box-arrow-up-right"></i>
           </a>`
        : '';
      msgEl.innerHTML = '✓ הודעת WhatsApp נשלחה' + ticketLink;
      msgEl.className = 'crm-form-msg success';
      msgEl.style.display = 'block';
      setTimeout(() => closeWaModal(), 3500);
    } else {
      showMsg(msgEl, d.error || 'שגיאה בשליחה', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-send-fill"></i> שלח הודעה';
    }
  } catch(ex) {
    showMsg(msgEl, 'שגיאת רשת', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send-fill"></i> שלח הודעה';
  }
}

/* ── Formatter prefill ── */
function openFormatter() {
  minimize();
  if (typeof window.openFormatterModal === 'function') {
    window.openFormatterModal({
      name:  state.name,
      phone: state.phone,
    });
  } else {
    const params = new URLSearchParams({ phone: state.phone, name: state.name });
    window.open(`${BASE}/formatter?${params}`, '_blank');
  }
}

/* ── Helpers ── */
function e(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function err(msg) {
  return `<div class="crm-empty-state"><i class="bi bi-exclamation-circle"></i><span>${msg}</span></div>`;
}
function showMsg(el, msg, type) {
  el.textContent = msg;
  el.className = `crm-form-msg ${type}`;
  el.style.display = 'block';
}

/* ── Size presets ── */
const CRM_SIZES = {
  s: { w: 860,  h: 440 },
  m: { w: 1160, h: 560 },
  l: { w: 1400, h: 700 },
};
function setSize(sz) {
  const el   = $('crm-popup');
  const dims = CRM_SIZES[sz] || CRM_SIZES.m;
  const w    = Math.min(dims.w, window.innerWidth  - 32);
  const h    = Math.min(dims.h, window.innerHeight - 32);
  el.style.width  = w + 'px';
  el.style.height = h + 'px';
  // Re-center if not manually positioned
  if (!el.classList.contains('crm-positioned')) {
    el.style.left = '50%';
    el.style.top  = '50%';
  }
  // Mark active button
  ['s','m','l'].forEach(s => {
    const btn = $('crm-size-' + s);
    if (btn) btn.classList.toggle('active', s === sz);
  });
  crmSavePrefs({ w, h, size: sz });
}

/* ── Show full note in sc modal ── */
function showFullNote(n) {
  $('crm-sc-modal').style.display = 'flex';
  $('crm-sc-title').textContent   = 'תיעוד שיחה';
  var html = '<div style="background:var(--bg3);border:1px solid var(--border);border-right:3px solid #10b981;border-radius:var(--radius-sm);padding:12px 14px;">'
    + '<div style="font-size:10px;font-weight:700;color:#10b981;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;"><i class=\'bi bi-journal-text\'></i> תיעוד</div>'
    + (n.is_critical ? '<div style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:#ef4444;font-weight:700;background:rgba(239,68,68,.1);border-radius:4px;padding:2px 8px;margin-bottom:8px;"><i class=\'bi bi-exclamation-triangle-fill\'></i> הערה קריטית</div>' : '')
    + '<div class="crm-sc-grid">'
    + (n.agent_name    ? '<div class="crm-sc-row"><span class="crm-sc-label">נציג</span><span class="crm-sc-val">' + e(n.agent_name)    + '</span></div>' : '')
    + (n.created_at    ? '<div class="crm-sc-row"><span class="crm-sc-label">תאריך</span><span class="crm-sc-val">' + e(n.created_at)   + '</span></div>' : '')
    + (n.customer_name ? '<div class="crm-sc-row"><span class="crm-sc-label">שם לקוח</span><span class="crm-sc-val">' + e(n.customer_name) + '</span></div>' : '')
    + '</div>'
    + (n.note ? '<div style="margin-top:10px;font-size:13px;color:var(--text2);line-height:1.7;white-space:pre-wrap;">' + e(n.note) + '</div>' : '')
    + '</div>';
  $('crm-sc-body').innerHTML = html;
}

/* ── Service call detail modal ── */
async function openScModal(item) {
  const modal = $('crm-sc-modal');
  const title = $('crm-sc-title');
  const body  = $('crm-sc-body');

  // מזעיר את ה-CRM
  minimize();

  modal.style.display = 'flex';
  title.textContent   = 'קריאה #' + (item.ticket_id || '—');
  body.innerHTML      = '<div style="display:flex;align-items:center;justify-content:center;padding:40px;gap:10px;color:var(--text3);"><div class="crm-spinner"></div><span>טוען פרטים...</span></div>';

  try {
    const r = await fetch(`${BASE}/api/wize/call?id=${encodeURIComponent(item.ticket_id)}`, { credentials: 'include' });
    const d = await r.json();
    if (d.ok && typeof renderWizCall === 'function') {
      renderWizCall(body, d, false, '');
    } else {
      // fallback — פרטים בסיסיים מהנתונים הקיימים
      _renderScFallback(body, item);
    }
  } catch {
    _renderScFallback(body, item);
  }
}

function _renderScFallback(body, item) {
  function row(label, val) {
    if (!val) return '';
    return '<div class="crm-sc-row"><span class="crm-sc-label">' + label + '</span><span class="crm-sc-val">' + e(val) + '</span></div>';
  }
  var html = '<div style="background:var(--bg3);border:1px solid var(--border);border-right:3px solid #8b5cf6;border-radius:var(--radius-sm);padding:12px 14px;">'
    + '<div class="crm-sc-grid">'
    + row('מספר קריאה', item.ticket_id)
    + row('תאריך פתיחה', item.open_date)
    + row('סטטוס', item.status)
    + row('נציג', item.agent)
    + row('מחלקה / מקור', item.dept)
    + row('חברה', item.company)
    + row('סניף', item.branch)
    + row('איש קשר', item.contact)
    + '</div></div>';
  if (item.description) {
    html += '<div style="background:var(--bg3);border:1px solid var(--border);border-right:3px solid #06b6d4;border-radius:var(--radius-sm);padding:12px 14px;margin-top:8px;">'
      + '<div style="font-size:13px;color:var(--text2);line-height:1.6;">' + e(item.description) + '</div></div>';
  }
  body.innerHTML = html;
}

function closeScModal() {
  $('crm-sc-modal').style.display = 'none';
  // משחזר את ה-CRM אם היה מזוער
  if (state.minimized) restore();
}


/* ── Prefs + Drag + Resize ────────────────────────────────────── */
const CRM_STORE_KEY = 'crm_popup_prefs';

function crmLoadPrefs() {
  try { return JSON.parse(localStorage.getItem(CRM_STORE_KEY) || '{}'); } catch(e) { return {}; }
}
function crmSavePrefs(patch) {
  const p = {...crmLoadPrefs(), ...patch};
  localStorage.setItem(CRM_STORE_KEY, JSON.stringify(p));
}

function crmApplyPrefs() {
  const p   = crmLoadPrefs();
  const el  = $('crm-popup');
  // Size preset
  if (p.size) {
    ['s','m','l'].forEach(s => {
      const btn = $('crm-size-' + s);
      if (btn) btn.classList.toggle('active', s === p.size);
    });
  }
  // Size
  if (p.w) el.style.width  = Math.min(Math.max(p.w, 760), window.innerWidth  - 32) + 'px';
  if (p.h) el.style.height = Math.min(Math.max(p.h, 400), window.innerHeight - 32) + 'px';
  // Position
  if (p.x != null && p.y != null) {
    el.classList.add('crm-positioned');
    el.style.left = Math.min(Math.max(p.x, 0), window.innerWidth  - (p.w || 920)) + 'px';
    el.style.top  = Math.min(Math.max(p.y, 0), window.innerHeight - (p.h || 560)) + 'px';
  }
  // PBX defaults
  if (p.pbxRange)  { const s=$('crm-pbx-range');  if(s) s.value = p.pbxRange;  }
  if (p.pbxSource) { const s=$('crm-pbx-source'); if(s) s.value = p.pbxSource; }
}

/* Drag to move */
(function() {
  let dragging=false, ox=0, oy=0, sx=0, sy=0;
  document.addEventListener('DOMContentLoaded', () => {
    const handle = $('crm-drag-handle');
    const popup  = $('crm-popup');
    if (!handle || !popup) return;

    handle.addEventListener('mousedown', e => {
      if (e.button !== 0) return;
      e.preventDefault();
      dragging = true;
      const rect = popup.getBoundingClientRect();
      // Switch from transform-center to absolute coords
      if (!popup.classList.contains('crm-positioned')) {
        popup.classList.add('crm-positioned');
        popup.style.left = rect.left + 'px';
        popup.style.top  = rect.top  + 'px';
      }
      ox = e.clientX - rect.left;
      oy = e.clientY - rect.top;
      popup.style.transition = 'none';
      document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', e => {
      if (!dragging) return;
      const x = Math.min(Math.max(e.clientX - ox, 0), window.innerWidth  - popup.offsetWidth);
      const y = Math.min(Math.max(e.clientY - oy, 0), window.innerHeight - popup.offsetHeight);
      popup.style.left = x + 'px';
      popup.style.top  = y + 'px';
    });

    document.addEventListener('mouseup', e => {
      if (!dragging) return;
      dragging = false;
      popup.style.transition = '';
      document.body.style.userSelect = '';
      crmSavePrefs({ x: parseInt(popup.style.left), y: parseInt(popup.style.top) });
    });
  });
})();

/* Resize from bottom */
(function() {
  let resizing=false, startY=0, startH=0, startW=0;
  document.addEventListener('DOMContentLoaded', () => {
    const handle = $('crm-resize-handle');
    const popup  = $('crm-popup');
    if (!handle || !popup) return;

    handle.addEventListener('mousedown', e => {
      if (e.button !== 0) return;
      e.preventDefault();
      resizing = true;
      startY   = e.clientY;
      startH   = popup.offsetHeight;
      startW   = popup.offsetWidth;
      popup.style.transition = 'none';
      document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', e => {
      if (!resizing) return;
      const newH = Math.min(Math.max(startH + (e.clientY - startY), 400), window.innerHeight - 32);
      popup.style.height = newH + 'px';
    });

    document.addEventListener('mouseup', () => {
      if (!resizing) return;
      resizing = false;
      popup.style.transition = '';
      document.body.style.userSelect = '';
      crmSavePrefs({ w: popup.offsetWidth, h: popup.offsetHeight });
    });
  });
})();

/* Save PBX filter changes */
document.addEventListener('DOMContentLoaded', () => {
  const pr = $('crm-pbx-range');
  const ps = $('crm-pbx-source');
  if (pr) pr.addEventListener('change', () => crmSavePrefs({ pbxRange:  pr.value }));
  if (ps) ps.addEventListener('change', () => crmSavePrefs({ pbxSource: ps.value }));
});

/* ── Event listeners ── */
document.addEventListener('DOMContentLoaded', () => {
  $('crm-close').onclick     = close;
  $('crm-minimize').onclick  = minimize;
  $('crm-mini-restore').onclick = restore;
  $('crm-mini-close').onclick   = close;
  $('crm-change-phone').onclick = togglePhoneReplace;
  $('crm-phone-cancel').onclick = cancelPhoneReplace;
  $('crm-phone-search-btn').onclick = searchNewPhone;
  $('crm-btn-note').onclick  = openNoteModal;
  $('crm-btn-wa').onclick    = openWaModal;
  $('crm-btn-fmt').onclick   = openFormatter;
  $('crm-overlay').onclick   = minimize;
  $('crm-pbx-refresh').onclick = () => { if (state.phone) { setLoading('crm-calls-body'); loadCalls(state.phone); } };

  // name edit
  $('crm-edit-name-btn').onclick  = openNameEdit;
  $('crm-name-cancel-btn').onclick = cancelNameEdit;
  $('crm-name-save-btn').onclick   = saveNameInline;
  $('crm-name-input').addEventListener('keydown', ev => {
    if (ev.key === 'Enter')  saveNameInline();
    if (ev.key === 'Escape') cancelNameEdit();
  });

  // Enter key in phone input
  $('crm-phone-input').addEventListener('keydown', ev => {
    if (ev.key === 'Enter') searchNewPhone();
    if (ev.key === 'Escape') cancelPhoneReplace();
  });

  // Escape closes modals
  document.addEventListener('keydown', ev => {
    if (ev.key === 'Escape') {
      if ($('crm-note-modal').style.display !== 'none') { closeNoteModal(); return; }
      if ($('crm-wa-modal').style.display !== 'none')   { closeWaModal();   return; }
      if ($('crm-sc-modal').style.display  !== 'none') { closeScModal();  return; }
      if (state.open && !state.minimized) minimize();
    }
  });

  // Auto-open if caller param present
  if (INIT_PHONE) {
    open(INIT_PHONE);
  }
});

/* ── Expose global API ── */
function openAutomationForPhone() {
  if (!window.openAutomationModal) return;
  minimize();
  window.openAutomationModal({ type: 'openCaseByPhone', phone: state.phone || '' });
}

function openAutomationForCase(item, type) {
  if (!window.openAutomationModal) return;
  minimize();
  window.openAutomationModal({
    type,
    caseNum: String(item.ticket_id || ''),
    phone:   state.phone || '',
  });
}

window.CRM = {
  open,
  close,
  minimize,
  restore,
  openNoteModal,
  closeNoteModal,
  saveNote,
  openWaModal,
  closeWaModal,
  sendWA,
  openFormatter,
  openScModal,
  closeScModal,
  showFullNote,
  setSize,
  loadNotes,
  renderNotes,
  resetPosition: () => {
    const el = $('crm-popup');
    el.classList.remove('crm-positioned');
    el.style.left = el.style.top = el.style.width = el.style.height = '';
    localStorage.removeItem(CRM_STORE_KEY);
  },
  getState: () => ({ ...state }),
  openAutomationForPhone,
  openAutomationForCase,
  openNameEdit,
  cancelNameEdit,
  saveNameInline,
  _scItems,
};

})();

/* כשסוגרים modal אוטומציה — משחזרים CRM אם היה מזוער */
window.addEventListener('load', () => {
  const _origClose = window.closeAutomationModal;
  if (typeof _origClose === 'function') {
    window.closeAutomationModal = function() {
      _origClose();
      if (window.CRM && window.CRM.getState().minimized) window.CRM.restore();
    };
  }
});
</script>
<?php /* ── Inject into main layout if not already triggered via URL ── */ ?>