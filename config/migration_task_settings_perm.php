<?php
declare(strict_types=1);
/**
 * Run once: php config/migration_task_settings_perm.php
 */
define('ROOT', __DIR__ . '/..');
$cfg = require ROOT . '/config/config.php';
$db  = $cfg['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}",
    $db['user'], $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function run2(PDO $pdo, string $desc, string $sql): void {
    try {
        $pdo->exec($sql);
        echo "✓ $desc\n";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, 'already exists') || str_contains($msg, 'Duplicate')) {
            echo "– $desc (כבר קיים)\n";
        } else {
            echo "✗ $desc: $msg\n";
        }
    }
}

// 1. ADD COLUMN sla_notified_at to tasks (MySQL 5.7: no IF NOT EXISTS)
$colExists = $pdo->query("
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tasks' AND COLUMN_NAME='sla_notified_at'
    LIMIT 1")->fetch();

if (!$colExists) {
    run2($pdo, 'ALTER tasks: add sla_notified_at',
        "ALTER TABLE tasks ADD COLUMN sla_notified_at DATETIME NULL AFTER sla_days");
} else {
    echo "– ALTER tasks: add sla_notified_at (כבר קיים)\n";
}

// 2. Add permission task_settings.manage to admin groups
$adminGroups = $pdo->query(
    "SELECT id FROM permission_groups WHERE name_heb LIKE '%מנהל%'"
)->fetchAll(PDO::FETCH_COLUMN);

if (empty($adminGroups)) {
    echo "⚠ לא נמצאו קבוצות מנהלים — עדכן ידנית:\n";
    echo "  INSERT INTO permission_group_grants (group_id, permission_key, granted) VALUES (<GROUP_ID>, 'task_settings.manage', 1);\n";
} else {
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO permission_group_grants (group_id, permission_key, granted) VALUES (?, 'task_settings.manage', 1)"
    );
    foreach ($adminGroups as $gid) {
        $stmt->execute([$gid]);
        echo "✓ permission task_settings.manage → group #{$gid}\n";
    }
}

echo "\nמיגרציה הושלמה.\n";
