<?php
use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
$permGroups = $permGroups ?? [];
$items = $items ?? [];
$parents  = array_filter((array)$items, fn($i) => !$i['parent_id']);
$childMap = [];

foreach ((array)$items as $i) {
    if ($i['parent_id']) $childMap[$i['parent_id']][] = $i;
}
usort($parents, fn($a,$b) => ($a['ordering']??0) <=> ($b['ordering']??0));

$itemsJson      = json_encode(array_values((array)$items), JSON_UNESCAPED_UNICODE);
$permGroupsJson = json_encode(array_values((array)$permGroups), JSON_UNESCAPED_UNICODE);
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
  <div class="page-title" style="margin-bottom:0;">ניהול תפריט ניווט</div>
  <div style="display:flex;gap:8px;">
    <button class="btn btn-ghost" id="reorder-btn" onclick="saveOrder()" style="display:none;">
      <i class="bi bi-check-lg"></i> שמור סדר
    </button>
    <button class="btn btn-primary" onclick="openModal(null)">
      <i class="bi bi-plus-lg"></i> פריט חדש
    </button>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start;">

  <div class="card" style="padding:0;overflow:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:8px;">
      <div style="font-size:13px;color:var(--text2);">
        <span id="count-active" style="color:var(--success);font-weight:600;"></span> פעילים ·
        <span id="count-total"></span> סה"כ
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--text2);">
          <input type="checkbox" id="show-inactive" onchange="renderTree()" checked> מושבתים
        </label>
        <select id="group-filter" onchange="renderTree()"
                style="background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:5px 9px;color:var(--text);font-size:12px;outline:none;">
          <option value="">כל הקבוצות</option>
          <option value="__all__">גלוי לכולם</option>
        </select>
        <input type="text" id="nav-filter" placeholder="חיפוש..." oninput="renderTree()"
               style="background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:5px 9px;color:var(--text);font-size:12px;outline:none;width:120px;">
      </div>
    </div>
    <div id="nav-tree" style="min-height:100px;"></div>
  </div>

  <div style="display:flex;flex-direction:column;gap:12px;">
    <!-- Group preview -->
    <div class="card" id="group-preview-card" style="display:none;">
      <div class="card-header" id="group-preview-title" style="color:#8b5cf6;"><i class="bi bi-shield-lock"></i> תצוגה לפי קבוצה</div>
      <div id="group-preview-list" style="font-size:13px;"></div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-info-circle" style="color:var(--accent);"></i> מקרא</div>
      <div style="font-size:13px;display:flex;flex-direction:column;gap:7px;">
        <div class="legend-row"><span class="ldot" style="background:var(--success)"></span>פעיל — גלוי</div>
        <div class="legend-row"><span class="ldot" style="background:var(--danger)"></span>מושבת</div>
        <div class="legend-row"><span class="ldot" style="background:var(--accent)"></span>קישור חיצוני</div>
        <div class="legend-row"><span class="ldot" style="background:#f59e0b"></span>קטגוריה</div>
        <div style="font-size:11px;color:var(--text3);padding-top:6px;border-top:1px solid var(--border);">
          ↑↓ לשינוי סדר · שמור סדר לאישור
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-shield-lock" style="color:#8b5cf6;"></i> קבוצות הרשאה</div>
      <div style="font-size:12px;">
        <?php foreach ($permGroups as $g): ?>
          <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--border);color:var(--text2);cursor:pointer;"
               onclick="document.getElementById('group-filter').value='<?= (int)$g['id'] ?>';renderTree();"
               title="סנן לפי קבוצה זו">
            <span><?= View::e($g['permmisionsGroupHeb']) ?></span>
            <span style="color:var(--text3);">ID <?= (int)$g['id'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:300;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:520px;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg2);z-index:1;">
      <div id="modal-title" style="font-size:16px;font-weight:700;"></div>
      <button onclick="closeModal()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
    </div>
    <div style="padding:20px;">
      <input type="hidden" id="f-id">
      <div class="mf-section" style="--mc:#5b8dee;">
        <div class="mf-title"><i class="bi bi-tag-fill"></i> זיהוי</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div>
            <label class="flabel">שם בתפריט <span style="color:var(--danger)">*</span></label>
            <input id="f-label" type="text" class="finput">
          </div>
          <div>
            <label class="flabel">אייקון <span style="font-size:10px;color:var(--text3);">bi-XXX</span></label>
            <div style="display:flex;gap:6px;">
              <input id="f-icon" type="text" class="finput" placeholder="bi-house" oninput="updateIconPreview()" style="flex:1;">
              <div style="width:38px;height:38px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;display:grid;place-items:center;font-size:18px;flex-shrink:0;">
                <i class="bi bi-circle" id="icon-i"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="mf-section" style="--mc:#10b981;">
        <div class="mf-title"><i class="bi bi-link-45deg"></i> קישור</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div>
            <label class="flabel">סוג פריט</label>
            <select id="f-type" class="finput" onchange="onTypeChange()">
              <option value="url">קישור רגיל</option>
              <option value="external">קישור חיצוני</option>
              <option value="jsfunction">פונקציה JS</option>
              <option value="parent">תפריט אב (ללא קישור)</option>
            </select>
          </div>
          <div id="f-link-wrap">
            <label class="flabel">כתובת</label>
            <input id="f-link" type="text" class="finput" placeholder="/tasks">
          </div>
        </div>
        <div style="display:flex;gap:20px;">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
            <input type="checkbox" id="f-blank" style="accent-color:var(--accent);"> פתח בטאב חדש
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
            <input type="checkbox" id="f-active" checked style="accent-color:var(--accent);"> פעיל
          </label>
        </div>
      </div>
      <div class="mf-section" style="--mc:#f59e0b;">
        <div class="mf-title"><i class="bi bi-diagram-3-fill"></i> מיקום</div>
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;">
          <div>
            <label class="flabel">תחת תפריט אב</label>
            <select id="f-parent" class="finput"><option value="">— רמה ראשית —</option></select>
          </div>
          <div>
            <label class="flabel">סדר הצגה</label>
            <input id="f-order" type="number" value="0" min="0" class="finput">
          </div>
        </div>
      </div>
      <div class="mf-section" style="--mc:#8b5cf6;">
        <div class="mf-title"><i class="bi bi-shield-lock-fill"></i> הרשאות <span style="font-size:11px;font-weight:400;color:var(--text3);">(ריק = לכולם)</span></div>
        <div id="f-perms" style="display:flex;flex-wrap:wrap;gap:6px;">
          <?php foreach ($permGroups as $g): ?>
            <label class="perm-label">
              <input type="checkbox" class="perm-cb" value="<?= (int)$g['id'] ?>">
              <span><?= View::e($g['permmisionsGroupHeb']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div id="modal-error" style="color:var(--danger);font-size:13px;margin-bottom:10px;display:none;padding:8px 12px;background:rgba(239,68,68,.1);border-radius:6px;"></div>
      <div style="display:flex;gap:10px;">
        <button class="btn btn-primary" style="flex:1;" onclick="saveItem()"><i class="bi bi-check-lg"></i> שמור</button>
        <button class="btn btn-ghost" onclick="closeModal()">ביטול</button>
        <button class="btn btn-danger" id="del-btn" style="display:none;" onclick="deleteItem()"><i class="bi bi-trash3"></i></button>
      </div>
    </div>
  </div>
</div>

<style>
.flabel{display:block;font-size:12px;color:var(--text2);margin-bottom:5px;font-weight:500}
.finput{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--text);font-size:14px;font-family:var(--font);outline:none}
.finput:focus{border-color:var(--accent)}
.mf-section{background:var(--bg3);border:1px solid var(--border);border-right:3px solid var(--mc,var(--accent));border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:12px}
.mf-title{font-size:11px;font-weight:700;color:var(--mc,var(--accent));text-transform:uppercase;letter-spacing:.07em;margin-bottom:12px;display:flex;align-items:center;gap:6px}
.perm-label{display:flex;align-items:center;gap:5px;cursor:pointer;background:var(--bg4);border:1px solid var(--border);border-radius:6px;padding:5px 10px;font-size:12px;transition:all .13s}
.perm-label:has(input:checked){background:var(--accent-dim);border-color:var(--accent);color:var(--accent)}
.legend-row{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2)}
.ldot{width:9px;height:9px;border-radius:50%;flex-shrink:0;display:inline-block}
.nm-section{margin-bottom:3px}
.nm-section-header{display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--bg3);border-bottom:1px solid var(--border);user-select:none}
.nm-section-header:hover{background:var(--bg4)}
.nm-children{border-bottom:1px solid var(--border)}
.nm-row{display:flex;align-items:center;gap:8px;padding:8px 14px 8px 32px;border-bottom:1px solid var(--border);font-size:13px;transition:background .1s}
.nm-row:last-child{border-bottom:none}
.nm-row:hover{background:var(--bg3)}
.nm-standalone{display:flex;align-items:center;gap:8px;padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px;background:var(--bg2)}
.nm-standalone:hover{background:var(--bg3)}
.nm-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.nm-arrows{display:flex;flex-direction:column;gap:1px;flex-shrink:0}
.nm-arr{background:none;border:none;padding:0 3px;cursor:pointer;color:var(--text3);font-size:13px;line-height:1.3;transition:color .1s,transform .1s;font-weight:700}
.nm-arr:hover{color:var(--accent);transform:scale(1.2)}
.nm-icon{font-size:16px;flex-shrink:0;width:20px;text-align:center}
.nm-name{flex:1;font-weight:600}
.nm-link{font-size:11px;color:var(--text3);max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;direction:ltr;text-align:right}
.nm-badge{font-size:10px;padding:2px 7px;border-radius:10px;font-weight:600;white-space:nowrap}
.nm-btn{background:var(--bg4);border:1px solid var(--border);border-radius:5px;padding:3px 9px;font-size:11px;cursor:pointer;color:var(--text2);transition:all .13s;font-family:var(--font)}
.nm-btn:hover{background:var(--accent-dim);color:var(--accent);border-color:var(--accent)}
.nm-btn.dng{color:var(--danger);border-color:rgba(239,68,68,.3)}
.nm-btn.dng:hover{background:rgba(239,68,68,.15)}
.nm-inactive{opacity:.45}
.nm-inactive .nm-name::after{content:' — מושבת';font-size:11px;color:var(--danger);font-weight:400}
/* Group preview */
.gp-section{padding:6px 0;border-bottom:1px solid var(--border);font-size:13px;}
.gp-child{padding:4px 0 4px 16px;font-size:12px;color:var(--text2);display:flex;align-items:center;gap:6px;}
</style>

