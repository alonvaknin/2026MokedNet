<?php
/** @var bool $canEdit */
/** @var bool $canAdd */
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <div class="page-title">
      <i class="bi bi-key-fill" style="color:var(--accent);"></i> סיסמאות מוקד
    </div>
    <div style="font-size:13px;color:var(--text3);" id="acc-summary">טוען...</div>
  </div>
  <button class="btn btn-ghost" onclick="accLoad()">
    <i class="bi bi-arrow-clockwise"></i> רענן
  </button>
</div>

<?php if ($canAdd): ?>
<div id="acc-add-card" class="card" style="margin-bottom:20px;padding:16px 20px;">
  <div style="font-size:13px;font-weight:700;color:var(--text2);margin-bottom:12px;">
    <i class="bi bi-plus-circle" style="color:var(--accent);margin-left:5px;"></i>הוסף חשבון
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:10px;align-items:end;">
    <div>
      <label class="form-label">שם APP / חשבון</label>
      <input type="text" id="acc-name" class="form-input" placeholder="שם APP\חשבון">
    </div>
    <div>
      <label class="form-label">הערות</label>
      <input type="text" id="acc-note" class="form-input" placeholder="הערות">
    </div>
    <div>
      <label class="form-label">משתמש</label>
      <input type="text" id="acc-user" class="form-input" placeholder="משתמש">
    </div>
    <div>
      <label class="form-label">סיסמא</label>
      <input type="text" id="acc-pass" class="form-input" placeholder="סיסמא">
    </div>
    <button class="btn btn-primary" id="acc-add-btn" onclick="accAdd()">
      <i class="bi bi-plus-lg"></i> הוסף
    </button>
  </div>
</div>
<?php endif; ?>

<!-- Search bar -->
<div style="margin-bottom:14px;">
  <div class="acc-search-wrap">
    <i class="bi bi-search" style="color:var(--text3);font-size:13px;flex-shrink:0;"></i>
    <input type="text" id="acc-search" class="acc-search-input" placeholder="חיפוש לפי שם, משתמש, הערות..." oninput="accFilter()">
    <button id="acc-search-clear" onclick="accClearSearch()" style="display:none;background:none;border:none;color:var(--text3);cursor:pointer;font-size:13px;padding:0;">✕</button>
  </div>
</div>

<div id="acc-root"></div>

<!-- Edit modal -->
<div id="acc-edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:500;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:480px;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);">
      <div style="font-size:15px;font-weight:700;">עריכת חשבון</div>
      <button onclick="accCloseEdit()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
    </div>
    <div style="padding:20px;display:grid;gap:12px;">
      <input type="hidden" id="edit-id">
      <div>
        <label class="form-label">שם APP / חשבון</label>
        <input type="text" id="edit-name" class="form-input">
      </div>
      <div>
        <label class="form-label">הערות</label>
        <input type="text" id="edit-note" class="form-input">
      </div>
      <div>
        <label class="form-label">משתמש</label>
        <input type="text" id="edit-user" class="form-input">
      </div>
      <div>
        <label class="form-label">סיסמא</label>
        <input type="text" id="edit-pass" class="form-input">
      </div>
    </div>
    <div style="display:flex;gap:10px;padding:0 20px 20px;">
      <button class="btn btn-primary" id="acc-save-btn" onclick="accSaveEdit()">
        <i class="bi bi-check-lg"></i> שמור
      </button>
      <button class="btn btn-ghost" onclick="accCloseEdit()">ביטול</button>
    </div>
  </div>
</div>

