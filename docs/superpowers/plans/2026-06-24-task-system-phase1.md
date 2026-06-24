# Task System Phase 1 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add task types, flexible statuses, watchers, and auto-create a task when an invoice-change-name request is submitted.

**Architecture:** Three new DB tables (`task_types`, `task_statuses`, `task_watchers`) plus four new columns on `tasks`. `TaskModel` gains `createFromSource()` and `addWatcher()`. `InvoiceChangeNameController::create()` calls `createFromSource()` after its existing insert. The `/tasks` view gains a coloured status badge with inline AJAX status-change and an editable title.

**Tech Stack:** PHP 8.1, PDO/MySQL, vanilla JS (no framework), Bootstrap Icons (already loaded in layout).

## Global Constraints

- PHP strict types (`declare(strict_types=1)`) in every new file.
- All DB access through `Core\DB` static helpers — never instantiate PDO directly.
- CSRF token required on every POST/PATCH endpoint (`$this->verifyCsrf()`).
- RTL Hebrew UI — direction:rtl on all new HTML.
- `is_active` column on `tasks` must remain functional (existing close flow).
- Foreign key `status_id` is nullable so existing tasks without a type are not broken.
- Migration file pattern: `config/migration_<name>.php`, run once manually.
- No composer packages to add.

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `config/migration_task_system_v1.php` | Create | DDL: new tables + ALTER tasks |
| `src/Models/TaskModel.php` | Modify | Add `createFromSource`, `addWatcher`, update `forUser`, `create`, `close`, `updateStatus`, `updateTitle` |
| `src/Controllers/TaskController.php` | Modify | Add `updateStatus`, `updateTitle` actions |
| `config/routes.php` | Modify | Add two new POST/PATCH routes |
| `src/Controllers/InvoiceChangeNameController.php` | Modify | Call `TaskModel::createFromSource()` after insert |
| `views/pages/tasks/index.php` | Modify | Status badge, inline title edit, source link |

---

## Task 1: Database Migration

**Files:**
- Create: `config/migration_task_system_v1.php`

**Interfaces:**
- Produces: Tables `task_types`, `task_statuses`, `task_watchers`; columns `task_type_id`, `status_id`, `source_type`, `source_id` on `tasks`.

- [ ] **Step 1: Create migration file**

```php
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
$pdo->exec("
    INSERT IGNORE INTO task_types (id, name, sla_days, default_assignee_ids, default_watcher_ids)
    VALUES (1, 'שינוי שם בחשבונית', 3,
            (SELECT JSON_ARRAY(id) FROM users WHERE CONCAT(first_name,' ',last_name) LIKE '%אייל גואטה%' LIMIT 1),
            JSON_ARRAY(0))
");
echo "✓ Seeded task_type id=1 (שינוי שם בחשבונית)\n";

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
```

- [ ] **Step 2: Run the migration**

```bash
php config/migration_task_system_v1.php
```

Expected output:
```
✓ CREATE task_types
✓ CREATE task_statuses
✓ ALTER tasks: add task_type_id
✓ ALTER tasks: add status_id
✓ ALTER tasks: add source_type
✓ ALTER tasks: add source_id
✓ FK tasks.task_type_id
✓ FK tasks.status_id
✓ CREATE task_watchers
✓ Seeded task_type id=1 (שינוי שם בחשבונית)
✓ status: פתוח
✓ status: בטיפול
✓ status: ממתין
✓ status: סגור

מיגרציה הושלמה.
```

- [ ] **Step 3: Verify seed data**

```bash
php -r "
define('ROOT','d:/repo/git/2026MokedNet');
\$cfg=require ROOT.'/config/config.php';
\$pdo=new PDO('mysql:host='.\$cfg['db']['host'].';dbname='.\$cfg['db']['name'].';charset=utf8mb4',\$cfg['db']['user'],\$cfg['db']['pass']);
print_r(\$pdo->query('SELECT id,name,sla_days,default_assignee_ids FROM task_types')->fetchAll(PDO::FETCH_ASSOC));
print_r(\$pdo->query('SELECT * FROM task_statuses')->fetchAll(PDO::FETCH_ASSOC));
"
```

