<?php
declare(strict_types=1);
/**
 * migration_nav_automation.php — הרץ פעם אחת מה-CLI:
 *   php config/migration_nav_automation.php
 *
 * מוסיף פריט ניווט "אוטומציה" לטבלת navBar ב-alon_db.
 * לחיצה עליו פותחת את openAutomationModal() (jsfunction).
 * בטוח לריצה חוזרת.
 */

define('ROOT', __DIR__ . '/..');
$cfg = require ROOT . '/config/config.php';

$db  = $cfg['db_v1'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}",
    $db['user'], $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "\nמתחבר ל: {$db['name']}\n";

// בדוק אם כבר קיים
$existing = $pdo->prepare("SELECT id FROM navBar WHERE navLink = 'openAutomationModal' AND navLinkType = 'jsfunction' LIMIT 1");
$existing->execute();

if ($existing->fetchColumn()) {
    echo "– פריט ניווט אוטומציה כבר קיים.\n\n";
    exit;
}

// שלוף את ה-ordering המקסימלי הנוכחי
$maxOrder = (int) $pdo->query("SELECT MAX(ordering) FROM navBar")->fetchColumn();

// הכנס את הפריט
$stmt = $pdo->prepare("
    INSERT INTO navBar
        (navNameHEB, navLink, navLinkType, icon,
         isParent, isSubMenu, mainNavContainer, parentID,
         ordering, toBlank, isActive, navPermmission)
    VALUES
        (?, ?, ?, ?,
         0, 1, 1, NULL,
         ?, 0, 1, 'all')
");

$stmt->execute([
    'אוטומציה',               // navNameHEB
    'openAutomationModal',    // navLink  — שם הפונקציה JS
    'jsfunction',             // navLinkType
    'bi-lightning-charge-fill', // icon (Bootstrap Icons)
    $maxOrder + 10,           // ordering
]);

echo "✓ פריט ניווט 'אוטומציה' נוסף (id=" . $pdo->lastInsertId() . ", ordering=" . ($maxOrder + 10) . ")\n\n";
