# Task System Phase 2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** הוספת ממשק ניהול הגדרות משימות (task types + statuses), SLA cron שעתי עם שליחת מייל, ובadge SLA גלובלי לחיץ בכל עמודי המערכת.

**Architecture:** `TaskSettingsController` מוגן ב-`requirePermission('task_settings.manage')` מטפל ב-CRUD של task_types וstatuses דרך AJAX. הbadge נטען inline ב-layout הראשי עם query קל. SLA cron משולב לתוך `cron_1hr.php` הקיים.

**Tech Stack:** PHP 8.x, PDO/MySQL 5.7+, Vanilla JS (אין frameworks), Bootstrap Icons (קיים), `mail()` PHP לשליחת מיילים (אותו pattern כמו cron_1hr.php).

## Global Constraints

- MySQL 5.7 compat — אין JSON DEFAULT, אין ADD COLUMN IF NOT EXISTS
- CSRF token נדרש בכל POST — `$this->verifyCsrf()` + `_csrf` בform/fetch
- כל controller מתחיל ב-`$this->requireAuth()` — כבר מבוצע ע"י `requirePermission()`
- צבעי CSS דרך CSS variables: `var(--bg2)`, `var(--accent)`, `var(--danger)` וכו'
- RTL, עברית, `font-family: var(--font)` (Assistant)
- `Auth::can('task_settings.manage')` — permission key מדויק לכל בדיקת הרשאה
- `bodyWrap()` ו-`buildHeaders()` קיימות ב-`cron_1hr.php` — אין להגדיר מחדש, להזיז לפונקציות גלובליות בcron bootstrap או לשכפל ב-function scope

---

## File Map

| קובץ | פעולה | תוכן |
|------|--------|-------|
| `config/migration_task_settings_perm.php` | חדש | INSERT permission + ADD COLUMN sla_notified_at |
| `config/routes.php` | עדכון | 7 routes חדשים לadmin/task-settings + api/users/active |
| `src/Controllers/TaskSettingsController.php` | חדש | CRUD task_types + statuses |
| `src/Controllers/UserController.php` | עדכון | הוספת `apiActiveList()` |
| `src/Controllers/TaskController.php` | עדכון | תמיכה ב-`?filter=overdue` |
| `views/pages/admin/task-settings.php` | חדש | UI עם 2 tabs |
| `views/pages/tasks/index.php` | עדכון | הדגשת שורות overdue + filter |
| `views/layouts/main.php` | עדכון | badge SLA גלובלי |
| `cron/cron_1hr.php` | עדכון | הוספת `notifyOverdueTasks()` |

---

## Task 1: Migration — permission + sla_notified_at

**Files:**
- Create: `config/migration_task_settings_perm.php`

**Interfaces:**
- Produces: שדה `tasks.sla_notified_at DATETIME NULL`, רשומת permission `task_settings.manage` בDB

- [ ] **Step 1: צור את קובץ ה-migration**

```php
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
```

- [ ] **Step 2: הרץ את ה-migration**

```bash
php config/migration_task_settings_perm.php
```

פלט צפוי:
```
✓ ALTER tasks: add sla_notified_at
✓ permission task_settings.manage → group #1
מיגרציה הושלמה.
```

- [ ] **Step 3: אמת ב-DB**

```sql
-- בדוק שהעמודה קיימת:
SHOW COLUMNS FROM tasks LIKE 'sla_notified_at';
-- בדוק permission:
SELECT * FROM permission_group_grants WHERE permission_key = 'task_settings.manage';
```

- [ ] **Step 4: Commit**

```bash
git add config/migration_task_settings_perm.php
git commit -m "feat: migration — sla_notified_at column + task_settings.manage permission"
```

---

## Task 2: Routes + UserController::apiActiveList()

**Files:**
- Modify: `config/routes.php`
- Modify: `src/Controllers/UserController.php`

**Interfaces:**
- Produces:
  - `GET /api/users/active` → `[{id, name}, ...]` JSON
  - `GET /admin/task-settings` → view
  - `POST /admin/task-settings/types` → JSON
  - `POST /admin/task-settings/types/{id}` → JSON
  - `POST /admin/task-settings/types/{id}/delete` → JSON
  - `POST /admin/task-settings/statuses` → JSON
  - `POST /admin/task-settings/statuses/{id}` → JSON
  - `POST /admin/task-settings/statuses/{id}/delete` → JSON

- [ ] **Step 1: הוסף routes ב-`config/routes.php`**

הוסף לאחר בלוק tasks הקיים (שורה ~29):

```php
// Task Settings (Admin)
$router->get ('/admin/task-settings',                        'Controllers\\TaskSettingsController@index');
$router->post('/admin/task-settings/types',                  'Controllers\\TaskSettingsController@createType');
$router->post('/admin/task-settings/types/{id}',             'Controllers\\TaskSettingsController@updateType');
$router->post('/admin/task-settings/types/{id}/delete',      'Controllers\\TaskSettingsController@deleteType');
$router->post('/admin/task-settings/statuses',               'Controllers\\TaskSettingsController@createStatus');
$router->post('/admin/task-settings/statuses/{id}',          'Controllers\\TaskSettingsController@updateStatus');
$router->post('/admin/task-settings/statuses/{id}/delete',   'Controllers\\TaskSettingsController@deleteStatus');

// Active users API (used by task-settings assignee picker)
$router->get('/api/users/active', 'Controllers\\UserController@apiActiveList');
```

- [ ] **Step 2: הוסף `apiActiveList()` ל-`src/Controllers/UserController.php`**

הוסף לפני הסוגר הסוגר `}` האחרון של המחלקה (אחרי `apiSearch()`):

```php
public function apiActiveList(): void
{
    $this->requireAuth();
    $users = \Core\DB::query(
        'SELECT id, CONCAT(first_name," ",last_name) AS name
         FROM users WHERE is_active=1 ORDER BY first_name, last_name'
    );
    $this->json($users);
}
```