<script>
let ITEMS = <?= $itemsJson ?>;
const GROUPS = <?= $permGroupsJson ?>;
const CSRF = '<?= View::e($csrf) ?>';
const BASE_URL = '<?= $base ?>';
let orderChanged = false;

/* ── Group filter helper ───────────────────────────── */
function matchGroup(item, gf) {
  if (!gf) return true;
  if (gf === '__all__') return !item.perm_groups || item.perm_groups.length === 0;
  return item.perm_groups && item.perm_groups.includes(parseInt(gf));
}

/* ── Build tree ────────────────────────────────────── */
function getTree(filter, showInactive) {
  const groupFilter = document.getElementById('group-filter')?.value || '';
  const childMap = {};
  ITEMS.forEach(i => {
    if (i.parent_id) (childMap[i.parent_id] = childMap[i.parent_id]||[]).push(i);
  });
  Object.values(childMap).forEach(arr => arr.sort((a,b)=>(a.ordering??0)-(b.ordering??0)));
  const parents = ITEMS.filter(i => !i.parent_id).sort((a,b)=>(a.ordering??0)-(b.ordering??0));
  const standalone = [], sections = [];

  for (const p of parents) {
    const kids = (childMap[p.id]||[]).filter(k => (showInactive||k.is_active) && matchGroup(k, groupFilter));
    const matchP = (!filter || p.label_he.toLowerCase().includes(filter)) && matchGroup(p, groupFilter);
    const matchKids = kids.filter(k => !filter || k.label_he.toLowerCase().includes(filter));
    if (!p.link || p.link_type === 'parent') {
      if ((matchP || matchKids.length) && (showInactive || p.is_active))
        sections.push({ parent: p, children: filter ? matchKids : kids });
    } else {
      if (matchP && (showInactive || p.is_active)) standalone.push(p);
    }
  }
  return { standalone, sections };
}

