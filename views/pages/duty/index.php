<?php
use Core\View;
use Core\Auth;
$base    = rtrim(CFG['app']['url'], '/');
$csrf    = $_SESSION['csrf_token'] ?? '';
$canEdit = Auth::can('canManageDuty');
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div class="page-title" style="margin-bottom:0;"><i class="bi bi-person-lines-fill" style="margin-left:8px;"></i>ניהול תורנות שבועית</div>
</div>

<!-- Tabs -->
<div style="display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:20px;">
  <button class="duty-tab active" data-tab="schedule" onclick="dutySetTab('schedule')"><i class="bi bi-calendar-week"></i> תורנות ניקיון</button>
  <button class="duty-tab" data-tab="reps" onclick="dutySetTab('reps')"><i class="bi bi-people-fill"></i> נציגים</button>
  <button class="duty-tab" data-tab="guidance" onclick="dutySetTab('guidance')"><i class="bi bi-journal-text"></i> הנחיות יומיות לניקיון</button>
  <button class="duty-tab" data-tab="roles" onclick="dutySetTab('roles')"><i class="bi bi-grid-3x3-gap-fill"></i> תורנות גלאס ושיחות</button>
</div>

<!-- ── Tab: תורנויות ── -->
<div id="duty-tab-schedule" class="duty-tab-panel">
  <?php if ($canEdit): ?>
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
    <button class="btn btn-primary btn-sm" onclick="dutyAutoAssign()"><i class="bi bi-magic"></i> שבוע הבא</button>
    <button class="btn btn-ghost btn-sm" onclick="dutyOpenMultiAutoModal()"><i class="bi bi-magic"></i> שיבוץ אוטומטי מרובה</button>
    <button class="btn btn-ghost btn-sm" onclick="dutyOpenManualModal()"><i class="bi bi-pencil-fill"></i> שיבוץ ידני</button>
  </div>
  <?php endif; ?>
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

<!-- ── Tab: תורנות יומית (גלאס / שיחות / שניהם) ── -->
<div id="duty-tab-roles" class="duty-tab-panel" style="display:none;">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
    <div style="display:flex;align-items:center;gap:8px;">
      <button class="btn btn-ghost btn-sm" onclick="dutyRolesShiftWeek(7)"><i class="bi bi-chevron-right"></i></button>
      <div id="duty-roles-weeklabel" style="font-size:14px;font-weight:700;min-width:190px;text-align:center;">&nbsp;</div>
      <button class="btn btn-ghost btn-sm" onclick="dutyRolesShiftWeek(-7)"><i class="bi bi-chevron-left"></i></button>
      <button class="btn btn-ghost btn-sm" onclick="dutyRolesGoToday()">היום</button>
      <span id="duty-roles-dirty" class="duty-dirty" style="display:none;"><i class="bi bi-dot"></i><span id="duty-dirty-count"></span></span>
    </div>
    <div style="display:flex;align-items:center;gap:14px;font-size:12px;color:var(--text2);flex-wrap:wrap;">
      <span><i class="bi bi-chat-dots-fill" style="color:#a855f7;"></i> גלאס</span>
      <span><i class="bi bi-telephone-fill" style="color:#38bdf8;"></i> שיחות</span>
      <span style="opacity:.7;border:1px dashed var(--border);border-radius:6px;padding:2px 8px;"><i class="bi bi-layers-fill" style="color:#22c55e;"></i> גלאס + שיחות</span>
      <span style="color:#f59e0b;">● שינוי לא שמור</span>
    </div>
  </div>

  <!-- ── סרגל סינון מחלקות ── -->
  <div class="filter-bar">
    <span class="filter-bar-title"><i class="bi bi-funnel-fill"></i> סינון לפי מחלקה</span>
    <span class="filter-bar-sep"></span>
    <div class="dept-filter" id="dept-filter"></div>
    <span class="filter-bar-hint" id="dept-filter-hint"></span>
  </div>
  <div id="duty-roles-content">
    <div style="text-align:center;padding:40px;color:var(--text3);"><i class="bi bi-hourglass-split" style="font-size:24px;display:block;margin-bottom:8px;"></i>טוען...</div>
  </div>
  <div style="margin-top:12px;font-size:12px;color:var(--text3);">
    <i class="bi bi-info-circle"></i> כל נציג מקבל אוטומטית <b>גלאס + שיחות</b> אלא אם נבחר אחרת. השינויים נשמרים רק בלחיצה על <b>שמור</b>.
  </div>
</div>

<div id="duty-float" role="status" aria-live="polite">
  <i class="bi bi-check-circle-fill"></i><span id="duty-float-txt"></span>
</div>

