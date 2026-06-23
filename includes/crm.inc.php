<?php
$caller = filter_var($_GET['caller'] ?? '', FILTER_SANITIZE_NUMBER_INT);

if (!$caller) {
    header('Location: https://alon.alexisdeveloping.com/dashboard');
    exit;
}

// If the main app is open in window.opener, tell it to open the CRM popup.
// Otherwise redirect to the dashboard with ?caller= so the main layout opens it automatically.
?><!DOCTYPE html>
<html lang="he">
<head>
  <meta charset="UTF-8">
  <title>פותח CRM…</title>
  <style>
    body { margin: 0; background: #0f1117; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: Arial, sans-serif; color: #aaa; }
    .msg { text-align: center; }
    .spinner { width: 32px; height: 32px; border: 3px solid #333; border-top-color: #5b8dee; border-radius: 50%; animation: spin .7s linear infinite; margin: 0 auto 16px; }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>
<div class="msg">
  <div class="spinner"></div>
  <div>פותח CRM…</div>
</div>
<script>
(function () {
  var caller = <?= json_encode($caller) ?>;
  var base   = 'https://alon.alexisdeveloping.com';

  // Try to reuse an existing opener/parent that already has CRM loaded
  var target = null;
  try {
    if (window.opener && !window.opener.closed && typeof window.opener.CRM !== 'undefined') {
      target = window.opener;
    } else if (window.parent !== window && typeof window.parent.CRM !== 'undefined') {
      target = window.parent;
    }
  } catch(e) {}

  if (target) {
    target.CRM.open(caller);
    target.focus();
    window.close();
  } else {
    // No usable opener — redirect to dashboard with caller param so the main layout opens the popup
    window.location.replace(base + '/dashboard?caller=' + encodeURIComponent(caller));
  }
})();
</script>
</body>
</html>