- [ ] **Step 3: בדוק שה-route עובד**

בדפדפן (כשמחובר): נווט ל-`/api/users/active` — צפוי: JSON array עם id ו-name.

- [ ] **Step 4: Commit**

```bash
git add config/routes.php src/Controllers/UserController.php
git commit -m "feat: routes + api/users/active for task-settings assignee picker"
```

---

## Task 3: TaskSettingsController

**Files:**
- Create: `src/Controllers/TaskSettingsController.php`

**Interfaces:**
- Consumes: `requirePermission('task_settings.manage')` מ-`Core\Controller`, `Core\DB`, `Core\Auth`
- Produces: כל endpoints של `/admin/task-settings/*`

- [ ] **Step 1: צור את הController**

```php
<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\DB;

class TaskSettingsController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('task_settings.manage');

        $types = DB::query('SELECT * FROM task_types ORDER BY id');

        $statusesByType = [];
        foreach ($types as $t) {
            $statusesByType[$t['id']] = DB::query(
                'SELECT * FROM task_statuses WHERE task_type_id=? ORDER BY sort_order',
                [$t['id']]
            );
        }

        $users = DB::query(
            'SELECT id, CONCAT(first_name," ",last_name) AS name
             FROM users WHERE is_active=1 ORDER BY first_name, last_name'
        );

        $this->view('pages/admin/task-settings', compact('types', 'statusesByType', 'users'));
    }

    public function createType(): void
    {
        $this->requirePermission('task_settings.manage');
        $this->verifyCsrf();

        $name    = trim($this->post('name', ''));
        $slaDays = max(1, (int)$this->post('sla_days', 3));
        $assigneeIds = $this->parseIds($this->post('assignee_ids', '[]'));

        if ($name === '') {
            $this->json(['error' => true, 'msg' => 'שם לא יכול להיות ריק'], 422);
        }

        $id = DB::insert(
            'INSERT INTO task_types (name, sla_days, default_assignee_ids, default_watcher_ids)
             VALUES (?, ?, ?, ?)',
            [$name, $slaDays, json_encode($assigneeIds, JSON_UNESCAPED_UNICODE), json_encode([0], JSON_UNESCAPED_UNICODE)]
        );

        $this->json(['error' => false, 'id' => $id, 'msg' => 'סוג נוצר']);
    }

    public function updateType(string $id): void
    {
        $this->requirePermission('task_settings.manage');
        $this->verifyCsrf();

        $fields = [];
        $params = [];

        $name = $this->post('name');
        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                $this->json(['error' => true, 'msg' => 'שם לא יכול להיות ריק'], 422);
            }
            $fields[] = 'name = ?';
            $params[] = $name;
        }

        $slaDays = $this->post('sla_days');
        if ($slaDays !== null) {
            $fields[] = 'sla_days = ?';
            $params[] = max(1, (int)$slaDays);
        }

        $assigneeIds = $this->post('assignee_ids');
        if ($assigneeIds !== null) {
            $fields[] = 'default_assignee_ids = ?';
            $params[] = json_encode($this->parseIds($assigneeIds), JSON_UNESCAPED_UNICODE);
        }

        if (empty($fields)) {
            $this->json(['error' => true, 'msg' => 'אין שדות לעדכון'], 422);
        }

        $params[] = (int)$id;
        DB::execute('UPDATE task_types SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
        $this->json(['error' => false, 'msg' => 'עודכן']);
    }

    public function deleteType(string $id): void
    {
        $this->requirePermission('task_settings.manage');
        $this->verifyCsrf();

        $openCount = (int)DB::value(
            'SELECT COUNT(*) FROM tasks WHERE task_type_id=? AND is_active=1',
            [(int)$id]
        );

        if ($openCount > 0) {
            $this->json(['error' => true, 'msg' => "לא ניתן למחוק — קיימות {$openCount} משימות פתוחות לסוג זה"], 409);
        }

        DB::execute('DELETE FROM task_types WHERE id=?', [(int)$id]);
        $this->json(['error' => false, 'msg' => 'נמחק']);
    }

    public function createStatus(): void
    {
        $this->requirePermission('task_settings.manage');
        $this->verifyCsrf();

        $typeId    = (int)$this->post('task_type_id', 0);
        $name      = trim($this->post('name', ''));
        $color     = $this->sanitizeColor($this->post('color', '#4f7fff'));
        $sortOrder = (int)$this->post('sort_order', 0);

        if ($typeId <= 0 || $name === '') {
            $this->json(['error' => true, 'msg' => 'נתונים חסרים'], 422);
        }

        $id = DB::insert(
            'INSERT INTO task_statuses (task_type_id, name, color, sort_order) VALUES (?,?,?,?)',
            [$typeId, $name, $color, $sortOrder]
        );

        $this->json(['error' => false, 'id' => $id, 'msg' => 'סטטוס נוצר']);
    }

    public function updateStatus(string $id): void
    {
        $this->requirePermission('task_settings.manage');
        $this->verifyCsrf();

        $fields = [];
        $params = [];

        $name = $this->post('name');
        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                $this->json(['error' => true, 'msg' => 'שם לא יכול להיות ריק'], 422);
            }
            $fields[] = 'name = ?';
            $params[] = $name;
        }

        $color = $this->post('color');
        if ($color !== null) {
            $fields[] = 'color = ?';
            $params[] = $this->sanitizeColor($color);
        }

        $sortOrder = $this->post('sort_order');
        if ($sortOrder !== null) {
            $fields[] = 'sort_order = ?';
            $params[] = (int)$sortOrder;
        }

        if (empty($fields)) {
            $this->json(['error' => true, 'msg' => 'אין שדות לעדכון'], 422);
        }

        $params[] = (int)$id;
        DB::execute('UPDATE task_statuses SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
        $this->json(['error' => false, 'msg' => 'עודכן']);
    }

    public function deleteStatus(string $id): void
    {
        $this->requirePermission('task_settings.manage');
        $this->verifyCsrf();

        DB::execute('DELETE FROM task_statuses WHERE id=?', [(int)$id]);
        $this->json(['error' => false, 'msg' => 'נמחק']);
    }

    private function parseIds(string $json): array
    {
        $arr = json_decode($json, true);
        if (!is_array($arr)) return [];
        return array_values(array_filter(array_map('intval', $arr), fn($v) => $v > 0));
    }

    private function sanitizeColor(string $color): string
    {
        $color = trim($color);
        return preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? $color : '#4f7fff';
    }
}
```

