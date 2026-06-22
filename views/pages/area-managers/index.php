<?php
/**
 * @var array[] $managers  List of area managers
 * @var bool    $canEdit   Whether the current user can edit
 */
use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
?>

<!-- ── Page header ── -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <div class="page-title" style="margin-bottom:4px;"><i class="bi bi-person-badge" style="margin-left:8px;"></i>מנהלי אזור</div>
    <div style="font-size:13px;color:var(--text3);"><?= count($managers) ?> מנהלים</div>
  </div>
  <?php if ($canEdit): ?>
  <button class="btn btn-primary" onclick="openAmEdit(null)">
    <i class="bi bi-person-plus-fill"></i> הוסף מנהל אזור
  </button>
  <?php endif; ?>
</div>

<!-- ── Two-panel layout ── -->
<div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">

  <!-- ── Left panel: manager list ── -->
  <div style="width:380px;flex-shrink:0;min-width:280px;">
    <div class="card" style="padding:0;overflow:hidden;max-height:calc(100vh - 120px);display:flex;flex-direction:column;">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border);background:var(--bg3);">
        <div style="font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px;">
          <i class="bi bi-person-badge" style="color:var(--accent);"></i>
          רשימת מנהלים
        </div>
        <span style="font-size:12px;color:var(--text3);"><?= count($managers) ?></span>
      </div>

      <div id="am-list" style="overflow-y:auto;flex:1;">
        <?php if (empty($managers)): ?>
          <div style="padding:40px;text-align:center;color:var(--text3);">
            <i class="bi bi-person-x" style="font-size:32px;display:block;margin-bottom:10px;opacity:.35;"></i>
            אין מנהלי אזור
          </div>
        <?php else: ?>
          <?php foreach ($managers as $m):
            $isActive  = (int)($m['is_active'] ?? 1) === 1;
            $srcLabel  = ($m['source_type'] ?? '') === 'user' ? 'משתמש מערכת' : 'איש קשר';
            $storeCnt  = (int)($m['store_count'] ?? 0);
          ?>
          <div class="mgr-row <?= $isActive ? '' : 'mgr-inactive' ?>"
               id="mgr-row-<?= (int)$m['id'] ?>"
               onclick="selectManager(<?= (int)$m['id'] ?>, <?= htmlspecialchars(json_encode($m, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES) ?>)">
            <div style="flex:1;min-width:0;">
              <div style="font-weight:700;font-size:14px;margin-bottom:4px;"><?= View::e($m['name'] ?? '') ?></div>
              <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;font-size:12px;color:var(--text3);">
                <?php if (!empty($m['phone'])): ?>
                  <span><i class="bi bi-telephone"></i> <?= View::e($m['phone']) ?></span>
                <?php endif; ?>
                <?php if (!empty($m['email'])): ?>
                  <span><i class="bi bi-envelope"></i> <?= View::e($m['email']) ?></span>
                <?php endif; ?>
              </div>
              <div style="display:flex;gap:6px;align-items:center;margin-top:5px;flex-wrap:wrap;">
                <span style="font-size:11px;background:var(--bg4);border:1px solid var(--border);border-radius:10px;padding:1px 8px;color:var(--text3);">
                  <?= $storeCnt ?> חנויות
                </span>
                <span style="font-size:11px;background:rgba(91,141,238,.1);border:1px solid rgba(91,141,238,.25);border-radius:10px;padding:1px 8px;color:var(--accent);">
                  <?= View::e($srcLabel) ?>
                </span>
                <?php if (!$isActive): ?>
                  <span style="font-size:11px;background:var(--bg4);border:1px solid var(--border);border-radius:10px;padding:1px 8px;color:var(--text3);">לא פעיל</span>
                <?php endif; ?>
              </div>
            </div>
            <?php if ($canEdit): ?>
            <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;" onclick="event.stopPropagation()">
              <button class="am-icon-btn" title="ערוך" onclick="openAmEdit(<?= htmlspecialchars(json_encode($m, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES) ?>)">
                <i class="bi bi-pencil-fill"></i>
              </button>
              <button class="am-icon-btn <?= $isActive ? 'am-btn-danger' : '' ?>"
                      title="<?= $isActive ? 'השבת' : 'הפעל' ?>"
                      onclick="doAmToggle(<?= (int)$m['id'] ?>)">
                <i class="bi bi-toggle-<?= $isActive ? 'on' : 'off' ?>"></i>
              </button>
              <button class="am-icon-btn am-btn-danger" title="מחק"
                      onclick="openAmDelete(<?= (int)$m['id'] ?>, <?= htmlspecialchars(json_encode($m['name'] ?? '', JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)">
                <i class="bi bi-trash3-fill"></i>
              </button>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── Right panel: drag-drop store assignment ── -->
  <div style="flex:1;min-width:300px;">
    <div id="am-stores-panel">
      <div class="card" style="padding:40px;text-align:center;color:var(--text3);">
        <i class="bi bi-arrow-right-circle" style="font-size:32px;display:block;margin-bottom:10px;opacity:.35;"></i>
        בחר מנהל כדי לנהל שיוך חנויות
      </div>
    </div>
  </div>
