# Invoice Change Name — V2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port the v1 invoice-change-name page to V2 MVC — migration, model, controller, management view, and formatter form integration.

**Architecture:** New `invoice_change_name` table in `alon_db2`. MVC controller/model pair. The management page at `/invoice-change-name` renders three status sections via JS (fetch → render). The create form lives inside the v2 formatter modal and POSTs to an API route. Handler dropdown pulled from `users` in שירות לקוחות / מוקד departments.

**Tech Stack:** PHP 8.x, Vanilla JS, MySQL (PDO via `Core\DB`), `Core\Mailer`, Bootstrap Icons, v2 CSS variables, SheetJS (already loaded in layout)

---

## File Map

| Action | Path |
|---|---|
| Create | `v2/config/migration_invoice_change_name.php` |
| Create | `v2/src/Models/InvoiceChangeNameModel.php` |
| Modify | `v2/src/Models/UserModel.php` |
| Create | `v2/src/Controllers/InvoiceChangeNameController.php` |
| Modify | `v2/config/routes.php` |
| Create | `v2/views/pages/invoice-change-name/index.php` |
| Modify | `v2/views/components/formatter-modal.php` |

---

## Task 1: Migration — create `invoice_change_name` table in `alon_db2`

**Files:**
- Create: `v2/config/migration_invoice_change_name.php`

- [ ] **Step 1: Create migration file**

```php
<?php
declare(strict_types=1);
/**
 * הרץ פעם אחת:  php config/migration_invoice_change_name.php
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
        if (str_contains($e->getMessage(), 'already exists')) {
            echo "– $desc (כבר קיים)\n";
        } else {
            echo "✗ $desc: " . $e->getMessage() . "\n";
        }
    }
}

run($pdo, 'invoice_change_name table',
    "CREATE TABLE IF NOT EXISTS invoice_change_name (
        id                 INT AUTO_INCREMENT PRIMARY KEY,
        open_by_id         INT NOT NULL,
        open_by_name       VARCHAR(100) NOT NULL DEFAULT '',
        new_name           VARCHAR(100) NOT NULL,
        invoice_sap_number VARCHAR(20)  NOT NULL,
        invoice_note       VARCHAR(500) NOT NULL DEFAULT '',
        customer_phone     VARCHAR(30)  NOT NULL DEFAULT '',
        customer_mail      VARCHAR(150) NOT NULL DEFAULT '',
        customer_name      VARCHAR(100) NOT NULL DEFAULT '',
        status             VARCHAR(30)  NOT NULL DEFAULT 'פתוחה',
        care_by            VARCHAR(100) NOT NULL DEFAULT '',
        time_added         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        time_change_status DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        isActive           TINYINT      NOT NULL DEFAULT 1,
        INDEX idx_status (status),
        INDEX idx_invoice (invoice_sap_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

echo "\nהמיגרציה הושלמה.\n";
```

- [ ] **Step 2: Run migration**

```bash
php v2/config/migration_invoice_change_name.php
```

Expected output:
```
✓ invoice_change_name table
המיגרציה הושלמה.
```

- [ ] **Step 3: Commit**

```bash
git add v2/config/migration_invoice_change_name.php
git commit -m "feat: add invoice_change_name migration for alon_db2"
```

---

## Task 2: Model — `InvoiceChangeNameModel` + `UserModel::customerServiceUsers()`

**Files:**
- Create: `v2/src/Models/InvoiceChangeNameModel.php`
- Modify: `v2/src/Models/UserModel.php`

- [ ] **Step 1: Create `InvoiceChangeNameModel.php`**

