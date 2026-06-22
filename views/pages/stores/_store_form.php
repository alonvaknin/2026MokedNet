<?php
/** @var array $storeTypes @var string $csrf @var string $base */
?>
<div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:640px;max-height:92vh;overflow-y:auto;">
  <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg2);z-index:1;">
    <div id="sf-title" style="font-size:16px;font-weight:700;"></div>
    <button onclick="closeStoreForm()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;line-height:1;">✕</button>
  </div>
  <div style="padding:20px;">
    <input type="hidden" id="sf-id">

    <div class="sf-section" style="--sc:#5b8dee;">
      <div class="sf-section-title"><i class="bi bi-shop"></i> פרטים בסיסיים</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
        <div><label class="flabel">מספר סניף <span id="sf-num-req" style="color:var(--danger)">*</span></label><input id="sf-num" type="text" class="finput" dir="ltr"></div>
        <div><label class="flabel">שם <span style="color:var(--danger)">*</span></label><input id="sf-name" type="text" class="finput"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label class="flabel">סוג</label>
          <select id="sf-type" class="finput">
            <?php foreach ($storeTypes as $t): ?>
              <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:flex;align-items:flex-end;padding-bottom:4px;">
          <label class="sf-toggle-wrap" for="sf-active">
            <input type="checkbox" id="sf-active" checked class="sf-toggle-input">
            <span class="sf-toggle-track">
              <span class="sf-toggle-thumb"></span>
            </span>
            <span class="sf-toggle-label">פעיל</span>
          </label>
        </div>
      </div>
    </div>

    <div class="sf-section" style="--sc:#10b981;">
      <div class="sf-section-title"><i class="bi bi-geo-alt-fill"></i> מיקום</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div><label class="flabel">עיר</label><input id="sf-city" type="text" class="finput"></div>
        <div><label class="flabel">כתובת</label><input id="sf-address" type="text" class="finput"></div>
      </div>
    </div>

    <div class="sf-section" style="--sc:#06b6d4;">
      <div class="sf-section-title"><i class="bi bi-telephone-fill"></i> טלפונים ותקשורת</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
        <div><label class="flabel">טלפון ראשי</label><input id="sf-phone" type="text" class="finput" dir="ltr"></div>
        <div><label class="flabel">טלפון נייד</label><input id="sf-cell" type="text" class="finput" dir="ltr"></div>
      </div>
      <div style="margin-bottom:12px;">
        <label class="flabel">אימייל</label><input id="sf-email" type="email" class="finput" dir="ltr" placeholder="example@domain.com">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div><label class="flabel">שלוחת Mvoice</label><input id="sf-mvoice" type="text" class="finput" dir="ltr"></div>
        <div><label class="flabel">קו טלפון</label><input id="sf-line" type="text" class="finput" dir="ltr"></div>
      </div>
    </div>

    <div class="sf-section" style="--sc:#f59e0b;">
      <div class="sf-section-title"><i class="bi bi-person-fill"></i> מנהל סניף</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div><label class="flabel">שם מנהל</label><input id="sf-mgr-name" type="text" class="finput"></div>
        <div><label class="flabel">טלפון מנהל</label><input id="sf-mgr-cell" type="text" class="finput" dir="ltr"></div>
      </div>
    </div>

    <div class="sf-section" style="--sc:var(--warning);">
      <div class="sf-section-title"><i class="bi bi-exclamation-triangle-fill" style="color:var(--warning);"></i> הערת התראה</div>
      <div style="font-size:11px;color:var(--text3);margin-bottom:8px;">מוצגת בולטת על הכרטיס. עדכון ישמור תאריך ומשתמש.</div>
      <input id="sf-alert" type="text" class="finput" placeholder="לדוגמה: סניף סגור שישי-שבת">
    </div>

    <div class="sf-section" style="--sc:#8b5cf6;">
      <div class="sf-section-title"><i class="bi bi-sticky-fill"></i> הערה פנימית</div>
      <textarea id="sf-note" rows="2" class="finput" style="resize:vertical;"></textarea>
    </div>

    <div id="sf-error" style="color:var(--danger);font-size:13px;margin-bottom:10px;display:none;padding:8px 12px;background:rgba(239,68,68,.1);border-radius:6px;"></div>
    <div style="display:flex;gap:10px;margin-top:4px;">
      <button class="btn btn-primary" style="flex:1;" onclick="submitStoreForm()"><i class="bi bi-check-lg"></i> שמור</button>
      <button class="btn btn-ghost" onclick="closeStoreForm()">ביטול</button>
    </div>
  </div>
</div>

