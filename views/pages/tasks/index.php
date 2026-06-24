<?php
use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
$filter = $filter ?? '';
$isOverdueFilter = $filter === 'overdue';
// JSON-encode statusesByType for JS
$statusesJson = json_encode($statusesByType ?? [], JSON_UNESCAPED_UNICODE);
?>
<style>
.task-status-badge{
  display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;
  font-size:12px;font-weight:700;cursor:pointer;border:1px solid transparent;
  transition:filter .15s,transform .12s;user-select:none;
}
.task-status-badge:hover{filter:brightness(1.2);transform:scale(1.04);}
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
.task-title-text{cursor:pointer;display:inline-block;border-radius:4px;padding:1px 4px;transition:background .13s;}
.task-title-text:hover{background:var(--bg3);}
.task-title-input{
  background:var(--bg3);border:1px solid var(--accent);border-radius:6px;
  color:var(--text);font-size:14px;font-weight:500;font-family:inherit;
  padding:3px 8px;outline:none;width:100%;
}
</style>

<?php if ($isOverdueFilter): ?>
<div style="display:flex;align-items:center;gap:10px;background:rgba(239,68,68,.1);
            border:1px solid rgba(239,68,68,.3);border-radius:var(--radius);
            padding:10px 16px;margin-bottom:16px;color:var(--danger);font-size:13px;font-weight:600;">
  <i class="bi bi-exclamation-triangle-fill"></i>
  מציג משימות שעברו SLA בלבד —
  <a href="<?= $base ?>/tasks" style="color:var(--accent);text-decoration:none;margin-right:4px;">הצג הכל</a>
</div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
  <div class="page-title" style="margin-bottom:0;">
    <?= $showClosed ? 'משימות סגורות' : 'משימות פתוחות' ?>
    <?= $scopeAll ? '— כולם' : '— שלי' ?>
  </div>

  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <!-- Open/Closed toggle -->
    <div style="display:inline-flex;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;font-size:13px;font-weight:600;">
      <a href="?show=open&scope=<?= $scopeAll ? 'all' : 'mine' ?>"
         style="padding:6px 14px;text-decoration:none;<?= !$showClosed ? 'background:var(--accent);color:#fff;' : 'color:var(--text2);' ?>">
        פתוחות
      </a>
      <a href="?show=closed&scope=<?= $scopeAll ? 'all' : 'mine' ?>"
         style="padding:6px 14px;text-decoration:none;<?= $showClosed ? 'background:var(--accent);color:#fff;' : 'color:var(--text2);' ?>">
        סגורות
      </a>
    </div>

    <?php if ($canViewAll): ?>
    <!-- Mine/All toggle -->
    <div style="display:inline-flex;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;font-size:13px;font-weight:600;">
      <a href="?show=<?= $showClosed ? 'closed' : 'open' ?>&scope=mine"
         style="padding:6px 14px;text-decoration:none;<?= !$scopeAll ? 'background:var(--accent);color:#fff;' : 'color:var(--text2);' ?>">
        שלי
      </a>
      <a href="?show=<?= $showClosed ? 'closed' : 'open' ?>&scope=all"
         style="padding:6px 14px;text-decoration:none;<?= $scopeAll ? 'background:var(--accent);color:#fff;' : 'color:var(--text2);' ?>">
        הכל
      </a>
    </div>
    <?php endif; ?>

    <?php if (!$showClosed): ?>
    <button class="btn btn-primary" onclick="document.getElementById('new-task-modal').style.display='flex'">
      + משימה חדשה
    </button>
    <?php endif; ?>
  </div>
</div>

<?php if (empty($tasks)): ?>
  <div class="alert alert-info">
    <?= $showClosed ? 'אין משימות סגורות' : 'אין משימות פתוחות 🎉' ?>
  </div>
