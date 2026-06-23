<?php
$caller = htmlspecialchars($_GET['caller'] ?? '', ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="he">
<head><meta charset="UTF-8"><title>CRM</title></head>
<body>
<script>
(function () {
    var caller = <?= json_encode($caller) ?>;
    if (!caller) { window.close(); return; }
    var target = window.opener || window.parent;
    if (target && typeof target.CRM !== 'undefined' && typeof target.CRM.open === 'function') {
        target.CRM.open(caller);
        window.close();
    } else {
        document.body.innerText = 'לא ניתן לפתוח CRM — נסה לרענן את הדף הראשי';
    }
})();
</script>
</body>
</html>
