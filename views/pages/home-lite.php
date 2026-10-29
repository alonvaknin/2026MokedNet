<?php use Core\View; ?>
<div style="text-align:center;padding:80px 20px;">
  <div style="font-size:22px;font-weight:600;margin-bottom:8px;">שלום <?= View::e($user['first_name'] ?? '') ?></div>
  <div style="font-size:15px;color:var(--text2);">השתמש בתפריט או בחיפוש העליון כדי להתחיל.</div>
</div>
