<?php
use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
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

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <div class="page-title" style="margin-bottom:0;">המשימות שלי</div>
  <button class="btn btn-primary" onclick="document.getElementById('new-task-modal').style.display='flex'">
    + משימה חדשה
  </button>
</div>

<?php if (empty($tasks)): ?>
  <div class="alert alert-info">אין משימות פתוחות 🎉</div>
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
    <tr style="border-bottom:1px solid var(--border);" id="task-row-<?= (int)$t['id'] ?>">
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
                style="color:<?= View::e($statusColor) ?>;background:<?= View::e($statusColor) ?>22;border-color:<?= View::e($statusColor) ?>44;"
                onclick="toggleStatusDropdown(event, <?= (int)$t['id'] ?>, <?= $typeId ?>, <?= $statusId ?>)">
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

      <td style="padding:10px 14px;">
        <form method="POST" action="<?= $base ?>/tasks/<?= (int)$t['id'] ?>/close"
              onsubmit="return confirm('לסגור משימה זו?')">
          <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
          <button type="submit" class="btn btn-ghost" style="padding:5px 10px;font-size:13px;">✓ סגור</button>
        </form>
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
    const active = s.id == currentStatusId;
    const safeColor = sanitizeColor(s.color);
    html += `<div class="status-option" onclick="setStatus(${taskId},${s.id},'${escJs(s.name)}','${escJs(s.color)}')"
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

async function setStatus(taskId, statusId, name, color) {
  document.getElementById('status-dd').style.display = 'none';
  _ddOpenTaskId = null;

  const fd = new FormData();
  fd.append('_csrf', TASK_CSRF);
  fd.append('status_id', statusId);

  const res = await fetch(`${TASK_BASE}/tasks/${taskId}/status`, {method:'POST', body:fd});
  const data = await res.json();
  if (data.error) { v2Toast('שגיאה: ' + data.msg); return; }

  // Update badge in-place
  const label = document.getElementById(`status-label-${taskId}`);
  if (label) {
    label.textContent = name;
    const badge = label.closest('.task-status-badge');
    if (badge) {
      const safeColor = sanitizeColor(color);
      badge.style.color = safeColor;
      badge.style.background = safeColor + '22';
      badge.style.borderColor = safeColor + '44';
      badge.querySelector('span').style.background = safeColor;
    }
  }
  v2Toast('סטטוס עודכן: ' + name);
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
</script>