Confirm: `default_assignee_ids` is not `[null]` — it should contain a valid user id like `[7]`. If it is `[null]`, find Eyal's real user id and run:
```sql
UPDATE task_types SET default_assignee_ids = JSON_ARRAY(<real_id>) WHERE id = 1;
```

- [ ] **Step 4: Commit**

```bash
git add config/migration_task_system_v1.php
git commit -m "feat: migration — task_types, task_statuses, task_watchers, ALTER tasks"
```

---

## Task 2: TaskModel — New and Updated Methods

**Files:**
- Modify: `src/Models/TaskModel.php`

**Interfaces:**
- Consumes: DB tables from Task 1.
- Produces:
  - `TaskModel::forUser(int $userId, bool $closed): array` — enriched with `status_name`, `status_color`, `type_name`, `source_type`, `source_id`
  - `TaskModel::create(array $data): int` — accepts optional `task_type_id`, `status_id`, `source_type`, `source_id`
  - `TaskModel::createFromSource(int $taskTypeId, string $sourceType, int $sourceId, int $openBy, string $title): int`
  - `TaskModel::addWatcher(int $taskId, int $userId): void`
  - `TaskModel::updateStatus(int $id, int $statusId, int $byUserId): bool`
  - `TaskModel::updateTitle(int $id, string $title, int $byUserId): bool`
  - `TaskModel::close(int $id, int $byUserId): bool` — unchanged signature, also sets `is_active=0`

- [ ] **Step 1: Replace TaskModel.php entirely**

