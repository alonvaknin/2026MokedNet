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
  display: flex; align-items: center; gap: 10px;
  padding: 10px 16px; border-radius: var(--radius) var(--radius) 0 0;
  font-weight: 700; font-size: 14px;
}
.icn-table { width: 100%; border-collapse: collapse; font-size: 18px; }
.icn-th { padding: 9px 12px; text-align: right; font-weight: 600; font-size: 11px;
          color: var(--text3); border-bottom: 1px solid var(--border);
          background: var(--bg3); white-space: nowrap; }
.icn-td { padding: 9px 12px; vertical-align: middle; border-bottom: 1px solid var(--border); }
.icn-tr:hover td { background: rgba(255,255,255,.025); }
.icn-editable { cursor: pointer; border-bottom: 1px dashed var(--accent); }
.icn-editable:hover { color: var(--accent); }
.icn-status-sel {
  background: var(--bg3); border: 1px solid var(--border); border-radius: 6px;
  padding: 4px 8px; color: var(--text); font-size: 12px; font-family: var(--font);
  cursor: pointer; outline: none;
}
.icn-handler-sel {
  background: var(--bg3); border: 1px solid var(--border); border-radius: 6px;
  padding: 4px 8px; color: var(--text); font-size: 12px; font-family: var(--font);
  min-width: 120px; outline: none;
}
.icn-badge {
  display: inline-block; padding: 2px 9px; border-radius: 12px;
  font-size: 11px; font-weight: 700;
}
</style>

<script>
const _ICN_BASE  = '<?= $base ?>';
const _ICN_CSRF  = '<?= htmlspecialchars($csrf) ?>';
let _icnData = { rows: [], users: [] };

const STATUS_OPTIONS = ['פתוחה','בהמתנה','תקלה בפרטים','טופלה + מייל','סגורה'];
const STATUS_COLOR   = {
  'פתוחה':         { bg: '#fffde7', text: '#795548', border: '#f9a825' },
  'בהמתנה':        { bg: '#fff3e0', text: '#e65100', border: '#fb8c00' },
  'תקלה בפרטים':   { bg: '#fce4ec', text: '#ad1457', border: '#e91e63' },
  'טופלה + מייל':  { bg: '#e8f5e9', text: '#2e7d32', border: '#43a047' },
  'סגורה':         { bg: '#e8f5e9', text: '#1b5e20', border: '#388e3c' },
};

async function icnLoad() {
  document.getElementById('icn-root').innerHTML =
    '<div style="padding:40px;text-align:center;color:var(--text3);">טוען...</div>';
  try {
    const r = await fetch(_ICN_BASE + '/api/invoice-change-name');
    _icnData = await r.json();
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

  let html = '';
  html += icnSection('פניות פתוחות', open,    '#fffde7', '#f9a825', true);
  html += icnSection('בהמתנה',        waiting, '#fff3e0', '#fb8c00', true);
  html += icnSectionCollapsible('טופלו / סגורות', done, '#e8f5e9', '#43a047');

  document.getElementById('icn-root').innerHTML = html;
}

function icnSection(title, rows, bg, border, editable) {
  return `
    <div class="icn-section card" style="padding:0;overflow:hidden;margin-bottom:20px;">
      <div class="icn-section-hdr" style="background:${bg};border-bottom:2px solid ${border};">
        <span style="color:${border};font-size:16px;">●</span>
        ${_ife(title)} <span style="font-size:18px;font-weight:400;color:var(--text3);">(${rows.length})</span>
      </div>
      ${rows.length === 0
        ? `<div style="padding:20px;text-align:center;color:var(--text3);font-size:13px;">אין רשומות</div>`
        : `<div style="overflow-x:auto;">${icnTable(rows, editable)}</div>`}
    </div>`;
}

function icnSectionCollapsible(title, rows, bg, border) {
  return `
    <div class="icn-section card" style="padding:0;overflow:hidden;">
      <div class="icn-section-hdr" style="background:${bg};border-bottom:2px solid ${border};cursor:pointer;"
           onclick="document.getElementById('icn-done-body').style.display=
             document.getElementById('icn-done-body').style.display==='none'?'block':'none';">
        <span style="color:${border};font-size:16px;">●</span>
        ${_ife(title)} <span style="font-size:18px;font-weight:400;color:var(--text3);">(${rows.length})</span>
        <i class="bi bi-chevron-down" style="margin-right:auto;font-size:12px;color:var(--text3);"></i>
      </div>
      <div id="icn-done-body" style="display:none;">
        ${rows.length === 0
          ? `<div style="padding:20px;text-align:center;color:var(--text3);font-size:13px;">אין רשומות</div>`
          : `<div style="overflow-x:auto;">${icnTable(rows, false)}</div>`}
      </div>
    </div>`;
}

function icnTable(rows, editable) {
  let h = `<table class="icn-table">
    <thead><tr>
      <th class="icn-th">זמן פתיחה</th>
      <th class="icn-th">נפתח ע"י</th>
      <th class="icn-th">חשבונית סאפ</th>
      <th class="icn-th">שם חדש</th>
      <th class="icn-th">הערה</th>
      <th class="icn-th">סטטוס</th>
      <th class="icn-th">זמן עדכון</th>
      <th class="icn-th">לטיפול ע"י</th>
    </tr></thead><tbody>`;

  rows.forEach(row => {
    const sc = STATUS_COLOR[row.status] || {};
    const badge = `<span class="icn-badge" style="background:${sc.bg||'var(--bg3)'};color:${sc.text||'var(--text2)'};border:1px solid ${sc.border||'var(--border)'};">${_ife(row.status)}</span>`;

    const nameTd = editable
      ? `<td class="icn-td icn-editable" onclick="icnEditField(${row.id},'new_name',this)">${_ife(row.new_name)}</td>`
      : `<td class="icn-td">${_ife(row.new_name)}</td>`;

    const noteTd = editable
      ? `<td class="icn-td icn-editable" onclick="icnEditField(${row.id},'invoice_note',this)">${_ife(row.invoice_note||'—')}</td>`
      : `<td class="icn-td">${_ife(row.invoice_note||'—')}</td>`;

    const statusTd = editable
      ? `<td class="icn-td">${icnStatusSelect(row)}</td>`
      : `<td class="icn-td">${badge}</td>`;

    const handlerTd = editable
      ? `<td class="icn-td">${icnHandlerSelect(row)}</td>`
      : `<td class="icn-td">${_ife(row.care_by||'—')}</td>`;
      row.time_added = row.time_added ? new Date(row.time_added).toLocaleString('he-IL') : '';
    h += `<tr class="icn-tr" data-id="${row.id}">
      <td class="icn-td" style="white-space:nowrap;font-size:17px;">${_ife(row.time_added)}</td>
      <td class="icn-td">${_ife(row.open_by_name)}</td>
      <td class="icn-td" style="font-family:monospace;">${_ife(row.invoice_sap_number)}</td>
      ${nameTd}
      ${noteTd}
      ${statusTd}
      <td class="icn-td" style="white-space:nowrap;font-size:16px;color:var(--text3);">${_ife(row.time_change_status)}</td>
      ${handlerTd}
    </tr>`;
  });

  h += '</tbody></table>';
  return h;
}

function icnStatusSelect(row) {
  let h = `<select class="icn-status-sel" onchange="icnUpdateStatus(${row.id},this)"
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