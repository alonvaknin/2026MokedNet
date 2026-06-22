<?php
declare(strict_types=1);
/**
 * migration_area_managers_nav.php
 * מוסיף פריט ניווט למנהלי אזור
 * הרץ פעם אחת: php config/migration_area_managers_nav.php
 */

define('ROOT', __DIR__ . '/..');
$cfg = require ROOT . '/config/config.php';

$db  = $cfg['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname=alon_db2;charset=utf8mb4",
    $db['user'], $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Check if item already exists
$exists = $pdo->query("SELECT COUNT(*) FROM nav_items WHERE link='/area-managers'")->fetchColumn();
if ($exists) {
    echo "– פריט ניווט מנהלי אזור כבר קיים\n";
    exit;
}

// Get max ordering to append at end
$maxOrder = (int)$pdo->query("SELECT MAX(ordering) FROM nav_items WHERE parent_id IS NULL")->fetchColumn();

$pdo->prepare(
    "INSERT INTO nav_items (label_he, icon, link, link_type, ordering, is_active)
     VALUES (?, ?, ?, 'route', ?, 1)"
)->execute(['מנהלי אזור', 'bi bi-person-badge', '/area-managers', $maxOrder + 10]);

echo "✓ פריט ניווט מנהלי אזור נוסף (ordering=" . ($maxOrder + 10) . ")\n";
