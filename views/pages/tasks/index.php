<?php
use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
$filter = $filter ?? '';
$isOverdueFilter = $filter === 'overdue';
// JSON-encode statusesByType for JS
$statusesJson  = json_encode($statusesByType ?? [], JSON_UNESCAPED_UNICODE);
$usersJson     = json_encode($users ?? [], JSON_UNESCAPED_UNICODE);
$allTypesJson  = json_encode($allTypes ?? [], JSON_UNESCAPED_UNICODE);
$recentClosed  = $recentClosed ?? [];
$users        = $users ?? [];
$showClosed   = $showClosed ?? false;
$canViewAll  = $canViewAll ?? false;
$scopeAll   = $scopeAll ?? false;

?>
<style>
.task-status-badge{
  display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;
  font-size:12px;font-weight:700;cursor:pointer;border:1px solid transparent;
  transition:filter .15s,transform .12s;user-select:none;
}
.task-status-badge:hover{filter:brightness(1.2);transform:scale(1.04);}
.task-status-cell{
  display:flex;align-items:center;justify-content:center;gap:6px;
  width:100%;height:100%;min-height:44px;padding:10px 12px;border-radius:0;
  font-size:13px;font-weight:700;cursor:pointer;border:none;
  transition:filter .15s;user-select:none;
}
.task-status-cell:hover{filter:brightness(1.12);}
.task-status-cell .dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
@keyframes badge-flip {
  0%   { transform: scaleY(1);   opacity:1; }
  40%  { transform: scaleY(0);   opacity:0; }
  100% { transform: scaleY(1);   opacity:1; }
}
.badge-flip { animation: badge-flip 0.22s ease; }
.status-dropdown{
  position:absolute;z-index:50;background:var(--bg2);border:1px solid var(--border2);
  border-radius:var(--radius);box-shadow:var(--shadow);min-width:130px;overflow:hidden;
}
.status-option{
  display:flex;align-items:center;gap:8px;padding:9px 14px;cursor:pointer;
  font-size:13px;font-weight:600;transition:background .12s;
}
.status-option:hover{background:var(--bg3);}
.task-title-cell{position:relative;}
.task-row{transition:background .13s;}
.task-row:hover{background:var(--bg3);}
.task-row-title{font-size:15px;font-weight:600;color:var(--text);line-height:1.4;cursor:pointer;display:inline-block;border-radius:5px;padding:2px 5px;margin:-2px -5px;transition:background .13s;}
.task-row-title:hover{background:var(--bg3);}
.task-row-desc{font-size:12.5px;color:var(--text3);margin-top:3px;line-height:1.4;}
.task-row-meta{font-size:12.5px;color:var(--text2);}
.task-title-input{
  background:var(--bg3);border:1px solid var(--accent);border-radius:6px;
  color:var(--text);font-size:14px;font-weight:500;font-family:inherit;
  padding:3px 8px;outline:none;width:100%;
}

/* ── Task detail modal ── */
.td-title-editable{
  font-size:18px;font-weight:700;color:var(--text);cursor:pointer;
  border-radius:6px;padding:2px 6px;margin:-2px -6px;display:inline-block;
  transition:background .13s;
}
.td-title-editable:hover{background:var(--bg3);}
.td-close-btn{
  background:none;border:none;color:var(--text2);font-size:18px;cursor:pointer;
  flex-shrink:0;width:32px;height:32px;border-radius:8px;display:grid;place-items:center;
  transition:background .13s,color .13s;
}
.td-close-btn:hover{background:var(--bg3);color:var(--text);}

.td-messages{display:flex;flex-direction:column;gap:10px;}
.td-msg{
  background:var(--bg3);border:1px solid var(--border);border-radius:12px;
  padding:10px 13px;position:relative;
}
.td-msg-head{
  display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text3);margin-bottom:5px;font-weight:600;
}
.td-msg-avatar{
  width:20px;height:20px;border-radius:50%;background:var(--accent-dim);color:var(--accent);
  display:grid;place-items:center;font-size:10px;font-weight:800;flex-shrink:0;
}
.td-msg-body{font-size:13px;color:var(--text);white-space:pre-wrap;line-height:1.5;}
.td-messages-empty{color:var(--text3);font-size:13px;text-align:center;padding:22px 10px;}

.td-composer-input{
  flex:1;background:var(--bg3);border:1px solid var(--border);
  border-radius:12px;color:var(--text);font-size:13px;
  font-family:inherit;padding:10px 13px;outline:none;
  resize:none;box-sizing:border-box;line-height:1.4;max-height:120px;
  transition:border-color .15s;
}
.td-composer-input:focus{border-color:var(--accent);}
.td-send-btn{
  background:var(--accent);color:#fff;border:none;border-radius:12px;
  width:40px;height:40px;flex-shrink:0;display:grid;place-items:center;
  font-size:15px;cursor:pointer;transition:filter .13s,transform .1s;
}
.td-send-btn:hover{filter:brightness(1.1);}
.td-send-btn:active{transform:scale(.94);}

.td-logs{flex:1;overflow-y:auto;padding:0 14px 14px;display:flex;flex-direction:column;gap:10px;}
.td-log-entry{
  position:relative;padding-right:16px;font-size:12px;
}
.td-log-entry::before{
  content:'';position:absolute;right:0;top:5px;width:6px;height:6px;border-radius:50%;
  background:var(--text3);
}
.td-log-entry::after{
  content:'';position:absolute;right:2.5px;top:13px;bottom:-10px;width:1px;background:var(--border);
}
.td-log-entry:last-child::after{display:none;}
.td-log-detail{color:var(--text2);font-weight:600;line-height:1.4;}
.td-log-meta{color:var(--text3);font-size:11px;margin-top:2px;}
.td-logs-empty{color:var(--text3);font-size:12px;text-align:center;padding:18px 8px;}

