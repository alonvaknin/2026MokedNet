<?php
use Core\View;
$base = rtrim(CFG['app']['url'],'/');
$csrf = $_SESSION['csrf_token'] ?? '';
?>

<div id="fmt-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:600;
     align-items:flex-start;justify-content:center;padding:46px 16px 16px;">
  <div id="fmt-modal-inner" style="background:var(--bg2);border:1px solid var(--border);
       border-radius:var(--radius);width:100%;max-width:920px;max-height:90vh;display:flex;flex-direction:column;
       position:relative;">

    <!-- header (drag handle) -->
    <div id="fmt-modal-header" style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--border);
         position:sticky;top:0;background:var(--bg2);z-index:1;border-radius:var(--radius) var(--radius) 0 0;flex-shrink:0;
         cursor:grab;user-select:none;">
      <i class="bi bi-file-earmark-text-fill" style="color:var(--accent);font-size:16px;flex-shrink:0;pointer-events:none;"></i>
      <div style="flex:1;pointer-events:none;">
        <div style="font-size:14px;font-weight:700;">פורמטר — תבניות טקסט</div>
        <div id="fmt-subtitle" style="font-size:11px;color:var(--text3);">בחר תבנית מהרשימה</div>
      </div>
      <!-- S/M/L size buttons -->
      <div class="fmt-size-btns" style="pointer-events:auto;">
        <button class="fmt-size-btn" id="fmt-size-s" onclick="fmtSetSize('s')" title="קטן">S</button>
        <button class="fmt-size-btn" id="fmt-size-m" onclick="fmtSetSize('m')" title="בינוני">M</button>
        <button class="fmt-size-btn" id="fmt-size-l" onclick="fmtSetSize('l')" title="גדול">L</button>
      </div>
      <!-- center button -->
      <button onclick="fmtCenterModal()" title="מרכז" style="pointer-events:auto;background:none;border:1px solid var(--border);
              color:var(--text3);font-size:13px;cursor:pointer;padding:3px 7px;border-radius:6px;line-height:1;flex-shrink:0;"
              onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--text3)'">
        <i class="bi bi-arrows-fullscreen"></i>
      </button>
      <button id="fmt-back-btn" onclick="fmtBackToList()"
              style="pointer-events:auto;display:none;background:none;border:1px solid var(--border);cursor:pointer;
                     color:var(--text2);font-size:12px;padding:4px 10px;border-radius:6px;">
        <i class="bi bi-arrow-right"></i> חזרה
      </button>
      <button onclick="closeFmtModal()" style="pointer-events:auto;background:none;border:none;color:var(--text2);
              font-size:22px;cursor:pointer;line-height:1;flex-shrink:0;">✕</button>
    </div>

    <!-- body -->
    <div style="display:flex;flex:1;overflow:hidden;min-height:0;">

      <!-- LEFT: template list -->
      <div id="fmt-list-panel" style="width:230px;flex-shrink:0;border-left:1px solid var(--border);
           overflow-y:auto;background:var(--bg3);padding:8px;">
        <div style="padding:10px 14px;font-size:11px;color:var(--text3);">טוען...</div>
      </div>

      <!-- RIGHT -->
      <div style="flex:1;overflow-y:auto;display:flex;flex-direction:column;min-width:0;">

        <!-- placeholder -->
        <div id="fmt-placeholder" style="flex:1;display:flex;align-items:center;justify-content:center;
             color:var(--text3);padding:40px;">
          <div style="text-align:center;">
            <i class="bi bi-file-earmark-text" style="font-size:44px;opacity:.2;display:block;margin-bottom:10px;"></i>
            <div>בחר תבנית מהרשימה</div>
          </div>
        </div>

        <!-- active template -->
        <div id="fmt-tpl-ui" style="display:none;padding:16px;flex:1;flex-direction:column;">

          <!-- gender -->
          <div id="fmt-gender-wrap" style="display:none;margin-bottom:14px;">
            <div id="fmt-gender-warn" style="display:flex;align-items:center;gap:8px;padding:8px 14px;
                 border-radius:8px;border:1.5px solid #f59e0b;background:rgba(245,158,11,.1);margin-bottom:8px;
                 animation:genderPulse 1.4s ease-in-out infinite;">
              <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b;font-size:16px;flex-shrink:0;"></i>
              <span style="font-size:13px;font-weight:600;color:#f59e0b;">יש לבחור לשון פנייה לפני סיום</span>
            </div>
            <div style="display:flex;gap:8px;">
              <button type="button" id="fmt-btn-male" onclick="fmtSetGender('male')"
                      style="flex:1;padding:10px 16px;border-radius:10px;border:2px solid var(--border);
                             background:var(--bg3);cursor:pointer;font-family:var(--font);font-size:14px;
                             font-weight:600;color:var(--text2);transition:all .15s;display:flex;
                             align-items:center;justify-content:center;gap:8px;">
                <i class="bi bi-gender-male" style="font-size:18px;"></i> זכר
              </button>
              <button type="button" id="fmt-btn-female" onclick="fmtSetGender('female')"
                      style="flex:1;padding:10px 16px;border-radius:10px;border:2px solid var(--border);
                             background:var(--bg3);cursor:pointer;font-family:var(--font);font-size:14px;
                             font-weight:600;color:var(--text2);transition:all .15s;display:flex;
                             align-items:center;justify-content:center;gap:8px;">
                <i class="bi bi-gender-female" style="font-size:18px;"></i> נקבה
              </button>
            </div>
          </div>

          <!-- built-in: name + phone -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
            <div>
              <label class="fmt-lbl">שם לקוח</label>
              <input id="fmt-cname" class="fmt-inp" type="text" placeholder="שם מלא" oninput="fmtPreview()">
            </div>
            <div>
              <label class="fmt-lbl">טלפון</label>
              <input id="fmt-cphone" class="fmt-inp" type="tel" placeholder="05X-XXXXXXX" oninput="fmtPreview()">
            </div>
          </div>

          <!-- dynamic fields -->
          <div id="fmt-dyn-fields" style="margin-bottom:12px;"></div>

          <!-- preview -->
          <div style="margin-bottom:12px;">
            <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;
                 letter-spacing:.06em;margin-bottom:5px;">תצוגה מקדימה</div>
            <div id="fmt-preview" style="background:var(--bg3);border:1px solid var(--border);
                 border-radius:8px;padding:12px;font-size:13px;line-height:1.8;color:var(--text);
                 white-space:pre-wrap;direction:rtl;min-height:80px;max-height:260px;overflow-y:auto;"></div>
          </div>

          <!-- actions -->
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn btn-primary" onclick="fmtCopy()" id="fmt-copy-btn">
              <i class="bi bi-clipboard"></i> העתק טקסט
            </button>
            <button class="btn btn-ghost" id="fmt-mail-btn" onclick="fmtMail()" style="display:none;">
              <i class="bi bi-envelope"></i> העתק ושלח מייל
            </button>
          </div>


          <!-- invoice change name form -->
          <div id="fmt-icn-form" style="display:none;margin-top:14px;border-top:1px solid var(--border);padding-top:14px;">
            <fieldset style="border:1px solid var(--border);border-radius:var(--radius);padding:14px;">
              <legend style="padding:0 8px;font-size:13px;font-weight:700;color:var(--text2);">בקשת שינוי שם בחשבונית</legend>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                <div>
                  <label class="fmt-lbl">מספר חשבונית סאפ <span style="color:var(--danger);">*</span></label>
                  <input id="fmt-icn-invoice" class="fmt-inp" type="text" autocomplete="off" placeholder="9 ספרות">
                </div>
                <div>
                  <label class="fmt-lbl">שם חדש על-גבי החשבונית <span style="color:var(--danger);">*</span></label>
                  <input id="fmt-icn-newname" class="fmt-inp" type="text" placeholder="עד 50 תווים">
                </div>
              </div>
              <div style="margin-bottom:10px;">
                <label class="fmt-lbl">הערה (לא חשוף ללקוח) — לא חובה</label>
                <input id="fmt-icn-note" class="fmt-inp" type="text">
              </div>
              <button type="button" id="fmt-icn-submit-btn"
                      onclick="fmtIcnSubmit()"
                      style="background:#f9a825;color:#000;border:none;border-radius:8px;
                             padding:9px 20px;font-weight:700;cursor:pointer;font-size:14px;
                             font-family:var(--font);">
                <i class="bi bi-send"></i> שלח בקשת שינוי שם
              </button>
            </fieldset>
          </div>

        </div><!-- /fmt-tpl-ui -->
      </div><!-- /right -->
    </div><!-- /body -->
  </div><!-- /inner -->