function renderTree() {
  const filter      = document.getElementById('nav-filter').value.toLowerCase().trim();
  const showInactive = document.getElementById('show-inactive').checked;
  const groupFilter  = document.getElementById('group-filter')?.value || '';
  const { standalone, sections } = getTree(filter, showInactive);
  const tree = document.getElementById('nav-tree');
  let html = '';
  for (const item of standalone) html += standaloneRow(item);
  for (const { parent, children } of sections) html += sectionBlock(parent, children);
  tree.innerHTML = html || '<div style="padding:20px;text-align:center;color:var(--text3);">אין פריטים</div>';

  // Group preview panel
  const previewCard  = document.getElementById('group-preview-card');
  const previewTitle = document.getElementById('group-preview-title');
  const previewList  = document.getElementById('group-preview-list');
  if (groupFilter && groupFilter !== '__all__') {
    const g = GROUPS.find(g => String(g.id) === String(groupFilter));
    previewCard.style.display = 'block';
    previewTitle.innerHTML = `<i class="bi bi-shield-lock"></i> נגיש לקבוצה: ${g?.permmisionsGroupHeb||groupFilter}`;
    const visible = ITEMS.filter(i => i.is_active && matchGroup(i, groupFilter));
    const parents = visible.filter(i => !i.parent_id);
    let ph = '';
    for (const p of parents.sort((a,b)=>(a.ordering??0)-(b.ordering??0))) {
      const dc = p.link ? 'var(--success)' : '#f59e0b';
      ph += `<div class="gp-section"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:${dc};margin-left:6px;"></span><strong>${esc(p.label_he)}</strong></div>`;
      const kids = visible.filter(i => String(i.parent_id) === String(p.id));
      for (const k of kids.sort((a,b)=>(a.ordering??0)-(b.ordering??0))) {
        ph += `<div class="gp-child"><i class="bi bi-arrow-return-left" style="font-size:11px;color:var(--text3);"></i>${esc(k.label_he)}${k.link?`<span style="font-size:10px;color:var(--text3);direction:ltr;">${esc(k.link)}</span>`:''}</div>`;
      }
    }
    previewList.innerHTML = ph || '<div style="color:var(--text3);font-size:12px;">אין פריטים נגישים</div>';
  } else if (groupFilter === '__all__') {
    previewCard.style.display = 'block';
    previewTitle.innerHTML = '<i class="bi bi-globe"></i> גלוי לכולם (ללא הגבלת קבוצה)';
    const all = ITEMS.filter(i => i.is_active && (!i.perm_groups || i.perm_groups.length === 0));
    previewList.innerHTML = all.map(i => `<div class="gp-section">${esc(i.label_he)}</div>`).join('') || '<div style="color:var(--text3);">אין</div>';
  } else {
    previewCard.style.display = 'none';
  }

  const active = ITEMS.filter(i=>i.is_active).length;
  document.getElementById('count-active').textContent = active;
  document.getElementById('count-total').textContent  = ITEMS.length;
}