- [ ] **Step 2: בדוק ידנית שה-route עובד**

נווט (כשמחובר כמנהל) ל-`/admin/task-settings` — צפוי: 500 עם "view not found" (ה-view עדיין לא קיים — זה תקין בשלב הזה).

- [ ] **Step 3: Commit**

```bash
git add src/Controllers/TaskSettingsController.php
git commit -m "feat: TaskSettingsController — CRUD task types and statuses"
```

---

## Task 4: View — `/admin/task-settings`

**Files:**
- Create: `views/pages/admin/task-settings.php`

**Interfaces:**
- Consumes: `$types` (array), `$statusesByType` (array keyed by type_id), `$users` (array [{id, name}])
- Produces: עמוד UI עם 2 tabs

- [ ] **Step 1: צור את תיקיית admin אם לא קיימת**

```bash
mkdir -p views/pages/admin
```

- [ ] **Step 2: צור את קובץ ה-view**

```php
<?php
use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
$typesJson    = json_encode($types         ?? [], JSON_UNESCAPED_UNICODE);
$statusesJson = json_encode($statusesByType ?? [], JSON_UNESCAPED_UNICODE);
$usersJson    = json_encode($users          ?? [], JSON_UNESCAPED_UNICODE);
?>
<style>
/* ── Tabs ── */
.ts-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:24px;}
.ts-tab{padding:10px 20px;font-size:14px;font-weight:600;color:var(--text2);cursor:pointer;
  border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;}
.ts-tab.active{color:var(--accent);border-bottom-color:var(--accent);}
.ts-pane{display:none;} .ts-pane.active{display:block;}

/* ── Table ── */
.ts-table{width:100%;border-collapse:collapse;font-size:14px;}
.ts-table th{text-align:right;padding:10px 14px;border-bottom:1px solid var(--border);
  font-weight:500;color:var(--text2);}
.ts-table td{padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:middle;}
.ts-table tr:last-child td{border-bottom:none;}

/* ── Inline edit ── */
.ts-edit-input{background:var(--bg3);border:1px solid var(--accent);border-radius:6px;
  color:var(--text);font-size:14px;font-family:inherit;padding:3px 8px;outline:none;width:100%;}

/* ── Chips ── */
.ts-chips{display:flex;flex-wrap:wrap;gap:5px;align-items:center;}
.ts-chip{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;background:var(--accent-dim);
  color:var(--accent);border-radius:12px;font-size:12px;font-weight:600;}
.ts-chip-x{cursor:pointer;opacity:.7;font-size:11px;} .ts-chip-x:hover{opacity:1;}

/* ── Assignee dropdown ── */
.ts-assign-dd{position:absolute;z-index:100;background:var(--bg2);border:1px solid var(--border2);
  border-radius:var(--radius);box-shadow:var(--shadow);min-width:200px;max-height:260px;
  overflow-y:auto;padding:6px 0;}
.ts-assign-opt{display:flex;align-items:center;gap:8px;padding:8px 14px;cursor:pointer;
  font-size:13px;transition:background .12s;}
.ts-assign-opt:hover{background:var(--bg3);}
.ts-assign-opt input[type=checkbox]{accent-color:var(--accent);}

/* ── Add row ── */
.ts-add-row{background:var(--bg3);}
.ts-add-input{background:var(--bg2);border:1px solid var(--border);border-radius:6px;
  color:var(--text);font-size:14px;font-family:inherit;padding:6px 10px;outline:none;
  transition:border-color .15s;}
.ts-add-input:focus{border-color:var(--accent);}

/* ── Status card ── */
.ts-status-card{display:flex;align-items:center;gap:10px;padding:10px 14px;
  border-bottom:1px solid var(--border);transition:background .12s;}
.ts-status-card:hover{background:var(--bg3);}
.ts-color-swatch{width:22px;height:22px;border-radius:5px;cursor:pointer;border:2px solid var(--border2);
  flex-shrink:0;transition:transform .15s;} .ts-color-swatch:hover{transform:scale(1.15);}
.ts-order-btn{background:none;border:1px solid var(--border);border-radius:5px;
  color:var(--text2);cursor:pointer;padding:2px 7px;font-size:13px;transition:background .12s,color .12s;}
.ts-order-btn:hover{background:var(--bg3);color:var(--text);}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <div class="page-title" style="margin-bottom:0;">הגדרות מערכת משימות</div>
</div>

<div class="card" style="padding:24px;">
  <!-- Tabs -->
  <div class="ts-tabs">
    <div class="ts-tab active" onclick="tsTab('types')">סוגי משימות</div>
    <div class="ts-tab"       onclick="tsTab('statuses')">סטטוסים</div>
  </div>

  <!-- Tab: Types -->
  <div id="ts-pane-types" class="ts-pane active">
    <table class="ts-table">
      <thead>
        <tr>
          <th>#</th><th>שם</th><th>SLA (ימים)</th><th>Assignees</th><th></th>
        </tr>
      </thead>
      <tbody id="ts-types-body">
      <!-- Add row -->
      <tr class="ts-add-row">
        <td style="color:var(--text3);">+</td>
        <td><input class="ts-add-input" id="new-type-name"    placeholder="שם הסוג" style="width:180px;"></td>
        <td><input class="ts-add-input" id="new-type-sla"     type="number" value="3" min="1" max="365" style="width:80px;"></td>
        <td style="color:var(--text3);font-size:12px;">ניתן להגדיר assignees לאחר יצירה</td>
        <td>
          <button class="btn btn-primary" style="padding:6px 14px;font-size:13px;" onclick="addType()">+ הוסף</button>
        </td>
      </tr>
      <?php foreach ($types as $t):
        $assigneeIds = json_decode($t['default_assignee_ids'] ?? '[]', true) ?: [];
      ?>
      <tr id="type-row-<?= (int)$t['id'] ?>">
        <td style="color:var(--text3);"><?= (int)$t['id'] ?></td>
        <td>
          <span class="ts-edit-span" onclick="startTypeEdit(<?= (int)$t['id'] ?>,'name',this)">
            <?= View::e($t['name']) ?>
          </span>
        </td>
        <td>
          <span class="ts-edit-span" onclick="startTypeEdit(<?= (int)$t['id'] ?>,'sla_days',this)">
            <?= (int)$t['sla_days'] ?>
          </span>
        </td>
        <td style="position:relative;">
          <div class="ts-chips" id="chips-<?= (int)$t['id'] ?>">
            <?php foreach ($assigneeIds as $uid):
              $uname = '';
              foreach ($users as $u) { if ((int)$u['id'] === $uid) { $uname = $u['name']; break; } }
              if (!$uname) continue;
            ?>
            <span class="ts-chip" data-uid="<?= $uid ?>">
              <?= View::e($uname) ?>
              <span class="ts-chip-x" onclick="removeAssignee(<?= (int)$t['id'] ?>,<?= $uid ?>)">✕</span>
            </span>
            <?php endforeach; ?>
            <button class="btn btn-ghost" style="padding:2px 8px;font-size:12px;"
                    onclick="openAssigneeDD(event,<?= (int)$t['id'] ?>)">✏️</button>
          </div>
        </td>
        <td>
          <button class="btn btn-ghost" style="padding:5px 10px;color:var(--danger);"
                  onclick="deleteType(<?= (int)$t['id'] ?>,'<?= View::e(addslashes($t['name'])) ?>')">🗑</button>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Tab: Statuses -->
  <div id="ts-pane-statuses" class="ts-pane">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
      <label style="color:var(--text2);font-size:13px;">סוג משימה:</label>
      <select id="ts-type-select" onchange="renderStatuses(this.value)"
              style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;
                     color:var(--text);padding:7px 12px;font-size:14px;font-family:inherit;outline:none;">
        <option value="">— בחר סוג —</option>
        <?php foreach ($types as $t): ?>
        <option value="<?= (int)$t['id'] ?>"><?= View::e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="ts-statuses-container"></div>
  </div>
</div>

<!-- Assignee dropdown (shared) -->
<div id="ts-assign-dd" class="ts-assign-dd" style="display:none;position:absolute;"></div>

<script>
const TS_BASE   = <?= json_encode($base) ?>;
const TS_CSRF   = <?= json_encode($csrf) ?>;
const TS_TYPES  = <?= $typesJson ?>;
const TS_STATUSES = <?= $statusesJson ?>;
const TS_USERS  = <?= $usersJson ?>;

// Current assignees per type (mutable)
const typeAssignees = {};
TS_TYPES.forEach(t => {
  try { typeAssignees[t.id] = JSON.parse(t.default_assignee_ids || '[]'); }
  catch(e) { typeAssignees[t.id] = []; }
});

/* ── Tab switching ── */
function tsTab(name) {
  document.querySelectorAll('.ts-tab').forEach((el,i) => {
    el.classList.toggle('active', (name==='types'&&i===0)||(name==='statuses'&&i===1));
  });
  document.querySelectorAll('.ts-pane').forEach(p => p.classList.remove('active'));
  document.getElementById('ts-pane-'+name).classList.add('active');
}

/* ── Helpers ── */
function esc(s){const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
async function tsPost(url, data) {
  const fd = new FormData();
  fd.append('_csrf', TS_CSRF);
  Object.entries(data).forEach(([k,v]) => fd.append(k, v));
  const res = await fetch(url, {method:'POST', body:fd});
  return res.json();
}

/* ── Type: add ── */
async function addType() {
  const name = document.getElementById('new-type-name').value.trim();
  const sla  = document.getElementById('new-type-sla').value;
  if (!name) { v2Toast('יש להזין שם'); return; }
  const d = await tsPost(`${TS_BASE}/admin/task-settings/types`, {name, sla_days: sla, assignee_ids: '[]'});
  if (d.error) { v2Toast('שגיאה: '+d.msg); return; }
  v2Toast('סוג נוצר — מרענן...');
  setTimeout(() => location.reload(), 800);
}

/* ── Type: inline edit ── */
function startTypeEdit(typeId, field, spanEl) {
  const current = spanEl.textContent.trim();
  const input = document.createElement('input');
  input.className = 'ts-edit-input';
  input.value = current;
  if (field === 'sla_days') { input.type='number'; input.min=1; input.max=365; input.style.width='80px'; }
  spanEl.replaceWith(input);
  input.focus(); input.select();

  const save = async () => {
    const val = input.value.trim();
    if (!val || val === current) { input.replaceWith(spanEl); return; }
    const d = await tsPost(`${TS_BASE}/admin/task-settings/types/${typeId}`, {[field]: val});
    if (d.error) { v2Toast('שגיאה: '+d.msg); input.replaceWith(spanEl); return; }
    spanEl.textContent = val;
    input.replaceWith(spanEl);
    v2Toast('עודכן');
  };
  input.addEventListener('blur', save);
  input.addEventListener('keydown', e => {
    if (e.key==='Enter') { e.preventDefault(); input.blur(); }
    if (e.key==='Escape') { input.value=current; input.blur(); }
  });
}

/* ── Assignees ── */
function openAssigneeDD(e, typeId) {
  e.stopPropagation();
  const dd = document.getElementById('ts-assign-dd');
  if (dd.dataset.typeId == typeId && dd.style.display !== 'none') {
    dd.style.display = 'none'; return;
  }
  dd.dataset.typeId = typeId;
  const current = typeAssignees[typeId] || [];
  let html = '';
  TS_USERS.forEach(u => {
    const checked = current.includes(u.id) ? 'checked' : '';
    html += `<label class="ts-assign-opt">
      <input type="checkbox" value="${u.id}" ${checked} onchange="toggleAssignee(${typeId},${u.id},this.checked)">
      ${esc(u.name)}
    </label>`;
  });
  dd.innerHTML = html;
  const btn = e.currentTarget;
  const rect = btn.getBoundingClientRect();
  dd.style.top  = (rect.bottom + window.scrollY + 4) + 'px';
  dd.style.right = (document.body.offsetWidth - rect.right) + 'px';
  dd.style.left  = 'auto';
  dd.style.display = 'block';
}

async function toggleAssignee(typeId, userId, add) {
  const arr = typeAssignees[typeId] || [];
  const next = add ? [...new Set([...arr, userId])] : arr.filter(id => id !== userId);
  typeAssignees[typeId] = next;
  const d = await tsPost(`${TS_BASE}/admin/task-settings/types/${typeId}`, {assignee_ids: JSON.stringify(next)});
  if (d.error) { v2Toast('שגיאה: '+d.msg); return; }
  refreshChips(typeId);
}

async function removeAssignee(typeId, userId) {
  await toggleAssignee(typeId, userId, false);
}

function refreshChips(typeId) {
  const container = document.getElementById('chips-'+typeId);
  if (!container) return;
  const arr = typeAssignees[typeId] || [];
  const btn = container.querySelector('button');
  container.querySelectorAll('.ts-chip').forEach(c => c.remove());
  arr.forEach(uid => {
    const u = TS_USERS.find(x => x.id == uid);
    if (!u) return;
    const chip = document.createElement('span');
    chip.className = 'ts-chip';
    chip.dataset.uid = uid;
    chip.innerHTML = `${esc(u.name)}<span class="ts-chip-x" onclick="removeAssignee(${typeId},${uid})">✕</span>`;
    container.insertBefore(chip, btn);
  });
}

document.addEventListener('click', e => {
  const dd = document.getElementById('ts-assign-dd');
  if (!dd.contains(e.target)) dd.style.display = 'none';
});

/* ── Type: delete ── */
async function deleteType(typeId, name) {
  if (!confirm(`למחוק את הסוג "${name}"?`)) return;
  const d = await tsPost(`${TS_BASE}/admin/task-settings/types/${typeId}/delete`, {});
  if (d.error) { v2Toast('שגיאה: '+d.msg); return; }
  document.getElementById('type-row-'+typeId)?.remove();
  v2Toast('הסוג נמחק');
}

/* ── Statuses tab ── */
function renderStatuses(typeId) {
  const container = document.getElementById('ts-statuses-container');
  if (!typeId) { container.innerHTML = ''; return; }
  const statuses = TS_STATUSES[typeId] || [];

  let html = `<div id="status-list-${typeId}">`;
  statuses.forEach((s, idx) => {
    const safeColor = /^#[0-9a-fA-F]{3,8}$/.test(s.color) ? s.color : '#4f7fff';
    html += `<div class="ts-status-card" id="sc-${s.id}">
      <button class="ts-order-btn" onclick="moveStatus(${typeId},${s.id},-1)" ${idx===0?'disabled':''}>▲</button>
      <button class="ts-order-btn" onclick="moveStatus(${typeId},${s.id},1)" ${idx===statuses.length-1?'disabled':''}>▼</button>
      <div class="ts-color-swatch" style="background:${safeColor};"
           onclick="document.getElementById('color-input-${s.id}').click()"></div>
      <input type="color" id="color-input-${s.id}" value="${safeColor}" style="display:none;"
             onchange="updateStatusColor(${s.id},this.value)">
      <span style="flex:1;" onclick="startStatusEdit(${s.id},this)">${esc(s.name)}</span>
      <button class="btn btn-ghost" style="padding:4px 8px;color:var(--danger);"
              onclick="deleteStatus(${typeId},${s.id},'${s.name.replace(/'/g,"\\'")}')">🗑</button>
    </div>`;
  });

  html += `</div>
  <div style="display:flex;gap:8px;align-items:center;margin-top:14px;padding:0 14px;">
    <input class="ts-add-input" id="new-status-name" placeholder="שם הסטטוס" style="flex:1;">
    <input type="color" id="new-status-color" value="#4f7fff" style="width:36px;height:36px;border:none;cursor:pointer;background:none;">
    <button class="btn btn-primary" style="padding:7px 16px;font-size:13px;" onclick="addStatus(${typeId})">+ הוסף</button>
  </div>`;

  container.innerHTML = html;
}

function startStatusEdit(statusId, spanEl) {
  const current = spanEl.textContent.trim();
  const input = document.createElement('input');
  input.className = 'ts-edit-input';
  input.value = current;
  spanEl.replaceWith(input);
  input.focus(); input.select();

  const save = async () => {
    const val = input.value.trim();
    if (!val || val === current) { input.replaceWith(spanEl); return; }
    const d = await tsPost(`${TS_BASE}/admin/task-settings/statuses/${statusId}`, {name: val});
    if (d.error) { v2Toast('שגיאה: '+d.msg); input.replaceWith(spanEl); return; }
    spanEl.textContent = val;
    input.replaceWith(spanEl);
    // update local data
    Object.values(TS_STATUSES).flat().forEach(s => { if (s.id==statusId) s.name=val; });
    v2Toast('עודכן');
  };
  input.addEventListener('blur', save);
  input.addEventListener('keydown', e => {
    if (e.key==='Enter') { e.preventDefault(); input.blur(); }
    if (e.key==='Escape') { input.value=current; input.blur(); }
  });
}

async function updateStatusColor(statusId, color) {
  const swatch = document.querySelector(`#sc-${statusId} .ts-color-swatch`);
  if (swatch) swatch.style.background = color;
  const d = await tsPost(`${TS_BASE}/admin/task-settings/statuses/${statusId}`, {color});
  if (d.error) v2Toast('שגיאה: '+d.msg);
  else v2Toast('צבע עודכן');
}

