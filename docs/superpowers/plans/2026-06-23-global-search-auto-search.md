# Global Search Auto-search + UX Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add auto-search on input (contacts + stores), mixed results with phone/email, and keyboard navigation to the Global Search modal in `views/layouts/main.php`.

**Architecture:** All changes are pure JS inside the existing Global Search `<script>` block in `views/layouts/main.php`. No new files, no backend changes. We extend three existing functions (`gsRenderContacts`, input event handler, keydown handler) and add two new functions (`gsAutoSearch`, `gsNavResult`).

**Tech Stack:** Vanilla JS, PHP (template), existing `/api/contacts` and `/api/stores` endpoints, local `window.ALL_BUG` / `window.ALL_MODAN` store pool.

## Global Constraints

- No new files — all changes in `views/layouts/main.php`
- No debounce — auto-search fires immediately on input
- Do not break existing Enter / scope-pill behavior
- Modal stays open after clicking a result
- RTL layout (Hebrew UI, `dir="rtl"`)

---

### Task 1: Auto-search contacts + stores on input

**Files:**
- Modify: `views/layouts/main.php` — `_gsInput` input event listener + new `gsAutoSearch()` function

**Interfaces:**
- Consumes: existing `_gsInput`, `_gsScope`, `gsRenderContacts()`, `gsRenderStores()`, `BASE`
- Produces: `gsAutoSearch()` — callable by later tasks for keyboard navigation trigger

- [ ] **Step 1: Locate the input listener**

Find this line in `main.php` (~line 1215):
```js
_gsInput.addEventListener('input',e=>{si.value=e.target.value;gsEmpty();});
```

- [ ] **Step 2: Replace the input listener**

Replace the line above with:
```js
_gsInput.addEventListener('input',e=>{
  si.value=e.target.value;
  _gsHighlightIdx=-1;
  const q=e.target.value.trim();
  if(q.length>2) gsAutoSearch(q);
  else gsEmpty();
});
```

- [ ] **Step 3: Add `gsAutoSearch()` function**

