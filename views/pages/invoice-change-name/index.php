<?php
use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.3/xlsx.full.min.js"></script>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <div class="page-title">
      <i class="bi bi-receipt-cutoff" style="color:var(--accent);"></i> שינוי שם בחשבונית
    </div>
    <div style="font-size:13px;color:var(--text3);" id="icn-summary">טוען...</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <button type="button" class="btn btn-ghost" onclick="icnOpenMail()">
      <i class="bi bi-envelope-paper"></i> מייל לאילן
    </button>
    <button class="btn btn-ghost" onclick="icnLoad()">
      <i class="bi bi-arrow-clockwise"></i> רענן
    </button>
    <button class="btn btn-ghost" id="icn-excel-btn" onclick="icnExportExcel()" style="display:none;">
      <i class="bi bi-file-earmark-excel"></i> ייצוא לאקסל
    </button>
  </div>
</div>

<div id="icn-root"></div>

<style>
.icn-section { margin-bottom: 24px; }
.icn-section-hdr {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 18px; font-weight: 800; font-size: 15px;
  border-bottom: 1px solid var(--border);
  border-right: 4px solid var(--sec, var(--accent));
  background: linear-gradient(270deg, color-mix(in srgb, var(--sec, var(--accent)) 14%, transparent), transparent 70%);
}
.icn-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--sec); box-shadow: 0 0 0 4px color-mix(in srgb, var(--sec) 22%, transparent); }
.icn-count {
  min-width: 26px; padding: 1px 9px; border-radius: 999px; text-align: center;
  font-size: 13px; font-weight: 800; color: var(--sec);
  background: color-mix(in srgb, var(--sec) 16%, transparent);
}
.icn-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 15px; }
.icn-th {
  padding: 11px 14px; text-align: right; font-weight: 700; font-size: 12px;
  letter-spacing: .3px; color: var(--text3); background: var(--bg3);
  border-bottom: 1px solid var(--border); white-space: nowrap;
}
.icn-td { padding: 13px 14px; vertical-align: middle; border-bottom: 1px solid var(--border); transition: background .12s; }
.icn-tr:last-child .icn-td { border-bottom: 0; }
.icn-tr:hover .icn-td { background: rgba(255,255,255,.035); }
.icn-tr.is-sel .icn-td { background: color-mix(in srgb, var(--accent) 12%, transparent); }
.icn-tr.is-sel .icn-td:first-child { box-shadow: inset -3px 0 0 var(--accent); }
.icn-chk { width: 18px; height: 18px; accent-color: var(--accent); cursor: pointer; }
.icn-chk-td { width: 44px; text-align: center; }
.icn-inv {
  font-family: ui-monospace, Consolas, monospace; font-weight: 800; font-size: 16px;
  letter-spacing: .5px; padding: 3px 10px; border-radius: 8px; background: var(--bg3);
  border: 1px solid var(--border); display: inline-block;
}
.icn-newname { font-weight: 700; font-size: 16px; }
.icn-note { color: var(--text2); max-width: 260px; }
.icn-editable { cursor: pointer; }
.icn-editable::after { content: '\F4CB'; font-family: 'bootstrap-icons'; font-size: 11px; margin-right: 6px; opacity: 0; color: var(--accent); transition: opacity .12s; }
.icn-editable:hover::after { opacity: 1; }
.icn-editable:hover { color: var(--accent); }
.icn-who { display: flex; align-items: center; gap: 8px; white-space: nowrap; }
.icn-av {
  width: 28px; height: 28px; border-radius: 50%; flex: none; display: grid; place-items: center;
  font-size: 12px; font-weight: 800; color: #fff; background: var(--accent);
}
.icn-time { font-size: 13px; color: var(--text3); white-space: nowrap; line-height: 1.4; }
.icn-time b { display: block; font-size: 14px; color: var(--text2); font-weight: 600; }
.icn-status-sel, .icn-handler-sel {
  background: var(--bg3); border: 1px solid var(--border); border-radius: 8px;
  padding: 7px 10px; color: var(--text); font-size: 13px; font-family: var(--font);
  outline: none; cursor: pointer; transition: border-color .12s, box-shadow .12s;
}
.icn-handler-sel { min-width: 140px; }
.icn-status-sel:focus, .icn-handler-sel:focus { border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 22%, transparent); }
.icn-status-sel { font-weight: 700; }
.icn-badge {
  display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px;
  font-size: 12px; font-weight: 800; white-space: nowrap;
  color: var(--c); background: color-mix(in srgb, var(--c) 16%, transparent);
  border: 1px solid color-mix(in srgb, var(--c) 45%, transparent);
}
.icn-badge::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--c); }
.icn-empty { padding: 36px 20px; text-align: center; color: var(--text3); font-size: 14px; }
.icn-empty i { display: block; font-size: 28px; margin-bottom: 6px; opacity: .5; }