function typeInfo(item) {
  if (!item.link || item.link_type==='parent') return {label:'קטגוריה', color:'#f59e0b'};
  if (item.link_type==='external'||item.link?.startsWith('http')) return {label:'חיצוני', color:'var(--accent)'};
  return {label:'route', color:'var(--success)'};
}
function dotColor(item) {
  if (!parseInt(item.is_active)) return 'var(--danger)';
  return typeInfo(item).color;
}

function standaloneRow(item) {
  const dc = dotColor(item); const ti = typeInfo(item);
  const active = !!parseInt(item.is_active);
  const inact = active ? '' : ' nm-inactive';
  return `<div class="nm-standalone${inact}">
    <div class="nm-arrows">
      <button class="nm-arr" onclick="moveItem(${item.id},-1)">↑</button>
      <button class="nm-arr" onclick="moveItem(${item.id},1)">↓</button>
    </div>
    <span class="nm-dot" style="background:${dc}"></span>
    <i class="bi ${item.icon||'bi-circle'} nm-icon" style="color:${dc}"></i>
    <span class="nm-name">${esc(item.label_he)}</span>
    <span class="nm-badge" style="background:rgba(255,255,255,.06);color:var(--text3);">${ti.label}</span>
    ${item.link?`<span class="nm-link">${esc(item.link)}</span>`:''}
    ${(item.open_in_blank||item.open_blank)?`<i class="bi " style="color:var(--text3);font-size:11px;" title="טאב חדש"></i>`:''}
    <button class="nm-btn" onclick="openModal(${item.id})"><i class="bi bi-pencil-fill"></i></button>
    <button class="nm-btn ${active?'':'dng'}" onclick="doToggle(${item.id})">${active?'השבת':'הפעל'}</button>
  </div>`;
}