```php
<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class TaskModel
{
    public static function forUser(int $userId, bool $closed = false): array
    {
        return DB::query(
            'SELECT t.id, t.title, t.description, t.sla_days,
                    t.created_at, t.status_changed_at, t.is_active,
                    t.source_type, t.source_id,
                    CONCAT(u.first_name," ",u.last_name) AS opened_by_name,
                    ts.name  AS status_name,
                    ts.color AS status_color,
                    tt.name  AS type_name,
                    tt.id    AS task_type_id,
                    t.status_id
             FROM tasks t
             LEFT JOIN users u         ON u.id  = t.open_by
             LEFT JOIN task_statuses ts ON ts.id = t.status_id
             LEFT JOIN task_types    tt ON tt.id = t.task_type_id
             WHERE t.assigned_user_id = ? AND t.is_active = ?
             ORDER BY t.created_at DESC',
            [$userId, $closed ? 0 : 1]
        );
    }

    public static function create(array $data): int
    {
        return DB::insert(
            'INSERT INTO tasks
                (open_by, assigned_user_id, title, description,
                 sla_days, status_id, assigned_dept_id,
                 task_type_id, source_type, source_id,
                 created_at, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)',
            [
                $data['open_by'],
                $data['assigned_user_id'],
                $data['title'],
                $data['description']      ?? '',
                $data['sla_days']         ?? 3,
                $data['status_id']        ?? null,
                $data['assigned_dept_id'] ?? null,
                $data['task_type_id']     ?? null,
                $data['source_type']      ?? null,
                $data['source_id']        ?? null,
            ]
        );
    }

    /**
     * Create a task automatically from a source entity (e.g. invoice_change_name).
     * Loads assignees and watchers from task_types defaults.
     * Watcher id 0 means "self" (the user who opened the request) and is replaced
     * with $openBy before inserting.
     */
    public static function createFromSource(
        int    $taskTypeId,
        string $sourceType,
        int    $sourceId,
        int    $openBy,
        string $title
    ): int {
        $type = DB::row(
            'SELECT tt.sla_days, tt.default_assignee_ids, tt.default_watcher_ids,
                    ts.id AS first_status_id
             FROM task_types tt
             LEFT JOIN task_statuses ts ON ts.task_type_id = tt.id
             WHERE tt.id = ?
             ORDER BY ts.sort_order ASC
             LIMIT 1',
            [$taskTypeId]
        );

        if (!$type) {
            return 0;
        }

        $assigneeIds = json_decode($type['default_assignee_ids'] ?? '[]', true) ?: [];
        $watcherIds  = json_decode($type['default_watcher_ids']  ?? '[]', true) ?: [];

        // Replace sentinel 0 with the actual opener
        $watcherIds = array_map(fn($id) => ($id === 0 ? $openBy : $id), $watcherIds);

        $assignedTo = $assigneeIds[0] ?? $openBy;

        $taskId = self::create([
            'open_by'          => $openBy,
            'assigned_user_id' => $assignedTo,
            'title'            => $title,
            'description'      => '',
            'sla_days'         => $type['sla_days'],
            'status_id'        => $type['first_status_id'] ?: null,
            'task_type_id'     => $taskTypeId,
            'source_type'      => $sourceType,
            'source_id'        => $sourceId,
        ]);

        foreach ($watcherIds as $uid) {
            if ($uid > 0) {
                self::addWatcher($taskId, $uid);
            }
        }

        return $taskId;
    }

    public static function addWatcher(int $taskId, int $userId): void
    {
        DB::execute(
            'INSERT IGNORE INTO task_watchers (task_id, user_id) VALUES (?, ?)',
            [$taskId, $userId]
        );
    }

    public static function close(int $id, int $byUserId): bool
    {
        return DB::execute(
            'UPDATE tasks SET is_active = 0, status_changed_at = NOW()
             WHERE id = ? AND (assigned_user_id = ? OR open_by = ?)',
            [$id, $byUserId, $byUserId]
        ) > 0;
    }

    public static function updateStatus(int $id, int $statusId, int $byUserId): bool
    {
        return DB::execute(
            'UPDATE tasks SET status_id = ?, status_changed_at = NOW()
             WHERE id = ? AND (assigned_user_id = ? OR open_by = ?)',
            [$statusId, $id, $byUserId, $byUserId]
        ) > 0;
    }

    public static function updateTitle(int $id, string $title, int $byUserId): bool
    {
        $title = trim($title);
        if ($title === '') {
            return false;
        }
        return DB::execute(
            'UPDATE tasks SET title = ?
             WHERE id = ? AND (assigned_user_id = ? OR open_by = ?)',
            [$title, $id, $byUserId, $byUserId]
        ) > 0;
    }

    public static function typeByName(string $name): ?array
    {
        return DB::row(
            'SELECT * FROM task_types WHERE name = ? LIMIT 1',
            [$name]
        );
    }
}
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l src/Models/TaskModel.php
```

Expected: `No syntax errors detected in src/Models/TaskModel.php`

- [ ] **Step 3: Commit**

```bash
git add src/Models/TaskModel.php
git commit -m "feat: TaskModel — createFromSource, addWatcher, updateStatus, updateTitle"
```

---

## Task 3: TaskController — New Endpoints

**Files:**
- Modify: `src/Controllers/TaskController.php`

**Interfaces:**
- Consumes: `TaskModel::updateStatus(int, int, int): bool`, `TaskModel::updateTitle(int, string, int): bool`
- Produces:
  - `POST /tasks/{id}/status` — body: `status_id` (int), returns JSON `{error, msg}`
  - `POST /tasks/{id}/title`  — body: `title` (string), returns JSON `{error, msg}`

- [ ] **Step 1: Replace TaskController.php**

