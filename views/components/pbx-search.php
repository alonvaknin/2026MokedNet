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
  var range  = document.getElementById('pbx-range').value;
  var source = document.getElementById('pbx-source').value;
  if(!phone || phone.replace(/\D/g,'').length < 9){
    var inp = document.getElementById('pbx-phone-input');
    inp.style.borderColor='var(--danger)'; inp.focus();
    setTimeout(function(){inp.style.borderColor='';}, 900);
    pbxSetEmpty('נא להזין מספר טלפון תקין (לפחות 9 ספרות)');
    return;
  }
  pbxSetLoading(true);
  document.getElementById('pbx-cnt').textContent='מחפש שיחות...';
  fetch('/API/getPhoneCalls.api.php',{
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
    body:'phoneQ='+encodeURIComponent(phone)+'&time-range='+encodeURIComponent(range)+'&source-select='+encodeURIComponent(source)+'&fromSearch=YES'
  })
  .then(function(r){if(!r.ok)throw new Error('HTTP '+r.status);return r.json();})
  .then(function(data){
    pbxSetLoading(false);
    var html = (data&&data.allcalls)?String(data.allcalls).trim():'';
    if(!html){
      pbxSetEmpty('לא נמצאו שיחות עבור '+phone);
      document.getElementById('pbx-cnt').textContent='אין תוצאות';
      return;
    }
    var res = document.getElementById('pbx-res');
    res.innerHTML='<div style="padding:8px 14px;">'+html+'</div>';
    pbxStyleResults();
    var rows = res.querySelectorAll('tr:not(:first-of-type)');
    document.getElementById('pbx-cnt').textContent = rows.length ? rows.length+' שיחות' : 'נמצאו תוצאות';
  })
  .catch(function(err){
    pbxSetLoading(false);
    pbxSetEmpty('שגיאת תקשורת — '+(err.message||''));
    document.getElementById('pbx-cnt').textContent='שגיאה';
  });
}

/* ══ 5. האזנה להקלטה ══ */
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