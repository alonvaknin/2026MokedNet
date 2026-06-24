# Tasks Phase 3 — Design Spec

**Date:** 2026-06-24
**Author:** design session with user

---

## Overview

Three complementary features added to the existing task management system (Phase 1 + 2 already shipped):

- **3a. Extended Tasks View** — closed/open toggle, all-users toggle (permission-gated), new columns
- **3b. Status-Change Animations** — badge flip on every change, canvas confetti when closing
- **3c. Internal Log / Comments** — per-task comment thread for team collaboration

---

## 3a. Extended Tasks View

### Database changes

```sql
ALTER TABLE tasks ADD COLUMN status_changed_by INT NULL AFTER status_id;
ALTER TABLE tasks ADD COLUMN status_changed_at DATETIME NULL AFTER status_changed_by;
ALTER TABLE tasks ADD COLUMN assigned_dept_id INT NULL AFTER assigned_user_id;
```

- `status_changed_by` FK → `users.id` (nullable, SET NULL on delete)
- `status_changed_at` — updated every time status_id changes
- `assigned_dept_id` — filled on task create: department of `assigned_user_id` if set, else `open_by_user_id`'s department

### New permission

`tasks.viewAll` — added to `UserModel::PERM_CATEGORIES` under "ניהול מערכת" and to `UserModel::PERM_LABELS`.

### Controller changes — `TaskController`

`index()` accepts two new query-string params:
- `show=closed` / `show=open` (default `open`)
- `scope=all` / `scope=mine` (default `mine`; `scope=all` silently ignored without `tasks.viewAll` permission)

Passes `$showClosed`, `$scopeAll`, `$canViewAll` to the view.

### Model changes — `TaskModel::forQuery()`

Replaces `forUser()`. Signature:

```php
public static function forQuery(int $userId, bool $closed = false, bool $allUsers = false, bool $overdueOnly = false): array
```

- When `$closed=true`: `WHERE t.is_active = 0` (closed tasks have `is_active = 0`)
- When `$allUsers=true`: removes `assigned_user_id = ?` filter
- Joins: `users AS changer ON changer.id = t.status_changed_by`, `departments AS dept ON dept.id = t.assigned_dept_id`

SELECT additions:
```sql
t.status_changed_at,
t.status_changed_by,
CONCAT(changer.first_name,' ',changer.last_name) AS changed_by_name,
dept.name_heb AS dept_name,
t.assigned_dept_id
```

### Task create — `TaskController::create()`

When inserting a new task, after resolving `assigned_user_id`:
1. Look up `department_id` of `assigned_user_id` if set
2. Else look up `department_id` of `open_by` (`$_SESSION['user']['id']`)
3. Save as `assigned_dept_id`

### Status update — `TaskController::updateStatus()`

When saving new status, also:
```php
DB::execute(
  'UPDATE tasks SET status_id=?, status_changed_by=?, status_changed_at=NOW() WHERE id=?',
  [$statusId, $_SESSION['user']['id'], $taskId]
);
```

`is_active` is set to 0 when the chosen status has `is_closed=1` (field already on `task_statuses`).

### View — `views/pages/tasks/index.php`

Header area gains two toggle groups (inline, Bootstrap-icon styled):

```
[פתוחות | סגורות]          [שלי | הכל ↑permission]
```

- Both are `<a>` links that rebuild the URL with the new params
- "הכל" only rendered if `$canViewAll`

New table columns (added after existing "נפתח" column):
| Column | Source |
|---|---|
| עודכן סטטוס | `status_changed_at` formatted `d/m/Y H:i` |
| עודכן ע"י | `changed_by_name` |
| מחלקה | `dept_name` |

Empty state message changes: "אין משימות סגורות 🎉" when `$showClosed`.

---

## 3b. Status-Change Animations

### CSS — `views/pages/tasks/index.php` `<style>` block

```css
@keyframes badge-flip {
  0%   { transform: scaleY(1); }
  40%  { transform: scaleY(0); }
  100% { transform: scaleY(1); }
}
.badge-flip { animation: badge-flip 0.22s ease; }
```

### JS — `setStatus()` function

1. After receiving a success response, apply `.badge-flip` to the badge element
2. Remove the class on `animationend`
3. If the new status name exactly equals `"סגור"` OR `data-is-closed="1"` attribute is present on the chosen status option → fire confetti