@media (max-width:720px){
  .td-modal-box{max-width:100%!important;}
  .td-modal-box > div:nth-child(2){flex-direction:column!important;}
  .td-modal-box > div:nth-child(2) > div:first-child{border-left:none!important;border-bottom:1px solid var(--border);}
  .td-modal-box > div:nth-child(2) > div:last-child{width:100%!important;max-height:180px;}
}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
  <div class="page-title" style="margin-bottom:0;">משימות</div>
  <button class="btn btn-primary" onclick="openNewTaskModal()">
    + משימה חדשה
  </button>
</div>

<!-- Filters bar -->
<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px;">

  <div style="position:relative;flex:1;min-width:180px;max-width:320px;">
    <i class="bi bi-search" style="position:absolute;top:50%;right:12px;transform:translateY(-50%);color:var(--text3);font-size:13px;pointer-events:none;"></i>
    <input type="text" id="task-search-input" placeholder="חיפוש משימה..." autocomplete="off"
           oninput="filterTaskRows(this.value)"
           style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);
                  padding:8px 34px 8px 12px;color:var(--text);font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;">
  </div>

  <?php if ($isOverdueFilter): ?>
  <div style="display:flex;align-items:center;gap:8px;background:rgba(239,68,68,.1);
              border:1px solid rgba(239,68,68,.3);border-radius:var(--radius);
              padding:7px 14px;color:var(--danger);font-size:13px;font-weight:600;">
    <i class="bi bi-exclamation-triangle-fill"></i>
    חריגות SLA בלבד —
    <a href="<?= $base ?>/tasks" style="color:var(--accent);text-decoration:none;">הצג הכל</a>
  </div>
  <?php endif; ?>

  <?php if ($canViewAll): ?>
  <!-- Mine/All toggle -->
  <div style="display:inline-flex;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;font-size:13px;font-weight:600;">
    <a href="?scope=mine"
       style="padding:6px 14px;text-decoration:none;<?= !$scopeAll ? 'background:var(--accent);color:#fff;' : 'color:var(--text2);' ?>">
      שלי
    </a>
    <a href="?scope=all"
       style="padding:6px 14px;text-decoration:none;<?= $scopeAll ? 'background:var(--accent);color:#fff;' : 'color:var(--text2);' ?>">
      הכל
    </a>
  </div>
  <?php endif; ?>

</div>

<?php if (empty($tasks)): ?>
  <div class="alert alert-info">אין משימות פתוחות 🎉</div>