</div>

<!-- ── Add/Edit Manager Modal ── -->
<div id="am-edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:520px;max-height:92vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg2);z-index:1;">
      <div id="am-edit-title" style="font-size:16px;font-weight:700;"></div>
      <button onclick="closeAmEdit()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
    </div>
    <div style="padding:20px;">
      <input type="hidden" id="am-id">
      <input type="hidden" id="am-source-type">
      <input type="hidden" id="am-source-id">

      <!-- Autocomplete search -->
      <div style="margin-bottom:16px;position:relative;">
        <label class="am-label">חיפוש איש קשר / משתמש</label>
        <div style="position:relative;">
          <i class="bi bi-search" style="position:absolute;top:50%;right:10px;transform:translateY(-50%);color:var(--text3);font-size:13px;pointer-events:none;"></i>
          <input type="text" id="am-search-input" class="am-input" style="padding-right:32px;" placeholder="הקלד שם לחיפוש (לפחות 2 תווים)..."
                 oninput="amSearchDebounce()" autocomplete="off">
        </div>
        <div id="am-search-dropdown" style="display:none;position:absolute;top:100%;right:0;left:0;background:var(--bg2);border:1px solid var(--border);border-radius:8px;margin-top:4px;z-index:10;max-height:220px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.4);"></div>
      </div>

      <div class="am-mf-section">
        <div style="display:grid;grid-template-columns:1fr;gap:12px;">
          <div>
            <label class="am-label">שם תצוגה *</label>
            <input type="text" id="am-name" class="am-input">
          </div>
          <div>
            <label class="am-label">טלפון</label>
            <input type="text" id="am-phone" class="am-input" dir="ltr">
          </div>
          <div>
            <label class="am-label">מייל</label>
            <input type="text" id="am-email" class="am-input" dir="ltr">
          </div>
        </div>
      </div>

      <div id="am-edit-error" style="display:none;color:var(--danger);font-size:13px;margin-bottom:10px;padding:8px 12px;background:rgba(239,68,68,.1);border-radius:6px;"></div>

      <div style="display:flex;gap:10px;">
        <button class="btn btn-primary" style="flex:1;" onclick="saveAmManager()"><i class="bi bi-check-lg"></i> שמור</button>
        <button class="btn btn-ghost" onclick="closeAmEdit()">ביטול</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Manager Detail Modal (reused by dashboard Task 6) ── -->
