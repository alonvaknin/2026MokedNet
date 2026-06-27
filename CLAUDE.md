# מוקדנט — הקשר לAI

## מה המערכת

**מוקדנט** — מערכת ניהול פנימית למוקד שירות (PHP, RTL, עברית). מורכב מ-MVC מותאם אישית ללא framework חיצוני.

URL ייצור: `https://alon.alexisdeveloping.com`

---

## ארכיטקטורה

```
config/          ← config.php (CFG[]), routes.php, local.php (לא ב-git)
src/Core/        ← DB, Router, Controller, View, Auth, ActivityLog, Mailer
src/Controllers/ ← Controller אחד לכל מודול
src/Models/      ← Model אחד לכל מודול
src/Services/    ← GlassixService (WhatsApp/CRM)
views/layouts/   ← main.php (layout ראשי עם sidebar + header)
views/pages/     ← עמודים לפי מודול
views/components/← רכיבים משותפים (modals, popups)
public/          ← entry point (index.php), API עצמאית (public/api/)
cron/            ← cron_5min.php, cron_1hr.php, cron_telephone.php
```

### Entry Point

`public/index.php` → `config/bootstrap.php` → `config/routes.php` → `Router::dispatch()`

### DB

שתי מסדי נתונים:
- `DB::*` — `alon_db2` (V2, ראשי)
- `DB::v1*` — `alon_db` (V1, לטבלאות משותפות: CronJob, callStatus)

Helper methods: `DB::query()`, `DB::row()`, `DB::value()`, `DB::execute()`, `DB::insert()` (ומקבילות v1).

### Auth

- `Auth::check()` — מאמת session + token מול DB
- `Auth::can('permissionKey')` — בודק הרשאה, cached ב-session
- `Auth::user()` — מחזיר פרטי משתמש מחובר
- `$this->requireAuth()` — בכל controller מוגן
- `$this->requirePermission('key')` — כולל auth
- CSRF: `$this->verifyCsrf()` בכל POST, token בheader `X-CSRF-TOKEN` או `_csrf` בPOST

### View / Controller

```php
// Controller
$this->view('pages/module/index', ['key' => $val]);
$this->json(['ok' => true]);
$this->redirect('/path');

// View
View::e($val)           // htmlspecialchars
View::component('name', $data)  // views/components/name.php
$content                // injected by layout
```

### Config

```php
CFG['app']['name']   // מוקדנט
CFG['app']['url']    // base URL
CFG['db']            // V2 credentials
CFG['db_v1']         // V1 credentials
CFG['tables']['users'] // שמות טבלאות
```

`local.php` (לא ב-git) — override לסיסמאות DB, debug mode וכו'.

---

## מודולים

| מודול | Route | Controller | הערות |
|-------|-------|-----------|-------|
| Dashboard | `/dashboard` | DashboardController | |
| חנויות | `/stores` | StoreController | search, toggle, show by id/sNum |
| CRM | `/crm` | CrmController | שיחות, הודעות WA, Glassix, notes |
| משימות | `/tasks` | TaskController | create, close, status, title, comments |
| הגדרות משימות | `/admin/task-settings` | TaskSettingsController | types + statuses |
| תמיכה | `/support` | SupportController | issues, products |
| משתמשים | `/users` | UserController | perm-groups, reset password |
| העדפות | `/preferences` | PreferencesController | נשמר לDB + session |
| אנשי קשר | `/contacts` | ContactController | |
| מנהלי אזור | `/area-managers` | AreaManagerController | assign/unassign לחנויות |
| Formatter | `/formatter` | FormatterController | תבניות הודעה לפי מוצר/חנות |
| Nav Manager | `/nav-manager` | NavManagerController | ניהול ניווט לפי קבוצת הרשאה |
| Activity Log | `/activity-log` | ActivityLogController | |
| Automation | `/automation` | AutomationController | CronJobs |
| Duty | `/duty` | DutyController | תורנות נציגים, שיבוץ שבועי |
| Lab | `/lab` | LabController | מלאי מעבדה, תנועות, דוחות |
| Invoice Change Name | `/invoice-change-name` | InvoiceChangeNameController | |
| Accounts | `/accounts` | AccountController | סיסמאות תמיכה |

---

## Frontend

- **RTL Hebrew** — כל ה-UI בעברית, `dir="rtl"`
- **Dark theme** — CSS variables ב-`main.php` (`:root { --bg, --accent, ... }`)
- **Sidebar** — collapsible, ניווט דינמי מDB, hover-expand כשמקופל
- **JavaScript** — vanilla JS, אין framework. AJAX עם `fetch()`.
- **Bootstrap Icons** — CDN (`bi-*`)
- **Google Fonts** — Assistant, Heebo, Rubik (Hebrew-first)
- CSRF token זמין ב-`window.__CSRF` (מוזרק ב-layout)
- Base URL זמין ב-`window.__V2_BASE`

### Patterns JS נפוצים

```js
// AJAX POST
fetch(`${window.__V2_BASE}/api/endpoint`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': window.__CSRF, 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
})

// Flash message (toast)
showToast('הודעה', 'success'); // success | error | warning
```

---

## מסד נתונים — טבלאות מרכזיות (V2 / alon_db2)

| טבלה | תיאור |
|------|-------|
| `users` | משתמשי המערכת (email, password_hash, auth_token, permission_group_id, must_change_password) |
| `permission_groups` | קבוצות הרשאה |
| `permission_group_grants` | הרשאות ספציפיות לקבוצה (permission_key, granted) |
| `stores` | חנויות (sNum, name, is_active) |
| `tasks` | משימות (assigned_user_id, task_type_id, task_status_id, sla_days, is_active) |
| `task_types` | סוגי משימות |
| `task_statuses` | סטטוסי משימות (is_closed) |
| `task_comments` | תגובות למשימות |
| `nav_items` | פריטי ניווט |
| `nav_permissions` | הרשאות ניווט לקבוצה |
| `lab_inventory_items` / `lab_inventory_movements` / `lab_inventory_logs` | מלאי מעבדה |

טבלאות V1 (alon_db): `CronJob`, `callStatus`, ועוד טבלאות legacy.

---

## כללים

- PHP 8.1+, `declare(strict_types=1)` בכל קובץ
- אין framework חיצוני (לא Laravel, לא Symfony)
- אין Composer autoload — autoload ידני ב-bootstrap.php
- הסטנדרט: Controller קורא ל-Model, Model מחזיר data, Controller מעביר ל-View
- POST APIs מחזירות JSON, GET pages מחזירות View
- ActivityLog::write() לפעולות משמעותיות
