<?php use Core\View;
$base = rtrim(CFG['app']['url'], '/');
?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
  <a href="<?= $base ?>/support" class="btn btn-ghost" style="padding:6px 10px;">← חזרה</a>
  <div class="page-title" style="margin-bottom:0;">מוצרים בקטגוריה</div>
</div>

<?php if (empty($products)): ?>
  <div class="alert alert-info">אין מוצרים בקטגוריה זו.</div>
<?php else: ?>
<div class="card">
  <table style="width:100%;border-collapse:collapse;font-size:14px;">
    <thead>
      <tr style="color:var(--text2);">
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">מודל</th>
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">יצרן</th>
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">ברקוד</th>
        <th style="padding:8px 12px;border-bottom:1px solid var(--border);"></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($products as $p): ?>
    <tr style="border-bottom:1px solid var(--border);">
      <td style="padding:10px 12px;font-weight:500;"><?= View::e($p['model']) ?></td>
      <td style="padding:10px 12px;color:var(--text2);"><?= View::e($p['manufacturer'] ?? '—') ?></td>
      <td style="padding:10px 12px;color:var(--text2);direction:ltr;text-align:right;"><?= View::e($p['barcode'] ?? '—') ?></td>
      <td style="padding:10px 12px;">
        <button class="btn btn-ghost" style="padding:5px 10px;font-size:13px;"
                onclick="loadIssues('<?= (int)$id ?>','<?= View::e($p['barcode'] ?? '') ?>','<?= View::e($p['model']) ?>')">
          🔍 בעיות
        </button>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<div id="issue-panel" style="margin-top:20px;display:none;"></div>

<script>
async function loadIssues(catId, barcode, name) {
  const panel = document.getElementById('issue-panel');
  panel.style.display = 'block';
  panel.innerHTML = '<div class="card" style="color:var(--text3);font-size:13px;">טוען...</div>';
  const body = new URLSearchParams({ cat_id: catId, barcode });
  const res  = await fetch('<?= $base ?>/api/support/issues', { method: 'POST', body });
  const data = await res.json();
  if (!data.length) {
    panel.innerHTML = '<div class="alert alert-info">אין בעיות מוכרות עדיין עבור מוצר זה.</div>';
    return;
  }
  panel.innerHTML = `<div class="card">
    <div class="card-header">בעיות ופתרונות — ${name}</div>
    ${data.map(i => `
      <details style="border-bottom:1px solid var(--border);padding:10px 0;" open>
        <summary style="cursor:pointer;font-weight:500;font-size:14px;">${i.title}</summary>
        <div style="margin-top:8px;font-size:13px;color:var(--text2);line-height:1.6;">${i.solution}</div>
      </details>`
    ).join('')}
  </div>`;
  panel.scrollIntoView({ behavior: 'smooth' });
}
</script>