<div id="am-detail-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:600;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:400px;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);">
      <div style="font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;">
        <i class="bi bi-person-badge" style="color:var(--accent);"></i>
        <span id="amd-name"></span>
      </div>
      <button onclick="document.getElementById('am-detail-modal').style.display='none'" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
    </div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:12px;">
      <div id="amd-src-badge" style="display:inline-flex;align-self:flex-start;"></div>
      <div id="amd-phone" style="display:none;">
        <a id="amd-phone-link" href="#" style="display:flex;align-items:center;gap:8px;font-size:15px;font-weight:600;color:var(--accent);text-decoration:none;background:var(--accent-dim);padding:8px 14px;border-radius:8px;border:1px solid rgba(91,141,238,.25);">
          <i class="bi bi-telephone-fill"></i>
          <span id="amd-phone-val"></span>
        </a>
      </div>
      <div id="amd-email" style="display:none;">
        <a id="amd-email-link" href="#" style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2);text-decoration:none;">
          <i class="bi bi-envelope-fill"></i>
          <span id="amd-email-val"></span>
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ── Delete Manager Modal ── -->
<div id="am-delete-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:550;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:480px;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);">
      <div style="font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;color:var(--danger);">
        <i class="bi bi-trash3-fill"></i> מחיקת מנהל אזור
      </div>
      <button onclick="closeAmDelete()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
    </div>
    <div style="padding:20px;">
      <div style="margin-bottom:16px;color:var(--text2);font-size:14px;">
        מחיקת <strong id="am-delete-name"></strong> — מה לעשות עם החנויות המשויכות?
      </div>
      <!-- Option A: reassign -->
      <div id="am-del-opt-reassign-wrap" style="border:1px solid var(--border);border-radius:8px;margin-bottom:8px;transition:border-color .13s;overflow:hidden;">
        <label style="display:flex;align-items:flex-start;gap:10px;padding:12px;cursor:pointer;">
          <input type="radio" name="am-del-opt" value="reassign" id="am-del-opt-reassign" style="margin-top:3px;flex-shrink:0;">
          <div>
            <div style="font-weight:600;font-size:13px;">העבר חנויות למנהל אחר</div>
            <div style="font-size:12px;color:var(--text3);margin-top:2px;">החנויות ישויכו למנהל אזור שתבחר</div>
          </div>
        </label>
        <div id="am-del-reassign-select-wrap" style="display:none;padding:0 12px 12px;">
          <select id="am-del-target" style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:7px 10px;color:var(--text);font-family:var(--font);font-size:13px;">
            <option value="">— בחר מנהל —</option>
          </select>
        </div>
      </div>
      <!-- Option B: remove -->
      <div id="am-del-opt-remove-wrap" style="border:1px solid var(--border);border-radius:8px;margin-bottom:16px;transition:border-color .13s;">
        <label style="display:flex;align-items:flex-start;gap:10px;padding:12px;cursor:pointer;">
          <input type="radio" name="am-del-opt" value="remove" id="am-del-opt-remove" style="margin-top:3px;flex-shrink:0;">
          <div>
            <div style="font-weight:600;font-size:13px;">הסר את השיוך</div>
            <div style="font-size:12px;color:var(--text3);margin-top:2px;">החנויות לא יהיו משויכות לאף מנהל</div>
          </div>
        </label>
      </div>
      <div id="am-delete-err" style="display:none;color:var(--danger);font-size:13px;margin-bottom:12px;"></div>
      <div style="display:flex;gap:8px;justify-content:flex-end;">
        <button class="btn btn-ghost" onclick="closeAmDelete()">ביטול</button>
        <button class="btn btn-danger" onclick="confirmAmDelete()"><i class="bi bi-trash3-fill"></i> מחק מנהל</button>
      </div>
    </div>
  </div>
</div>

