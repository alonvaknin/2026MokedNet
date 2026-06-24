# מערכת משימות — שלב 2: ניהול הגדרות + SLA + התראות גלובליות

**תאריך:** 2026-06-24  
**סטטוס:** מאושר לפיתוח  
**תלות:** [שלב 1](2026-06-24-task-system-phase1-design.md) — חייב להיות מוטמע

---

## סקירה

שלב 2 מוסיף שלושה רכיבים:

1. **ממשק ניהול הגדרות משימות** — עמוד `/admin/task-settings` מוגן ב-permission, עם tabs: סוגי משימות וסטטוסים. עריכה inline ללא modals.
2. **SLA cron שעתי** — משולב ל-`cron_1hr.php` הקיים, שולח מייל ל-assignee ו-watchers כשמשימה עוברת deadline.
3. **התראת SLA גלובלית** — badge לחיץ בכל עמוד (layout ראשי) שמציג ספירת משימות שעברו SLA, מקשר ל-`/tasks?filter=overdue`.

---

## 1. Permission

### migration: `config/migration_task_settings_perm.php`

סקריפט חד-פעמי שמוסיף את ה-permission key לקבוצת המנהלים:

```sql
INSERT IGNORE INTO permission_group_grants (group_id, permission_key, granted)
SELECT id, 'task_settings.manage', 1 FROM permission_groups WHERE name_heb LIKE '%מנהל%' LIMIT 1;
```

ה-permission key: `task_settings.manage`  
שימוש: `Auth::can('task_settings.manage')` — נבדק בכל endpoint של `TaskSettingsController`.

---

## 2. DB — שינוי בטבלה `tasks`

שדה חדש לסימון שנשלחה התראת SLA:

```sql
ALTER TABLE tasks ADD COLUMN sla_notified_at DATETIME NULL AFTER sla_days;
```

ייכלל ב-migration נפרד: `config/migration_task_sla_notify.php`.

---

## 3. Routes

```php
// Task Settings (Admin)
$router->get   ('/admin/task-settings',              'Controllers\\TaskSettingsController@index');
$router->post  ('/admin/task-settings/types',         'Controllers\\TaskSettingsController@createType');
$router->post  ('/admin/task-settings/types/{id}',    'Controllers\\TaskSettingsController@updateType');
$router->post  ('/admin/task-settings/types/{id}/delete', 'Controllers\\TaskSettingsController@deleteType');
$router->post  ('/admin/task-settings/statuses',      'Controllers\\TaskSettingsController@createStatus');
$router->post  ('/admin/task-settings/statuses/{id}', 'Controllers\\TaskSettingsController@updateStatus');
$router->post  ('/admin/task-settings/statuses/{id}/delete', 'Controllers\\TaskSettingsController@deleteStatus');
$router->get   ('/api/users/active',                  'Controllers\\UserController@apiActiveList');
```

---

## 4. Controller: `TaskSettingsController`

כל method מתחיל ב:
```php
$this->requireAuth();
if (!Auth::can('task_settings.manage')) { $this->redirect('/403'); }
```

### `index()`
טוען: כל `task_types` + הסטטוסים שלהם + רשימת משתמשים פעילים (לdropdown assignees).  
מעביר לview: `$types`, `$statusesByType`, `$users`.

### `createType()` / `updateType()`
פרמטרים: `name` (required, max 100), `sla_days` (int, 1–365), `assignee_ids` (JSON array of user IDs).  
`updateType` — מחיקה חסומה: אם קיימות משימות פתוחות (`is_active=1`) עם `task_type_id={id}`, מחזיר שגיאה JSON.

### `deleteType()`
בדיקת תלות לפני מחיקה — אם קיימות משימות פתוחות לסוג זה, מחזיר `error: true` עם הסבר.

### `createStatus()` / `updateStatus()`
פרמטרים: `task_type_id`, `name` (max 50), `color` (validated hex), `sort_order` (int).

