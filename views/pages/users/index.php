<?php use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
$permGroups = $permGroups ?? [];
$deptMap  = array_column($depts ?? [], 'desc', 'id');
$groupMap = array_column($permGroups, 'permmisionsGroupHeb', 'id');
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <div class="page-title" style="margin-bottom:0;">ניהול משתמשים</div>
  <div style="display:flex;gap:8px;">
    <a href="<?= $base ?>/users/perm-groups" class="btn btn-ghost">⚙️ קבוצות הרשאה</a>
    <button class="btn btn-primary" onclick="openModal()">+ משתמש חדש</button>
  </div>
</div>

<div style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
  <input type="text" id="usr-filter" placeholder="חיפוש שם, אימייל..."
         oninput="filterUsers(this.value)"
         style="flex:1;min-width:200px;background:var(--bg2);border:1px solid var(--border);
                border-radius:8px;padding:8px 12px;color:var(--text);font-size:14px;
                font-family:inherit;outline:none;">
  <select id="grp-filter" onchange="filterUsers(document.getElementById('usr-filter').value)"
          style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;
                 padding:8px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;">
    <option value="">כל הקבוצות</option>
    <?php foreach ($permGroups as $g): ?>
      <option value="<?= (int)$g['id'] ?>"><?= View::e($g['permmisionsGroupHeb']) ?></option>
    <?php endforeach; ?>
  </select>
  <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text2);cursor:pointer;">
    <input type="checkbox" id="show-inactive" onchange="filterUsers(document.getElementById('usr-filter').value)">
    הצג לא פעילים
  </label>
</div>

<div class="card">
  <table id="usr-table" style="width:100%;border-collapse:collapse;font-size:14px;">
    <thead>
      <tr style="color:var(--text2);">
        <th class="sortable" data-col="0" style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;cursor:pointer;white-space:nowrap;"># <span class="sort-icon">↕</span></th>
        <th class="sortable" data-col="1" style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;cursor:pointer;white-space:nowrap;">שם <span class="sort-icon">↕</span></th>
        <th class="sortable" data-col="2" style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;cursor:pointer;white-space:nowrap;">אימייל <span class="sort-icon">↕</span></th>
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">טלפון</th>
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">מחלקה</th>
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">קבוצת הרשאה</th>
        <th class="sortable" data-col="6" style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;cursor:pointer;white-space:nowrap;">כניסה אחרונה <span class="sort-icon">↕</span></th>
        <th class="sortable" data-col="7" style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;cursor:pointer;white-space:nowrap;">נוצר <span class="sort-icon">↕</span></th>
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">סטטוס</th>
        <th style="padding:8px 12px;border-bottom:1px solid var(--border);"></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($users ?? [] as $u):
      $lastLogin  = $u['last_login']  ? date('d/m/Y H:i', strtotime($u['last_login']))  : '—';
      $createdAt  = $u['created_at']  ? date('d/m/Y', strtotime($u['created_at']))       : '—';
      $mustChange = !empty($u['must_change_password']);
    ?>
    <tr class="usr-row"
        data-name="<?= strtolower(View::e($u['first_name'].' '.$u['last_name'].' '.($u['email']??''))) ?>"
        data-group="<?= (int)$u['permission_group_id'] ?>"
        data-active="<?= (int)$u['is_active'] ?>"
        data-last-login="<?= View::e($u['last_login'] ?? '') ?>"
        data-created="<?= View::e($u['created_at'] ?? '') ?>"
        style="border-bottom:1px solid var(--border);<?= $u['is_active'] ? '' : 'opacity:.5;' ?>">
      <td style="padding:9px 12px;color:var(--text3);"><?= (int)$u['id'] ?></td>
      <td style="padding:9px 12px;font-weight:500;">
        <?= View::e($u['first_name'].' '.$u['last_name']) ?>
        <?php if ($mustChange): ?>
          <span title="ממתין לשינוי סיסמא" style="font-size:11px;color:var(--warning,#f59e0b);margin-right:4px;">🔒</span>
        <?php endif; ?>
      </td>
      <td style="padding:9px 12px;color:var(--text2);font-size:13px;"><?= View::e($u['email'] ?? '—') ?></td>
      <td style="padding:9px 12px;color:var(--text2);"><?= View::e($u['phone'] ?? '—') ?></td>
      <td style="padding:9px 12px;color:var(--text2);"><?= View::e($u['dept_name'] ?? '—') ?></td>
      <td style="padding:9px 12px;">
        <span class="badge badge-info"><?= View::e($u['group_name'] ?? '—') ?></span>
      </td>
      <td style="padding:9px 12px;color:var(--text2);font-size:12px;"><?= $lastLogin ?></td>
      <td style="padding:9px 12px;color:var(--text2);font-size:12px;"><?= $createdAt ?></td>
      <td style="padding:9px 12px;">
        <span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-danger' ?>">
          <?= $u['is_active'] ? 'פעיל' : 'לא פעיל' ?>
        </span>
      </td>
      <td style="padding:9px 12px;display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
        <button class="btn btn-ghost" style="padding:4px 10px;font-size:12px;"
                onclick="openModal(<?= (int)$u['id'] ?>)">עריכה</button>
        <?php if (!empty($u['email'])): ?>
        <button class="btn btn-ghost" style="padding:4px 10px;font-size:12px;color:var(--accent);"
                onclick="sendResetEmail(<?= (int)$u['id'] ?>, '<?= View::e($u['first_name']) ?>')"
                title="שלח קישור איפוס סיסמא">🔑 איפוס</button>
        <button class="btn btn-ghost" style="padding:4px 10px;font-size:12px;color:var(--warning,#f59e0b);"
                onclick="openTempPassModal(<?= (int)$u['id'] ?>, '<?= View::e($u['first_name'].' '.$u['last_name']) ?>')"
                title="קבע סיסמא זמנית">🔐 זמנית</button>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal עריכת/הוספת משתמש -->
