<?php
use Core\Auth;
$base      = rtrim(CFG['app']['url'], '/');
$csrf      = $_SESSION['csrf_token'] ?? '';
$userEmail = Auth::user()['email'] ?? '';
?>

<!-- ═══════════════════════════════════════════════════════════
     AUTOMATION MODAL — moked-net V2
     ─────────────────────────────────────────────────────────
     API גלובלית:
       window.openAutomationModal(prefill?)

       prefill = {
         type     : 'notifyOnChangeTo'|'techCare'|'openCaseByPhone'|'chechOrderNote',
         caseNum  : '123456',    // notifyOnChangeTo / techCare
         statusId : '5',         // notifyOnChangeTo — pre-select status
         phone    : '0501234567',// openCaseByPhone
         orderNum : '654321',    // chechOrderNote
         msg      : 'טקסט...',  // הודעה אישית
       }
     ═══════════════════════════════════════════════════════════ -->

<div id="auto-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:610;
            align-items:flex-start;justify-content:center;padding:44px 16px 16px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);
              width:100%;max-width:560px;max-height:90vh;display:flex;flex-direction:column;
              box-shadow:0 24px 80px rgba(0,0,0,.6);">

    <!-- Header -->
    <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;
                border-bottom:1px solid var(--border);flex-shrink:0;">
      <i class="bi bi-lightning-charge-fill" style="color:var(--accent);font-size:18px;"></i>
      <div style="flex:1;">
        <div style="font-size:15px;font-weight:700;">הוספת משימה אוטומטית</div>
        <div style="font-size:11px;color:var(--text3);">מוקדנט יבצע את הפעולה בשבילכם</div>
      </div>
      <button onclick="closeAutomationModal()"
              style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;line-height:1;">✕</button>
    </div>

    <!-- Body -->
    <div style="overflow-y:auto;padding:20px 22px;flex:1;">

      <!-- 1. Job type -->
      <div class="af-group">
        <label class="af-lbl">סוג משימה</label>
        <select id="af-type" class="af-inp" onchange="_afTypeChange()">
          <option value="">— נא לבחור סוג משימה —</option>
          <option value="openCaseByPhone">📞 התראה כשלקוח פותח קריאה לפי טלפון</option>
          <option value="notifyOnChangeTo">🔀 התראה כשקריאה עוברת לסטטוס</option>
          <option value="techCare">🔧 התראה כשטכנאי מעדכן טיפול</option>
          <option value="chechOrderNote">🛒 התראה בעת שינוי בהערות הזמנה</option>
        </select>
      </div>

      <!-- 2. Status (notifyOnChangeTo) -->
      <div class="af-group" id="af-group-status" style="display:none;">
        <label class="af-lbl">בחירת סטטוס להתראה</label>
        <select id="af-status" class="af-inp">
          <option value="">טוען סטטוסים...</option>
        </select>
        <label id="af-group-runeven"
               style="display:none;align-items:center;gap:7px;margin-top:8px;
                      font-size:13px;color:var(--text2);cursor:pointer;">
          <input type="checkbox" id="af-runeven" style="accent-color:var(--accent);">
          עדכן גם אם השתנה לסטטוס אחר
        </label>
      </div>

      <!-- 3. Case number -->
      <div class="af-group" id="af-group-casenum" style="display:none;">
        <label class="af-lbl">מספר קריאה (6 ספרות)</label>
        <input type="number" id="af-casenum" class="af-inp" placeholder="123456" maxlength="6">
      </div>

      <!-- 4. Phone -->
      <div class="af-group" id="af-group-phone" style="display:none;">
        <label class="af-lbl">מספר טלפון (10 ספרות)</label>
        <input type="tel" id="af-phone" class="af-inp" placeholder="05X-XXXXXXX" maxlength="10">
      </div>

      <!-- 5. Order number -->
      <div class="af-group" id="af-group-order" style="display:none;">
        <label class="af-lbl">מספר הזמנה (6 ספרות)</label>
        <input type="number" id="af-order" class="af-inp" placeholder="654321" maxlength="6">
      </div>

      <!-- 6. "שלח אליי" toggle -->
      <div class="af-group">
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;
                    padding:10px 14px;display:flex;align-items:center;justify-content:space-between;">
          <div>
            <div style="font-size:13px;font-weight:600;">
              <i class="bi bi-person-fill" style="color:var(--accent);margin-left:5px;"></i>
              שלח אליי התראה
            </div>
            <div style="font-size:11px;color:var(--text3);margin-top:2px;">
              <?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>
          <label style="cursor:pointer;position:relative;display:inline-block;
                        width:42px;height:24px;flex-shrink:0;">
            <input type="checkbox" id="af-notify-me" checked
                   style="opacity:0;width:0;height:0;position:absolute;">
            <span id="af-nm-track"
                  style="display:block;width:100%;height:100%;background:var(--accent);
                         border-radius:12px;transition:background .2s;position:relative;">
              <span id="af-nm-thumb"
                    style="position:absolute;top:3px;right:calc(100% - 21px);width:18px;height:18px;
                           background:#fff;border-radius:50%;transition:right .2s;
                           box-shadow:0 1px 4px rgba(0,0,0,.3);"></span>
            </span>
          </label>
        </div>
      </div>

      <!-- 7. Contacts multi-select -->
      <div class="af-group">
        <label class="af-lbl">
          <i class="bi bi-people-fill" style="color:var(--accent);margin-left:4px;"></i>
          שליחת התראה לאנשי קשר נוספים
        </label>

        <div style="position:relative;" id="af-contacts-wrap">
          <div class="af-search-box">
            <i class="bi bi-search" style="color:var(--text3);font-size:12px;flex-shrink:0;"></i>
            <input type="text" id="af-contacts-q" class="af-search-input"
                   placeholder="חיפוש איש קשר..." autocomplete="off"
                   oninput="_afFilterContacts(this.value)"
                   onfocus="_afOpenDropdown()">
            <span id="af-contacts-badge"
                  style="display:none;background:var(--accent);color:#fff;border-radius:10px;
                         padding:1px 7px;font-size:11px;font-weight:700;flex-shrink:0;"></span>
          </div>

          <div id="af-contacts-dropdown"
               style="display:none;position:absolute;top:calc(100% + 4px);right:0;left:0;
                      background:var(--bg2);border:1px solid var(--border);border-radius:8px;
                      max-height:200px;overflow-y:auto;z-index:50;
                      box-shadow:0 8px 30px rgba(0,0,0,.4);">
            <div id="af-contacts-inner">
              <div style="padding:12px;color:var(--text3);font-size:13px;text-align:center;">
                <i class="bi bi-hourglass-split"></i> טוען...
              </div>
            </div>
          </div>
        </div>

        <!-- Selected chips -->
        <div id="af-selected-chips" style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px;"></div>
      </div>

      <!-- 8. Personal message -->
      <div class="af-group">
        <label class="af-lbl">הודעה אישית (אופציונלי)</label>
        <textarea id="af-msg" class="af-inp" rows="3" placeholder="כתוב/י הודעה..."></textarea>
      </div>

      <!-- Alert -->
      <div id="af-alert"
           style="display:none;padding:10px 14px;border-radius:8px;margin-top:6px;
                  font-size:13px;font-weight:600;"></div>

      <!-- Order note preview -->
      <div id="af-order-preview"
           style="display:none;padding:10px 14px;background:var(--bg3);border:1px solid var(--border);
                  border-radius:8px;margin-top:8px;font-size:13px;color:var(--text2);">
        <div style="font-size:10px;font-weight:700;color:var(--text3);
                    text-transform:uppercase;margin-bottom:4px;">הערה נוכחית:</div>
        <div id="af-order-note-text"></div>
      </div>

    </div><!-- /body -->

    <!-- Footer -->
    <div style="padding:14px 22px;border-top:1px solid var(--border);
                display:flex;align-items:center;gap:10px;flex-shrink:0;">
      <button id="af-submit" class="btn btn-primary" onclick="_afSubmit()">
        <i class="bi bi-lightning-charge-fill"></i> הוספת משימה
      </button>
      <button class="btn btn-ghost" onclick="closeAutomationModal()">ביטול</button>
      <span id="af-spinner"
            style="display:none;margin-right:auto;font-size:12px;color:var(--text3);">
        <i class="bi bi-hourglass-split"></i> שולח...
      </span>
    </div>

  </div>