### `deleteStatus()`
ללא בדיקת תלות (סטטוסים מוחלפים ל-NULL על המשימה הפעילה — מקובל).

---

## 5. UI — `/admin/task-settings`

### מבנה כללי

עמוד בסגנון המערכת הקיימת (dark theme, `var(--bg2)`, `var(--accent)`).  
כותרת: "הגדרות מערכת משימות"  
2 tabs בסגנון underline (לא pill):
- **סוגי משימות** (active by default)
- **סטטוסים**

Tab switching — JavaScript טהור, אין page reload.

---

### Tab 1: סוגי משימות

**שורת הוספה** בראש הטבלה (רקע `var(--bg3)`, border מודגש):
- שדה טקסט: שם הסוג
- שדה מספר: SLA (ימים), ברירת מחדל 3
- כפתור "+ הוסף"

**טבלת סוגים קיימים** — עמודות:

| # | שם | SLA (ימים) | Assignees | פעולות |
|---|----|-----------:|-----------|--------|
| 1 | שינוי שם בחשבונית | 3 | [אייל גואטה ✕] | 🗑 |

- **שם** — לחיצה בודדת → `<input>` inline. Enter / blur → `POST /admin/task-settings/types/{id}`.
- **SLA** — כנ"ל.
- **Assignees** — chips עם שמות + ✕ להסרה. כפתור ✏️ פותח dropdown multi-select מרשימת משתמשים פעילים (נטען ב-JS מ-`/api/users/active`). שמירה אוטומטית עם סגירת dropdown.
- **🗑** — `confirm()` → `POST /admin/task-settings/types/{id}/delete`. אם יש משימות פתוחות — toast שגיאה ואין מחיקה.

---

### Tab 2: סטטוסים

**Dropdown** בראש: "בחר סוג משימה" → בחירה מציגה את הסטטוסים שלו מתחת.

**רשימת סטטוסים** (כרטיסים אנכיים):

```
[▲] [▼]  ████  שם הסטטוס (inline editable)    [🗑]
```

- **color swatch** — `<input type="color">` מוסתר, לחיצה על הריבוע צבעוני מפעילה אותו. שינוי צבע → `POST /admin/task-settings/statuses/{id}` מיד.
- **שם** — inline editable (click → input → blur/Enter → save).
- **▲ ▼** — שינוי `sort_order`: מחליף בין השורה הנוכחית לשכנה, שולח שתי בקשות `POST /admin/task-settings/statuses/{id}` עם sort_order מעודכן.
- **🗑** — מחיקה עם `confirm()`.

**שורת הוספה** בתחתית:
- שדה שם + color picker + כפתור "+ הוסף סטטוס"

---

## 6. API: `/api/users/active`

מתודה חדשה ב-`UserController::apiActiveList()`:
```sql
SELECT id, CONCAT(first_name,' ',last_name) AS name FROM users WHERE is_active=1 ORDER BY first_name
```
מחזיר JSON array. מוגן ב-`requireAuth()`.

---

## 7. SLA Notifications — Cron

### שדה חדש: `tasks.sla_notified_at`

מסומן ב-`NOW()` לאחר שליחת מייל SLA למשימה. מונע שליחה כפולה.

### שילוב ב-`cron_1hr.php`

מוסיפים קריאה לפונקציה `notifyOverdueTasks()` בסוף הקובץ הקיים (אחרי `processActiveJobs`).

```php
notifyOverdueTasks(DB::get());
```

### `notifyOverdueTasks(PDO $pdo)`

לוגיקה:
1. שולפת משימות פתוחות (`is_active=1`) שעברו SLA ועוד לא נשלחה להן התראה:
   ```sql
   SELECT t.id, t.title, t.sla_days, t.created_at,
          t.assigned_user_id, t.open_by,
          u.email AS assignee_email,
          CONCAT(u.first_name,' ',u.last_name) AS assignee_name
   FROM tasks t
   JOIN users u ON u.id = t.assigned_user_id
   WHERE t.is_active = 1
     AND t.sla_notified_at IS NULL
     AND DATE_ADD(t.created_at, INTERVAL t.sla_days DAY) < NOW()
   ```