<style>
/* ── Manager list ── */
.mgr-row{display:flex;align-items:center;gap:12px;padding:12px 16px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .13s;}
.mgr-row:hover{background:var(--bg3);}
.mgr-row.selected{background:var(--accent-dim);border-right:3px solid var(--accent);}
.mgr-inactive{opacity:.5;}
/* ── Drop zones ── */
.drop-zone{min-height:200px;max-height:calc(100vh - 260px);overflow-y:auto;border:1px solid var(--border);border-radius:0 0 8px 8px;padding:8px;display:flex;flex-direction:column;gap:6px;transition:background .15s;}
.drop-zone.drag-over{background:var(--accent-dim);border-color:var(--accent);}
/* ── Store chips ── */
.store-chip{display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;cursor:grab;font-size:13px;transition:background .13s,box-shadow .13s;}
.store-chip:hover{background:var(--bg2);box-shadow:0 2px 8px rgba(0,0,0,.2);}
.store-chip.dragging{opacity:.4;}
/* ── AM badge ── */
.am-badge{display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;background:rgba(91,141,238,.15);color:var(--accent);border:1px solid rgba(91,141,238,.3);cursor:pointer;margin:1px 2px;transition:background .13s;}
.am-badge:hover{background:rgba(91,141,238,.28);}
/* ── Form ── */
.am-label{display:block;font-size:12px;color:var(--text2);margin-bottom:5px;font-weight:500;}
.am-input{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--text);font-size:14px;font-family:var(--font);outline:none;transition:border-color .15s;box-sizing:border-box;}
.am-input:focus{border-color:var(--accent);}
.am-mf-section{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:14px;}
/* ── Icon buttons ── */
.am-icon-btn{background:var(--bg4);border:1px solid var(--border);border-radius:6px;padding:5px 7px;cursor:pointer;font-size:13px;color:var(--text3);transition:all .13s;display:flex;align-items:center;}
.am-icon-btn:hover{background:var(--accent-dim);color:var(--accent);border-color:rgba(91,141,238,.4);}
.am-btn-danger:hover{background:rgba(239,68,68,.1)!important;color:#ef4444!important;border-color:rgba(239,68,68,.3)!important;}
/* ── Autocomplete dropdown ── */
.am-ac-item{padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .1s;display:flex;flex-direction:column;gap:2px;}
.am-ac-item:last-child{border-bottom:none;}
.am-ac-item:hover{background:var(--bg3);}
.am-ac-item-name{font-size:14px;font-weight:600;color:var(--text);}
.am-ac-item-meta{font-size:11px;color:var(--text3);}
.am-ac-badge{display:inline-block;font-size:10px;padding:1px 6px;border-radius:8px;font-weight:600;margin-left:5px;}
/* ── Column header ── */
.am-col-hdr{background:var(--bg3);border:1px solid var(--border);border-radius:8px 8px 0 0;padding:10px 14px;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:space-between;}
@media(max-width:700px){
  [style*="width:380px"]{width:100%!important;}
}
</style>

<script>
const AM_BASE     = typeof BASE !== 'undefined' ? BASE : '<?= $base ?>';
const AM_CSRF     = '<?= View::e($csrf) ?>';
const AM_CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;

/* ── Global helpers (used by dashboard Task 6) ── */
function escHtml(s) {
  return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function showAmDetail(am) {
  document.getElementById('amd-name').textContent = am.name || '';
  const srcLabel = am.source_type === 'user' ? 'משתמש מערכת' : 'איש קשר';
  document.getElementById('amd-src-badge').innerHTML =
    `<span style="font-size:11px;background:rgba(91,141,238,.12);border:1px solid rgba(91,141,238,.3);border-radius:10px;padding:2px 10px;color:var(--accent);font-weight:600;">${escHtml(srcLabel)}</span>`;
  const phoneEl = document.getElementById('amd-phone');
  if (am.phone) {
    phoneEl.style.display = 'block';
    document.getElementById('amd-phone-link').href = 'tel:' + am.phone;
    document.getElementById('amd-phone-val').textContent = am.phone;
  } else {
    phoneEl.style.display = 'none';
  }
  const emailEl = document.getElementById('amd-email');
  if (am.email) {
    emailEl.style.display = 'block';
    document.getElementById('amd-email-link').href = 'mailto:' + am.email;
    document.getElementById('amd-email-val').textContent = am.email;
  } else {
    emailEl.style.display = 'none';
  }
  document.getElementById('am-detail-modal').style.display = 'flex';
}

document.getElementById('am-detail-modal').addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});

/* ── Manager list state ── */
let _amSelectedId = null;

function selectManager(id, data) {
  // Deselect previous
  if (_amSelectedId) {
    const prev = document.getElementById('mgr-row-' + _amSelectedId);
    if (prev) prev.classList.remove('selected');
  }
  _amSelectedId = id;
  const row = document.getElementById('mgr-row-' + id);
  if (row) row.classList.add('selected');
  loadStoresPanel(id, data.name || '');
}

/* ── Store drag-drop panel ── */
let _amDragStoreId  = null;
let _amDragAssigned = null;

function loadStoresPanel(mgrId, mgrName) {
  const panel = document.getElementById('am-stores-panel');
  panel.innerHTML = '<div class="card" style="padding:24px;text-align:center;color:var(--text3);"><i class="bi bi-hourglass-split" style="font-size:24px;display:block;margin-bottom:8px;"></i>טוען חנויות...</div>';

  fetch(AM_BASE + '/api/area-managers/' + mgrId + '/stores')
    .then(r => r.json())
    .then(stores => renderStoresPanel(mgrId, mgrName, stores))
    .catch(() => {
      panel.innerHTML = '<div class="card" style="padding:24px;text-align:center;color:var(--danger);">שגיאה בטעינת חנויות</div>';
    });
}