async function moveStatus(typeId, statusId, dir) {
  const list = TS_STATUSES[typeId] || [];
  const idx  = list.findIndex(s => s.id == statusId);
  const swapIdx = idx + dir;
  if (swapIdx < 0 || swapIdx >= list.length) return;

  // Swap sort_order values
  const a = list[idx], b = list[swapIdx];
  [a.sort_order, b.sort_order] = [b.sort_order, a.sort_order];
  list[idx] = b; list[swapIdx] = a;

  await Promise.all([
    tsPost(`${TS_BASE}/admin/task-settings/statuses/${a.id}`, {sort_order: a.sort_order}),
    tsPost(`${TS_BASE}/admin/task-settings/statuses/${b.id}`, {sort_order: b.sort_order}),
  ]);

  renderStatuses(typeId);
}

async function addStatus(typeId) {
  const name  = document.getElementById('new-status-name')?.value.trim();
  const color = document.getElementById('new-status-color')?.value || '#4f7fff';
  if (!name) { v2Toast('יש להזין שם'); return; }
  const list = TS_STATUSES[typeId] || [];
  const sortOrder = list.length ? Math.max(...list.map(s => s.sort_order)) + 1 : 1;
  const d = await tsPost(`${TS_BASE}/admin/task-settings/statuses`, {
    task_type_id: typeId, name, color, sort_order: sortOrder
  });
  if (d.error) { v2Toast('שגיאה: '+d.msg); return; }
  // update local & re-render
  list.push({id: d.id, task_type_id: typeId, name, color, sort_order: sortOrder});
  TS_STATUSES[typeId] = list;
  renderStatuses(typeId);
  v2Toast('סטטוס נוסף');
}

