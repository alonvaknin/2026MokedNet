<?php
/** @var array|null $savedPrefs */
use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
$prefsJson = $savedPrefs ? json_encode($savedPrefs, JSON_UNESCAPED_UNICODE) : 'null';
?>
<script>window.__V2_BASE=window.__V2_BASE||'<?= $base ?>';</script>

<div class="page-title">העדפות תצוגה</div>

<div style="max-width:900px;">

  <div class="pref-block">
    <div class="pref-block-title"><i class="bi bi-moon-fill"></i> מצב תצוגה</div>
    <div class="mode-row">
      <?php foreach([['dark','כהה','bi-moon-fill'],['light','בהיר','bi-sun-fill'],['system','מערכת','bi-circle-half']] as [$v,$l,$ic]): ?>
      <label class="mode-opt">
        <input type="radio" name="mode" value="<?= $v ?>" class="pref-radio">
        <div class="mode-opt-inner"><i class="bi <?= $ic ?>"></i><span><?= $l ?></span></div>
      </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="pref-block">
    <div class="pref-block-title"><i class="bi bi-palette-fill"></i> פלטת צבעים</div>
    <div id="palette-grid" class="palette-grid"></div>
  </div>


  <div class="pref-block">
    <div class="pref-block-title"><i class="bi bi-type"></i> גופן</div>
    <div id="font-grid" class="font-grid"></div>
  </div>

  <div class="pref-block">
    <div class="pref-block-title"><i class="bi bi-sliders"></i> עיצוב</div>
    <div class="options-grid">

      <div class="opt-card">
        <div class="opt-label">פינות</div>
        <div class="chip-row">
          <?php foreach([['0','ישר'],['6','קל'],['10','רגיל'],['16','עגול']] as [$v,$l]): ?>
          <label class="chip"><input type="radio" name="radius" value="<?= $v ?>" class="pref-radio">
            <span class="chip-inner" style="border-radius:<?= $v ?>px;"><?= $l ?></span></label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="opt-card">
        <div class="opt-label">הצללות</div>
        <div class="chip-row">
          <?php foreach([['none','ללא'],['soft','עדין'],['medium','בינוני'],['strong','חזק']] as [$v,$l]): ?>
          <label class="chip"><input type="radio" name="shadows" value="<?= $v ?>" class="pref-radio">
            <span class="chip-inner"><?= $l ?></span></label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="opt-card">
        <div class="opt-label">כפתורים</div>
        <div class="chip-row">
          <label class="chip"><input type="radio" name="btn_style" value="filled" class="pref-radio"><span class="chip-inner" style="background:var(--accent);color:#fff;border-color:transparent;">מלא</span></label>
          <label class="chip"><input type="radio" name="btn_style" value="outlined" class="pref-radio"><span class="chip-inner" style="border-color:var(--accent);color:var(--accent);">מסגרת</span></label>
          <label class="chip"><input type="radio" name="btn_style" value="soft" class="pref-radio"><span class="chip-inner" style="background:var(--accent-dim);color:var(--accent);border-color:transparent;">רך</span></label>
        </div>
      </div>

      <div class="opt-card">
        <div class="opt-label">צפיפות תפריט</div>
        <div class="chip-row">
          <?php foreach([['compact','צפוף'],['normal','רגיל'],['spacious','מרווח']] as [$v,$l]): ?>
          <label class="chip"><input type="radio" name="density" value="<?= $v ?>" class="pref-radio">
            <span class="chip-inner"><?= $l ?></span></label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="opt-card">
        <div class="opt-label">אפקט זכוכית (Glassmorphism)</div>
        <label class="toggle-row">
          <label class="tog"><input type="checkbox" name="glass" class="pref-toggle" value="1">
            <span class="tog-track"><span class="tog-thumb"></span></span></label>
          <span style="font-size:13px;flex:1;">blur + שקיפות לכרטיסים וסרגל</span>
          <div style="width:52px;height:32px;background:rgba(255,255,255,.07);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);border-radius:8px;display:grid;place-items:center;font-size:12px;color:var(--text2);">Aa</div>
        </label>
      </div>

      <div class="opt-card">
        <div class="opt-label">אנימציות</div>
        <label class="toggle-row">
          <label class="tog"><input type="checkbox" name="animations" class="pref-toggle" value="1" checked>
            <span class="tog-track"><span class="tog-thumb"></span></span></label>
          <span style="font-size:13px;">מעברים ואנימציות — כיבוי משפר ביצועים</span>
        </label>
      </div>

    </div>
  </div>

  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <button class="btn btn-primary" onclick="savePreferences()"><i class="bi bi-check-lg"></i> שמור העדפות</button>
    <button class="btn btn-ghost" onclick="resetPreferences()"><i class="bi bi-arrow-counterclockwise"></i> אפס</button>
    <div id="save-status" style="font-size:13px;display:none;padding:5px 12px;border-radius:6px;"></div>
    <div style="margin-right:auto;font-size:12px;color:var(--text3);"><i class="bi bi-cloud-check"></i> נשמר ב-localStorage + DB</div>
  </div>
</div>