Insert this function directly after `gsRenderProducts()` (before `async function gsSearch()`):
```js
async function gsAutoSearch(q){
  const res=document.getElementById('gs-results');
  const E=s=>String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  res.innerHTML='<div class="gs-empty"><i class="bi bi-hourglass-split"></i>מחפש...</div>';

  // Fetch contacts and stores in parallel
  const [contacts, stores] = await Promise.all([
    fetch(BASE+'/api/contacts?q='+encodeURIComponent(q)).then(r=>r.json()).catch(()=>[]),
    (()=>{
      const pool=[...(window.ALL_BUG||[]),...(window.ALL_MODAN||[])];
      if(pool.length){
        const ql=q.toLowerCase();
        return Promise.resolve(pool.filter(s=>
          (s.name||'').toLowerCase().includes(ql)||
          (s.store_num||'').includes(ql)||
          (s.phone_main||'').includes(ql)||
          (s.city||'').toLowerCase().includes(ql)
        ).slice(0,10));
      }
      return fetch(BASE+'/api/stores?q='+encodeURIComponent(q)).then(r=>r.json()).catch(()=>[]);
    })()
  ]);

  const cArr=Array.isArray(contacts)?contacts:[];
  const sArr=Array.isArray(stores)?stores:[];

  if(!cArr.length&&!sArr.length){
    res.innerHTML='<div class="gs-empty"><i class="bi bi-search"></i>לא נמצאו תוצאות עבור "'+E(q)+'"</div>';
    return;
  }

  let h='<div>';

  // Contacts section
  if(cArr.length){
    h+='<div style="padding:6px 14px 4px;font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid var(--border);background:var(--bg3);"><i class="bi bi-people-fill" style="margin-left:4px;"></i>אנשי קשר</div>';
    _gsContacts=cArr;
    const typeCol={'נותן שירות':'#10b981','פנים ארגוני':'#5b8dee','ספק':'#f59e0b','תמיכה טכנית':'#06b6d4','איש קשר':'#8b5cf6','אחר':'#7c829c'};
    const avatarColors=['#5b8dee','#8b5cf6','#10b981','#f59e0b','#ec4899','#06b6d4','#f97316'];
    cArr.forEach((c,i)=>{
      const fullName=((c.first_name||'')+' '+(c.last_name||'')).trim();
      const col=typeCol[c.contact_type||'איש קשר']||'#8b5cf6';
      const hash=fullName.split('').reduce((a,ch)=>Math.imul(31,a)+ch.charCodeAt(0)|0,0);
      const acolor=avatarColors[Math.abs(hash)%avatarColors.length];
      h+='<div class="gs-row" tabindex="-1" onclick="gsOpenContactView(_gsContacts['+i+'])">';
      h+='<div style="width:36px;height:36px;border-radius:50%;background:'+acolor+';display:grid;place-items:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;">'+E((c.first_name||'?').charAt(0))+'</div>';
      h+='<div style="flex:1;min-width:0;">';
      h+='<div style="font-weight:600;">'+gsHl(fullName,q)+'</div>';
      if(c.phone)h+='<div style="font-size:12px;color:var(--text3);"><i class="bi bi-telephone-fill" style="font-size:10px;margin-left:3px;"></i>'+gsHl(c.phone,q)+'</div>';
      if(c.email&&c.email.trim())h+='<div style="font-size:12px;color:var(--text3);"><i class="bi bi-envelope-fill" style="font-size:10px;margin-left:3px;"></i>'+gsHl(c.email,q)+'</div>';
      h+='</div>';
      h+='<span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;color:'+col+';border:1px solid '+col+'44;background:'+col+'15;white-space:nowrap;">'+E(c.contact_type||'איש קשר')+'</span>';
      h+='</div>';
    });
  }

  // Stores section
  if(sArr.length){
    h+='<div style="padding:6px 14px 4px;font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid var(--border);background:var(--bg3);border-top:1px solid var(--border);"><i class="bi bi-shop" style="margin-left:4px;"></i>חנויות</div>';
    sArr.forEach(s=>{
      const col=(s.type==='סניף באג')?'var(--accent)':'#8b5cf6';
      const num=E(s.id||'');
      const onclick=typeof openStoreView!=='undefined'
        ?'openStoreView(\''+num+'\')'
        :'window.location.href=BASE+\'/stores/\'+encodeURIComponent(\''+num+'\')';
      h+='<div class="gs-row" tabindex="-1" onclick="'+onclick+'">';
      h+='<span style="font-size:19px;font-weight:800;color:'+col+';min-width:50px;flex-shrink:0;">'+gsHl(s.store_num,q)+'</span>';
      h+='<div style="flex:1;min-width:0;"><div style="font-weight:600;font-size:14px;">'+gsHl(s.name,q)+'</div>';
      if(s.city)h+='<div style="font-size:11px;color:var(--text3);"><i class="bi bi-geo-alt-fill" style="font-size:10px;"></i> '+gsHl(s.city,q)+'</div>';
      h+='</div>';
      if(s.phone_main)h+='<a href="tel:'+E(s.phone_main)+'" onclick="event.stopPropagation()" style="font-size:12px;color:var(--accent);text-decoration:none;white-space:nowrap;"><i class="bi bi-telephone-fill"></i> '+gsHl(s.phone_main,q)+'</a>';
      if(s.alert_note)h+='<i class="bi bi-exclamation-triangle-fill" style="color:var(--warning);font-size:13px;flex-shrink:0;" title="'+E(s.alert_note)+'"></i>';
      h+='</div>';
    });
  }

  h+='</div>';
  res.innerHTML=h;
}
```

- [ ] **Step 4: Manual test — open Ctrl+K, type 3+ chars, verify auto-results appear for contacts and stores without pressing Enter**

- [ ] **Step 5: Commit**

```bash
git add views/layouts/main.php
git commit -m "feat: auto-search contacts+stores on input in global search"
```

---

### Task 2: Keyboard navigation in results

**Files:**
- Modify: `views/layouts/main.php` — add `_gsHighlightIdx` variable, `gsNavResult()` function, update keydown handler

**Interfaces:**
- Consumes: `gsAutoSearch()` (Task 1), `.gs-row` elements in `#gs-results`, existing `_gsInput` keydown handler
- Produces: `gsNavResult(dir)` — moves highlight; Enter on highlighted row activates it

- [ ] **Step 1: Add highlight variable**

Find the line that declares `let _gsScope` (~line 1040). Add directly below it:
```js
let _gsHighlightIdx=-1;
```

- [ ] **Step 2: Add `gsNavResult()` function**

Insert directly after the `gsAutoSearch()` function added in Task 1:
```js
function gsNavResult(dir){
  const rows=[...document.querySelectorAll('#gs-results .gs-row')];
  if(!rows.length)return;
  // remove current highlight
  if(_gsHighlightIdx>=0&&rows[_gsHighlightIdx])
    rows[_gsHighlightIdx].style.background='';
  _gsHighlightIdx=Math.max(0,Math.min(rows.length-1,_gsHighlightIdx+dir));
  const target=rows[_gsHighlightIdx];
  if(target){
    target.style.background='var(--bg3)';
    target.scrollIntoView({block:'nearest'});
  }
}
```