</div><!-- /modal -->

<style>
@keyframes genderPulse{0%,100%{opacity:1}50%{opacity:.55}}
.fmt-lbl{display:block;font-size:12px;color:var(--text2);margin-bottom:4px;font-weight:500;}
.fmt-inp{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;
         padding:7px 11px;color:var(--text);font-size:13px;font-family:var(--font);outline:none;}
.fmt-inp:focus{border-color:var(--accent);}

/* ── Category cards ── */
.fmt-cat-card{border-radius:10px;overflow:hidden;border:1.5px solid var(--fmt-cat-c,var(--border));
              margin-bottom:8px;background:var(--bg2);}
.fmt-cat-hdr{padding:9px 13px;font-size:12px;font-weight:800;
             text-transform:uppercase;letter-spacing:.07em;
             color:var(--fmt-cat-c,var(--text3));
             background:var(--fmt-cat-bg,var(--bg4));
             display:flex;align-items:center;gap:8px;}
.fmt-cat-hdr::before{content:'';display:inline-block;width:8px;height:8px;flex-shrink:0;
  border-radius:50%;background:var(--fmt-cat-c,var(--text3));}

.fmt-tpl-btn{display:block;width:100%;text-align:right;background:none;border:none;
             border-top:1px solid color-mix(in srgb,var(--fmt-item-c) 15%,transparent);
             padding:9px 13px 9px 16px;cursor:pointer;
             color:var(--text2);font-size:13.5px;font-family:var(--font);
             transition:background .12s,color .12s;
             border-right:3px solid transparent;}
.fmt-tpl-btn:first-of-type{border-top:none;}
.fmt-tpl-btn:hover{background:var(--fmt-item-bg);color:var(--fmt-item-c);
                   border-right-color:var(--fmt-item-c);}
