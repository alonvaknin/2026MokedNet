<?php
/**
 * PBX Search – V2
 * הרשאות: pbxSearch + pbxRecordings ב-permission_group_grants
 */
$hasPbxSearch = \Core\Auth::can('pbxSearch');
$hasPbxRec    = \Core\Auth::can('pbxRecordings');

?>
<?php if (!$hasPbxSearch): ?>
<script>
/* stub — user has no pbxSearch permission; nav item shows toast instead of error */
function openPbxModal(){if(typeof v2Toast==='function')v2Toast('אין הרשאה לחיפוש שיחות מרכזיה');}
function closePbxModal(){}
function pbxSearch(){}
</script>
<?php return; endif; ?>
<!-- PBX Search Modal – V2 -->
<style>
#pbx-ov{position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:490;display:none;backdrop-filter:blur(2px);}
#pbx-ov.pbxshow{display:block;}
#pbx-modal{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(.95);background:var(--bg2);border:1px solid var(--border2);border-radius:14px;box-shadow:0 24px 80px rgba(0,0,0,.65);z-index:491;width:min(900px,calc(100vw - 32px));max-height:88vh;display:none;flex-direction:column;direction:rtl;font-family:var(--font);transition:opacity .18s,transform .18s;opacity:0;}
#pbx-modal.pbxshow{display:flex;opacity:1;transform:translate(-50%,-50%) scale(1);}
#pbx-hdr{display:flex;align-items:center;gap:12px;padding:14px 18px 12px;border-bottom:1px solid var(--border);background:var(--bg3);border-radius:14px 14px 0 0;flex-shrink:0;}
#pbx-hdr-icon{width:36px;height:36px;border-radius:9px;background:rgba(33,150,243,.15);border:1px solid rgba(33,150,243,.3);display:flex;align-items:center;justify-content:center;font-size:17px;color:#2196f3;flex-shrink:0;}
#pbx-hdr-text{flex:1;}
#pbx-hdr-text h3{font-size:15px;font-weight:700;color:var(--text);margin:0;}
#pbx-hdr-text p{font-size:11px;color:var(--text3);margin:0;}
#pbx-closebtn{width:32px;height:32px;background:none;border:none;color:var(--text3);font-size:18px;cursor:pointer;border-radius:7px;transition:all .13s;display:flex;align-items:center;justify-content:center;}
#pbx-closebtn:hover{background:rgba(239,68,68,.12);color:#ef4444;}
#pbx-sarea{padding:13px 18px 11px;border-bottom:1px solid var(--border);background:var(--bg3);flex-shrink:0;}
.pbx-srow{display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;}
.pbx-fld{display:flex;flex-direction:column;gap:4px;}
.pbx-fld label{font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;}
.pbx-inp{background:var(--bg4);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:var(--font);font-size:14px;padding:8px 12px;outline:none;transition:border-color .13s,box-shadow .13s;}
.pbx-inp:focus{border-color:#2196f3;box-shadow:0 0 0 3px rgba(33,150,243,.12);}
#pbx-phone-input{width:190px;}
.pbx-sel{width:155px;cursor:pointer;-webkit-appearance:none;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' fill='%237c829c' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:left 10px center;padding-left:28px;}
.pbx-gobtn{display:flex;align-items:center;gap:6px;padding:9px 18px;background:#2196f3;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;font-family:var(--font);cursor:pointer;transition:all .13s;white-space:nowrap;align-self:flex-end;}
.pbx-gobtn:hover{background:#1976d2;transform:translateY(-1px);}
.pbx-gobtn:active{transform:scale(.96);}
.pbx-gobtn:disabled{opacity:.5;cursor:default;transform:none;}
#pbx-res{flex:1;overflow-y:auto;position:relative;min-height:100px;}
#pbx-res::-webkit-scrollbar{width:4px;}
#pbx-res::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px;}
.pbx-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:44px 20px;color:var(--text3);gap:10px;}
.pbx-empty i{font-size:36px;opacity:.3;}
.pbx-empty p{font-size:13px;}
#pbx-res table{width:100%;border-collapse:collapse;font-size:13px;}
#pbx-res th{background:var(--bg3);color:var(--text3);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:8px 12px;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:1;text-align:right;}
#pbx-res td{padding:9px 12px;border-bottom:1px solid var(--border);color:var(--text2);vertical-align:middle;}
#pbx-res tr:hover td{background:var(--bg3);}
#pbx-res audio{width:100%;max-width:320px;height:32px;border-radius:6px;outline:none;display:block;margin-top:4px;}
#pbx-res a{color:#2196f3;text-decoration:none;}
.pbx-recbtn{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border:1px solid rgba(33,150,243,.35);border-radius:20px;background:rgba(33,150,243,.1);color:#2196f3;cursor:pointer;font-size:11px;font-weight:600;font-family:var(--font);transition:all .13s;white-space:nowrap;}
.pbx-recbtn:hover{background:rgba(33,150,243,.2);}
.pbx-recbtn.no-access{border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.07);color:var(--danger);cursor:not-allowed;}
.pbx-stat{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;}
.pbx-stat-ok{background:rgba(34,197,94,.12);color:#22c55e;}
.pbx-stat-miss{background:rgba(239,68,68,.12);color:#ef4444;}
.pbx-stat-busy{background:rgba(245,158,11,.12);color:#f59e0b;}
.pbx-spin{border-radius:50%;border:2.5px solid var(--border2);border-top-color:#2196f3;animation:pbxspin .7s linear infinite;}
@keyframes pbxspin{to{transform:rotate(360deg)}}
#pbx-foot{padding:8px 18px;border-top:1px solid var(--border);background:var(--bg3);border-radius:0 0 14px 14px;display:flex;align-items:center;gap:10px;flex-shrink:0;font-size:11px;color:var(--text3);}
#pbx-cnt{flex:1;}
.pbx-kbdhint{display:flex;align-items:center;gap:3px;}
.pbx-kbdhint kbd{font-size:9px;background:var(--bg4);border:1px solid var(--border2);border-radius:4px;padding:1px 5px;font-family:var(--font);color:var(--text3);}
.pbx-norec{font-size:10px;color:var(--text3);font-style:italic;}
</style>

<div id="pbx-ov" onclick="closePbxModal()"></div>
<div id="pbx-modal" role="dialog">
  <div id="pbx-hdr">
    <div id="pbx-hdr-icon"><i class="bi bi-telephone-fill"></i></div>
    <div id="pbx-hdr-text">
      <h3>חיפוש שיחה במרכזיה</h3>
      <p>תיעוד שיחות לפי מספר טלפון<?= $hasPbxRec ? ' | האזנה להקלטה' : '' ?></p>
    </div>
    <button id="pbx-closebtn" onclick="closePbxModal()"><i class="bi bi-x-lg"></i></button>
  </div>
  <div id="pbx-sarea">
    <div class="pbx-srow">
      <div class="pbx-fld" style="flex:1;min-width:160px;">
        <label for="pbx-phone-input">מספר טלפון</label>
        <input type="tel" id="pbx-phone-input" class="pbx-inp" placeholder="05XXXXXXXX" maxlength="15" autocomplete="off">
      </div>
      <div class="pbx-fld">
        <label for="pbx-range">טווח חיפוש</label>
        <select id="pbx-range" class="pbx-inp pbx-sel">
          <option value="last1week" selected>שבוע אחרון</option>
          <option value="1MonthOld">חודש אחרון</option>
          <option value="halfYearOld">חצי שנה</option>
          <option value="1YearOld">שנה אחרונה</option>
        </select>
      </div>
      <div class="pbx-fld">
        <label for="pbx-source">מקור</label>
        <select id="pbx-source" class="pbx-inp pbx-sel">
          <option value="branches" selected>מוקד וחנויות</option>
          <option value="moked">מוקד בלבד</option>
        </select>
      </div>
      <button class="pbx-gobtn" id="pbx-gobtn" onclick="pbxSearch()">
        <i class="bi bi-search"></i> חיפוש
      </button>
    </div>
  </div>
  <div id="pbx-res">
    <div class="pbx-empty"><i class="bi bi-telephone-minus"></i><p>הכנס מספר טלפון ולחץ חיפוש</p></div>
  </div>
  <div id="pbx-foot">
    <span id="pbx-cnt"></span>
    <div class="pbx-kbdhint">
      <i class="bi bi-keyboard"></i>&nbsp;
      <kbd>Ctrl</kbd><kbd>Shift</kbd><kbd>P</kbd>&nbsp;פתיחה&nbsp;|&nbsp;<kbd>Enter</kbd>&nbsp;חיפוש
    </div>
  </div>
</div>

<script>
var _pbxCanSearch = <?= $hasPbxSearch ? 'true' : 'false' ?>;
var _pbxCanRec    = <?= $hasPbxRec    ? 'true' : 'false' ?>;

/* ══ 1. כפתור בסרגל עליון ══ */
// (function(){
//   var av = document.getElementById('topbar-av');
//   if(!av) return;
//   var btn = document.createElement('button');
//   btn.id='pbx-tb-btn'; btn.className='topbar-icon-btn';
//   btn.title='חיפוש שיחה (Ctrl+Shift+P)';
//   btn.innerHTML='<i class="bi bi-telephone-fill"></i>';
//   btn.onclick = openPbxModal;
//   av.parentNode.parentNode.insertBefore(btn, av.parentNode);
// })();

/* ══ 2. שילוב בחיפוש גלובלי (Ctrl+K) ══
   ─────────────────────────────────────────────────────────────────
   הגישה: לא ניתן לשנות function declarations מחוץ לscript שלהן.
   במקום לpatch את gsSearch, מוסיפים event listener ישיר על gs-input
   עם stopImmediatePropagation כדי לטפל בPBX scope לפני main.php.
   ─────────────────────────────────────────────────────────────────*/
(function pbxGsHook(){

  /* a) הפעל scope button (disabled → active) */
  var enableScopeBtn = function(){
    var btn = document.querySelector('.gs-scope[data-scope="pbx"]');
    if(!btn) return;
    btn.disabled = false;
    btn.classList.remove('gs-scope-soon');
    btn.title = 'חיפוש שיחות מרכזיה';
    /* הסר "בקרוב" span */
    var soon = btn.querySelector('span');
    if(soon && soon.textContent.trim()==='בקרוב') soon.remove();
    /* כפתור PBX מפעיל גם hint מותאם */
    btn.addEventListener('click', function(){
      setTimeout(function(){
        var res = document.getElementById('gs-results');
        if(res){
          var empty = res.querySelector('.gs-empty');
          if(empty){
            /* הוסף hint אם לא קיים */
            if(!empty.querySelector('.pbx-hint')){
              var hint = document.createElement('div');
              hint.className='pbx-hint';
              hint.style.cssText='font-size:11px;margin-top:5px;opacity:.55;';
              hint.textContent='מספר טלפון לחיפוש שיחות מרכזיה';
              empty.appendChild(hint);
            }
          }
        }
      }, 10);
    });
  };

  /* b) ✦ המפתח: keydown על gs-input לטיפול בPBX scope
        event.stopImmediatePropagation() מונע מmain.php להריץ gsSearch()
        (שלא מכיר pbx ויחזיר ריק) */
  var hookGsInput = function(){
    var inp = document.getElementById('gs-input');
    if(!inp) return;
    inp.addEventListener('keydown', function(e){
      if(e.key !== 'Enter') return;
      /* בדוק scope PBX — _gsScope מוגדר ב-let בmain.php אבל נגיש כ-global lexical */
      if(typeof _gsScope === 'undefined' || _gsScope !== 'pbx') return;
      e.stopImmediatePropagation(); /* עצור את main.php מלקרוא gsSearch */
      e.preventDefault();
      var q = inp.value.trim();
      if(typeof gsClose === 'function') gsClose();
      openPbxModal();
      if(q){
        var pi = document.getElementById('pbx-phone-input');
        if(pi){ pi.value = q; pbxSearch(); }
      }
    }, true); /* capture phase — לפני כל bubble handler */
  };

  /* c) גם ArrowRight/Left בgs-input: הוסף pbx לסיבוב */
  var hookArrowNav = function(){
    var inp = document.getElementById('gs-input');
    if(!inp) return;
    inp.addEventListener('keydown', function(e){
      if(e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
      if(typeof _gsScope === 'undefined') return;
      /* הscopes של main.php: stores, calls, contacts — לא כולל pbx */
      var allScopes = ['stores','calls','contacts','pbx'];
      var idx = allScopes.indexOf(_gsScope);
      if(idx < 0) return; /* נתן לmain.php לטפל */
      /* RTL: Right=prev, Left=next */
      var dir = e.key === 'ArrowRight' ? -1 : 1;
      var next = allScopes[(idx + dir + allScopes.length) % allScopes.length];
      if(typeof gsSetScope === 'function'){
        e.stopImmediatePropagation();
        gsSetScope(next);
        /* הוסף hint לPBX */
        if(next === 'pbx'){
          setTimeout(function(){
            var res = document.getElementById('gs-results');
            var empty = res && res.querySelector('.gs-empty');
            if(empty && !empty.querySelector('.pbx-hint')){
              var h = document.createElement('div');
              h.className='pbx-hint';
              h.style.cssText='font-size:11px;margin-top:5px;opacity:.55;';
              h.textContent='מספר טלפון לחיפוש שיחות מרכזיה';
              empty.appendChild(h);
            }
          }, 10);
        }
      }
    }, true);
  };

  /* הרץ לאחר שהDOM מוכן */
  var init = function(){
    enableScopeBtn();
    hookGsInput();
    hookArrowNav();
  };
  document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', init)
    : init();
})();

/* ══ 3. פתיחה / סגירה ══ */
function openPbxModal(){
  document.getElementById('pbx-ov').classList.add('pbxshow');
  document.getElementById('pbx-modal').classList.add('pbxshow');
  var tb = document.getElementById('pbx-tb-btn');
  if(tb){tb.style.background='rgba(33,150,243,.15)';tb.style.color='#2196f3';tb.style.borderColor='rgba(33,150,243,.4)';}
  setTimeout(function(){var el=document.getElementById('pbx-phone-input');if(el)el.focus();}, 130);
}
function closePbxModal(){
  document.getElementById('pbx-ov').classList.remove('pbxshow');
  document.getElementById('pbx-modal').classList.remove('pbxshow');
  var tb = document.getElementById('pbx-tb-btn');
  if(tb) tb.style.cssText='';
  pbxSetEmpty('הכנס מספר טלפון ולחץ חיפוש');
  document.getElementById('pbx-cnt').textContent='';
}

/* ══ 4. חיפוש ══ */
function pbxSearch(){
  if(!_pbxCanSearch) return;
  var phone = (document.getElementById('pbx-phone-input').value||'').trim();
  if(!phone || phone.replace(/\D/g,'').length < 7){
    var inp = document.getElementById('pbx-phone-input');
    inp.style.borderColor='var(--danger)'; inp.focus();
    setTimeout(function(){inp.style.borderColor='';}, 900);
    pbxSetEmpty('נא להזין מספר טלפון תקין');
    return;
  }
  pbxSetLoading(true);
  document.getElementById('pbx-cnt').textContent='מחפש שיחות...';
  var range = document.getElementById('pbx-range').value;
  fetch('/api/crm/calls?phone='+encodeURIComponent(phone)+'&range='+encodeURIComponent(range), {credentials:'include'})
  .then(function(r){if(!r.ok)throw new Error('HTTP '+r.status);return r.json();})
  .then(function(data){
    pbxSetLoading(false);
    if(!data.ok || !data.data || !data.data.length){
      pbxSetEmpty('לא נמצאו שיחות עבור '+phone);
      document.getElementById('pbx-cnt').textContent='אין תוצאות';
      return;
    }
    var rows = data.data;
    var E = function(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');};
    var TH = 'text-align:right;padding:8px 12px;background:var(--bg3);color:var(--text3);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:1;';
    var TD = 'padding:9px 12px;border-bottom:1px solid var(--border);vertical-align:middle;';

    var dirLabel = function(d){
      if(d==='in'||d==='inbound')  return '<span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#22c55e;"><i class="bi bi-telephone-inbound-fill"></i> נכנסת</span>';
      if(d==='out'||d==='outbound')return '<span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#5b8dee;"><i class="bi bi-telephone-outbound-fill"></i> יוצאת</span>';
      return '<span style="font-size:11px;color:var(--text3);">'+E(d)+'</span>';
    };
    var statLabel = function(s){
      if(s==='answer')   return '<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:rgba(34,197,94,.12);color:#22c55e;"><i class="bi bi-check-circle-fill"></i> ענה</span>';
      if(s==='ivr')      return '<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:rgba(245,158,11,.12);color:#f59e0b;"><i class="bi bi-hourglass-split"></i> המתנה</span>';
      if(s==='noanswer'||s==='no answer') return '<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:rgba(239,68,68,.12);color:#ef4444;"><i class="bi bi-x-circle-fill"></i> לא ענה</span>';
      if(s==='busy')     return '<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:rgba(239,68,68,.12);color:#ef4444;"><i class="bi bi-dash-circle-fill"></i> תפוס</span>';
      if(s==='cancel')   return '<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:rgba(239,68,68,.08);color:#ef4444;"><i class="bi bi-slash-circle-fill"></i> בוטל</span>';
      return '<span style="font-size:11px;color:var(--text3);">'+E(s)+'</span>';
    };
    var fmtDuration = function(secs){
      secs = parseInt(secs)||0;
      if(!secs) return '<span style="color:var(--text3);font-size:11px;">—</span>';
      var h=Math.floor(secs/3600), m=Math.floor((secs%3600)/60), s=secs%60;
      var label='';
      if(h) label+=h+'<span style="font-size:10px;font-weight:500;opacity:.7;">שע׳</span> ';
      if(m||h) label+=m+'<span style="font-size:10px;font-weight:500;opacity:.7;">ד׳</span> ';
      label+=s+'<span style="font-size:10px;font-weight:500;opacity:.7;">ש׳</span>';
      var color=secs>=120?'#22c55e':secs>=30?'#f59e0b':'var(--text3)';
      var bg=secs>=120?'rgba(34,197,94,.08)':secs>=30?'rgba(245,158,11,.08)':'transparent';
      return '<span style="font-size:15px;font-weight:800;color:'+color+';font-variant-numeric:tabular-nums;background:'+bg+';padding:2px 7px;border-radius:6px;display:inline-block;line-height:1.3;">'+label+'</span>';
    };
    var agentCell = function(row){
      if(row.agent_name) return '<div style="font-weight:600;font-size:13px;color:var(--text);">'+E(row.agent_name)+'</div>';
      if(row.agent_line) return '<span style="color:var(--text2);">'+E(row.agent_line)+'</span>';
      return '<span style="color:var(--text3);font-size:11px;">—</span>';
    };
    var recCell = function(row){
      if(!row.uniqueid) return '<span style="font-size:11px;color:var(--text3);">—</span>';
      var href='https://app.mvoice.co.il/#/calls/cdrs/edit/?callid='+encodeURIComponent(row.uniqueid)+'&customer=8113&cost_customer=scustomer&include_tax=1&archive=0&sort=start&descending=0&detail=leg';
      return '<a href="'+href+'" target="_blank" class="pbx-recbtn"><i class="bi bi-play-circle-fill"></i> הקלטה</a>';
    };

    var h='<table style="width:100%;border-collapse:collapse;font-size:13px;">';
    h+='<tr>';
    h+='<th style="'+TH+'">זמן &amp; משך</th>';
    h+='<th style="'+TH+'">כיוון</th>';
    h+='<th style="'+TH+'">נציג</th>';
    h+='<th style="'+TH+'">סטטוס</th>';
    if(_pbxCanRec)h+='<th style="'+TH+'">הקלטה</th>';
    h+='</tr>';
    rows.forEach(function(row){
      var hov='onmouseenter="this.querySelectorAll(\'td\').forEach(function(t){t.style.background=\'var(--bg3)\'})" onmouseleave="this.querySelectorAll(\'td\').forEach(function(t){t.style.background=\'\';})"';
      h+='<tr '+hov+'>';
      h+='<td style="'+TD+'color:var(--text2);"><div style="font-size:13px;margin-bottom:4px;">'+E(row.call_time)+'</div>'+fmtDuration(row.duration_sec)+'</td>';
      h+='<td style="'+TD+'">'+dirLabel(row.direction)+'</td>';
      h+='<td style="'+TD+'">'+agentCell(row)+'</td>';
      h+='<td style="'+TD+'">'+statLabel(row.status)+'</td>';
      if(_pbxCanRec)h+='<td style="'+TD+'" id="pbxrec-'+E(row.uniqueid)+'">'+recCell(row)+'</td>';
      h+='</tr>';
    });
    h+='</table>';
    var res = document.getElementById('pbx-res');
    res.innerHTML='<div style="padding:0 0 8px;">'+h+'</div>';
    if(data.caller_name)res.insertAdjacentHTML('afterbegin','<div style="padding:8px 14px 4px;font-size:12px;color:#10b981;font-weight:600;"><i class="bi bi-person-fill"></i> '+E(data.caller_name)+'</div>');
    if(data.critical_note)res.insertAdjacentHTML('afterbegin','<div style="padding:6px 14px;font-size:12px;color:#ef4444;font-weight:600;background:rgba(239,68,68,.07);border-bottom:1px solid rgba(239,68,68,.2);"><i class="bi bi-exclamation-triangle-fill"></i> '+E(data.critical_note)+'</div>');
    document.getElementById('pbx-cnt').textContent=rows.length+' שיחות';
  })
  .catch(function(err){
    pbxSetLoading(false);
    pbxSetEmpty('שגיאת תקשורת — '+(err.message||''));
    document.getElementById('pbx-cnt').textContent='שגיאה';
  });
}

/* ══ 5. טעינת הקלטה lazy ══ */
window.pbxLoadRec = function(btn, uniqueid){
  var td = btn.closest('td');
  if(!td) return;
  td.innerHTML='<span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--text3);"><div class="pbx-spin" style="width:13px;height:13px;"></div> טוען...</span>';
  // בדיקה אם קיימת הקלטה ישירה ב-mvoice ע"י uniqueid
  fetch('/api/crm/calls/recording?uniqueid='+encodeURIComponent(uniqueid),{credentials:'include'})
  .then(function(r){return r.json();})
  .then(function(d){
    if(d.ok && d.url){
      td.innerHTML='<audio src="'+d.url+'" controls style="height:28px;max-width:220px;border-radius:6px;outline:none;display:block;"></audio>';
    } else {
      td.innerHTML='<span style="font-size:11px;color:var(--text3);">אין הקלטה</span>';
    }
  })
  .catch(function(){
    td.innerHTML='<span style="font-size:11px;color:var(--danger);">שגיאה</span>';
  });
};

/* ══ 5b. האזנה להקלטה (ישן) ══ */
window.getCallrecord = function(uniqueid, elSel){
  var el = typeof elSel==='string' ? document.querySelector(elSel) : elSel;
  if(!el) return;
  if(!_pbxCanRec){
    el.innerHTML='<span class="pbx-norec"><i class="bi bi-lock-fill"></i> אין הרשאת האזנה</span>';
    return;
  }
  el.innerHTML='<span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--text3);"><div class="pbx-spin" style="width:13px;height:13px;"></div> טוען...</span>';
  fetch('/API/getRecordCall.api.php',{
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'callrecordid='+encodeURIComponent(uniqueid)
  })
  .then(function(r){return r.text();})
  .then(function(html){
    el.innerHTML = html||'<span class="pbx-norec">אין הקלטה</span>';
    var audio = el.querySelector('audio');
    if(audio){audio.style.cssText='width:100%;max-width:320px;height:32px;border-radius:6px;outline:none;display:block;margin-top:4px;';audio.controls=true;}
    el.querySelectorAll('a').forEach(function(a){a.style.color='#2196f3';});
  })
  .catch(function(){el.innerHTML='<span style="font-size:11px;color:var(--danger);"><i class="bi bi-x-circle"></i> שגיאה</span>';});
};

/* ══ 6. עיצוב תוצאות API ישן → V2 dark ══ */
function pbxStyleResults(){
  var r=document.getElementById('pbx-res'); if(!r) return;
  r.querySelectorAll('table').forEach(function(t){t.style.cssText='width:100%;border-collapse:collapse;font-size:13px;';});
  r.querySelectorAll('th').forEach(function(th){th.style.cssText='background:var(--bg3);color:var(--text3);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:8px 12px;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:1;text-align:right;';});
  r.querySelectorAll('td').forEach(function(td){td.style.cssText='padding:9px 12px;border-bottom:1px solid var(--border);color:var(--text2);vertical-align:middle;';});
  r.querySelectorAll('tr').forEach(function(tr){
    tr.addEventListener('mouseenter',function(){tr.querySelectorAll('td').forEach(function(td){td.style.background='var(--bg3)';});});
    tr.addEventListener('mouseleave',function(){tr.querySelectorAll('td').forEach(function(td){td.style.background='';});});
  });
  r.querySelectorAll('[onclick*="getCallrecord"]').forEach(function(el){
    el.className=_pbxCanRec?'pbx-recbtn':'pbx-recbtn no-access';
    el.innerHTML=_pbxCanRec?'<i class="bi bi-play-circle-fill"></i> האזן':'<i class="bi bi-lock-fill"></i> נעול';
  });
  r.querySelectorAll('td').forEach(function(td){
    var t=(td.textContent||'').trim().toUpperCase();
    if(t==='ANSWERED'||t==='ענה') td.innerHTML='<span class="pbx-stat pbx-stat-ok">✓ ענה</span>';
    else if(t==='NO ANSWER'||t==='לא ענה'||t==='NOANSWER') td.innerHTML='<span class="pbx-stat pbx-stat-miss">✗ לא ענה</span>';
    else if(t==='BUSY'||t==='תפוס') td.innerHTML='<span class="pbx-stat pbx-stat-busy">תפוס</span>';
  });
  r.querySelectorAll('a').forEach(function(a){a.style.color='#2196f3';});
}

function pbxSetEmpty(msg){document.getElementById('pbx-res').innerHTML='<div class="pbx-empty"><i class="bi bi-telephone-minus"></i><p>'+msg+'</p></div>';}
function pbxSetLoading(on){
  var btn=document.getElementById('pbx-gobtn');
  if(on){btn.disabled=true;btn.innerHTML='<div class="pbx-spin" style="width:15px;height:15px;border-width:2px;flex-shrink:0;"></div> מחפש...';document.getElementById('pbx-res').innerHTML='<div class="pbx-empty"><div class="pbx-spin" style="width:38px;height:38px;"></div><p>מחפש שיחות...</p></div>';}
  else{btn.disabled=false;btn.innerHTML='<i class="bi bi-search"></i> חיפוש';}
}

/* ══ 7. קיצורי מקלדת ══ */
document.getElementById('pbx-phone-input').addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();pbxSearch();}});
document.addEventListener('keydown',function(e){
  if((e.ctrlKey||e.metaKey)&&e.shiftKey&&e.code==='KeyP'){e.preventDefault();document.getElementById('pbx-modal').classList.contains('pbxshow')?closePbxModal():openPbxModal();}
  if(e.key==='Escape'&&document.getElementById('pbx-modal').classList.contains('pbxshow'))closePbxModal();
});
</script>