/* סרגל עדכון גורף */
.icn-bulk {
  position: sticky; top: 10px; z-index: 30; display: none; align-items: center; gap: 12px; flex-wrap: wrap;
  margin-bottom: 18px; padding: 12px 16px; border-radius: 14px;
  background: var(--bg2, var(--bg3)); border: 1px solid var(--accent);
  box-shadow: 0 10px 30px rgba(0,0,0,.35), 0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent);
}
.icn-bulk.show { display: flex; animation: icnPop .18s ease-out; }
@keyframes icnPop { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: none; } }
.icn-bulk-count { font-weight: 800; font-size: 15px; display: flex; align-items: center; gap: 8px; }
.icn-bulk-count span { background: var(--accent); color: #fff; border-radius: 999px; padding: 1px 11px; }
.icn-bulk-fields { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-right: auto; }
.icn-bulk-hint { flex-basis: 100%; font-size: 12px; color: var(--text3); }
.icn-bulk-hint:empty { display: none; }
.icn-link-btn { background: none; border: 0; color: var(--text3); cursor: pointer; font-family: var(--font); font-size: 13px; }
.icn-link-btn:hover { color: var(--text); }
</style>

<script>
const _ICN_BASE  = '<?= $base ?>';
const _ICN_CSRF  = '<?= htmlspecialchars($csrf) ?>';
let _icnData = { rows: [], users: [] };

const STATUS_OPTIONS = ['פתוחה','בהמתנה','תקלה בפרטים','טופלה + מייל','סגורה'];
const STATUS_COLOR   = {
  'פתוחה':         '#f5b301',
  'בהמתנה':        '#fb8c00',
  'תקלה בפרטים':   '#ef4478',
  'טופלה + מייל':  '#34c38f',
  'סגורה':         '#4caf50',
};
const _icnSel = new Set();

// כפתור ולא <a href="mailto:"> — ה-layout מעתיק ללוח כל קישור mailto (פתיחה רק עם Alt)
function icnOpenMail() {
  const n = new Date();
  const d = String(n.getDate()).padStart(2,'0');
  const m = String(n.getMonth()+1).padStart(2,'0');
  const subject = `שינוי שם בחשבונית - ${d}/${m}/${n.getFullYear()}`;
  window.location.href = 'mailto:ilan@bug.co.il?subject=' + encodeURIComponent(subject);
}

async function icnLoad() {
  document.getElementById('icn-root').innerHTML =
    '<div style="padding:40px;text-align:center;color:var(--text3);">טוען...</div>';
  try {
    const r = await fetch(_ICN_BASE + '/api/invoice-change-name');
    _icnData = await r.json();
    _icnSel.clear();
    icnRender();
  } catch(e) {
    document.getElementById('icn-root').innerHTML =
      '<div style="padding:20px;color:var(--danger);">שגיאה בטעינה</div>';
  }
}

function icnRender() {
  const open    = _icnData.rows.filter(r => r.status === 'פתוחה');
  const waiting = _icnData.rows.filter(r => r.status === 'בהמתנה' || r.status === 'תקלה בפרטים');
  const done    = _icnData.rows.filter(r => r.status === 'טופלה + מייל' || r.status === 'סגורה');

  document.getElementById('icn-summary').textContent =
    `פתוחות: ${open.length} | בהמתנה: ${waiting.length} | טופלו: ${done.length}`;

  const showExcel = _icnData.rows.length > 0;
  document.getElementById('icn-excel-btn').style.display = showExcel ? 'inline-flex' : 'none';

  let html = icnBulkBar();
  html += icnSection('פניות פתוחות', open,    '#f5b301', true);
  html += icnSection('בהמתנה / תקלה', waiting, '#fb8c00', true);
  html += icnSectionCollapsible('טופלו / סגורות', done, '#34c38f');

  document.getElementById('icn-root').innerHTML = html;
  icnSyncSelection();
}

function icnBulkBar() {
  const statuses = STATUS_OPTIONS.map(s => `<option value="${_ife(s)}">${_ife(s)}</option>`).join('');
  const users = (_icnData.users||[]).map(u => `<option value="${_ife(u.name)}">${_ife(u.name)}</option>`).join('');
  return `
    <div class="icn-bulk" id="icn-bulk">
      <div class="icn-bulk-count"><i class="bi bi-check2-square"></i> נבחרו <span id="icn-bulk-n">0</span></div>
      <div class="icn-bulk-fields">
        <select class="icn-status-sel" id="icn-bulk-status" onchange="icnBulkChanged()">
          <option value="">עדכון סטטוס ל...</option>${statuses}
        </select>
        <select class="icn-handler-sel" id="icn-bulk-handler">
          <option value="">— לטיפול ע"י (ללא שינוי) —</option>${users}
        </select>
        <button class="btn btn-primary" id="icn-bulk-apply" onclick="icnBulkApply()">
          <i class="bi bi-lightning-charge-fill"></i> החל על הנבחרות
        </button>
        <button class="icn-link-btn" onclick="icnClearSelection()">נקה בחירה</button>
      </div>
      <div class="icn-bulk-hint" id="icn-bulk-hint"></div>
    </div>`;
}

function icnBulkChanged() {
  const st = document.getElementById('icn-bulk-status').value;
  document.getElementById('icn-bulk-hint').textContent =
    st === 'טופלה + מייל' ? 'יישלח מייל לפותח כל בקשה, עם "לטיפול ע"י" שנבחר' : '';
}

function icnSection(title, rows, color, editable) {
  return `
    <div class="icn-section card" style="padding:0;overflow:hidden;--sec:${color};">
      <div class="icn-section-hdr">
        <span class="icn-dot"></span>
        ${_ife(title)} <span class="icn-count">${rows.length}</span>
      </div>
      ${rows.length === 0
        ? `<div class="icn-empty"><i class="bi bi-inbox"></i>אין רשומות</div>`
        : `<div style="overflow-x:auto;">${icnTable(rows, editable)}</div>`}
    </div>`;
}

function icnSectionCollapsible(title, rows, color) {
  return `
    <div class="icn-section card" style="padding:0;overflow:hidden;--sec:${color};">
      <div class="icn-section-hdr" style="cursor:pointer;"
           onclick="const b=document.getElementById('icn-done-body');b.style.display=b.style.display==='none'?'block':'none';">
        <span class="icn-dot"></span>
        ${_ife(title)} <span class="icn-count">${rows.length}</span>
        <i class="bi bi-chevron-down" style="margin-right:auto;font-size:13px;color:var(--text3);"></i>
      </div>
      <div id="icn-done-body" style="display:none;">
        ${rows.length === 0
          ? `<div class="icn-empty"><i class="bi bi-inbox"></i>אין רשומות</div>`
          : `<div style="overflow-x:auto;">${icnTable(rows, false)}</div>`}
      </div>
    </div>`;
}

function icnFmtTime(v) {
  if (!v) return '';
  const d = new Date(String(v).replace(' ', 'T'));
  if (isNaN(d)) return _ife(v);
  return `<b>${d.toLocaleDateString('he-IL')}</b>${d.toLocaleTimeString('he-IL', {hour:'2-digit', minute:'2-digit'})}`;
}

function icnTable(rows, editable) {
  let h = `<table class="icn-table">
    <thead><tr>
      ${editable ? `<th class="icn-th icn-chk-td"><input type="checkbox" class="icn-chk" title="בחר הכל" onchange="icnToggleAll(this)"></th>` : ''}
      <th class="icn-th">נפתח</th>
      <th class="icn-th">נפתח ע"י</th>
      <th class="icn-th">חשבונית סאפ</th>
      <th class="icn-th">שם חדש</th>
      <th class="icn-th">הערה</th>
      <th class="icn-th">סטטוס</th>
      <th class="icn-th">עודכן</th>
      <th class="icn-th">לטיפול ע"י</th>
    </tr></thead><tbody>`;

  rows.forEach(row => {
    const c = STATUS_COLOR[row.status] || 'var(--text3)';
    const badge = `<span class="icn-badge" style="--c:${c};">${_ife(row.status)}</span>`;

    const nameTd = editable
      ? `<td class="icn-td icn-editable icn-newname" onclick="icnEditField(${row.id},'new_name',this)">${_ife(row.new_name)}</td>`
      : `<td class="icn-td icn-newname">${_ife(row.new_name)}</td>`;

    const noteTd = editable
      ? `<td class="icn-td icn-editable icn-note" onclick="icnEditField(${row.id},'invoice_note',this)">${_ife(row.invoice_note||'—')}</td>`
      : `<td class="icn-td icn-note">${_ife(row.invoice_note||'—')}</td>`;

    const statusTd = editable
      ? `<td class="icn-td">${icnStatusSelect(row)}</td>`
      : `<td class="icn-td">${badge}</td>`;

    const handlerTd = editable
      ? `<td class="icn-td">${icnHandlerSelect(row)}</td>`
      : `<td class="icn-td">${_ife(row.care_by||'—')}</td>`;

    const opener = row.open_by_name || '';
    h += `<tr class="icn-tr" data-id="${row.id}">
      ${editable ? `<td class="icn-td icn-chk-td"><input type="checkbox" class="icn-chk icn-row-chk" value="${row.id}" onchange="icnToggleRow(this)"></td>` : ''}
      <td class="icn-td icn-time">${icnFmtTime(row.time_added)}</td>
      <td class="icn-td"><div class="icn-who"><span class="icn-av">${_ife(opener.trim().charAt(0))}</span>${_ife(opener)}</div></td>
      <td class="icn-td"><span class="icn-inv">${_ife(row.invoice_sap_number)}</span></td>
      ${nameTd}
      ${noteTd}
      ${statusTd}
      <td class="icn-td icn-time">${icnFmtTime(row.time_change_status)}</td>
      ${handlerTd}
    </tr>`;
  });

  h += '</tbody></table>';
  return h;
}

// ── בחירה מרובה ─────────────────────────────────────────────
function icnToggleRow(cb) {
  cb.checked ? _icnSel.add(cb.value) : _icnSel.delete(cb.value);
  icnSyncSelection();
}

function icnToggleAll(master) {
  master.closest('table').querySelectorAll('.icn-row-chk').forEach(cb => {
    master.checked ? _icnSel.add(cb.value) : _icnSel.delete(cb.value);
  });
  icnSyncSelection();
}

function icnClearSelection() {
  _icnSel.clear();
  icnSyncSelection();
}

function icnSyncSelection() {
  document.querySelectorAll('.icn-row-chk').forEach(cb => {
    cb.checked = _icnSel.has(cb.value);
    cb.closest('tr').classList.toggle('is-sel', cb.checked);
  });
  document.querySelectorAll('.icn-table').forEach(t => {
    const boxes  = [...t.querySelectorAll('.icn-row-chk')];
    const master = t.querySelector('thead .icn-chk');
    if (!master || !boxes.length) return;
    const n = boxes.filter(b => b.checked).length;
    master.checked = n === boxes.length;
    master.indeterminate = n > 0 && n < boxes.length;
  });
  document.getElementById('icn-bulk-n').textContent = _icnSel.size;
  document.getElementById('icn-bulk').classList.toggle('show', _icnSel.size > 0);
}

async function icnBulkApply() {
  const status = document.getElementById('icn-bulk-status').value;
  const careBy = document.getElementById('icn-bulk-handler').value;
  if (!status) { v2Toast('בחר סטטוס לעדכון'); return; }
  if (!_icnSel.size) return;

  const mailNote = status === 'טופלה + מייל' ? '\nיישלח מייל לפותח כל בקשה.' : '';
  if (!confirm(`לעדכן ${_icnSel.size} רשומות לסטטוס "${status}"?${mailNote}`)) return;

  const fd = new FormData();
  fd.append('_csrf', _ICN_CSRF);
  fd.append('status', status);
  fd.append('care_by', careBy);
  _icnSel.forEach(id => fd.append('ids[]', id));

  const btn = document.getElementById('icn-bulk-apply');
  btn.disabled = true;
  try {
    const r   = await fetch(_ICN_BASE + '/api/invoice-change-name/bulk-status', { method:'POST', body:fd });
    const res = await r.json();
    v2Toast(res.msg || (res.error ? 'שגיאה' : 'עודכן'));
    if (!res.error) icnLoad(); else btn.disabled = false;
  } catch(e) {
    v2Toast('שגיאת רשת');
    btn.disabled = false;
  }
}

function icnStatusSelect(row) {
  const c = STATUS_COLOR[row.status] || 'var(--border)';
  let h = `<select class="icn-status-sel" style="border-color:${c};color:${c};" onchange="icnUpdateStatus(${row.id},this)"
              data-row-id="${row.id}">`;
  STATUS_OPTIONS.forEach(s => {
    h += `<option value="${_ife(s)}" ${row.status===s?'selected':''}>${_ife(s)}</option>`;
  });
  h += '</select>';
  return h;
}

function icnHandlerSelect(row) {
  let h = `<select class="icn-handler-sel" id="icn-handler-${row.id}">
    <option value="">— בחר מטפל —</option>`;
  (_icnData.users||[]).forEach(u => {
    const sel = row.care_by === u.name ? 'selected' : '';
    h += `<option value="${_ife(u.name)}" ${sel}>${_ife(u.name)}</option>`;
  });
  h += '</select>';
  return h;
}

async function icnUpdateStatus(id, sel) {
  const status  = sel.value;
  const handler = document.getElementById('icn-handler-' + id);
  const careBy  = handler ? handler.value : '';

  const fd = new FormData();
  fd.append('_csrf', _ICN_CSRF);
  fd.append('status', status);
  fd.append('care_by', careBy);

  try {
    const r   = await fetch(_ICN_BASE + '/api/invoice-change-name/' + id + '/status', { method:'POST', body:fd });
    const res = await r.json();
    v2Toast(res.msg || (res.error ? 'שגיאה' : 'עודכן'));
    if (!res.error) icnLoad();
  } catch(e) {
    v2Toast('שגיאת רשת');
  }
}

async function icnEditField(id, field, cell) {
  const current = cell.textContent === '—' ? '' : cell.textContent;
  const val = prompt('עריכת שדה:', current);
  if (val === null) return;

  const fd = new FormData();
  fd.append('_csrf', _ICN_CSRF);
  fd.append('field', field);
  fd.append('value', val.trim());

  try {
    const r   = await fetch(_ICN_BASE + '/api/invoice-change-name/' + id + '/edit', { method:'POST', body:fd });
    const res = await r.json();
    v2Toast(res.msg || (res.error ? 'שגיאה' : 'עודכן'));
    if (!res.error) { cell.textContent = val.trim() || '—'; }
  } catch(e) {
    v2Toast('שגיאת רשת');
  }
}

function icnExportExcel() {
  const rows = _icnData.rows.filter(r => r.status === 'פתוחה');
  if (!rows.length) { v2Toast('אין שורות פתוחות לייצוא'); return; }
  const headers = ['חשבונית סאפ', 'שם חדש'];
  const data = [headers, ...rows.map(r => [r.invoice_sap_number, r.new_name])];
  const ws = XLSX.utils.aoa_to_sheet(data);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'שינוי שם');
  const now = new Date();
  const d = String(now.getDate()).padStart(2,'0');
  const m = String(now.getMonth()+1).padStart(2,'0');
  const y = now.getFullYear();
  XLSX.writeFile(wb, `${d}-${m}-${y} שינוי שם בחשבונית.xlsx`);
}

function _ife(s) {
  if (s === null || s === undefined) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

icnLoad();
</script>