<style>
.pref-block{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;margin-bottom:16px}
.pref-block-title{font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;display:flex;align-items:center;gap:7px;padding-bottom:10px;border-bottom:1px solid var(--border)}
.mode-row{display:flex;gap:10px;flex-wrap:wrap}
.mode-opt{cursor:pointer}
.mode-opt input{display:none}
.mode-opt-inner{display:flex;align-items:center;gap:8px;padding:8px 18px;border-radius:8px;border:2px solid var(--border);font-size:14px;font-weight:500;color:var(--text2);transition:all .13s}
.mode-opt:has(input:checked) .mode-opt-inner{border-color:var(--accent);color:var(--accent);background:var(--accent-dim)}
.palette-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px}
.pal-card{cursor:pointer;border:2px solid var(--border);border-radius:8px;overflow:hidden;transition:border-color .13s,transform .13s}
.pal-card:hover{transform:translateY(-2px);border-color:var(--border2)}
.pal-card.selected{border-color:var(--accent)}
.pal-swatches{height:32px;display:flex}
.pal-name{padding:4px 8px;font-size:11px;font-weight:500;background:var(--bg3);color:var(--text2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.options-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px}
.opt-card{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px 16px}
.opt-label{font-size:12px;font-weight:600;color:var(--text2);margin-bottom:10px}
.chip-row{display:flex;gap:6px;flex-wrap:wrap}
.chip{cursor:pointer}
.chip input{display:none}
.chip-inner{display:inline-block;padding:5px 12px;border-radius:6px;font-size:13px;border:1.5px solid var(--border2);color:var(--text2);transition:all .13s;background:var(--bg4)}
.chip:has(input:checked) .chip-inner{border-color:var(--accent);color:var(--accent);background:var(--accent-dim)}
.toggle-row{display:flex;align-items:center;gap:10px;cursor:pointer}
.tog{cursor:pointer;flex-shrink:0}
.tog input{display:none}
.tog-track{display:block;width:40px;height:22px;background:var(--bg4);border:1px solid var(--border2);border-radius:11px;position:relative;transition:background .2s}
.tog:has(input:checked) .tog-track{background:var(--accent);border-color:var(--accent)}
.tog-thumb{position:absolute;top:2px;right:2px;width:18px;height:18px;background:var(--text2);border-radius:50%;transition:right .2s,background .2s}
.tog:has(input:checked) .tog-thumb{right:calc(100% - 20px);background:#fff}

.font-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px}
.font-card{cursor:pointer;border:2px solid var(--border);border-radius:8px;padding:12px 14px;transition:border-color .13s,transform .13s,background .13s}
.font-card:hover{border-color:var(--border2);transform:translateY(-2px);background:var(--bg3)}
.font-card.selected{border-color:var(--accent);background:var(--accent-dim)}
.font-preview{font-size:18px;font-weight:600;margin-bottom:5px;color:var(--text)}
.font-name{font-size:12px;font-weight:700;color:var(--text2)}
.font-desc{font-size:11px;color:var(--text3);margin-top:2px}
</style>

<script>
const _P_BASE=typeof BASE!=='undefined'?BASE:'<?= $base ?>';
const _P_CSRF='<?= View::e($csrf) ?>';
const SAVED_DB=<?= $prefsJson ?? 'null' ?>;
const PALETTES=[
  {id:'blue',name:'Ocean Blue',colors:['#1a1e2b','#5b8dee','#3d6be8','#1e2a45']},
  {id:'purple',name:'Deep Purple',colors:['#1c1525','#8b5cf6','#7c3aed','#2d1f4a']},
  {id:'cyan',name:'Cyan Wave',colors:['#0f1e20','#06b6d4','#0891b2','#143040']},
  {id:'emerald',name:'Emerald',colors:['#0d1f18','#10b981','#059669','#0f2e24']},
  {id:'rose',name:'Rose Gold',colors:['#1f1218','#f43f5e','#e11d48','#3a1a25']},
  {id:'amber',name:'Amber',colors:['#1f1a0e','#f59e0b','#d97706','#332b14']},
  {id:'indigo',name:'Midnight',colors:['#11131f','#6366f1','#4f46e5','#1e2048']},
  {id:'teal',name:'Deep Teal',colors:['#0d1e1c','#14b8a6','#0d9488','#153530']},
  {id:'orange',name:'Sunset',colors:['#1f1610','#f97316','#ea6c0a','#301f10']},
  {id:'pink',name:'Neon Pink',colors:['#1f1220','#ec4899','#db2777','#351530']},
  {id:'slate',name:'Slate Pro',colors:['#0f1117','#64748b','#475569','#1a1f2e']},
  {id:'lime',name:'Lime',colors:['#111a0d','#84cc16','#65a30d','#1c2e10']},
  {id:'sky',name:'Sky Clear',colors:['#0c1825','#38bdf8','#0ea5e9','#0f2540']},
  {id:'violet',name:'Electric',colors:['#150d2a','#a78bfa','#8b5cf6','#25154a']},
  {id:'red',name:'Ruby Red',colors:['#1e0d0d','#ef4444','#dc2626','#330f0f']},
  {id:'green',name:'Forest',colors:['#0d1e12','#22c55e','#16a34a','#122b1a']},
  {id:'gold',name:'Gold Dark',colors:['#1c1808','#eab308','#ca8a04','#2e2608']},
  {id:'navy',name:'Navy Steel',colors:['#090d1a','#3b82f6','#2563eb','#0f1633']},
  {id:'coral',name:'Coral',colors:['#1f1410','#fb923c','#f97316','#32200f']},
  {id:'mono',name:'Monochrome',colors:['#111111','#a3a3a3','#737373','#1f1f1f']},
];
const DEFAULT={mode:'dark',palette:'blue',radius:'10',shadows:'medium',btn_style:'filled',glass:false,animations:true,density:'normal',font:'assistant'};
const localPrefs=JSON.parse(localStorage.getItem('v2_prefs')||'{}');
let prefs={...DEFAULT,...(SAVED_DB||{}),...localPrefs};

function buildPaletteGrid(){
  document.getElementById('palette-grid').innerHTML=PALETTES.map(p=>
    `<div class="pal-card${prefs.palette===p.id?' selected':''}" onclick="pickPalette('${p.id}',this)">
      <div class="pal-swatches">${p.colors.map(c=>`<div style="flex:1;background:${c}"></div>`).join('')}</div>
      <div class="pal-name">${p.name}</div>
    </div>`).join('');
}

function buildFontGrid() {
  const fonts = [
    {id:'assistant', name:'Assistant',    preview:'שלום Hello 123', desc:'ברירת מחדל — עברית מעולה'},
    {id:'heebo',     name:'Heebo',        preview:'שלום Hello 123', desc:'עגול ואלגנטי'},
    {id:'rubik',     name:'Rubik',        preview:'שלום Hello 123', desc:'מודרני ומקצועי'},
    {id:'inter',     name:'Inter',        preview:'Hello World 123',desc:'מושלם לממשקים'},
    {id:'ibm',       name:'IBM Plex Sans',preview:'שלום Hello 123', desc:'טכני ונקי'},
    {id:'nunito',    name:'Nunito',       preview:'שלום Hello 123', desc:'ידידותי ועגול'},
  ];
  document.getElementById('font-grid').innerHTML = fonts.map(f =>
    `<div class="font-card${prefs.font===f.id?' selected':''}" data-font-id="${f.id}" onclick="pickFont('${f.id}',this)">
      <div class="font-preview" style="font-family:'${f.name}',sans-serif">${f.preview}</div>
      <div class="font-name">${f.name}</div>
      <div class="font-desc">${f.desc}</div>
    </div>`
  ).join('');
}
function pickFont(id, el) {
  document.querySelectorAll('.font-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  prefs.font = id;
  preview();
}
function pickPalette(id,el){document.querySelectorAll('.pal-card').forEach(c=>c.classList.remove('selected'));el.classList.add('selected');prefs.palette=id;preview();}
function initForm(){
  ['mode','radius','shadows','btn_style','density'].forEach(n=>{const el=document.querySelector(`[name="${n}"][value="${prefs[n]}"]`);if(el)el.checked=true;});
  const g=document.querySelector('[name="glass"]');const a=document.querySelector('[name="animations"]');
  if(g)g.checked=!!prefs.glass;if(a)a.checked=prefs.animations!==false;
}
function readForm(){
  ['mode','radius','shadows','btn_style','density'].forEach(n=>{const el=document.querySelector(`[name="${n}"]:checked`);if(el)prefs[n]=el.value;});
  prefs.glass=!!document.querySelector('[name="glass"]')?.checked;
  prefs.animations=document.querySelector('[name="animations"]')?.checked!==false;
}
function preview(){readForm();if(window.applyV2Prefs)window.applyV2Prefs(prefs);}
async function savePreferences(){
  readForm();prefs._updated=Date.now();
  localStorage.setItem('v2_prefs',JSON.stringify(prefs));
  const st=document.getElementById('save-status');
  st.style.display='inline';st.style.color='var(--text2)';st.textContent='⏳ שומר...';st.style.background='var(--bg3)';
  try{
    const res=await fetch(_P_BASE+'/preferences/save',{method:'POST',body:new URLSearchParams({_csrf:_P_CSRF,prefs:JSON.stringify(prefs)})});
    const d=await res.json();
    if(d.ok){st.style.color='var(--success)';st.style.background='rgba(34,197,94,.1)';st.textContent='✓ נשמר בהצלחה';}
    else throw new Error(d.error);
  }catch(e){st.style.color='var(--warning)';st.textContent='⚠ localStorage בלבד';}
  setTimeout(()=>st.style.display='none',3000);
  if(window.applyV2Prefs)window.applyV2Prefs(prefs);
}
function resetPreferences(){prefs={...DEFAULT};localStorage.removeItem('v2_prefs');initForm();buildPaletteGrid();buildFontGrid();if(window.applyV2Prefs)window.applyV2Prefs(prefs);}
document.addEventListener('change',e=>{if(e.target.classList.contains('pref-radio')||e.target.classList.contains('pref-toggle'))preview();});
buildPaletteGrid();buildFontGrid();initForm();
</script>