function renderStoresPanel(mgrId, mgrName, stores) {
  const unassigned = stores.filter(s => !s.is_assigned);
  const assigned   = stores.filter(s => s.is_assigned);

  const panel = document.getElementById('am-stores-panel');
  panel.innerHTML = `
    <div style="margin-bottom:12px;">
      <div style="font-size:14px;font-weight:700;color:var(--text2);">
        <i class="bi bi-shop" style="margin-left:6px;color:var(--accent);"></i>
        שיוך חנויות למנהל: <span style="color:var(--accent);">${escHtml(mgrName)}</span>
      </div>
      <div style="font-size:12px;color:var(--text3);margin-top:3px;">גרור חנות בין העמודות לשינוי שיוך</div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div>
        <div class="am-col-hdr">
          <span><i class="bi bi-shop" style="margin-left:5px;color:var(--text3);"></i>חנויות לא משויכות</span>
          <span id="am-unassigned-cnt" style="font-size:12px;color:var(--text3);">${unassigned.length}</span>
        </div>
        <div class="drop-zone" id="am-drop-unassigned"
             ondragover="amDragOver(event, false)"
             ondragleave="amDragLeave(event)"
             ondrop="amDrop(event, ${mgrId}, false)">
          ${unassigned.map(s => renderStoreChip(s, false)).join('') || '<div style="color:var(--text3);font-size:12px;text-align:center;padding:16px;opacity:.6;">אין חנויות</div>'}
        </div>
      </div>
      <div>
        <div class="am-col-hdr" style="border-color:rgba(91,141,238,.3);background:var(--accent-dim);">
          <span><i class="bi bi-shop-window" style="margin-left:5px;color:var(--accent);"></i>חנויות משויכות</span>
          <span id="am-assigned-cnt" style="font-size:12px;color:var(--accent);">${assigned.length}</span>
        </div>
        <div class="drop-zone" id="am-drop-assigned"
             style="border-color:rgba(91,141,238,.2);"
             ondragover="amDragOver(event, true)"
             ondragleave="amDragLeave(event)"
             ondrop="amDrop(event, ${mgrId}, true)">
          ${assigned.map(s => renderStoreChip(s, true)).join('') || '<div style="color:var(--text3);font-size:12px;text-align:center;padding:16px;opacity:.6;">אין חנויות משויכות</div>'}
        </div>
      </div>
    </div>`;
}

function renderStoreChip(s, assigned) {
  const others = s.other_managers || [];
  const warnHtml = others.length > 0
    ? `<span title="${escHtml('שייך גם ל: ' + others.map(o => o.name).join(', '))}" style="cursor:help;color:#f59e0b;font-size:14px;">🔔</span>`
    : '';
  return `<div class="store-chip"
              draggable="true"
              data-store-id="${escHtml(String(s.id))}"
              data-assigned="${assigned ? '1' : '0'}"
              ondragstart="amDragStart(event, ${s.id}, ${assigned ? 'true' : 'false'})"
              ondragend="amDragEnd(event)">
    <span style="font-weight:700;color:var(--accent);font-size:12px;flex-shrink:0;">#${escHtml(String(s.store_num))}</span>
    <span style="flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(s.name || '')}</span>
    ${s.city ? `<span style="font-size:11px;color:var(--text3);flex-shrink:0;">${escHtml(s.city)}</span>` : ''}
    ${warnHtml}
  </div>`;
}

function amDragStart(e, storeId, isAssigned) {
  _amDragStoreId  = storeId;
  _amDragAssigned = isAssigned;
  const chip = e.currentTarget;
  setTimeout(() => chip.classList.add('dragging'), 0);
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', String(storeId));
}

function amDragEnd(e) {
  e.currentTarget.classList.remove('dragging');
}

function amDragOver(e, targetIsAssigned) {
  // Only allow drop if moving to the opposite column
  if (_amDragAssigned === targetIsAssigned) return;
  e.preventDefault();
  e.currentTarget.classList.add('drag-over');
}

function amDragLeave(e) {
  e.currentTarget.classList.remove('drag-over');
}

async function amDrop(e, mgrId, targetIsAssigned) {
  e.preventDefault();
  e.currentTarget.classList.remove('drag-over');
  if (_amDragStoreId === null || _amDragAssigned === targetIsAssigned) return;

  const storeId = _amDragStoreId;
  const action  = targetIsAssigned ? 'assign' : 'unassign';
  _amDragStoreId  = null;
  _amDragAssigned = null;

  try {
    const res = await fetch(AM_BASE + '/api/area-managers/' + mgrId + '/' + action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': AM_CSRF },
      body: 'store_id=' + encodeURIComponent(storeId)
    });
    const data = await res.json();
    if (data.ok || res.ok) {
      v2Toast(targetIsAssigned ? 'החנות שויכה בהצלחה ✓' : 'השיוך הוסר בהצלחה ✓');
      const row = document.getElementById('mgr-row-' + mgrId);
      if (row) {
        const nameEl = row.querySelector('[style*="font-weight:700"]');
        loadStoresPanel(mgrId, nameEl ? nameEl.textContent : '');
      }
      refreshManagerList();
    }
  } catch (err) {
    console.error('amDrop error:', err);
  }
}