<style>
.form-label       { display:block; font-size:11px; color:var(--text3); margin-bottom:4px; font-weight:600; }
.form-input       { width:100%; background:var(--bg3); border:1px solid var(--border); border-radius:7px; padding:8px 10px; color:var(--text); font-size:13px; font-family:var(--font); outline:none; box-sizing:border-box; transition:border-color .15s; }
.form-input:focus { border-color:var(--accent); }
.acc-search-wrap  { display:flex;align-items:center;gap:8px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0 12px;max-width:360px;transition:border-color .15s; }
.acc-search-wrap:focus-within { border-color:var(--accent); }
.acc-search-input { background:none;border:none;outline:none;color:var(--text);font-family:var(--font);font-size:13px;padding:9px 0;width:100%; }
.acc-search-input::placeholder { color:var(--text3); }
.acc-table        { width:100%; border-collapse:collapse; font-size:13px; }
.acc-th           { padding:9px 12px; text-align:right; font-weight:600; font-size:11px; color:var(--text3); border-bottom:1px solid var(--border); background:var(--bg3); white-space:nowrap; }
.acc-td           { padding:9px 12px; vertical-align:middle; border-bottom:1px solid var(--border); }
.acc-tr           { transition:background .1s; }
.acc-tr:hover td  { background:rgba(255,255,255,.025); }
.acc-tr.acc-hidden{ display:none; }
.acc-pass         { font-family:monospace; direction:ltr; display:inline-block; }
.acc-copy         { cursor:pointer; color:var(--accent); transition:opacity .1s; }
.acc-copy:hover   { opacity:.7; text-decoration:underline; }
.acc-act-btn      { background:none;border:1px solid var(--border);border-radius:5px;padding:3px 8px;cursor:pointer;font-size:12px;color:var(--text2);transition:all .13s; }
.acc-act-btn:hover{ background:var(--accent-dim);color:var(--accent);border-color:var(--accent); }
.acc-act-btn.del:hover { background:rgba(239,68,68,.12);color:var(--danger);border-color:rgba(239,68,68,.35); }
mark { background:rgba(91,141,238,.25); color:var(--text); border-radius:2px; padding:0 1px; }
</style>

<script>
const _ACC_BASE     = '<?= $base ?>';
const _ACC_CSRF     = '<?= htmlspecialchars($csrf) ?>';
const _ACC_CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
const _ACC_CAN_ADD  = <?= $canAdd  ? 'true' : 'false' ?>;

let _accRows = [];

function accLoad() {
  document.getElementById('acc-root').innerHTML = '<div style="padding:30px;text-align:center;color:var(--text3);">טוען...</div>';
  fetch(_ACC_BASE + '/api/accounts')
    .then(r => r.json())
    .then(d => { _accRows = d.rows || []; accRender(d.canEdit, d.canAdd); })
    .catch(() => {
      document.getElementById('acc-root').innerHTML = '<div style="padding:30px;text-align:center;color:var(--danger);">שגיאה בטעינה</div>';
    });
}

