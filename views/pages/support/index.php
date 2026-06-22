<?php use Core\View;
$base = rtrim(CFG['app']['url'], '/');
?>
<div class="page-title">בסיס ידע תמיכה</div>

<div style="margin-bottom:16px;">
  <input type="text" id="prod-search" placeholder="חיפוש מוצר לפי שם / ברקוד..."
         style="width:100%;max-width:400px;background:var(--bg2);border:1px solid var(--border);
                border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;
                font-family:inherit;outline:none;"
         oninput="searchProduct(this.value)">
  <div id="prod-results" style="margin-top:8px;"></div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
<?php foreach ($categories as $cat): ?>
  <a href="<?= $base ?>/support/cat/<?= (int)$cat['id'] ?>"
     style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);
            padding:18px 16px;text-decoration:none;transition:border-color .15s;display:block;"
     onmouseover="this.style.borderColor='var(--border2)'" onmouseout="this.style.borderColor='var(--border)'">
    <div style="width:12px;height:12px;border-radius:50%;margin-bottom:10px;
                background:<?= View::e($cat['color'] ?: '#4f7fff') ?>;"></div>
    <div style="font-size:15px;font-weight:500;color:var(--text);"><?= View::e($cat['name']) ?></div>
  </a>
<?php endforeach; ?>
</div>

<!-- Issue Panel -->
<div id="issue-panel" style="display:none;margin-top:20px;"></div>

<script>
let prodTimer = null;
async function searchProduct(q) {
  clearTimeout(prodTimer);
  const el = document.getElementById('prod-results');
  if (q.length < 2) { el.innerHTML = ''; return; }
  prodTimer = setTimeout(async () => {
    const res  = await fetch(`<?= $base ?>/api/support/search?q=${encodeURIComponent(q)}`);
    const data = await res.json();
    if (!data.length) { el.innerHTML = '<span style="color:var(--text3);font-size:13px;">לא נמצאו מוצרים</span>'; return; }
    el.innerHTML = `<div class="card" style="padding:12px;">` +
      data.map(p => `
        <div style="padding:8px 0;border-bottom:1px solid var(--border);display:flex;gap:10px;cursor:pointer;font-size:14px;"
             onclick="loadIssues(null,'${p.barcode}','${p.category}')">
          <span style="font-weight:500;flex:1;">${p.model}</span>
          <span style="color:var(--text3);font-size:12px;">${p.category || ''}</span>
          <span style="color:var(--text3);font-size:12px;direction:ltr;">${p.barcode || ''}</span>
        </div>`
      ).join('') + `</div>`;
  }, 280);
}

async function loadIssues(catId, barcode, catName) {
  const panel = document.getElementById('issue-panel');
  panel.style.display = 'block';
  panel.innerHTML = '<div class="card"><div style="color:var(--text3);font-size:13px;">טוען...</div></div>';
  const body = new URLSearchParams({ cat_id: catId || '', barcode: barcode || '' });
  const res  = await fetch('<?= $base ?>/api/support/issues', { method: 'POST', body });
  const data = await res.json();
  if (!data.length) {
    panel.innerHTML = '<div class="alert alert-info">אין בעיות מוכרות עדיין עבור מוצר זה.</div>'; return;
  }
  panel.innerHTML = `<div class="card">
    <div class="card-header">בעיות ופתרונות — ${catName || barcode || ''}</div>
    ${data.map(i => `
      <details style="border-bottom:1px solid var(--border);padding:10px 0;" open>
        <summary style="cursor:pointer;font-weight:500;font-size:14px;">${i.title}</summary>
        <div style="margin-top:8px;font-size:13px;color:var(--text2);line-height:1.6;">${i.solution}</div>
      </details>`
    ).join('')}
  </div>`;
}
</script>
