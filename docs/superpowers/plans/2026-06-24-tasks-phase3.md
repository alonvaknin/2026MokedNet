# Tasks Phase 3 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add extended tasks view (closed/open + all-users toggles, new columns), status-change animations with confetti on close, and a per-task internal comment log.

**Architecture:** PHP 8 MVC on top of MySQL. Each feature layer builds on the previous: DB migration first, then model, then controller, then view. The comment system gets its own model file. All JS stays inline in the view file (no build step). Canvas-confetti loaded from CDN lazily.

**Tech Stack:** PHP 8, PDO/MySQL, vanilla JS, canvas-confetti CDN (v1.9.3), Bootstrap Icons (already loaded in layout).

## Global Constraints

- PHP 8.x, PDO, MySQL 5.7+ (no `ADD COLUMN IF NOT EXISTS`, check `information_schema` first)
- All controllers extend `Core\Controller`; use `$this->requireAuth()` / `$this->requirePermission(key)` / `$this->verifyCsrf()` / `$this->json()` / `$this->view()` / `$this->get()` / `$this->post()`
- Models use `Core\DB::query()`, `DB::row()`, `DB::execute()`, `DB::insert()`, `DB::value()`
- CSRF: every POST endpoint calls `$this->verifyCsrf()` — no exceptions
- Session user id: `$_SESSION['user_id']`; user array: `$_SESSION['user']`
- Auth::can() is NOT used directly in views — pass `$canViewAll` etc. from controller
- No framework CSS — inline styles using CSS variables: `var(--bg2)`, `var(--bg3)`, `var(--border)`, `var(--border2)`, `var(--text)`, `var(--text2)`, `var(--text3)`, `var(--accent)`, `var(--danger)`, `var(--radius)`, `var(--shadow)`
- Permission keys follow dot-notation: `tasks.viewAll`
- `is_active=0` means a task is closed; `is_active=1` means open
- No automated test suite exists — verify manually via browser + SQL queries
- Commit after every task
- Hebrew UI: all user-facing labels in Hebrew

---

## File Map

| File | Action | Purpose |
|---|---|---|
| `config/migration_tasks_phase3.php` | Create | DB migration: 3 new columns + task_comments table + permission |
| `src/Models/TaskModel.php` | Modify | Add `forQuery()`, update `updateStatus()` to track who changed |
| `src/Models/TaskCommentModel.php` | Create | CRUD for task_comments |
| `src/Controllers/TaskController.php` | Modify | Extended index(), getComments(), addComment() |
| `src/Models/UserModel.php` | Modify | Add `tasks.viewAll` to both PERM_CATEGORIES and PERM_LABELS |
| `config/routes.php` | Modify | Add GET/POST routes for comments |
| `views/pages/tasks/index.php` | Modify | Toggles, new columns, animations, confetti, comment drawer |

---

### Task 1: Database Migration

**Files:**
- Create: `config/migration_tasks_phase3.php`

**Interfaces:**
- Produces: columns `tasks.status_changed_by`, `tasks.status_changed_at` (already exists — verify), `tasks.assigned_dept_id`; table `task_comments`; permission `tasks.viewAll`

**Note:** `tasks.status_changed_at` already exists in the DB (added in Phase 2). The migration must check before adding it.

- [ ] **Step 1: Create the migration file**

Create `config/migration_tasks_phase3.php` with the following complete content:

```php
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

// 4. task_comments table
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

// 5. tasks.viewAll permission for admin groups
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
```

- [ ] **Step 2: Run the migration**

```bash
php config/migration_tasks_phase3.php
```

Expected output (all lines starting with `✓` or `–`, no `✗`):
```
– tasks.status_changed_by (כבר קיים)   ← or ✓ if new
– tasks.status_changed_at (כבר קיים)   ← expected, Phase 2 added it
– tasks.assigned_dept_id (כבר קיים)    ← or ✓ if new
✓ CREATE task_comments
✓ tasks.viewAll → group #N
מיגרציה הושלמה.
```

- [ ] **Step 3: Verify in MySQL**

```sql
SHOW COLUMNS FROM tasks LIKE 'status_changed_by';
SHOW COLUMNS FROM tasks LIKE 'assigned_dept_id';
SHOW CREATE TABLE task_comments\G
SELECT * FROM permission_group_grants WHERE permission_key='tasks.viewAll';
```

Each query must return at least one row.

- [ ] **Step 4: Commit**

```bash
git add config/migration_tasks_phase3.php
git commit -m "feat(tasks-p3): migration — status_changed_by, assigned_dept_id, task_comments, tasks.viewAll perm"
```

---

### Task 2: TaskModel — `forQuery()` + updated `updateStatus()`

**Files:**
- Modify: `src/Models/TaskModel.php`