function sectionBlock(parent, children) {
  const dc = dotColor(parent);
  const pActive = !!parseInt(parent.is_active);
  const inact = pActive ? '' : ' nm-inactive';
  let html = `<div class="nm-section">
    <div class="nm-section-header${inact}">
      <div class="nm-arrows">
        <button class="nm-arr" onclick="moveItem(${parent.id},-1)">↑</button>
        <button class="nm-arr" onclick="moveItem(${parent.id},1)">↓</button>
      </div>
      <span class="nm-dot" style="background:${dc}"></span>
      <i class="bi ${parent.icon||'bi-folder'} nm-icon" style="color:${dc}"></i>
      <span class="nm-name" style="font-size:14px;">${esc(parent.label_he)}</span>
      <span class="nm-badge" style="background:rgba(245,158,11,.15);color:#f59e0b;">קטגוריה · ${children.length} פריטים</span>
      <button class="nm-btn" onclick="openModal(${parent.id})" style="margin-right:auto;"><i class="bi bi-pencil-fill"></i></button>
      <button class="nm-btn ${pActive?'':'dng'}" onclick="doToggle(${parent.id})">${pActive?'השבת':'הפעל'}</button>
    </div>
    <div class="nm-children">`;
  for (const child of children) {
    const cdc = dotColor(child); const cti = typeInfo(child);
    const cActive = !!parseInt(child.is_active);
    const cinact = cActive ? '' : ' nm-inactive';
    html += `<div class="nm-row${cinact}">
      <div class="nm-arrows">
        <button class="nm-arr" onclick="moveItem(${child.id},-1)">↑</button>
        <button class="nm-arr" onclick="moveItem(${child.id},1)">↓</button>
      </div>
      <i class="bi bi-arrow-return-left" style="color:var(--text3);font-size:11px;flex-shrink:0;"></i>
      <span class="nm-dot" style="background:${cdc}"></span>
      <i class="bi ${child.icon||'bi-circle'} nm-icon" style="color:${cdc};font-size:15px;"></i>
      <span class="nm-name" style="font-weight:500;">${esc(child.label_he)}</span>
      <span class="nm-badge" style="background:rgba(255,255,255,.05);color:var(--text3);font-size:10px;">${cti.label}</span>
      ${child.link?`<span class="nm-link">${esc(child.link)}</span>`:''}
      ${(child.open_in_blank||child.open_blank)?`<i class="bi bi-box-arrow-up-right" style="color:var(--text3);font-size:11px;" title="טאב חדש"></i>`:''}
      <button class="nm-btn" onclick="openModal(${child.id})"><i class="bi bi-pencil-fill"></i></button>
      <button class="nm-btn ${cActive?'':'dng'}" onclick="doToggle(${child.id})">${cActive?'השבת':'הפעל'}</button>
    </div>`;
  }
  if (!children.length)
    html += `<div style="padding:10px 32px;font-size:12px;color:var(--text3);font-style:italic;">אין פריטים בקטגוריה זו</div>`;
  html += `</div></div>`;
  return html;
}

