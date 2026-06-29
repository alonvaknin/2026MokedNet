<?php
use Core\View;
/** @var array|null $tpl @var array $fieldTypes @var array $categories @var int $id */
$base    = rtrim(CFG['app']['url'],'/');
$csrf    = $_SESSION['csrf_token'] ?? '';
$isNew   = !$id;
$tplJson = $tpl ? json_encode($tpl, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) : 'null';
?>

<div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;flex-wrap:wrap;">
  <a href="<?= $base ?>/formatter" class="btn btn-ghost" style="padding:5px 10px;font-size:12px;">
    <i class="bi bi-arrow-right"></i> חזרה לרשימה
  </a>
  <div class="page-title" style="margin-bottom:0;">
    <?= $isNew ? 'תבנית חדשה' : 'עריכת: '.View::e(is_array($tpl)?$tpl['name']:'') ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start;">

  <!-- LEFT -->
  <div>

    <!-- Meta -->
    <div class="card" style="margin-bottom:14px;">
      <div class="card-header"><i class="bi bi-info-circle" style="color:var(--accent);"></i> פרטי תבנית</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
        <div>
          <label class="flabel">שם תבנית *</label>
          <input id="f-name" class="finput" type="text" style="max-width:300px;" value="<?= View::e($tpl['name']??'') ?>">
        </div>
        <div>
          <label class="flabel">קטגוריה</label>
          <input id="f-category" class="finput" type="text" list="cat-list" style="max-width:200px;"
                 value="<?= View::e($tpl['category']??'כללי') ?>">
          <datalist id="cat-list">
            <?php foreach ($categories as $c): ?>
              <option value="<?= View::e($c) ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
      </div>
      <div style="margin-bottom:10px;">
        <label class="flabel">תיאור קצר</label>
        <input id="f-desc" class="finput" type="text" style="max-width:400px;" value="<?= View::e($tpl['description']??'') ?>">
      </div>
      <!-- mail row -->
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-start;margin-bottom:10px;">
        <!-- To -->
        <div style="flex:2;min-width:200px;">
          <label class="flabel">נמענים (To)</label>
          <div id="fmt-to-wrap">
            <div class="fmt-email-box" id="fmt-to-box">
              <i class="bi bi-search" style="color:var(--text3);font-size:12px;flex-shrink:0;"></i>
              <input type="text" id="fmt-to-q" class="fmt-email-input"
                     placeholder="חיפוש או הזנת מייל..." autocomplete="off"
                     oninput="fmtEmailFilter('to',this.value)"
                     onfocus="fmtEmailOpen('to')"
                     onkeydown="fmtEmailKey(event,'to')">
              <span id="fmt-to-badge" style="display:none;background:var(--accent);color:#fff;
                    border-radius:10px;padding:1px 7px;font-size:11px;font-weight:700;flex-shrink:0;"></span>
            </div>
          </div>
          <div id="fmt-to-chips" style="display:flex;flex-wrap:wrap;gap:5px;margin-top:6px;"></div>
          <input type="hidden" id="f-mail-to" value="<?= View::e($tpl['mail_to']??'') ?>">
        </div>
        <!-- CC -->
        <div style="flex:2;min-width:200px;">
          <label class="flabel">עותק (CC)</label>
          <div id="fmt-cc-wrap">
            <div class="fmt-email-box" id="fmt-cc-box">
              <i class="bi bi-search" style="color:var(--text3);font-size:12px;flex-shrink:0;"></i>
              <input type="text" id="fmt-cc-q" class="fmt-email-input"
                     placeholder="חיפוש או הזנת מייל..." autocomplete="off"
                     oninput="fmtEmailFilter('cc',this.value)"
                     onfocus="fmtEmailOpen('cc')"
                     onkeydown="fmtEmailKey(event,'cc')">
              <span id="fmt-cc-badge" style="display:none;background:var(--accent);color:#fff;
                    border-radius:10px;padding:1px 7px;font-size:11px;font-weight:700;flex-shrink:0;"></span>
            </div>
          </div>
          <div id="fmt-cc-chips" style="display:flex;flex-wrap:wrap;gap:5px;margin-top:6px;"></div>
          <input type="hidden" id="f-mail-cc" value="<?= View::e($tpl['mail_cc']??'') ?>">
        </div>
        <div style="flex:1;min-width:140px;">
          <label class="flabel">נושא מייל</label>
          <div id="toolbar-mail-subject" class="ph-toolbar" style="display:none;margin-bottom:4px;"></div>
          <input id="f-mail-subject" class="finput" type="text"
                 value="<?= View::e($tpl['mail_subject']??'') ?>"
                 onfocus="activeField='f-mail-subject';showToolbar('toolbar-mail-subject')"
                 oninput="renderPreview()">
        </div>
      </div>
      <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
          <input type="checkbox" id="f-active" style="accent-color:var(--accent);"
                 <?= ($tpl['is_active']??1)?'checked':'' ?>> פעיל
        </label>
        <div style="display:flex;align-items:center;gap:6px;">
          <label class="flabel" style="margin:0;">סדר:</label>
          <input id="f-sort" class="finput" type="number" value="<?= (int)($tpl['sort_order']??0) ?>"
                 style="width:70px;">
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="card" style="margin-bottom:14px;">
      <div class="card-header">
        <i class="bi bi-file-text" style="color:#10b981;"></i> גוף התבנית
        <span style="font-size:11px;font-weight:400;color:var(--text3);margin-right:8px;">
          placeholder: <code style="background:var(--bg4);padding:1px 5px;border-radius:3px;">[[field_key]]</code>
        </span>
      </div>
      <div style="margin-bottom:12px;">
        <label class="flabel">גוף — לשון זכר <span style="font-weight:400;color:var(--text3);">(ברירת מחדל)</span></label>
        <div id="toolbar-body-male" class="ph-toolbar" style="margin-bottom:5px;"></div>
        <textarea id="f-body-male" class="finput" rows="7"
                  style="font-family:monospace;font-size:13px;resize:vertical;"
                  onfocus="activeField='f-body-male'"
                  oninput="renderPreview()"><?= View::e($tpl['body_male']??'') ?></textarea>
      </div>
      <div>
        <label class="flabel">גוף — לשון נקבה <span style="font-weight:400;color:var(--text3);">(ריק = ללא הפרדת מין)</span></label>
        <div id="toolbar-body-female" class="ph-toolbar" style="margin-bottom:5px;"></div>
        <textarea id="f-body-female" class="finput" rows="7"
                  style="font-family:monospace;font-size:13px;resize:vertical;"
                  onfocus="activeField='f-body-female'"
                  oninput="renderPreview()"><?= View::e($tpl['body_female']??'') ?></textarea>
      </div>
    </div>

    <!-- Fields builder -->
    <div class="card" style="margin-bottom:14px;">
      <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <span><i class="bi bi-ui-radios" style="color:#8b5cf6;"></i> שדות קלט</span>
        <button class="btn btn-ghost" style="padding:4px 10px;font-size:12px;" onclick="addField()">
          <i class="bi bi-plus"></i> הוסף שדה
        </button>
      </div>
      <div id="fields-list"></div>
      <div id="fields-empty" style="text-align:center;padding:20px;color:var(--text3);font-size:13px;">
        אין שדות — לחץ "הוסף שדה"
      </div>
    </div>

    <div style="display:flex;gap:10px;">
      <button class="btn btn-primary" style="flex:1;" onclick="saveTemplate()">
        <i class="bi bi-check-lg"></i> שמור תבנית
      </button>
      <a href="<?= $base ?>/formatter" class="btn btn-ghost">ביטול</a>
    </div>
    <div id="save-error" style="color:var(--danger);font-size:13px;margin-top:8px;display:none;
         padding:8px 12px;background:rgba(239,68,68,.1);border-radius:6px;"></div>
  </div>

  <!-- RIGHT: preview (sticky) -->
  <div style="position:sticky;top:80px;">
    <div class="card">
      <div class="card-header"><i class="bi bi-eye" style="color:var(--accent);"></i> תצוגה מקדימה</div>
      <div id="preview-gender-row" style="display:none;margin-bottom:10px;gap:8px;">
        <label style="display:inline-flex;align-items:center;gap:5px;cursor:pointer;font-size:13px;">
          <input type="radio" name="prev-gender" value="male" checked> זכר
        </label>
        <label style="display:inline-flex;align-items:center;gap:5px;cursor:pointer;font-size:13px;margin-right:10px;">
          <input type="radio" name="prev-gender" value="female"> נקבה
        </label>
      </div>
      <div id="preview-text" style="font-size:13px;line-height:1.8;background:var(--bg3);
           padding:12px;border-radius:var(--radius-sm);min-height:80px;direction:rtl;white-space:pre-wrap;"></div>
    </div>
  </div>