<?php else: ?>
<div class="card" style="padding:0;overflow:visible;">
  <table style="width:100%;border-collapse:collapse;font-size:14px;table-layout:fixed;">
    <colgroup>
      <col style="width:52px;">
      <col>
      <col style="width:110px;">
      <col style="width:110px;">
      <col style="width:100px;">
      <col style="width:140px;">
      <col style="width:130px;">
      <col style="width:130px;">
    </colgroup>
    <thead>
      <tr style="color:var(--text2);">
        <th style="text-align:right;padding:12px 14px;border-bottom:1px solid var(--border);font-weight:500;">#</th>
        <th style="text-align:right;padding:12px 14px;border-bottom:1px solid var(--border);font-weight:500;">כותרת</th>
        <th style="text-align:right;padding:12px 14px;border-bottom:1px solid var(--border);font-weight:500;">סוג</th>
        <th style="text-align:right;padding:12px 14px;border-bottom:1px solid var(--border);font-weight:500;">SLA</th>
        <th style="text-align:right;padding:12px 14px;border-bottom:1px solid var(--border);font-weight:500;">נפתח</th>
        <th style="text-align:right;padding:12px 14px;border-bottom:1px solid var(--border);font-weight:500;">עודכן ע"י</th>
        <th style="text-align:right;padding:12px 14px;border-bottom:1px solid var(--border);font-weight:500;">מחלקה</th>
        <th style="text-align:center;padding:12px 14px;border-bottom:1px solid var(--border);font-weight:500;">סטטוס</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($tasks as $t):
      $created = $t['created_at'] ? date('d/m/Y', strtotime($t['created_at'])) : '—';
      $slaTs   = $t['created_at'] && $t['sla_days']
                 ? strtotime($t['created_at'] . ' +' . (int)$t['sla_days'] . ' days')
                 : 0;
      $slaDate = $slaTs ? date('d/m/Y', $slaTs) : '—';
      $overdue = $slaTs && $slaTs < time();
      $statusColor = $t['status_color'] ?? '#6b7280';
      $statusName  = $t['status_name']  ?? '—';
      $typeId      = (int)($t['task_type_id'] ?? 0);
      $statusId    = (int)($t['status_id']    ?? 0);
    ?>
    <tr class="task-row" style="border-bottom:1px solid var(--border);cursor:pointer;<?= $overdue ? 'border-right:3px solid var(--danger);background:rgba(239,68,68,.05);' : '' ?>"
        id="task-row-<?= (int)$t['id'] ?>"
        data-search="<?= View::e(mb_strtolower(($t['title'] ?? '') . ' ' . ($t['description'] ?? '') . ' ' . ($t['type_name'] ?? '') . ' ' . ($statusName) . ' ' . ($t['dept_name'] ?? ''))) ?>"
        onclick="openTaskDetail(<?= (int)$t['id'] ?>)">
      <td style="padding:14px;color:var(--text3);font-size:13px;"><?= (int)$t['id'] ?></td>

      <!-- Title: double-click to edit -->
      <td style="padding:14px;" class="task-title-cell">
        <div class="task-row-title"
             id="title-text-<?= (int)$t['id'] ?>"
             title="לחץ פעמיים לעריכה"
             ondblclick="startTitleEdit(<?= (int)$t['id'] ?>, this)">
          <?= View::e($t['title'] ?? '') ?>
        </div>
        <?php if (!empty($t['description'])): ?>
          <div class="task-row-desc">
            <?= View::e(mb_substr($t['description'], 0, 90)) ?><?= mb_strlen($t['description']) > 90 ? '…' : '' ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($t['source_type']) && $t['source_type'] === 'invoice_change_name'): ?>
          <a href="<?= $base ?>/invoice-change-name"
             onclick="event.stopPropagation()"
             style="font-size:11.5px;color:var(--accent);text-decoration:none;margin-top:4px;display:inline-flex;align-items:center;gap:3px;">
            <i class="bi bi-box-arrow-up-left"></i> צפה בבקשה
          </a>
        <?php endif; ?>
      </td>

      <td class="task-row-meta" style="padding:14px;">
        <?= View::e($t['type_name'] ?? '—') ?>
      </td>

      <td style="padding:14px;">
        <?php if ($slaTs): ?>
          <span class="badge <?= $overdue ? 'badge-danger' : 'badge-success' ?>"><?= $slaDate ?></span>
        <?php else: ?><span class="task-row-meta">—</span><?php endif; ?>
      </td>

      <td class="task-row-meta" style="padding:14px;"><?= $created ?></td>

      <td class="task-row-meta" style="padding:14px;font-size:12px;">
        <?= \Core\View::e($t['changed_by_name'] ?? '—') ?>
        <?php if ($t['status_changed_at']): ?>
          <div style="color:var(--text3);font-size:11px;margin-top:1px;"><?= date('d/m/Y H:i', strtotime($t['status_changed_at'])) ?></div>
        <?php endif; ?>
      </td>
      <td class="task-row-meta" style="padding:14px;">
        <?= \Core\View::e($t['dept_name'] ?? '—') ?>
      </td>

      <!-- Status: fills the entire cell, click to open dropdown -->
      <td style="padding:0;position:relative;" onclick="event.stopPropagation()">
        <?php if ($typeId): ?>
          <div class="task-status-cell"
               data-type-id="<?= $typeId ?>"
               data-current-status="<?= $statusId ?>"
               style="color:<?= View::e($statusColor) ?>;background:<?= View::e($statusColor) ?>1a;"
               onclick="toggleStatusDropdown(event, <?= (int)$t['id'] ?>, parseInt(this.dataset.typeId), parseInt(this.dataset.currentStatus))">
            <span class="dot" style="background:<?= View::e($statusColor) ?>;"></span>
            <span id="status-label-<?= (int)$t['id'] ?>"><?= $statusId ? View::e($statusName) : '— בחר —' ?></span>
          </div>
        <?php else: ?>
          <div class="task-status-cell" style="color:var(--text3);cursor:default;">—</div>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php if (!$showClosed && !empty($recentClosed)): ?>
<div style="margin-top:24px;">
  <button onclick="toggleClosedSection()"
          style="display:flex;align-items:center;gap:8px;background:none;border:none;
                 color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;padding:0;margin-bottom:10px;">
    <i class="bi bi-chevron-left" id="closed-chevron" style="transition:transform .2s;font-size:11px;"></i>
    <?= count($recentClosed) ?> משימות סגורות אחרונות
  </button>
  <div id="closed-section" style="display:none;">
    <div class="card" style="padding:0;overflow:visible;opacity:.8;">
      <table style="width:100%;border-collapse:collapse;font-size:13px;table-layout:fixed;">
        <colgroup>
          <col style="width:48px;">
          <col>
          <col style="width:100px;">
          <col style="width:100px;">
          <col style="width:120px;">
          <col style="width:120px;">
        </colgroup>
        <thead>
          <tr style="color:var(--text3);">
            <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">#</th>
            <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">כותרת</th>
            <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">סוג</th>
            <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">נסגר</th>
            <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">סגר</th>
            <th style="text-align:center;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">סטטוס</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recentClosed as $c):
          $sColor = $c['status_color'] ?? '#6b7280';
          $sName  = $c['status_name']  ?? '—';
          $cTypeId   = (int)($c['task_type_id'] ?? 0);
          $cStatusId = (int)($c['status_id']    ?? 0);
        ?>
          <tr class="task-row" style="border-bottom:1px solid var(--border);cursor:pointer;"
              id="task-row-<?= (int)$c['id'] ?>"
              data-search="<?= View::e(mb_strtolower(($c['title'] ?? '') . ' ' . ($c['type_name'] ?? '') . ' ' . ($sName) . ' ' . ($c['changed_by_name'] ?? ''))) ?>"
              onclick="openTaskDetail(<?= (int)$c['id'] ?>)">
            <td style="padding:12px 14px;color:var(--text3);"><?= (int)$c['id'] ?></td>
            <td style="padding:12px 14px;" class="task-title-cell">
              <div class="task-row-title" style="font-size:14px;"
                   id="title-text-<?= (int)$c['id'] ?>"
                   title="לחץ פעמיים לעריכה"
                   ondblclick="startTitleEdit(<?= (int)$c['id'] ?>, this)">
                <?= View::e($c['title'] ?? '') ?>
              </div>
            </td>
            <td class="task-row-meta" style="padding:12px 14px;"><?= View::e($c['type_name'] ?? '—') ?></td>
            <td class="task-row-meta" style="padding:12px 14px;font-size:12px;">
              <?= $c['status_changed_at'] ? date('d/m/Y', strtotime($c['status_changed_at'])) : '—' ?>
            </td>
            <td class="task-row-meta" style="padding:12px 14px;">
              <?= View::e($c['changed_by_name'] ?? '—') ?>
            </td>
            <td style="padding:0;position:relative;" onclick="event.stopPropagation()">
              <?php if ($cTypeId): ?>
              <div class="task-status-cell"
                   data-type-id="<?= $cTypeId ?>"
                   data-current-status="<?= $cStatusId ?>"
                   style="color:<?= View::e($sColor) ?>;background:<?= View::e($sColor) ?>1a;min-height:40px;font-size:12.5px;"
                   onclick="toggleStatusDropdown(event, <?= (int)$c['id'] ?>, parseInt(this.dataset.typeId), parseInt(this.dataset.currentStatus))">
                <span class="dot" style="background:<?= View::e($sColor) ?>;"></span>
                <span id="status-label-<?= (int)$c['id'] ?>"><?= View::e($sName) ?></span>
              </div>
              <?php else: ?>
              <div class="task-status-cell" style="color:var(--text3);cursor:default;min-height:40px;">—</div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Status dropdown (shared, positioned absolutely) -->
