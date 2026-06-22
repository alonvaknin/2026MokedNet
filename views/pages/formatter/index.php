<?php
use Core\View;
/** @var array $categorised */
$base = rtrim(CFG['app']['url'],'/');
$csrf = $_SESSION['csrf_token'] ?? '';
$canEdit = \Core\Auth::can('canFormatter');
$totalActive = array_sum(array_map(fn($g) => count(array_filter($g, fn($t) => $t['is_active'])), $categorised));
$totalAll    = array_sum(array_map('count', $categorised));
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
  <div class="page-title" style="margin-bottom:0;">ניהול תבניות פורמטר</div>
  <?php if ($canEdit): ?>
  <a href="<?= $base ?>/formatter/editor" class="btn btn-primary">
    <i class="bi bi-plus-lg"></i> תבנית חדשה
  </a>
  <?php endif; ?>
</div>

<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
  <div class="card" style="padding:14px 20px;flex:1;min-width:120px;">
    <div style="font-size:24px;font-weight:700;color:var(--accent);"><?= $totalActive ?></div>
    <div style="font-size:12px;color:var(--text3);">תבניות פעילות</div>
  </div>
  <div class="card" style="padding:14px 20px;flex:1;min-width:120px;">
    <div style="font-size:24px;font-weight:700;color:var(--text2);"><?= $totalAll ?></div>
    <div style="font-size:12px;color:var(--text3);">סה"כ תבניות</div>
  </div>
  <div class="card" style="padding:14px 20px;flex:1;min-width:120px;">
    <div style="font-size:24px;font-weight:700;color:#8b5cf6;"><?= count($categorised) ?></div>
    <div style="font-size:12px;color:var(--text3);">קטגוריות</div>
  </div>
</div>

<?php foreach ($categorised as $cat => $templates): ?>
<div class="card" style="padding:0;overflow:hidden;margin-bottom:16px;">
  <div style="padding:11px 18px;background:var(--bg3);border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;">
    <i class="bi bi-folder-fill" style="color:#f59e0b;font-size:16px;"></i>
    <span style="font-weight:700;font-size:15px;"><?= View::e($cat) ?></span>
    <span class="badge badge-info"><?= count($templates) ?></span>
  </div>
  <div>
    <?php foreach ($templates as $i => $t): ?>
    <div style="display:flex;align-items:center;gap:12px;padding:11px 18px;<?= $i<count($templates)-1?'border-bottom:1px solid var(--border)':'' ?>;<?= !$t['is_active']?'opacity:.5':'' ?>">
      <div style="flex:1;min-width:0;">
        <div style="font-weight:600;color:var(--text);margin-bottom:2px;"><?= View::e($t['name']) ?></div>
        <?php if ($t['description']): ?>
          <div style="font-size:12px;color:var(--text3);"><?= View::e($t['description']) ?></div>
        <?php endif; ?>
        <div style="font-size:11px;color:var(--text3);margin-top:3px;display:flex;gap:10px;flex-wrap:wrap;">
          <?php if ($t['mail_subject']): ?>
            <span><i class="bi bi-envelope" style="font-size:10px;"></i> <?= View::e($t['mail_subject']) ?></span>
          <?php endif; ?>
          <?php if ($t['body_female']): ?>
            <span><i class="bi bi-gender-ambiguous" style="font-size:10px;"></i> עם הפרדת מין</span>
          <?php endif; ?>
        </div>
      </div>
      <?php if (!$t['is_active']): ?>
        <span class="badge badge-danger">מושבת</span>
      <?php else: ?>
        <span class="badge badge-success">פעיל</span>
      <?php endif; ?>
      <?php if ($canEdit): ?>
      <a href="<?= $base ?>/formatter/editor?id=<?= $t['id'] ?>" class="btn btn-ghost" style="padding:4px 10px;font-size:12px;">
        <i class="bi bi-pencil-fill"></i> ערוך
      </a>
      <button class="btn btn-ghost" style="padding:4px 10px;font-size:12px;" onclick="fmtToggle(<?= $t['id'] ?>)">
        <?= $t['is_active'] ? 'השבת' : 'הפעל' ?>
      </button>
      <button class="btn btn-ghost" style="padding:4px 10px;font-size:12px;color:var(--danger);" onclick='fmtDelete(<?= $t['id'] ?>, <?= json_encode($t['name'], JSON_UNESCAPED_UNICODE) ?>)'>
        <i class="bi bi-trash3"></i>
      </button>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>

<?php if (!$categorised): ?>
<div style="text-align:center;padding:60px;color:var(--text3);">
  <i class="bi bi-file-earmark-text" style="font-size:48px;display:block;margin-bottom:12px;opacity:.3;"></i>
  <div>אין תבניות עדיין</div>
  <?php if ($canEdit): ?>
    <a href="<?= $base ?>/formatter/editor" class="btn btn-primary" style="margin-top:16px;">
      <i class="bi bi-plus-lg"></i> צור תבנית ראשונה
    </a>
  <?php endif; ?>
</div>
<?php endif; ?>

<script>
const FMT_BASE = typeof BASE!=='undefined'?BASE:'<?= $base ?>';
const FMT_CSRF = '<?= View::e($csrf) ?>';

async function fmtToggle(id){
  const r = await fetch(FMT_BASE+'/formatter/toggle',{method:'POST',body:new URLSearchParams({_csrf:FMT_CSRF,id})});
  const d = await r.json();
  if(d.ok) location.reload();
}
async function fmtDelete(id, name){
  if(!confirm('למחוק את התבנית "'+name+'"?\nפעולה זו בלתי הפיכה.')) return;
  const r = await fetch(FMT_BASE+'/formatter/delete',{method:'POST',body:new URLSearchParams({_csrf:FMT_CSRF,id})});
  const d = await r.json();
  if(d.ok) location.reload();
  else alert(d.error||'שגיאה');
}
</script>