```php
<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class InvoiceChangeNameModel
{
    private const EDITABLE_FIELDS = [
        'new_name', 'invoice_note', 'invoice_sap_number',
        'customer_name', 'customer_phone', 'customer_mail',
    ];

    public static function all(): array
    {
        return DB::query(
            "SELECT * FROM invoice_change_name
             WHERE isActive = 1
             ORDER BY
               CASE status
                 WHEN 'פתוחה'        THEN 1
                 WHEN 'בהמתנה'       THEN 2
                 WHEN 'תקלה בפרטים' THEN 3
                 WHEN 'טופלה + מייל' THEN 4
                 WHEN 'סגורה'        THEN 5
                 ELSE 6
               END,
               time_change_status DESC"
        );
    }

    public static function byId(int $id): ?array
    {
        return DB::row(
            'SELECT * FROM invoice_change_name WHERE id = ? AND isActive = 1',
            [$id]
        );
    }

    public static function create(array $data): int
    {
        return DB::insert(
            'INSERT INTO invoice_change_name
                (open_by_id, open_by_name, new_name, invoice_sap_number,
                 invoice_note, customer_phone, customer_mail, customer_name)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['open_by_id'],
                $data['open_by_name'],
                $data['new_name'],
                $data['invoice_sap_number'],
                $data['invoice_note'],
                $data['customer_phone'],
                $data['customer_mail'],
                $data['customer_name'],
            ]
        );
    }

    public static function updateStatus(int $id, string $status, string $careBy): bool
    {
        $rows = DB::execute(
            'UPDATE invoice_change_name
             SET status = ?, care_by = ?, time_change_status = NOW()
             WHERE id = ? AND isActive = 1',
            [$status, $careBy, $id]
        );
        return $rows > 0;
    }

    public static function editField(int $id, string $field, string $value): bool
    {
        if (!in_array($field, self::EDITABLE_FIELDS, true)) {
            return false;
        }
        $rows = DB::execute(
            "UPDATE invoice_change_name SET `{$field}` = ? WHERE id = ? AND isActive = 1",
            [$value, $id]
        );
        return $rows > 0;
    }

    public static function checkDuplicate(string $invoiceNum): bool
    {
        $count = DB::value(
            "SELECT COUNT(*) FROM invoice_change_name
             WHERE invoice_sap_number = ? AND isActive = 1
               AND status NOT IN ('סגורה','טופלה + מייל')",
            [$invoiceNum]
        );
        return (int)$count > 0;
    }
}
```

- [ ] **Step 2: Add `customerServiceUsers()` to `UserModel`**

Open `v2/src/Models/UserModel.php` and add this method at the end of the class, before the closing `}`:

```php
    public static function customerServiceUsers(): array
    {
        return DB::query(
            "SELECT u.id, CONCAT(u.first_name,' ',u.last_name) AS name
             FROM users u
             JOIN departments d ON d.id = u.department_id
             WHERE u.is_active = 1
               AND (d.name_heb LIKE '%שירות%' OR d.name_heb LIKE '%מוקד%')
             ORDER BY u.first_name ASC"
        );
    }
```

- [ ] **Step 3: Commit**

```bash
git add v2/src/Models/InvoiceChangeNameModel.php v2/src/Models/UserModel.php
git commit -m "feat: add InvoiceChangeNameModel and UserModel::customerServiceUsers"
```

---

## Task 3: Controller — `InvoiceChangeNameController`

**Files:**
- Create: `v2/src/Controllers/InvoiceChangeNameController.php`

- [ ] **Step 1: Create controller**