<div id="usr-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:300;
            align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);
              width:100%;max-width:560px;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;
                padding:16px 20px;border-bottom:1px solid var(--border);
                position:sticky;top:0;background:var(--bg2);z-index:1;">
      <div id="modal-title" style="font-size:16px;font-weight:600;">משתמש חדש</div>
      <button onclick="closeModal()"
              style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
    </div>
    <div style="display:flex;border-bottom:1px solid var(--border);padding:0 20px;">
      <button class="mtab active" onclick="switchTab('details')" data-tab="details">פרטים</button>
    </div>
    <div style="padding:20px;">
      <input type="hidden" id="f-id">
      <div id="tab-details">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
          <div><label class="flabel">שם פרטי *</label><input id="f-fname" type="text" class="finput"></div>
          <div><label class="flabel">שם משפחה</label><input id="f-lname" type="text" class="finput"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
          <div><label class="flabel">אימייל</label><input id="f-email" type="email" class="finput"></div>
          <div><label class="flabel">טלפון</label><input id="f-phone" type="text" class="finput" dir="ltr"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
          <div>
            <label class="flabel">מחלקה</label>
            <select id="f-depart" class="finput">
              <option value="">— בחר —</option>
              <?php foreach ($depts ?? [] as $d): ?>
                <option value="<?= (int)$d['id'] ?>"><?= View::e($d['desc']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="flabel">קבוצת הרשאה</label>
            <select id="f-group" class="finput">
              <option value="">— בחר —</option>
              <?php foreach ($permGroups as $g): ?>
                <option value="<?= (int)$g['id'] ?>"><?= View::e($g['permmisionsGroupHeb']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
          <div><label class="flabel">Mvoice ID</label><input id="f-mvoice" type="text" class="finput" dir="ltr"></div>
          <div><label class="flabel">SIP</label><input id="f-sip" type="text" class="finput" dir="ltr"></div>
        </div>
        <div style="margin-bottom:14px;">
          <label class="flabel">הערה</label>
          <textarea id="f-note" rows="2" class="finput" style="resize:vertical;"></textarea>
        </div>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
          <input type="checkbox" id="f-active" checked> פעיל
        </label>
      </div>

      <div id="modal-error" style="color:var(--danger);font-size:13px;margin:10px 0;display:none;"></div>
      <div style="display:flex;gap:10px;margin-top:16px;">
        <button class="btn btn-primary" style="flex:1;" onclick="saveUser()">שמור</button>
        <button class="btn btn-ghost" onclick="closeModal()">ביטול</button>
        <button class="btn btn-danger" id="toggle-btn" style="display:none;" onclick="toggleUser()"></button>
      </div>
    </div>
  </div>
</div>

<!-- Modal סיסמא זמנית -->
<div id="temp-pass-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:400;
            align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);
              width:100%;max-width:400px;">
    <div style="display:flex;justify-content:space-between;align-items:center;
                padding:16px 20px;border-bottom:1px solid var(--border);">
      <div style="font-size:16px;font-weight:600;">🔐 סיסמא זמנית</div>
      <button onclick="closeTempPassModal()"
              style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
    </div>
    <div style="padding:20px;">
      <p id="temp-pass-user-name" style="font-size:14px;color:var(--text2);margin:0 0 16px;"></p>
      <p style="font-size:13px;color:var(--text2);margin:0 0 12px;">
        הסיסמא הזמנית תפוג לאחר כניסה ראשונה — המשתמש יחויב לבחור סיסמא חדשה.
      </p>
      <div style="margin-bottom:14px;">
        <label class="flabel">סיסמא זמנית *</label>
        <div style="display:flex;gap:8px;">
          <input id="tp-password" type="text" class="finput" dir="ltr" placeholder="לפחות 6 תווים"
                 style="flex:1;">
          <button class="btn btn-ghost" style="padding:8px 12px;font-size:12px;white-space:nowrap;"
                  onclick="generateTempPass()">יצירה אוטו׳</button>
        </div>
      </div>
      <div id="temp-pass-error" style="color:var(--danger);font-size:13px;margin-bottom:10px;display:none;"></div>
      <div style="display:flex;gap:10px;">
        <button class="btn btn-primary" style="flex:1;" onclick="saveTempPass()">קבע סיסמא</button>
        <button class="btn btn-ghost" onclick="closeTempPassModal()">ביטול</button>
      </div>
    </div>
  </div>
</div>

<style>
.flabel { display:block;font-size:12px;color:var(--text2);margin-bottom:5px;font-weight:500; }
.finput { width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;
          padding:8px 12px;color:var(--text);font-size:14px;font-family:var(--font);outline:none; }
.finput:focus { border-color:var(--accent); }
.mtab { background:none;border:none;padding:10px 16px;font-size:14px;font-family:var(--font);
        color:var(--text2);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px; }
.mtab.active { color:var(--text);border-bottom-color:var(--accent); }
.sortable:hover { background:var(--bg3); }
.sortable.sort-asc .sort-icon::after  { content:' ↑'; }
.sortable.sort-desc .sort-icon::after { content:' ↓'; }
.sort-icon { opacity:.5;font-size:11px; }
.sortable.sort-asc .sort-icon,
.sortable.sort-desc .sort-icon { opacity:1; }
</style>

<script>
const BASE_URL = '<?= $base ?>';
const CSRF = '<?= View::e($csrf) ?>';
let currentUserId = null;
let tempPassUserId = null;
let sortState = { col: null, dir: 'asc' };

/* ── Filter ── */
function filterUsers(q) {
  q = q.toLowerCase();
  const grp = document.getElementById('grp-filter').value;
  const showInactive = document.getElementById('show-inactive').checked;
  document.querySelectorAll('.usr-row').forEach(row => {
    const nameMatch = !q || row.dataset.name.includes(q);
    const grpMatch  = !grp || row.dataset.group === grp;
    const actMatch  = showInactive || row.dataset.active === '1';
    row.style.display = (nameMatch && grpMatch && actMatch) ? '' : 'none';
  });
}

/* ── Sort ── */
document.querySelectorAll('#usr-table th.sortable').forEach(th => {
  th.addEventListener('click', () => {
    const col = parseInt(th.dataset.col);
    if (sortState.col === col) {
      sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
    } else {
      sortState.col = col;
      sortState.dir = 'asc';
    }
    document.querySelectorAll('#usr-table th.sortable').forEach(h => {
      h.classList.remove('sort-asc','sort-desc');
    });
    th.classList.add(sortState.dir === 'asc' ? 'sort-asc' : 'sort-desc');
    sortTable(col, sortState.dir);
  });
});

function sortTable(col, dir) {
  const tbody = document.querySelector('#usr-table tbody');
  const rows  = Array.from(tbody.querySelectorAll('tr.usr-row'));
  rows.sort((a, b) => {
    let av = a.cells[col]?.innerText.trim() ?? '';
    let bv = b.cells[col]?.innerText.trim() ?? '';
    // date columns — compare raw ISO values from data attributes
    if (col === 6) { av = a.dataset.lastLogin; bv = b.dataset.lastLogin; }
    if (col === 7) { av = a.dataset.created;   bv = b.dataset.created; }
    const n = parseFloat(av), m = parseFloat(bv);
    const cmp = (!isNaN(n) && !isNaN(m)) ? n - m : av.localeCompare(bv, 'he');
    return dir === 'asc' ? cmp : -cmp;
  });
  rows.forEach(r => tbody.appendChild(r));
}

/* ── User Modal ── */
function openModal(id) {
  currentUserId = id || null;
  document.getElementById('modal-title').textContent = id ? 'עריכת משתמש' : 'משתמש חדש';
  document.getElementById('modal-error').style.display = 'none';
  document.getElementById('toggle-btn').style.display  = id ? 'block' : 'none';
  switchTab('details');

  if (id) {
    fetch(`${BASE_URL}/users/${id}`,{headers:{'Accept':'application/json'}}).then(r => r.json()).then(u => {
      document.getElementById('f-id').value      = u.id;
      document.getElementById('f-fname').value   = u.first_name || '';
      document.getElementById('f-lname').value   = u.last_name  || '';
      document.getElementById('f-email').value   = u.email      || '';
      document.getElementById('f-phone').value   = u.phone      || '';
      document.getElementById('f-depart').value  = u.department_id       || '';
      document.getElementById('f-group').value   = u.permission_group_id || '';
      document.getElementById('f-mvoice').value  = u.mvoice_id  || '';
      document.getElementById('f-sip').value     = u.sip_voice  || '';
      document.getElementById('f-note').value    = u.note       || '';
      document.getElementById('f-active').checked = !!parseInt(u.is_active);
      const btn = document.getElementById('toggle-btn');
      btn.textContent = parseInt(u.is_active) ? 'השבת משתמש' : 'הפעל משתמש';
      btn.style.background = parseInt(u.is_active) ? 'var(--danger)' : 'var(--success)';
    });
  } else {
    ['f-id','f-fname','f-lname','f-email','f-phone','f-mvoice','f-sip','f-note']
      .forEach(id => { const el = document.getElementById(id); if (el) el.value=''; });
    document.getElementById('f-active').checked = true;
  }
  document.getElementById('usr-modal').style.display = 'flex';
}

function closeModal() { document.getElementById('usr-modal').style.display = 'none'; }

function switchTab(tab) {
  document.querySelectorAll('.mtab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
  document.getElementById('tab-details').style.display = tab==='details' ? 'block' : 'none';
}

async function saveUser() {
  const fName = document.getElementById('f-fname').value.trim();
  if (!fName) { showErr('שם פרטי חובה'); return; }
  const isNew = !currentUserId;
  if (isNew && !document.getElementById('f-email').value.trim()) {
    showErr('אימייל חובה למשתמש חדש — יישלח קישור לקביעת סיסמא');
    return;
  }

  const body = new URLSearchParams({
    _csrf: CSRF, id: currentUserId || '',
    fName, lName: document.getElementById('f-lname').value.trim(),
    email: document.getElementById('f-email').value.trim(),
    phoneNum: document.getElementById('f-phone').value.trim(),
    depart: document.getElementById('f-depart').value,
    permissionGroupID: document.getElementById('f-group').value,
    mvoiceid: document.getElementById('f-mvoice').value.trim(),
    sipVoice: document.getElementById('f-sip').value.trim(),
    userNote: document.getElementById('f-note').value.trim(),
    active: document.getElementById('f-active').checked ? '1' : '0',
  });

  const res  = await fetch(`${BASE_URL}/users/save`, { method:'POST', body });
  const data = await res.json();
  if (data.ok) {
    if (data.warn) alert(data.warn);
    closeModal();
    location.reload();
  } else {
    showErr(data.error || 'שגיאה');
  }
}

async function toggleUser() {
  if (!currentUserId || !confirm('לשנות סטטוס משתמש זה?')) return;
  const res  = await fetch(`${BASE_URL}/users/toggle`, {
    method:'POST', body: new URLSearchParams({ _csrf: CSRF, id: currentUserId })
  });
  const data = await res.json();
  if (data.ok) { closeModal(); location.reload(); }
}

function showErr(msg) {
  const el = document.getElementById('modal-error');
  el.textContent = msg; el.style.display = 'block';
}

async function sendResetEmail(userId, userName) {
  if (!confirm(`לשלוח קישור לאיפוס סיסמא למשתמש ${userName}?`)) return;
  const res  = await fetch(`${BASE_URL}/users/send-reset-email`, {
    method: 'POST',
    body: new URLSearchParams({ _csrf: CSRF, id: userId })
  });
  const data = await res.json();
  if (data.ok) {
    alert(`קישור איפוס סיסמא נשלח למשתמש ${userName}.`);
  } else {
    alert('שגיאה: ' + (data.error || 'לא ניתן לשלוח'));
  }
}

/* ── Temp Password Modal ── */
function openTempPassModal(userId, userName) {
  tempPassUserId = userId;
  document.getElementById('temp-pass-user-name').textContent = 'משתמש: ' + userName;
  document.getElementById('tp-password').value = '';
  document.getElementById('temp-pass-error').style.display = 'none';
  document.getElementById('temp-pass-modal').style.display = 'flex';
}

function closeTempPassModal() {
  document.getElementById('temp-pass-modal').style.display = 'none';
  tempPassUserId = null;
}

function generateTempPass() {
  const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#';
  let pass = '';
  for (let i = 0; i < 8; i++) pass += chars[Math.floor(Math.random() * chars.length)];
  document.getElementById('tp-password').value = pass;
}

async function saveTempPass() {
  const pass = document.getElementById('tp-password').value.trim();
  if (pass.length < 6) {
    const el = document.getElementById('temp-pass-error');
    el.textContent = 'הסיסמא חייבת להכיל לפחות 6 תווים';
    el.style.display = 'block';
    return;
  }
  const res  = await fetch(`${BASE_URL}/users/set-temp-password`, {
    method: 'POST',
    body: new URLSearchParams({ _csrf: CSRF, id: tempPassUserId, password: pass })
  });
  const data = await res.json();
  if (data.ok) {
    closeTempPassModal();
    alert('הסיסמא הזמנית נקבעה בהצלחה. המשתמש יחויב לשנותה בכניסה הבאה.');
    location.reload();
  } else {
    const el = document.getElementById('temp-pass-error');
    el.textContent = data.error || 'שגיאה בקביעת הסיסמא';
    el.style.display = 'block';
  }
}

document.addEventListener('keydown', e => { if (e.key==='Escape') { closeModal(); closeTempPassModal(); } });

// הפעלת filter בטעינת הדף
filterUsers(document.getElementById('usr-filter').value);
</script>