<div id="status-dd" class="status-dropdown" style="display:none;"></div>

<!-- New task modal -->
<div id="new-task-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;align-items:center;justify-content:center;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:28px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;">
    <button onclick="document.getElementById('new-task-modal').style.display='none'"
            style="float:left;background:none;border:none;color:var(--text2);font-size:20px;cursor:pointer;">✕</button>
    <div style="font-size:17px;font-weight:600;margin-bottom:20px;">משימה חדשה</div>
    <form method="POST" action="<?= $base ?>/tasks/create">
      <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
      <input type="hidden" name="for_user" id="new-task-for-user" value="<?= (int)($_SESSION['user_id'] ?? 0) ?>">

      <!-- Assign to user -->
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">שייך למשתמש</label>
        <div style="position:relative;">
          <input type="text" id="user-search-input" autocomplete="off" placeholder="חיפוש משתמש..."
                 style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;
                        padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;box-sizing:border-box;"
                 oninput="filterUsers(this.value)" onfocus="showUserDD()" onblur="hideUserDD()">
          <div id="user-dd"
               style="display:none;position:absolute;top:100%;right:0;left:0;z-index:300;
                      background:var(--bg2);border:1px solid var(--border2);border-radius:8px;
                      box-shadow:var(--shadow);max-height:200px;overflow-y:auto;margin-top:2px;">
          </div>
        </div>
        <div id="user-selected-label" style="font-size:12px;color:var(--accent);margin-top:5px;"></div>
      </div>

      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">כותרת *</label>
        <input type="text" name="title" required
               style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;box-sizing:border-box;">
      </div>
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">תיאור</label>
        <textarea name="description" rows="3"
                  style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;resize:vertical;box-sizing:border-box;"></textarea>
      </div>
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">סוג משימה</label>
        <select name="task_type_id" id="new-task-type"
                style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;box-sizing:border-box;">
          <option value="">— ללא סוג —</option>
        </select>
      </div>
      <div style="margin-bottom:20px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">SLA (ימים)</label>
        <input type="number" name="sla_days" value="3" min="1" max="30"
               style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;box-sizing:border-box;">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">צור משימה</button>
    </form>
  </div>
</div>