```php
<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\Mailer;
use Models\InvoiceChangeNameModel;
use Models\UserModel;

class InvoiceChangeNameController extends Controller
{
    private const MAIL_TO = 'eyal@bug.co.il;alonv@bug.co.il;bat-el@bug.co.il';

    public function index(): void
    {
        $this->requirePermission('canUseInvoiceChangeName');
        $users = UserModel::customerServiceUsers();
        $this->view('pages/invoice-change-name/index', compact('users'));
    }

    public function apiList(): void
    {
        $this->requirePermission('canUseInvoiceChangeName');
        $rows  = InvoiceChangeNameModel::all();
        $users = UserModel::customerServiceUsers();
        $this->json(compact('rows', 'users'));
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $invoiceNum   = trim($this->post('invoice_sap_number', ''));
        $newName      = trim($this->post('new_name', ''));
        $note         = trim($this->post('invoice_note', ''));
        $phone        = trim($this->post('customer_phone', ''));
        $mail         = trim($this->post('customer_mail', ''));
        $customerName = trim($this->post('customer_name', ''));

        if (!ctype_digit($invoiceNum) || strlen($invoiceNum) !== 9) {
            $this->json(['error' => true, 'msg' => 'מספר חשבונית לא תקין — חייב להיות 9 ספרות'], 422);
            return;
        }
        if (mb_strlen($newName) > 50 || $newName === '') {
            $this->json(['error' => true, 'msg' => 'שם חדש חייב להיות בין 1-50 תווים'], 422);
            return;
        }
        if (!ctype_digit($phone)) {
            $this->json(['error' => true, 'msg' => 'מספר טלפון לא תקין'], 422);
            return;
        }
        if ($mail === '') {
            $this->json(['error' => true, 'msg' => 'נא למלא מייל לקוח'], 422);
            return;
        }
        if (InvoiceChangeNameModel::checkDuplicate($invoiceNum)) {
            $this->json(['error' => true, 'msg' => 'קיימת בקשה פתוחה לחשבונית ' . $invoiceNum], 409);
            return;
        }

        $user = Auth::user();
        $id   = InvoiceChangeNameModel::create([
            'open_by_id'         => $user['id'],
            'open_by_name'       => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'new_name'           => $newName,
            'invoice_sap_number' => $invoiceNum,
            'invoice_note'       => $note,
            'customer_phone'     => $phone,
            'customer_mail'      => $mail,
            'customer_name'      => $customerName,
        ]);

        $this->sendCreateMail($invoiceNum, $newName, $note, $customerName, $phone, $mail, $user);

        $this->json(['error' => false, 'msg' => 'בקשת שינוי שם נוספה בהצלחה', 'id' => $id]);
    }

    public function updateStatus(int $id): void
    {
        $this->requirePermission('canUseInvoiceChangeName');
        $this->verifyCsrf();

        $status  = trim($this->post('status', ''));
        $careBy  = trim($this->post('care_by', ''));

        $allowed = ['פתוחה', 'בהמתנה', 'טופלה + מייל', 'סגורה', 'תקלה בפרטים'];
        if (!in_array($status, $allowed, true)) {
            $this->json(['error' => true, 'msg' => 'סטטוס לא תקין'], 422);
            return;
        }

        $ok = InvoiceChangeNameModel::updateStatus($id, $status, $careBy);
        if (!$ok) {
            $this->json(['error' => true, 'msg' => 'לא נמצאה רשומה'], 404);
            return;
        }

        if ($status === 'טופלה + מייל') {
            $row = InvoiceChangeNameModel::byId($id);
            if ($row) {
                $this->sendStatusMail($row, $careBy);
            }
        }

        $this->json(['error' => false, 'msg' => 'סטטוס עודכן: ' . $status]);
    }

    public function editField(int $id): void
    {
        $this->requirePermission('canUseInvoiceChangeName');
        $this->verifyCsrf();

        $field = trim($this->post('field', ''));
        $value = trim($this->post('value', ''));

        $ok = InvoiceChangeNameModel::editField($id, $field, $value);
        if (!$ok) {
            $this->json(['error' => true, 'msg' => 'עדכון נכשל — שדה לא חוקי או רשומה לא נמצאה'], 422);
            return;
        }

        $this->json(['error' => false, 'msg' => 'שדה עודכן בהצלחה']);
    }

    // ── Private mail helpers ────────────────────────────────────────────────

    private function sendCreateMail(
        string $invoiceNum, string $newName, string $note,
        string $customerName, string $phone, string $mail,
        array $user
    ): void {
        $from    = $user['email'] ?? '';
        $uname   = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $subject = "[שינוי שם בחש] לחש: {$invoiceNum} נא לשנות לשם {$newName}";
        $body    = $this->buildMailBody(
            'בקשה לשינוי שם',
            $invoiceNum, $newName, $note, $customerName, $phone, $mail
        );
        $headers  = "From: {$uname} <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "CC: {$from}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        mail(self::MAIL_TO, $subject, $body, $headers);
    }

    private function sendStatusMail(array $row, string $careByUserId): void
    {
        $user    = Auth::user();
        $from    = $user['email'] ?? '';
        $uname   = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $openerMail = \Core\DB::value(
            'SELECT email FROM users WHERE id = ?', [$row['open_by_id']]
        ) ?? '';

        $to      = $openerMail;
        $subject = "[שינוי שם בחש] לחש: {$row['invoice_sap_number']} בוצע";
        $body    = $this->buildMailBody(
            'בוצעה בקשה לשינוי שם',
            $row['invoice_sap_number'], $row['new_name'], $row['invoice_note'],
            $row['customer_name'], $row['customer_phone'], $row['customer_mail']
        );
        $headers  = "From: {$uname} <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        mail($to, $subject, $body, $headers);
    }

    private function buildMailBody(
        string $title,
        string $invoiceNum, string $newName, string $note,
        string $customerName, string $phone, string $mail
    ): string {
        $t  = htmlspecialchars($title);
        $in = htmlspecialchars($invoiceNum);
        $nn = htmlspecialchars($newName);
        $nt = htmlspecialchars($note);
        $cn = htmlspecialchars($customerName);
        $ph = htmlspecialchars($phone);
        $em = htmlspecialchars($mail);

        return <<<HTML
<html lang="he" dir="rtl">
<head><meta charset="utf-8"></head>
<body style="font-family:Tahoma,Arial,sans-serif;text-align:right;direction:rtl;">
  <b style="font-size:18pt;">{$t}</b>
  <br>נא לשנות בחשבונית: <span style="font-size:15pt;">{$in}</span>
  <br>חשבונית חדשה על שם: <span style="font-size:15pt;">{$nn}</span>
  <br>הערת נציג: <span style="font-size:15pt;">{$nt}</span>
  <br><br>
  <p>פרטי לקוח/ה:<br>
    שם: <b>{$cn}</b><br>
    טלפון: <b>{$ph}</b><br>
    מייל: <b>{$em}</b>
  </p>
  <br><span style="color:#999;">מופעל באמצעות מערכת מוקד-נט</span>
</body>
</html>
HTML;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add v2/src/Controllers/InvoiceChangeNameController.php
git commit -m "feat: add InvoiceChangeNameController"
```

