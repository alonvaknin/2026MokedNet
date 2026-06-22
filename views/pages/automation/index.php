<?php
use Core\View;
$base       = rtrim(CFG['app']['url'], '/');
$canViewAll = $canViewAll ?? false;
$agents     = $agents ?? [];
?>
<style>
.aut-toolbar{display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap;}
.aut-search-wrap{display:flex;align-items:center;gap:7px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0 12px;flex:1;max-width:320px;transition:border-color .15s,box-shadow .15s;}
.aut-search-wrap:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px rgba(91,141,238,.12);}
.aut-search-wrap i{color:var(--text3);font-size:13px;flex-shrink:0;}
.aut-search-wrap input{background:none;border:none;outline:none;color:var(--text);font-family:var(--font,sans-serif);font-size:13px;padding:8px 0;width:100%;}
.aut-search-wrap input::placeholder{color:var(--text3);}
.aut-sel{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:7px 11px;color:var(--text);font-size:13px;font-family:var(--font,sans-serif);outline:none;cursor:pointer;transition:border-color .15s;}
.aut-sel:focus{border-color:var(--accent);}

/* table */
.aut-wrap{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;}
.aut-table{width:100%;border-collapse:collapse;font-size:13px;}
.aut-table thead th{background:var(--bg3);padding:9px 13px;font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid var(--border);white-space:nowrap;}
.aut-table tbody td{padding:9px 13px;border-bottom:1px solid var(--border);color:var(--text2);vertical-align:middle;}
.aut-table tbody tr:last-child td{border-bottom:none;}
.aut-table tbody tr:hover td{background:rgba(255,255,255,.025);}
.aut-table tbody tr.row-ended td{opacity:.45;}
.aut-badge{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;}
.aut-badge-active{background:rgba(34,197,94,.13);color:#22c55e;}
.aut-badge-done{background:rgba(124,130,156,.1);color:var(--text3);}
.aut-badge-cancelled{background:rgba(239,68,68,.1);color:#ef4444;}
.aut-empty{text-align:center;padding:44px 20px;color:var(--text3);}
.aut-empty i{font-size:34px;display:block;margin-bottom:10px;opacity:.3;}

/* pagination */
.aut-pg{display:flex;align-items:center;gap:8px;padding:11px 14px;border-top:1px solid var(--border);}
.aut-pg-btn{display:inline-flex;align-items:center;gap:4px;padding:5px 11px;border-radius:6px;font-size:13px;font-family:var(--font,sans-serif);border:1px solid var(--border);background:var(--bg3);color:var(--text2);cursor:pointer;transition:all .12s;}
.aut-pg-btn:hover:not(:disabled){background:var(--accent-dim);color:var(--accent);border-color:rgba(91,141,238,.4);}
.aut-pg-btn:disabled{opacity:.38;cursor:default;}
.aut-pg-info{font-size:12px;color:var(--text3);margin-right:auto;}

/* collapsed section */
.aut-collapse-hdr{display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--bg3);border-top:1px solid var(--border);cursor:pointer;user-select:none;transition:background .12s;}
.aut-collapse-hdr:hover{background:var(--bg4);}
.aut-collapse-hdr i.chev{transition:transform .22s;}
.aut-collapse-hdr.open i.chev{transform:rotate(180deg);}
.aut-collapse-body{display:none;}
.aut-collapse-body.open{display:block;}
</style>

<!-- ── Page header ── -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
  <div class="page-title" style="margin-bottom:0;">
    <i class="bi bi-lightning-charge-fill" style="color:var(--accent);"></i> משימות אוטומטיות
  </div>
  <button class="btn btn-primary" onclick="openAutomationModal()">
    <i class="bi bi-plus-lg"></i> משימה חדשה
  </button>
</div>

<!-- ── Toolbar ── -->
<div class="aut-toolbar">

  <!-- Search -->
  <div class="aut-search-wrap">
    <i class="bi bi-search"></i>
    <input type="search" id="aut-q" placeholder="חיפוש קריאה, טלפון, מייל, הודעה..." autocomplete="off"
           oninput="_autDebouncedLoad()">
  </div>

  <!-- Status filter -->
  <select class="aut-sel" id="aut-status" onchange="_autLoad()">
    <option value="">כל הסטטוסים</option>
    <option value="active">פעילות בלבד</option>
    <option value="closed">סגורות בלבד</option>
  </select>

  <?php if ($canViewAll): ?>
  <!-- Agent filter -->
  <select class="aut-sel" id="aut-agent" onchange="_autLoad()">
    <option value="0">כל הנציגים</option>
    <?php foreach ($agents as $a): ?>
      <option value="<?= (int)$a['userID'] ?>"><?= View::e($a['userName']) ?></option>
    <?php endforeach; ?>
  </select>
  <?php endif; ?>

  <!-- Refresh -->
  <button class="btn btn-ghost" onclick="_autLoad()" title="רענן" style="padding:7px 10px;">
    <i class="bi bi-arrow-clockwise"></i>
  </button>

  <span id="aut-loading" style="display:none;font-size:12px;color:var(--text3);">
    <i class="bi bi-hourglass-split"></i> טוען...
  </span>
</div>

<!-- ── Active jobs table ── -->
<div class="aut-wrap" id="aut-active-wrap">
  <div style="overflow-x:auto;">
    <table class="aut-table">
      <thead>
        <tr>
          <th>תאריך פתיחה</th>
          <th>נציג</th>
          <th>סוג משימה</th>
          <th>תנאי / סטטוס</th>
          <th>קריאה / טלפון</th>
          <th>שלח ל</th>
          <th>הודעה</th>
          <th>סטטוס</th>
          <th>תפוגה</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="aut-active-body">
        <tr><td colspan="10" class="aut-empty"><i class="bi bi-hourglass-split"></i>טוען...</td></tr>
      </tbody>
    </table>
  </div>
  <div class="aut-pg" id="aut-pg" style="display:none;">
    <button class="aut-pg-btn" id="aut-pg-prev" onclick="_autPage(-1)"><i class="bi bi-chevron-right"></i> הקודם</button>
    <button class="aut-pg-btn" id="aut-pg-next" onclick="_autPage(1)">הבא <i class="bi bi-chevron-left"></i></button>
    <span class="aut-pg-info" id="aut-pg-info"></span>
  </div>
</div>

<!-- ── Closed jobs — collapsed by default ── -->
<div class="aut-wrap" id="aut-closed-wrap" style="margin-top:14px;display:none;">
  <div class="aut-collapse-hdr" id="aut-closed-hdr" onclick="_autToggleClosed()">
    <i class="bi bi-chevron-down chev"></i>
    <span style="font-size:13px;font-weight:600;color:var(--text2);">משימות סגורות</span>
    <span id="aut-closed-count" class="badge badge-info" style="margin-right:4px;"></span>
  </div>
  <div class="aut-collapse-body" id="aut-closed-body-wrap">
    <div style="overflow-x:auto;">
      <table class="aut-table">
        <thead>
          <tr>
            <th>תאריך פתיחה</th>
            <th>נציג</th>
            <th>סוג משימה</th>
            <th>תנאי / סטטוס</th>
            <th>קריאה / טלפון</th>
            <th>שלח ל</th>
            <th>הודעה</th>
            <th>סטטוס</th>
            <th>הסתיים</th>
          </tr>
        </thead>
        <tbody id="aut-closed-body">
          <tr><td colspan="9" class="aut-empty"><i class="bi bi-hourglass-split"></i>טוען...</td></tr>
        </tbody>
      </table>
    </div>
    <div class="aut-pg" id="aut-cpg" style="display:none;">
      <button class="aut-pg-btn" id="aut-cpg-prev" onclick="_autCPage(-1)"><i class="bi bi-chevron-right"></i> הקודם</button>
      <button class="aut-pg-btn" id="aut-cpg-next" onclick="_autCPage(1)">הבא <i class="bi bi-chevron-left"></i></button>
      <span class="aut-pg-info" id="aut-cpg-info"></span>
    </div>
  </div>
</div>

<script>
(function(){
  const BASE_A  = '<?= $base ?>';
  const CSRF_A  = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>';
  const CAN_ALL = <?= $canViewAll ? 'true' : 'false' ?>;
  const LIMIT   = 30;

  let _aOff=0, _aTotal=0;   // active
  let _cOff=0, _cTotal=0;   // closed
  let _debTimer=null;
  let _closedLoaded=false;

  /* ── helpers ── */
  const $  = id => document.getElementById(id);
  const E  = s  => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

  function _params(extra={}){
    const p={
      all:    CAN_ALL && (!$('aut-agent') || $('aut-agent').value==='0') ? '1':'0',
      agent:  $('aut-agent')?.value||'0',
      q:      $('aut-q')?.value.trim()||'',
      limit:  LIMIT,
    };
    return {...p,...extra};
  }

  function _qs(obj){return new URLSearchParams(obj).toString();}

  /* ── debounce for search input ── */
  window._autDebouncedLoad=function(){
    clearTimeout(_debTimer);
    _debTimer=setTimeout(()=>_autLoad(),380);
  };

  /* ── Main load (active) ── */
  window._autLoad=async function(resetPage=true){
    if(resetPage) _aOff=0;
    $('aut-loading').style.display='inline-flex';

    const statusFilter=$('aut-status')?.value||'';

    // If filter is "closed only" → show closed section, hide active
    if(statusFilter==='closed'){
      $('aut-active-wrap').style.display='none';
      $('aut-closed-wrap').style.display='block';
      $('aut-closed-hdr').classList.add('open');
      $('aut-closed-body-wrap').classList.add('open');
      await _autLoadClosed(true);
      $('aut-loading').style.display='none';
      return;
    }

    $('aut-active-wrap').style.display='block';

    try{
      const p=_params({status:'active',offset:_aOff});
      const r=await fetch(`${BASE_A}/api/automation?${_qs(p)}`,{credentials:'include'});
      const d=await r.json();
      _aTotal=d.total||0;
      _renderRows('aut-active-body',d.rows||[],true);
      _renderPg('aut-pg','aut-pg-prev','aut-pg-next','aut-pg-info',_aOff,_aTotal);
    }catch(e){
      $('aut-active-body').innerHTML='<tr><td colspan="10" class="aut-empty" style="color:var(--danger);"><i class="bi bi-wifi-off"></i><br>שגיאת תקשורת</td></tr>';
    }

    // Always load closed count (for badge), but not rows unless open
    await _autLoadClosedCount();

    $('aut-loading').style.display='none';
  };

  window._autPage=function(dir){_aOff=Math.max(0,_aOff+dir*LIMIT);_autLoad(false);};

  /* ── Closed section ── */
  async function _autLoadClosedCount(){
    try{
      const p=_params({status:'closed',offset:0,limit:1});
      const r=await fetch(`${BASE_A}/api/automation?${_qs(p)}`,{credentials:'include'});
      const d=await r.json();
      _cTotal=d.total||0;
      const badge=$('aut-closed-count');
      if(badge) badge.textContent=_cTotal>0?_cTotal:'';
      $('aut-closed-wrap').style.display=_cTotal>0?'block':'none';
    }catch(e){}
  }

  window._autLoadClosed=async function(resetPage=true){
    if(resetPage) _cOff=0;
    try{
      const p=_params({status:'closed',offset:_cOff});
      const r=await fetch(`${BASE_A}/api/automation?${_qs(p)}`,{credentials:'include'});
      const d=await r.json();
      _cTotal=d.total||0;
      _renderRows('aut-closed-body',d.rows||[],false);
      const badge=$('aut-closed-count');
      if(badge) badge.textContent=_cTotal>0?_cTotal:'';
      _renderPg('aut-cpg','aut-cpg-prev','aut-cpg-next','aut-cpg-info',_cOff,_cTotal);
      _closedLoaded=true;
    }catch(e){
      $('aut-closed-body').innerHTML='<tr><td colspan="9" class="aut-empty" style="color:var(--danger);"><i class="bi bi-wifi-off"></i><br>שגיאת תקשורת</td></tr>';
    }
  };

  window._autCPage=function(dir){_cOff=Math.max(0,_cOff+dir*LIMIT);_autLoadClosed(false);};

  window._autToggleClosed=function(){
    const hdr=$('aut-closed-hdr');
    const body=$('aut-closed-body-wrap');
    const isOpen=hdr.classList.toggle('open');
    body.classList.toggle('open',isOpen);
    if(isOpen && !_closedLoaded) _autLoadClosed();
  };

  /* ── Render rows ── */
  function _renderRows(tbodyId, rows, showCancel){
    const tb=$(tbodyId);
    const cols=showCancel?10:9;
    if(!rows.length){
      tb.innerHTML=`<tr><td colspan="${cols}" class="aut-empty"><i class="bi bi-lightning"></i><br>אין משימות</td></tr>`;
      return;
    }
    let html='';
    rows.forEach(row=>{
      const active=row.isactive==1;
      const badge=active
        ?'<span class="aut-badge aut-badge-active">פעילה</span>'
        :row.statusOfJob==='בוטל'
          ?'<span class="aut-badge aut-badge-cancelled">בוטל</span>'
          :'<span class="aut-badge aut-badge-done">הסתיימה</span>';

      const msg=(row.msgFromUser||'').trim();
      const msgHtml=msg.length>24
        ?`<span title="${E(msg)}" style="cursor:help;border-bottom:1px dotted var(--border2);">${E(msg.slice(0,22))}…</span>`
        :E(msg);

      const cc=(row.toCcmail||'').trim();
      const ccHtml=cc.length>22
        ?`<span title="${E(cc)}" style="cursor:help;border-bottom:1px dotted var(--border2);">${E(cc.slice(0,20))}…</span>`
        :E(cc);

      const mailCell=[E(row.mailto||''),ccHtml].filter(Boolean).join('<br>');

      const lastCol=showCancel&&active
        ?`<button onclick="_autCancel(${row.id})"
             style="background:none;border:1px solid rgba(239,68,68,.3);border-radius:6px;
                    padding:3px 8px;color:var(--danger);cursor:pointer;font-size:11px;
                    font-family:var(--font,sans-serif);transition:background .12s;"
             onmouseover="this.style.background='rgba(239,68,68,.1)'"
             onmouseout="this.style.background='none'">
             <i class="bi bi-x-lg"></i> בטל
           </button>`:showCancel?'':'';

      const dateCol=showCancel?E(row.upToDateFmt||''):E(row.statusChangeFmt||'—');
      const dateTh=showCancel?'תפוגה':'הסתיים';

      html+=`<tr class="${active?'':'row-ended'}">
        <td style="white-space:nowrap;font-size:12px;">${E(row.addJobTimeFmt||'')}</td>
        <td>${E(row.userName||'')}</td>
        <td style="font-size:12px;">${E(row.typeLabel||row.typeOfJob||'')}</td>
        <td style="font-size:12px;">${E(row.conditionLabel||row.conditionOfType||'—')}</td>
        <td><strong>${E(row.valueOfType||'—')}</strong></td>
        <td style="font-size:11px;">${mailCell}</td>
        <td>${msgHtml}</td>
        <td>${badge}</td>
        <td style="white-space:nowrap;font-size:12px;">${dateCol}</td>
        ${showCancel?`<td>${lastCol}</td>`:''}
      </tr>`;
    });
    tb.innerHTML=html;
  }

  /* ── Pagination ── */
  function _renderPg(pgId,prevId,nextId,infoId,offset,total){
    const pg=$(pgId);
    if(total<=LIMIT){pg.style.display='none';return;}
    pg.style.display='flex';
    const from=offset+1,to=Math.min(offset+LIMIT,total);
    $(infoId).textContent=`מציג ${from}–${to} מתוך ${total}`;
    $(prevId).disabled=offset===0;
    $(nextId).disabled=offset+LIMIT>=total;
  }

  /* ── Cancel ── */
  window._autCancel=async function(id){
    if(!confirm('לבטל משימה #'+id+'?'))return;
    try{
      const r=await fetch(`${BASE_A}/automation/${id}/cancel`,{
        method:'POST',credentials:'include',
        headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':CSRF_A},
        body:`_csrf=${encodeURIComponent(CSRF_A)}`,
      });
      const d=await r.json();
      if(d.ok){if(typeof v2Toast==='function')v2Toast('✓ משימה בוטלה');_autLoad();}
      else alert(d.msg||'שגיאה בביטול');
    }catch(e){alert('שגיאת תקשורת');}
  };

  /* ── Init ── */
  if(document.readyState==='loading')
    document.addEventListener('DOMContentLoaded',()=>_autLoad());
  else _autLoad();

  // Reload after adding new automation
  const _origOpen=window.openAutomationModal;
  if(typeof _origOpen==='function'){
    window.openAutomationModal=function(){
      _origOpen();
      // hook: reload table after modal closes (לא override, רק side-effect)
    };
  }
})();
</script>