async function deleteStatus(typeId, statusId, name) {
  if (!confirm(`למחוק סטטוס "${name}"?`)) return;
  const d = await tsPost(`${TS_BASE}/admin/task-settings/statuses/${statusId}/delete`, {});
  if (d.error) { v2Toast('שגיאה: '+d.msg); return; }
  if (TS_STATUSES[typeId]) {
    TS_STATUSES[typeId] = TS_STATUSES[typeId].filter(s => s.id != statusId);
  }
  renderStatuses(typeId);
  v2Toast('נמחק');
}
</script>
```

- [ ] **Step 3: בדוק בדפדפן**

נווט ל-`/admin/task-settings` כמנהל. בדוק:
- שני tabs עובדים
- שינוי שם type בלחיצה ← שמירה + toast
- שינוי SLA ← שמירה
- הוספת/הסרת assignees
- Tab סטטוסים: בחירת סוג ← רשימה מוצגת, שינוי צבע + שם + סדר

- [ ] **Step 4: Commit**

```bash
git add views/pages/admin/task-settings.php
git commit -m "feat: task-settings admin UI — types + statuses tabs with inline editing"
```

---

## Task 5: Global SLA Badge בLayout

**Files:**
- Modify: `views/layouts/main.php`

**Interfaces:**
- Consumes: `$_SESSION['user_id']`, `Core\DB`
- Produces: badge אדום לחיץ על פריט ניווט "משימות" אם יש overdue

- [ ] **Step 1: הוסף ספירת overdue inline ב-`views/layouts/main.php`**

מצא את השורה שבה מוגדר `$canPbxSearch` (שורה ~13) והוסף אחריה:

```php
$overdueCount = 0;
if (!empty($_SESSION['user_id'])) {
    $overdueCount = (int)\Core\DB::value(
        'SELECT COUNT(*) FROM tasks
         WHERE assigned_user_id=? AND is_active=1
           AND DATE_ADD(created_at, INTERVAL sla_days DAY) < NOW()',
        [$_SESSION['user_id']]
    );
}
```

- [ ] **Step 2: הוסף CSS לbadge**

בבלוק ה-`<style>` הגדול של main.php, לפני הסגירה (`</style>`), הוסף:

```css
.nav-sla-badge{display:inline-flex;align-items:center;justify-content:center;
  min-width:18px;height:18px;padding:0 5px;background:var(--danger);color:#fff;
  font-size:10px;font-weight:700;border-radius:9px;margin-right:auto;flex-shrink:0;
  animation:sla-pulse 2s ease-in-out infinite;}