---

## Task 4: Routes

**Files:**
- Modify: `v2/config/routes.php`

- [ ] **Step 1: Add routes**

At the end of `v2/config/routes.php` (before the closing `?>` if present, or at end of file), add:

```php
// Invoice Change Name
$router->get ('/invoice-change-name',                      'Controllers\\InvoiceChangeNameController@index');
$router->get ('/api/invoice-change-name',                  'Controllers\\InvoiceChangeNameController@apiList');
$router->post('/api/invoice-change-name/create',           'Controllers\\InvoiceChangeNameController@create');
$router->post('/api/invoice-change-name/{id}/status',      'Controllers\\InvoiceChangeNameController@updateStatus');
$router->post('/api/invoice-change-name/{id}/edit',        'Controllers\\InvoiceChangeNameController@editField');
```

- [ ] **Step 2: Commit**

```bash
git add v2/config/routes.php
git commit -m "feat: register invoice-change-name routes"
```

---

## Task 5: View — Management Page

**Files:**
- Create: `v2/views/pages/invoice-change-name/index.php`

- [ ] **Step 1: Create directory and view**

```php
<?php
use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <div class="page-title">
      <i class="bi bi-receipt-cutoff" style="color:var(--accent);"></i> שינוי שם בחשבונית
    </div>
    <div style="font-size:13px;color:var(--text3);" id="icn-summary">טוען...</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <button class="btn btn-ghost" onclick="icnLoad()">
      <i class="bi bi-arrow-clockwise"></i> רענן
    </button>
    <button class="btn btn-ghost" id="icn-excel-btn" onclick="icnExportExcel()" style="display:none;">
      <i class="bi bi-file-earmark-excel"></i> ייצוא לאקסל
    </button>
  </div>
</div>

<div id="icn-root"></div>

<style>
.icn-section { margin-bottom: 24px; }
.icn-section-hdr {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 16px; border-radius: var(--radius) var(--radius) 0 0;
  font-weight: 700; font-size: 14px;
}
.icn-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.icn-th { padding: 9px 12px; text-align: right; font-weight: 600; font-size: 11px;
          color: var(--text3); border-bottom: 1px solid var(--border);
          background: var(--bg3); white-space: nowrap; }
.icn-td { padding: 9px 12px; vertical-align: middle; border-bottom: 1px solid var(--border); }
.icn-tr:hover td { background: rgba(255,255,255,.025); }
.icn-editable { cursor: pointer; border-bottom: 1px dashed var(--accent); }
.icn-editable:hover { color: var(--accent); }
.icn-status-sel {
  background: var(--bg3); border: 1px solid var(--border); border-radius: 6px;
  padding: 4px 8px; color: var(--text); font-size: 12px; font-family: var(--font);
  cursor: pointer; outline: none;
}
.icn-handler-sel {
  background: var(--bg3); border: 1px solid var(--border); border-radius: 6px;
  padding: 4px 8px; color: var(--text); font-size: 12px; font-family: var(--font);
  min-width: 120px; outline: none;
}
.icn-badge {
  display: inline-block; padding: 2px 9px; border-radius: 12px;
  font-size: 11px; font-weight: 700;
}
.icn-collapse-btn {
  background: none; border: none; cursor: pointer; color: var(--text2);
  font-size: 13px; display: flex; align-items: center; gap: 6px;
  padding: 0;
}
</style>

<script>
const _ICN_BASE  = '<?= $base ?>';
const _ICN_CSRF  = '<?= htmlspecialchars($csrf) ?>';
let _icnData = { rows: [], users: [] };

const STATUS_OPTIONS = ['פתוחה','בהמתנה','תקלה בפרטים','טופלה + מייל','סגורה'];
const STATUS_COLOR   = {
  'פתוחה':         { bg: '#fffde7', text: '#795548', border: '#f9a825' },
  'בהמתנה':        { bg: '#fff3e0', text: '#e65100', border: '#fb8c00' },
  'תקלה בפרטים':   { bg: '#fce4ec', text: '#ad1457', border: '#e91e63' },
  'טופלה + מייל':  { bg: '#e8f5e9', text: '#2e7d32', border: '#43a047' },
  'סגורה':         { bg: '#e8f5e9', text: '#1b5e20', border: '#388e3c' },
};

async function icnLoad() {
  document.getElementById('icn-root').innerHTML =
    '<div style="padding:40px;text-align:center;color:var(--text3);">טוען...</div>';
  try {
    const r = await fetch(_ICN_BASE + '/api/invoice-change-name');
    _icnData = await r.json();
    icnRender();
  } catch(e) {
    document.getElementById('icn-root').innerHTML =
      '<div style="padding:20px;color:var(--danger);">שגיאה בטעינה</div>';
  }
}

function icnRender() {
  const open    = _icnData.rows.filter(r => r.status === 'פתוחה');
  const waiting = _icnData.rows.filter(r => r.status === 'בהמתנה' || r.status === 'תקלה בפרטים');
  const done    = _icnData.rows.filter(r => r.status === 'טופלה + מייל' || r.status === 'סגורה');

  document.getElementById('icn-summary').textContent =
    `פתוחות: ${open.length} | בהמתנה: ${waiting.length} | טופלו: ${done.length}`;

  const showExcel = _icnData.rows.length > 0;
  document.getElementById('icn-excel-btn').style.display = showExcel ? 'inline-flex' : 'none';

  let html = '';
  html += icnSection('פניות פתוחות', open,    '#fffde7', '#f9a825', true);
  html += icnSection('בהמתנה',        waiting, '#fff3e0', '#fb8c00', true);
  html += icnSectionCollapsible('טופלו / סגורות', done, '#e8f5e9', '#43a047');

  document.getElementById('icn-root').innerHTML = html;
}

function icnSection(title, rows, bg, border, editable) {
  return `
    <div class="icn-section card" style="padding:0;overflow:hidden;margin-bottom:20px;">
      <div class="icn-section-hdr" style="background:${bg};border-bottom:2px solid ${border};">
        <span style="color:${border};font-size:16px;">●</span>
        ${_ife(title)} <span style="font-size:12px;font-weight:400;color:var(--text3);">(${rows.length})</span>
      </div>
      ${rows.length === 0
        ? `<div style="padding:20px;text-align:center;color:var(--text3);font-size:13px;">אין רשומות</div>`
        : `<div style="overflow-x:auto;">${icnTable(rows, editable)}</div>`}
    </div>`;
}

function icnSectionCollapsible(title, rows, bg, border) {
  return `
    <div class="icn-section card" style="padding:0;overflow:hidden;">
      <div class="icn-section-hdr" style="background:${bg};border-bottom:2px solid ${border};cursor:pointer;"
           onclick="document.getElementById('icn-done-body').style.display=
             document.getElementById('icn-done-body').style.display==='none'?'block':'none';">
        <span style="color:${border};font-size:16px;">●</span>
        ${_ife(title)} <span style="font-size:12px;font-weight:400;color:var(--text3);">(${rows.length})</span>
        <i class="bi bi-chevron-down" style="margin-right:auto;font-size:12px;color:var(--text3);"></i>
      </div>
      <div id="icn-done-body" style="display:none;">
        ${rows.length === 0
          ? `<div style="padding:20px;text-align:center;color:var(--text3);font-size:13px;">אין רשומות</div>`
          : `<div style="overflow-x:auto;">${icnTable(rows, false)}</div>`}
      </div>
    </div>`;
}

function icnTable(rows, editable) {
  let h = `<table class="icn-table">
    <thead><tr>
      <th class="icn-th">זמן פתיחה</th>
      <th class="icn-th">נפתח ע"י</th>
      <th class="icn-th">חשבונית סאפ</th>
      <th class="icn-th">שם חדש</th>
      <th class="icn-th">הערה</th>
      <th class="icn-th">סטטוס</th>
      <th class="icn-th">זמן עדכון</th>
      <th class="icn-th">לטיפול ע"י</th>
    </tr></thead><tbody>`;

  rows.forEach(row => {
    const sc = STATUS_COLOR[row.status] || {};
    const badge = `<span class="icn-badge" style="background:${sc.bg||'var(--bg3)'};color:${sc.text||'var(--text2)'};border:1px solid ${sc.border||'var(--border)'};">${_ife(row.status)}</span>`;

    const nameTd = editable
      ? `<td class="icn-td icn-editable" onclick="icnEditField(${row.id},'new_name',this)">${_ife(row.new_name)}</td>`
      : `<td class="icn-td">${_ife(row.new_name)}</td>`;

    const noteTd = editable
      ? `<td class="icn-td icn-editable" onclick="icnEditField(${row.id},'invoice_note',this)">${_ife(row.invoice_note||'—')}</td>`
      : `<td class="icn-td">${_ife(row.invoice_note||'—')}</td>`;

    const statusTd = editable
      ? `<td class="icn-td">${icnStatusSelect(row)}</td>`
      : `<td class="icn-td">${badge}</td>`;

    const handlerTd = editable
      ? `<td class="icn-td">${icnHandlerSelect(row)}</td>`
      : `<td class="icn-td">${_ife(row.care_by||'—')}</td>`;

    h += `<tr class="icn-tr" data-id="${row.id}">
      <td class="icn-td" style="white-space:nowrap;font-size:12px;">${_ife(row.time_added)}</td>
      <td class="icn-td">${_ife(row.open_by_name)}</td>
      <td class="icn-td" style="font-family:monospace;">${_ife(row.invoice_sap_number)}</td>
      ${nameTd}
      ${noteTd}
      ${statusTd}
      <td class="icn-td" style="white-space:nowrap;font-size:12px;color:var(--text3);">${_ife(row.time_change_status)}</td>
      ${handlerTd}
    </tr>`;
  });

  h += '</tbody></table>';
  return h;
}

function icnStatusSelect(row) {
  let h = `<select class="icn-status-sel" onchange="icnUpdateStatus(${row.id},this)"
              data-row-id="${row.id}">`;
  STATUS_OPTIONS.forEach(s => {
    h += `<option value="${_ife(s)}" ${row.status===s?'selected':''}>${_ife(s)}</option>`;
  });
  h += '</select>';
  return h;
}

function icnHandlerSelect(row) {
  let h = `<select class="icn-handler-sel" id="icn-handler-${row.id}">
    <option value="">— בחר מטפל —</option>`;
  (_icnData.users||[]).forEach(u => {
    const sel = row.care_by === u.name ? 'selected' : '';
    h += `<option value="${_ife(u.name)}" ${sel}>${_ife(u.name)}</option>`;
  });
  h += '</select>';
  return h;
}

async function icnUpdateStatus(id, sel) {
  const status  = sel.value;
  const handler = document.getElementById('icn-handler-' + id);
  const careBy  = handler ? handler.value : '';

  const fd = new FormData();
  fd.append('_csrf', _ICN_CSRF);
  fd.append('status', status);
  fd.append('care_by', careBy);

  try {
    const r   = await fetch(_ICN_BASE + '/api/invoice-change-name/' + id + '/status', { method:'POST', body:fd });
    const res = await r.json();
    v2Toast(res.msg || (res.error ? 'שגיאה' : 'עודכן'));
    if (!res.error) icnLoad();
  } catch(e) {
    v2Toast('שגיאת רשת');
  }
}

async function icnEditField(id, field, cell) {
  const current = cell.textContent === '—' ? '' : cell.textContent;
  const val = prompt('עריכת שדה:', current);
  if (val === null) return;

  const fd = new FormData();
  fd.append('_csrf', _ICN_CSRF);
  fd.append('field', field);
  fd.append('value', val.trim());

  try {
    const r   = await fetch(_ICN_BASE + '/api/invoice-change-name/' + id + '/edit', { method:'POST', body:fd });
    const res = await r.json();
    v2Toast(res.msg || (res.error ? 'שגיאה' : 'עודכן'));
    if (!res.error) { cell.textContent = val.trim() || '—'; }
  } catch(e) {
    v2Toast('שגיאת רשת');
  }
}

function icnExportExcel() {
  const rows = _icnData.rows;
  if (!rows.length) return;
  const headers = ['זמן פתיחה','נפתח ע"י','חשבונית סאפ','שם חדש','הערה','סטטוס','זמן עדכון','מטפל'];
  const data = [headers, ...rows.map(r => [
    r.time_added, r.open_by_name, r.invoice_sap_number,
    r.new_name, r.invoice_note, r.status, r.time_change_status, r.care_by
  ])];
  const ws = XLSX.utils.aoa_to_sheet(data);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'שינוי שם');
  const now = new Date();
  const d = String(now.getDate()).padStart(2,'0');
  const m = String(now.getMonth()+1).padStart(2,'0');
  const y = now.getFullYear();
  XLSX.writeFile(wb, `${d}-${m}-${y} שינוי שם בחשבונית.xlsx`);
}

function _ife(s) {
  if (s === null || s === undefined) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

icnLoad();
</script>
```

