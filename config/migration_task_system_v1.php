<?php
declare(strict_types=1);
/**
 * Run once: php config/migration_task_system_v1.php
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
        if (str_contains($e->getMessage(), 'already exists') || str_contains($e->getMessage(), 'Duplicate')) {
            echo "– $desc (כבר קיים)\n";
        } else {
            echo "✗ $desc: " . $e->getMessage() . "\n";
        }
    }
}

run($pdo, 'CREATE task_types', "
    CREATE TABLE IF NOT EXISTS task_types (
        id                   INT AUTO_INCREMENT PRIMARY KEY,
        name                 VARCHAR(100) NOT NULL,
        sla_days             INT          NOT NULL DEFAULT 3,
        default_assignee_ids JSON         NOT NULL DEFAULT '[]',
        default_watcher_ids  JSON         NOT NULL DEFAULT '[]',
        created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

run($pdo, 'CREATE task_statuses', "
    CREATE TABLE IF NOT EXISTS task_statuses (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        task_type_id INT         NOT NULL,
        name         VARCHAR(50) NOT NULL,
        color        VARCHAR(20) NOT NULL DEFAULT '#4f7fff',
        sort_order   INT         NOT NULL DEFAULT 0,
        FOREIGN KEY (task_type_id) REFERENCES task_types(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

run($pdo, 'ALTER tasks: add task_type_id', "
    ALTER TABLE tasks ADD COLUMN IF NOT EXISTS task_type_id INT NULL AFTER id
");
run($pdo, 'ALTER tasks: add status_id', "
    ALTER TABLE tasks ADD COLUMN IF NOT EXISTS status_id INT NULL AFTER task_type_id
");
run($pdo, 'ALTER tasks: add source_type', "
    ALTER TABLE tasks ADD COLUMN IF NOT EXISTS source_type VARCHAR(50) NULL AFTER assigned_dept_id
");
run($pdo, 'ALTER tasks: add source_id', "
    ALTER TABLE tasks ADD COLUMN IF NOT EXISTS source_id INT NULL AFTER source_type
");

// FK may already exist if re-run — catch silently
run($pdo, 'FK tasks.task_type_id', "
    ALTER TABLE tasks ADD CONSTRAINT fk_tasks_type
    FOREIGN KEY (task_type_id) REFERENCES task_types(id)
");
run($pdo, 'FK tasks.status_id', "
    ALTER TABLE tasks ADD CONSTRAINT fk_tasks_status
    FOREIGN KEY (status_id) REFERENCES task_statuses(id)
");

run($pdo, 'CREATE task_watchers', "
    CREATE TABLE IF NOT EXISTS task_watchers (
        id      INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        user_id INT NOT NULL,
        UNIQUE KEY uq_task_user (task_id, user_id),
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ── Seed data ──────────────────────────────────────────────────────────────
// Insert task type "שינוי שם בחשבונית"
// default_assignee_ids: replace USER_ID_EYAL with the real user id from the users table
// default_watcher_ids:  [0] means "self" (the user who opened the request)
// Find Eyal Guata's user ID
$eyalRow = $pdo->query("SELECT id FROM users WHERE CONCAT(first_name,' ',last_name) LIKE '%אייל גואטה%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$eyalId  = $eyalRow ? (int)$eyalRow['id'] : 0;
$assigneeJson = json_encode($eyalId > 0 ? [$eyalId] : [], JSON_UNESCAPED_UNICODE);

$stmt = $pdo->prepare("INSERT IGNORE INTO task_types (id, name, sla_days, default_assignee_ids, default_watcher_ids) VALUES (1, 'שינוי שם בחשבונית', 3, ?, JSON_ARRAY(0))");
$stmt->execute([$assigneeJson]);
if ($eyalId > 0) {
    echo "✓ Seeded task_type id=1 (שינוי שם בחשבונית), assignee=user#{$eyalId}\n";
} else {
    echo "⚠ Seeded task_type id=1 — אייל גואטה לא נמצא, עדכן ידנית: UPDATE task_types SET default_assignee_ids='[ID]' WHERE id=1\n";
}

// Insert statuses for type 1
$statuses = [
    ['פתוח',    '#22c55e', 1],
    ['בטיפול',  '#f97316', 2],
    ['ממתין',   '#6b7280', 3],
    ['סגור',    '#4f7fff', 4],
];
$stmt = $pdo->prepare("INSERT IGNORE INTO task_statuses (task_type_id, name, color, sort_order) VALUES (1,?,?,?)");
foreach ($statuses as [$name, $color, $order]) {
    $stmt->execute([$name, $color, $order]);
    echo "✓ status: $name\n";
}

echo "\nמיגרציה הושלמה.\n";
echo "בדוק שה-assignee_id של אייל גואטה הוכנס נכון:\n";
echo "SELECT * FROM task_types;\n";