**Interfaces:**
- Consumes: existing `Core\DB` methods; columns added in Task 1
- Produces:
  - `TaskModel::forQuery(int $userId, bool $closed, bool $allUsers, bool $overdueOnly): array` — each row includes all existing fields plus `status_changed_at`, `status_changed_by`, `changed_by_name`, `dept_name`, `assigned_dept_id`
  - `TaskModel::updateStatus(int $id, int $statusId, int $byUserId): bool` — also writes `status_changed_by` and sets `is_active=0` when status has `is_closed=1`

- [ ] **Step 1: Add `forQuery()` to TaskModel**

Open `src/Models/TaskModel.php`. Add this method after the existing `forUser()` method (keep `forUser()` in place — it is used by existing callers outside the tasks page):

```php
public static function forQuery(
    int  $userId,
    bool $closed      = false,
    bool $allUsers    = false,
    bool $overdueOnly = false
): array {
    $conditions = ['t.is_active = ?'];
    $params     = [$closed ? 0 : 1];

    if (!$allUsers) {
        $conditions[] = 't.assigned_user_id = ?';
        $params[]     = $userId;
    }

    if ($overdueOnly) {
        $conditions[] = 'DATE_ADD(t.created_at, INTERVAL t.sla_days DAY) < NOW()';
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    return DB::query(
        "SELECT t.id, t.title, t.description, t.sla_days,
                t.created_at, t.status_changed_at, t.is_active,
                t.source_type, t.source_id,
                t.status_id, t.task_type_id,
                t.assigned_user_id, t.assigned_dept_id,
                t.status_changed_by,
                CONCAT(opener.first_name,' ',opener.last_name) AS opened_by_name,
                CONCAT(changer.first_name,' ',changer.last_name) AS changed_by_name,
                ts.name    AS status_name,
                ts.color   AS status_color,
                ts.is_closed AS status_is_closed,
                tt.name    AS type_name,
                dept.name_heb AS dept_name
         FROM tasks t
         LEFT JOIN users opener    ON opener.id  = t.open_by
         LEFT JOIN users changer   ON changer.id = t.status_changed_by
         LEFT JOIN task_statuses ts ON ts.id     = t.status_id
         LEFT JOIN task_types    tt ON tt.id     = t.task_type_id
         LEFT JOIN departments   dept ON dept.id = t.assigned_dept_id
         {$where}
         ORDER BY t.created_at DESC",
        $params
    );
}
```

- [ ] **Step 2: Update `updateStatus()` to track who changed + handle is_closed**

Replace the existing `updateStatus()` method (lines ~137-144 in the original file):

```php
public static function updateStatus(int $id, int $statusId, int $byUserId): bool
{
    // Check if the target status is a closing status
    $isClosed = (int)DB::value(
        'SELECT is_closed FROM task_statuses WHERE id = ?',
        [$statusId]
    );

    return DB::execute(
        'UPDATE tasks
         SET status_id          = ?,
             status_changed_by  = ?,
             status_changed_at  = NOW(),
             is_active          = ?
         WHERE id = ? AND (assigned_user_id = ? OR open_by = ?)',
        [$statusId, $byUserId, $isClosed ? 0 : 1, $id, $byUserId, $byUserId]
    ) > 0;
}
```

- [ ] **Step 3: Update `statusesByType` query in `TaskController::index()` to include `is_closed`**

Open `src/Controllers/TaskController.php`. In the `index()` method, find the `statusesByType` query (around line 25):

```php
$rows = \Core\DB::query(
    'SELECT id, name, color FROM task_statuses WHERE task_type_id = ? ORDER BY sort_order',
    [$tid]
);
```

Replace with:

```php
$rows = \Core\DB::query(
    'SELECT id, name, color, is_closed FROM task_statuses WHERE task_type_id = ? ORDER BY sort_order',
    [$tid]
);
```

- [ ] **Step 4: Verify via SQL**

After the model changes, test the query manually in MySQL:

```sql
SELECT t.id, t.status_changed_by,
       CONCAT(changer.first_name,' ',changer.last_name) AS changed_by_name,
       dept.name_heb AS dept_name
FROM tasks t
LEFT JOIN users changer ON changer.id = t.status_changed_by
LEFT JOIN departments dept ON dept.id = t.assigned_dept_id
LIMIT 5;
```

Should return rows (with NULL values for the new columns on old rows — that is fine).

- [ ] **Step 5: Commit**

```bash
git add src/Models/TaskModel.php src/Controllers/TaskController.php
git commit -m "feat(tasks-p3): TaskModel::forQuery() + updateStatus tracks changed_by + is_closed"
```

---

### Task 3: Register `tasks.viewAll` permission in UserModel

**Files:**
- Modify: `src/Models/UserModel.php`

**Interfaces:**
- Produces: `tasks.viewAll` available as a toggleable permission in `/users/perm-groups`

- [ ] **Step 1: Add to `PERM_CATEGORIES`**

