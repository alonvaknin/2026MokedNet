<?php
declare(strict_types=1);
/**
 * Run once: php config/migration_tasks_phase3.php
 */
define('ROOT', __DIR__ . '/..');
$cfg = require ROOT . '/config/config.php';
$db  = $cfg['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}",
    $db['user'], $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function colExists(PDO $pdo, string $table, string $col): bool {
    return (bool)$pdo->query(
        "SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' AND COLUMN_NAME='{$col}' LIMIT 1"
    )->fetch();
}

function run3(PDO $pdo, string $desc, string $sql): void {
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

// 1. status_changed_by
if (!colExists($pdo, 'tasks', 'status_changed_by')) {
    run3($pdo, 'ADD tasks.status_changed_by',
        "ALTER TABLE tasks ADD COLUMN status_changed_by INT NULL AFTER status_id");
} else {
    echo "– tasks.status_changed_by (כבר קיים)\n";
}

// 2. status_changed_at — may already exist from Phase 2
if (!colExists($pdo, 'tasks', 'status_changed_at')) {
    run3($pdo, 'ADD tasks.status_changed_at',
        "ALTER TABLE tasks ADD COLUMN status_changed_at DATETIME NULL AFTER status_changed_by");
} else {
    echo "– tasks.status_changed_at (כבר קיים)\n";
}

// 3. assigned_dept_id
if (!colExists($pdo, 'tasks', 'assigned_dept_id')) {
    run3($pdo, 'ADD tasks.assigned_dept_id',
        "ALTER TABLE tasks ADD COLUMN assigned_dept_id INT NULL AFTER assigned_user_id");
} else {
    echo "– tasks.assigned_dept_id (כבר קיים)\n";
}

// 4. task_statuses.is_closed
if (!colExists($pdo, 'task_statuses', 'is_closed')) {
    run3($pdo, 'ADD task_statuses.is_closed',
        "ALTER TABLE task_statuses ADD COLUMN is_closed TINYINT(1) NOT NULL DEFAULT 0 AFTER color");
} else {
    echo "– task_statuses.is_closed (כבר קיים)\n";
}

// 5. task_comments table
run3($pdo, 'CREATE task_comments',
    "CREATE TABLE IF NOT EXISTS task_comments (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        task_id    INT UNSIGNED NOT NULL,
        user_id    INT NOT NULL,
        body       TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_task (task_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

// 6. tasks.viewAll permission for admin groups
$adminGroups = $pdo->query(
    "SELECT id FROM permission_groups WHERE name_heb LIKE '%מנהל%'"
)->fetchAll(PDO::FETCH_COLUMN);

if (empty($adminGroups)) {
    echo "⚠ לא נמצאו קבוצות מנהלים — עדכן ידנית:\n";
    echo "  INSERT INTO permission_group_grants (group_id, permission_key, granted) VALUES (<ID>, 'tasks.viewAll', 1);\n";
} else {
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO permission_group_grants (group_id, permission_key, granted) VALUES (?, 'tasks.viewAll', 1)"
    );
    foreach ($adminGroups as $gid) {
        $stmt->execute([$gid]);
        echo "✓ tasks.viewAll → group #{$gid}\n";
    }
}

echo "\nמיגרציה הושלמה.\n";
