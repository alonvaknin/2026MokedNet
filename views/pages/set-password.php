<?php
// views/pages/set-password.php
use Core\View;
$base    = rtrim(CFG['app']['url'], '/');
$appName = CFG['app']['name'];
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>קביעת סיסמא — <?= View::e($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg: #0f1117; --bg2: #181b23; --bg3: #1e2130;
  --border: rgba(255,255,255,.08);
  --text: #e8eaf0; --text2: #8b8fa8; --text3: #5a5e78;
  --accent: #4f7fff; --accent2: #3d6be8;
  --danger: #e05555; --success: #34c77b; --radius: 12px;
}
body { font-family: 'Heebo', sans-serif; background: var(--bg); color: var(--text);
       min-height: 100vh; display: grid; place-items: center; padding: 20px; }
body::before { content: ''; position: fixed; inset: 0;
  background: radial-gradient(circle at 20% 80%, rgba(79,127,255,.06) 0%, transparent 50%),
              radial-gradient(circle at 80% 20%, rgba(79,127,255,.04) 0%, transparent 40%);
  pointer-events: none; }
.card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius);
        padding: 40px 36px; width: 100%; max-width: 400px; position: relative; }
.brand { text-align: center; margin-bottom: 32px; }
.brand-icon { width: 52px; height: 52px; background: var(--accent); border-radius: 14px;
              display: grid; place-items: center; font-size: 22px; font-weight: 700;
              color: #fff; margin: 0 auto 14px; }
.brand-name { font-size: 22px; font-weight: 600; }
.brand-sub  { font-size: 13px; color: var(--text3); margin-top: 4px; }
.field { margin-bottom: 16px; }
.field label { display: block; font-size: 13px; font-weight: 500; color: var(--text2); margin-bottom: 6px; }
.field input { width: 100%; background: var(--bg3); border: 1px solid var(--border);
               border-radius: 8px; padding: 10px 14px; font-size: 15px;
               font-family: 'Heebo', sans-serif; color: var(--text); outline: none;
               transition: border-color .15s; direction: ltr; }
.field input:focus { border-color: var(--accent); }
.btn { width: 100%; background: var(--accent); color: #fff; border: none; border-radius: 8px;
       padding: 11px; font-size: 15px; font-weight: 500; font-family: 'Heebo', sans-serif;
       cursor: pointer; margin-top: 8px; transition: background .15s; }
.btn:hover { background: var(--accent2); }
.alert { border-radius: 8px; padding: 12px 14px; font-size: 13px; margin-bottom: 20px;
         display: flex; align-items: flex-start; gap: 8px; }
.alert-err { background: rgba(224,85,85,.1); border: 1px solid rgba(224,85,85,.25); color: #ef9090; }
.alert-ok  { background: rgba(52,199,123,.1); border: 1px solid rgba(52,199,123,.25); color: #6ee4a8; }
.footer-link { text-align: center; margin-top: 20px; font-size: 12px; color: var(--text3); }
</style>
</head>
<body>
<div class="card">
  <div class="brand">
    <div class="brand-icon">מ</div>
    <div class="brand-name"><?= View::e($appName) ?></div>
    <div class="brand-sub">קביעת סיסמא</div>
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert alert-ok">✓ הסיסמה נקבעה בהצלחה! ניתן להתחבר כעת.</div>
    <a href="<?= $base ?>/login" class="btn" style="display:block;text-align:center;text-decoration:none;line-height:normal;padding:11px;">מעבר לכניסה</a>

  <?php elseif (!empty($error)): ?>
    <div class="alert alert-err">⚠ <?= View::e($error) ?></div>
    <a href="<?= $base ?>/login" style="display:block;text-align:center;font-size:13px;color:var(--text2);margin-top:8px;text-decoration:none;">חזרה לדף הכניסה</a>

  <?php else: ?>
    <form method="POST" action="<?= $base ?>/set-password">
      <input type="hidden" name="token" value="<?= View::e($token ?? '') ?>">
      <div class="field">
        <label for="password">סיסמה חדשה</label>
        <input type="password" id="password" name="password" placeholder="לפחות 6 תווים" autocomplete="new-password" autofocus required minlength="6">
      </div>
      <div class="field">
        <label for="password2">אימות סיסמה</label>
        <input type="password" id="password2" name="password2" placeholder="הכנס/י שוב" autocomplete="new-password" required>
      </div>
      <button type="submit" class="btn">קבע סיסמא</button>
    </form>
  <?php endif; ?>

  <div class="footer-link"><?= View::e($appName) ?> v2 · <?= date('Y') ?></div>
</div>
</body>
</html>
