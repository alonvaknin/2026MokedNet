<?php
use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
$typesJson    = json_encode($types         ?? [], JSON_UNESCAPED_UNICODE);
$statusesJson = json_encode($statusesByType ?? [], JSON_UNESCAPED_UNICODE);
$usersJson    = json_encode($users          ?? [], JSON_UNESCAPED_UNICODE);
?>
<style>
/* ── Tabs ── */
.ts-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:24px;}
.ts-tab{padding:10px 20px;font-size:14px;font-weight:600;color:var(--text2);cursor:pointer;
  border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;}
.ts-tab.active{color:var(--accent);border-bottom-color:var(--accent);}
.ts-pane{display:none;} .ts-pane.active{display:block;}

/* ── Table ── */
.ts-table{width:100%;border-collapse:collapse;font-size:14px;}
.ts-table th{text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);
  font-weight:500;color:var(--text2);}
.ts-table td{padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:middle;}
.ts-table tr:last-child td{border-bottom:none;}

/* ── Inline edit ── */
.ts-edit-input{background:var(--bg3);border:1px solid var(--accent);border-radius:6px;
  color:var(--text);font-size:14px;font-family:inherit;padding:3px 8px;outline:none;width:100%;}

/* ── Chips ── */
.ts-chips{display:flex;flex-wrap:wrap;gap:5px;align-items:center;}
.ts-chip{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;background:var(--accent-dim);
  color:var(--accent);border-radius:12px;font-size:12px;font-weight:600;}
.ts-chip-x{cursor:pointer;opacity:.7;font-size:11px;} .ts-chip-x:hover{opacity:1;}

/* ── Assignee dropdown ── */
.ts-assign-dd{position:absolute;z-index:100;background:var(--bg2);border:1px solid var(--border2);
  border-radius:var(--radius);box-shadow:var(--shadow);min-width:200px;max-height:260px;
  overflow-y:auto;padding:6px 0;}
.ts-assign-opt{display:flex;align-items:center;gap:8px;padding:8px 14px;cursor:pointer;
  font-size:13px;transition:background .12s;}
.ts-assign-opt:hover{background:var(--bg3);}
.ts-assign-opt input[type=checkbox]{accent-color:var(--accent);}

/* ── Add row ── */
.ts-add-row{background:var(--bg3);}
.ts-add-input{background:var(--bg2);border:1px solid var(--border);border-radius:6px;
  color:var(--text);font-size:14px;font-family:inherit;padding:6px 10px;outline:none;
  transition:border-color .15s;}
.ts-add-input:focus{border-color:var(--accent);}

/* ── Status card ── */
.ts-status-card{display:flex;align-items:center;gap:10px;padding:10px 14px;
  border-bottom:1px solid var(--border);transition:background .12s;}
.ts-status-card:hover{background:var(--bg3);}
.ts-color-swatch{width:22px;height:22px;border-radius:5px;cursor:pointer;border:2px solid var(--border2);
  flex-shrink:0;transition:transform .15s;} .ts-color-swatch:hover{transform:scale(1.15);}
.ts-order-btn{background:none;border:1px solid var(--border);border-radius:5px;
  color:var(--text2);cursor:pointer;padding:2px 7px;font-size:13px;transition:background .12s,color .12s;}
.ts-order-btn:hover{background:var(--bg3);color:var(--text);}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <div class="page-title" style="margin-bottom:0;">הגדרות מערכת משימות</div>
</div>

