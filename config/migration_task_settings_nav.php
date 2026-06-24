<?php
declare(strict_types=1);
/**
 * migration_task_settings_nav.php
 * מוסיף פריט ניווט לעמוד הגדרות משימות (admin)
 * הרץ פעם אחת: php config/migration_task_settings_nav.php
 */

define('ROOT', __DIR__ . '/..');
$cfg = require ROOT . '/config/config.php';

$db  = $cfg['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
    $db['user'], $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Check if item already exists
$exists = $pdo->query("SELECT COUNT(*) FROM nav_items WHERE link='/admin/task-settings'")->fetchColumn();
if ($exists) {
    echo "– פריט ניווט הגדרות משימות כבר קיים\n";
    exit;
}

// Get max ordering to append at end
$maxOrder = (int)$pdo->query("SELECT MAX(ordering) FROM nav_items WHERE parent_id IS NULL")->fetchColumn();

// Insert nav item
$stmt = $pdo->prepare(
    "INSERT INTO nav_items (label_he, icon, link, link_type, ordering, is_active)
     VALUES (?, ?, ?, 'route', ?, 1)"
);
$stmt->execute(['הגדרות משימות', 'bi bi-gear-fill', '/admin/task-settings', $maxOrder + 10]);
$navItemId = (int)$pdo->lastInsertId();

echo "✓ פריט ניווט הגדרות משימות נוסף (id={$navItemId}, ordering=" . ($maxOrder + 10) . ")\n";

// Add permission restrictions: visible only to admin groups
$adminGroups = $pdo->query(
    "SELECT id FROM permission_groups WHERE name_heb LIKE '%מנהל%'"
)->fetchAll(PDO::FETCH_COLUMN);

if (empty($adminGroups)) {
    echo "⚠ לא נמצאו קבוצות מנהלים — הקישור יהיה גלוי לכולם\n";
} else {
    $permStmt = $pdo->prepare("INSERT IGNORE INTO nav_permissions (nav_item_id, perm_group_id) VALUES (?, ?)");
    foreach ($adminGroups as $gid) {
        $permStmt->execute([$navItemId, (int)$gid]);
        echo "  ✓ הרשאה → group #{$gid}\n";
    }
}