<!-- Task Detail Modal -->
<div id="task-detail-modal"
     onclick="if(event.target===this) closeTaskDetail()"
     style="display:none;position:fixed;inset:0;background:rgba(10,12,20,.65);backdrop-filter:blur(2px);z-index:400;align-items:center;justify-content:center;padding:24px;">
  <div class="td-modal-box"
       style="background:var(--bg2);border:1px solid var(--border2);border-radius:16px;box-shadow:0 24px 60px -12px rgba(0,0,0,.5);
              width:100%;max-width:900px;height:min(88vh,720px);display:flex;flex-direction:column;overflow:hidden;">

    <!-- Header -->
    <div style="padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-shrink:0;">
      <div style="min-width:0;flex:1;">
        <div style="font-size:11px;color:var(--text3);margin-bottom:4px;letter-spacing:.02em;">משימה #<span id="td-id"></span></div>
        <div id="td-title" class="td-title-editable"
             title="לחץ פעמיים לעריכה" ondblclick="startDetailTitleEdit(this)"></div>
      </div>
      <button onclick="closeTaskDetail()" class="td-close-btn" title="סגור (Esc)">✕</button>
    </div>

    <!-- Two-column body -->
    <div style="flex:1;display:flex;min-height:0;">

      <!-- Main column (right, RTL-first): meta + description + messages -->
      <div style="flex:1;min-width:0;display:flex;flex-direction:column;border-left:1px solid var(--border);">
        <div style="flex:1;overflow-y:auto;padding:18px 24px;">
          <div id="td-loading" style="color:var(--text3);font-size:13px;text-align:center;padding:30px;">טוען...</div>

          <div id="td-content" style="display:none;">
            <!-- Meta grid -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:9px 18px;margin-bottom:16px;font-size:13px;">
              <div><span style="color:var(--text3);">סטטוס: </span>
                <span id="td-status-badge" class="task-status-badge" style="cursor:pointer;" data-task-id="">
                  <span id="td-status-dot" style="width:7px;height:7px;border-radius:50%;flex-shrink:0;"></span>
                  <span id="td-status-label"></span>
                </span>
              </div>
              <div><span style="color:var(--text3);">סוג: </span><span id="td-type"></span></div>
              <div><span style="color:var(--text3);">SLA: </span><span id="td-sla"></span></div>
              <div><span style="color:var(--text3);">נפתח: </span><span id="td-created"></span></div>
              <div><span style="color:var(--text3);">נפתח ע"י: </span><span id="td-opener"></span></div>
              <div><span style="color:var(--text3);">משויך ל: </span><span id="td-assignee"></span></div>
              <div><span style="color:var(--text3);">מחלקה: </span><span id="td-dept"></span></div>
              <div><span style="color:var(--text3);">עדכון אחרון: </span><span id="td-changed"></span></div>
            </div>

            <div id="td-desc-wrap" style="margin-bottom:20px;display:none;">
              <div style="color:var(--text3);font-size:12px;margin-bottom:5px;font-weight:600;">תיאור</div>
              <div id="td-desc" style="font-size:13px;color:var(--text2);white-space:pre-wrap;background:var(--bg3);border-radius:10px;padding:11px 13px;line-height:1.55;"></div>
            </div>

            <!-- Messages -->
            <div style="color:var(--text3);font-size:12px;margin-bottom:8px;font-weight:600;display:flex;align-items:center;gap:6px;">
              <i class="bi bi-chat-left-text"></i> התכתבות פנימית
            </div>
            <div id="td-messages" class="td-messages"></div>
          </div>
        </div>

        <!-- Comment composer (main column only) -->
        <div style="padding:14px 24px;border-top:1px solid var(--border);flex-shrink:0;background:var(--bg2);">
          <div style="display:flex;gap:8px;align-items:flex-end;">
            <textarea id="td-comment-body" rows="1" placeholder="כתוב עדכון פנימי..." class="td-composer-input"
                      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();submitDetailComment();}"></textarea>
            <button onclick="submitDetailComment()" class="td-send-btn" title="שלח (Enter)">
              <i class="bi bi-send-fill"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Logs column (left): system activity, own scroller -->
      <div style="width:260px;flex-shrink:0;display:flex;flex-direction:column;background:var(--bg1,var(--bg3));">
        <div style="padding:14px 16px 10px;font-size:12px;font-weight:700;color:var(--text3);display:flex;align-items:center;gap:6px;flex-shrink:0;">
          <i class="bi bi-clock-history"></i> יומן פעילות
        </div>
        <div id="td-logs" class="td-logs"></div>
      </div>

    </div>
  </div>
</div>

<script>
const TASK_CSRF   = <?= json_encode($csrf) ?>;
const TASK_BASE   = <?= json_encode($base) ?>;
const STATUSES_BY_TYPE = <?= $statusesJson ?>;
const ALL_TASK_TYPES   = <?= $allTypesJson ?>;
const TASK_USERS  = <?= $usersJson ?>;

/* ── User search in new-task modal ── */
let _selectedUserId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
let _userDDBlurTimer = null;

function filterUsers(q) {
  const dd = document.getElementById('user-dd');
  const filtered = q.trim()
    ? TASK_USERS.filter(u => u.name.includes(q.trim()))
    : TASK_USERS;
  renderUserDD(filtered);
  dd.style.display = 'block';
}

function showUserDD() {
  clearTimeout(_userDDBlurTimer);
  renderUserDD(TASK_USERS);
  document.getElementById('user-dd').style.display = 'block';
}

function hideUserDD() {
  _userDDBlurTimer = setTimeout(() => {
    document.getElementById('user-dd').style.display = 'none';
  }, 200);
}

function renderUserDD(list) {
  const dd = document.getElementById('user-dd');
  if (!dd) return;
  dd.innerHTML = list.map(u => {
    const active = u.id == _selectedUserId;
    return `<div onmousedown="selectUser(${u.id}, ${JSON.stringify(u.name)})"
                 style="padding:8px 14px;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:8px;
                        background:${active ? 'var(--accent-dim)' : 'transparent'};
                        color:${active ? 'var(--accent)' : 'var(--text)'};
                        ${active ? 'font-weight:600;' : ''}
                        transition:background .1s;"
                 onmouseover="this.style.background='var(--bg3)'"
                 onmouseout="this.style.background='${active ? 'var(--accent-dim)' : 'transparent'}'">
              <i class="bi bi-person" style="opacity:.5;"></i>
              ${esc(u.name)}
              ${active ? '<i class="bi bi-check2" style="margin-right:auto;"></i>' : ''}
            </div>`;
  }).join('') || '<div style="padding:10px 14px;color:var(--text3);font-size:13px;">לא נמצאו משתמשים</div>';
}

function selectUser(id, name) {
  clearTimeout(_userDDBlurTimer);
  _selectedUserId = id;
  document.getElementById('new-task-for-user').value = id;
  document.getElementById('user-search-input').value = name;
  document.getElementById('user-dd').style.display = 'none';
}