<?php else: ?>
<div class="card" style="padding:0;overflow:visible;">
  <table style="width:100%;border-collapse:collapse;font-size:14px;">
    <thead>
      <tr style="color:var(--text2);">
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">#</th>
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">כותרת</th>
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">סטטוס</th>
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">סוג</th>
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">SLA</th>
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">נפתח</th>
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">עדכון סטטוס</th>
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">עודכן ע"י</th>
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">מחלקה</th>
        <th style="padding:10px 14px;border-bottom:1px solid var(--border);"></th>
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
    <tr style="border-bottom:1px solid var(--border);<?= $overdue ? 'border-right:3px solid var(--danger);background:rgba(239,68,68,.05);' : '' ?>"
        id="task-row-<?= (int)$t['id'] ?>">
      <td style="padding:10px 14px;color:var(--text3);"><?= (int)$t['id'] ?></td>

      <!-- Title: double-click to edit -->
      <td style="padding:10px 14px;" class="task-title-cell">
        <div>
          <span class="task-title-text"
                id="title-text-<?= (int)$t['id'] ?>"
                title="לחץ פעמיים לעריכה"
                ondblclick="startTitleEdit(<?= (int)$t['id'] ?>, this)">
            <?= View::e($t['title'] ?? '') ?>
          </span>
        </div>
        <?php if (!empty($t['description'])): ?>
          <div style="font-size:12px;color:var(--text3);margin-top:2px;">
            <?= View::e(mb_substr($t['description'], 0, 70)) ?><?= mb_strlen($t['description']) > 70 ? '…' : '' ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($t['source_type']) && $t['source_type'] === 'invoice_change_name'): ?>
          <a href="<?= $base ?>/invoice-change-name"
             style="font-size:11px;color:var(--accent);text-decoration:none;margin-top:3px;display:inline-flex;align-items:center;gap:3px;">
            <i class="bi bi-box-arrow-up-left"></i> צפה בבקשה
          </a>
        <?php endif; ?>
      </td>

      <!-- Status badge with dropdown -->
      <td style="padding:10px 14px;position:relative;">
        <?php if ($typeId && $statusId): ?>
          <span class="task-status-badge"
                data-type-id="<?= $typeId ?>"
                data-current-status="<?= $statusId ?>"
                style="color:<?= View::e($statusColor) ?>;background:<?= View::e($statusColor) ?>22;border-color:<?= View::e($statusColor) ?>44;"
                onclick="toggleStatusDropdown(event, <?= (int)$t['id'] ?>, parseInt(this.dataset.typeId), parseInt(this.dataset.currentStatus))">
            <span style="width:7px;height:7px;border-radius:50%;background:<?= View::e($statusColor) ?>;flex-shrink:0;"></span>
            <span id="status-label-<?= (int)$t['id'] ?>"><?= View::e($statusName) ?></span>
          </span>
        <?php else: ?>
          <span style="color:var(--text3);font-size:13px;">—</span>
        <?php endif; ?>
      </td>

      <td style="padding:10px 14px;color:var(--text2);font-size:13px;">
        <?= View::e($t['type_name'] ?? '—') ?>
      </td>

      <td style="padding:10px 14px;">
        <?php if ($slaTs): ?>
          <span class="badge <?= $overdue ? 'badge-danger' : 'badge-success' ?>"><?= $slaDate ?></span>
        <?php else: ?>—<?php endif; ?>
      </td>

      <td style="padding:10px 14px;color:var(--text2);font-size:13px;"><?= $created ?></td>

      <td style="padding:10px 14px;color:var(--text2);font-size:12px;white-space:nowrap;">
        <?= $t['status_changed_at'] ? date('d/m/Y H:i', strtotime($t['status_changed_at'])) : '—' ?>
      </td>
      <td style="padding:10px 14px;color:var(--text2);font-size:13px;">
        <?= \Core\View::e($t['changed_by_name'] ?? '—') ?>
      </td>
      <td style="padding:10px 14px;color:var(--text2);font-size:13px;">
        <?= \Core\View::e($t['dept_name'] ?? '—') ?>
      </td>

      <td style="padding:10px 14px;text-align:center;">
        <button class="btn-icon" onclick="openComments(<?= (int)$t['id'] ?>, <?= \Core\View::e(json_encode($t['title'])) ?>)"
                title="הערות פנימיות"
                style="background:none;border:none;color:var(--text3);cursor:pointer;font-size:16px;padding:4px 8px;border-radius:6px;transition:color .15s,background .15s;">
          <i class="bi bi-chat-dots"></i>
        </button>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Status dropdown (shared, positioned absolutely) -->