```php
<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\ActivityLog;
use Models\TaskModel;

class TaskController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $userId = $_SESSION['user_id'];
        $tasks  = TaskModel::forUser($userId, false);

        // Build status lists indexed by task_type_id for the JS dropdown
        $statusesByType = [];
        foreach ($tasks as $t) {
            $tid = (int)($t['task_type_id'] ?? 0);
            if ($tid && !isset($statusesByType[$tid])) {
                $rows = \Core\DB::query(
                    'SELECT id, name, color FROM task_statuses WHERE task_type_id = ? ORDER BY sort_order',
                    [$tid]
                );
                $statusesByType[$tid] = $rows;
            }
        }

        $this->view('pages/tasks/index', compact('tasks', 'statusesByType'));
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $newId = TaskModel::create([
            'open_by'          => $_SESSION['user_id'],
            'assigned_user_id' => (int)$this->post('for_user', $_SESSION['user_id']),
            'title'            => trim($this->post('title', '')),
            'description'      => trim($this->post('description', '')),
            'sla_days'         => (int)$this->post('sla_days', 3),
            'assigned_dept_id' => (int)$this->post('depart_id', 0) ?: null,
        ]);

        ActivityLog::create('task', $newId, trim($this->post('title', '')));
        $this->redirect('/tasks');
    }

    public function close(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        TaskModel::close((int)$id, $_SESSION['user_id']);
        ActivityLog::log('task.close', 'task', (int)$id, "משימה #{$id}");
        $this->redirect('/tasks');
    }

    public function updateStatus(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $statusId = (int)$this->post('status_id', 0);
        if ($statusId <= 0) {
            $this->json(['error' => true, 'msg' => 'סטטוס לא תקין'], 422);
            return;
        }

        $ok = TaskModel::updateStatus((int)$id, $statusId, $_SESSION['user_id']);
        if (!$ok) {
            $this->json(['error' => true, 'msg' => 'לא נמצאה משימה או אין הרשאה'], 404);
            return;
        }

        ActivityLog::log('task.status', 'task', (int)$id, "משימה #{$id}", "status_id → {$statusId}");
        $this->json(['error' => false, 'msg' => 'סטטוס עודכן']);
    }

    public function updateTitle(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $title = trim($this->post('title', ''));
        if ($title === '') {
            $this->json(['error' => true, 'msg' => 'כותרת לא יכולה להיות ריקה'], 422);
            return;
        }

        $ok = TaskModel::updateTitle((int)$id, $title, $_SESSION['user_id']);
        if (!$ok) {
            $this->json(['error' => true, 'msg' => 'לא נמצאה משימה או אין הרשאה'], 404);
            return;
        }

        $this->json(['error' => false, 'msg' => 'כותרת עודכנה']);
    }
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l src/Controllers/TaskController.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add src/Controllers/TaskController.php
git commit -m "feat: TaskController — updateStatus, updateTitle endpoints"
```

---

## Task 4: Register Routes

**Files:**
- Modify: `config/routes.php`

**Interfaces:**
- Produces: `POST /tasks/{id}/status`, `POST /tasks/{id}/title` routed to TaskController.

- [ ] **Step 1: Add two lines after the existing task routes**

Find this block in `config/routes.php`:
```php
$router->get ('/tasks',           'Controllers\TaskController@index');
$router->post('/tasks/create',    'Controllers\TaskController@create');
$router->post('/tasks/{id}/close','Controllers\TaskController@close');
```

Add after it:
```php
$router->post('/tasks/{id}/status', 'Controllers\\TaskController@updateStatus');
$router->post('/tasks/{id}/title',  'Controllers\\TaskController@updateTitle');
```

- [ ] **Step 2: Verify syntax**

```bash
php -l config/routes.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add config/routes.php
git commit -m "feat: routes — /tasks/{id}/status and /tasks/{id}/title"
```

---

## Task 5: Auto-Create Task on Invoice Change Name Request

**Files:**
- Modify: `src/Controllers/InvoiceChangeNameController.php`

**Interfaces:**
- Consumes: `TaskModel::typeByName(string): ?array`, `TaskModel::createFromSource(int, string, int, int, string): int`
- Produces: A task row in `tasks` linked to the new `invoice_change_name` row via `source_type='invoice_change_name'` and `source_id`.