```js
// confetti burst (canvas-confetti CDN, loaded lazily)
function fireConfetti(originEl) {
  const rect = originEl.getBoundingClientRect();
  const x = (rect.left + rect.width / 2) / window.innerWidth;
  const y = (rect.top + rect.height / 2) / window.innerHeight;
  confetti({ particleCount: 80, spread: 70, origin: { x, y } });
}
```

### canvas-confetti loading

Loaded lazily on first "סגור" event via dynamic `<script>` injection (CDN: `https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js`). No build step required.

Each status option rendered in the dropdown includes `data-is-closed` attribute derived from `STATUSES_BY_TYPE[typeId]` (the `is_closed` field must be included in the JSON).

---

## 3c. Internal Log / Comments

### Database

```sql
CREATE TABLE task_comments (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id     INT UNSIGNED NOT NULL,
  user_id     INT NOT NULL,
  body        TEXT NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_task (task_id)
);
```

### Routes — `config/routes.php`

```php
['GET',  '/tasks/{id}/comments',       'TaskController@getComments'],
['POST', '/tasks/{id}/comments',       'TaskController@addComment'],
```

### Controller — `TaskController`

`getComments(int $id)`: returns JSON array, each item:
```json
{ "id": 1, "body": "...", "created_at": "2026-06-24 10:00:00",
  "user_name": "אלון וקנין" }
```

Authorization: task must exist AND (`assigned_user_id = session user` OR `open_by = session user` OR user has `tasks.viewAll`).

`addComment(int $id)`: POST with `body` (trim, max 2000 chars). Same auth. Returns `{"ok":true,"comment":{...}}`.

### Model — `TaskCommentModel` (new file)

```php
class TaskCommentModel {
  public static function forTask(int $taskId): array;
  public static function add(int $taskId, int $userId, string $body): array; // returns saved row
}
```

### View changes — `views/pages/tasks/index.php`

Each task row gets a comment icon cell (last column):

```html
<button class="btn-icon" onclick="openComments(<?= $t['id'] ?>)" title="הערות">
  <i class="bi bi-chat-dots"></i>
  <span class="comment-count" id="cc-<?= $t['id'] ?>"></span>
</button>
```

A shared **drawer** (slide-in from right, 360px wide) appended to `<body>`:

```html
<div id="comment-drawer" style="position:fixed;top:0;right:-380px;width:360px;height:100vh;
     background:var(--bg2);border-right:1px solid var(--border);z-index:300;
     transition:right .25s ease;padding:20px;overflow-y:auto;">
  <div id="comment-drawer-title" style="font-size:16px;font-weight:700;margin-bottom:16px;"></div>
  <div id="comment-list"></div>
  <div style="margin-top:16px;">
    <textarea id="comment-body" rows="3" placeholder="כתוב עדכון פנימי..."
              style="width:100%;..."></textarea>
    <button onclick="submitComment()" class="btn btn-primary" style="width:100%;margin-top:8px;">שלח</button>
  </div>
  <button onclick="closeDrawer()" style="position:absolute;top:14px;left:14px;...">✕</button>
</div>
```

JS functions: `openComments(taskId)`, `renderComments(list)`, `submitComment()`, `closeDrawer()`.

`openComments` fetches `GET /tasks/{id}/comments`, slides drawer open, renders timeline.

Comment bubble HTML:
```html
<div style="margin-bottom:12px;">
  <div style="font-size:12px;color:var(--text3);">👤 {name} · {date}</div>
  <div style="background:var(--bg3);border-radius:8px;padding:8px 12px;margin-top:4px;font-size:13px;">
    {body}
  </div>
</div>
```

---

## Migration file

`config/migration_tasks_phase3.php` — run once, idempotent:

1. ADD COLUMN `tasks.status_changed_by` (check information_schema first)
2. ADD COLUMN `tasks.status_changed_at`
3. ADD COLUMN `tasks.assigned_dept_id`
4. CREATE TABLE `task_comments` (IF NOT EXISTS)
5. INSERT IGNORE `tasks.viewAll` permission for admin groups

---

## Not in scope

- Real-time push (WebSocket/SSE) — polling or manual refresh only
- Comment editing or deletion
- @mentions or notifications on new comments
- Pagination of comments (all loaded at once)
- File attachments