function openNewTaskModal() {
  _selectedUserId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
  document.getElementById('new-task-for-user').value = _selectedUserId;
  const me = TASK_USERS.find(u => u.id == _selectedUserId);
  const inp = document.getElementById('user-search-input');
  if (inp) inp.value = me ? me.name : '';

  // Populate type select
  const typeEl = document.getElementById('new-task-type');
  typeEl.innerHTML = '<option value="">— ללא סוג —</option>';
  ALL_TASK_TYPES.forEach(t => {
    typeEl.innerHTML += `<option value="${t.id}">${esc(t.name)}</option>`;
  });
  document.getElementById('new-task-modal').style.display = 'flex';
}

/* ── Status dropdown ──────────────────────────────────── */
let _ddOpenTaskId = null;

function toggleStatusDropdown(e, taskId, typeId, currentStatusId) {
  e.stopPropagation();
  const dd = document.getElementById('status-dd');
  if (_ddOpenTaskId === taskId) {
    dd.style.display = 'none';
    _ddOpenTaskId = null;
    return;
  }
  _ddOpenTaskId = taskId;
  const statuses = STATUSES_BY_TYPE[typeId] || [];
  let html = '';
  statuses.forEach(s => {
    const active     = s.id == currentStatusId;
    const safeColor  = sanitizeColor(s.color);
    const isClosed   = s.is_closed == 1;
    html += `<div class="status-option"
                  onclick="setStatus(${taskId},${s.id},'${escJs(s.name)}','${escJs(s.color)}',${isClosed ? 'true' : 'false'})"
                  style="color:${safeColor}${active?' font-weight:800;':''}">`
          + `<span style="width:8px;height:8px;border-radius:50%;background:${safeColor};flex-shrink:0;"></span>`
          + `${esc(s.name)}`
          + (active ? ' <i class="bi bi-check2" style="margin-right:auto;"></i>' : '')
          + `</div>`;
  });
  dd.innerHTML = html;
  const badge = e.currentTarget;
  const rect  = badge.getBoundingClientRect();
  dd.style.top    = (rect.bottom + window.scrollY + 4) + 'px';
  dd.style.right  = (document.body.offsetWidth - rect.right) + 'px';
  dd.style.left   = 'auto';
  dd.style.display = 'block';
}

document.addEventListener('click', () => {
  document.getElementById('status-dd').style.display = 'none';
  _ddOpenTaskId = null;
});

async function setStatus(taskId, statusId, name, color, isClosed) {
  document.getElementById('status-dd').style.display = 'none';
  _ddOpenTaskId = null;

  const fd = new FormData();
  fd.append('_csrf', TASK_CSRF);
  fd.append('status_id', statusId);

  const res  = await fetch(`${TASK_BASE}/tasks/${taskId}/status`, {method:'POST', body:fd});
  const data = await res.json();
  if (data.error) { v2Toast('שגיאה: ' + data.msg); return; }

  // Update badge in-place with flip animation
  const label = document.getElementById(`status-label-${taskId}`);
  if (label) {
    const badge = label.closest('.task-status-badge, .task-status-cell');
    if (badge) {
      const isCell = badge.classList.contains('task-status-cell');
      badge.classList.remove('badge-flip');
      void badge.offsetWidth; // force reflow to restart animation
      badge.classList.add('badge-flip');
      badge.addEventListener('animationend', () => badge.classList.remove('badge-flip'), { once: true });

      const safeColor = sanitizeColor(color);
      badge.style.color       = safeColor;
      badge.style.background  = safeColor + (isCell ? '1a' : '22');
      if (!isCell) badge.style.borderColor = safeColor + '44';
      const dot = badge.querySelector('.dot') || badge.querySelector('span');
      if (dot) dot.style.background = safeColor;
      badge.dataset.currentStatus = statusId;
      label.textContent = name;

      if (isClosed) {
        loadConfettiAndFire(badge);
        const row = document.getElementById(`task-row-${taskId}`);
        if (row) {
          setTimeout(() => {
            row.style.transition = 'opacity .4s';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 420);
          }, 800);
        }
      }
    }
  }

  // Keep the detail modal's badge in sync if it's open on this task
  const modalBadge = document.getElementById('td-status-badge');
  if (modalBadge && modalBadge.dataset.taskId == taskId) {
    const safeColor = sanitizeColor(color);
    modalBadge.style.color = safeColor;
    modalBadge.style.background = safeColor + '22';
    modalBadge.style.borderColor = safeColor + '44';
    modalBadge.dataset.currentStatus = statusId;
    document.getElementById('td-status-dot').style.background = safeColor;
    document.getElementById('td-status-label').textContent = name;
  }

  v2Toast('סטטוס עודכן: ' + name);
}

function loadConfettiAndFire(originEl) {
  if (window.confetti) { fireConfetti(originEl); return; }
  const s = document.createElement('script');
  s.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js';
  s.onload = () => fireConfetti(originEl);
  document.head.appendChild(s);
}

// preload confetti so it's ready on first close
(function(){ const s=document.createElement('script');
  s.src='https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js';
  document.head.appendChild(s); })();

function toggleClosedSection() {
  const sec  = document.getElementById('closed-section');
  const chev = document.getElementById('closed-chevron');
  const open = sec.style.display === 'none';
  sec.style.display  = open ? 'block' : 'none';
  chev.style.transform = open ? 'rotate(-90deg)' : '';
}