/* ── Reorder ───────────────────────────────────────── */
function moveItem(id, dir) {
  const item = ITEMS.find(i => parseInt(i.id) === parseInt(id));
  if (!item) return;
  const pid = item.parent_id ? String(item.parent_id) : '';
  const siblings = ITEMS.filter(i => String(i.parent_id||'') === pid)
                        .sort((a,b) => (a.ordering??0)-(b.ordering??0));
  const idx = siblings.findIndex(i => parseInt(i.id) === parseInt(id));
  const newIdx = idx + dir;
  if (newIdx < 0 || newIdx >= siblings.length) return;
  const a = siblings[idx], b = siblings[newIdx];
  const tmp = a.ordering ?? 0;
  a.ordering = b.ordering ?? tmp + dir;
  b.ordering = tmp;
  orderChanged = true;
  document.getElementById('reorder-btn').style.display = 'flex';
  renderTree();
}

async function saveOrder() {
  const body = new URLSearchParams({ _csrf: CSRF });
  // Normalize ordering per level then send id:ordering pairs
  ITEMS.filter(i=>!i.parent_id).sort((a,b)=>(a.ordering??0)-(b.ordering??0)).forEach((item,idx)=>{item.ordering=idx+1;});
  const cmap={};
  ITEMS.filter(i=>i.parent_id).forEach(i=>{const p=String(i.parent_id);(cmap[p]=cmap[p]||[]).push(i);});
  Object.values(cmap).forEach(arr=>{arr.sort((a,b)=>(a.ordering??0)-(b.ordering??0));arr.forEach((item,idx)=>{item.ordering=idx+1;});});
  ITEMS.forEach(i => body.append('pairs[]', `${i.id}:${i.ordering??0}`));
  const res  = await fetch(BASE_URL + '/nav-manager/reorder', { method:'POST', body });
  const data = await res.json();
  if (data.ok) {
    orderChanged = false;
    document.getElementById('reorder-btn').style.display = 'none';
    if (typeof v2Toast === 'function') v2Toast('סדר נשמר ✓');
    renderTree();
  } else {
    if (typeof v2Toast === 'function') v2Toast('שגיאה בשמירת הסדר');
  }
}