Open `src/Models/UserModel.php`. In the `PERM_CATEGORIES` array, find the `'ניהול מערכת'` block. Add `'tasks.viewAll'` right after `'task_settings.manage'`:

```php
'ניהול מערכת' => [
    'canAddUsers'          => 'הוספת משתמשים',
    'canEditDB'            => 'עריכת DB / Nav',
    'canAddAutomation'     => 'הוספת אוטומציה',
    'automation.viewAll'   => 'צפייה בכל האוטומציות (כל נציגים)',
    'canFormatter'         => 'עריכת פורמטור',
    'canOrianorder'        => 'הזמנות אוריאן',
    'canViewLogs'          => 'צפייה בלוג פעולות',
    'task_settings.manage' => 'ניהול הגדרות משימות',
    'tasks.viewAll'        => 'צפייה בכל המשימות (כל נציגים)',
],
```

- [ ] **Step 2: Add to `PERM_LABELS`**

In the same file, in the `PERM_LABELS` flat array, add after the `'task_settings.manage'` line:

```php
'task_settings.manage'         => 'ניהול הגדרות משימות',
'tasks.viewAll'                => 'צפייה בכל המשימות (כל נציגים)',
```

- [ ] **Step 3: Verify in browser**

Navigate to `/users/perm-groups`. Open any permission group editor. Confirm you see "צפייה בכל המשימות (כל נציגים)" under "ניהול מערכת".

- [ ] **Step 4: Commit**

```bash
git add src/Models/UserModel.php
git commit -m "feat(tasks-p3): add tasks.viewAll permission to UserModel"
```

---

### Task 4: TaskCommentModel — new file

**Files:**
- Create: `src/Models/TaskCommentModel.php`

**Interfaces:**
- Produces:
  - `TaskCommentModel::forTask(int $taskId): array` — returns rows ordered by `created_at ASC`, each with `id`, `body`, `created_at`, `user_name`
  - `TaskCommentModel::add(int $taskId, int $userId, string $body): array` — inserts row, returns the saved row (same shape as `forTask` items)
  - `TaskCommentModel::taskExists(int $taskId, int $userId, bool $canViewAll): bool` — auth check

- [ ] **Step 1: Create the file**

Create `src/Models/TaskCommentModel.php`:

```php
<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class TaskCommentModel
{
    public static function forTask(int $taskId): array
    {
        return DB::query(
            "SELECT tc.id, tc.body, tc.created_at,
                    CONCAT(u.first_name,' ',u.last_name) AS user_name
             FROM task_comments tc
             JOIN users u ON u.id = tc.user_id
             WHERE tc.task_id = ?
             ORDER BY tc.created_at ASC",
            [$taskId]
        );
    }

    public static function add(int $taskId, int $userId, string $body): array
    {
        $id = DB::insert(
            'INSERT INTO task_comments (task_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())',
            [$taskId, $userId, $body]
        );

        return DB::row(
            "SELECT tc.id, tc.body, tc.created_at,
                    CONCAT(u.first_name,' ',u.last_name) AS user_name
             FROM task_comments tc
             JOIN users u ON u.id = tc.user_id
             WHERE tc.id = ?",
            [$id]
        ) ?? ['id' => $id, 'body' => $body, 'created_at' => date('Y-m-d H:i:s'), 'user_name' => ''];
    }

    /**
     * Returns true if the given user may read/write comments on this task.
     * Allowed when: user is assigned, OR user opened the task, OR user has tasks.viewAll.
     */
    public static function canAccess(int $taskId, int $userId, bool $canViewAll): bool
    {
        if ($canViewAll) {
            return (bool)DB::value('SELECT 1 FROM tasks WHERE id = ? AND id > 0', [$taskId]);
        }

        return (bool)DB::value(
            'SELECT 1 FROM tasks WHERE id = ? AND (assigned_user_id = ? OR open_by = ?)',
            [$taskId, $userId, $userId]
        );
    }
}
```

- [ ] **Step 2: Verify the file loads without PHP errors**

```bash
php -l src/Models/TaskCommentModel.php
```

Expected: `No syntax errors detected in src/Models/TaskCommentModel.php`

- [ ] **Step 3: Commit**

```bash
git add src/Models/TaskCommentModel.php
git commit -m "feat(tasks-p3): TaskCommentModel — forTask(), add(), canAccess()"
```

---

### Task 5: Routes + TaskController — index, getComments, addComment

**Files:**
- Modify: `config/routes.php`
- Modify: `src/Controllers/TaskController.php`

**Interfaces:**
- Consumes: `TaskModel::forQuery()` (Task 2), `TaskCommentModel` (Task 4), permission `tasks.viewAll` (Task 3)
- Produces:
  - `GET /tasks` — accepts `?show=closed&scope=all` query params, passes `$showClosed`, `$scopeAll`, `$canViewAll` to view
  - `GET /tasks/{id}/comments` → JSON array of comment objects
  - `POST /tasks/{id}/comments` → JSON `{ok:true, comment:{...}}`