function fireConfetti(originEl) {
  const rect = originEl.getBoundingClientRect();
  const x = (rect.left + rect.width / 2) / window.innerWidth;
  const y = (rect.top  + rect.height / 2) / window.innerHeight;
  confetti({ particleCount: 100, spread: 80, origin: { x, y }, zIndex: 9999 });
}

/* ── Inline title edit ───────────────────────────────── */
function startTitleEdit(taskId, spanEl) {
  const current = spanEl.textContent.trim();
  const input = document.createElement('input');
  input.type  = 'text';
  input.value = current;
  input.className = 'task-title-input';
  spanEl.replaceWith(input);
  input.focus();
  input.select();

  const save = async () => {
    const val = input.value.trim();
    if (!val || val === current) {
      input.replaceWith(spanEl);
      return;
    }
    const fd = new FormData();
    fd.append('_csrf', TASK_CSRF);
    fd.append('title', val);
    const res  = await fetch(`${TASK_BASE}/tasks/${taskId}/title`, {method:'POST', body:fd});
    const data = await res.json();
    if (data.error) { v2Toast('שגיאה: ' + data.msg); input.replaceWith(spanEl); return; }
    spanEl.textContent = val;
    input.replaceWith(spanEl);
    v2Toast('כותרת עודכנה');
  };

  input.addEventListener('blur', save);
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter')  { e.preventDefault(); input.blur(); }
    if (e.key === 'Escape') { input.value = current; input.blur(); }
  });
}

/* ── In-page task search ────────────────────────────────── */
function filterTaskRows(q) {
  const query = q.trim().toLowerCase();
  document.querySelectorAll('.task-row').forEach(row => {
    const hay = row.dataset.search || '';
    row.style.display = (!query || hay.includes(query)) ? '' : 'none';
  });
  // Auto-expand the closed section if the query only matches closed tasks
  const closedSec = document.getElementById('closed-section');
  if (closedSec && query) {
    const closedRows = closedSec.querySelectorAll('.task-row');
    const anyClosedVisible = Array.from(closedRows).some(r => r.style.display !== 'none');
    if (anyClosedVisible && closedSec.style.display === 'none') {
      toggleClosedSection();
    }
  }
}

