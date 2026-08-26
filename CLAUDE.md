# מוקדנט — הקשר לAI

## מה המערכת

**מוקדנט** — מערכת ניהול פנימית למוקד שירות (PHP, RTL, עברית). מורכב מ-MVC מותאם אישית ללא framework חיצוני.

URL ייצור: `https://alon.alexisdeveloping.com`

---

## ארכיטקטורה

```
config/          ← config.php (CFG[]), routes.php, local.php (לא ב-git)
src/Core/        ← DB, Router, Controller, View, Auth, ActivityLog, Mailer
src/Controllers/ ← Controller אחד לכל מודול (23 קבצים)
src/Models/      ← Model אחד לכל מודול (13 קבצים)
src/Services/    ← GlassixService (WhatsApp/CRM)
views/layouts/   ← main.php (layout ראשי עם sidebar + header + חיפוש כללי)
views/pages/     ← עמודים לפי מודול
views/components/← רכיבים משותפים (modals, popups)
public/          ← entry point (index.php), API עצמאית (public/api/*.php — לא עובר דרך Router!)
cron/            ← משימות מתוזמנות (שמות קבצים לא אחידים, ראה למטה)
API/glassix-api/ ← endpoints נפרדים ל-webhook/bug של Glassix (לא src/, לא Router)
```

⚠️ שני תיקיות נוספות בשורש שלא מתועדות בהמשך אך קיימות: `database/`, `docs/`, `includes/`, `digitalSignageBug/` — כדאי לבדוק רלוונטיות לפני עבודה בהן.

### Entry Point

`public/index.php` → `config/bootstrap.php` → `config/routes.php` → `Router::dispatch()`