- [ ] **Step 1: Add `use Models\TaskModel;` to the imports**

In `src/Controllers/InvoiceChangeNameController.php`, find:
```php
use Models\InvoiceChangeNameModel;
use Models\UserModel;
```

Replace with:
```php
use Models\InvoiceChangeNameModel;
use Models\TaskModel;
use Models\UserModel;
```

- [ ] **Step 2: Add task creation after ActivityLog::create call**

Find this block (around line 80):
```php
        $this->sendCreateMail($invoiceNum, $newName, $note, $customerName, $phone, $mail, $user);

        ActivityLog::create('invoice_change_name', $id, "חשבונית {$invoiceNum} → {$newName}");

        $this->json(['error' => false, 'msg' => 'בקשת שינוי שם נוספה בהצלחה', 'id' => $id]);
```

Replace with:
```php
        $this->sendCreateMail($invoiceNum, $newName, $note, $customerName, $phone, $mail, $user);

        ActivityLog::create('invoice_change_name', $id, "חשבונית {$invoiceNum} → {$newName}");

        $taskType = TaskModel::typeByName('שינוי שם בחשבונית');
        if ($taskType) {
            TaskModel::createFromSource(
                (int)$taskType['id'],
                'invoice_change_name',
                $id,
                (int)$user['id'],
                "שינוי שם בחש' {$invoiceNum} → {$newName}"
            );
        }

        $this->json(['error' => false, 'msg' => 'בקשת שינוי שם נוספה בהצלחה', 'id' => $id]);
```

- [ ] **Step 3: Verify syntax**

```bash
php -l src/Controllers/InvoiceChangeNameController.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add src/Controllers/InvoiceChangeNameController.php
git commit -m "feat: auto-create task when invoice-change-name request is submitted"
```

---

## Task 6: Updated Tasks View

**Files:**
- Modify: `views/pages/tasks/index.php`

**Interfaces:**
- Consumes:
  - `$tasks` array — each row has: `id`, `title`, `description`, `sla_days`, `created_at`, `status_name`, `status_color`, `type_name`, `task_type_id`, `status_id`, `source_type`, `source_id`, `opened_by_name`
  - `$statusesByType` — `array<int, array<{id, name, color}>>` indexed by `task_type_id`
- Produces: HTML page with status badge dropdown, inline title edit, source link.

- [ ] **Step 1: Replace views/pages/tasks/index.php**