function accRender(canEdit, canAdd) {
  canAdd = canAdd ?? _ACC_CAN_ADD;
  const q = document.getElementById('acc-search').value.trim().toLowerCase();
  document.getElementById('acc-summary').textContent = _accRows.length + ' סיסמאות';
  const addCard = document.getElementById('acc-add-card');
  if (addCard) addCard.style.display = canAdd ? '' : 'none';

  if (!_accRows.length) {
    document.getElementById('acc-root').innerHTML = '<div style="padding:40px;text-align:center;color:var(--text3);">אין סיסמאות</div>';
    return;
  }

  const fmtDate = v => v ? new Date(v).toLocaleDateString('he-IL',{day:'2-digit',month:'2-digit',year:'2-digit'}) : '—';

  const hi = s => {
    if (!q) return esc(s);
    const escaped = esc(s);
    const qEsc    = q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
    return escaped.replace(new RegExp(qEsc,'gi'), m => `<mark>${m}</mark>`);
  };

  let html = `<div class="card" style="padding:0;overflow:hidden;">
    <table class="acc-table">
      <thead><tr>
        <th class="acc-th">שם APP / חשבון</th>
        <th class="acc-th">הערות</th>
        <th class="acc-th">משתמש</th>
        <th class="acc-th">סיסמא</th>
        <th class="acc-th">נוסף על ידי</th>
        <th class="acc-th">עודכן על ידי</th>
        ${canEdit ? '<th class="acc-th"></th>' : ''}
      </tr></thead>
      <tbody>`;

  let visible = 0;
  _accRows.forEach(r => {
    const searchStr = [r.appName, r.appUser, r.appNote].join(' ').toLowerCase();
    const hidden    = q && !searchStr.includes(q);
    if (!hidden) visible++;

    const note = r.appNote && r.appNote.length > 60
      ? `<span title="${esc(r.appNote)}">${hi(r.appNote.substring(0,40))}…</span>`
      : hi(r.appNote || '');

    const createdCell = `<div style="font-size:12px;color:var(--text2);">${esc(r.created_by_name||'')}</div><div style="font-size:11px;color:var(--text3);">${fmtDate(r.created_at)}</div>`;
    const updatedCell = r.updated_by_name
      ? `<div style="font-size:12px;color:var(--text2);">${esc(r.updated_by_name)}</div><div style="font-size:11px;color:var(--text3);">${fmtDate(r.updated_at)}</div>`
      : '<span style="color:var(--text3);font-size:11px;">—</span>';

    const editData = JSON.stringify({id:r.id,appName:r.appName,appUser:r.appUser,appPass:r.appPass,appNote:r.appNote});

    html += `<tr class="acc-tr${hidden?' acc-hidden':''}">
      <td class="acc-td" style="font-weight:700;">${hi((r.appName||'').toUpperCase())}</td>
      <td class="acc-td" style="color:var(--text2);">${note}</td>
      <td class="acc-td">
        <span class="acc-copy" onclick="accCopy('${esc(r.appUser||'')}')" title="לחץ להעתקה">
          <i class="bi bi-person-fill" style="font-size:11px;margin-left:4px;opacity:.6;"></i>${hi(r.appUser||'')}
        </span>
      </td>
      <td class="acc-td">
        <span class="acc-copy acc-pass" onclick="accCopy('${esc(r.appPass||'')}')" title="לחץ להעתקה">${esc(r.appPass||'')}</span>
      </td>
      <td class="acc-td">${createdCell}</td>
      <td class="acc-td">${updatedCell}</td>
      ${canEdit ? `<td class="acc-td">
        <div style="display:flex;gap:5px;">
          <button class="acc-act-btn" onclick='accOpenEdit(${editData.replace(/'/g,"\\'")})'  title="עריכה"><i class="bi bi-pencil-fill"></i></button>
          <button class="acc-act-btn del" onclick="accDelete(${r.id|0})" title="מחיקה"><i class="bi bi-trash3"></i></button>
        </div>
      </td>` : ''}
    </tr>`;
  });

  html += '</tbody></table></div>';

  if (q && visible === 0) {
    html += '<div style="padding:30px;text-align:center;color:var(--text3);">לא נמצאו תוצאות</div>';
  }

  document.getElementById('acc-root').innerHTML = html;
}

function accFilter() {
  const q = document.getElementById('acc-search').value.trim();
  document.getElementById('acc-search-clear').style.display = q ? 'block' : 'none';
  accRender(_ACC_CAN_EDIT, _ACC_CAN_ADD);
}
function accClearSearch() {
  document.getElementById('acc-search').value = '';
  document.getElementById('acc-search-clear').style.display = 'none';
  accRender(_ACC_CAN_EDIT, _ACC_CAN_ADD);
}

function accAdd() {
  const name = document.getElementById('acc-name').value.trim();
  const note = document.getElementById('acc-note').value.trim();
  const user = document.getElementById('acc-user').value.trim();
  const pass = document.getElementById('acc-pass').value.trim();

  const errs = [];
  if (!name) errs.push('שם APP');
  if (!note) errs.push('הערות');
  if (!user) errs.push('משתמש');
  if (!pass) errs.push('סיסמא');
  if (errs.length) { accToast('נא למלא: ' + errs.join(', '), 'error'); return; }

  const btn = document.getElementById('acc-add-btn');
  btn.disabled = true;
  fetch(_ACC_BASE + '/api/accounts/create', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':_ACC_CSRF},
    body: new URLSearchParams({_csrf:_ACC_CSRF, appname:name, appnote:note, appuser:user, apppass:pass}),
  })
  .then(r => r.json())
  .then(d => {
    if (d.error) { accToast(d.msg, 'error'); return; }
    accToast(d.msg, 'success');
    ['acc-name','acc-note','acc-user','acc-pass'].forEach(id => document.getElementById(id).value = '');
    accLoad();
  })
  .catch(() => accToast('שגיאת שרת', 'error'))
  .finally(() => btn.disabled = false);
}