</div>

<!-- Email picker dropdowns — fixed so they escape any stacking/overflow context -->
<div id="fmt-to-dropdown" style="display:none;position:fixed;background:var(--bg2);
     border:1px solid var(--border);border-radius:8px;max-height:220px;overflow-y:auto;
     z-index:9500;box-shadow:0 8px 30px rgba(0,0,0,.5);">
  <div id="fmt-to-inner">
    <div style="padding:12px;color:var(--text3);font-size:13px;text-align:center;">
      <i class="bi bi-hourglass-split"></i> טוען...
    </div>
  </div>
</div>
<div id="fmt-cc-dropdown" style="display:none;position:fixed;background:var(--bg2);
     border:1px solid var(--border);border-radius:8px;max-height:220px;overflow-y:auto;
     z-index:9500;box-shadow:0 8px 30px rgba(0,0,0,.5);">
  <div id="fmt-cc-inner">
    <div style="padding:12px;color:var(--text3);font-size:13px;text-align:center;">
      <i class="bi bi-hourglass-split"></i> טוען...
    </div>
  </div>
</div>

<style>
.flabel{display:block;font-size:12px;color:var(--text2);margin-bottom:4px;font-weight:500}
.finput{background:var(--bg3);border:1px solid var(--border);border-radius:8px;
        padding:7px 11px;color:var(--text);font-size:13px;font-family:var(--font);
        outline:none;width:100%;}