<div class="card" style="padding:24px;">
  <!-- Tabs -->
  <div class="ts-tabs">
    <div class="ts-tab active" onclick="tsTab('types')">סוגי משימות</div>
    <div class="ts-tab"       onclick="tsTab('statuses')">סטטוסים</div>
  </div>

  <!-- Tab: Types -->
  <div id="ts-pane-types" class="ts-pane active">
    <table class="ts-table">
      <thead>
        <tr>
          <th>#</th><th>שם</th><th>SLA (ימים)</th><th>Assignees</th><th></th>
        </tr>
      </thead>
      <tbody id="ts-types-body">
      <!-- Add row -->
      <tr class="ts-add-row">
        <td style="color:var(--text3);">+</td>
        <td><input class="ts-add-input" id="new-type-name"    placeholder="שם הסוג" style="width:180px;"></td>
        <td><input class="ts-add-input" id="new-type-sla"     type="number" value="3" min="1" max="365" style="width:80px;"></td>
        <td style="color:var(--text3);font-size:12px;">ניתן להגדיר assignees לאחר יצירה</td>
        <td>
          <button class="btn btn-primary" style="padding:6px 14px;font-size:13px;" onclick="addType()">+ הוסף</button>
        </td>
      </tr>
      <?php foreach ($types as $t):
        $decoded = json_decode($t['default_assignee_ids'] ?? '[]', true);
        $assigneeIds = is_array($decoded) ? $decoded : [];
      ?>
      <tr id="type-row-<?= (int)$t['id'] ?>">
        <td style="color:var(--text3);"><?= (int)$t['id'] ?></td>
        <td>
          <span class="ts-edit-span" onclick="startTypeEdit(<?= (int)$t['id'] ?>,'name',this)">
            <?= View::e($t['name']) ?>
          </span>
        </td>
        <td>
          <span class="ts-edit-span" onclick="startTypeEdit(<?= (int)$t['id'] ?>,'sla_days',this)">
            <?= (int)$t['sla_days'] ?>
          </span>
        </td>
        <td style="position:relative;">
          <div class="ts-chips" id="chips-<?= (int)$t['id'] ?>">
            <?php foreach ($assigneeIds as $uid):
              $uname = '';
              foreach ($users as $u) { if ((int)$u['id'] === $uid) { $uname = $u['name']; break; } }
              if (!$uname) continue;
            ?>
            <span class="ts-chip" data-uid="<?= $uid ?>">
              <?= View::e($uname) ?>
              <span class="ts-chip-x" onclick="removeAssignee(<?= (int)$t['id'] ?>,<?= $uid ?>)">✕</span>
            </span>
            <?php endforeach; ?>
            <button class="btn btn-ghost" style="padding:2px 8px;font-size:12px;"
                    onclick="openAssigneeDD(event,<?= (int)$t['id'] ?>)">✏️</button>
          </div>
        </td>
        <td>
          <button class="btn btn-ghost" style="padding:5px 10px;color:var(--danger);"
                  onclick="deleteType(<?= (int)$t['id'] ?>,'<?= View::e(addslashes($t['name'])) ?>')">🗑</button>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Tab: Statuses -->
  <div id="ts-pane-statuses" class="ts-pane">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
      <label style="color:var(--text2);font-size:13px;">סוג משימה:</label>
      <select id="ts-type-select" onchange="renderStatuses(this.value)"
              style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;
                     color:var(--text);padding:7px 12px;font-size:14px;font-family:inherit;outline:none;">
        <option value="">— בחר סוג —</option>
        <?php foreach ($types as $t): ?>
        <option value="<?= (int)$t['id'] ?>"><?= View::e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="ts-statuses-container"></div>
  </div>
</div>

<!-- Assignee dropdown (shared) -->
<div id="ts-assign-dd" class="ts-assign-dd" style="display:none;position:absolute;"></div>

<script>
const TS_BASE   = <?= json_encode($base) ?>;
const TS_CSRF   = <?= json_encode($csrf) ?>;
const TS_TYPES  = <?= $typesJson ?>;
const TS_STATUSES = <?= $statusesJson ?>;
const TS_USERS  = <?= $usersJson ?>;

// Current assignees per type (mutable)
const typeAssignees = {};
TS_TYPES.forEach(t => {
  try { typeAssignees[t.id] = JSON.parse(t.default_assignee_ids || '[]'); }
  catch(e) { typeAssignees[t.id] = []; }
});

/* ── Tab switching ── */
function tsTab(name) {
  document.querySelectorAll('.ts-tab').forEach((el,i) => {
    el.classList.toggle('active', (name==='types'&&i===0)||(name==='statuses'&&i===1));
  });
  document.querySelectorAll('.ts-pane').forEach(p => p.classList.remove('active'));
  document.getElementById('ts-pane-'+name).classList.add('active');
}

