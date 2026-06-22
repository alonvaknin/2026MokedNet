<?php use Core\View; ?>
<div style="text-align:center;padding:60px 20px;">
  <div style="font-size:64px;margin-bottom:16px;">🔒</div>
  <div style="font-size:22px;font-weight:600;margin-bottom:8px;">אין הרשאה</div>
  <div style="font-size:15px;color:var(--text2);margin-bottom:24px;">אין לך גישה לעמוד זה. פנה למנהל המערכת.</div>
  <a href="<?= rtrim(CFG['app']['url'],'/') ?>/dashboard" class="btn btn-primary">חזרה לדף הבית</a>
</div>