- [ ] **Step 2: Commit**

```bash
git add v2/views/pages/invoice-change-name/index.php
git commit -m "feat: add invoice-change-name management view"
```

---

## Task 6: Formatter Modal Integration

**Files:**
- Modify: `v2/views/components/formatter-modal.php`

The formatter modal needs two additions:
1. A hidden invoice-change-name form injected below the actions row when a template has a special marker field (`invoice_change_name` type).
2. JS that handles submit and sends to `/api/invoice-change-name/create`.

- [ ] **Step 1: Add the invoice form HTML**

In `v2/views/components/formatter-modal.php`, find the closing `</div><!-- /fmt-tpl-ui -->` comment (around line 113) and add the form **before** it:

```html
          <!-- invoice change name form (shown when template has invoice_change_name field) -->
          <div id="fmt-icn-form" style="display:none;margin-top:14px;border-top:1px solid var(--border);padding-top:14px;">
            <fieldset style="border:1px solid var(--border);border-radius:var(--radius);padding:14px;">
              <legend style="padding:0 8px;font-size:13px;font-weight:700;color:var(--text2);">בקשת שינוי שם בחשבונית</legend>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                <div>
                  <label class="fmt-lbl">מספר חשבונית סאפ <span style="color:var(--danger);">*</span></label>
                  <input id="fmt-icn-invoice" class="fmt-inp" type="text" autocomplete="off" placeholder="9 ספרות">
                </div>
                <div>
                  <label class="fmt-lbl">שם חדש על-גבי החשבונית <span style="color:var(--danger);">*</span></label>
                  <input id="fmt-icn-newname" class="fmt-inp" type="text" placeholder="עד 50 תווים">
                </div>
              </div>
              <div style="margin-bottom:10px;">
                <label class="fmt-lbl">הערה (לא חשוף ללקוח) — לא חובה</label>
                <input id="fmt-icn-note" class="fmt-inp" type="text">
              </div>
              <button type="button" id="fmt-icn-submit-btn"
                      onclick="fmtIcnSubmit()"
                      style="background:#f9a825;color:#000;border:none;border-radius:8px;
                             padding:9px 20px;font-weight:700;cursor:pointer;font-size:14px;
                             font-family:var(--font);">
                <i class="bi bi-send"></i> שלח בקשת שינוי שם
              </button>
            </fieldset>
          </div>
```