function accOpenEdit(r) {
  if (typeof r === 'string') r = JSON.parse(r);
  document.getElementById('edit-id').value   = r.id;
  document.getElementById('edit-name').value = r.appName;
  document.getElementById('edit-note').value = r.appNote;
  document.getElementById('edit-user').value = r.appUser;
  document.getElementById('edit-pass').value = r.appPass;
  document.getElementById('acc-edit-modal').style.display = 'flex';
  setTimeout(() => document.getElementById('edit-name').focus(), 50);
}
function accCloseEdit() {
  document.getElementById('acc-edit-modal').style.display = 'none';
}
function accSaveEdit() {
  const id   = document.getElementById('edit-id').value;
  const name = document.getElementById('edit-name').value.trim();
  const note = document.getElementById('edit-note').value.trim();
  const user = document.getElementById('edit-user').value.trim();
  const pass = document.getElementById('edit-pass').value.trim();

  const errs = [];
  if (!name) errs.push('שם APP');
  if (!note) errs.push('הערות');
  if (!user) errs.push('משתמש');
  if (!pass) errs.push('סיסמא');
  if (errs.length) { accToast('נא למלא: ' + errs.join(', '), 'error'); return; }

  const btn = document.getElementById('acc-save-btn');
  btn.disabled = true;
  fetch(_ACC_BASE + '/api/accounts/' + id + '/update', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':_ACC_CSRF},
    body: new URLSearchParams({_csrf:_ACC_CSRF, appname:name, appnote:note, appuser:user, apppass:pass}),
  })
  .then(r => r.json())
  .then(d => {
    if (d.error) { accToast(d.msg, 'error'); return; }
    accToast(d.msg, 'success');
    accCloseEdit();
    accLoad();
  })
  .catch(() => accToast('שגיאת שרת', 'error'))
  .finally(() => btn.disabled = false);
}

function accDelete(id) {
  if (!confirm('האם למחוק?')) return;
  if (!confirm('בטוח?')) return;
  fetch(_ACC_BASE + '/api/accounts/' + id + '/delete', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':_ACC_CSRF},
    body: new URLSearchParams({_csrf:_ACC_CSRF}),
  })
  .then(r => r.json())
  .then(d => {
    if (d.error) { accToast(d.msg, 'error'); return; }
    accToast(d.msg, 'success');
    accLoad();
  })
  .catch(() => accToast('שגיאת שרת', 'error'));
}

let _accToastTimer = null;
function accCopy(val) {
  navigator.clipboard.writeText(val).catch(() => {
    const ta = document.createElement('textarea'); ta.value = val;
    document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
  });
  accToast('הועתק: ' + val, 'success');
}

function accToast(msg, type) {
  const colors = {success:'#10b981', error:'var(--danger)', warning:'#f59e0b'};
  const t = document.createElement('div');
  t.style.cssText = `position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:10px 20px;font-size:13px;color:${colors[type]||'var(--text)'};box-shadow:0 8px 30px rgba(0,0,0,.4);z-index:9999;transition:opacity .25s;`;
  t.textContent = msg;
  document.body.appendChild(t);
  clearTimeout(_accToastTimer);
  _accToastTimer = setTimeout(() => { t.style.opacity='0'; setTimeout(() => t.remove(), 250); }, 2500);
}

function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('acc-edit-modal').addEventListener('click', e => {
  if (e.target === document.getElementById('acc-edit-modal')) accCloseEdit();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') accCloseEdit(); });

accLoad();
</script>