/* ── Refresh manager list ── */
async function refreshManagerList() {
  try {
    const res  = await fetch(AM_BASE + '/api/area-managers');
    const list = await res.json();
    if (!Array.isArray(list)) return;
    const container = document.getElementById('am-list');
    if (!container) return;

    if (!list.length) {
      container.innerHTML = `<div style="padding:40px;text-align:center;color:var(--text3);">
        <i class="bi bi-person-x" style="font-size:32px;display:block;margin-bottom:10px;opacity:.35;"></i>
        אין מנהלי אזור</div>`;
      return;
    }

    container.innerHTML = list.map(m => {
      const isActive = parseInt(m.is_active) === 1;
      const srcLabel = m.source_type === 'user' ? 'משתמש מערכת' : 'איש קשר';
      const cnt      = parseInt(m.store_count) || 0;
      const mJson    = escAttr(JSON.stringify(m));
      return `<div class="mgr-row ${isActive ? '' : 'mgr-inactive'}" id="mgr-row-${m.id}"
                   onclick="selectManager(${m.id}, ${mJson})">
        <div style="flex:1;min-width:0;">
          <div style="font-weight:700;font-size:14px;margin-bottom:4px;">${escHtml(m.name || '')}</div>
          <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;font-size:12px;color:var(--text3);">
            ${m.phone ? `<span><i class="bi bi-telephone"></i> ${escHtml(m.phone)}</span>` : ''}
            ${m.email ? `<span><i class="bi bi-envelope"></i> ${escHtml(m.email)}</span>` : ''}
          </div>
          <div style="display:flex;gap:6px;align-items:center;margin-top:5px;flex-wrap:wrap;">
            <span style="font-size:11px;background:var(--bg4);border:1px solid var(--border);border-radius:10px;padding:1px 8px;color:var(--text3);">${cnt} חנויות</span>
            <span style="font-size:11px;background:rgba(91,141,238,.1);border:1px solid rgba(91,141,238,.25);border-radius:10px;padding:1px 8px;color:var(--accent);">${escHtml(srcLabel)}</span>
            ${!isActive ? `<span style="font-size:11px;background:var(--bg4);border:1px solid var(--border);border-radius:10px;padding:1px 8px;color:var(--text3);">לא פעיל</span>` : ''}
          </div>
        </div>
        ${AM_CAN_EDIT ? `
        <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;" onclick="event.stopPropagation()">
          <button class="am-icon-btn" title="ערוך" onclick="openAmEdit(${mJson})">
            <i class="bi bi-pencil-fill"></i>
          </button>
          <button class="am-icon-btn ${isActive ? 'am-btn-danger' : ''}"
                  title="${isActive ? 'השבת' : 'הפעל'}"
                  onclick="doAmToggle(${m.id})">
            <i class="bi bi-toggle-${isActive ? 'on' : 'off'}"></i>
          </button>
          <button class="am-icon-btn am-btn-danger" title="מחק"
                  onclick="openAmDelete(${m.id}, '${escAttr(m.name||'')}')">
            <i class="bi bi-trash3-fill"></i>
          </button>
        </div>` : ''}
      </div>`;
    }).join('');
  } catch(e) {
    console.error('refreshManagerList', e);
  }
}