<style>
.sf-section{background:var(--bg3);border:1px solid var(--border);border-right:3px solid var(--sc,var(--accent));border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:12px}
.sf-section-title{font-size:11px;font-weight:700;color:var(--sc,var(--accent));text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;display:flex;align-items:center;gap:6px}
.flabel{display:block;font-size:12px;color:var(--text2);margin-bottom:5px;font-weight:500}
.finput{width:100%;background:var(--bg4);border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--text);font-size:14px;font-family:var(--font);outline:none}
.finput:focus{border-color:var(--accent)}
.sf-toggle-wrap{display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none;}
.sf-toggle-input{position:absolute;opacity:0;width:0;height:0;}
.sf-toggle-track{position:relative;width:48px;height:26px;border-radius:13px;background:var(--border2);transition:background .2s;flex-shrink:0;}
.sf-toggle-input:checked+.sf-toggle-track{background:var(--accent);}
.sf-toggle-thumb{position:absolute;top:3px;right:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.3);transition:right .2s;}
.sf-toggle-input:checked+.sf-toggle-track .sf-toggle-thumb{right:calc(100% - 23px);}
.sf-toggle-label{font-size:14px;font-weight:500;color:var(--text);}
</style>

<script>
const _SF_CSRF  = '<?= htmlspecialchars($csrf ?? '') ?>';
const _SF_BASE  = typeof BASE!=='undefined'?BASE:'<?= rtrim($base ?? '', '/') ?>';

function openStoreForm(s){
  const modal=document.getElementById('store-edit-modal')||document.getElementById('edit-modal');
  if(!modal)return;
  s=s||{};
  document.getElementById('sf-title').textContent=s.id?'עריכה: '+s.name:'סניף חדש';
  document.getElementById('sf-id').value        =s.id||'';
  document.getElementById('sf-num').value        =s.store_num||'';
  document.getElementById('sf-name').value       =s.name||'';
  document.getElementById('sf-type').value       =s.type||'סניף באג';
  document.getElementById('sf-city').value       =s.city||'';
  document.getElementById('sf-address').value    =s.address||'';
  document.getElementById('sf-phone').value      =s.phone_main||'';
  document.getElementById('sf-cell').value       =s.phone_cell||'';
  document.getElementById('sf-email').value      =s.email||'';
  document.getElementById('sf-mgr-name').value   =s.manager_name||'';
  document.getElementById('sf-mgr-cell').value   =s.manager_cell||'';
  document.getElementById('sf-mvoice').value     =s.mvoice_queue||'';
  document.getElementById('sf-line').value       =s.telephone_line_num||'';
  document.getElementById('sf-alert').value      =s.alert_note||'';
  document.getElementById('sf-note').value       =s.note||'';
  document.getElementById('sf-active').checked   =s.id?!!parseInt(s.is_active):true;
  document.getElementById('sf-error').style.display='none';
  _sfUpdateNumReq();
  modal.style.display='flex';
  setTimeout(()=>document.getElementById('sf-name').focus(),60);
}
window.fillStoreForm=openStoreForm;

function _sfUpdateNumReq(){
  const isBug=document.getElementById('sf-type').value==='סניף באג';
  document.getElementById('sf-num-req').style.display=isBug?'':'none';
}
document.getElementById('sf-type').addEventListener('change',_sfUpdateNumReq);

function closeStoreForm(){
  const m=document.getElementById('store-edit-modal')||document.getElementById('edit-modal');
  if(m)m.style.display='none';
}

async function submitStoreForm(){
  const name=document.getElementById('sf-name').value.trim();
  const num=document.getElementById('sf-num').value.trim();
  const err=document.getElementById('sf-error');
  const type=document.getElementById('sf-type').value;
  const numRequired=type==='סניף באג';
  if(!name){err.textContent='שם הוא שדה חובה';err.style.display='block';return;}
  if(numRequired&&!num){err.textContent='מספר סניף הוא שדה חובה עבור סניף באג';err.style.display='block';return;}
  err.style.display='none';
  const body=new URLSearchParams({
    _csrf:_SF_CSRF,id:document.getElementById('sf-id').value,
    store_num:num||'',name,type:document.getElementById('sf-type').value,
    city:document.getElementById('sf-city').value.trim(),
    address:document.getElementById('sf-address').value.trim(),
    phone_main:document.getElementById('sf-phone').value.trim(),
    phone_cell:document.getElementById('sf-cell').value.trim(),
    email:document.getElementById('sf-email').value.trim(),
    manager_name:document.getElementById('sf-mgr-name').value.trim(),
    manager_cell:document.getElementById('sf-mgr-cell').value.trim(),
    mvoice_queue:document.getElementById('sf-mvoice').value.trim(),
    telephone_line_num:document.getElementById('sf-line').value.trim(),
    alert_note:document.getElementById('sf-alert').value.trim(),
    note:document.getElementById('sf-note').value.trim(),
    is_active:document.getElementById('sf-active').checked?'1':'0',
  });
  const res=await fetch(_SF_BASE+'/stores/save',{method:'POST',body});
  const data=await res.json();
  if(data.ok){
    closeStoreForm();
    if(typeof v2Toast==='function') v2Toast('✓ נשמר בהצלחה');
    setTimeout(()=>location.reload(), 1200);
  } else {
    err.textContent=data.error||data['שגיאה']||'שגיאה בשמירה';
    err.style.display='block';
  }
}

['store-edit-modal','edit-modal'].forEach(id=>{
  const el=document.getElementById(id);
  if(el)el.addEventListener('click',e=>{if(e.target===el)closeStoreForm();});
});
</script>