- [ ] **Step 2: Add JS for the invoice form**

In the `<script>` block of `formatter-modal.php`, add these functions after the existing JS (before the closing `</script>`):

```js
/* ── Invoice Change Name integration ────────────────────────────────────── */
function _fmtShowIcnForm(tpl) {
  // show the form only if template name/category signals it
  const show = tpl && tpl.name && tpl.name.includes('שינוי שם');
  document.getElementById('fmt-icn-form').style.display = show ? 'block' : 'none';
}

async function fmtIcnSubmit() {
  const invoiceNum   = document.getElementById('fmt-icn-invoice').value.trim();
  const newName      = document.getElementById('fmt-icn-newname').value.trim();
  const note         = document.getElementById('fmt-icn-note').value.trim();
  const customerName = document.getElementById('fmt-cname').value.trim();
  const phone        = document.getElementById('fmt-cphone').value.trim().replace(/-/g,'');
  // look for email field in dynamic fields
  const mailEl = document.querySelector('#fmt-dyn-fields input[type="email"]') ||
                 document.getElementById('fmt-f-email') ||
                 document.getElementById('fmt-f-costumerMail');
  const mail   = mailEl ? mailEl.value.trim() : '';

  const errors = [];
  if (!/^\d{9}$/.test(invoiceNum))    errors.push('מספר חשבונית חייב להיות 9 ספרות');
  if (!newName || newName.length > 50) errors.push('שם חדש: 1-50 תווים');
  if (!/^\d+$/.test(phone))           errors.push('טלפון לא תקין');
  if (!mail)                           errors.push('נא להזין מייל לקוח');
  if (errors.length) { alert(errors.join('\n')); return; }

  if (!confirm("האם לשלוח בקשת שינוי שם עפ\"י הפרטים שציינת?")) return;

  const btn = document.getElementById('fmt-icn-submit-btn');
  btn.disabled = true;
  btn.textContent = 'שולח...';

  const csrf = typeof _FMT_CSRF !== 'undefined' ? _FMT_CSRF : '';
  const fd   = new FormData();
  fd.append('_csrf',              csrf);
  fd.append('invoice_sap_number', invoiceNum);
  fd.append('new_name',           newName);
  fd.append('invoice_note',       note);
  fd.append('customer_name',      customerName);
  fd.append('customer_phone',     phone);
  fd.append('customer_mail',      mail);

  try {
    const r   = await fetch(_FBASE + '/api/invoice-change-name/create', { method:'POST', body:fd });
    const res = await r.json();
    if (res.error) {
      alert(res.msg || 'שגיאה בשליחה');
    } else {
      if (typeof v2Toast === 'function') v2Toast(res.msg || 'נשלח בהצלחה');
      document.getElementById('fmt-icn-invoice').value = '';
      document.getElementById('fmt-icn-newname').value = '';
      document.getElementById('fmt-icn-note').value    = '';
    }
  } catch(e) {
    alert('שגיאת רשת — נסה שוב');
  } finally {
    btn.disabled    = false;
    btn.textContent = 'שלח בקשת שינוי שם';
  }
}
```

