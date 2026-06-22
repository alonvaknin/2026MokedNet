<?php use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <div class="page-title" style="margin-bottom:0;">ניהול בעיות ופתרונות</div>
  <button class="btn btn-primary"
          onclick="document.getElementById('add-modal').style.display='flex'">+ הוסף</button>
</div>

<div class="card">
  <table style="width:100%;border-collapse:collapse;font-size:14px;">
    <thead>
      <tr style="color:var(--text2);">
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">בעיה</th>
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">קטגוריה</th>
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">ברקוד</th>
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">נוסף ע"י</th>
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">סטטוס</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($issues as $i): ?>
    <tr style="border-bottom:1px solid var(--border);">
      <td style="padding:10px 12px;">
        <div style="font-weight:500;"><?= View::e($i['title']) ?></div>
        <div style="font-size:12px;color:var(--text3);margin-top:3px;"><?= View::e(mb_substr(strip_tags($i['solution']),0,80)) ?>...</div>
      </td>
      <td style="padding:10px 12px;color:var(--text2);"><?= View::e($i['cat_name'] ?? '—') ?></td>
      <td style="padding:10px 12px;color:var(--text2);direction:ltr;font-size:12px;"><?= View::e($i['barcode'] ?? '—') ?></td>
      <td style="padding:10px 12px;color:var(--text2);font-size:13px;"><?= View::e($i['added_by'] ?? '—') ?></td>
      <td style="padding:10px 12px;">
        <span class="badge <?= $i['active'] ? 'badge-success' : 'badge-danger' ?>">
          <?= $i['active'] ? 'פעיל' : 'מושבת' ?>
        </span>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add Issue Modal -->
<div id="add-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;align-items:center;justify-content:center;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);
              padding:28px;width:100%;max-width:560px;position:relative;">
    <button onclick="document.getElementById('add-modal').style.display='none'"
            style="position:absolute;left:16px;top:16px;background:none;border:none;color:var(--text2);font-size:20px;cursor:pointer;">✕</button>
    <div style="font-size:17px;font-weight:600;margin-bottom:20px;">הוספת בעיה/פתרון</div>
    <form method="POST" action="<?= $base ?>/support/issues">
      <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">כותרת הבעיה *</label>
        <input type="text" name="title" required
               style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;">
      </div>
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">פתרון *</label>
        <textarea name="solution" rows="4" required
                  style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;resize:vertical;"></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
        <div>
          <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">קטגוריה</label>
          <select name="cat_id" style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;">
            <option value="">-- בחר --</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= View::e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">ברקוד מוצר</label>
          <input type="text" name="barcode"
                 style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;direction:ltr;">
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">שמור</button>
    </form>
  </div>
</div>
