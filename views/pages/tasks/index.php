<?php use Core\View;
$base  = rtrim(CFG['app']['url'], '/');
$csrf  = $_SESSION['csrf_token'] ?? '';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <div class="page-title" style="margin-bottom:0;">המשימות שלי</div>
  <button class="btn btn-primary" onclick="document.getElementById('new-task-modal').style.display='flex'">
    + משימה חדשה
  </button>
</div>

<?php if (empty($tasks)): ?>
  <div class="alert alert-info">אין משימות פתוחות 🎉</div>
<?php else: ?>
<div class="card">
  <table style="width:100%;border-collapse:collapse;font-size:14px;">
    <thead>
      <tr style="color:var(--text2);">
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">#</th>
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">כותרת</th>
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">SLA</th>
        <th style="text-align:right;padding:8px 12px;border-bottom:1px solid var(--border);font-weight:500;">נפתח</th>
        <th style="padding:8px 12px;border-bottom:1px solid var(--border);"></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($tasks as $t):
      $created = $t['created_at'] ? date('d/m/Y', strtotime($t['created_at'])) : '—';
      $slaTs   = $t['created_at'] && $t['sla_days']
                 ? strtotime($t['created_at'] . ' +' . (int)$t['sla_days'] . ' days')
                 : 0;
      $slaDate = $slaTs ? date('d/m/Y', $slaTs) : '—';
      $overdue = $slaTs && $slaTs < time();
    ?>
    <tr style="border-bottom:1px solid var(--border);">
      <td style="padding:10px 12px;color:var(--text3);"><?= (int)$t['id'] ?></td>
      <td style="padding:10px 12px;">
        <div style="font-weight:500;"><?= View::e($t['title'] ?? '') ?></div>
        <?php if (!empty($t['description'])): ?>
          <div style="font-size:12px;color:var(--text3);margin-top:2px;"><?= View::e(mb_substr($t['description'],0,70)) ?><?= mb_strlen($t['description'])>70?'…':'' ?></div>
        <?php endif; ?>
      </td>
      <td style="padding:10px 12px;">
        <?php if ($slaTs): ?>
          <span class="badge <?= $overdue?'badge-danger':'badge-success' ?>"><?= $slaDate ?></span>
        <?php else: ?>—<?php endif; ?>
      </td>
      <td style="padding:10px 12px;color:var(--text2);font-size:13px;"><?= $created ?></td>
      <td style="padding:10px 12px;">
        <form method="POST" action="<?= $base ?>/tasks/<?= (int)$t['id'] ?>/close"
              onsubmit="return confirm('לסגור משימה זו?')">
          <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
          <button type="submit" class="btn btn-ghost" style="padding:5px 10px;font-size:13px;">✓ סגור</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<div id="new-task-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;align-items:center;justify-content:center;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:28px;width:100%;max-width:480px;">
    <button onclick="document.getElementById('new-task-modal').style.display='none'"
            style="float:left;background:none;border:none;color:var(--text2);font-size:20px;cursor:pointer;">✕</button>
    <div style="font-size:17px;font-weight:600;margin-bottom:20px;">משימה חדשה</div>
    <form method="POST" action="<?= $base ?>/tasks/create">
      <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">כותרת *</label>
        <input type="text" name="title" required
               style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;">
      </div>
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">תיאור</label>
        <textarea name="description" rows="3"
                  style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;resize:vertical;"></textarea>
      </div>
      <div style="margin-bottom:20px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">SLA (ימים)</label>
        <input type="number" name="sla_days" value="3" min="1" max="30"
               style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">צור משימה</button>
    </form>
  </div>
</div>