<div id="status-dd" class="status-dropdown" style="display:none;"></div>

<!-- New task modal -->
<div id="new-task-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;align-items:center;justify-content:center;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:28px;width:100%;max-width:480px;">
    <button onclick="document.getElementById('new-task-modal').style.display='none'"
            style="float:left;background:none;border:none;color:var(--text2);font-size:20px;cursor:pointer;">✕</button>
    <div style="font-size:17px;font-weight:600;margin-bottom:20px;">משימה חדשה</div>
    <form method="POST" action="<?= $base ?>/tasks/create">
      <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">כותרת *</label>
        <input type="text" name="title" required
               style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;">
      </div>
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">תיאור</label>
        <textarea name="description" rows="3"
                  style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;resize:vertical;"></textarea>
      </div>
      <div style="margin-bottom:20px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">SLA (ימים)</label>
        <input type="number" name="sla_days" value="3" min="1" max="30"
               style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">צור משימה</button>
    </form>
  </div>
</div>

<!-- Comment Drawer -->
<div id="comment-drawer"
     style="position:fixed;top:0;right:-400px;width:370px;height:100vh;
            background:var(--bg2);border-left:1px solid var(--border2);
            box-shadow:var(--shadow);z-index:400;
            transition:right .25s ease;
            display:flex;flex-direction:column;padding:0;">

  <!-- Header -->
  <div style="padding:16px 18px;border-bottom:1px solid var(--border);
              display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
    <div id="comment-drawer-title" style="font-size:15px;font-weight:700;color:var(--text);
         max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>
    <button onclick="closeCommentDrawer()"
            style="background:none;border:none;color:var(--text3);font-size:20px;cursor:pointer;line-height:1;">✕</button>
  </div>

  <!-- Comment list (scrollable) -->
  <div id="comment-list"
       style="flex:1;overflow-y:auto;padding:14px 18px;display:flex;flex-direction:column;gap:10px;">
    <div id="comment-loading" style="color:var(--text3);font-size:13px;text-align:center;padding:20px;">טוען...</div>
  </div>

  <!-- Input area -->
  <div style="padding:14px 18px;border-top:1px solid var(--border);flex-shrink:0;">
    <textarea id="comment-body" rows="3" placeholder="כתוב עדכון פנימי..."
              style="width:100%;background:var(--bg3);border:1px solid var(--border);
                     border-radius:var(--radius);color:var(--text);font-size:13px;
                     font-family:inherit;padding:9px 12px;outline:none;
                     resize:vertical;box-sizing:border-box;"></textarea>
    <button onclick="submitComment()" class="btn btn-primary"
            style="width:100%;margin-top:8px;">שלח עדכון</button>
  </div>
</div>
<div id="comment-overlay"
     onclick="closeCommentDrawer()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:399;"></div>

<script>
const TASK_CSRF   = <?= json_encode($csrf) ?>;
const TASK_BASE   = <?= json_encode($base) ?>;
const STATUSES_BY_TYPE = <?= $statusesJson ?>;

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
    const badge = label.closest('.task-status-badge');
    if (badge) {
      badge.classList.remove('badge-flip');
      void badge.offsetWidth; // force reflow to restart animation
      badge.classList.add('badge-flip');
      badge.addEventListener('animationend', () => badge.classList.remove('badge-flip'), { once: true });

      const safeColor = sanitizeColor(color);
      badge.style.color       = safeColor;
      badge.style.background  = safeColor + '22';
      badge.style.borderColor = safeColor + '44';
      badge.querySelector('span').style.background = safeColor;
      badge.dataset.currentStatus = statusId;
      label.textContent = name;

      if (isClosed) {
        loadConfettiAndFire(badge);
      }
    }
  }
  v2Toast('סטטוס עודכן: ' + name);
}