- [ ] **Step 1: Add comment routes to `config/routes.php`**

Open `config/routes.php`. Find the existing task routes block (around line 25-29). Add two lines after the existing task routes:

```php
$router->get ('/tasks',           'Controllers\TaskController@index');
$router->post('/tasks/create',    'Controllers\TaskController@create');
$router->post('/tasks/{id}/close','Controllers\TaskController@close');
$router->post('/tasks/{id}/status', 'Controllers\\TaskController@updateStatus');
$router->post('/tasks/{id}/title',  'Controllers\\TaskController@updateTitle');
$router->get ('/tasks/{id}/comments',  'Controllers\\TaskController@getComments');
$router->post('/tasks/{id}/comments',  'Controllers\\TaskController@addComment');
```

- [ ] **Step 2: Update `TaskController::index()`**

Replace the entire `index()` method in `src/Controllers/TaskController.php`:

```php
public function index(): void
{
    $this->requireAuth();
    $userId     = $_SESSION['user_id'];
    $canViewAll = \Core\Auth::can('tasks.viewAll');

    $show  = $this->get('show', 'open');   // 'open' | 'closed'
    $scope = $this->get('scope', 'mine');  // 'mine' | 'all'
    $filter = $this->get('filter', '');    // 'overdue' (legacy)

    $showClosed  = ($show === 'closed');
    $scopeAll    = ($scope === 'all') && $canViewAll;
    $overdueOnly = ($filter === 'overdue');

    $tasks = TaskModel::forQuery($userId, $showClosed, $scopeAll, $overdueOnly);

    // Build status lists indexed by task_type_id for the JS dropdown
    $statusesByType = [];
    foreach ($tasks as $t) {
        $tid = (int)($t['task_type_id'] ?? 0);
        if ($tid && !isset($statusesByType[$tid])) {
            $rows = \Core\DB::query(
                'SELECT id, name, color, is_closed FROM task_statuses WHERE task_type_id = ? ORDER BY sort_order',
                [$tid]
            );
            $statusesByType[$tid] = $rows;
        }
    }

    $this->view('pages/tasks/index', compact(
        'tasks', 'statusesByType', 'filter',
        'showClosed', 'scopeAll', 'canViewAll'
    ));
}
```

- [ ] **Step 3: Add `getComments()` to TaskController**

Add this method after `updateTitle()`:

```php
public function getComments(string $id): void
{
    $this->requireAuth();
    $taskId  = (int)$id;
    $userId  = $_SESSION['user_id'];
    $canAll  = \Core\Auth::can('tasks.viewAll');

    if (!TaskCommentModel::canAccess($taskId, $userId, $canAll)) {
        $this->json(['error' => true, 'msg' => 'אין הרשאה'], 403);
        return;
    }

    $this->json(\Models\TaskCommentModel::forTask($taskId));
}
```

- [ ] **Step 4: Add `addComment()` to TaskController**

Add this method after `getComments()`:

```php
public function addComment(string $id): void
{
    $this->requireAuth();
    $this->verifyCsrf();

    $taskId = (int)$id;
    $userId = $_SESSION['user_id'];
    $canAll = \Core\Auth::can('tasks.viewAll');

    if (!TaskCommentModel::canAccess($taskId, $userId, $canAll)) {
        $this->json(['error' => true, 'msg' => 'אין הרשאה'], 403);
        return;
    }

    $body = trim($this->post('body', ''));
    if ($body === '') {
        $this->json(['error' => true, 'msg' => 'תוכן לא יכול להיות ריק'], 422);
        return;
    }
    if (mb_strlen($body) > 2000) {
        $this->json(['error' => true, 'msg' => 'תוכן ארוך מדי (מקס 2000 תווים)'], 422);
        return;
    }

    $comment = \Models\TaskCommentModel::add($taskId, $userId, $body);
    $this->json(['ok' => true, 'comment' => $comment]);
}
```

- [ ] **Step 5: Add `use Models\TaskCommentModel;` import at the top of TaskController**

At the top of `src/Controllers/TaskController.php`, add `TaskCommentModel` to the use block:

```php
use Models\TaskModel;
use Models\TaskCommentModel;
```

- [ ] **Step 6: Verify PHP syntax**

```bash
php -l src/Controllers/TaskController.php
php -l config/routes.php
```

Both should output: `No syntax errors detected`

- [ ] **Step 7: Smoke-test in browser**

Navigate to `/tasks` — page must load. In the browser console run:

```js
fetch('/tasks/1/comments').then(r=>r.json()).then(console.log)
```

Should return an array (possibly empty) with no PHP errors.

- [ ] **Step 8: Commit**