**חריג חשוב:** `public/api/*.php` (למשל `game-score.php`, `game-leaderboard.php`, `crm/*.php`) הם endpoints עצמאיים שלא עוברים דרך ה-Router — הם עושים `require config/bootstrap.php` ישירות, בודקים `Auth::user()` ו-CSRF ידנית, ומחזירים JSON. תבנית מקבילה לזו של ה-Controllers, בשימוש בעיקר עבור ה-widget של המשחק (BubblePop) בדשבורד.

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
- התחברות/ניהול סיסמה: `AuthController` (`/`, `/login`, `/logout`) ו-`PasswordResetController` (`/set-password`)

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
CFG['tables']['users'] // שמות טבלאות (רק חלק מהטבלאות רשומות שם — ראה בהמשך)
```

`local.php` (לא ב-git) — override לסיסמאות DB, debug mode וכו'.

---

## מודולים

| מודול | Route | Controller | הערות |
|-------|-------|-----------|-------|
| התחברות | `/`, `/login`, `/logout` | AuthController | |
| איפוס סיסמה | `/set-password` | PasswordResetController | כניסה ראשונה / שכחתי סיסמה |
| Dashboard | `/dashboard` | DashboardController | כולל widget משחק BubblePop, calendar-widget |
| חנויות | `/stores` | StoreController | search, toggle, show by id/sNum, sync-work-hours |
| CRM | `/crm` | CrmController | שיחות, הודעות WA, Glassix, notes — API תחת `/api/crm/*` |
| משימות | `/tasks` | TaskController | create, close, status, title; תגובות (drawer עם אנימציית סגירה + קונפטי), `assigned_dept_id` אוטומטי, טוגלים open/closed וall-users |
| הגדרות משימות | `/admin/task-settings` | TaskSettingsController | types + statuses |
| תמיכה | `/support` | SupportController | issues, products |
| משתמשים | `/users` | UserController | perm-groups, reset password, active-list API |
| העדפות | `/preferences` | PreferencesController | נשמר לDB + session |
| אנשי קשר | `/contacts` | ContactController | חיפוש לפי note, העלאת קבצים |
| מנהלי אזור | `/area-managers` | AreaManagerController | assign/unassign לחנויות |
| Wizenet | `/api/wize/*` | WizenetController | API בלבד, ללא עמוד |
| Formatter | `/formatter` | FormatterController | תבניות הודעה לפי מוצר/חנות |
| Nav Manager | `/nav-manager` | NavManagerController | ניהול פריטי ניווט לפי קבוצת הרשאה |
| Nav API | `/api/nav` | NavController | מגיש את הניווט בפועל ל-frontend (controller נפרד מ-NavManagerController) |
| Activity Log | `/activity-log` | ActivityLogController | |
| Automation | `/automation` | AutomationController | CronJobs |
| תורנות (Duty) | `/duty`, `/duty/signage` | DutyController | שיבוץ נציגים שבועי, הנחיות יומיות, **מסך שילוט דיגיטלי (`/duty/signage`)** — עוצב מחדש לאחרונה |
| Lab | `/lab` | LabController | מלאי מעבדה, תנועות, יבוא, דוחות אקסל, לוג משתמשים |
| Invoice Change Name | `/invoice-change-name` | InvoiceChangeNameController | |
| Accounts | `/accounts` | AccountController | סיסמאות תמיכה |

⚠️ `src/Controllers/ProductController.php` קיים אך אין לו route ב-`routes.php` — כנראה קוד legacy/מת, לבדוק לפני מחיקה.

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

### חיפוש כללי (Global Search) — `views/layouts/main.php`

תכונה משמעותית שחיה כולה ב-`main.php` (function בשם `gsAutoSearch` וכו'). זו לא מודול נפרד — היא חלק מה-topbar:

- מחפש חנויות + אנשי קשר במקביל מול ה-API הקיים
- מציג snippet מתוך שדה ה-note כשהחיפוש תאם שם, עם הדגשת הביטוי (`gsHl`)
- badge סגול "נ.מודן" לחנויות מודן (מחליף את מספר החנות, לא נגרר אחרי השם)
- ממיין אנשי קשר מסוג "נותן שירות" (רכש) לסוף הרשימה
- **חשוב:** משתני מערך שמשתנים תוך כדי מיון (`sort`) חייבים להיות `let`, לא `const` — הייתה תקלה מזה בעבר

### Endpoint עצמאי (לא Router) — תבנית חדשה

`public/api/game-score.php`, `public/api/game-leaderboard.php` (ותיקיית `public/api/crm/*` הישנה) עוקפים את ה-Router/Controller לגמרי: `require config/bootstrap.php` ישירות, בדיקת `Auth::user()` + CSRF (מ-header או מגוף ה-JSON), מחזירים JSON. משתמשים ב-`sendBeacon` לשמירה לפני `beforeunload`. **שים לב: לא כל ה-API עובר דרך `routes.php`.**

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

רק חלק מהטבלאות רשומות ב-`CFG['tables']` (config/config.php) — השאר hardcoded בקוד ה-Model/Controller.

**רשומות ב-CFG['tables']:**

| טבלה | תיאור |
|------|-------|
| `users` | משתמשי המערכת (email, password_hash, auth_token, permission_group_id, must_change_password) |
| `permission_groups` | קבוצות הרשאה |
| `permission_group_grants` | הרשאות ספציפיות לקבוצה (permission_key, granted) |
| `stores` | חנויות (sNum, name, is_active) |
| `tasks` | משימות (assigned_user_id, task_type_id, task_status_id, sla_days, is_active, assigned_dept_id) |
| `departments` | מחלקות |
| `nav_items` | פריטי ניווט |
| `nav_permissions` | הרשאות ניווט לקבוצה |
| `lab_inventory_items` / `lab_inventory_movements` / `lab_inventory_logs` | מלאי מעבדה |

**טבלאות נוספות (hardcoded, לא ב-CFG):** `task_types`, `task_statuses` (עם `is_closed`), `task_comments`, `task_watchers`, `accounts`, `activity_log`, `area_managers`, `area_manager_stores`, `automations`, `contacts`, `crm_caller_notes`, `cron_log`, `duty_representatives`, `duty_schedule`, `duty_daily_guidance`, `guidance`, `formatter_templates`, `formatter_fields`, `invoice_change_name`, `mokedAccounts`, `navBar`, `password_reset_tokens`, `pref_value`, `user_preferences`, `supportIssues`, `SupportProducts`, `SupportProductsCategory`, `SupportProductsManufactures`, `game_scores` (BubblePop).

טבלאות V1 (`alon_db`, דרך `DB::v1*`): `CronJob`, `callStatus`, ועוד טבלאות legacy.

---

## Cron

⚠️ שמות הקבצים ב-`cron/` לא אחידים (שונו לאחרונה, "עדכות שמות CRON לריצה אוט") — יש לבדוק את שמות הקבצים בפועל לפני הוספת cron חדש:

```
cron/bootstrap.php                  — bootstrap ייעודי ל-cron
cron/Cron5min.php                   — ריצה כל 5 דקות
cron/Cron1Hr.php                    — ריצה כל שעה
cron/checkTelephoneLine.cron.php    — בדיקת קו טלפון
cron/cronLabToStroe.con.php         — סנכרון מעבדה לחנות
cron/lab.crn.php                    — דוח מעבדה
```

---

## כללים

- PHP 8.1+, `declare(strict_types=1)` בכל קובץ
- אין framework חיצוני (לא Laravel, לא Symfony)
- אין Composer autoload — autoload ידני ב-bootstrap.php
- הסטנדרט: Controller קורא ל-Model, Model מחזיר data, Controller מעביר ל-View
- POST APIs מחזירות JSON, GET pages מחזירות View
- **חריג:** חלק מה-API (`public/api/*.php`) הוא עצמאי ולא עובר Controller/Router — ראה סעיף Frontend למעלה
- ActivityLog::write() לפעולות משמעותיות
