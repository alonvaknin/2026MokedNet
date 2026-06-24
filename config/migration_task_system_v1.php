<?php
declare(strict_types=1);
/**
 * Run once: php config/migration_task_system_v1.php
 *
 * Compatible with MySQL 5.7+ (no JSON DEFAULT, no ADD COLUMN IF NOT EXISTS)
 */
define('ROOT', __DIR__ . '/..');
$cfg = require ROOT . '/config/config.php';
$db  = $cfg['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}",
    $db['user'], $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function run(PDO $pdo, string $desc, string $sql): void {
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

// Detect the id column type on tasks (INT vs INT UNSIGNED) so FKs match
$tasksIdType = 'INT';
try {
    $row = $pdo->query("
        SELECT COLUMN_TYPE FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'id'
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $tasksIdType = strtoupper(trim($row['COLUMN_TYPE']));
        echo "ℹ tasks.id type detected: {$tasksIdType}\n";
    }
} catch (PDOException $e) {
    echo "⚠ Could not detect tasks.id type, defaulting to INT\n";
}

// ── Create task_types ───────────────────────────────────────────────────────
// JSON columns cannot have DEFAULT in MySQL < 8.0.13 — omit DEFAULT,
// application always supplies values on INSERT.
run($pdo, 'CREATE task_types', "
    CREATE TABLE IF NOT EXISTS task_types (
        id                   INT AUTO_INCREMENT PRIMARY KEY,
        name                 VARCHAR(100) NOT NULL,
        sla_days             INT          NOT NULL DEFAULT 3,
        default_assignee_ids JSON         NOT NULL,
        default_watcher_ids  JSON         NOT NULL,
        created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Detect task_types.id column type so task_statuses.task_type_id matches exactly
$taskTypesIdType = 'INT';
try {
    $row2 = $pdo->query("
        SELECT COLUMN_TYPE FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'task_types' AND COLUMN_NAME = 'id'
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    if ($row2) {
        $taskTypesIdType = strtoupper(trim($row2['COLUMN_TYPE']));
        echo "ℹ task_types.id type: {$taskTypesIdType}\n";
    }
} catch (PDOException $e) {}

// ── Create task_statuses ────────────────────────────────────────────────────
run($pdo, 'CREATE task_statuses', "
    CREATE TABLE IF NOT EXISTS task_statuses (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        task_type_id {$taskTypesIdType} NOT NULL,
        name         VARCHAR(50)  NOT NULL,
        color        VARCHAR(20)  NOT NULL DEFAULT '#4f7fff',
        sort_order   INT          NOT NULL DEFAULT 0,
        FOREIGN KEY (task_type_id) REFERENCES task_types(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ── ALTER tasks — check column existence first (MySQL 5.7 has no IF NOT EXISTS) ──
function columnExists(PDO $pdo, string $table, string $column): bool {
    $row = $pdo->query("
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = '{$table}'
          AND COLUMN_NAME  = '{$column}'
        LIMIT 1
    ")->fetch();
    return (bool)$row;
}

if (!columnExists($pdo, 'tasks', 'task_type_id')) {
    run($pdo, 'ALTER tasks: add task_type_id', "ALTER TABLE tasks ADD COLUMN task_type_id INT NULL AFTER id");
} else {
    echo "– ALTER tasks: add task_type_id (כבר קיים)\n";
}

if (!columnExists($pdo, 'tasks', 'status_id')) {
    run($pdo, 'ALTER tasks: add status_id', "ALTER TABLE tasks ADD COLUMN status_id INT NULL AFTER task_type_id");
} else {
    echo "– ALTER tasks: add status_id (כבר קיים)\n";
}

if (!columnExists($pdo, 'tasks', 'source_type')) {
    run($pdo, 'ALTER tasks: add source_type', "ALTER TABLE tasks ADD COLUMN source_type VARCHAR(50) NULL");
} else {
    echo "– ALTER tasks: add source_type (כבר קיים)\n";
}

if (!columnExists($pdo, 'tasks', 'source_id')) {
    run($pdo, 'ALTER tasks: add source_id', "ALTER TABLE tasks ADD COLUMN source_id INT NULL");
} else {
    echo "– ALTER tasks: add source_id (כבר קיים)\n";
}

// ── Foreign keys on tasks (best-effort — skip if type mismatch) ─────────────
// These FKs are optional — the app uses numeric lookups, not DB-enforced FK.
// Detect actual column types on tasks to decide if FKs are safe to add.
function getColType(PDO $pdo, string $table, string $col): string {
    $r = $pdo->query("
        SELECT COLUMN_TYPE FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' AND COLUMN_NAME='{$col}'
        LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    return $r ? strtoupper(trim($r['COLUMN_TYPE'])) : '';
}
$ttIdType  = getColType($pdo, 'task_types',    'id');
$tsIdType  = getColType($pdo, 'task_statuses', 'id');
$tTtColType = getColType($pdo, 'tasks', 'task_type_id');
$tStColType = getColType($pdo, 'tasks', 'status_id');

if ($ttIdType && $tTtColType && $ttIdType === $tTtColType) {
    run($pdo, 'FK tasks.task_type_id', "ALTER TABLE tasks ADD CONSTRAINT fk_tasks_type FOREIGN KEY (task_type_id) REFERENCES task_types(id)");
} else {
    echo "– FK tasks.task_type_id (דילוג — type mismatch: tasks.task_type_id={$tTtColType} vs task_types.id={$ttIdType})\n";
}
if ($tsIdType && $tStColType && $tsIdType === $tStColType) {
    run($pdo, 'FK tasks.status_id', "ALTER TABLE tasks ADD CONSTRAINT fk_tasks_status FOREIGN KEY (status_id) REFERENCES task_statuses(id)");
} else {
    echo "– FK tasks.status_id (דילוג — type mismatch: tasks.status_id={$tStColType} vs task_statuses.id={$tsIdType})\n";
}

// ── Create task_watchers ─────────────────────────────────────────────────────
// task_id must match tasks.id type exactly
run($pdo, 'CREATE task_watchers', "
    CREATE TABLE IF NOT EXISTS task_watchers (
        id      INT AUTO_INCREMENT PRIMARY KEY,
        task_id {$tasksIdType} NOT NULL,
        user_id INT             NOT NULL,
        UNIQUE KEY uq_task_user (task_id, user_id),
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ── Seed data ────────────────────────────────────────────────────────────────
$eyalRow      = $pdo->query("SELECT id FROM users WHERE CONCAT(first_name,' ',last_name) LIKE '%אייל גואטה%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$eyalId       = $eyalRow ? (int)$eyalRow['id'] : 0;
$assigneeJson = json_encode($eyalId > 0 ? [$eyalId] : [], JSON_UNESCAPED_UNICODE);
$watcherJson  = json_encode([0], JSON_UNESCAPED_UNICODE); // 0 = "self"

$stmt = $pdo->prepare("INSERT IGNORE INTO task_types (id, name, sla_days, default_assignee_ids, default_watcher_ids) VALUES (1, 'שינוי שם בחשבונית', 3, ?, ?)");
$stmt->execute([$assigneeJson, $watcherJson]);

if ($eyalId > 0) {
    echo "✓ Seeded task_type id=1 (שינוי שם בחשבונית), assignee=user#{$eyalId}\n";
} else {
    echo "⚠ Seeded task_type id=1 — אייל גואטה לא נמצא, עדכן ידנית:\n";
    echo "  UPDATE task_types SET default_assignee_ids='[<USER_ID>]' WHERE id=1;\n";
}

$stmt = $pdo->prepare("INSERT IGNORE INTO task_statuses (task_type_id, name, color, sort_order) VALUES (1,?,?,?)");
foreach ([
    ['פתוח',   '#22c55e', 1],
    ['בטיפול', '#f97316', 2],
    ['ממתין',  '#6b7280', 3],
    ['סגור',   '#4f7fff', 4],
] as [$name, $color, $order]) {
    $stmt->execute([$name, $color, $order]);
    echo "✓ status: $name\n";
}

echo "\nמיגרציה הושלמה.\n";
echo "בדוק: SELECT id, name, default_assignee_ids FROM task_types;\n";