@keyframes sla-pulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(239,68,68,.4)}
  50%{opacity:.85;box-shadow:0 0 0 5px rgba(239,68,68,0)}}
```

- [ ] **Step 3: הוסף JS להזרקת הbadge לפריט "משימות" בnavbar**

מצא את הJS שמרנדר את הnavigation (סביב `buildNav` או `renderNav` — נמצא כ-`const NI_CLASS` בשורה ~957). הוסף פונקציה חדשה שמחפשת את קישור `/tasks` ומוסיפה badge, ומפעיל אותה אחרי שהnav נבנה.

הוסף ב-JS של main.php (לפני סגירת ה-`</script>` הראשי):

```php
<script>
window.__OVERDUE_COUNT = <?= (int)$overdueCount ?>;
</script>
```

ואחרי בניית ה-nav (מצא את `buildNav()` או `renderNav()` שקוראת לאחר fetch — הוסף שם):

```js
function injectSlaBadge() {
  if (!window.__OVERDUE_COUNT) return;
  const navLinks = document.querySelectorAll('#sidebar .nav-item');
  navLinks.forEach(el => {
    if (el.getAttribute('href') === window.__V2_BASE + '/tasks' || el.getAttribute('href') === '/tasks') {
      if (!el.querySelector('.nav-sla-badge')) {
        const badge = document.createElement('span');
        badge.className = 'nav-sla-badge';
        badge.textContent = window.__OVERDUE_COUNT;
        badge.title = window.__OVERDUE_COUNT + ' משימות שעברו SLA';
        el.appendChild(badge);
      }
    }
  });
}
// קרא אחרי שהnav נבנה
document.addEventListener('DOMContentLoaded', () => setTimeout(injectSlaBadge, 300));
```

- [ ] **Step 4: בדוק בדפדפן**

עם משתמש שיש לו משימות שעברו SLA — badge אדום מופיע ליד "משימות" בsidebar.  
ללא משימות overdue — badge לא מוצג.

- [ ] **Step 5: Commit**

```bash
git add views/layouts/main.php
git commit -m "feat: global SLA overdue badge in sidebar nav — pulsing red, links to /tasks"
```

---

## Task 6: TaskController — filter=overdue

**Files:**
- Modify: `src/Controllers/TaskController.php`
- Modify: `views/pages/tasks/index.php`

**Interfaces:**
- Produces: `GET /tasks?filter=overdue` מציג רק משימות שעברו SLA עם הדגשה חזותית

- [ ] **Step 1: עדכן `TaskController::index()`**

הוסף תמיכה ב-filter. עדכן את method `index()` ב-`src/Controllers/TaskController.php`:

```php
public function index(): void
{
    $this->requireAuth();
    $userId = $_SESSION['user_id'];
    $filter = $this->get('filter', '');
    $tasks  = TaskModel::forUser($userId, false, $filter === 'overdue');

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

    $this->view('pages/tasks/index', compact('tasks', 'statusesByType', 'filter'));
}
```

- [ ] **Step 2: עדכן `TaskModel::forUser()`**

ב-`src/Models/TaskModel.php`, שנה את חתימת `forUser()` להוסיף פרמטר `$overdueOnly`:

```php
public static function forUser(int $userId, bool $closed = false, bool $overdueOnly = false): array
{
    $where = 'WHERE t.assigned_user_id = ? AND t.is_active = ?';
    $params = [$userId, $closed ? 0 : 1];

    if ($overdueOnly) {
        $where .= ' AND DATE_ADD(t.created_at, INTERVAL t.sla_days DAY) < NOW()';
    }

    return DB::query(
        "SELECT t.id, t.title, t.description, t.sla_days,
                t.created_at, t.status_changed_at, t.is_active,
                t.source_type, t.source_id,
                CONCAT(u.first_name,\" \",u.last_name) AS opened_by_name,
                ts.name  AS status_name,
                ts.color AS status_color,
                tt.name  AS type_name,
                tt.id    AS task_type_id,
                t.status_id
         FROM tasks t
         LEFT JOIN users u          ON u.id  = t.open_by
         LEFT JOIN task_statuses ts ON ts.id = t.status_id
         LEFT JOIN task_types    tt ON tt.id = t.task_type_id
         {$where}
         ORDER BY t.created_at DESC",
        $params
    );
}
```

- [ ] **Step 3: עדכן `views/pages/tasks/index.php`**

הוסף אחרי שורת `$base = ...` בראש הקובץ:

```php
$filter = $filter ?? '';
$isOverdueFilter = $filter === 'overdue';
```

הוסף banner אם filter פעיל — לפני ה-`<div style="display:flex...">` הראשון:

```php
<?php if ($isOverdueFilter): ?>
<div style="display:flex;align-items:center;gap:10px;background:rgba(239,68,68,.1);
            border:1px solid rgba(239,68,68,.3);border-radius:var(--radius);
            padding:10px 16px;margin-bottom:16px;color:var(--danger);font-size:13px;font-weight:600;">
  <i class="bi bi-exclamation-triangle-fill"></i>
  מציג משימות שעברו SLA בלבד —
  <a href="<?= $base ?>/tasks" style="color:var(--accent);text-decoration:none;margin-right:4px;">הצג הכל</a>
</div>
<?php endif; ?>
```

בשורת הגדרת `<tr>` בלולאה, החלף את:
```php
<tr style="border-bottom:1px solid var(--border);" id="task-row-<?= (int)$t['id'] ?>">
```
ב:
```php
<tr style="border-bottom:1px solid var(--border);<?= $overdue ? 'border-right:3px solid var(--danger);background:rgba(239,68,68,.05);' : '' ?>"
    id="task-row-<?= (int)$t['id'] ?>">
```

- [ ] **Step 4: בדוק**

נווט ל-`/tasks` — שורות overdue מודגשות באדום.  
נווט ל-`/tasks?filter=overdue` — רק משימות overdue, banner מוצג.

- [ ] **Step 5: Commit**

```bash
git add src/Controllers/TaskController.php src/Models/TaskModel.php views/pages/tasks/index.php
git commit -m "feat: tasks overdue filter — ?filter=overdue + red row highlight"
```

---

## Task 7: SLA Cron — notifyOverdueTasks()

**Files:**
- Modify: `cron/cron_1hr.php`

**Interfaces:**
- Consumes: `tasks.sla_notified_at` (מ-Task 1), `task_watchers`, `users`
- Produces: מיילים ל-assignees + watchers, עדכון `sla_notified_at`

- [ ] **Step 1: הוסף `notifyOverdueTasks()` בסוף `cron/cron_1hr.php`**

הוסף לפני הסגירה של הקובץ (אחרי `sendMailExpired`):

```php
function notifyOverdueTasks(PDO $pdo): void
{
    $stmt = $pdo->query("
        SELECT t.id, t.title, t.sla_days, t.created_at,
               t.assigned_user_id,
               u.email  AS assignee_email,
               CONCAT(u.first_name,' ',u.last_name) AS assignee_name
        FROM tasks t
        JOIN users u ON u.id = t.assigned_user_id
        WHERE t.is_active = 1
          AND t.sla_notified_at IS NULL
          AND DATE_ADD(t.created_at, INTERVAL t.sla_days DAY) < NOW()
    ");
    $tasks = $stmt->fetchAll();

    if (empty($tasks)) return;

    $appUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://moked-net.co.il';

    foreach ($tasks as $task) {
        $taskId   = (int)$task['id'];
        $title    = $task['title'];
        $slaDays  = (int)$task['sla_days'];
        $created  = date('d/m/Y', strtotime($task['created_at']));

        // Collect watcher emails
        $watchers = $pdo->prepare("
            SELECT u.email FROM task_watchers tw
            JOIN users u ON u.id = tw.user_id
            WHERE tw.task_id = ? AND u.is_active = 1 AND u.email != ''
        ");
        $watchers->execute([$taskId]);
        $watcherEmails = $watchers->fetchAll(PDO::FETCH_COLUMN);

        $subject = "[SLA] משימה #{$taskId} עברה את מועד הטיפול";
        $body    = "<p>שלום <b>{$task['assignee_name']}</b>,</p>"
                 . "<p>המשימה <b>\"" . htmlspecialchars($title, ENT_QUOTES) . "\"</b>"
                 . " עברה את יעד ה-SLA ({$slaDays} ימים).</p>"
                 . "<p>נפתחה ב: {$created}</p>"
                 . "<p><a href=\"{$appUrl}/tasks?filter=overdue\" style=\"color:#5b8dee;\">לצפייה ולטיפול במשימות שעברו SLA</a></p>";

        $ccList = implode(',', array_unique(array_filter($watcherEmails)));
        mail($task['assignee_email'], $subject, bodyWrap($body), buildHeaders($ccList));

        $pdo->prepare("UPDATE tasks SET sla_notified_at=NOW() WHERE id=?")->execute([$taskId]);
    }

    echo "✓ SLA notifications sent: " . count($tasks) . "\n";
}
```

- [ ] **Step 2: הוסף קריאה ל-`notifyOverdueTasks()` בסוף ה-cron**

בסוף `cron_1hr.php`, לאחר `processActiveJobs($pdo, $pdoV1);`, הוסף:

```php
notifyOverdueTasks($pdo);
```

- [ ] **Step 3: בדוק שה-cron רץ ללא שגיאות**

```bash
php cron/cron_1hr.php
```

פלט צפוי (ללא משימות overdue):
```
✓ SLA notifications sent: 0
```

- [ ] **Step 4: בדוק שיש APP_URL זמין**

פתח `cron/bootstrap.php` וודא שמוגדר `APP_URL` או הוסף:

```php
// ב-bootstrap.php, לאחר require config:
if (!defined('APP_URL')) {
    define('APP_URL', CFG['app']['url'] ?? 'https://moked-net.co.il');
}
```

- [ ] **Step 5: Commit**

```bash
git add cron/cron_1hr.php cron/bootstrap.php
git commit -m "feat: SLA cron — hourly email notifications to assignee + watchers on overdue tasks"
```

---

## Task 8: הוספת קישור לעמוד ניהול בNav

**Files:**
- Modify: `views/layouts/main.php` (או הNav שנטען דינמית — בדוק)

**Interfaces:**
- Consumes: `Auth::can('task_settings.manage')`, `$base`
- Produces: קישור "הגדרות משימות" בsidebar למשתמשים עם permission

- [ ] **Step 1: בדוק איך הNav נבנה**

```bash
grep -n "task\|/tasks\|nav-item" views/layouts/main.php | head -30
```

אם הnav נטען מ-DB דרך API (`/api/nav`) — הוסף רשומה בטבלת הnavigation דרך `/nav-manager`.  
אם הוא hard-coded בlayout — הוסף ישירות.

- [ ] **Step 2א: אם הNav מגיע מDB**

היכנס ל-`/nav-manager` ← הוסף פריט: שם "הגדרות משימות", URL `/admin/task-settings`, אייקון `bi-gear-fill`, הרשאה `task_settings.manage`.

- [ ] **Step 2ב: אם הNav hard-coded**

מצא את הקישור לtasks ב-layout והוסף אחריו (בתוך בלוק `<?php if (Auth::can('task_settings.manage')): ?>`):

```php
<?php if (\Core\Auth::can('task_settings.manage')): ?>
<a href="<?= $base ?>/admin/task-settings" class="nav-item ni-admin">
  <i class="bi bi-gear-fill nav-icon"></i>
  <span class="nav-text">הגדרות משימות</span>
</a>
<?php endif; ?>
```

- [ ] **Step 3: בדוק בדפדפן**

כמנהל — קישור "הגדרות משימות" מופיע בsidebar ומוביל לעמוד.  
כמשתמש רגיל — הקישור לא מופיע.

- [ ] **Step 4: Commit**

```bash
git add views/layouts/main.php
git commit -m "feat: add task-settings nav link for users with task_settings.manage permission"
```

---

## Self-Review

**Spec coverage:**
- ✓ Task 1 — migration permission + sla_notified_at
- ✓ Task 2 — routes + api/users/active
- ✓ Task 3 — TaskSettingsController (כל 7 methods)
- ✓ Task 4 — View עם 2 tabs, inline editing, assignees, statuses
- ✓ Task 5 — global SLA badge בlayout
- ✓ Task 6 — filter=overdue בtasks + הדגשת שורות
- ✓ Task 7 — SLA cron שעתי עם מיילים
- ✓ Task 8 — קישור ניווט לעמוד הניהול

**Placeholder scan:** אין TBD. כל קוד מלא. ✓

**Type consistency:**
- `TaskModel::forUser(int, bool, bool)` — חתימה חדשה תואמת את השימוש ב-`TaskController::index()`. ✓
- `tsPost()` ב-JS — משמשת עקבית בכל פונקציות ה-UI. ✓
- `notifyOverdueTasks(PDO $pdo)` — מקבלת את `$pdo` מהcron הקיים. ✓