/* ── Modal ─────────────────────────────────────────── */
function updateIconPreview() {
  document.getElementById('icon-i').className = 'bi ' + (document.getElementById('f-icon').value.trim() || 'bi-circle');
}
function populateParentSelect(excludeId) {
  const sel = document.getElementById('f-parent');
  sel.innerHTML = '<option value="">— רמה ראשית —</option>';
  ITEMS.filter(i => !i.parent_id && (!i.link || i.link_type==='parent') && parseInt(i.id) !== parseInt(excludeId))
       .sort((a,b)=>(a.ordering??0)-(b.ordering??0))
       .forEach(i => { const o=document.createElement('option'); o.value=i.id; o.textContent=i.label_he; sel.appendChild(o); });
}
function openModal(id) {
  const item = id !== null ? ITEMS.find(i => String(i.id) === String(id)) : null;
  document.getElementById('modal-title').textContent = item ? `עריכה: ${item.label_he}` : 'פריט חדש';
  document.getElementById('modal-error').style.display = 'none';
  document.getElementById('del-btn').style.display = item ? 'inline-flex' : 'none';
  document.getElementById('f-id').value     = item?.id    || '';
  document.getElementById('f-label').value  = item?.label_he || '';
  document.getElementById('f-icon').value   = item?.icon  || '';
  document.getElementById('f-link').value   = item?.link  || '';
  document.getElementById('f-order').value  = item?.ordering ?? 0;
  document.getElementById('f-active').checked = item ? !!parseInt(item.is_active) : true;
  document.getElementById('f-blank').checked  = item ? !!(item.open_in_blank||item.open_blank) : false;
  let lt = item?.link_type || 'url';
  if (item && !item.link) lt = 'parent';
  else if (item?.link?.startsWith('http') && lt !== 'jsfunction') lt = 'external';
  document.getElementById('f-type').value = lt;
  onTypeChange();
  populateParentSelect(item?.id);
  document.getElementById('f-parent').value = item?.parent_id || '';
  document.querySelectorAll('.perm-cb').forEach(cb => {
    cb.checked = (item?.perm_groups||[]).includes(parseInt(cb.value));
  });
  updateIconPreview();
  document.getElementById('edit-modal').style.display = 'flex';
  setTimeout(() => document.getElementById('f-label').focus(), 50);
}
function closeModal() { document.getElementById('edit-modal').style.display = 'none'; }
function onTypeChange() {
  const type = document.getElementById('f-type').value;
  const wrap  = document.getElementById('f-link-wrap');
  const input = document.getElementById('f-link');
  wrap.style.display = type === 'parent' ? 'none' : 'block';
  if (type === 'jsfunction') {
    input.placeholder = 'openFormatterModal';
    wrap.querySelector('.flabel').textContent = 'שם פונקציה (JS)';
  } else if (type === 'external') {
    input.placeholder = 'https://...';
    wrap.querySelector('.flabel').textContent = 'כתובת';
  } else {
    input.placeholder = '/tasks';
    wrap.querySelector('.flabel').textContent = 'כתובת';
  }
}
async function saveItem() {
  const label = document.getElementById('f-label').value.trim();
  if (!label) { showErr('שם הפריט חובה'); return; }
  const type  = document.getElementById('f-type').value;
  const perms = [...document.querySelectorAll('.perm-cb:checked')].map(c => c.value);
  // link_type: parent → 'url' (ללא קישור), אחרת שולח את הערך הנבחר (url/external/jsfunction)
  const linkType = type === 'parent' ? 'url' : type === 'external' ? 'url' : type;
  const body  = new URLSearchParams({
    _csrf: CSRF, id: document.getElementById('f-id').value,
    label_he: label, name_heb: label, icon: document.getElementById('f-icon').value.trim(),
    link: type === 'parent' ? '' : document.getElementById('f-link').value.trim(),
    link_type: linkType,
    parent_id: document.getElementById('f-parent').value,
    ordering:  document.getElementById('f-order').value,
    is_active: document.getElementById('f-active').checked ? '1' : '0',
    open_in_blank: document.getElementById('f-blank').checked ? '1' : '0',
    open_blank:    document.getElementById('f-blank').checked ? '1' : '0',
  });
  perms.forEach(p => body.append('perm_groups[]', p));
  const res  = await fetch(BASE_URL + '/nav-manager/save', { method:'POST', body });
  const data = await res.json();
  if (data.ok) { closeModal(); location.reload(); }
  else showErr(data.error || 'שגיאה');
}
async function doToggle(id) {
  const res  = await fetch(BASE_URL + '/nav-manager/toggle', { method:'POST', body: new URLSearchParams({ _csrf:CSRF, id }) });
  const data = await res.json();
  if (data.ok) {
    const item = ITEMS.find(i => String(i.id) === String(id));
    if (item) {
      item.is_active = data.is_active ? 1 : 0;
      if (typeof v2Toast === 'function') v2Toast(item.label_he + (item.is_active ? ' — הופעל ✓' : ' — הושבת'));
    }
    renderTree();
  }
}
async function deleteItem() {
  if (!confirm('למחוק פריט זה לצמיתות?')) return;
  const id  = document.getElementById('f-id').value;
  const res = await fetch(BASE_URL + '/nav-manager/save', { method:'POST', body: new URLSearchParams({ _csrf:CSRF, id, _delete:'1' }) });
  const data = await res.json();
  if (data.ok) { closeModal(); location.reload(); }
  else showErr(data.error || 'שגיאה');
}
function showErr(msg) { const el=document.getElementById('modal-error'); el.textContent=msg; el.style.display='block'; }
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// Populate group filter select
const gfSel = document.getElementById('group-filter');
if (gfSel) {
  GROUPS.forEach(g => {
    const opt = document.createElement('option');
    opt.value = g.id;
    opt.textContent = g.permmisionsGroupHeb || g.name_heb || 'קבוצה ' + g.id;
    gfSel.appendChild(opt);
  });
}

document.addEventListener('keydown', e => { if (e.key==='Escape') closeModal(); });
document.getElementById('edit-modal').addEventListener('click', e => { if (e.target===document.getElementById('edit-modal')) closeModal(); });
renderTree();
</script>