```bash
git add config/routes.php src/Controllers/TaskController.php
git commit -m "feat(tasks-p3): TaskController — extended index, getComments, addComment"
```

---

### Task 6: View — Toggles + New Columns (3a)

**Files:**
- Modify: `views/pages/tasks/index.php`

**Interfaces:**
- Consumes: `$showClosed`, `$scopeAll`, `$canViewAll` (passed from controller in Task 5); new task row fields `status_changed_at`, `changed_by_name`, `dept_name`

- [ ] **Step 1: Replace the page header section**

In `views/pages/tasks/index.php`, replace the existing header `<div>` (lines 46-51 in original):

```php
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
  <div class="page-title" style="margin-bottom:0;">
    <?= $showClosed ? 'משימות סגורות' : 'משימות פתוחות' ?>
    <?= $scopeAll ? '— כולם' : '— שלי' ?>
  </div>

  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <!-- Open/Closed toggle -->
    <div style="display:inline-flex;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;font-size:13px;font-weight:600;">
      <a href="?show=open&scope=<?= $scopeAll ? 'all' : 'mine' ?>"
         style="padding:6px 14px;text-decoration:none;<?= !$showClosed ? 'background:var(--accent);color:#fff;' : 'color:var(--text2);' ?>">
        פתוחות
      </a>
      <a href="?show=closed&scope=<?= $scopeAll ? 'all' : 'mine' ?>"
         style="padding:6px 14px;text-decoration:none;<?= $showClosed ? 'background:var(--accent);color:#fff;' : 'color:var(--text2);' ?>">
        סגורות
      </a>
    </div>

    <?php if ($canViewAll): ?>
    <!-- Mine/All toggle -->
    <div style="display:inline-flex;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;font-size:13px;font-weight:600;">
      <a href="?show=<?= $showClosed ? 'closed' : 'open' ?>&scope=mine"
         style="padding:6px 14px;text-decoration:none;<?= !$scopeAll ? 'background:var(--accent);color:#fff;' : 'color:var(--text2);' ?>">
        שלי
      </a>
      <a href="?show=<?= $showClosed ? 'closed' : 'open' ?>&scope=all"
         style="padding:6px 14px;text-decoration:none;<?= $scopeAll ? 'background:var(--accent);color:#fff;' : 'color:var(--text2);' ?>">
        הכל
      </a>
    </div>
    <?php endif; ?>

    <?php if (!$showClosed): ?>
    <button class="btn btn-primary" onclick="document.getElementById('new-task-modal').style.display='flex'">
      + משימה חדשה
    </button>
    <?php endif; ?>
  </div>
</div>
```

- [ ] **Step 2: Update the empty state message**

Replace the existing empty state (around line 53-54):

```php
<?php if (empty($tasks)): ?>
  <div class="alert alert-info">
    <?= $showClosed ? 'אין משימות סגורות' : 'אין משימות פתוחות 🎉' ?>
  </div>
```

- [ ] **Step 3: Add three new table header columns**

In the `<thead>` section, after the existing "נפתח" `<th>` and before the empty last `<th>`, add:

```html
<th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">עדכון סטטוס</th>
<th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">עודכן ע"י</th>
<th style="text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);font-weight:500;">מחלקה</th>
```

- [ ] **Step 4: Add three new `<td>` cells in each task row**

In the `foreach` loop, after the existing `<td style="padding:10px 14px;color:var(--text2);font-size:13px;"><?= $created ?></td>` and before the last empty `<td></td>`, add:

```php
      <td style="padding:10px 14px;color:var(--text2);font-size:12px;white-space:nowrap;">
        <?= $t['status_changed_at'] ? date('d/m/Y H:i', strtotime($t['status_changed_at'])) : '—' ?>
      </td>
      <td style="padding:10px 14px;color:var(--text2);font-size:13px;">
        <?= \Core\View::e($t['changed_by_name'] ?? '—') ?>
      </td>
      <td style="padding:10px 14px;color:var(--text2);font-size:13px;">
        <?= \Core\View::e($t['dept_name'] ?? '—') ?>
      </td>
```

- [ ] **Step 5: Update the last `<td>` (action column) to be a comment button placeholder**

Replace the last `<td></td>` in the row with:

```php
      <td style="padding:10px 14px;text-align:center;">
        <button class="btn-icon" onclick="openComments(<?= (int)$t['id'] ?>, <?= \Core\View::e(json_encode($t['title'])) ?>)"
                title="הערות פנימיות"
                style="background:none;border:none;color:var(--text3);cursor:pointer;font-size:16px;padding:4px 8px;border-radius:6px;transition:color .15s,background .15s;">
          <i class="bi bi-chat-dots"></i>
        </button>
      </td>
```

- [ ] **Step 6: Verify the page loads correctly in browser**

