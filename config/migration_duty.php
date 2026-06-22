<?php
declare(strict_types=1);
/**
 * migration_duty.php
 * מוסיף עמודת הרשאה canManageDuty ופריט ניווט לניהול תורנות
 * הרץ פעם אחת: php config/migration_duty.php
 */

define('ROOT', __DIR__ . '/..');
$cfg = require ROOT . '/config/config.php';
$db  = $cfg['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
    $db['user'], $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// 1. הרשאה — מוסיף canManageDuty לכל קבוצות ההרשאה (ברירת מחדל: לא מאושר)
$groups = $pdo->query("SELECT id FROM permission_groups")->fetchAll(PDO::FETCH_COLUMN);
$stmt = $pdo->prepare(
    "INSERT IGNORE INTO permission_group_grants (group_id, permission_key, granted)
     VALUES (?, 'canManageDuty', 0)"
);
foreach ($groups as $gid) {
    $stmt->execute([$gid]);
}
echo "✓ הרשאה canManageDuty נוספה לכל הקבוצות (ברירת מחדל: כבוי)\n";
echo "  הפעל ידנית למשתמשים הרלוונטיים דרך ניהול משתמשים\n";

// 2. פריט ניווט
$exists = $pdo->query("SELECT COUNT(*) FROM nav_items WHERE link='/duty'")->fetchColumn();
if ($exists) {
    echo "– פריט ניווט תורנות כבר קיים\n";
} else {
    $maxOrder = (int)$pdo->query("SELECT MAX(ordering) FROM nav_items WHERE parent_id IS NULL")->fetchColumn();
    $pdo->prepare(
        "INSERT INTO nav_items (label_he, icon, link, link_type, ordering, is_active)
         VALUES (?, ?, ?, 'route', ?, 1)"
    )->execute(['תורנות שבועית', 'bi bi-person-lines-fill', '/duty', $maxOrder + 10]);
    echo "✓ פריט ניווט תורנות נוסף\n";
}
