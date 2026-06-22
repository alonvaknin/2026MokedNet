<?php
/** @var array $store @var bool $canEdit */
use Core\View;
$base    = rtrim(CFG['app']['url'], '/');
$csrf    = $_SESSION['csrf_token'] ?? '';
$isBug   = ($store['type']??'') === 'סניף באג';
$isModan = ($store['type']??'') === 'נקודת מודן';
$accentColor = $isBug ? 'var(--accent)' : ($isModan ? '#8b5cf6' : 'var(--text2)');
$hasAlert = !empty($store['alert_note']);
$storeTypes = ['סניף באג','נקודת מודן','מחסן','אחר'];
?>

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
  <a href="<?= $base ?>/stores" class="btn btn-ghost" style="padding:6px 10px;"><i class="bi bi-arrow-right"></i></a>
  <div style="flex:1;min-width:0;">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <div class="page-title" style="margin-bottom:0;"><?= View::e($store['name']) ?></div>
      <?php if ($store['store_num']): ?>
        <span style="font-size:24px;font-weight:800;color:<?= $accentColor ?>;">#<?= View::e($store['store_num']) ?></span>
      <?php endif; ?>
      <?php if ($isBug): ?><span class="badge badge-info">סניף באג</span>
      <?php elseif ($isModan): ?><span class="badge badge-purple">נקודת מודן</span><?php endif; ?>
      <span class="badge <?= $store['is_active']?'badge-success':'badge-danger' ?>"><?= $store['is_active']?'פעיל':'לא פעיל' ?></span>
    </div>
  </div>
  <div style="display:flex;gap:8px;">
    <?php if (!empty($store['phone_main'])): ?>
      <a href="tel:<?= View::e($store['phone_main']) ?>" class="btn btn-primary">
        <i class="bi bi-telephone-fill"></i> <?= View::e($store['phone_main']) ?>
      </a>
    <?php endif; ?>
    <?php if ($canEdit): ?>
      <button class="btn btn-ghost" onclick="document.getElementById('edit-modal').style.display='flex'">
        <i class="bi bi-pencil-fill"></i> עריכה
      </button>
    <?php endif; ?>
  </div>
</div>