function loadConfettiAndFire(originEl) {
  if (window.confetti) {
    fireConfetti(originEl);
    return;
  }
  const s = document.createElement('script');
  s.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js';
  s.onload = () => fireConfetti(originEl);
  document.head.appendChild(s);
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

/* ── Helpers ─────────────────────────────────────────── */
function esc(s){ const d=document.createElement('div');d.textContent=s;return d.innerHTML; }
function escJs(s){ return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
function sanitizeColor(s) {
  return /^(#[0-9a-fA-F]{3,8}|rgb[a]?\([^)]*\)|hsl[a]?\([^)]*\)|[a-zA-Z]+)$/.test(String(s).trim())
    ? String(s).trim() : '#6b7280';
}

/* ── Comment Drawer ──────────────────────────────────── */
let _commentTaskId = null;

function openComments(taskId, taskTitle) {
  _commentTaskId = taskId;
  document.getElementById('comment-drawer-title').textContent = taskTitle;
  document.getElementById('comment-list').innerHTML =
    '<div style="color:var(--text3);font-size:13px;text-align:center;padding:20px;">טוען...</div>';
  document.getElementById('comment-body').value = '';

  // Slide open
  document.getElementById('comment-drawer').style.right  = '0';
  document.getElementById('comment-overlay').style.display = 'block';

  // Load comments
  fetch(`${TASK_BASE}/tasks/${taskId}/comments`)
    .then(r => r.json())
    .then(data => {
      if (data.error) {
        document.getElementById('comment-list').innerHTML =
          `<div style="color:var(--danger);font-size:13px;">${esc(data.msg)}</div>`;
        return;
      }
      renderComments(data);
    })
    .catch(() => {
      document.getElementById('comment-list').innerHTML =
        '<div style="color:var(--danger);font-size:13px;">שגיאה בטעינת הערות</div>';
    });
}

function renderComments(list) {
  const container = document.getElementById('comment-list');
  if (!list.length) {
    container.innerHTML = '<div id="comment-empty" style="color:var(--text3);font-size:13px;text-align:center;padding:20px;">אין הערות עדיין</div>';
    return;
  }
  container.innerHTML = list.map(c => {
    const dt = c.created_at ? c.created_at.slice(0,16).replace('T',' ') : '';
    return `<div style="background:var(--bg3);border-radius:8px;padding:10px 12px;">
      <div style="font-size:11px;color:var(--text3);margin-bottom:5px;">
        <i class="bi bi-person-fill"></i> ${esc(c.user_name)} &nbsp;·&nbsp; ${esc(dt)}
      </div>
      <div style="font-size:13px;color:var(--text);white-space:pre-wrap;">${esc(c.body)}</div>
    </div>`;
  }).join('');
  // scroll to bottom
  container.scrollTop = container.scrollHeight;
}

async function submitComment() {
  if (!_commentTaskId) return;
  const body = document.getElementById('comment-body').value.trim();
  if (!body) { v2Toast('כתוב משהו תחילה'); return; }
  if (body.length > 2000) { v2Toast('הערה ארוכה מדי (מקס 2000 תווים)'); return; }

  const fd = new FormData();
  fd.append('_csrf', TASK_CSRF);
  fd.append('body', body);

  const res  = await fetch(`${TASK_BASE}/tasks/${_commentTaskId}/comments`, {method:'POST', body:fd});
  const data = await res.json();
  if (data.error || !data.ok) { v2Toast('שגיאה: ' + (data.msg || 'לא ידוע')); return; }

  document.getElementById('comment-body').value = '';

  // Append new comment to list
  const container = document.getElementById('comment-list');
  const emptyMsg  = container.querySelector('#comment-empty');
  if (emptyMsg) emptyMsg.remove();

  const c   = data.comment;
  const dt  = (c.created_at || '').slice(0,16).replace('T',' ');
  const div = document.createElement('div');
  div.style.cssText = 'background:var(--bg3);border-radius:8px;padding:10px 12px;';
  div.innerHTML = `<div style="font-size:11px;color:var(--text3);margin-bottom:5px;">
      <i class="bi bi-person-fill"></i> ${esc(c.user_name)} &nbsp;·&nbsp; ${esc(dt)}
    </div>
    <div style="font-size:13px;color:var(--text);white-space:pre-wrap;">${esc(c.body)}</div>`;
  container.appendChild(div);
  container.scrollTop = container.scrollHeight;
  v2Toast('הערה נשמרה');
}

function closeCommentDrawer() {
  document.getElementById('comment-drawer').style.right  = '-400px';
  document.getElementById('comment-overlay').style.display = 'none';
  _commentTaskId = null;
}
</script>