</div>

<style>
.af-group { margin-bottom: 16px; }
.af-lbl   { display:block; font-size:13px; font-weight:600; color:var(--text2); margin-bottom:5px; }
.af-inp   {
  width:100%; background:var(--bg3); border:1px solid var(--border); border-radius:8px;
  padding:9px 12px; color:var(--text); font-size:13px; font-family:var(--font,sans-serif);
  outline:none; transition:border-color .15s, box-shadow .15s;
}
.af-inp:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(91,141,238,.12); }
textarea.af-inp { resize:vertical; min-height:70px; }

.af-search-box {
  display:flex; align-items:center; gap:7px; background:var(--bg3);
  border:1px solid var(--border); border-radius:8px; padding:0 12px;
  transition:border-color .15s;
}
.af-search-box:focus-within { border-color:var(--accent); box-shadow:0 0 0 3px rgba(91,141,238,.12); }
.af-search-input {
  background:none; border:none; outline:none; color:var(--text);
  font-family:var(--font,sans-serif); font-size:13px; padding:9px 0; flex:1;
}
.af-search-input::placeholder { color:var(--text3); }

.af-ct-item {
  display:flex; align-items:center; gap:10px; padding:9px 14px; cursor:pointer;
  border-bottom:1px solid var(--border); transition:background .12s;
}
.af-ct-item:last-child { border-bottom:none; }
.af-ct-item:hover, .af-ct-item.af-selected { background:var(--accent-dim); }
.af-ct-avatar {
  width:28px; height:28px; border-radius:50%; background:var(--accent);
  color:#fff; display:grid; place-items:center; font-size:11px; font-weight:700; flex-shrink:0;
}
.af-ct-name { font-size:13px; font-weight:600; color:var(--text); }
.af-ct-sub  { font-size:11px; color:var(--text3); }
.af-ct-check { margin-right:auto; font-size:15px; color:var(--accent); display:none; }
.af-ct-item.af-selected .af-ct-check { display:block; }

