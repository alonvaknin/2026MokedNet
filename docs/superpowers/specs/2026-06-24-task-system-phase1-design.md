# מערכת משימות — שלב 1: תשתית + אינטגרציה עם שינוי שם בחשבונית

**תאריך:** 2026-06-24  
**סטטוס:** מאושר לפיתוח

---

## סקירה

מערכת המשימות הקיימת (`tasks` / `TaskController` / `TaskModel`) היא שלד בסיסי בלבד. בשלב הזה נוסיף:

1. **סוגי משימות** (`task_types`) — מנהל מגדיר סוג, SLA ברירת מחדל, ו-assignees/watchers ברירת מחדל כ-JSON.
2. **סטטוסים גמישים** (`task_statuses`) — במקום `is_active` בינארי, כל סוג משימה מחזיק רשימת סטטוסים עם צבע וסדר.
3. **Watchers** (`task_watchers`) — משתמשים שעוקבים אחרי משימה (ללא שיוך פעיל).
4. **קישור לשינוי שם** — כאשר נציג יוצר בקשת שינוי שם בחשבונית, נוצרת אוטומטית משימה עם `source_type='invoice_change_name'` ו-`source_id=<id>`.

מערכת שינוי השם (`invoice_change_name`) **נשארת ללא שינוי** — המשימה היא overlay.

---

## סכמת DB

### טבלה: `task_types`

```sql
CREATE TABLE task_types (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(100) NOT NULL,
    sla_days             INT          NOT NULL DEFAULT 3,
    default_assignee_ids JSON         NOT NULL DEFAULT '[]',
    default_watcher_ids  JSON         NOT NULL DEFAULT '[]',
    -- ערך 0 ב-watcher_ids = "עצמי" (מי שפתח את המשימה)
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**שורת ברירת מחדל לשלב זה:**
```sql
INSERT INTO task_types (name, sla_days, default_assignee_ids, default_watcher_ids)
VALUES ('שינוי שם בחשבונית', 3, '[<eyal_id>]', '[0]');
-- 0 = עצמי (פותח הבקשה)
```

### טבלה: `task_statuses`

```sql
CREATE TABLE task_statuses (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    task_type_id INT          NOT NULL,
    name         VARCHAR(50)  NOT NULL,
    color        VARCHAR(20)  NOT NULL DEFAULT '#4f7fff',
    sort_order   INT          NOT NULL DEFAULT 0,
    FOREIGN KEY (task_type_id) REFERENCES task_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**סטטוסים לסוג "שינוי שם בחשבונית":**
- פתוח (ירוק `#22c55e`, sort 1)
- בטיפול (כתום `#f97316`, sort 2)
- ממתין (אפור `#6b7280`, sort 3)
- סגור (כחול `#4f7fff`, sort 4)

### שינויים ב-`tasks`

```sql
ALTER TABLE tasks
    ADD COLUMN task_type_id  INT     NULL AFTER id,
    ADD COLUMN status_id     INT     NULL AFTER task_type_id,
    ADD COLUMN source_type   VARCHAR(50) NULL AFTER assigned_dept_id,
    ADD COLUMN source_id     INT     NULL AFTER source_type,
    ADD FOREIGN KEY (task_type_id) REFERENCES task_types(id),
    ADD FOREIGN KEY (status_id)    REFERENCES task_statuses(id);
```

`is_active` נשאר לתאימות אחורה — סגירת משימה תעדכן גם אותו.

### טבלה: `task_watchers`

```sql
CREATE TABLE task_watchers (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    UNIQUE KEY uq_task_user (task_id, user_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## לוגיקת יצירת משימה אוטומטית

כאשר `InvoiceChangeNameController::create()` מצליח ויוצר רשומה חדשה:

1. טוען את `task_type` ששמו `'שינוי שם בחשבונית'`.
2. כותרת אוטומטית: `"שינוי שם בחש' {invoiceNum} → {newName}"`.
3. assignees = `default_assignee_ids` מהטיפוס.
4. watchers = `default_watcher_ids` — כאשר `0` מוחלף ב-`$user['id']` (הנציג הפותח).
5. `status_id` = הסטטוס הראשון (sort_order=1) של הטיפוס.
6. `source_type = 'invoice_change_name'`, `source_id = $invoiceChangeNameId`.

המשימה הראשונה מוקצית ל-assignee הראשון. אם יש כמה assignees — משימה אחת לכל אחד (phase 1: פשוט, רק assignee אחד).

---

## שינויי קוד

### `TaskModel`
- `forUser()` — מוסיפים JOIN ל-`task_statuses` ול-`task_types`, מחזירים `status_name`, `status_color`, `type_name`.
- `create()` — מקבל `task_type_id`, `status_id`, `source_type`, `source_id`.
- `createFromSource()` — מתודה חדשה: מקבלת `task_type_id` + `source_type` + `source_id` + `open_by` + כותרת, קוראת ל-`create()` ומוסיפה watchers.
- `addWatcher()` — INSERT IGNORE ל-`task_watchers`.

### `InvoiceChangeNameController::create()`
- אחרי שורת `ActivityLog::create(...)`, קוראת ל-`TaskModel::createFromSource()`.

### `TaskController`
- `index()` — מחזיר גם `task_types` ו-`task_statuses` לתצוגה.
- `updateStatus()` — endpoint חדש `POST /tasks/{id}/status` לשינוי status_id.
- `close()` — מעדכן גם `is_active=0`.

### `routes.php`
```php
$router->post('/tasks/{id}/status', 'Controllers\\TaskController@updateStatus');
```

---

## Migration

קובץ: `config/migration_task_system_v1.php`

מריץ בסדר:
1. CREATE `task_types`
2. CREATE `task_statuses`
3. ALTER `tasks` (ADD COLUMNs)
4. CREATE `task_watchers`
5. INSERT סוג "שינוי שם בחשבונית" + סטטוסים
6. (ידני) UPDATE `task_types` עם ה-ID הנכון של אייל גואטה

---

## UI — שלב 1

### `/tasks` (עדכון לתצוגה קיימת)
- עמודת **סטטוס** עם badge צבעוני (שם הסטטוס + צבע מ-DB).
- לחיצה על badge פותחת dropdown עם הסטטוסים הזמינים לסוג המשימה — שינוי מיידי ב-AJAX.
- עמודת **סוג משימה** (אם קיים).
- אם `source_type = 'invoice_change_name'` — כפתור "צפה בבקשה" שמקשר ל-`/invoice-change-name`.

### כותרת ניתנת לעריכה
- double-click על כותרת → inline edit → `PATCH /tasks/{id}/title` (endpoint חדש).

---

## מה לא בשלב זה

- UI ניהול `task_types` ו-`task_statuses` (מנהל) — שלב 2
- מנגנון `@mention` + מייל — שלב 3
- פיד הערות/log בתוך משימה — שלב 3
- אנימציית Monday בשינוי סטטוס — שלב 2
- חלוקה אוטומטית לנציגים בתור — שלב מאוחר
- התראות גלובליות על SLA — שלב 2