Navigate to `/tasks`. Confirm:
- Toggle buttons appear (פתוחות/סגורות and, if admin, שלי/הכל)
- Three new columns visible (even if showing `—` on old rows)
- Comment icon visible in each row
- Switching show/scope updates the URL and reloads the correct data

- [ ] **Step 7: Commit**

```bash
git add views/pages/tasks/index.php
git commit -m "feat(tasks-p3): tasks view — open/closed toggle, all-users toggle, new columns"
```

---

### Task 7: View — Status-Change Animations + Confetti (3b)

**Files:**
- Modify: `views/pages/tasks/index.php`

**Interfaces:**
- Consumes: `is_closed` field in `STATUSES_BY_TYPE` JSON (available after Task 2 + Task 5)

- [ ] **Step 1: Add animation CSS to the `<style>` block**

In the `<style>` block at the top of the file, add:

```css
@keyframes badge-flip {
  0%   { transform: scaleY(1);   opacity:1; }
  40%  { transform: scaleY(0);   opacity:0; }
  100% { transform: scaleY(1);   opacity:1; }
}
.badge-flip { animation: badge-flip 0.22s ease; }
```

- [ ] **Step 2: Update `setStatus()` JS to add animation and confetti trigger**

In the `<script>` block, replace the entire `setStatus()` function:

```js
async function setStatus(taskId, statusId, name, color, isClosed) {
  document.getElementById('status-dd').style.display = 'none';
  _ddOpenTaskId = null;

  const fd = new FormData();
  fd.append('_csrf', TASK_CSRF);
  fd.append('status_id', statusId);

  const res  = await fetch(`${TASK_BASE}/tasks/${taskId}/status`, {method:'POST', body:fd});
  const data = await res.json();
  if (data.error) { v2Toast('שגיאה: ' + data.msg); return; }

  // Update badge in-place with flip animation
  const label = document.getElementById(`status-label-${taskId}`);
  if (label) {
    const badge = label.closest('.task-status-badge');
    if (badge) {
      badge.classList.remove('badge-flip');
      void badge.offsetWidth; // force reflow to restart animation
      badge.classList.add('badge-flip');
      badge.addEventListener('animationend', () => badge.classList.remove('badge-flip'), { once: true });

      const safeColor = sanitizeColor(color);
      badge.style.color       = safeColor;
      badge.style.background  = safeColor + '22';
      badge.style.borderColor = safeColor + '44';
      badge.querySelector('span').style.background = safeColor;
      badge.dataset.currentStatus = statusId;
      label.textContent = name;

      if (isClosed) {
        loadConfettiAndFire(badge);
      }
    }
  }
  v2Toast('סטטוס עודכן: ' + name);
}

function loadConfettiAndFire(originEl) {
  if (window.confetti) {
    fireConfetti(originEl);
    return;
  }
  const s = document.createElement('script');
  s.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js';
  s.onload = () => fireConfetti(originEl);
  document.head.appendChild(s);
}

function fireConfetti(originEl) {
  const rect = originEl.getBoundingClientRect();
  const x = (rect.left + rect.width / 2) / window.innerWidth;
  const y = (rect.top  + rect.height / 2) / window.innerHeight;
  confetti({ particleCount: 100, spread: 80, origin: { x, y }, zIndex: 9999 });
}
```

- [ ] **Step 3: Update the dropdown rendering to pass `isClosed` and include `data-is-closed`**

In `toggleStatusDropdown()`, replace the statuses `forEach` loop:

```js
statuses.forEach(s => {
  const active     = s.id == currentStatusId;
  const safeColor  = sanitizeColor(s.color);
  const isClosed   = s.is_closed == 1;
  html += `<div class="status-option"
                onclick="setStatus(${taskId},${s.id},'${escJs(s.name)}','${escJs(s.color)}',${isClosed ? 'true' : 'false'})"
                style="color:${safeColor}${active?' font-weight:800;':''}">`
        + `<span style="width:8px;height:8px;border-radius:50%;background:${safeColor};flex-shrink:0;"></span>`
        + `${esc(s.name)}`
        + (active ? ' <i class="bi bi-check2" style="margin-right:auto;"></i>' : '')
        + `</div>`;
});
```

- [ ] **Step 4: Verify animations in browser**

Open `/tasks`, change a task's status:
1. The badge should briefly flip (scaleY animation)
2. Change to a status with `is_closed=1` — confetti should burst from the badge

- [ ] **Step 5: Commit**

```bash
git add views/pages/tasks/index.php
git commit -m "feat(tasks-p3): status-change flip animation + confetti on close"
```

---

### Task 8: View — Comment Drawer (3c)

**Files:**
- Modify: `views/pages/tasks/index.php`

**Interfaces:**
- Consumes: `GET /tasks/{id}/comments`, `POST /tasks/{id}/comments` (Task 5)

- [ ] **Step 1: Add the comment drawer HTML**