```php
<?php
use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
// JSON-encode statusesByType for JS
$statusesJson = json_encode($statusesByType ?? [], JSON_UNESCAPED_UNICODE);
?>
<style>
.task-status-badge{
  display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;
  font-size:12px;font-weight:700;cursor:pointer;border:1px solid transparent;
  transition:filter .15s,transform .12s;user-select:none;
}
.task-status-badge:hover{filter:brightness(1.2);transform:scale(1.04);}
.status-dropdown{
  position:absolute;z-index:50;background:var(--bg2);border:1px solid var(--border2);
  border-radius:var(--radius);box-shadow:var(--shadow);min-width:130px;overflow:hidden;
}
.status-option{
  display:flex;align-items:center;gap:8px;padding:9px 14px;cursor:pointer;
  font-size:13px;font-weight:600;transition:background .12s;
}
.status-option:hover{background:var(--bg3);}
.task-title-cell{position:relative;}
.task-title-text{cursor:pointer;display:inline-block;border-radius:4px;padding:1px 4px;transition:background .13s;}
.task-title-text:hover{background:var(--bg3);}
.task-title-input{
  background:var(--bg3);border:1px solid var(--accent);border-radius:6px;
  color:var(--text);font-size:14px;font-weight:500;font-family:inherit;
  padding:3px 8px;outline:none;width:100%;
}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <div class="page-title" style="margin-bottom:0;">המשימות שלי</div>
  <button class="btn btn-primary" onclick="document.getElementById('new-task-modal').style.display='flex'">
    + משימה חדשה
  </button>
</div>

<?php if (empty($tasks)): ?>
  <div class="alert alert-info">אין משימות פתוחות 🎉</div>
<?php else: ?>
<div class="card" style="padding:0;overflow:visible;">
  <table style="width:100%;border-collapse:collapse;font-size:14px;">
    <thead>
      <tr style="color:var(--text2);">
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">#</th>
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">כותרת</th>
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">סטטוס</th>
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">סוג</th>
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">SLA</th>
        <th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">נפתח</th>
        <th style="padding:10px 14px;border-bottom:1px solid var(--border);"></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($tasks as $t):
      $created = $t['created_at'] ? date('d/m/Y', strtotime($t['created_at'])) : '—';
      $slaTs   = $t['created_at'] && $t['sla_days']
                 ? strtotime($t['created_at'] . ' +' . (int)$t['sla_days'] . ' days')
                 : 0;
      $slaDate = $slaTs ? date('d/m/Y', $slaTs) : '—';
      $overdue = $slaTs && $slaTs < time();
      $statusColor = $t['status_color'] ?? '#6b7280';
      $statusName  = $t['status_name']  ?? '—';
      $typeId      = (int)($t['task_type_id'] ?? 0);
      $statusId    = (int)($t['status_id']    ?? 0);
    ?>
    <tr style="border-bottom:1px solid var(--border);" id="task-row-<?= (int)$t['id'] ?>">
      <td style="padding:10px 14px;color:var(--text3);"><?= (int)$t['id'] ?></td>

      <!-- Title: double-click to edit -->
      <td style="padding:10px 14px;" class="task-title-cell">
        <div>
          <span class="task-title-text"
                id="title-text-<?= (int)$t['id'] ?>"
                title="לחץ פעמיים לעריכה"
                ondblclick="startTitleEdit(<?= (int)$t['id'] ?>, this)">
            <?= View::e($t['title'] ?? '') ?>
          </span>
        </div>
        <?php if (!empty($t['description'])): ?>
          <div style="font-size:12px;color:var(--text3);margin-top:2px;">
            <?= View::e(mb_substr($t['description'], 0, 70)) ?><?= mb_strlen($t['description']) > 70 ? '…' : '' ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($t['source_type']) && $t['source_type'] === 'invoice_change_name'): ?>
          <a href="<?= $base ?>/invoice-change-name"
             style="font-size:11px;color:var(--accent);text-decoration:none;margin-top:3px;display:inline-flex;align-items:center;gap:3px;">
            <i class="bi bi-box-arrow-up-left"></i> צפה בבקשה
          </a>
        <?php endif; ?>
      </td>

      <!-- Status badge with dropdown -->
      <td style="padding:10px 14px;position:relative;">
        <?php if ($typeId && $statusId): ?>
          <span class="task-status-badge"
                style="color:<?= View::e($statusColor) ?>;background:<?= View::e($statusColor) ?>22;border-color:<?= View::e($statusColor) ?>44;"
                onclick="toggleStatusDropdown(event, <?= (int)$t['id'] ?>, <?= $typeId ?>, <?= $statusId ?>)">
            <span style="width:7px;height:7px;border-radius:50%;background:<?= View::e($statusColor) ?>;flex-shrink:0;"></span>
            <span id="status-label-<?= (int)$t['id'] ?>"><?= View::e($statusName) ?></span>
          </span>
        <?php else: ?>
          <span style="color:var(--text3);font-size:13px;">—</span>
        <?php endif; ?>
      </td>

      <td style="padding:10px 14px;color:var(--text2);font-size:13px;">
        <?= View::e($t['type_name'] ?? '—') ?>
      </td>

      <td style="padding:10px 14px;">
        <?php if ($slaTs): ?>
          <span class="badge <?= $overdue ? 'badge-danger' : 'badge-success' ?>"><?= $slaDate ?></span>
        <?php else: ?>—<?php endif; ?>
      </td>

      <td style="padding:10px 14px;color:var(--text2);font-size:13px;"><?= $created ?></td>

      <td style="padding:10px 14px;">
        <form method="POST" action="<?= $base ?>/tasks/<?= (int)$t['id'] ?>/close"
              onsubmit="return confirm('לסגור משימה זו?')">
          <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
          <button type="submit" class="btn btn-ghost" style="padding:5px 10px;font-size:13px;">✓ סגור</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Status dropdown (shared, positioned absolutely) -->
<div id="status-dd" class="status-dropdown" style="display:none;"></div>

<!-- New task modal -->
<div id="new-task-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;align-items:center;justify-content:center;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:28px;width:100%;max-width:480px;">
    <button onclick="document.getElementById('new-task-modal').style.display='none'"
            style="float:left;background:none;border:none;color:var(--text2);font-size:20px;cursor:pointer;">✕</button>
    <div style="font-size:17px;font-weight:600;margin-bottom:20px;">משימה חדשה</div>
    <form method="POST" action="<?= $base ?>/tasks/create">
      <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">כותרת *</label>
        <input type="text" name="title" required
               style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;">
      </div>
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">תיאור</label>
        <textarea name="description" rows="3"
                  style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;resize:vertical;"></textarea>
      </div>
      <div style="margin-bottom:20px;">
        <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px;">SLA (ימים)</label>
        <input type="number" name="sla_days" value="3" min="1" max="30"
               style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-size:14px;font-family:inherit;outline:none;">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">צור משימה</button>
    </form>
  </div>
</div>

<script>
const TASK_CSRF   = <?= json_encode($csrf) ?>;
const TASK_BASE   = <?= json_encode($base) ?>;
const STATUSES_BY_TYPE = <?= $statusesJson ?>;

/* ── Status dropdown ──────────────────────────────────── */
let _ddOpenTaskId = null;

function toggleStatusDropdown(e, taskId, typeId, currentStatusId) {
  e.stopPropagation();
  const dd = document.getElementById('status-dd');
  if (_ddOpenTaskId === taskId) {
    dd.style.display = 'none';
    _ddOpenTaskId = null;
    return;
  }
  _ddOpenTaskId = taskId;
  const statuses = STATUSES_BY_TYPE[typeId] || [];
  let html = '';
  statuses.forEach(s => {
    const active = s.id == currentStatusId;
    html += `<div class="status-option" onclick="setStatus(${taskId},${s.id},'${escJs(s.name)}','${escJs(s.color)}')"
                  style="color:${s.color}${active?' font-weight:800;':''}">`
          + `<span style="width:8px;height:8px;border-radius:50%;background:${s.color};flex-shrink:0;"></span>`
          + `${esc(s.name)}`
          + (active ? ' <i class="bi bi-check2" style="margin-right:auto;"></i>' : '')
          + `</div>`;
  });
  dd.innerHTML = html;
  const badge = e.currentTarget;
  const rect  = badge.getBoundingClientRect();
  dd.style.top    = (rect.bottom + window.scrollY + 4) + 'px';
  dd.style.right  = (document.body.offsetWidth - rect.right) + 'px';
  dd.style.left   = 'auto';
  dd.style.display = 'block';
}

document.addEventListener('click', () => {
  document.getElementById('status-dd').style.display = 'none';
  _ddOpenTaskId = null;
});

async function setStatus(taskId, statusId, name, color) {
  document.getElementById('status-dd').style.display = 'none';
  _ddOpenTaskId = null;

  const fd = new FormData();
  fd.append('_csrf', TASK_CSRF);
  fd.append('status_id', statusId);

  const res = await fetch(`${TASK_BASE}/tasks/${taskId}/status`, {method:'POST', body:fd});
  const data = await res.json();
  if (data.error) { v2Toast('שגיאה: ' + data.msg); return; }

  // Update badge in-place
  const label = document.getElementById(`status-label-${taskId}`);
  if (label) {
    label.textContent = name;
    const badge = label.closest('.task-status-badge');
    if (badge) {
      badge.style.color = color;
      badge.style.background = color + '22';
      badge.style.borderColor = color + '44';
      badge.querySelector('span').style.background = color;
      badge.setAttribute('onclick',
        `toggleStatusDropdown(event,${taskId},${badge.getAttribute('onclick').match(/,(\d+),/)[1]},${statusId})`);
    }
  }
  v2Toast('סטטוס עודכן: ' + name);
}

/* ── Inline title edit ───────────────────────────────── */
function startTitleEdit(taskId, spanEl) {
  const current = spanEl.textContent.trim();
  const input = document.createElement('input');
  input.type  = 'text';
  input.value = current;
  input.className = 'task-title-input';
  spanEl.replaceWith(input);
  input.focus();
  input.select();

  const save = async () => {
    const val = input.value.trim();
    if (!val || val === current) {
      input.replaceWith(spanEl);
      return;
    }
    const fd = new FormData();
    fd.append('_csrf', TASK_CSRF);
    fd.append('title', val);
    const res  = await fetch(`${TASK_BASE}/tasks/${taskId}/title`, {method:'POST', body:fd});
    const data = await res.json();
    if (data.error) { v2Toast('שגיאה: ' + data.msg); input.replaceWith(spanEl); return; }
    spanEl.textContent = val;
    input.replaceWith(spanEl);
    v2Toast('כותרת עודכנה');
  };

  input.addEventListener('blur', save);
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter')  { e.preventDefault(); input.blur(); }
    if (e.key === 'Escape') { input.value = current; input.blur(); }
  });
}

/* ── Helpers ─────────────────────────────────────────── */
function esc(s){ const d=document.createElement('div');d.textContent=s;return d.innerHTML; }
function escJs(s){ return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
</script>
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l views/pages/tasks/index.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add views/pages/tasks/index.php
git commit -m "feat: tasks view — status badge dropdown, inline title edit, source link"
```