<?php if ($hasAlert): ?>
<div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-right:4px solid var(--warning);border-radius:var(--radius);padding:12px 16px;margin-bottom:18px;display:flex;align-items:flex-start;gap:12px;">
  <i class="bi bi-exclamation-triangle-fill" style="color:var(--warning);font-size:20px;flex-shrink:0;margin-top:1px;"></i>
  <div style="flex:1;">
    <div style="font-weight:600;color:var(--warning);margin-bottom:3px;">התראה פעילה</div>
    <div style="font-size:14px;"><?= View::e($store['alert_note']) ?></div>
    <?php if (!empty($store['alert_updated_at'])): ?>
    <div style="font-size:11px;color:var(--text3);margin-top:4px;">
      עודכן: <?= date('d/m/Y H:i', strtotime($store['alert_updated_at'])) ?>
      <?php if (!empty($store['alert_by_name'])): ?> · ע"י <?= View::e($store['alert_by_name']) ?><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:16px;">

  <!-- פרטים בסיסיים -->
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;gap:8px;color:<?= $accentColor ?>">
      <i class="bi bi-info-circle-fill"></i> פרטים בסיסיים
    </div>
    <div style="display:flex;flex-direction:column;gap:14px;">
      <?php if ($store['city']||$store['address']): ?>
      <div style="display:flex;gap:10px;align-items:flex-start;">
        <div style="width:34px;height:34px;border-radius:8px;background:rgba(16,185,129,.15);display:grid;place-items:center;flex-shrink:0;">
          <i class="bi bi-geo-alt-fill" style="color:#10b981;font-size:16px;"></i>
        </div>
        <div>
          <?php if ($store['city']): ?><div style="font-weight:600;"><?= View::e($store['city']) ?></div><?php endif; ?>
          <?php if ($store['address']): ?><div style="font-size:13px;color:var(--text2);margin-top:2px;"><?= View::e($store['address']) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($store['phone_main']||$store['phone_cell']): ?>
      <div style="display:flex;gap:10px;align-items:flex-start;">
        <div style="width:34px;height:34px;border-radius:8px;background:rgba(91,141,238,.15);display:grid;place-items:center;flex-shrink:0;">
          <i class="bi bi-telephone-fill" style="color:var(--accent);font-size:16px;"></i>
        </div>
        <div>
          <?php if ($store['phone_main']): ?>
            <a href="tel:<?= View::e($store['phone_main']) ?>" style="color:var(--accent);text-decoration:none;font-weight:600;"><?= View::e($store['phone_main']) ?></a>
            <span style="font-size:11px;color:var(--text3);margin-right:4px;">ראשי</span>
          <?php endif; ?>
          <?php if ($store['phone_cell']): ?>
            <div style="margin-top:3px;">
              <a href="tel:<?= View::e($store['phone_cell']) ?>" style="color:var(--accent);text-decoration:none;"><?= View::e($store['phone_cell']) ?></a>
              <span style="font-size:11px;color:var(--text3);margin-right:4px;">נייד</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($store['email']??''): ?>
      <div style="display:flex;gap:10px;align-items:flex-start;">
        <div style="width:34px;height:34px;border-radius:8px;background:rgba(16,185,129,.15);display:grid;place-items:center;flex-shrink:0;">
          <i class="bi bi-envelope-fill" style="color:#10b981;font-size:16px;"></i>
        </div>
        <div>
          <a href="mailto:<?= View::e($store['email']) ?>" style="color:var(--accent);text-decoration:none;font-weight:600;"><?= View::e($store['email']) ?></a>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($store['mvoice_queue']||$store['telephone_line_num']): ?>
      <div style="display:flex;gap:10px;align-items:flex-start;">
        <div style="width:34px;height:34px;border-radius:8px;background:rgba(139,92,246,.15);display:grid;place-items:center;flex-shrink:0;">
          <i class="bi bi-headset" style="color:#8b5cf6;font-size:16px;"></i>
        </div>
        <div>
          <?php if ($store['mvoice_queue']): ?><div style="font-weight:500;">שלוחה <?= View::e($store['mvoice_queue']) ?></div><?php endif; ?>
          <?php if ($store['telephone_line_num']): ?><div style="font-size:13px;color:var(--text2);">קו: <?= View::e($store['telephone_line_num']) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- איש קשר -->
  <?php if ($store['manager_name']||$store['manager_cell']): ?>
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;gap:8px;color:#f59e0b">
      <i class="bi bi-person-fill"></i> איש קשר
    </div>
    <div style="display:flex;align-items:center;gap:14px;">
      <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#f59e0b,#f97316);display:grid;place-items:center;font-size:20px;font-weight:700;color:#fff;flex-shrink:0;">
        <?= mb_substr($store['manager_name']??'?',0,1) ?>
      </div>
      <div>
        <?php if ($store['manager_name']): ?><div style="font-weight:600;font-size:15px;"><?= View::e($store['manager_name']) ?></div><?php endif; ?>
        <?php if ($store['manager_cell']): ?><a href="tel:<?= View::e($store['manager_cell']) ?>" style="color:var(--accent);text-decoration:none;font-size:13px;"><?= View::e($store['manager_cell']) ?></a><?php endif; ?>
        <div style="font-size:11px;color:var(--text3);margin-top:2px;">מנהל סניף</div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- מנהלי אזור -->
  <?php if (!empty($areaManagers)): ?>
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;gap:8px;color:var(--accent)">
      <i class="bi bi-person-badge-fill"></i> מנהלי אזור
    </div>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <?php foreach ($areaManagers as $am): ?>
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#7c6fcd);display:grid;place-items:center;font-size:15px;font-weight:700;color:#fff;flex-shrink:0;">
          <?= View::e(mb_substr($am['name'], 0, 1)) ?>
        </div>
        <div style="flex:1;">
          <div style="font-weight:600;font-size:14px;"><?= View::e($am['name']) ?></div>
          <div style="display:flex;gap:10px;margin-top:3px;flex-wrap:wrap;">
            <?php if ($am['phone']): ?>
              <a href="tel:<?= View::e($am['phone']) ?>" style="color:var(--accent);text-decoration:none;font-size:13px;">
                <i class="bi bi-telephone-fill"></i> <?= View::e($am['phone']) ?>
              </a>
            <?php endif; ?>
            <?php if ($am['email']): ?>
              <a href="mailto:<?= View::e($am['email']) ?>" style="color:var(--accent);text-decoration:none;font-size:13px;">
                <i class="bi bi-envelope-fill"></i> <?= View::e($am['email']) ?>
              </a>
            <?php endif; ?>
          </div>
          <div style="font-size:11px;color:var(--text3);margin-top:2px;">
            <?= $am['source_type'] === 'contact' ? 'איש קשר' : 'משתמש מערכת' ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- שעות פעילות -->
  <?php
  $wh = null;
  if (!empty($store['work_hours'])) {
    $wh = is_string($store['work_hours']) ? json_decode($store['work_hours'], true) : $store['work_hours'];
  }
  if ($wh): ?>
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;gap:8px;color:#06b6d4">
      <i class="bi bi-clock-fill"></i> שעות פעילות
    </div>
    <?php
    $days = ['א'=>'ראשון','ב'=>'שני','ג'=>'שלישי','ד'=>'רביעי','ה'=>'חמישי','ו'=>'שישי','ש'=>'שבת'];
    foreach ($wh as $dayKey => $hours): ?>
    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--border);font-size:13px;">
      <span style="color:var(--text2);"><?= View::e($days[$dayKey]??$dayKey) ?></span>
      <span style="font-weight:500;"><?= View::e($hours?:'סגור') ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- הערות -->
  <?php if ($store['note']): ?>
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;gap:8px;color:#f97316">
      <i class="bi bi-sticky-fill"></i> הערות
    </div>
    <div style="font-size:14px;line-height:1.7;color:var(--text2);"><?= nl2br(View::e($store['note'])) ?></div>
  </div>
  <?php endif; ?>

</div>

<?php if ($canEdit): ?>
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:center;justify-content:center;padding:20px;">
  <?php include __DIR__ . '/_store_form.php'; ?>
</div>
<script>
const _SD = <?= json_encode($store, JSON_UNESCAPED_UNICODE) ?>;
window.addEventListener('DOMContentLoaded', () => {
  if(typeof openStoreForm==='function') openStoreForm(_SD);
  document.getElementById('edit-modal').style.display='none';
});
</script>
<?php endif; ?>
