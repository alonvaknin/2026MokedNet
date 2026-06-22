<?php
/** @var array $user @var bool $isOwnProfile */
use Core\View;
use Core\Auth;
$base    = rtrim(CFG['app']['url'], '/');
$csrf    = $_SESSION['csrf_token'] ?? '';
$canEdit = Auth::can('canAddUsers');
$fullName = trim(($user['first_name']??'') . ' ' . ($user['last_name']??''));
$initials = mb_substr($user['first_name']??'?',0,1) . mb_substr($user['last_name']??'',0,1);
?>

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
  <a href="<?= $base ?>/users" class="btn btn-ghost" style="padding:6px 10px;"><i class="bi bi-arrow-right"></i></a>
  <div class="page-title" style="margin-bottom:0;"><?= View::e($fullName) ?></div>
  <?php if (!empty($isOwnProfile)): ?>
    <span class="badge badge-info">הפרופיל שלי</span>
  <?php endif; ?>
  <span class="badge <?= ($user['is_active']??0)?'badge-success':'badge-danger' ?>">
    <?= ($user['is_active']??0)?'פעיל':'לא פעיל' ?>
  </span>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">

  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;gap:8px;">
      <i class="bi bi-person-fill" style="color:var(--accent);"></i> פרטי משתמש
    </div>
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
      <div style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#c084fc);display:grid;place-items:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0;">
        <?= View::e($initials) ?>
      </div>
      <div>
        <div style="font-size:16px;font-weight:700;"><?= View::e($fullName) ?></div>
        <div style="font-size:13px;color:var(--text3);"><?= View::e($user['email']??'') ?></div>
      </div>
    </div>
    <?php
    $fields = [
      'מחלקה'          => $user['dept_name']  ?? '—',
      'קבוצת הרשאה'   => $user['group_name'] ?? '—',
      'טלפון'          => $user['phone']      ?? '—',
      'כניסה אחרונה'  => !empty($user['last_login']) ? date('d/m/Y H:i', strtotime($user['last_login'])) : '—',
    ];
    ?>
    <table style="width:100%;font-size:13px;border-collapse:collapse;">
      <?php foreach ($fields as $label => $val): ?>
      <tr style="border-bottom:1px solid var(--border);">
        <td style="padding:8px 0;color:var(--text2);width:130px;"><?= View::e($label) ?></td>
        <td style="padding:8px 0;font-weight:500;"><?= View::e($val) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <?php if (!empty($isOwnProfile)): ?>
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;gap:8px;">
      <i class="bi bi-gear-fill" style="color:#f59e0b;"></i> פעולות מהירות
    </div>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <a href="<?= $base ?>/preferences" class="btn btn-ghost" style="justify-content:flex-start;gap:10px;">
        <i class="bi bi-palette-fill" style="color:var(--accent);"></i> העדפות תצוגה
      </a>
      <a href="<?= $base ?>/logout" class="btn btn-ghost" style="justify-content:flex-start;gap:10px;color:var(--danger);">
        <i class="bi bi-box-arrow-left"></i> התנתקות
      </a>
    </div>
  </div>
  <?php endif; ?>

</div>