/* ── Helpers ─────────────────────────────────────────── */
function esc(s){ const d=document.createElement('div');d.textContent=s;return d.innerHTML; }
function escJs(s){ return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
function sanitizeColor(s) {
  return /^(#[0-9a-fA-F]{3,8}|rgb[a]?\([^)]*\)|hsl[a]?\([^)]*\)|[a-zA-Z]+)$/.test(String(s).trim())
    ? String(s).trim() : '#6b7280';
}

/* ── Task Detail Modal ──────────────────────────────────── */
let _detailTaskId = null;
let _detailTask    = null;

function openTaskDetail(taskId) {
  _detailTaskId = taskId;
  document.getElementById('task-detail-modal').style.display = 'flex';
  document.getElementById('td-loading').style.display = 'block';
  document.getElementById('td-content').style.display = 'none';
  document.getElementById('td-comment-body').value = '';
  document.getElementById('td-id').textContent = taskId;
  document.addEventListener('keydown', _tdEscHandler);

  fetch(`${TASK_BASE}/tasks/${taskId}`)
    .then(r => r.json())
    .then(data => {
      if (data.error) {
        v2Toast('שגיאה: ' + data.msg);
        closeTaskDetail();
        return;
      }
      renderTaskDetail(data);
    })
    .catch(() => {
      v2Toast('שגיאה בטעינת המשימה');
      closeTaskDetail();
    });
}

function closeTaskDetail() {
  document.getElementById('task-detail-modal').style.display = 'none';
  document.removeEventListener('keydown', _tdEscHandler);
  _detailTaskId = null;
  _detailTask = null;
}

function _tdEscHandler(e) {
  if (e.key === 'Escape') {
    // Don't steal Escape from the inline title-edit input (it has its own handler)
    if (document.activeElement && document.activeElement.classList.contains('task-title-input')) return;
    closeTaskDetail();
  }
}

function renderTaskDetail(data) {
  const t = data.task;
  _detailTask = t;

  document.getElementById('td-title').textContent = t.title || '';
  document.getElementById('td-type').textContent  = t.type_name || '—';
  document.getElementById('td-opener').textContent   = t.opened_by_name || '—';
  document.getElementById('td-assignee').textContent = t.assigned_to_name || '—';
  document.getElementById('td-dept').textContent     = t.dept_name || '—';
  document.getElementById('td-created').textContent  = t.created_at ? t.created_at.slice(0,16).replace('T',' ') : '—';
  document.getElementById('td-changed').textContent  = t.status_changed_at
    ? t.status_changed_at.slice(0,16).replace('T',' ') + (t.changed_by_name ? ' · ' + t.changed_by_name : '')
    : '—';

  const slaTs = t.created_at && t.sla_days
    ? new Date(t.created_at.replace(' ', 'T')).getTime() + (t.sla_days * 86400000)
    : 0;
  document.getElementById('td-sla').textContent = slaTs
    ? new Date(slaTs).toLocaleDateString('he-IL') + (slaTs < Date.now() ? ' (חריגה)' : '')
    : '—';

  const descWrap = document.getElementById('td-desc-wrap');
  if (t.description) {
    descWrap.style.display = 'block';
    document.getElementById('td-desc').textContent = t.description;
  } else {
    descWrap.style.display = 'none';
  }

  // Status badge
  const badge = document.getElementById('td-status-badge');
  const color = t.status_color || '#6b7280';
  badge.style.color = color;
  badge.style.background = color + '22';
  badge.style.borderColor = color + '44';
  badge.dataset.typeId = t.task_type_id || '';
  badge.dataset.currentStatus = t.status_id || '';
  badge.dataset.taskId = t.id;
  document.getElementById('td-status-dot').style.background = color;
  document.getElementById('td-status-label').textContent = t.status_name || '— בחר סטטוס —';
  badge.onclick = (e) => {
    toggleStatusDropdown(e, t.id, parseInt(badge.dataset.typeId) || 0, parseInt(badge.dataset.currentStatus) || 0);
  };

  renderTaskMessages(data.comments || []);
  renderTaskLogs(data.logs || []);

  document.getElementById('td-loading').style.display = 'none';
  document.getElementById('td-content').style.display = 'block';
}

function msgInitial(name) {
  return esc(String(name || '?').trim().charAt(0) || '?');
}

function messageEl(c) {
  const dt = c.created_at ? String(c.created_at).slice(0,16).replace('T',' ') : '';
  const div = document.createElement('div');
  div.className = 'td-msg';
  div.innerHTML = `<div class="td-msg-head">
      <span class="td-msg-avatar">${msgInitial(c.user_name)}</span>
      <span>${esc(c.user_name)}</span>
      <span style="opacity:.5;">·</span>
      <span>${esc(dt)}</span>
    </div>
    <div class="td-msg-body">${esc(c.body)}</div>`;
  return div;
}

function renderTaskMessages(comments) {
  const wrap = document.getElementById('td-messages');
  if (!comments.length) {
    wrap.innerHTML = '<div class="td-messages-empty"><i class="bi bi-chat-left"></i><br>אין עדיין הודעות</div>';
    return;
  }
  wrap.innerHTML = '';
  comments
    .slice()
    .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
    .forEach(c => wrap.appendChild(messageEl(c)));
  wrap.scrollTop = wrap.scrollHeight;
}

function renderTaskLogs(logs) {
  const wrap = document.getElementById('td-logs');
  if (!logs.length) {
    wrap.innerHTML = '<div class="td-logs-empty">אין פעילות רשומה</div>';
    return;
  }
  const sorted = logs.slice().sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  wrap.innerHTML = sorted.map(l => {
    const dt = l.created_at ? String(l.created_at).slice(0,16).replace('T',' ') : '';
    return `<div class="td-log-entry">
      <div class="td-log-detail">${esc(l.detail || l.action || 'פעולה')}</div>
      <div class="td-log-meta">${esc(l.user_name || 'מערכת')} · ${esc(dt)}</div>
    </div>`;
  }).join('');
}

async function submitDetailComment() {
  if (!_detailTaskId) return;
  const el = document.getElementById('td-comment-body');
  const body = el.value.trim();
  if (!body) { v2Toast('כתוב משהו תחילה'); return; }
  if (body.length > 2000) { v2Toast('הערה ארוכה מדי (מקס 2000 תווים)'); return; }

  const fd = new FormData();
  fd.append('_csrf', TASK_CSRF);
  fd.append('body', body);

  const res  = await fetch(`${TASK_BASE}/tasks/${_detailTaskId}/comments`, {method:'POST', body:fd});
  const data = await res.json();
  if (data.error || !data.ok) { v2Toast('שגיאה: ' + (data.msg || 'לא ידוע')); return; }

  el.value = '';

  const wrap = document.getElementById('td-messages');
  const emptyMsg = wrap.querySelector('.td-messages-empty');
  if (emptyMsg) emptyMsg.remove();
  wrap.appendChild(messageEl(data.comment));
  wrap.scrollTop = wrap.scrollHeight;
  v2Toast('הערה נשמרה');
}

/* ── Inline title edit (detail modal) ─────────────────── */
function startDetailTitleEdit(titleEl) {
  if (!_detailTaskId) return;
  const current = titleEl.textContent.trim();
  const input = document.createElement('input');
  input.type  = 'text';
  input.value = current;
  input.className = 'task-title-input';
  input.style.fontSize = '16px';
  titleEl.replaceWith(input);
  input.focus();
  input.select();

  const taskId = _detailTaskId;
  const save = async () => {
    const val = input.value.trim();
    if (!val || val === current) {
      input.replaceWith(titleEl);
      return;
    }
    const fd = new FormData();
    fd.append('_csrf', TASK_CSRF);
    fd.append('title', val);
    const res  = await fetch(`${TASK_BASE}/tasks/${taskId}/title`, {method:'POST', body:fd});
    const data = await res.json();
    if (data.error) { v2Toast('שגיאה: ' + data.msg); input.replaceWith(titleEl); return; }
    titleEl.textContent = val;
    input.replaceWith(titleEl);
    // keep the row's title text in sync too
    const rowSpan = document.getElementById(`title-text-${taskId}`);
    if (rowSpan) rowSpan.textContent = val;
    v2Toast('כותרת עודכנה');
  };

  input.addEventListener('blur', save);
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter')  { e.preventDefault(); input.blur(); }
    if (e.key === 'Escape') { input.value = current; input.blur(); }
  });
}

/* ── Deep-link: open a task's detail modal from ?openTask=ID (e.g. global search) ── */
(function () {
  const params = new URLSearchParams(window.location.search);
  const openTaskId = parseInt(params.get('openTask'), 10);
  if (openTaskId) {
    openTaskDetail(openTaskId);
    params.delete('openTask');
    const qs = params.toString();
    history.replaceState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
  }
})();
</script>