Just before the closing `</div>` that wraps the new task modal (before the `<script>` block), add:

```html
<!-- Comment Drawer -->
<div id="comment-drawer"
     style="position:fixed;top:0;right:-400px;width:370px;height:100vh;
            background:var(--bg2);border-left:1px solid var(--border2);
            box-shadow:var(--shadow);z-index:400;
            transition:right .25s ease;
            display:flex;flex-direction:column;padding:0;">

  <!-- Header -->
  <div style="padding:16px 18px;border-bottom:1px solid var(--border);
              display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
    <div id="comment-drawer-title" style="font-size:15px;font-weight:700;color:var(--text);
         max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>
    <button onclick="closeCommentDrawer()"
            style="background:none;border:none;color:var(--text3);font-size:20px;cursor:pointer;line-height:1;">✕</button>
  </div>

  <!-- Comment list (scrollable) -->
  <div id="comment-list"
       style="flex:1;overflow-y:auto;padding:14px 18px;display:flex;flex-direction:column;gap:10px;">
    <div id="comment-loading" style="color:var(--text3);font-size:13px;text-align:center;padding:20px;">טוען...</div>
  </div>

  <!-- Input area -->
  <div style="padding:14px 18px;border-top:1px solid var(--border);flex-shrink:0;">
    <textarea id="comment-body" rows="3" placeholder="כתוב עדכון פנימי..."
              style="width:100%;background:var(--bg3);border:1px solid var(--border);
                     border-radius:var(--radius);color:var(--text);font-size:13px;
                     font-family:inherit;padding:9px 12px;outline:none;
                     resize:vertical;box-sizing:border-box;"></textarea>
    <button onclick="submitComment()" class="btn btn-primary"
            style="width:100%;margin-top:8px;">שלח עדכון</button>
  </div>
</div>
<div id="comment-overlay"
     onclick="closeCommentDrawer()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:399;"></div>
```

- [ ] **Step 2: Add comment JS to the `<script>` block**

At the end of the existing `<script>` block (before the closing `</script>` tag), add:

```js
/* ── Comment Drawer ──────────────────────────────────── */
let _commentTaskId = null;

function openComments(taskId, taskTitle) {
  _commentTaskId = taskId;
  document.getElementById('comment-drawer-title').textContent = taskTitle;
  document.getElementById('comment-list').innerHTML =
    '<div style="color:var(--text3);font-size:13px;text-align:center;padding:20px;">טוען...</div>';
  document.getElementById('comment-body').value = '';

  // Slide open
  document.getElementById('comment-drawer').style.right  = '0';
  document.getElementById('comment-overlay').style.display = 'block';

  // Load comments
  fetch(`${TASK_BASE}/tasks/${taskId}/comments`)
    .then(r => r.json())
    .then(data => {
      if (data.error) {
        document.getElementById('comment-list').innerHTML =
          `<div style="color:var(--danger);font-size:13px;">${esc(data.msg)}</div>`;
        return;
      }
      renderComments(data);
    })
    .catch(() => {
      document.getElementById('comment-list').innerHTML =
        '<div style="color:var(--danger);font-size:13px;">שגיאה בטעינת הערות</div>';
    });
}

function renderComments(list) {
  const container = document.getElementById('comment-list');
  if (!list.length) {
    container.innerHTML = '<div style="color:var(--text3);font-size:13px;text-align:center;padding:20px;">אין הערות עדיין</div>';
    return;
  }
  container.innerHTML = list.map(c => {
    const dt = c.created_at ? c.created_at.slice(0,16).replace('T',' ') : '';
    return `<div style="background:var(--bg3);border-radius:8px;padding:10px 12px;">
      <div style="font-size:11px;color:var(--text3);margin-bottom:5px;">
        <i class="bi bi-person-fill"></i> ${esc(c.user_name)} &nbsp;·&nbsp; ${esc(dt)}
      </div>
      <div style="font-size:13px;color:var(--text);white-space:pre-wrap;">${esc(c.body)}</div>
    </div>`;
  }).join('');
  // scroll to bottom
  container.scrollTop = container.scrollHeight;
}

async function submitComment() {
  if (!_commentTaskId) return;
  const body = document.getElementById('comment-body').value.trim();
  if (!body) { v2Toast('כתוב משהו תחילה'); return; }
  if (body.length > 2000) { v2Toast('הערה ארוכה מדי (מקס 2000 תווים)'); return; }

  const fd = new FormData();
  fd.append('_csrf', TASK_CSRF);
  fd.append('body', body);

  const res  = await fetch(`${TASK_BASE}/tasks/${_commentTaskId}/comments`, {method:'POST', body:fd});
  const data = await res.json();
  if (data.error || !data.ok) { v2Toast('שגיאה: ' + (data.msg || 'לא ידוע')); return; }

  document.getElementById('comment-body').value = '';

  // Append new comment to list
  const container = document.getElementById('comment-list');
  const emptyMsg  = container.querySelector('div[style*="text-align:center"]');
  if (emptyMsg) emptyMsg.remove();

  const c   = data.comment;
  const dt  = (c.created_at || '').slice(0,16).replace('T',' ');
  const div = document.createElement('div');
  div.style.cssText = 'background:var(--bg3);border-radius:8px;padding:10px 12px;';
  div.innerHTML = `<div style="font-size:11px;color:var(--text3);margin-bottom:5px;">
      <i class="bi bi-person-fill"></i> ${esc(c.user_name)} &nbsp;·&nbsp; ${esc(dt)}
    </div>
    <div style="font-size:13px;color:var(--text);white-space:pre-wrap;">${esc(c.body)}</div>`;
  container.appendChild(div);
  container.scrollTop = container.scrollHeight;
  v2Toast('הערה נשמרה');
}

function closeCommentDrawer() {
  document.getElementById('comment-drawer').style.right  = '-400px';
  document.getElementById('comment-overlay').style.display = 'none';
  _commentTaskId = null;
}
```