- [ ] **Step 3: Wire `_fmtShowIcnForm` into `_fmtSelect`**

In the existing `_fmtSelect` function (find the line `await _fmtBuildDynFields();`), add a call right after it:

```js
  await _fmtBuildDynFields();
  _fmtShowIcnForm(_fmtCur);   // ← add this line
  fmtPreview();
```

Also add the CSRF constant near the top of the script block (after `const _FBASE = ...`):

```js
const _FMT_CSRF = '<?= $csrf ?>';
```

- [ ] **Step 4: Commit**

```bash
git add v2/views/components/formatter-modal.php
git commit -m "feat: add invoice-change-name form in formatter modal"
```

---

## Task 7: Verify end-to-end

- [ ] **Step 1: Check the management page loads**

Navigate to `/invoice-change-name` (must have `canUseInvoiceChangeName` permission).  
Expected: page loads with three sections (פתוחות / בהמתנה / טופלו). If empty, sections show "אין רשומות".

- [ ] **Step 2: Test create via formatter modal**

Open formatter modal, select a template whose name contains `שינוי שם`.  
Expected: the invoice form appears below the regular actions.  
Fill in: 9-digit invoice number, new name ≤50 chars, phone (digits), email.  
Click submit → confirm → success toast.  
Refresh management page → new row appears under פתוחות.

- [ ] **Step 3: Test status update**

On management page, change status of a row from `פתוחה` to `בהמתנה`.  
Expected: `v2Toast` success, row moves to בהמתנה section on next load.

Change to `טופלה + מייל` → opener receives email.

- [ ] **Step 4: Test inline edit**

Click on a name or note cell (editable, dashed underline).  
Expected: `prompt()` opens with current value.  
Change value → submit → cell updates without full reload.

- [ ] **Step 5: Test Excel export**

Click ייצוא לאקסל → file downloads with all rows.

- [ ] **Step 6: Test validation**

Submit form with 8-digit invoice → alert about 9 digits.  
Submit with name > 50 chars → alert.  
Submit with non-numeric phone → alert.  
Submit duplicate (already open) invoice number → error toast from server.

- [ ] **Step 7: Final commit if any tweaks**

```bash
git add -A
git commit -m "fix: invoice-change-name e2e verification tweaks"
```