.finput:focus{border-color:var(--accent)}
.field-row{background:var(--bg3);border:1px solid var(--border);border-radius:8px;
           padding:11px 13px;margin-bottom:8px;}
.field-row .finput{background:var(--bg4);}
/* Placeholder toolbar */
.ph-toolbar{display:flex;flex-wrap:wrap;gap:4px;padding:5px 8px;
            background:var(--bg4);border:1px solid var(--border);
            border-radius:8px;min-height:34px;align-items:center;}
.ph-btn{display:inline-flex;flex-direction:column;align-items:flex-start;
        background:var(--bg3);border:1px solid var(--border);border-radius:5px;
        padding:3px 8px;cursor:pointer;font-family:var(--font);
        transition:background .12s,border-color .12s,transform .1s;user-select:none;}
.ph-btn:hover{background:var(--accent-dim);border-color:rgba(91,141,238,.4);transform:translateY(-1px);}
.ph-btn:active{transform:scale(.96);}
.ph-key{font-size:10px;font-weight:700;color:var(--accent);font-family:monospace;pointer-events:none;}
.ph-desc{font-size:10px;color:var(--text3);white-space:nowrap;max-width:100px;
          overflow:hidden;text-overflow:ellipsis;pointer-events:none;}
.ph-built{border-color:rgba(91,141,238,.2);background:rgba(91,141,238,.06);}
.ph-built .ph-key{color:#8bb0f5;}
/* Highlight in preview */
mark.ph-hi{background:#f59e0b22;color:var(--warning);border-radius:2px;padding:0 1px;}

/* Email picker widget */
.fmt-email-box{display:flex;align-items:center;gap:7px;background:var(--bg3);
  border:1px solid var(--border);border-radius:8px;padding:0 12px;transition:border-color .15s;}
.fmt-email-box:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px rgba(91,141,238,.12);}
.fmt-email-input{background:none;border:none;outline:none;color:var(--text);
  font-family:var(--font);font-size:13px;padding:8px 0;flex:1;}
.fmt-email-input::placeholder{color:var(--text3);}
.fmt-ct-item{display:flex;align-items:center;gap:10px;padding:9px 14px;cursor:pointer;
  border-bottom:1px solid var(--border);transition:background .12s;font-size:13px;}
.fmt-ct-item:last-child{border-bottom:none;}
.fmt-ct-item:hover,.fmt-ct-item.fmt-sel,.fmt-ct-item.fmt-cursor{background:var(--accent-dim);}
.fmt-ct-avatar{width:26px;height:26px;border-radius:50%;color:#fff;display:grid;
  place-items:center;font-size:11px;font-weight:700;flex-shrink:0;}
.fmt-ct-name{font-size:13px;font-weight:600;color:var(--text);}
.fmt-ct-sub{font-size:11px;color:var(--text3);}
.fmt-ct-check{margin-right:auto;font-size:15px;color:var(--accent);display:none;}
.fmt-ct-item.fmt-sel .fmt-ct-check{display:block;}
.fmt-chip{display:inline-flex;align-items:center;gap:5px;background:rgba(91,141,238,.12);
  border:1px solid rgba(91,141,238,.3);border-radius:20px;padding:3px 10px;
  font-size:12px;color:var(--accent);}
.fmt-chip button{background:none;border:none;color:var(--accent);cursor:pointer;
  font-size:15px;line-height:1;padding:0;opacity:.7;}
.fmt-chip button:hover{opacity:1;}
</style>

<script>
const FMT_BASE  = typeof BASE!=='undefined'?BASE:'<?= $base ?>';
const FMT_CSRF  = '<?= View::e($csrf) ?>';
const FMT_ID    = <?= $id ?>;

/* ── All constants FIRST — before any function calls ─────────────────────── */
const FIELD_TYPES = <?= json_encode($fieldTypes, JSON_UNESCAPED_UNICODE) ?>;

const BUILT_IN_PH = [
  {key:'customer_name',  label:'שם לקוח',   desc:'שם מלא של הלקוח/ה'},
  {key:'customer_phone', label:'טלפון',      desc:'מספר טלפון הלקוח'},
  {key:'time_state',     label:'שעת יום',    desc:'בוקר טוב / צהרים / ערב טוב'},
];

const TYPE_LABELS = {
  text:'טקסט', tel:'טלפון', email:'מייל', number:'מספר',
  textarea:'אזור טקסט', select:'רשימה', radio:'radio',
  checkbox:'checkbox', date:'תאריך',
  product_search:'חיפוש מוצר', store_select:'בחירת חנות',
};

/* ── State ────────────────────────────────────────────────────────────────── */
let activeField = 'f-body-male';
let fields      = [];

/* ── Init from server ─────────────────────────────────────────────────────── */
const TPL_DATA = <?= $tplJson ?>;
if(TPL_DATA && TPL_DATA.fields){
  fields = TPL_DATA.fields.map(f=>({
    field_key:   f.field_key   || '',
    label:       f.label       || '',
    field_type:  f.field_type  || 'text',
    placeholder: f.placeholder || '',
    options:     Array.isArray(f.options) ? f.options.map(o=>o.value+'|'+o.label).join('\n') : '',
    required:    f.required    || 0,
    sort_order:  f.sort_order  || 0,
  }));
}

/* run after all functions are defined — called at bottom */
function init(){
  renderFields();
  renderToolbar('toolbar-body-male');
  renderToolbar('toolbar-body-female');
  renderPreview();
  document.querySelectorAll('[name="prev-gender"]').forEach(r=>
    r.addEventListener('change', renderPreview)
  );
  // Load existing mail_to / mail_cc into the picker widgets
  _fmtLoadInitial('to',  document.getElementById('f-mail-to')?.value  || '');
  _fmtLoadInitial('cc',  document.getElementById('f-mail-cc')?.value  || '');
  // Preload contacts in background
  _fmtEnsureContacts();
}

/* ── Toolbar helpers ──────────────────────────────────────────────────────── */
function showToolbar(id){
  const el=document.getElementById(id);
  if(!el)return;
  el.style.display='flex';
  renderToolbar(id);
}

function insertPlaceholder(key){
  const el = document.getElementById(activeField);
  if(!el) return;
  const ph = `[[${key}]]`;
  const s  = (el.selectionStart !== undefined) ? el.selectionStart : el.value.length;
  const e  = (el.selectionEnd   !== undefined) ? el.selectionEnd   : s;
  el.value = el.value.slice(0,s) + ph + el.value.slice(e);
  el.selectionStart = el.selectionEnd = s + ph.length;
  el.focus();
  // trigger oninput manually for preview
  renderPreview();
}

function renderToolbar(containerId){
  const container = document.getElementById(containerId);
  if(!container) return;
  const customPh = fields.filter(f=>f.field_key).map(f=>({
    key:       f.field_key,
    label:     f.label || f.field_key,
    desc:      'סוג: ' + (TYPE_LABELS[f.field_type] || f.field_type),
    isBuiltIn: false,
  }));
  const allPh = [
    ...BUILT_IN_PH.map(p=>({...p, isBuiltIn:true})),
    ...customPh,
  ];
  if(!allPh.length){
    container.innerHTML='<span style="font-size:11px;color:var(--text3);padding:0 4px;">הוסף שדות כדי לראות placeholders</span>';
    return;
  }
  // Use data-key attribute + onclick on container to avoid closure issues
  container.innerHTML = allPh.map(p=>
    `<button type="button" class="ph-btn${p.isBuiltIn?' ph-built':''}"
             data-phkey="${fesc(p.key)}"
             title="${p.isBuiltIn?'מובנה — ':''}${p.desc}">
       <span class="ph-key">[[${fesc(p.key)}]]</span>
       <span class="ph-desc">${fesc(p.label)}</span>
     </button>`
  ).join('');
  // Single delegated listener on container (replaces any old one)
  container.onclick = function(e){
    const btn = e.target.closest('.ph-btn');
    if(!btn) return;
    e.preventDefault();
    e.stopPropagation();
    insertPlaceholder(btn.dataset.phkey);
  };
}

function updateToolbars(){
  renderToolbar('toolbar-body-male');
  renderToolbar('toolbar-body-female');
  const ms = document.getElementById('toolbar-mail-subject');
  if(ms && ms.style.display !== 'none') renderToolbar('toolbar-mail-subject');
}

/* ── Fields ──────────────────────────────────────────────────────────────── */
function renderFields(){
  const list  = document.getElementById('fields-list');
  const empty = document.getElementById('fields-empty');
  if(!fields.length){ list.innerHTML=''; empty.style.display='block'; return; }
  empty.style.display = 'none';

  // Build each field row independently — NO nesting
  const rows = fields.map((f,i) => {
    const optBlock = ['select','radio'].includes(f.field_type)
      ? `<div style="margin-top:8px;">
           <label class="flabel">אפשרויות — <code>value|תווית</code> לכל שורה</label>
           <textarea class="finput" rows="3" style="font-family:monospace;font-size:12px;resize:vertical;"
                     oninput="updField(${i},'options',this.value)">${fesc(f.options||'')}</textarea>
         </div>`
      : '';
    return `<div class="field-row" id="frow-${i}">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
        <span style="font-size:11px;font-weight:600;color:var(--text3);">#${i+1}</span>
        <code style="flex:1;font-size:11px;color:var(--accent);">[[${fesc(f.field_key||'...')}]]</code>
        <button type="button" onclick="moveField(${i},-1)" style="background:none;border:none;cursor:pointer;color:var(--text3);padding:0 3px;">↑</button>
        <button type="button" onclick="moveField(${i},1)"  style="background:none;border:none;cursor:pointer;color:var(--text3);padding:0 3px;">↓</button>
        <button type="button" onclick="removeField(${i})"  style="background:none;border:none;cursor:pointer;color:var(--danger);padding:0 4px;">✕</button>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:8px;">
        <div>
          <label class="flabel">מזהה (key)</label>
          <input class="finput" value="${fesc(f.field_key)}" oninput="updField(${i},'field_key',this.value)">
        </div>
        <div>
          <label class="flabel">תווית</label>
          <input class="finput" value="${fesc(f.label)}" oninput="updField(${i},'label',this.value)">
        </div>
        <div>
          <label class="flabel">סוג</label>
          <select class="finput" onchange="updField(${i},'field_type',this.value)">
            ${Object.entries(FIELD_TYPES).map(([v,l])=>
              `<option value="${v}"${f.field_type===v?' selected':''}>${l}</option>`
            ).join('')}
          </select>
        </div>
      </div>
      <div style="display:flex;gap:8px;align-items:flex-end;">
        <div style="flex:1;">
          <label class="flabel">Placeholder</label>
          <input class="finput" value="${fesc(f.placeholder||'')}" oninput="updField(${i},'placeholder',this.value)">
        </div>
        <label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:12px;padding-bottom:8px;white-space:nowrap;">
          <input type="checkbox" ${f.required?'checked':''} onchange="updField(${i},'required',this.checked?1:0)"
                 style="accent-color:var(--accent);"> חובה
        </label>
      </div>
      ${optBlock}
    </div>`;
  });

  list.innerHTML = rows.join('');
  updateToolbars();
  renderPreview();
}

function addField(){
  fields.push({field_key:'',label:'',field_type:'text',placeholder:'',options:'',required:0,sort_order:fields.length});
  renderFields();
}
function removeField(i){ fields.splice(i,1); renderFields(); }
function moveField(i,dir){
  const j=i+dir; if(j<0||j>=fields.length) return;
  [fields[i],fields[j]]=[fields[j],fields[i]]; renderFields();
}
function updField(i,key,val){
  fields[i][key]=val;
  if(key==='field_key'||key==='label') updateToolbars();
  if(key==='field_key') renderPreview();
}

/* ── Preview with highlight ───────────────────────────────────────────────── */
function renderPreview(){
  const gender = document.querySelector('[name="prev-gender"]:checked')?.value||'male';
  const bm = document.getElementById('f-body-male')?.value||'';
  const bf = document.getElementById('f-body-female')?.value||'';
  const body = (gender==='female'&&bf) ? bf : bm;
  const gDiv = document.getElementById('preview-gender-row');
  if(gDiv) gDiv.style.display = bf?'flex':'none';

  const h = new Date().getHours();
  const ts = h<12?'בוקר טוב':h<17?'צהרים טובים':h<19?'ערב טוב':'לילה טוב';

  // Collect field→value pairs for substitution + highlight
  const subs = [
    ['time_state',     ts,               true],
    ['customer_name',  'ישראל ישראלי',   true],
    ['customer_phone', '050-1234567',     true],
    ...fields.filter(f=>f.field_key).map(f=>{
      const sample = {
        text:`[${f.label||f.field_key}]`, tel:'050-1234567',
        email:'test@example.com', number:'123', date:'01/01/2025',
        product_search:'[מוצר נבחר]', store_select:'[חנות נבחרת]',
      }[f.field_type]||`[${f.label||f.field_key}]`;
      return [f.field_key, sample, false];
    }),
  ];

  // Build HTML with <mark> around substituted values
  let html = hesc(body);
  subs.forEach(([key,,isBuiltIn])=>{
    const ph = hesc(`[[${key}]]`);
    const sampleRaw = subs.find(s=>s[0]===key)?.[1]||'';
    const cls = isBuiltIn ? 'ph-hi' : 'ph-hi';
    html = html.replaceAll(ph,
      `<mark class="${cls}" style="background:${isBuiltIn?'rgba(91,141,238,.18)':'rgba(245,158,11,.2)'};color:${isBuiltIn?'var(--accent)':'var(--warning)'};border-radius:3px;padding:0 2px;">${hesc(sampleRaw)}</mark>`
    );
  });

  const el = document.getElementById('preview-text');
  if(el) el.innerHTML = html.replace(/\n/g,'<br>');
}

/* ── Save ─────────────────────────────────────────────────────────────────── */
async function saveTemplate(){
  const name = document.getElementById('f-name').value.trim();
  const errEl = document.getElementById('save-error');
  if(!name){ errEl.textContent='שם תבנית חובה'; errEl.style.display='block'; return; }
  errEl.style.display = 'none';
  const body = new URLSearchParams({
    _csrf:        FMT_CSRF,
    id:           FMT_ID,
    name,
    category:     document.getElementById('f-category').value.trim()||'כללי',
    description:  document.getElementById('f-desc').value.trim(),
    body_male:    document.getElementById('f-body-male').value,
    body_female:  document.getElementById('f-body-female').value,
    mail_to:      document.getElementById('f-mail-to').value.trim(),
    mail_cc:      document.getElementById('f-mail-cc').value.trim(),
    mail_subject: document.getElementById('f-mail-subject').value.trim(),
    is_active:    document.getElementById('f-active').checked?'1':'0',
    sort_order:   document.getElementById('f-sort').value,
  });
  fields.forEach((f,i)=>{
    body.append(`fields[${i}][field_key]`,  f.field_key);
    body.append(`fields[${i}][label]`,       f.label);
    body.append(`fields[${i}][field_type]`,  f.field_type);
    body.append(`fields[${i}][placeholder]`, f.placeholder||'');
    body.append(`fields[${i}][options_raw]`, f.options||'');
    body.append(`fields[${i}][required]`,    f.required?'1':'0');
    body.append(`fields[${i}][sort_order]`,  String(i));
  });
  const r = await fetch(FMT_BASE+'/formatter/save',{method:'POST',body});
  const d = await r.json();
  if(d.ok){
    if(typeof v2Toast==='function') v2Toast('תבנית נשמרה ✓');
    setTimeout(()=>window.location.href=FMT_BASE+'/formatter',800);
  }else{
    errEl.textContent = d.error||'שגיאה';
    errEl.style.display='block';
  }
}

/* ── Utils ────────────────────────────────────────────────────────────────── */
// fesc: for HTML attributes (no innerHTML injection)
function fesc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
// hesc: for innerHTML (escapes but keeps text safe)
function hesc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

/* ── Email picker (To / CC) ──────────────────────────────────────────────── */
const FMT_EMAIL_COLORS = ['#5b8dee','#8b5cf6','#10b981','#f59e0b','#ec4899','#06b6d4'];
let _fmtContacts = null;
const _fmtSel    = { to: new Map(), cc: new Map() };
const _fmtCursor = { to: -1, cc: -1 };

function _fmtHash(s){ let h=0; for(let i=0;i<s.length;i++) h=(h*31+s.charCodeAt(i))|0; return Math.abs(h); }
function _fmtIsEmail(s){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((s||'').trim()); }
function _fmtAttr(s){ return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;'); }
function _fmtEsc2(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

async function _fmtEnsureContacts(){
  if(_fmtContacts !== null) return;
  try{
    const r = await fetch(FMT_BASE+'/api/contacts/list',{credentials:'include'});
    _fmtContacts = await r.json();
  }catch{ _fmtContacts = []; }
}

function _fmtPositionDropdown(field){
  const anchor = document.getElementById(`fmt-${field}-box`);
  const dd     = document.getElementById(`fmt-${field}-dropdown`);
  if(!anchor || !dd) return;
  const r = anchor.getBoundingClientRect();
  dd.style.top   = (r.bottom + 4) + 'px';
  dd.style.left  = r.left + 'px';
  dd.style.width = r.width + 'px';
}

function fmtEmailOpen(field){
  _fmtEnsureContacts().then(()=>{
    const q = document.getElementById(`fmt-${field}-q`)?.value||'';
    _fmtCursor[field] = -1;
    fmtEmailFilter(field, q);
    _fmtPositionDropdown(field);
    document.getElementById(`fmt-${field}-dropdown`).style.display='block';
  });
}

function fmtEmailFilter(field, q){
  const rawQ = (q||'').trim();
  const lq   = rawQ.toLowerCase();
  const list = _fmtContacts ? (lq
    ? _fmtContacts.filter(c =>
        ((c.first_name||'') + ' ' + (c.last_name||'') + ' ' + (c.email||''))
          .toLowerCase().includes(lq))
    : _fmtContacts) : [];
  _fmtCursor[field] = -1;
  _fmtRenderDropdown(field, list, rawQ);
  _fmtPositionDropdown(field);
  document.getElementById(`fmt-${field}-dropdown`).style.display='block';
}

function fmtEmailKey(e, field){
  const dd = document.getElementById(`fmt-${field}-dropdown`);
  if(!dd || dd.style.display === 'none'){
    if(e.key === 'ArrowDown'){ fmtEmailOpen(field); e.preventDefault(); }
    return;
  }
  const items = dd.querySelectorAll('.fmt-ct-item');
  if(!items.length) return;

  if(e.key === 'ArrowDown'){
    e.preventDefault();
    _fmtCursor[field] = Math.min(_fmtCursor[field] + 1, items.length - 1);
    _fmtHighlightCursor(field, items);
  } else if(e.key === 'ArrowUp'){
    e.preventDefault();
    _fmtCursor[field] = Math.max(_fmtCursor[field] - 1, -1);
    _fmtHighlightCursor(field, items);
  } else if(e.key === 'Enter'){
    e.preventDefault();
    const idx = _fmtCursor[field];
    if(idx >= 0 && idx < items.length){
      const item = items[idx];
      const isManual = item.dataset.manual === '1';
      const cid = isManual ? item.dataset.cid : parseInt(item.dataset.cid);
      _fmtToggle(field, cid, item.dataset.name, item.dataset.email);
      document.getElementById(`fmt-${field}-q`).value = '';
      _fmtCursor[field] = -1;
    }
  } else if(e.key === 'Escape'){
    dd.style.display = 'none';
    _fmtCursor[field] = -1;
  }
}

function _fmtHighlightCursor(field, items){
  items.forEach((el, i) => {
    el.classList.toggle('fmt-cursor', i === _fmtCursor[field]);
    if(i === _fmtCursor[field]) el.scrollIntoView({block:'nearest'});
  });
}

function _fmtRenderDropdown(field, list, rawQ){
  const inner = document.getElementById(`fmt-${field}-inner`);
  if(!inner) return;
  const sel = _fmtSel[field];

  let manualHtml = '';
  if(rawQ && _fmtIsEmail(rawQ)){
    const emailLower = rawQ.toLowerCase();
    const inList     = list && list.some(c => (c.email||'').toLowerCase() === emailLower);
    const inSel      = [...sel.values()].some(c => (c.email||'').toLowerCase() === emailLower);
    if(!inList && !inSel){
      const mKey = '__manual__'+rawQ;
      const cls  = sel.has(mKey) ? 'fmt-sel' : '';
      manualHtml = `<div class="fmt-ct-item ${cls}" data-cid="${_fmtAttr(mKey)}"
           data-name="${_fmtAttr(rawQ)}" data-email="${_fmtAttr(rawQ)}" data-manual="1">
        <div class="fmt-ct-avatar" style="background:#10b981;">&#9993;</div>
        <div style="flex:1;min-width:0;">
          <div class="fmt-ct-name">${_fmtEsc2(rawQ)}</div>
          <div class="fmt-ct-sub">הזנה ידנית</div>
        </div>
        <i class="bi bi-check2-circle fmt-ct-check"></i>
      </div>`;
    }
  }

  if(!list || !list.length){
    inner.innerHTML = manualHtml ||
      `<div style="padding:12px;color:var(--text3);font-size:13px;text-align:center;">לא נמצאו אנשי קשר${rawQ && rawQ.length>2 && !_fmtIsEmail(rawQ) ? '<br><span style="font-size:11px;">הזן כתובת מייל מלאה להוספה ידנית</span>' : ''}</div>`;
  } else {
    inner.innerHTML = manualHtml + list.map(c=>{
      const name  = ((c.first_name||'')+' '+(c.last_name||'')).trim();
      const init  = (c.first_name||'?').charAt(0)+(c.last_name||'').charAt(0);
      const ac    = FMT_EMAIL_COLORS[_fmtHash(name) % FMT_EMAIL_COLORS.length];
      const cls   = sel.has(c.id) ? 'fmt-sel' : '';
      const isUsr = c.source === 'user';
      const sub   = (isUsr ? [c.email] : [c.role, c.department, c.email]).filter(Boolean).join(' · ');
      const badge = isUsr ? ' <span style="font-size:10px;background:rgba(91,141,238,.15);color:var(--accent);border-radius:4px;padding:1px 5px;font-weight:600;">משתמש</span>' : '';
      return `<div class="fmt-ct-item ${cls}" data-cid="${c.id}"
           data-name="${_fmtAttr(name)}" data-email="${_fmtAttr(c.email||'')}">
        <div class="fmt-ct-avatar" style="background:${ac};">${_fmtEsc2(init)}</div>
        <div style="flex:1;min-width:0;">
          <div class="fmt-ct-name">${_fmtEsc2(name)}${badge}</div>
          ${sub?`<div class="fmt-ct-sub">${_fmtEsc2(sub)}</div>`:''}
        </div>
        <i class="bi bi-check2-circle fmt-ct-check"></i>
      </div>`;
    }).join('');
  }

  inner.onclick = e => {
    const item = e.target.closest('.fmt-ct-item');
    if(!item) return;
    const isManual = item.dataset.manual === '1';
    const cid = isManual ? item.dataset.cid : parseInt(item.dataset.cid);
    _fmtToggle(field, cid, item.dataset.name, item.dataset.email);
    document.getElementById(`fmt-${field}-q`).value = '';
    _fmtCursor[field] = -1;
  };
}

function _fmtToggle(field, id, name, email){
  const sel = _fmtSel[field];
  if(sel.has(id)) sel.delete(id);
  else            sel.set(id, {id, name, email});
  const q = document.getElementById(`fmt-${field}-q`)?.value||'';
  fmtEmailFilter(field, q);
  _fmtRenderChips(field);
  _fmtUpdateBadge(field);
  _fmtSyncHidden(field);
}

function _fmtRenderChips(field){
  const wrap = document.getElementById(`fmt-${field}-chips`);
  if(!wrap) return;
  const sel = _fmtSel[field];
  if(!sel.size){ wrap.innerHTML=''; return; }
  wrap.innerHTML = [...sel.values()].map(c=>
    `<span class="fmt-chip">
      <i class="bi bi-envelope" style="font-size:11px;"></i>
      ${_fmtEsc2(c.email)}
      <button data-field="${field}" data-cid="${_fmtAttr(String(c.id))}"
              style="background:none;border:none;color:var(--accent);cursor:pointer;font-size:15px;line-height:1;padding:0;opacity:.7;">×</button>
    </span>`
  ).join('');
  wrap.onclick = e => {
    const btn = e.target.closest('button[data-cid]');
    if(!btn) return;
    const f = btn.dataset.field;
    const cid = _fmtSel[f] && [..._fmtSel[f].keys()].find(k=>String(k)===btn.dataset.cid);
    if(cid !== undefined){
      const entry = _fmtSel[f].get(cid);
      _fmtToggle(f, cid, entry.name, entry.email);
    }
  };
}

function _fmtUpdateBadge(field){
  const b = document.getElementById(`fmt-${field}-badge`);
  if(!b) return;
  const n = _fmtSel[field].size;
  b.textContent   = n || '';
  b.style.display = n ? 'inline-block' : 'none';
}

function _fmtSyncHidden(field){
  const emails = [..._fmtSel[field].values()].map(c=>c.email).join(',');
  const el = document.getElementById(`f-mail-${field}`);
  if(el) el.value = emails;
}

function _fmtLoadInitial(field, csvStr){
  if(!csvStr) return;
  csvStr.split(',').map(s=>s.trim()).filter(Boolean).forEach(email=>{
    const key = '__loaded__'+email;
    _fmtSel[field].set(key, {id: key, name: email, email});
  });
  _fmtRenderChips(field);
  _fmtUpdateBadge(field);
}

// Close dropdowns on outside click; reposition on scroll/resize
document.addEventListener('click', e => {
  ['to','cc'].forEach(field => {
    const wrap = document.getElementById(`fmt-${field}-wrap`);
    const dd   = document.getElementById(`fmt-${field}-dropdown`);
    if(dd && wrap && !wrap.contains(e.target) && !dd.contains(e.target))
      dd.style.display = 'none';
  });
});
window.addEventListener('scroll', () => {
  ['to','cc'].forEach(field => {
    const dd = document.getElementById(`fmt-${field}-dropdown`);
    if(dd && dd.style.display !== 'none') _fmtPositionDropdown(field);
  });
}, true);
window.addEventListener('resize', () => {
  ['to','cc'].forEach(field => {
    const dd = document.getElementById(`fmt-${field}-dropdown`);
    if(dd && dd.style.display !== 'none') _fmtPositionDropdown(field);
  });
});

// Run init AFTER all functions are defined
init();
</script>