.af-chip {
  display:inline-flex; align-items:center; gap:5px; background:rgba(91,141,238,.12);
  border:1px solid rgba(91,141,238,.3); border-radius:20px; padding:3px 10px;
  font-size:12px; color:var(--accent);
}
.af-chip button {
  background:none; border:none; color:var(--accent); cursor:pointer;
  font-size:15px; line-height:1; padding:0; opacity:.7;
}
.af-chip button:hover { opacity:1; }

#af-alert.af-err { background:rgba(239,68,68,.1);  border:1px solid rgba(239,68,68,.3);  color:#f87171; }
#af-alert.af-ok  { background:rgba(34,197,94,.1);  border:1px solid rgba(34,197,94,.3);  color:#4ade80; }
</style>

<script>
(function () {
  const BASE_AF  = '<?= $base ?>';
  const CSRF_AF  = '<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>';
  const MY_EMAIL = '<?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?>';

  const AVATAR_COLORS = ['#5b8dee','#8b5cf6','#10b981','#f59e0b','#ec4899','#06b6d4'];

  /* ── State ── */
  let _statuses = null;          // cache
  let _contacts = null;          // cache
  let _selected = new Map();     // id → {id, name, email}

  /* ════════════════════════════════════════════════════════════
     OPEN / CLOSE
     ════════════════════════════════════════════════════════════ */

  /**
   * openAutomationModal(prefill?)
   *
   * prefill = {
   *   type     : string,    // סוג המשימה
   *   caseNum  : string,    // מספר קריאה
   *   statusId : string,    // id סטטוס לבחירה מראש
   *   phone    : string,    // מספר טלפון
   *   orderNum : string,    // מספר הזמנה
   *   msg      : string,    // הודעה אישית
   * }
   */
  window.openAutomationModal = function (prefill) {
    _reset();
    document.getElementById('auto-modal').style.display = 'flex';
    _ensureContacts(); // preload in background

    if (prefill && typeof prefill === 'object') {
      if (prefill.type) {
        const sel = document.getElementById('af-type');
        if (sel) { sel.value = prefill.type; _afTypeChange(); }
      }
      if (prefill.caseNum)  _setVal('af-casenum', String(prefill.caseNum));
      if (prefill.phone)    _setVal('af-phone',   String(prefill.phone));
      if (prefill.orderNum) _setVal('af-order',   String(prefill.orderNum));
      if (prefill.msg)      _setVal('af-msg',      String(prefill.msg));

      if (prefill.statusId) {
        _ensureStatuses().then(() => {
          const sel = document.getElementById('af-status');
          if (sel) sel.value = String(prefill.statusId);
        });
      }
    }

    setTimeout(() => document.getElementById('af-type')?.focus(), 40);
  };

  window.closeAutomationModal = function () {
    document.getElementById('auto-modal').style.display = 'none';
    _closeDropdown();
  };

  // Backdrop click
  document.getElementById('auto-modal').addEventListener('click', e => {
    if (e.target === document.getElementById('auto-modal')) window.closeAutomationModal();
  });
  // Escape key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && document.getElementById('auto-modal').style.display === 'flex')
      window.closeAutomationModal();
  });
  // Close dropdown on outside click
  document.addEventListener('click', e => {
    if (!document.getElementById('af-contacts-wrap')?.contains(e.target))
      _closeDropdown();
  });

  /* ════════════════════════════════════════════════════════════
     RESET
     ════════════════════════════════════════════════════════════ */
  function _reset() {
    ['af-type','af-status','af-casenum','af-phone','af-order','af-msg']
      .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });

    // "שלח אליי" — on by default
    _setToggle('af-notify-me', 'af-nm-track', 'af-nm-thumb', true);

    _selected.clear();
    _renderChips();

    _afAlert('', '');
    ['af-group-status','af-group-casenum','af-group-phone','af-group-order',
     'af-group-runeven','af-order-preview']
      .forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });

    const sub = document.getElementById('af-submit');
    if (sub) { sub.disabled = false; sub.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> הוספת משימה'; }

    document.getElementById('af-contacts-q').value = '';
    _closeDropdown();
    _updateBadge();
  }

  function _setVal(id, v) { const el = document.getElementById(id); if (el) el.value = v; }

  /* ════════════════════════════════════════════════════════════
     TOGGLE HELPER (visual only — no CSS classes needed)
     ════════════════════════════════════════════════════════════ */
  function _setToggle(cbId, trackId, thumbId, on) {
    const cb    = document.getElementById(cbId);
    const track = document.getElementById(trackId);
    const thumb = document.getElementById(thumbId);
    if (cb)    cb.checked = on;
    if (track) track.style.background = on ? 'var(--accent)' : 'var(--border2)';
    if (thumb) thumb.style.right = on ? 'calc(100% - 21px)' : '3px';
  }

  document.addEventListener('change', e => {
    if (e.target.id === 'af-notify-me')
      _setToggle('af-notify-me', 'af-nm-track', 'af-nm-thumb', e.target.checked);
  });

  /* ════════════════════════════════════════════════════════════
     TYPE CHANGE
     ════════════════════════════════════════════════════════════ */
  window._afTypeChange = function () {
    const type = document.getElementById('af-type')?.value || '';
    ['af-group-status','af-group-casenum','af-group-phone','af-group-order',
     'af-group-runeven','af-order-preview']
      .forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });
    _afAlert('', '');

    switch (type) {
      case 'notifyOnChangeTo':
        _show('af-group-status');
        _show('af-group-casenum');
        document.getElementById('af-group-runeven').style.display = 'flex';
        _ensureStatuses();
        break;
      case 'techCare':
        _show('af-group-casenum');
        break;
      case 'openCaseByPhone':
        _show('af-group-phone');
        break;
      case 'chechOrderNote':
        _show('af-group-order');
        break;
    }
  };

  function _show(id) { const el = document.getElementById(id); if (el) el.style.display = 'block'; }

  /* ════════════════════════════════════════════════════════════
     STATUSES
     ════════════════════════════════════════════════════════════ */
  async function _ensureStatuses() {
    if (_statuses) { _fillStatuses(); return; }
    try {
      const r = await fetch(BASE_AF + '/api/automation/statuses', { credentials: 'include' });
      _statuses = await r.json();
      _fillStatuses();
    } catch {
      const sel = document.getElementById('af-status');
      if (sel) sel.innerHTML = '<option value="">שגיאה בטעינת סטטוסים</option>';
    }
  }

  function _fillStatuses() {
    const sel = document.getElementById('af-status');
    if (!sel || !_statuses) return;
    sel.innerHTML = '<option value="">— נא לבחור סטטוס —</option>';
    _statuses.forEach(s => {
      const o = document.createElement('option');
      o.value = s.id; o.textContent = s.label; sel.appendChild(o);
    });
  }

  /* ════════════════════════════════════════════════════════════
     CONTACTS DROPDOWN
     ════════════════════════════════════════════════════════════ */
  async function _ensureContacts() {
    if (_contacts !== null) return;
    try {
      const r = await fetch(BASE_AF + '/api/contacts/list', { credentials: 'include' });
      _contacts = await r.json();
      _renderDropdown(_contacts);
    } catch { _contacts = []; }
  }

  window._afOpenDropdown = function () {
    _ensureContacts();
    document.getElementById('af-contacts-dropdown').style.display = 'block';
  };

  function _closeDropdown() {
    const el = document.getElementById('af-contacts-dropdown');
    if (el) el.style.display = 'none';
  }

  window._afFilterContacts = function (q) {
    if (!_contacts) { _ensureContacts(); return; }
    const rawQ = (q || '').trim();
    const lq   = rawQ.toLowerCase();
    const list = lq
      ? _contacts.filter(c =>
          ((c.first_name||'') + ' ' + (c.last_name||'') + ' ' + (c.email||''))
            .toLowerCase().includes(lq))
      : _contacts;
    _renderDropdown(list, rawQ);
    document.getElementById('af-contacts-dropdown').style.display = 'block';
  };

  function _isValidEmail(s) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s.trim());
  }

  function _renderDropdown(list, rawQ) {
    const inner = document.getElementById('af-contacts-inner');
    if (!inner) return;

    const manualKey = '__manual__';
    let manualHtml = '';
    if (rawQ && _isValidEmail(rawQ)) {
      const emailLower = rawQ.toLowerCase();
      const alreadyInList   = list && list.some(c => (c.email||'').toLowerCase() === emailLower);
      const alreadySelected = [..._selected.values()].some(c => (c.email||'').toLowerCase() === emailLower);
      if (!alreadyInList && !alreadySelected) {
        const mKey = manualKey + rawQ;
        const sel  = _selected.has(mKey) ? 'af-selected' : '';
        manualHtml = `<div class="af-ct-item ${sel}"
             data-cid="${_attr(mKey)}"
             data-name="${_attr(rawQ)}"
             data-email="${_attr(rawQ)}"
             data-manual="1">
          <div class="af-ct-avatar" style="background:#10b981;">&#9993;</div>
          <div style="flex:1;min-width:0;">
            <div class="af-ct-name">${_esc(rawQ)}</div>
            <div class="af-ct-sub">&#1492;&#1494;&#1504;&#1492; &#1497;&#1491;&#1504;&#1497;&#1514;</div>
          </div>
          <i class="bi bi-check2-circle af-ct-check"></i>
        </div>`;
      }
    }

    if (!list || !list.length) {
      if (manualHtml) {
        inner.innerHTML = manualHtml;
      } else {
        inner.innerHTML = '<div style="padding:12px;color:var(--text3);font-size:13px;text-align:center;">&#1500;&#1488; &#1504;&#1502;&#1510;&#1488;&#1493; &#1488;&#1504;&#1513;&#1497; &#1511;&#1513;&#1512;</div>';
        if (rawQ && rawQ.length > 2 && !_isValidEmail(rawQ)) {
          inner.innerHTML += '<div style="padding:0 12px 10px;color:var(--text3);font-size:11px;text-align:center;">&#1492;&#1494;&#1503; &#1499;&#1514;&#1493;&#1489;&#1514; &#1502;&#1497;&#1497;&#1500; &#1502;&#1500;&#1488;&#1492; &#1500;&#1492;&#1493;&#1505;&#1508;&#1492; &#1497;&#1491;&#1504;&#1497;&#1514;</div>';
        }
      }
      _bindDropdownClicks(inner);
      return;
    }

    inner.innerHTML = manualHtml + list.map(c => {
      const name   = ((c.first_name||'') + ' ' + (c.last_name||'')).trim();
      const init   = (c.first_name||'?').charAt(0) + (c.last_name||'').charAt(0);
      const ac     = AVATAR_COLORS[_hash(name) % AVATAR_COLORS.length];
      const sel    = _selected.has(c.id) ? 'af-selected' : '';
      const isUser = c.source === 'user';
      const sub    = (isUser ? [c.email] : [c.role, c.department, c.email]).filter(Boolean).join(' · ');
      const badge  = isUser ? ' <span style="font-size:10px;background:rgba(91,141,238,.15);color:var(--accent);border-radius:4px;padding:1px 5px;font-weight:600;">&#1502;&#1513;&#1514;&#1502;&#1513;</span>' : '';
      return `<div class="af-ct-item ${sel}"
                   data-cid="${c.id}"
                   data-name="${_attr(name)}"
                   data-email="${_attr(c.email||'')}">
        <div class="af-ct-avatar" style="background:${ac};">${_esc(init)}</div>
        <div style="flex:1;min-width:0;">
          <div class="af-ct-name">${_esc(name)}${badge}</div>
          ${sub ? `<div class="af-ct-sub">${_esc(sub)}</div>` : ''}
        </div>
        <i class="bi bi-check2-circle af-ct-check"></i>
      </div>`;
    }).join('');

    _bindDropdownClicks(inner);
  }

  function _bindDropdownClicks(inner) {
    inner.onclick = e => {
      const item = e.target.closest('.af-ct-item');
      if (!item) return;
      const isManual = item.dataset.manual === '1';
      const cid = isManual ? item.dataset.cid : parseInt(item.dataset.cid);
      _afToggleContact(cid, item.dataset.name, item.dataset.email);
    };
  }

  window._afToggleContact = function (id, name, email) {
    if (_selected.has(id)) {
      _selected.delete(id);
    } else {
      _selected.set(id, { id, name, email });
    }
    _afFilterContacts(document.getElementById('af-contacts-q')?.value || '');
    _renderChips();
    _updateBadge();
  };

  function _renderChips() {
    const wrap = document.getElementById('af-selected-chips');
    if (!wrap) return;
    if (!_selected.size) { wrap.innerHTML = ''; return; }
    wrap.innerHTML = [..._selected.values()].map(c =>
      `<span class="af-chip" data-cid="${c.id}" data-name="${_attr(c.name)}" data-email="${_attr(c.email)}">
        <i class="bi bi-person-fill" style="font-size:11px;"></i>
        ${_esc(c.name)}
        <button data-cid="${c.id}" data-name="${_attr(c.name)}" data-email="${_attr(c.email)}"
                style="background:none;border:none;color:var(--accent);cursor:pointer;font-size:15px;line-height:1;padding:0;opacity:.7;">×</button>
      </span>`
    ).join('');

    wrap.onclick = e => {
      const btn = e.target.closest('button[data-cid]');
      if (!btn) return;
      _afToggleContact(parseInt(btn.dataset.cid), btn.dataset.name, btn.dataset.email);
    };
  }

  function _updateBadge() {
    const b = document.getElementById('af-contacts-badge');
    if (!b) return;
    b.textContent    = _selected.size || '';
    b.style.display  = _selected.size ? 'inline-block' : 'none';
  }

  /* ════════════════════════════════════════════════════════════
     VALIDATION
     ════════════════════════════════════════════════════════════ */
  function _validate() {
    const type = document.getElementById('af-type')?.value || '';
    if (!type) { _afAlert('נא לבחור סוג משימה', 'err'); return false; }

    if (type === 'notifyOnChangeTo') {
      if (!document.getElementById('af-status')?.value) {
        _afAlert('נא לבחור סטטוס', 'err'); return false;
      }
      if (!/^\d{6}$/.test(document.getElementById('af-casenum')?.value || '')) {
        _afAlert('מספר קריאה חייב להיות בן 6 ספרות', 'err'); return false;
      }
    }
    if (type === 'techCare') {
      if (!/^\d{6}$/.test(document.getElementById('af-casenum')?.value || '')) {
        _afAlert('מספר קריאה חייב להיות בן 6 ספרות', 'err'); return false;
      }
    }
    if (type === 'openCaseByPhone') {
      const ph = (document.getElementById('af-phone')?.value || '').replace(/\D/g, '');
      if (ph.length !== 10) { _afAlert('נא להזין מספר טלפון תקין (10 ספרות)', 'err'); return false; }
    }
    if (type === 'chechOrderNote') {
      if (!/^\d{6}$/.test(document.getElementById('af-order')?.value || '')) {
        _afAlert('נא להזין מספר הזמנה תקין (6 ספרות)', 'err'); return false;
      }
    }

    const notifyMe = document.getElementById('af-notify-me')?.checked;
    if (!notifyMe && _selected.size === 0) {
      _afAlert('יש לבחור לפחות נמען אחד (שלח אליי או איש קשר)', 'err'); return false;
    }
    return true;
  }

  /* ════════════════════════════════════════════════════════════
     SUBMIT
     ════════════════════════════════════════════════════════════ */
  window._afSubmit = async function () {
    if (!_validate()) return;

    const type     = document.getElementById('af-type').value;
    const notifyMe = document.getElementById('af-notify-me')?.checked;
    const extras   = [..._selected.values()].filter(c => c.email);

    // Build mailto list: me first (if checked) + selected contacts
    const toList = [];
    if (notifyMe) toList.push(MY_EMAIL);
    extras.forEach(c => { if (!toList.includes(c.email)) toList.push(c.email); });

    const mailto = toList[0]  || MY_EMAIL;
    const cc     = toList.slice(1).join(', ');

    const body = new URLSearchParams({
      _csrf:             CSRF_AF,
      typeOfJob:         type,
      mailTo:            mailto,
      Ccmail:            cc,
      msgUser:           document.getElementById('af-msg')?.value || '',
      runevenvaluechange:document.getElementById('af-runeven')?.checked ? 'true' : 'false',
      valueOfType:       document.getElementById('af-casenum')?.value || '',
      conditionOfType:   document.getElementById('af-status')?.value  || '',
      cosCellNum:        (document.getElementById('af-phone')?.value || '').replace(/\D/g, ''),
      orderNum:          document.getElementById('af-order')?.value   || '',
    });

    const sub  = document.getElementById('af-submit');
    const spin = document.getElementById('af-spinner');
    sub.disabled = true;
    if (spin) spin.style.display = 'inline-flex';

    try {
      const r = await fetch(BASE_AF + '/automation/create', {
        method: 'POST', credentials: 'include',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': CSRF_AF },
        body: body.toString(),
      });
      const d = await r.json();
      if (d.ok) {
        _afAlert('✓ ' + d.msg, 'ok');
        if (type === 'chechOrderNote' && d.order_note) {
          document.getElementById('af-order-note-text').textContent = d.order_note;
          document.getElementById('af-order-preview').style.display = 'block';
        }
        if (typeof v2Toast === 'function') v2Toast('✓ ' + d.msg);
        // רענן לוח אוטומציה אם נמצאים בעמוד
        if (typeof window._autLoad === 'function') window._autLoad();
        setTimeout(() => window.closeAutomationModal(), 2000);
      } else {
        _afAlert(d.msg || 'שגיאה לא ידועה', 'err');
      }
    } catch {
      _afAlert('שגיאת תקשורת, נסה שוב', 'err');
    }

    sub.disabled = false;
    if (spin) spin.style.display = 'none';
  };

  /* ════════════════════════════════════════════════════════════
     HELPERS
     ════════════════════════════════════════════════════════════ */
  function _afAlert(msg, type) {
    const el = document.getElementById('af-alert');
    if (!el) return;
    if (!msg) { el.style.display = 'none'; el.className = ''; return; }
    el.style.display = 'block';
    el.className     = type === 'err' ? 'af-err' : type === 'ok' ? 'af-ok' : '';
    el.textContent   = msg;
    if (type === 'err') el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function _esc(s) {
    return String(s || '')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  // ל-data attributes: ניקוי רק גרשיים כפוליים
  function _attr(s) {
    return String(s || '').replace(/"/g, '&quot;');
  }

  function _hash(s) {
    let h = 0;
    for (let i = 0; i < s.length; i++) h = (Math.imul(31, h) + s.charCodeAt(i)) | 0;
    return Math.abs(h);
  }

})();
</script>