2. לכל משימה — שולפת watchers (`task_watchers` JOIN `users`).
3. שולחת מייל ל-assignee + כל watcher (כ-CC או מיילים נפרדים).
4. מסמנת `sla_notified_at = NOW()`.

### תבנית מייל SLA

```
נושא: [SLA] משימה #{id} עברה את מועד הטיפול
גוף:
  שלום {assignee_name},
  המשימה "{title}" עברה את יעד ה-SLA ({sla_days} ימים).
  נפתחה ב: {created_at}
  לצפייה ולטיפול: https://{base}/tasks?filter=overdue
```

סגנון: `bodyWrap()` הקיים בcron.

---

## 8. התראה גלובלית בכל העמודים

### מה מוצג

Badge אדום (`var(--danger)`) בsidebar/header, מציג ספירת משימות פתוחות שעברו SLA **של המשתמש המחובר**.  
לחיץ → מנווט ל-`/tasks?filter=overdue`.

### איך נטען

ב-`views/layouts/main.php`, מייד לפני `</body>`:

```php
<?php
if (!empty($_SESSION['user_id'])) {
    $overdueCount = \Core\DB::value(
        'SELECT COUNT(*) FROM tasks
         WHERE assigned_user_id = ? AND is_active = 1
           AND DATE_ADD(created_at, INTERVAL sla_days DAY) < NOW()',
        [$_SESSION['user_id']]
    );
}
?>
<script>
window.__OVERDUE_COUNT = <?= (int)($overdueCount ?? 0) ?>;
</script>
```

ה-JS בlayout מזריק את הbadge לפריט הניווט של "משימות" בsidebar (מזהה לפי `href="/tasks"`), ומוסיף `title="X משימות שעברו SLA"`.

### עיצוב הbadge

```css
.nav-sla-badge {
  background: var(--danger);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  border-radius: 10px;
  padding: 1px 6px;
  margin-right: auto;
  min-width: 18px;
  text-align: center;
  animation: sla-pulse 2s infinite;
}
@keyframes sla-pulse {
  0%,100% { opacity:1; }
  50%      { opacity:.6; }
}
```

Badge מוצג רק אם `__OVERDUE_COUNT > 0`.

### עמוד `/tasks?filter=overdue`

ב-`TaskController::index()`:
- אם `$_GET['filter'] === 'overdue'` — מסנן רק משימות שעברו SLA.
- שורות overdue מודגשות עם `border-right: 3px solid var(--danger)` ו-background `rgba(239,68,68,.07)`.

---

## 9. קבצים שנוצרים / משתנים

| קובץ | פעולה |
|------|--------|
| `config/migration_task_settings_perm.php` | חדש — migration permission |
| `config/migration_task_sla_notify.php` | חדש — ADD COLUMN sla_notified_at |
| `config/routes.php` | עדכון — הוספת routes ניהול |
| `src/Controllers/TaskSettingsController.php` | חדש |
| `src/Controllers/UserController.php` | עדכון — הוספת `apiActiveList()` |
| `src/Controllers/TaskController.php` | עדכון — תמיכה ב-`?filter=overdue` |
| `views/pages/tasks/index.php` | עדכון — הדגשת overdue + filter |
| `views/pages/admin/task-settings.php` | חדש |
| `views/layouts/main.php` | עדכון — badge SLA גלובלי |
| `cron/cron_1hr.php` | עדכון — הוספת `notifyOverdueTasks()` |

---

## מה לא בשלב זה

- drag-to-reorder סטטוסים (חצים ↑↓ מספיק)
- התראות push/browser notifications
- @mention ו-log פנימי במשימה — שלב 3
- ניהול permission_groups מתוך הממשק
- חלוקה אוטומטית לנציגים בתור — שלב מאוחר