function escAttr(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* ── Add/Edit modal ── */
let _amSearchTimer = null;

function openAmEdit(m) {
  document.getElementById('am-id').value          = m ? m.id : '';
  document.getElementById('am-source-type').value = m ? (m.source_type || '') : '';
  document.getElementById('am-source-id').value   = m ? (m.source_id || '') : '';
  document.getElementById('am-name').value         = m ? (m.name || '') : '';
  document.getElementById('am-phone').value        = m ? (m.phone || '') : '';
  document.getElementById('am-email').value        = m ? (m.email || '') : '';
  document.getElementById('am-search-input').value = m ? (m.name || '') : '';
  document.getElementById('am-search-dropdown').style.display = 'none';
  document.getElementById('am-edit-error').style.display = 'none';
  document.getElementById('am-edit-title').textContent = m ? 'עריכת מנהל אזור' : 'הוסף מנהל אזור';
  document.getElementById('am-edit-modal').style.display = 'flex';
  setTimeout(() => document.getElementById('am-search-input').focus(), 50);
}

function closeAmEdit() {
  document.getElementById('am-edit-modal').style.display = 'none';
}

document.getElementById('am-edit-modal').addEventListener('click', function(e) {
  if (e.target === this) closeAmEdit();
});

function amSearchDebounce() {
  clearTimeout(_amSearchTimer);
  _amSearchTimer = setTimeout(amDoSearch, 220);
}

async function amDoSearch() {
  const q = document.getElementById('am-search-input').value.trim();
  const dropdown = document.getElementById('am-search-dropdown');
  if (q.length < 2) { dropdown.style.display = 'none'; return; }

  dropdown.innerHTML = '<div class="am-ac-item"><span class="am-ac-item-meta">מחפש...</span></div>';
  dropdown.style.display = 'block';

  try {
    const [resC, resU] = await Promise.all([
      fetch(AM_BASE + '/api/contacts?q=' + encodeURIComponent(q)),
      fetch(AM_BASE + '/api/users/search?q=' + encodeURIComponent(q))
    ]);

    const contacts = resC.ok ? await resC.json() : [];
    const users    = resU.ok ? await resU.json() : [];

    if (!contacts.length && !users.length) {
      dropdown.innerHTML = '<div class="am-ac-item"><span class="am-ac-item-meta">לא נמצאו תוצאות</span></div>';
      return;
    }

    let html = '';
    (contacts || []).forEach(c => {
      const name = [c.first_name, c.last_name].filter(Boolean).join(' ');
      const meta = [c.phone, c.email, c.role].filter(Boolean).join(' · ');
      html += `<div class="am-ac-item" onclick="amSelectResult('contact', ${escHtml(String(c.id))}, ${escHtml(JSON.stringify(name))}, ${escHtml(JSON.stringify(c.phone||''))}, ${escHtml(JSON.stringify(c.email||''))})">
        <div class="am-ac-item-name">${escHtml(name)} <span class="am-ac-badge" style="background:rgba(139,92,246,.12);color:#8b5cf6;border:1px solid rgba(139,92,246,.3);">איש קשר</span></div>
        ${meta ? `<div class="am-ac-item-meta">${escHtml(meta)}</div>` : ''}
      </div>`;
    });
    (users || []).forEach(u => {
      const name = [u.first_name, u.last_name].filter(Boolean).join(' ');
      const meta = [u.phone, u.email].filter(Boolean).join(' · ');
      html += `<div class="am-ac-item" onclick="amSelectResult('user', ${escHtml(String(u.id))}, ${escHtml(JSON.stringify(name))}, ${escHtml(JSON.stringify(u.phone||''))}, ${escHtml(JSON.stringify(u.email||''))})">
        <div class="am-ac-item-name">${escHtml(name)} <span class="am-ac-badge" style="background:rgba(91,141,238,.12);color:var(--accent);border:1px solid rgba(91,141,238,.3);">משתמש מערכת</span></div>
        ${meta ? `<div class="am-ac-item-meta">${escHtml(meta)}</div>` : ''}
      </div>`;
    });

    dropdown.innerHTML = html;
  } catch {
    dropdown.innerHTML = '<div class="am-ac-item"><span class="am-ac-item-meta" style="color:var(--danger);">שגיאה בחיפוש</span></div>';
  }
}

function amSelectResult(srcType, srcId, name, phone, email) {
  document.getElementById('am-source-type').value = srcType;
  document.getElementById('am-source-id').value   = srcId;
  document.getElementById('am-name').value         = name;
  document.getElementById('am-phone').value        = phone;
  document.getElementById('am-email').value        = email;
  document.getElementById('am-search-input').value = name;
  document.getElementById('am-search-dropdown').style.display = 'none';
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
  const dd = document.getElementById('am-search-dropdown');
  if (!dd) return;
  if (!dd.contains(e.target) && e.target.id !== 'am-search-input') {
    dd.style.display = 'none';
  }
});

async function saveAmManager() {
  const name = document.getElementById('am-name').value.trim();
  const errEl = document.getElementById('am-edit-error');
  if (!name) { errEl.textContent = 'שם תצוגה חובה'; errEl.style.display = 'block'; return; }
  errEl.style.display = 'none';

  const body = new URLSearchParams({
    _csrf:       AM_CSRF,
    id:          document.getElementById('am-id').value,
    name:        name,
    phone:       document.getElementById('am-phone').value.trim(),
    email:       document.getElementById('am-email').value.trim(),
    source_type: document.getElementById('am-source-type').value,
    source_id:   document.getElementById('am-source-id').value,
  });

  try {
    const res  = await fetch(AM_BASE + '/area-managers/save', {
      method: 'POST',
      headers: { 'X-CSRF-Token': AM_CSRF },
      body
    });
    const data = await res.json();
    if (data.ok) {
      const isNew = !document.getElementById('am-id').value;
      closeAmEdit();
      await refreshManagerList();
      v2Toast(isNew ? 'מנהל אזור נוסף בהצלחה ✓' : 'מנהל אזור עודכן בהצלחה ✓');
    }
    else { errEl.textContent = data.error || 'שגיאה בשמירה'; errEl.style.display = 'block'; }
  } catch {
    errEl.textContent = 'שגיאת רשת'; errEl.style.display = 'block';
  }
}