.fmt-tpl-btn.active{background:var(--fmt-item-bg);color:var(--fmt-item-c);font-weight:700;
                    border-right:4px solid var(--fmt-item-c);}

.fmt-gender-male-active{background:rgba(91,141,238,.15)!important;border-color:var(--accent)!important;color:var(--accent)!important;}
.fmt-gender-female-active{background:rgba(236,72,153,.12)!important;border-color:#ec4899!important;color:#ec4899!important;}

/* ── Size buttons ── */
.fmt-size-btns{display:flex;align-items:center;gap:2px;background:var(--bg4);
  border:1px solid var(--border);border-radius:7px;padding:2px;}
.fmt-size-btn{width:26px;height:22px;border:none;border-radius:5px;background:transparent;
  color:var(--text3);font-size:11px;font-weight:700;cursor:pointer;font-family:var(--font);
  transition:background .13s,color .13s;}
.fmt-size-btn:hover{background:var(--bg2);color:var(--text);}
.fmt-size-btn.active{background:var(--accent);color:#fff;}

/* ── Font sizes by modal size ── */
#fmt-modal-inner.fmt-modal-s .fmt-inp,
#fmt-modal-inner.fmt-modal-s #fmt-preview{font-size:11px;}
#fmt-modal-inner.fmt-modal-s .fmt-lbl{font-size:11px;}
#fmt-modal-inner.fmt-modal-s .fmt-tpl-btn{font-size:12px;padding:7px 14px 7px 18px;}
#fmt-modal-inner.fmt-modal-s .fmt-cat-hdr{font-size:9px;}

#fmt-modal-inner.fmt-modal-l .fmt-inp,
#fmt-modal-inner.fmt-modal-l #fmt-preview{font-size:15px;}
#fmt-modal-inner.fmt-modal-l .fmt-lbl{font-size:13px;}
#fmt-modal-inner.fmt-modal-l .fmt-tpl-btn{font-size:14px;}
#fmt-modal-inner.fmt-modal-l .fmt-cat-hdr{font-size:11px;}

/* ── Drag cursor ── */
#fmt-modal-header:active{cursor:grabbing;}

/* inventory popup table */
.fmt-inv-popup table{width:100%;border-collapse:collapse;font-size:12px;}
.fmt-inv-popup th{background:var(--bg3);color:var(--text3);font-size:10px;font-weight:700;padding:6px 10px;border-bottom:1px solid var(--border);text-align:right;}
.fmt-inv-popup td{padding:7px 10px;border-bottom:1px solid var(--border);color:var(--text2);}
.fmt-inv-popup tr:hover td{background:var(--bg3);}
</style>

<script>
const _FBASE = typeof BASE!=='undefined'?BASE:'<?= $base ?>';
const _FMT_CSRF = '<?= htmlspecialchars($csrf) ?>';
let _fmtTpls={}, _fmtCur=null, _fmtStores=[], _fmtProdTimers={};
let _fmtGender = null;
let _fmtPrefillEmail = null;
let _fmtPrefillTpl = null;

/* ── Open / Close ─────────────────────────────────────────────────────────── */
function openFormatterModal(prefill){
  document.getElementById('fmt-modal').style.display='flex';
  _fmtLoadList();
  if(prefill){
    if(prefill.name)  document.getElementById('fmt-cname').value =prefill.name;
    if(prefill.phone) document.getElementById('fmt-cphone').value=prefill.phone;
    _fmtPrefillEmail = prefill.email || null;
    _fmtPrefillTpl   = prefill.tpl   || null;
  }
}
function closeFmtModal(){
  document.getElementById('fmt-modal').style.display='none';
  document.querySelectorAll('.fmt-inv-popup').forEach(el=>el.remove());
  if (window.CRM && window.CRM.getState().minimized) window.CRM.restore();
}
document.getElementById('fmt-modal').addEventListener('click',e=>{
  if(e.target===document.getElementById('fmt-modal'))closeFmtModal();
});

/* ── Gender ───────────────────────────────────────────────────────────────── */
function fmtSetGender(g){
  _fmtGender=g;
  const mb=document.getElementById('fmt-btn-male');
  const fb=document.getElementById('fmt-btn-female');
  mb.className = g==='male'  ? 'fmt-gender-male-active'   : '';
  fb.className = g==='female'? 'fmt-gender-female-active' : '';
  const baseStyle='flex:1;padding:10px 16px;border-radius:10px;cursor:pointer;font-family:var(--font);font-size:14px;font-weight:600;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:8px;';
  if(g==='male'){
    mb.style.cssText=baseStyle+'background:rgba(91,141,238,.15);border:2px solid var(--accent);color:var(--accent);';
    fb.style.cssText=baseStyle+'background:var(--bg3);border:2px solid var(--border);color:var(--text2);';
  }else{
    fb.style.cssText=baseStyle+'background:rgba(236,72,153,.12);border:2px solid #ec4899;color:#ec4899;';
    mb.style.cssText=baseStyle+'background:var(--bg3);border:2px solid var(--border);color:var(--text2);';
  }
  const warn=document.getElementById('fmt-gender-warn');
  if(warn)warn.style.display='none';
  fmtPreview();
}

/* ── Load list ────────────────────────────────────────────────────────────── */
async function _fmtLoadList(){
  if(Object.keys(_fmtTpls).length){_fmtRenderList();return;}
  try{
    const r=await fetch(_FBASE+'/api/formatter/list');
    _fmtTpls=await r.json();
    _fmtRenderList();
  }catch(e){
    document.getElementById('fmt-list-panel').innerHTML=
      '<div style="padding:12px;color:var(--danger);font-size:12px;">שגיאה בטעינה</div>';
  }
}
const _FMT_CAT_COLORS=[
  {c:'#5b8dee',bg:'rgba(91,141,238,.12)'},
  {c:'#10b981',bg:'rgba(16,185,129,.12)'},
  {c:'#f59e0b',bg:'rgba(245,158,11,.12)'},
  {c:'#ec4899',bg:'rgba(236,72,153,.12)'},
  {c:'#8b5cf6',bg:'rgba(139,92,246,.12)'},
  {c:'#06b6d4',bg:'rgba(6,182,212,.12)'},
  {c:'#f97316',bg:'rgba(249,115,22,.12)'},
  {c:'#84cc16',bg:'rgba(132,204,22,.12)'},
];
function _fmtRenderList(){
  const p=document.getElementById('fmt-list-panel');
  let h=''; let ci=0;
  for(const[cat,arr] of Object.entries(_fmtTpls)){
    const col=_FMT_CAT_COLORS[ci%_FMT_CAT_COLORS.length];
    h+=`<div class="fmt-cat-card" style="--fmt-cat-c:${col.c};--fmt-cat-bg:${col.bg};">`;
    h+=`<div class="fmt-cat-hdr">${_fe(cat)}</div>`;
    arr.forEach(t=>{
      h+=`<button class="fmt-tpl-btn" data-id="${t.id}" data-cat="${ci}"
            style="--fmt-item-c:${col.c};--fmt-item-bg:${col.bg};"
            onclick="_fmtSelect(${t.id})">${_fe(t.name)}</button>`;
    });
    h+=`</div>`;
    ci++;
  }
  p.innerHTML=h||'<div style="padding:12px;color:var(--text3);">אין תבניות</div>';
    if(_fmtPrefillTpl){
    for(const[,arr] of Object.entries(_fmtTpls)){
      const match=arr.find(t=>t.name===_fmtPrefillTpl);
      if(match){ _fmtSelect(match.id); break; }
    }
    _fmtPrefillTpl=null;
  }
}

/* ── Select template ──────────────────────────────────────────────────────── */
async function _fmtSelect(id){
  document.querySelectorAll('.fmt-tpl-btn').forEach(b=>b.classList.toggle('active',Number(b.dataset.id)===id));
  try{
    const r=await fetch(_FBASE+'/api/formatter/template?id='+id);
    _fmtCur=await r.json();
  }catch(e){return;}

  _fmtGender=null;
  const mb=document.getElementById('fmt-btn-male');
  const fb=document.getElementById('fmt-btn-female');
  const baseStyle='flex:1;padding:10px 16px;border-radius:10px;border:2px solid var(--border);background:var(--bg3);cursor:pointer;font-family:var(--font);font-size:14px;font-weight:600;color:var(--text2);transition:all .15s;display:flex;align-items:center;justify-content:center;gap:8px;';
  if(mb)mb.style.cssText=baseStyle;
  if(fb)fb.style.cssText=baseStyle;

  document.getElementById('fmt-placeholder').style.display='none';
  const ui=document.getElementById('fmt-tpl-ui');
  ui.style.display='flex'; ui.style.flexDirection='column';
  document.getElementById('fmt-subtitle').textContent=_fmtCur.name;

  const gWrap=document.getElementById('fmt-gender-wrap');
  gWrap.style.display=_fmtCur.body_female?'block':'none';
  if(_fmtCur.body_female){
    const warn=document.getElementById('fmt-gender-warn');
    if(warn)warn.style.display='flex';
  }

  document.getElementById('fmt-mail-btn').style.display=
    (_fmtCur.mail_to||_fmtCur.mail_subject)?'inline-flex':'none';

  // Remove any leftover inventory buttons
  document.querySelectorAll('[id^="fmt-inv-btn-"]').forEach(el=>el.remove());
  document.querySelectorAll('.fmt-inv-popup').forEach(el=>el.remove());

  await _fmtBuildDynFields();
  _fmtShowIcnForm(_fmtCur);
  fmtPreview();
}

/* ── Dynamic fields ───────────────────────────────────────────────────────── */
async function _fmtBuildDynFields(){
  const c=document.getElementById('fmt-dyn-fields');
  if(!_fmtCur.fields||!_fmtCur.fields.length){c.innerHTML='';return;}
  if(!_fmtStores.length){
    try{ const r=await fetch(_FBASE+'/api/formatter/stores'); _fmtStores=await r.json(); }catch(e){}
  }
  let h='<div style="display:flex;flex-direction:column;gap:10px;">';
  for(const f of _fmtCur.fields){
    const req=f.required?'<span style="color:var(--danger);">*</span>':'';
    h+=`<div id="fmt-field-wrap-${f.field_key}"><label class="fmt-lbl">${_fe(f.label)}${req}</label>`;
    switch(f.field_type){
      case 'text':case 'tel':case 'email':case 'number':
        h+=`<input id="fmt-f-${f.field_key}" class="fmt-inp" type="${f.field_type}" placeholder="${_fe(f.placeholder||'')}" oninput="fmtPreview()">`;break;
      case 'textarea':
        h+=`<textarea id="fmt-f-${f.field_key}" class="fmt-inp" rows="3" placeholder="${_fe(f.placeholder||'')}" oninput="fmtPreview()"></textarea>`;break;
      case 'date':
        h+=`<input id="fmt-f-${f.field_key}" class="fmt-inp" type="date" oninput="fmtPreview()">`;break;
      case 'checkbox':
        h+=`<label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;"><input id="fmt-f-${f.field_key}" type="checkbox" style="accent-color:var(--accent);" onchange="fmtPreview()"> ${_fe(f.placeholder||f.label)}</label>`;break;
      case 'radio':{
        const opts=f.options||[];
        h+=`<div style="display:flex;flex-wrap:wrap;gap:10px;">`;
        opts.forEach(o=>{ h+=`<label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:13px;"><input type="radio" name="fmt-r-${f.field_key}" value="${_fe(o.value)}" onchange="fmtPreview()" style="accent-color:var(--accent);"> ${_fe(o.label)}</label>`; });
        h+=`</div>`;break;}
      case 'select':{
        const opts=f.options||[];
        h+=`<select id="fmt-f-${f.field_key}" class="fmt-inp" onchange="fmtPreview()"><option value="">— בחר —</option>`;
        opts.forEach(o=>{ h+=`<option value="${_fe(o.value)}">${_fe(o.label)}</option>`; });
        h+=`</select>`;break;}
      case 'store_select':
        h+=`<select id="fmt-f-${f.field_key}" class="fmt-inp" onchange="fmtPreview()"><option value="">— בחר חנות —</option>`;
        _fmtStores.forEach(s=>{ h+=`<option value="${_fe(s.label)}" data-mail="${_fe(s.mail||'')}">${_fe(s.label)}</option>`; });
        h+=`</select>`;break;
      case 'product_search':
        h+=`<div style="position:relative;">`;
        h+=`<input id="fmt-f-${f.field_key}" class="fmt-inp" type="text" autocomplete="off"
                   placeholder="${_fe(f.placeholder||'חפש מקט או שם מוצר')}"
                   oninput="_fmtProdAC(this,'fmt-drop-${f.field_key}')">`;
        h+=`<div id="fmt-drop-${f.field_key}" style="display:none;position:absolute;top:100%;right:0;left:0;
                 background:var(--bg2);border:1px solid var(--border);border-radius:0 0 8px 8px;z-index:9999;
                 max-height:220px;overflow-y:auto;box-shadow:var(--shadow);"></div>`;
        h+=`</div>`;break;
    }
    h+=`</div>`;
  }
  h+='</div>';
  c.innerHTML=h;
  if(_fmtPrefillEmail){
    const mailEl=document.getElementById('fmt-f-email')||document.getElementById('fmt-f-costumerMail');
    if(mailEl){ mailEl.value=_fmtPrefillEmail; fmtPreview(); }
  }
}

/* ── Product autocomplete + inventory check ───────────────────────────────── */
async function _fmtProdAC(inp,dropId){
  const q=inp.value.trim(); const drop=document.getElementById(dropId);
  if(!drop)return;
  clearTimeout(_fmtProdTimers[dropId]);
  if(q.length<3){drop.style.display='none'; fmtPreview(); return;}
  _fmtProdTimers[dropId]=setTimeout(async()=>{
    try{
      const r=await fetch(_FBASE+'/api/products?query='+encodeURIComponent(q));
      const data=await r.json();
      if(!Array.isArray(data)||!data.length){drop.style.display='none';return;}
      drop.innerHTML=data.slice(0,10).map(p=>`
        <div onclick="_fmtPickProd('${inp.id}','${dropId}','${_feJs((p.barcode||'')+' '+(p.description||''))}',${p.bugid||0})"
             style="padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border);"
             onmouseover="this.style.background='var(--bg3)'" onmouseout="this.style.background=''">
          <div style="display:flex;align-items:center;gap:6px;justify-content:space-between;">
            <span>
              <strong>${_fe(p.description)}</strong>
              <span style="font-size:11px;color:var(--text3);margin-right:6px;">${_fe(p.barcode)}</span>
            </span>
            ${p.user&&p.user!=='לא צוין איש רכש'
              ? `<span style="font-size:10px;color:var(--text3);white-space:nowrap;">${_fe(p.user)}</span>`
              : ''}
          </div>
        </div>`).join('');
      drop.style.display='block';
    }catch(e){}
  },420);
  fmtPreview();
}

function _fmtPickProd(inpId,dropId,val,bugid){
  const inp=document.getElementById(inpId); if(inp)inp.value=val;
  const drop=document.getElementById(dropId); if(drop)drop.style.display='none';
  // הסר כפתור קודם ואז הצג חדש אם יש bugid
  const invBtnId='fmt-inv-btn-'+inpId.replace('fmt-f-','');
  const existing=document.getElementById(invBtnId);
  if(existing) existing.remove();
  if(bugid){
    const btn=document.createElement('button');
    btn.id=invBtnId; btn.type='button';
    btn.style.cssText='display:inline-flex;align-items:center;gap:5px;margin-top:6px;padding:4px 12px;font-size:12px;font-family:var(--font);background:rgba(91,141,238,.1);border:1px solid rgba(91,141,238,.3);border-radius:6px;color:var(--accent);cursor:pointer;transition:all .13s;';
    btn.innerHTML='<i class="bi bi-binoculars"></i> בדיקת מלאי';
    btn.onmouseover=()=>btn.style.background='rgba(91,141,238,.2)';
    btn.onmouseout=()=>btn.style.background='rgba(91,141,238,.1)';
    btn.onclick=()=>_fmtCheckInventory(bugid,btn);
    // הכנס אחרי ה-input wrapper
    inp.closest('[id^="fmt-field-wrap"]')?.appendChild(btn)
      || inp.parentNode.insertAdjacentElement('afterend',btn);
  }
  fmtPreview();
}

/* ── Inventory popup ──────────────────────────────────────────────────────── */
async function _fmtCheckInventory(bugid,btn){
  btn.disabled=true;
  btn.innerHTML='<div style="width:13px;height:13px;border-radius:50%;border:2px solid rgba(91,141,238,.3);border-top-color:var(--accent);animation:pbxspin .7s linear infinite;flex-shrink:0;"></div> טוען...';
  try{
    const r=await fetch(_FBASE+'/api/inventory?itemid='+bugid);
    const data=await r.json();
    document.querySelectorAll('.fmt-inv-popup').forEach(el=>el.remove());
    if(data.error){
      btn.innerHTML='<i class="bi bi-binoculars"></i> בדיקת מלאי';
      btn.disabled=false;
      if(typeof v2Toast!=='undefined') v2Toast(data.error);
      return;
    }
    const popup=document.createElement('div');
    popup.className='fmt-inv-popup';
    popup.style.cssText='position:fixed;z-index:9999;background:var(--bg2);border:1px solid var(--border2);border-radius:10px;box-shadow:0 16px 48px rgba(0,0,0,.6);padding:14px 16px;max-width:500px;width:92vw;max-height:72vh;overflow-y:auto;direction:rtl;font-family:var(--font);';
    popup.innerHTML='<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">'
      +'<strong style="font-size:14px;display:flex;align-items:center;gap:7px;"><i class="bi bi-box-seam" style="color:var(--accent);"></i> מלאי</strong>'
      +'<button onclick="this.closest(\'.fmt-inv-popup\').remove()" style="background:none;border:none;color:var(--text2);font-size:18px;cursor:pointer;line-height:1;">✕</button>'
      +'</div>'
      +(data.table||'<div style="color:var(--text3);font-size:13px;">אין נתוני מלאי</div>');
    document.body.appendChild(popup);
    // מיקום ליד הכפתור
    const rect=btn.getBoundingClientRect();
    const popW=Math.min(500, window.innerWidth*0.92);
    const leftPos=Math.max(8, Math.min(rect.left, window.innerWidth-popW-8));
    const topPos=rect.bottom+8;
    popup.style.top=(topPos > window.innerHeight*0.6 ? rect.top-popup.offsetHeight-8 : topPos)+'px';
    popup.style.left=leftPos+'px';
    // סגור בלחיצה חיצונית
    setTimeout(()=>document.addEventListener('click',function closeInv(e){
      if(!popup.contains(e.target)&&e.target!==btn){
        popup.remove();
        document.removeEventListener('click',closeInv);
      }
    },true),50);
  }catch(e){
    if(typeof v2Toast!=='undefined') v2Toast('שגיאה בטעינת מלאי');
  }
  btn.innerHTML='<i class="bi bi-binoculars"></i> בדיקת מלאי';
  btn.disabled=false;
}

/* ── Get field value ──────────────────────────────────────────────────────── */
function _fmtVal(f){
  switch(f.field_type){
    case 'radio':{const r=document.querySelector(`[name="fmt-r-${f.field_key}"]:checked`);return r?r.value:'';}
    case 'checkbox':{const cb=document.getElementById('fmt-f-'+f.field_key);return cb&&cb.checked?f.label:'';}
    default:{const el=document.getElementById('fmt-f-'+f.field_key);return el?el.value:'';}
  }
}

/* ── Preview ──────────────────────────────────────────────────────────────── */
function fmtPreview(){
  if(!_fmtCur)return;
  const gender=_fmtGender||'male';
  const body=(gender==='female'&&_fmtCur.body_female)?_fmtCur.body_female:_fmtCur.body_male;
  const h=new Date().getHours();
  const ts=h<12?'בוקר טוב':h<17?'צהרים טובים':h<19?'ערב טוב':'לילה טוב';
  let text=body||'';
  text=text.replace(/\[\[time_state\]\]/g,ts)
           .replace(/\[\[customer_name\]\]/g,  document.getElementById('fmt-cname')?.value||'')
           .replace(/\[\[customer_phone\]\]/g, document.getElementById('fmt-cphone')?.value||'');
  if(_fmtCur.fields){
    _fmtCur.fields.forEach(f=>{
      text=text.replaceAll(`[[${f.field_key}]]`, _fmtVal(f));
    });
  }
  document.getElementById('fmt-preview').textContent=text;
}

/* ── Copy ─────────────────────────────────────────────────────────────────── */
function fmtCopy(){
  if(_fmtCur?.body_female && !_fmtGender){
    const warn=document.getElementById('fmt-gender-warn');
    if(warn){warn.style.display='flex';warn.style.animation='none';setTimeout(()=>warn.style.animation='genderPulse 1.4s ease-in-out infinite',10);}
    document.getElementById('fmt-gender-wrap').scrollIntoView({behavior:'smooth',block:'nearest'});
    return;
  }
  const text=document.getElementById('fmt-preview')?.textContent||'';
  navigator.clipboard.writeText(text).then(()=>{
    const btn=document.getElementById('fmt-copy-btn');
    const orig=btn.innerHTML;
    btn.innerHTML='<i class="bi bi-check-lg"></i> הועתק!';
    setTimeout(()=>btn.innerHTML=orig,2000);
    if(typeof v2Toast==='function') v2Toast('טקסט הועתק ✓');
  });
}

/* ── Mail ─────────────────────────────────────────────────────────────────── */
function fmtMail(){
  if(_fmtCur?.body_female && !_fmtGender){fmtCopy();return;}
  fmtCopy();
  if(!_fmtCur)return;
  let to=_fmtCur.mail_to||'';
  let rawSubj=_fmtCur.mail_subject||'';
  if(_fmtCur.fields){
    _fmtCur.fields.forEach(f=>{
      const el = document.getElementById('fmt-f-'+f.field_key);
      if(f.field_type==='store_select'){
        const mail = el?.selectedOptions[0]?.dataset?.mail||'';
        if(mail) to = to ? (to+';'+mail) : mail;
      }
      if (el) {
        const val = el.value || '';
        rawSubj = rawSubj.replace(new RegExp(`\\[${f.field_key}\\]`, 'g'), val);
      }
    });
  }
  const cname=document.getElementById('fmt-cname')?.value||'';
  const cphone=document.getElementById('fmt-cphone')?.value||'';
  rawSubj = rawSubj.replace(/\[cname\]/g, cname).replace(/\[cphone\]/g, cphone);
  const subj=encodeURIComponent(rawSubj);
  const cc=encodeURIComponent(_fmtCur.mail_cc||'');
  window.open(`mailto:${encodeURIComponent(to)}?cc=${cc}&subject=${subj}`, '_blank');
}

/* ── Back ─────────────────────────────────────────────────────────────────── */
function fmtBackToList(){
  document.getElementById('fmt-tpl-ui').style.display='none';
  document.getElementById('fmt-placeholder').style.display='flex';
  document.querySelectorAll('.fmt-tpl-btn').forEach(b=>b.classList.remove('active'));
  document.querySelectorAll('[id^="fmt-inv-btn-"]').forEach(el=>el.remove());
  document.querySelectorAll('.fmt-inv-popup').forEach(el=>el.remove());
  _fmtCur=null; _fmtGender=null;
}

/* ── Utils ────────────────────────────────────────────────────────────────── */
function _fe(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function _feJs(s){return String(s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'")}

/* ── Modal size S/M/L ─────────────────────────────────────────────────────── */
const _FMT_SIZES={s:{w:680,mxh:'80vh'},m:{w:920,mxh:'90vh'},l:{w:1200,mxh:'94vh'}};
const _FMT_STORE='fmt_modal_prefs';
function _fmtLoadPrefs(){try{return JSON.parse(localStorage.getItem(_FMT_STORE)||'{}');}catch(e){return{};}}
function _fmtSavePrefs(patch){localStorage.setItem(_FMT_STORE,JSON.stringify({..._fmtLoadPrefs(),...patch}));}
function fmtSetSize(sz){
  const inner=document.getElementById('fmt-modal-inner');
  const dims=_FMT_SIZES[sz]||_FMT_SIZES.m;
  inner.style.maxWidth=Math.min(dims.w,window.innerWidth-32)+'px';
  inner.style.maxHeight=dims.mxh;
  inner.classList.remove('fmt-modal-s','fmt-modal-m','fmt-modal-l');
  inner.classList.add('fmt-modal-'+sz);
  ['s','m','l'].forEach(s=>{
    const b=document.getElementById('fmt-size-'+s);
    if(b) b.classList.toggle('active',s===sz);
  });
  _fmtSavePrefs({size:sz});
}
function fmtCenterModal(){
  const inner=document.getElementById('fmt-modal-inner');
  inner.style.position='';inner.style.left='';inner.style.top='';inner.style.margin='';
  inner.classList.remove('fmt-dragged');
  const overlay=document.getElementById('fmt-modal');
  overlay.style.alignItems='flex-start';
  overlay.style.justifyContent='center';
  _fmtSavePrefs({x:null,y:null});
}
(function _fmtApplyPrefs(){
  document.addEventListener('DOMContentLoaded',()=>{
    const p=_fmtLoadPrefs();
    fmtSetSize(p.size||'m');
  });
})();

/* ── Drag modal ───────────────────────────────────────────────────────────── */
(function(){
  let dragging=false,ox=0,oy=0;
  document.addEventListener('DOMContentLoaded',()=>{
    const handle=document.getElementById('fmt-modal-header');
    const inner =document.getElementById('fmt-modal-inner');
    const overlay=document.getElementById('fmt-modal');
    if(!handle||!inner)return;
    handle.addEventListener('mousedown',e=>{
      if(e.target.closest('button'))return;
      if(e.button!==0)return;
      e.preventDefault();
      const rect=inner.getBoundingClientRect();
      // Switch to absolute positioning inside overlay
      if(!inner.classList.contains('fmt-dragged')){
        inner.style.position='fixed';
        inner.style.left=rect.left+'px';
        inner.style.top=rect.top+'px';
        inner.style.margin='0';
        overlay.style.alignItems='flex-start';
        overlay.style.justifyContent='flex-start';
        inner.classList.add('fmt-dragged');
      }
      ox=e.clientX-inner.getBoundingClientRect().left;
      oy=e.clientY-inner.getBoundingClientRect().top;
      dragging=true;
      document.body.style.userSelect='none';
      handle.style.cursor='grabbing';
    });
    document.addEventListener('mousemove',e=>{
      if(!dragging)return;
      const x=Math.min(Math.max(e.clientX-ox,0),window.innerWidth -inner.offsetWidth);
      const y=Math.min(Math.max(e.clientY-oy,0),window.innerHeight-inner.offsetHeight);
      inner.style.left=x+'px';
      inner.style.top =y+'px';
    });
    document.addEventListener('mouseup',()=>{
      if(!dragging)return;
      dragging=false;
      document.body.style.userSelect='';
      handle.style.cursor='grab';
      _fmtSavePrefs({x:parseInt(inner.style.left),y:parseInt(inner.style.top)});
    });
  });
})();

/* ── Invoice Change Name integration ──────────────────────────────────────── */
function _fmtShowIcnForm(tpl) {
  // show_icn_form flag takes precedence; name substring is a fallback
  const show = tpl && (tpl.show_icn_form === true || (tpl.name && tpl.name.includes('שינוי שם')));
  document.getElementById('fmt-icn-form').style.display = show ? 'block' : 'none';
}

async function fmtIcnSubmit() {
  const invoiceNum   = document.getElementById('fmt-icn-invoice').value.trim();
  const newName      = document.getElementById('fmt-icn-newname').value.trim();
  const note         = document.getElementById('fmt-icn-note').value.trim();
  const customerName = document.getElementById('fmt-cname').value.trim();
  const phone        = document.getElementById('fmt-cphone').value.trim().replace(/-/g,'');
  const mailEl = document.querySelector('#fmt-dyn-fields input[type="email"]') ||
                 document.getElementById('fmt-f-email') ||
                 document.getElementById('fmt-f-costumerMail');
  const mail   = mailEl ? mailEl.value.trim() : '';

  const errors = [];
  if (!/^\d{9}$/.test(invoiceNum))    errors.push('מספר חשבונית חייב להיות 9 ספרות');
  if (!newName || newName.length > 50) errors.push('שם חדש: 1-50 תווים');
  if (!/^\d+$/.test(phone))           errors.push('טלפון לא תקין');
  if (!mail)                           errors.push('נא להזין מייל לקוח');
  if (errors.length) { alert(errors.join('\n')); return; }

  if (!confirm('האם לשלוח בקשת שינוי שם עפ"י הפרטים שציינת?')) return;

  const btn = document.getElementById('fmt-icn-submit-btn');
  btn.disabled = true;
  btn.textContent = 'שולח...';

  const fd = new FormData();
  fd.append('_csrf',              _FMT_CSRF);
  fd.append('invoice_sap_number', invoiceNum);
  fd.append('new_name',           newName);
  fd.append('invoice_note',       note);
  fd.append('customer_name',      customerName);
  fd.append('customer_phone',     phone);
  fd.append('customer_mail',      mail);

  try {
    const r   = await fetch(_FBASE + '/api/invoice-change-name/create', { method:'POST', body:fd });
    const res = await r.json();
    if (res.error) {
      alert(res.msg || 'שגיאה בשליחה');
      btn.disabled  = false;
      btn.innerHTML = '<i class="bi bi-send"></i> שלח בקשת שינוי שם';
    } else {
      btn.innerHTML = '<i class="bi bi-check-lg"></i> נשלח בהצלחה!';
      btn.style.background = '#43a047';
      btn.style.color = '#fff';
      if (typeof v2Toast === 'function') v2Toast(res.msg || 'נשלח בהצלחה');
      setTimeout(() => {
        // clear ICN fields
        document.getElementById('fmt-icn-invoice').value = '';
        document.getElementById('fmt-icn-newname').value = '';
        document.getElementById('fmt-icn-note').value    = '';
        // clear shared name/phone
        document.getElementById('fmt-cname').value  = '';
        document.getElementById('fmt-cphone').value = '';
        // clear dynamic fields
        document.querySelectorAll('#fmt-dyn-fields input, #fmt-dyn-fields textarea, #fmt-dyn-fields select')
          .forEach(el => { el.type === 'checkbox' || el.type === 'radio' ? el.checked = false : el.value = ''; });
        fmtPreview();
        closeFmtModal();
        btn.disabled    = false;
        btn.innerHTML   = '<i class="bi bi-send"></i> שלח בקשת שינוי שם';
        btn.style.background = '';
        btn.style.color = '';
      }, 1500);
    }
  } catch(e) {
    alert('שגיאת רשת — נסה שוב');
    btn.disabled  = false;
    btn.innerHTML = '<i class="bi bi-send"></i> שלח בקשת שינוי שם';
  }
}
</script>