<?php if ($canEdit): ?>
<!-- ── סרגל שמירה צף ── -->
<div id="duty-savebar" role="region" aria-label="שמירת שינויים">
  <span class="savebar-txt"><i class="bi bi-pencil-fill"></i><span id="savebar-count"></span></span>
  <button id="duty-roles-discard" class="btn btn-ghost btn-sm" onclick="dutyRolesDiscard()"><i class="bi bi-arrow-counterclockwise"></i> בטל</button>
  <button id="duty-roles-save" class="btn btn-primary btn-sm" onclick="dutyRolesSave()"><i class="bi bi-check-lg"></i> שמור</button>
</div>
<?php endif; ?>

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
/* ── הודעת FLOAT לשמירה בטבלה היומית ── */
#duty-float{position:fixed;bottom:26px;left:50%;transform:translateX(-50%) translateY(10px);z-index:99999;display:none;align-items:center;gap:9px;padding:10px 18px;border-radius:12px;font-size:13.5px;font-weight:600;font-family:var(--font);background:var(--bg2);color:var(--text);border:1px solid var(--border);box-shadow:0 10px 34px rgba(0,0,0,.45);pointer-events:none;opacity:0;transition:opacity .18s ease,transform .18s ease;}
#duty-float.show{display:flex;opacity:1;transform:translateX(-50%) translateY(0);}
#duty-float i{font-size:15px;}
#duty-float.is-saving{color:var(--text2);}
#duty-float.is-ok{color:#22c55e;border-color:rgba(34,197,94,.4);background:linear-gradient(135deg,rgba(34,197,94,.14),var(--bg2));}
#duty-float.is-err{color:var(--danger);border-color:rgba(239,68,68,.45);background:linear-gradient(135deg,rgba(239,68,68,.14),var(--bg2));}
#duty-float .duty-float-spin{display:inline-block;animation:dutyFloatSpin .8s linear infinite;}
@keyframes dutyFloatSpin{to{transform:rotate(360deg);}}
@media (prefers-reduced-motion:reduce){#duty-float{transition:none;} #duty-float .duty-float-spin{animation:none;}}
/* ── סרגל שמירה צף ── */
#duty-savebar{
  position:fixed;bottom:26px;left:50%;
  transform:translateX(-50%) translateY(14px);
  z-index:99998;display:none;align-items:center;gap:10px;
  padding:10px 12px 10px 16px;border-radius:14px;
  background:var(--bg2);border:1px solid rgba(245,158,11,.45);
  box-shadow:0 12px 38px rgba(0,0,0,.5);
  opacity:0;transition:opacity .18s ease,transform .18s ease;
}
#duty-savebar.show{display:flex;opacity:1;transform:translateX(-50%) translateY(0);}
#duty-savebar .savebar-txt{
  display:inline-flex;align-items:center;gap:7px;
  font-size:13px;font-weight:700;color:#f59e0b;
  font-family:var(--font);white-space:nowrap;padding-inline-end:4px;
}
#duty-savebar .savebar-txt i{font-size:12px;}
/* כשהסרגל פתוח, ההודעה הצפה עולה מעליו כדי לא להסתיר אותו */
#duty-float.above-savebar{bottom:92px;}
@media (prefers-reduced-motion:reduce){#duty-savebar{transition:none;}}
@media (max-width:520px){
  #duty-savebar{left:12px;right:12px;transform:translateY(14px);width:auto;justify-content:space-between;}
  #duty-savebar.show{transform:translateY(0);}
}

/* ── סרגל סינון ── */
.filter-bar{
  display:flex;align-items:center;gap:10px;flex-wrap:wrap;
  margin-bottom:14px;padding:9px 14px;
  background:var(--bg3);
  border:1px solid var(--border);
  border-radius:10px;
  border-inline-start:3px solid var(--accent);
}
.filter-bar-title{
  display:inline-flex;align-items:center;gap:6px;flex:none;
  font-size:11.5px;font-weight:800;letter-spacing:.02em;
  color:var(--text2);white-space:nowrap;
}
.filter-bar-title i{color:var(--accent);font-size:12px;}
.filter-bar-sep{width:1px;align-self:stretch;background:var(--border);flex:none;margin:1px 2px;}
.filter-bar-hint{
  font-size:11px;color:var(--text3);margin-inline-start:auto;
  white-space:nowrap;flex:none;
}
@media (max-width:620px){
  .filter-bar{padding:9px 11px;}
  .filter-bar-sep{display:none;}
  .filter-bar-hint{margin-inline-start:0;width:100%;}
}

/* ── מסנן מחלקות — כפתורי toggle ── */
.dept-filter{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.dept-btn{
  display:inline-flex;align-items:center;gap:5px;
  border:1px solid var(--border);border-radius:999px;
  padding:4px 11px 4px 9px;
  font-size:11.5px;font-weight:700;font-family:var(--font);
  cursor:pointer;white-space:nowrap;
  background:var(--bg3);color:var(--text2);
  transition:opacity .13s,border-color .13s,background .13s,filter .13s;
}
.dept-btn i{font-size:11px;flex:none;}
.dept-btn:hover{filter:brightness(1.15);}
/* מחלקה מוצגת — צבע מלא של המחלקה */
.dept-btn.dept-service{background:rgba(239,68,68,.16);border-color:rgba(239,68,68,.45);color:#ef4444;}
.dept-btn.dept-support{background:rgba(91,141,238,.16);border-color:rgba(91,141,238,.45);color:var(--accent);}
.dept-btn.dept-internet{background:rgba(34,197,94,.16);border-color:rgba(34,197,94,.45);color:#22c55e;}
/* מחלקה מוסתרת — אפור, מקווקו, עם ✕ אדום */
.dept-btn.off{
  background:transparent !important;
  border-color:var(--border) !important;
  border-style:dashed;
  color:var(--text3) !important;
  opacity:.65;
}
.dept-btn.off:hover{opacity:1;}
.dept-btn.off span{text-decoration:line-through;text-decoration-thickness:1px;}
.dept-btn .dept-btn-x{color:#ef4444 !important;font-weight:900;}
/* כפתור איפוס */
.dept-btn-reset{
  background:transparent;border-style:dashed;color:var(--text3);
  padding:4px 11px;
}
.dept-btn-reset:hover{color:var(--text);border-color:var(--text3);}

/* ── תאי בחירה (select) בטבלה היומית ── */
.role-cell{padding:3px 7px;text-align:center;}
.role-sel-wrap{
  position:relative;display:flex;align-items:center;justify-content:center;gap:5px;
  border:1px solid var(--border);border-radius:7px;background:var(--bg3);
  padding:0 6px;min-height:26px;
  transition:border-color .12s,background .12s,opacity .12s;
}
.role-sel-ico{font-size:11px;flex:none;pointer-events:none;}
.role-sel{
  flex:0 1 auto;min-width:0;appearance:none;-webkit-appearance:none;
  background:transparent;border:none;outline:none;cursor:pointer;
  color:inherit;font-family:var(--font);font-size:15px;font-weight:600;
  padding:3px 0;text-overflow:ellipsis;
  /* text-align לבדו לא ממרכז את הערך הסגור ב-select */
  text-align:center;text-align-last:center;
}
.role-sel option{background:var(--bg2);color:var(--text);font-weight:500;font-size:13px;}
.role-sel:focus-visible{outline:2px solid var(--accent);outline-offset:1px;border-radius:4px;}
.role-sel-wrap:hover{border-color:var(--text3);}
.role-sel-wrap.on-glassix{background:rgba(168,85,247,.16);border-color:rgba(168,85,247,.5);color:#a855f7;}
.role-sel-wrap.on-calls{background:rgba(56,189,248,.16);border-color:rgba(56,189,248,.5);color:#38bdf8;}
.role-sel-wrap.on-both{background:rgba(34,197,94,.16);border-color:rgba(34,197,94,.5);color:#22c55e;}
/* ללא שיבוץ ידני — אותו תפקיד (גלאס + שיחות) אך מעומעם ומקווקו */
.role-sel-wrap.is-default{background:transparent;border-style:dashed;opacity:.5;}
.role-sel-wrap.is-default:hover{opacity:1;}
/* שינוי שטרם נשמר */
.role-sel-wrap.is-dirty{box-shadow:0 0 0 2px rgba(245,158,11,.5);border-color:rgba(245,158,11,.7);}
.role-sel-wrap.is-dirty::after{
  content:'';position:absolute;top:-3px;inset-inline-end:-3px;
  width:7px;height:7px;border-radius:50%;background:#f59e0b;
}
.role-sel-wrap:has(.role-sel:disabled){cursor:default;}
.role-sel:disabled{cursor:default;}

/* ── תא הנציג: ספירה (שמאל) | שם | מחלקה אנכית (ימין) ── */
.rep-cell{
  padding:3px 8px 3px 6px;
  display:flex;align-items:center;gap:7px;
  flex-direction:row-reverse;   /* RTL: המחלקה בקצה הימני, הספירה בשמאלי */
}
.rep-cell .rep-name{
  flex:1;min-width:0;font-weight:600;font-size:13px;white-space:nowrap;
  overflow:hidden;text-overflow:ellipsis;text-align:center;
}
/* שם המחלקה — מסובב אנכית, קטן, בקצה ימין */
.dept-vert{
  flex:none;writing-mode:vertical-rl;transform:rotate(180deg);
  font-size:8.5px;font-weight:700;letter-spacing:.02em;
  line-height:1;padding:2px 1px;border-radius:3px;
  max-height:40px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}
/* ספירות — טקסט צבעוני בלבד, בלי באדג' */
.rep-counts{
  flex:none;display:flex;flex-direction:column;align-items:center;
  gap:0;min-width:44px;
}
.rep-counts .cnt{
  font-size:10px;font-weight:800;line-height:1.25;
  white-space:nowrap;background:none;padding:0;border-radius:0;
}
.cnt-glassix{color:#a855f7;}
.cnt-calls{color:#38bdf8;}

/* ── כפתורי מיון בכותרת ── */
.rep-th{display:flex;align-items:center;justify-content:center;gap:8px;}
.rep-th .sort-btns{display:flex;gap:3px;}
.sort-btn{
  display:inline-flex;align-items:center;gap:2px;
  background:transparent;border:1px solid var(--border);border-radius:5px;
  padding:1px 5px;font-size:9.5px;font-weight:700;font-family:var(--font);
  color:var(--text3);cursor:pointer;white-space:nowrap;
  transition:color .12s,border-color .12s,background .12s;
}
.sort-btn i{font-size:10px;}
.sort-btn:hover{color:var(--text);border-color:var(--text3);}
.sort-btn.on{color:var(--accent);border-color:var(--accent);background:rgba(91,141,238,.1);}
.sort-glassix i:first-child{color:#a855f7;}
.sort-calls i:first-child{color:#38bdf8;}

/* ── טבלה קומפקטית ── */
.roles-table th{padding:6px 8px !important;text-align:center;}
.roles-table th,.roles-table td{border-bottom:1px solid var(--border);}
.roles-table tbody tr{transition:background .1s;}
.roles-table tbody tr:hover{background:rgba(255,255,255,.028);}
.roles-table tbody tr:last-child td{border-bottom:none;}
/* קו עדין מפריד בין ימי השבוע.
   הקו על הקצה המתחיל (RTL = ימין) של כל תא יום,
   כלומר גם מפריד את עמודת הנציג מהיום הראשון. */
.roles-table th:not(:first-child),
.roles-table td.role-cell{border-inline-start:1px solid var(--border);}
.roles-table td.role-today,.roles-table th.role-today{background:rgba(91,141,238,.07);}
.roles-table th.role-today{box-shadow:inset 0 -2px 0 var(--accent);}
.roles-table .day-num{font-weight:500;opacity:.6;font-size:10px;}

.dept-chip{display:inline-block;padding:2px 9px;border-radius:12px;font-size:11px;font-weight:600;}
.dept-service{background:rgba(239,68,68,.12);color:#ef4444;}
.dept-support{background:rgba(91,141,238,.12);color:var(--accent);}
.dept-internet{background:rgba(34,197,94,.12);color:#22c55e;}
</style>

<script>
// ── העדפות מקומיות (סינון / מיון) ──
const DUTY_PK = 'v2_duty';
function dutyGetPref(k, d) {
  try { const p = JSON.parse(localStorage.getItem(DUTY_PK) || '{}'); return k in p ? p[k] : d; }
  catch (e) { return d; }
}
function dutySetPref(k, v) {
  try {
    const p = JSON.parse(localStorage.getItem(DUTY_PK) || '{}');
    p[k] = v;
    localStorage.setItem(DUTY_PK, JSON.stringify(p));
  } catch (e) {}
}

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
function dutyLocalIso(d){ const p2=v=>String(v).padStart(2,'0'); return `${d.getFullYear()}-${p2(d.getMonth()+1)}-${p2(d.getDate())}`; }
function getSundayOf(date){ const d=new Date(date); d.setDate(d.getDate()-d.getDay()); return dutyLocalIso(d); }

// ── Tabs ──────────────────────────────────────────────────────────
function dutySetTab(tab) {
  document.querySelectorAll('.duty-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
  document.querySelectorAll('.duty-tab-panel').forEach(p => p.style.display = 'none');
  document.getElementById('duty-tab-' + tab).style.display = '';
  if (tab === 'schedule') dutyLoadSchedule();
  if (tab === 'reps')     dutyLoadReps();
  if (tab === 'guidance') dutyLoadGuidance();
  if (tab === 'roles')    dutyLoadRoles();
}

// ── Daily roles (גלאס / שיחות / שניהם) ────────────────────────────
const ROLE_INFO = {
  glassix: { label:'גלאס',         icon:'bi-chat-dots-fill', cls:'on-glassix' },
  calls:   { label:'שיחות',        icon:'bi-telephone-fill', cls:'on-calls'   },
  both:    { label:'גלאס + שיחות', icon:'bi-layers-fill',    cls:'on-both'    },
};
const DAY_SHORT = ['א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ש'];

let _rolesWeek  = null;   // יום א' של השבוע המוצג
let _rolesState = {};     // repId => { 'YYYY-MM-DD': role }  — כפי שנשמר בשרת
let _rolesDraft = {};     // 'repId|date' => role             — שינויים שטרם נשמרו
let _rolesReps  = [];
let _rolesDays  = [];

function dutyRolesDirtyCount() { return Object.keys(_rolesDraft).length; }

// אזהרה בעזיבת העמוד עם שינויים שלא נשמרו
window.addEventListener('beforeunload', e => {
  if (dutyRolesDirtyCount()) { e.preventDefault(); e.returnValue = ''; }
});

/** הערך האפקטיבי של תא: טיוטה אם יש, אחרת מה שנשמר */
function dutyRoleAt(repId, date) {
  const k = repId + '|' + date;
  if (k in _rolesDraft) return _rolesDraft[k];
  return (_rolesState[repId] || {})[date] || '';
}

function dutyRolesGuard() {
  if (!dutyRolesDirtyCount()) return true;
  return confirm('יש שינויים שלא נשמרו. לעבור לשבוע אחר ולבטל אותם?');
}

function dutyRolesShiftWeek(days) {
  if (!dutyRolesGuard()) return;
  _rolesDraft = {};
  const d = new Date(_rolesWeek || getSundayOf(new Date()));
  d.setDate(d.getDate() + days);
  _rolesWeek = getSundayOf(d);
  dutyLoadRoles();
}
function dutyRolesGoToday() {
  if (!dutyRolesGuard()) return;
  _rolesDraft = {};
  _rolesWeek = getSundayOf(new Date());
  dutyLoadRoles();
}

function dutyAddDays(iso, n) {
  const [y, m, d] = iso.split('-').map(Number);
  const dt = new Date(y, m - 1, d + n);
  const p2 = v => String(v).padStart(2, '0');
  return `${dt.getFullYear()}-${p2(dt.getMonth() + 1)}-${p2(dt.getDate())}`;
}

/**
 * תא בטבלה — select. ערך ריק = ברירת המחדל (גלאס + שיחות),
 * ומוצג מקווקו כדי שיהיה ברור שלא נקבע במפורש.
 */
function dutyRoleSelectHtml(repId, date) {
  const role = dutyRoleAt(repId, date);
  // 'both' זהה בפועל לברירת המחדל, אז הוא לא אפשרות נפרדת ברשימה.
  // רשומות ישנות עם 'both' מוצגות כברירת מחדל.
  const isDefault = !role || role === 'both';
  const isDirty   = (repId + '|' + date) in _rolesDraft;
  const info      = ROLE_INFO[isDefault ? 'both' : role];
  const sel = v => (isDefault ? '' : role) === v ? ' selected' : '';
  const opts =
    `<option value=""${isDefault ? ' selected' : ''}>גלאס + שיחות</option>` +
    `<option value="glassix"${sel('glassix')}>גלאס</option>` +
    `<option value="calls"${sel('calls')}>שיחות</option>`;
  return `<div class="role-sel-wrap ${info.cls}${isDefault ? ' is-default' : ''}${isDirty ? ' is-dirty' : ''}">
    <i class="bi ${info.icon} role-sel-ico"></i>
    <select class="role-sel"${DUTY_CAN_EDIT ? '' : ' disabled'}
            onchange="dutyPickRole(${repId},'${date}',this.value)">${opts}</select>
  </div>`;
}

/** ספירת תפקידים לנציג לאורך השבוע המוצג */
/** ספירה לנציג: רק גלאס ושיחות — ברירת המחדל לא נספרת */
function dutyRepCountsOf(repId, days) {
  const c = { glassix:0, calls:0 };
  days.forEach(d => {
    const r = dutyRoleAt(repId, d);
    if (r === 'glassix' || r === 'calls') c[r]++;
  });
  return c;
}

function dutyRepCounts(repId, days) {
  const c = dutyRepCountsOf(repId, days);
  const parts = [];
  if (c.glassix) parts.push(`<span class="cnt cnt-glassix">${c.glassix} גלאס</span>`);
  if (c.calls)   parts.push(`<span class="cnt cnt-calls">${c.calls} שיחות</span>`);
  return parts.join('');
}

function dutyRefreshCounts() {
  dutyVisibleReps().forEach(rep => {
    const box = document.getElementById('cnt-' + rep.id);
    if (box) box.innerHTML = dutyRepCounts(rep.id, _rolesDays);
  });
}

function dutyRolesUpdateDirtyUi() {
  const n     = dutyRolesDirtyCount();
  const txt   = n === 1 ? 'שינוי אחד לא שמור' : `${n} שינויים לא שמורים`;

  const badge = document.getElementById('duty-roles-dirty');
  if (badge) {
    badge.style.display = n ? '' : 'none';
    const c = document.getElementById('duty-dirty-count');
    if (c) c.textContent = txt;
  }

  // סרגל השמירה הצף — מופיע רק כשיש מה לשמור.
  // הוא markup סטטי שלא מרונדר מחדש, אז חובה לאפס כאן את disabled
  // שנקבע בזמן השמירה — אחרת שמירה שנייה לא תגיב עד רענון.
  const bar = document.getElementById('duty-savebar');
  if (bar) {
    bar.classList.toggle('show', n > 0);
    const c = document.getElementById('savebar-count');
    if (c) c.textContent = txt;
  }
  const saveBtn = document.getElementById('duty-roles-save');
  const discBtn = document.getElementById('duty-roles-discard');
  if (saveBtn) saveBtn.disabled = false;
  if (discBtn) discBtn.disabled = false;
  const float = document.getElementById('duty-float');
  if (float) float.classList.toggle('above-savebar', n > 0);
}

function dutyPickRole(repId, date, value) {
  const k = repId + '|' + date;
  // 'both' שמור מוצג כברירת מחדל (''), אז שניהם שקולים בהשוואה
  const rawSaved = (_rolesState[repId] || {})[date] || '';
  const saved    = rawSaved === 'both' ? '' : rawSaved;
  if (value === saved) delete _rolesDraft[k];   // חזר לערך השמור — אין שינוי
  else _rolesDraft[k] = value;

  const cell = document.getElementById('cell-' + repId + '-' + date);
  if (cell) cell.innerHTML = dutyRoleSelectHtml(repId, date);
  dutyRefreshCounts();
  dutyRolesUpdateDirtyUi();
}

function dutyRolesDiscard() {
  if (!dutyRolesDirtyCount()) return;
  _rolesDraft = {};
  dutyLoadRoles();
  dutyFloat('ok', 'השינויים בוטלו');
}

async function dutyRolesSave() {
  const entries = Object.entries(_rolesDraft);
  if (!entries.length) return;
  const btn = document.getElementById('duty-roles-save');
  if (btn) btn.disabled = true;
  dutyFloat('saving', `שומר ${entries.length} שינויים...`);

  try {
    const stillDirty = {};   // שינויים שנכשלו — נשארים בטיוטה כדי שלא יאבדו
    for (const [k, role] of entries) {
      const [repId, date] = k.split('|');
      try {
        const res = await fetch(DUTY_BASE + '/api/duty/daily-roles', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': DUTY_CSRF, 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ representative_id: repId, duty_date: date, role })
        }).then(r => r.json());
        if (res.error) throw new Error(res.error);
      } catch (e) { stillDirty[k] = role; }
    }

    const failed = Object.keys(stillDirty).length;
    const saved  = entries.length - failed;
    _rolesDraft  = stillDirty;
    await dutyLoadRoles();      // מרענן גם את מצב הכפתורים דרך dutyRolesUpdateDirtyUi

    if (failed) dutyFloat('err', `${failed} מתוך ${entries.length} שינויים נכשלו — נשארו לשמירה חוזרת`);
    else        dutyFloat('ok', saved === 1 ? 'השינוי נשמר' : `${saved} שינויים נשמרו`);
  } finally {
    // גם אם הטעינה מחדש נכשלה — הכפתור חייב לחזור לפעולה
    if (btn) btn.disabled = false;
  }
}

/** מרנדר את הטבלה מהנתונים שכבר נטענו (בלי פנייה לשרת) */
// ── מיון הטבלה לפי ספירת גלאס / שיחות ─────────────────────────────
let _rolesSort = dutyGetPref('sort', { key: null, dir: 'desc' });   // key: 'glassix' | 'calls' | null

function dutySortRoles(key) {
  if (_rolesSort.key === key) {
    // אותו עמוד: יורד → עולה → ללא מיון
    if (_rolesSort.dir === 'desc')      _rolesSort.dir = 'asc';
    else                                _rolesSort = { key: null, dir: 'desc' };
  } else {
    _rolesSort = { key, dir: 'desc' };
  }
  dutySetPref('sort', _rolesSort);
  dutyRenderRoles();
}

function dutySortIcon(key) {
  if (_rolesSort.key !== key) return 'bi-arrow-down-up';
  return _rolesSort.dir === 'desc' ? 'bi-sort-numeric-down-alt' : 'bi-sort-numeric-up';
}

/** מחזיר עותק ממוין של הנציגים המוצגים */
function dutySortedReps(reps, days) {
  if (!_rolesSort.key) return reps;
  const k   = _rolesSort.key;
  const mul = _rolesSort.dir === 'asc' ? 1 : -1;
  // עותק — לא ממיינים את המערך המקורי במקום
  return [...reps].sort((a, b) => {
    const diff = dutyRepCountsOf(a.id, days)[k] - dutyRepCountsOf(b.id, days)[k];
    return diff !== 0 ? diff * mul : a.name.localeCompare(b.name, 'he');
  });
}

function dutyRenderRoles() {
  const el   = document.getElementById('duty-roles-content');
  const days = _rolesDays;
  if (!el || !days.length) return;

  if (!_rolesReps.length) {
    el.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text3);">
      <i class="bi bi-people" style="font-size:32px;display:block;margin-bottom:10px;opacity:.35;"></i>
      אין נציגים פעילים — הוסף נציגים בלשונית "נציגים"
    </div>`;
    dutyRolesUpdateDirtyUi();
    return;
  }

  const visible = dutyVisibleReps();
  if (!visible.length) {
    el.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text3);">
      <i class="bi bi-funnel" style="font-size:32px;display:block;margin-bottom:10px;opacity:.35;"></i>
      אין נציגים במחלקות שנבחרו
    </div>`;
    dutyRolesUpdateDirtyUi();
    return;
  }

  const reps  = dutySortedReps(visible, days);
  const today = dutyLocalIso(new Date());
  el.innerHTML = `<div class="card" style="padding:0;overflow-x:auto;">
    <table class="roles-table" style="width:100%;border-collapse:collapse;font-size:14px;min-width:800px;table-layout:fixed;">
      <thead><tr style="background:var(--bg3);font-size:12px;font-weight:700;color:var(--text3);">
        <th style="width:215px;">
          <div class="rep-th">
            <span>נציג</span>
            <span class="sort-btns">
              <button type="button" class="sort-btn sort-glassix ${_rolesSort.key === 'glassix' ? 'on' : ''}"
                      onclick="dutySortRoles('glassix')" title="מיון לפי מספר ימי גלאס">
                <i class="bi bi-chat-dots-fill"></i><i class="bi ${dutySortIcon('glassix')}"></i>
              </button>
              <button type="button" class="sort-btn sort-calls ${_rolesSort.key === 'calls' ? 'on' : ''}"
                      onclick="dutySortRoles('calls')" title="מיון לפי מספר ימי שיחות">
                <i class="bi bi-telephone-fill"></i><i class="bi ${dutySortIcon('calls')}"></i>
              </button>
            </span>
          </div>
        </th>
        ${days.map((d, i) => `<th class="${d === today ? 'role-today' : ''}" style="text-align:center;white-space:nowrap;">
          ${DAY_SHORT[i]}&#1523; <span class="day-num">${d.slice(8)}/${d.slice(5,7)}</span>
        </th>`).join('')}
      </tr></thead>
      <tbody>${reps.map(rep => {
        const dc = DEPT_INFO[rep.department] || { cls:'', icon:'' };
        return `<tr>
          <td class="rep-cell">
            <div class="rep-counts" id="cnt-${rep.id}">${dutyRepCounts(rep.id, days)}</div>
            <div class="rep-name">${dutyEsc(rep.name)}</div>
            <div class="dept-vert ${dc.cls}" title="${dutyEsc(rep.department)}">${dutyEsc(rep.department)}</div>
          </td>
          ${days.map(d => `<td class="role-cell ${d === today ? 'role-today' : ''}" id="cell-${rep.id}-${d}">
              ${dutyRoleSelectHtml(rep.id, d)}
            </td>`).join('')}
        </tr>`;
      }).join('')}</tbody>
    </table>
  </div>`;
  dutyRolesUpdateDirtyUi();
}

async function dutyLoadRoles() {
  const el = document.getElementById('duty-roles-content');
  if (!_rolesWeek) _rolesWeek = getSundayOf(new Date());
  try {
    const data = await fetch(DUTY_BASE + '/api/duty/daily-roles?week=' + _rolesWeek).then(r => r.json());
    _rolesWeek  = data.week_start;
    _rolesState = data.roles || {};
    _rolesReps  = data.reps  || [];

    const days = [];
    for (let i = 0; i < 6; i++) days.push(dutyAddDays(_rolesWeek, i));
    _rolesDays = days;

    document.getElementById('duty-roles-weeklabel').textContent =
      formatDate(days[0]) + ' – ' + formatDate(days[5]);

    dutyBuildDeptMenu(_rolesReps);
    dutyRenderRoles();
  } catch (e) {
    el.innerHTML = '<div style="text-align:center;padding:40px;color:var(--danger);">שגיאה בטעינת התורנות היומית</div>';
  }
}

// ── מסנן מחלקות (כפתורי toggle + שמירה ב-localStorage) ────────────
// null = הכול מוצג; אחרת Set של המחלקות המוסתרות
let _deptHidden = new Set(dutyGetPref('deptHidden', []));

function dutyPersistDeptFilter() {
  dutySetPref('deptHidden', [..._deptHidden]);
}

/** בונה את כפתורי המחלקות לפי מה שקיים בפועל */
function dutyBuildDeptMenu(reps) {
  const box = document.getElementById('dept-filter');
  if (!box) return;
  const depts = [...new Set(reps.map(r => r.department).filter(Boolean))].sort();

  // אין מחלקות — אין מה לסנן, מסתירים את כל הסרגל
  const bar = box.closest('.filter-bar');
  if (bar) bar.style.display = depts.length ? '' : 'none';
  if (!depts.length) { box.innerHTML = ''; return; }

  // מחלקה שנעלמה מהרשימה לא צריכה להישאר מוסתרת
  const before = _deptHidden.size;
  _deptHidden = new Set([..._deptHidden].filter(d => depts.includes(d)));
  if (_deptHidden.size !== before) dutyPersistDeptFilter();

  const anyHidden = _deptHidden.size > 0;
  box.innerHTML =
    depts.map(d => {
      const off = _deptHidden.has(d);
      const dc  = DEPT_INFO[d] || { cls:'', icon:'' };
      return `<button type="button" class="dept-btn ${dc.cls}${off ? ' off' : ''}"
                      onclick="dutyToggleDept('${dutyEsc(d).replace(/'/g, "\\'")}')"
                      title="${off ? 'מוסתר — לחץ להצגה' : 'מוצג — לחץ להסתרה'}">
        <i class="bi ${off ? 'bi-x-lg dept-btn-x' : 'bi-check-lg'}"></i>
        <span>${dutyEsc(d)}</span>
      </button>`;
    }).join('') +
    (anyHidden
      ? `<button type="button" class="dept-btn dept-btn-reset" onclick="dutyResetDepts()" title="הצג את כל המחלקות">
           <i class="bi bi-arrow-counterclockwise"></i><span>הצג הכל</span>
         </button>`
      : '');

  // חיווי מצב בקצה הסרגל
  const hint = document.getElementById('dept-filter-hint');
  if (hint) {
    const shown = depts.length - _deptHidden.size;
    hint.textContent = anyHidden
      ? `מוצגות ${shown} מתוך ${depts.length} מחלקות`
      : 'כל המחלקות מוצגות';
  }
}

function dutyToggleDept(dept) {
  if (_deptHidden.has(dept)) _deptHidden.delete(dept);
  else                       _deptHidden.add(dept);
  dutyPersistDeptFilter();
  dutyBuildDeptMenu(_rolesReps);
  dutyRenderRoles();
}

function dutyResetDepts() {
  _deptHidden.clear();
  dutyPersistDeptFilter();
  dutyBuildDeptMenu(_rolesReps);
  dutyRenderRoles();
}

/** הנציגים שמוצגים אחרי סינון */
function dutyVisibleReps() {
  if (!_deptHidden.size) return _rolesReps;
  return _rolesReps.filter(r => !_deptHidden.has(r.department));
}

/** הודעת float קטנה לשמירה בטבלה היומית */
let _dutyFloatTimer = null;
function dutyFloat(state, msg) {
  const el = document.getElementById('duty-float');
  if (!el) return;
  const ICON = {
    saving: '<i class="bi bi-arrow-repeat duty-float-spin"></i>',
    ok:     '<i class="bi bi-check-circle-fill"></i>',
    err:    '<i class="bi bi-exclamation-triangle-fill"></i>',
  };
  el.className = 'show is-' + state;
  el.innerHTML = ICON[state] + '<span id="duty-float-txt"></span>';
  el.querySelector('#duty-float-txt').textContent = msg;

  clearTimeout(_dutyFloatTimer);
  if (state !== 'saving') {                       // "שומר..." נשאר עד שמסתיים
    _dutyFloatTimer = setTimeout(() => el.classList.remove('show'), state === 'err' ? 3200 : 1600);
  }
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