async function doAmToggle(id) {
  if (!confirm('לשנות סטטוס מנהל?')) return;
  try {
    const res  = await fetch(AM_BASE + '/area-managers/' + id + '/toggle', {
      method: 'POST',
      headers: { 'X-CSRF-Token': AM_CSRF },
      body: new URLSearchParams({ _csrf: AM_CSRF })
    });
    const data = await res.json();
    if (data.ok) location.reload();
  } catch {
    alert('שגיאה בשינוי סטטוס');
  }
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeAmEdit();
    closeAmDelete();
    document.getElementById('am-detail-modal').style.display = 'none';
  }
});

/* ── Delete manager ── */
let _amDeleteId = null;

function openAmDelete(id, name) {
  _amDeleteId = id;
  document.getElementById('am-delete-name').textContent = name;
  document.getElementById('am-delete-err').style.display = 'none';

  // Reset options
  document.getElementById('am-del-opt-remove').checked = true;
  document.getElementById('am-del-reassign-select-wrap').style.display = 'none';
  document.getElementById('am-del-opt-reassign-wrap').style.borderColor = 'var(--border)';
  document.getElementById('am-del-opt-remove-wrap').style.borderColor   = 'var(--border)';

  // Populate target select with other active managers
  fetch(AM_BASE + '/api/area-managers').then(r => r.json()).then(list => {
    const sel = document.getElementById('am-del-target');
    sel.innerHTML = '<option value="">— בחר מנהל —</option>';
    list.filter(m => m.id != id && parseInt(m.is_active) === 1).forEach(m => {
      const opt = document.createElement('option');
      opt.value = m.id;
      opt.textContent = m.name + (m.store_count > 0 ? ' (' + m.store_count + ' חנויות)' : '');
      sel.appendChild(opt);
    });
    // Hide reassign option if no other managers
    document.getElementById('am-del-opt-reassign-wrap').style.display =
      sel.options.length > 1 ? '' : 'none';
  }).catch(() => {});

  document.getElementById('am-delete-modal').style.display = 'flex';
}

// Show/hide select based on radio choice
document.addEventListener('change', function(e) {
  if (e.target.name !== 'am-del-opt') return;
  const isReassign = e.target.value === 'reassign';
  document.getElementById('am-del-reassign-select-wrap').style.display = isReassign ? '' : 'none';
  document.getElementById('am-del-opt-reassign-wrap').style.borderColor = isReassign ? 'var(--accent)' : 'var(--border)';
  document.getElementById('am-del-opt-remove-wrap').style.borderColor   = isReassign ? 'var(--border)' : 'var(--danger)';
});

function closeAmDelete() {
  document.getElementById('am-delete-modal').style.display = 'none';
  _amDeleteId = null;
}

async function confirmAmDelete() {
  if (!_amDeleteId) return;
  const errEl = document.getElementById('am-delete-err');
  errEl.style.display = 'none';

  const opt = document.querySelector('input[name="am-del-opt"]:checked')?.value;
  let transferTo = 0;

  if (opt === 'reassign') {
    transferTo = parseInt(document.getElementById('am-del-target').value) || 0;
    if (!transferTo) {
      errEl.textContent = 'יש לבחור מנהל להעברה';
      errEl.style.display = 'block';
      return;
    }
  }

  const body = new URLSearchParams({ _csrf: AM_CSRF });
  if (transferTo) body.set('transfer_to', transferTo);

  try {
    const res  = await fetch(AM_BASE + '/area-managers/' + _amDeleteId + '/delete', {
      method: 'POST',
      headers: { 'X-CSRF-Token': AM_CSRF },
      body,
    });
    const data = await res.json();
    if (data.ok) {
      closeAmDelete();
      await refreshManagerList();
      v2Toast('מנהל האזור נמחק ✓');
    } else {
      errEl.textContent = data.error || 'שגיאה במחיקה';
      errEl.style.display = 'block';
    }
  } catch {
    errEl.textContent = 'שגיאת רשת';
    errEl.style.display = 'block';
  }
}

</script>