- [ ] **Step 3: Verify comment drawer in browser**

1. Open `/tasks`
2. Click the chat icon on any row — drawer should slide in from the right
3. Drawer shows "טוען..." then either comments or "אין הערות עדיין"
4. Type a comment and click "שלח עדכון" — comment appears in the drawer immediately
5. Click ✕ or the overlay — drawer slides closed
6. Reopen the same task — the posted comment appears

- [ ] **Step 4: Commit**

```bash
git add views/pages/tasks/index.php
git commit -m "feat(tasks-p3): comment drawer — slide-in panel, load/submit comments"
```

---

### Task 9: `assigned_dept_id` auto-fill on task create

**Files:**
- Modify: `src/Controllers/TaskController.php`

**Interfaces:**
- Consumes: `tasks.assigned_dept_id` column (Task 1); `users.department_id` (existing)

- [ ] **Step 1: Update `TaskController::create()`**

Replace the existing `create()` method:

```php
public function create(): void
{
    $this->requireAuth();
    $this->verifyCsrf();

    $assignedUserId = (int)$this->post('for_user', $_SESSION['user_id']);
    $openBy         = $_SESSION['user_id'];

    // Resolve department: assigned user's dept, else opener's dept
    $assignedDept = \Core\DB::value(
        'SELECT department_id FROM users WHERE id = ?',
        [$assignedUserId]
    );
    if (!$assignedDept) {
        $assignedDept = \Core\DB::value(
            'SELECT department_id FROM users WHERE id = ?',
            [$openBy]
        );
    }

    $newId = TaskModel::create([
        'open_by'          => $openBy,
        'assigned_user_id' => $assignedUserId,
        'title'            => trim($this->post('title', '')),
        'description'      => trim($this->post('description', '')),
        'sla_days'         => (int)$this->post('sla_days', 3),
        'assigned_dept_id' => $assignedDept ?: null,
    ]);

    ActivityLog::create('task', $newId, trim($this->post('title', '')));
    $this->redirect('/tasks');
}
```

- [ ] **Step 2: Verify in browser**

Create a new task. Then in MySQL:

```sql
SELECT id, title, assigned_dept_id FROM tasks ORDER BY id DESC LIMIT 1;
```

`assigned_dept_id` should be a non-NULL integer matching the assigned user's department.

- [ ] **Step 3: Commit**

```bash
git add src/Controllers/TaskController.php
git commit -m "feat(tasks-p3): auto-fill assigned_dept_id on task create"
```

---

## Self-Review Checklist

After writing the plan, verifying against the spec:

| Spec requirement | Task that covers it |
|---|---|
| DB: `status_changed_by`, `status_changed_at`, `assigned_dept_id` columns | Task 1 |
| DB: `task_comments` table | Task 1 |
| `tasks.viewAll` permission | Task 1 (migration) + Task 3 (UserModel) |
| `TaskModel::forQuery()` with all params | Task 2 |
| `updateStatus()` writes `status_changed_by` + handles `is_closed` | Task 2 |
| `statusesByType` includes `is_closed` field | Task 2 step 3, Task 5 step 2 |
| `TaskCommentModel` with canAccess auth | Task 4 |
| Routes for comments | Task 5 |
| Controller: index() extended params | Task 5 |
| Controller: getComments(), addComment() | Task 5 |
| View: toggles (open/closed, mine/all) | Task 6 |
| View: new columns (status_changed_at, changed_by_name, dept_name) | Task 6 |
| View: comment icon per row | Task 6 |
| Status badge flip animation | Task 7 |
| Confetti on is_closed status | Task 7 |
| Comment drawer HTML + JS (open/close/load/submit) | Task 8 |
| `assigned_dept_id` auto-fill on create | Task 9 |