/* ── Helpers ── */
function esc(s){const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
async function tsPost(url, data) {
  const fd = new FormData();
  fd.append('_csrf', TS_CSRF);
  Object.entries(data).forEach(([k,v]) => fd.append(k, v));
  const res = await fetch(url, {method:'POST', body:fd});
  return res.json();
}

/* ── Type: add ── */
async function addType() {
  const name = document.getElementById('new-type-name').value.trim();
  const sla  = document.getElementById('new-type-sla').value;
  if (!name) { v2Toast('יש להזין שם'); return; }
  const d = await tsPost(`${TS_BASE}/admin/task-settings/types`, {name, sla_days: sla, assignee_ids: '[]'});
  if (d.error) { v2Toast('שגיאה: '+d.msg); return; }
  v2Toast('סוג נוצר — מרענן...');
  setTimeout(() => location.reload(), 800);
}

/* ── Type: inline edit ── */
function startTypeEdit(typeId, field, spanEl) {
  const current = spanEl.textContent.trim();
  const input = document.createElement('input');
  input.className = 'ts-edit-input';
  input.value = current;
  if (field === 'sla_days') { input.type='number'; input.min=1; input.max=365; input.style.width='80px'; }
  spanEl.replaceWith(input);
  input.focus(); input.select();

  const save = async () => {
    const val = input.value.trim();
    if (!val || val === current) { input.replaceWith(spanEl); return; }
    const d = await tsPost(`${TS_BASE}/admin/task-settings/types/${typeId}`, {[field]: val});
    if (d.error) { v2Toast('שגיאה: '+d.msg); input.replaceWith(spanEl); return; }
    spanEl.textContent = val;
    input.replaceWith(spanEl);
    v2Toast('עודכן');
  };
  input.addEventListener('blur', save);
  input.addEventListener('keydown', e => {
    if (e.key==='Enter') { e.preventDefault(); input.blur(); }
    if (e.key==='Escape') { input.value=current; input.blur(); }
  });
}

/* ── Assignees ── */
function openAssigneeDD(e, typeId) {
  e.stopPropagation();
  const dd = document.getElementById('ts-assign-dd');
  if (dd.dataset.typeId == typeId && dd.style.display !== 'none') {
    dd.style.display = 'none'; return;
  }
  dd.dataset.typeId = typeId;
  const current = typeAssignees[typeId] || [];
  let html = '';
  TS_USERS.forEach(u => {
    const checked = current.includes(u.id) ? 'checked' : '';
    html += `<label class="ts-assign-opt">
      <input type="checkbox" value="${u.id}" ${checked} onchange="toggleAssignee(${typeId},${u.id},this.checked)">
      ${esc(u.name)}
    </label>`;
  });
  dd.innerHTML = html;
  const btn = e.currentTarget;
  const rect = btn.getBoundingClientRect();
  dd.style.top  = (rect.bottom + window.scrollY + 4) + 'px';
  dd.style.right = (document.body.offsetWidth - rect.right) + 'px';
  dd.style.left  = 'auto';
  dd.style.display = 'block';
}

async function toggleAssignee(typeId, userId, add) {
  const arr = typeAssignees[typeId] || [];
  const next = add ? [...new Set([...arr, userId])] : arr.filter(id => id !== userId);
  typeAssignees[typeId] = next;
  const d = await tsPost(`${TS_BASE}/admin/task-settings/types/${typeId}`, {assignee_ids: JSON.stringify(next)});
  if (d.error) { v2Toast('שגיאה: '+d.msg); return; }
  refreshChips(typeId);
}

async function removeAssignee(typeId, userId) {
  await toggleAssignee(typeId, userId, false);
}

function refreshChips(typeId) {
  const container = document.getElementById('chips-'+typeId);
  if (!container) return;
  const arr = typeAssignees[typeId] || [];
  const btn = container.querySelector('button');
  container.querySelectorAll('.ts-chip').forEach(c => c.remove());
  arr.forEach(uid => {
    const u = TS_USERS.find(x => x.id == uid);
    if (!u) return;
    const chip = document.createElement('span');
    chip.className = 'ts-chip';
    chip.dataset.uid = uid;
    chip.innerHTML = `${esc(u.name)}<span class="ts-chip-x" onclick="removeAssignee(${typeId},${uid})">✕</span>`;
    container.insertBefore(chip, btn);
  });
}

document.addEventListener('click', e => {
  const dd = document.getElementById('ts-assign-dd');
  if (!dd.contains(e.target)) dd.style.display = 'none';
});

/* ── Type: delete ── */
async function deleteType(typeId, name) {
  if (!confirm(`למחוק את הסוג "${name}"?`)) return;
  const d = await tsPost(`${TS_BASE}/admin/task-settings/types/${typeId}/delete`, {});
  if (d.error) { v2Toast('שגיאה: '+d.msg); return; }
  document.getElementById('type-row-'+typeId)?.remove();
  v2Toast('הסוג נמחק');
}

/* ── Statuses tab ── */
function renderStatuses(typeId) {
  const container = document.getElementById('ts-statuses-container');
  if (!typeId) { container.innerHTML = ''; return; }
  const statuses = TS_STATUSES[typeId] || [];

  let html = `<div id="status-list-${typeId}">`;
  statuses.forEach((s, idx) => {
    const safeColor = /^#[0-9a-fA-F]{3,8}$/.test(s.color) ? s.color : '#4f7fff';
    html += `<div class="ts-status-card" id="sc-${s.id}">
      <button class="ts-order-btn" onclick="moveStatus(${typeId},${s.id},-1)" ${idx===0?'disabled':''}>▲</button>
      <button class="ts-order-btn" onclick="moveStatus(${typeId},${s.id},1)" ${idx===statuses.length-1?'disabled':''}>▼</button>
      <div class="ts-color-swatch" style="background:${safeColor};"
           onclick="document.getElementById('color-input-${s.id}').click()"></div>
      <input type="color" id="color-input-${s.id}" value="${safeColor}" style="display:none;"
             onchange="updateStatusColor(${s.id},this.value)">
      <span style="flex:1;" onclick="startStatusEdit(${s.id},this)">${esc(s.name)}</span>
      <button class="btn btn-ghost" style="padding:4px 8px;color:var(--danger);"
              onclick="deleteStatus(${typeId},${s.id},'${s.name.replace(/'/g,"\\'")}')">🗑</button>
    </div>`;
  });

  html += `</div>
  <div style="display:flex;gap:8px;align-items:center;margin-top:14px;padding:0 14px;">
    <input class="ts-add-input" id="new-status-name" placeholder="שם הסטטוס" style="flex:1;">
    <input type="color" id="new-status-color" value="#4f7fff" style="width:36px;height:36px;border:none;cursor:pointer;background:none;">
    <button class="btn btn-primary" style="padding:7px 16px;font-size:13px;" onclick="addStatus(${typeId})">+ הוסף</button>
  </div>`;

  container.innerHTML = html;
}

function startStatusEdit(statusId, spanEl) {
  const current = spanEl.textContent.trim();
  const input = document.createElement('input');
  input.className = 'ts-edit-input';
  input.value = current;
  spanEl.replaceWith(input);
  input.focus(); input.select();

  const save = async () => {
    const val = input.value.trim();
    if (!val || val === current) { input.replaceWith(spanEl); return; }
    const d = await tsPost(`${TS_BASE}/admin/task-settings/statuses/${statusId}`, {name: val});
    if (d.error) { v2Toast('שגיאה: '+d.msg); input.replaceWith(spanEl); return; }
    spanEl.textContent = val;
    input.replaceWith(spanEl);
    // update local data
    Object.values(TS_STATUSES).flat().forEach(s => { if (s.id==statusId) s.name=val; });
    v2Toast('עודכן');
  };
  input.addEventListener('blur', save);
  input.addEventListener('keydown', e => {
    if (e.key==='Enter') { e.preventDefault(); input.blur(); }
    if (e.key==='Escape') { input.value=current; input.blur(); }
  });
}

async function updateStatusColor(statusId, color) {
  const swatch = document.querySelector(`#sc-${statusId} .ts-color-swatch`);
  if (swatch) swatch.style.background = color;
  const d = await tsPost(`${TS_BASE}/admin/task-settings/statuses/${statusId}`, {color});
  if (d.error) v2Toast('שגיאה: '+d.msg);
  else v2Toast('צבע עודכן');
}

async function moveStatus(typeId, statusId, dir) {
  const list = TS_STATUSES[typeId] || [];
  const idx  = list.findIndex(s => s.id == statusId);
  const swapIdx = idx + dir;
  if (swapIdx < 0 || swapIdx >= list.length) return;

  const a = list[idx], b = list[swapIdx];
  const origA = a.sort_order, origB = b.sort_order;
  [a.sort_order, b.sort_order] = [b.sort_order, a.sort_order];
  list[idx] = b; list[swapIdx] = a;

  const [ra, rb] = await Promise.all([
    tsPost(`${TS_BASE}/admin/task-settings/statuses/${a.id}`, {sort_order: a.sort_order}),
    tsPost(`${TS_BASE}/admin/task-settings/statuses/${b.id}`, {sort_order: b.sort_order}),
  ]);

  if (ra.error || rb.error) {
    // rollback local state
    a.sort_order = origA; b.sort_order = origB;
    list[idx] = a; list[swapIdx] = b;
    v2Toast('שגיאה בשינוי סדר');
  }

  renderStatuses(typeId);
}

async function addStatus(typeId) {
  const name  = document.getElementById('new-status-name')?.value.trim();
  const color = document.getElementById('new-status-color')?.value || '#4f7fff';
  if (!name) { v2Toast('יש להזין שם'); return; }
  const list = TS_STATUSES[typeId] || [];
  const sortOrder = list.length ? Math.max(...list.map(s => s.sort_order)) + 1 : 1;
  const d = await tsPost(`${TS_BASE}/admin/task-settings/statuses`, {
    task_type_id: typeId, name, color, sort_order: sortOrder
  });
  if (d.error) { v2Toast('שגיאה: '+d.msg); return; }
  // update local & re-render
  list.push({id: d.id, task_type_id: typeId, name, color, sort_order: sortOrder});
  TS_STATUSES[typeId] = list;
  renderStatuses(typeId);
  v2Toast('סטטוס נוסף');
}

async function deleteStatus(typeId, statusId, name) {
  if (!confirm(`למחוק סטטוס "${name}"?`)) return;
  const d = await tsPost(`${TS_BASE}/admin/task-settings/statuses/${statusId}/delete`, {});
  if (d.error) { v2Toast('שגיאה: '+d.msg); return; }
  if (TS_STATUSES[typeId]) {
    TS_STATUSES[typeId] = TS_STATUSES[typeId].filter(s => s.id != statusId);
  }
  renderStatuses(typeId);
  v2Toast('נמחק');
}
</script>