---

## Task 7: End-to-End Smoke Test

No automated test framework exists in this codebase. Manual verification steps:

- [ ] **Step 1: Open the app and log in as a representative with `canUseInvoiceChangeName` permission**

Navigate to `/invoice-change-name`.

- [ ] **Step 2: Submit a new invoice-change-name request**

Fill in all required fields (9-digit invoice number, new name, phone, email) and submit.

Expected: success toast "בקשת שינוי שם נוספה בהצלחה"

- [ ] **Step 3: Navigate to `/tasks`**

Expected: a new row appears with:
- Title like `שינוי שם בחש' 123456789 → שם חדש`
- Status badge "פתוח" (green)
- Type "שינוי שם בחשבונית"
- Link "צפה בבקשה"

- [ ] **Step 4: Click the status badge**

A dropdown should appear with: פתוח / בטיפול / ממתין / סגור

Select "בטיפול". The badge should update in-place to orange without page reload.

- [ ] **Step 5: Double-click the task title**

An input field appears. Change the title and press Enter.
Expected: title updates in-place, toast "כותרת עודכנה".

- [ ] **Step 6: Check that אייל גואטה also sees the task**

Log in as אייל גואטה and navigate to `/tasks`.
Expected: the task appears in his list (he was set as the assignee in `task_types.default_assignee_ids`).

- [ ] **Step 7: Check watchers**

In the DB:
```sql
SELECT tw.*, u.first_name, u.last_name
FROM task_watchers tw
JOIN users u ON u.id = tw.user_id
WHERE tw.task_id = <new_task_id>;
```

Expected: one row — the user who submitted the invoice-change-name request.

- [ ] **Step 8: Final commit tag**

```bash
git tag task-system-phase1
git log --oneline -8
```

Verify all 5 feature commits are present.