- [ ] **Step 3: Update keydown handler to use navigation**

Find the existing keydown handler on `_gsInput` (~line 1218):
```js
_gsInput.addEventListener('keydown',e=>{
  if(e.key==='Enter'){e.preventDefault();gsSearch();}
  // Arrow keys navigate scopes (RTL: Right=prev, Left=next)
  else if(e.key==='ArrowRight'){e.preventDefault();gsNavScope(-1);}
  else if(e.key==='ArrowLeft') {e.preventDefault();gsNavScope(1);}
});
```

Replace it with:
```js
_gsInput.addEventListener('keydown',e=>{
  if(e.key==='ArrowDown'){
    e.preventDefault();
    gsNavResult(1);
  }else if(e.key==='ArrowUp'){
    e.preventDefault();
    gsNavResult(-1);
  }else if(e.key==='Enter'){
    e.preventDefault();
    if(_gsHighlightIdx>=0){
      const rows=[...document.querySelectorAll('#gs-results .gs-row')];
      if(rows[_gsHighlightIdx]){rows[_gsHighlightIdx].click();return;}
    }
    gsSearch();
  }
  // Arrow keys navigate scopes (RTL: Right=prev, Left=next)
  else if(e.key==='ArrowRight'){e.preventDefault();gsNavScope(-1);}
  else if(e.key==='ArrowLeft') {e.preventDefault();gsNavScope(1);}
});
```

- [ ] **Step 4: Manual test — open Ctrl+K, type 3+ chars, press ArrowDown to move through results, Enter to open highlighted result**

- [ ] **Step 5: Commit**

```bash
git add views/layouts/main.php
git commit -m "feat: keyboard navigation in global search results"
```

---

### Task 3: Keep modal open after result activation + update gsRenderContacts with phone/email

**Files:**
- Modify: `views/layouts/main.php` — `gsRenderContacts()` and contact rows in auto-search (already done in Task 1), fix `gsClose()` calls

**Interfaces:**
- Consumes: `gsOpenContactView()`, `openStoreView()`, `_gsModal`
- Produces: results open without closing modal; `gsRenderContacts()` shows phone + email

- [ ] **Step 1: Update `gsRenderContacts()` to show phone and email**

Find `gsRenderContacts()` (~line 1099). Find the row that adds phone:
```js
if(c.phone)h+='<div style="font-size:12px;color:var(--text3);"><i class="bi bi-telephone-fill" style="font-size:10px;"></i> '+gsHl(c.phone,q)+'</div>';
```

Add email line directly after it:
```js
if(c.email&&c.email.trim())h+='<div style="font-size:12px;color:var(--text3);"><i class="bi bi-envelope-fill" style="font-size:10px;margin-left:3px;"></i>'+gsHl(c.email,q)+'</div>';
```

- [ ] **Step 2: Remove `gsClose()` from contact row onclick in `gsRenderContacts()`**

Find in `gsRenderContacts()`:
```js
h+='<div class="gs-row" onclick="gsClose();gsOpenContactView(_gsContacts['+i+'])">';
```

Replace with:
```js
h+='<div class="gs-row" tabindex="-1" onclick="gsOpenContactView(_gsContacts['+i+'])">';
```

- [ ] **Step 3: Remove `gsClose()` from store rows in `gsRenderStores()`**

Find in `gsRenderStores()` (~line 1083):
```js
const onclick=typeof openStoreView!=='undefined'
  ?'gsClose();openStoreView(\''+num+'\')'
  :'gsClose();window.location.href=BASE+\'/stores/\'+encodeURIComponent(\''+num+'\')';
```

Replace with:
```js
const onclick=typeof openStoreView!=='undefined'
  ?'openStoreView(\''+num+'\')'
  :'window.location.href=BASE+\'/stores/\'+encodeURIComponent(\''+num+'\')';
```

- [ ] **Step 4: Manual test — open Ctrl+K, search, click a contact → contact view opens, Global Search modal remains visible behind it. Click a store → store view opens, modal remains open.**

- [ ] **Step 5: Commit**

```bash
git add views/layouts/main.php
git commit -m "feat: keep global search modal open after result click, show phone+email in contacts"
```
