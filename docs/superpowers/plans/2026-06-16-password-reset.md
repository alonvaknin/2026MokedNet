# Password Reset Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a token-based password reset flow: admin sends email with a secure link, user sets their own password via that link; new users also receive this link instead of having a password set for them.

**Architecture:** A `password_reset_tokens` table stores single-use tokens (64-char hex, 2-hour TTL). `Mailer` sends styled RTL HTML emails via PHP `mail()`. `PasswordResetController` handles token validation and password setting. Admin UI sends the email instead of setting a password directly.

**Tech Stack:** PHP 8, MySQL, PHP `mail()`, existing Controller/DB/View patterns from v2.

---

## File Map

| File | Action |
|------|--------|
| `config/migration_password_reset.php` | Create — DB migration for tokens table |
| `src/Core/Mailer.php` | Create — HTML email sender via mail() |
| `src/Controllers/PasswordResetController.php` | Create — show form + process reset |
| `views/pages/set-password.php` | Create — standalone password-setting page |
| `config/routes.php` | Modify — add 3 new routes, remove old reset-password route |
| `src/Controllers/UserController.php` | Modify — save() removes password logic, resetPassword() replaced by sendResetEmail() |
| `views/pages/users/index.php` | Modify — remove password tab/fields, add send-reset button per row |
| `views/pages/login.php` | Modify — add "שכחתי סיסמא" with contact-admin message |

---

## Task 1: DB Migration — password_reset_tokens

**Files:**
- Create: `v2/config/migration_password_reset.php`

- [ ] **Step 1: Create the migration file**

```php
<?php
// config/migration_password_reset.php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Core\DB;

DB::execute("
    CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id    INT UNSIGNED NOT NULL,
        token      CHAR(64)     NOT NULL UNIQUE,
        expires_at DATETIME     NOT NULL,
        used_at    DATETIME     NULL DEFAULT NULL,
        created_at DATETIME     NOT NULL DEFAULT NOW(),
        INDEX idx_token (token),
        INDEX idx_user  (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

echo "Migration complete: password_reset_tokens created.\n";
```

- [ ] **Step 2: Run the migration**

```bash
php v2/config/migration_password_reset.php
```

Expected output: `Migration complete: password_reset_tokens created.`

- [ ] **Step 3: Commit**

```bash
git add v2/config/migration_password_reset.php
git commit -m "feat: add password_reset_tokens migration"
```

---

## Task 2: Mailer Service

**Files:**
- Create: `v2/src/Core/Mailer.php`

- [ ] **Step 1: Create Mailer class**

```php
<?php
// src/Core/Mailer.php
declare(strict_types=1);

namespace Core;

class Mailer
{
    private const FROM_NAME    = 'מוקד-נט';
    private const FROM_ADDRESS = 'moked-net-noreply@alexisdeveloping.com';

    /**
     * Send a password-set/reset link to a user.
     *
     * @param string $toEmail   Recipient email
     * @param string $toName    Recipient display name
     * @param string $resetUrl  Full URL with token
     * @param bool   $isNew     true = new user (קביעת סיסמא), false = reset
     */
    public static function sendPasswordReset(
        string $toEmail,
        string $toName,
        string $resetUrl,
        bool $isNew = false
    ): bool {
        $appName = CFG['app']['name'] ?? 'מוקד-נט';
        $subject = $isNew
            ? "[{$appName}] קביעת סיסמא למשתמש חדש"
            : "[{$appName}] איפוס סיסמא";

        $actionLabel = $isNew ? 'קביעת סיסמא' : 'איפוס סיסמא';
        $greeting    = $isNew
            ? "חשבון משתמש חדש נוצר עבורך במערכת <b>{$appName}</b>."
            : "קיבלנו בקשה לאיפוס הסיסמא שלך במערכת <b>{$appName}</b>.";

        $message  = '<!DOCTYPE html>';
        $message .= '<html lang="he" dir="rtl">';
        $message .= '<head><meta charset="utf-8"><title>' . htmlspecialchars($subject) . '</title></head>';
        $message .= '<body style="font-family:Tahoma,Arial,sans-serif;background:#0f1117;color:#e8eaf0;';
        $message .= 'direction:rtl;text-align:right;margin:0;padding:0;">';
        $message .= '<table width="100%" cellpadding="0" cellspacing="0" style="background:#0f1117;padding:32px 0;">';
        $message .= '<tr><td align="center">';
        $message .= '<table width="520" cellpadding="0" cellspacing="0" style="background:#181b23;';
        $message .= 'border:1px solid rgba(255,255,255,.08);border-radius:12px;overflow:hidden;">';

        // Header
        $message .= '<tr><td style="background:#4f7fff;padding:24px 32px;text-align:right;">';
        $message .= '<span style="font-size:24px;font-weight:700;color:#fff;">' . htmlspecialchars($appName) . '</span>';
        $message .= '<span style="font-size:14px;color:rgba(255,255,255,.75);margin-right:12px;">מערכת ניהול פנים-ארגונית</span>';
        $message .= '</td></tr>';

        // Body
        $message .= '<tr><td style="padding:32px;">';
        $message .= '<p style="font-size:16px;margin:0 0 12px;">שלום, <b>' . htmlspecialchars($toName) . '</b></p>';
        $message .= '<p style="font-size:14px;color:#b0b3c6;margin:0 0 24px;">' . $greeting . '</p>';
        $message .= '<p style="font-size:14px;color:#b0b3c6;margin:0 0 24px;">';
        $message .= 'לחץ/י על הכפתור הבא ל' . $actionLabel . '. הקישור בתוקף למשך <b>שעתיים</b>.</p>';

        // CTA Button
        $message .= '<table cellpadding="0" cellspacing="0" style="margin:0 0 28px;">';
        $message .= '<tr><td style="background:#4f7fff;border-radius:8px;padding:0;">';
        $message .= '<a href="' . htmlspecialchars($resetUrl) . '" ';
        $message .= 'style="display:block;padding:12px 28px;color:#fff;font-size:15px;';
        $message .= 'font-weight:600;text-decoration:none;">' . $actionLabel . ' ←</a>';
        $message .= '</td></tr></table>';

        // Fallback URL
        $message .= '<p style="font-size:12px;color:#5a5e78;margin:0 0 8px;">אם הכפתור לא עובד, העתק את הקישור הבא:</p>';
        $message .= '<p style="font-size:12px;color:#4f7fff;word-break:break-all;margin:0 0 24px;">';
        $message .= htmlspecialchars($resetUrl) . '</p>';

        $message .= '<hr style="border:none;border-top:1px solid rgba(255,255,255,.08);margin:0 0 20px;">';
        $message .= '<p style="font-size:12px;color:#5a5e78;margin:0;">אם לא ביקשת פעולה זו, ניתן להתעלם ממייל זה.</p>';
        $message .= '</td></tr>';

        // Footer
        $message .= '<tr><td style="background:#13161e;padding:16px 32px;text-align:right;">';
        $message .= '<span style="font-size:12px;color:#5a5e78;">מופעל באמצעות מערכת ' . htmlspecialchars($appName) . '</span>';
        $message .= '</td></tr>';

        $message .= '</table></td></tr></table></body></html>';

        $headers  = 'From: ' . self::FROM_NAME . ' <' . self::FROM_ADDRESS . ">\r\n";
        $headers .= 'Reply-To: ' . self::FROM_ADDRESS . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";

        return mail($toEmail, $subject, $message, $headers);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add v2/src/Core/Mailer.php
git commit -m "feat: add Mailer service for HTML password reset emails"
```

---

## Task 3: PasswordResetController

**Files:**
- Create: `v2/src/Controllers/PasswordResetController.php`

- [ ] **Step 1: Create the controller**

```php
<?php
// src/Controllers/PasswordResetController.php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\DB;
use Core\ActivityLog;
use Models\UserModel;

class PasswordResetController extends Controller
{
    /** GET /set-password?token=... */
    public function showForm(): void
    {
        $token = trim($_GET['token'] ?? '');
        $row   = $this->findValidToken($token);
        $error = null;

        if (!$row) {
            $error = 'הקישור אינו תקף או שפג תוקפו. פנה/י למנהל המערכת לשליחת קישור חדש.';
        }

        $this->view('pages/set-password', compact('token', 'error'), null);
    }

    /** POST /set-password */
    public function processForm(): void
    {
        $token = trim($_POST['token'] ?? '');
        $pass  = $_POST['password']  ?? '';
        $pass2 = $_POST['password2'] ?? '';
        $error = null;

        $row = $this->findValidToken($token);
        if (!$row) {
            $error = 'הקישור אינו תקף או שפג תוקפו.';
            $this->view('pages/set-password', compact('token', 'error'), null);
            return;
        }

        if (strlen($pass) < 6) {
            $error = 'הסיסמה חייבת להכיל לפחות 6 תווים.';
            $this->view('pages/set-password', compact('token', 'error'), null);
            return;
        }

        if ($pass !== $pass2) {
            $error = 'הסיסמאות אינן תואמות.';
            $this->view('pages/set-password', compact('token', 'error'), null);
            return;
        }

        // Set password and mark token as used
        UserModel::resetPassword((int)$row['user_id'], $pass);
        DB::execute(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?',
            [$row['id']]
        );

        $user = UserModel::byId((int)$row['user_id']);
        $label = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        ActivityLog::log('user.password_set_via_link', 'user', (int)$row['user_id'], $label);

        $success = true;
        $this->view('pages/set-password', compact('token', 'success'), null);
    }

    private function findValidToken(string $token): ?array
    {
        if (strlen($token) !== 64) return null;
        $row = DB::row(
            'SELECT id, user_id FROM password_reset_tokens
             WHERE token = ? AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1',
            [$token]
        );
        return $row ?: null;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add v2/src/Controllers/PasswordResetController.php
git commit -m "feat: add PasswordResetController for token-based password setting"
```

---

## Task 4: set-password View

**Files:**
- Create: `v2/views/pages/set-password.php`

Note: This page is rendered **without a layout** (`null` layout), standalone like the login page.

- [ ] **Step 1: Create the view**

```php
<?php
// views/pages/set-password.php
use Core\View;
$base    = rtrim(CFG['app']['url'], '/');
$appName = CFG['app']['name'];
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>קביעת סיסמא — <?= View::e($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg: #0f1117; --bg2: #181b23; --bg3: #1e2130;
  --border: rgba(255,255,255,.08);
  --text: #e8eaf0; --text2: #8b8fa8; --text3: #5a5e78;
  --accent: #4f7fff; --accent2: #3d6be8;
  --danger: #e05555; --success: #34c77b; --radius: 12px;
}
body { font-family: 'Heebo', sans-serif; background: var(--bg); color: var(--text);
       min-height: 100vh; display: grid; place-items: center; padding: 20px; }
body::before { content: ''; position: fixed; inset: 0;
  background: radial-gradient(circle at 20% 80%, rgba(79,127,255,.06) 0%, transparent 50%),
              radial-gradient(circle at 80% 20%, rgba(79,127,255,.04) 0%, transparent 40%);
  pointer-events: none; }
.card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius);
        padding: 40px 36px; width: 100%; max-width: 400px; position: relative; }
.brand { text-align: center; margin-bottom: 32px; }
.brand-icon { width: 52px; height: 52px; background: var(--accent); border-radius: 14px;
              display: grid; place-items: center; font-size: 22px; font-weight: 700;
              color: #fff; margin: 0 auto 14px; }
.brand-name { font-size: 22px; font-weight: 600; }
.brand-sub  { font-size: 13px; color: var(--text3); margin-top: 4px; }
.field { margin-bottom: 16px; }
.field label { display: block; font-size: 13px; font-weight: 500; color: var(--text2); margin-bottom: 6px; }
.field input { width: 100%; background: var(--bg3); border: 1px solid var(--border);
               border-radius: 8px; padding: 10px 14px; font-size: 15px;
               font-family: 'Heebo', sans-serif; color: var(--text); outline: none;
               transition: border-color .15s; direction: ltr; }
.field input:focus { border-color: var(--accent); }
.btn { width: 100%; background: var(--accent); color: #fff; border: none; border-radius: 8px;
       padding: 11px; font-size: 15px; font-weight: 500; font-family: 'Heebo', sans-serif;
       cursor: pointer; margin-top: 8px; transition: background .15s; }
.btn:hover { background: var(--accent2); }
.alert { border-radius: 8px; padding: 12px 14px; font-size: 13px; margin-bottom: 20px;
         display: flex; align-items: flex-start; gap: 8px; }
.alert-err { background: rgba(224,85,85,.1); border: 1px solid rgba(224,85,85,.25); color: #ef9090; }
.alert-ok  { background: rgba(52,199,123,.1); border: 1px solid rgba(52,199,123,.25); color: #6ee4a8; }
.footer-link { text-align: center; margin-top: 20px; font-size: 12px; color: var(--text3); }
</style>
</head>
<body>
<div class="card">
  <div class="brand">
    <div class="brand-icon">מ</div>
    <div class="brand-name"><?= View::e($appName) ?></div>
    <div class="brand-sub">קביעת סיסמא</div>
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert alert-ok">✓ הסיסמה נקבעה בהצלחה! ניתן להתחבר כעת.</div>
    <a href="<?= $base ?>/login" class="btn" style="display:block;text-align:center;text-decoration:none;line-height:normal;padding:11px;">מעבר לכניסה</a>

  <?php elseif (!empty($error)): ?>
    <div class="alert alert-err">⚠ <?= View::e($error) ?></div>
    <a href="<?= $base ?>/login" style="display:block;text-align:center;font-size:13px;color:var(--text2);margin-top:8px;text-decoration:none;">חזרה לדף הכניסה</a>

  <?php else: ?>
    <form method="POST" action="<?= $base ?>/set-password">
      <input type="hidden" name="token" value="<?= View::e($token ?? '') ?>">
      <div class="field">
        <label for="password">סיסמה חדשה</label>
        <input type="password" id="password" name="password" placeholder="לפחות 6 תווים" autocomplete="new-password" autofocus>
      </div>
      <div class="field">
        <label for="password2">אימות סיסמה</label>
        <input type="password" id="password2" name="password2" placeholder="הכנס/י שוב" autocomplete="new-password">
      </div>
      <button type="submit" class="btn">קבע סיסמא</button>
    </form>
  <?php endif; ?>

  <div class="footer-link"><?= View::e($appName) ?> v2 · <?= date('Y') ?></div>
</div>
</body>
</html>
```

- [ ] **Step 2: Commit**

```bash
git add v2/views/pages/set-password.php
git commit -m "feat: add set-password standalone view"
```

---

## Task 5: Update routes.php

**Files:**
- Modify: `v2/config/routes.php`

- [ ] **Step 1: Replace the old reset-password route and add new routes**

Remove this line:
```php
$router->post('/users/reset-password',   'Controllers\\UserController@resetPassword');
```

Add these lines in its place (within the users block):
```php
$router->post('/users/send-reset-email', 'Controllers\\UserController@sendResetEmail');
```

Add these lines after the `/logout` route:
```php
$router->get ('/set-password', 'Controllers\\PasswordResetController@showForm');
$router->post('/set-password', 'Controllers\\PasswordResetController@processForm');
```

Final users block should look like:
```php
$router->get ('/users',                  'Controllers\\UserController@index');
$router->post('/users/save',             'Controllers\\UserController@save');
$router->post('/users/toggle',           'Controllers\\UserController@toggle');
$router->post('/users/send-reset-email', 'Controllers\\UserController@sendResetEmail');
$router->get ('/users/perm-groups',      'Controllers\\UserController@permGroups');
$router->post('/users/perm-groups/save', 'Controllers\\UserController@savePermGroup');
$router->get ('/users/{id}',             'Controllers\\UserController@show');
```

Auth block should look like:
```php
$router->get ('/login',        'Controllers\AuthController@showLogin');
$router->post('/login',        'Controllers\AuthController@login');
$router->get ('/logout',       'Controllers\AuthController@logout');
$router->get ('/set-password', 'Controllers\\PasswordResetController@showForm');
$router->post('/set-password', 'Controllers\\PasswordResetController@processForm');
```

- [ ] **Step 2: Commit**

```bash
git add v2/config/routes.php
git commit -m "feat: add set-password routes, replace reset-password with send-reset-email"
```

---

## Task 6: Update UserController

**Files:**
- Modify: `v2/src/Controllers/UserController.php`

Changes:
1. Add `use Core\Mailer;` import
2. `save()` — for new users: remove password validation, send reset email instead
3. `save()` — for existing users: remove the password-reset-in-save block
4. Replace `resetPassword()` method with `sendResetEmail()`

- [ ] **Step 1: Add Mailer import at top of file (after existing `use` statements)**

In the `use` block (lines 6-9), add:
```php
use Core\Mailer;
```

- [ ] **Step 2: Replace the entire `save()` method**

Replace the current `save()` method (lines 37-76) with:

```php
    public function save(): void
    {
        $this->requirePermission('canAddUsers');
        $this->verifyCsrf();

        $id = (int)$this->post('id', 0);
        $data = [
            'id'                  => $id,
            'first_name'          => trim($this->post('fName', '')),
            'last_name'           => trim($this->post('lName', '')),
            'email'               => trim($this->post('email', '')),
            'phone'               => trim($this->post('phoneNum', '')),
            'department_id'       => (int)$this->post('depart', 0) ?: null,
            'is_active'           => (bool)$this->post('active', 1),
            'permission_group_id' => (int)$this->post('permissionGroupID', 0) ?: null,
            'note'                => trim($this->post('userNote', '')),
            'mvoice_id'           => trim($this->post('mvoiceid', '')),
            'sip_voice'           => trim($this->post('sipVoice', '')),
        ];

        if (!$data['first_name']) {
            $this->json(['error' => 'שם פרטי חובה'], 400);
        }
        if (!$id && !$data['email']) {
            $this->json(['error' => 'אימייל חובה למשתמש חדש'], 400);
        }

        UserModel::save($data);
        $label = trim($data['first_name'] . ' ' . $data['last_name']);

        if ($id) {
            ActivityLog::log('user.update', 'user', $id, $label);
        } else {
            ActivityLog::log('user.create', 'user', null, $label);
            // Send password-setup link to new user
            $newUser = \Core\DB::row(
                'SELECT id FROM users WHERE email = ? ORDER BY id DESC LIMIT 1',
                [$data['email']]
            );
            if ($newUser) {
                $this->dispatchResetEmail((int)$newUser['id'], $data['email'], $data['first_name'], isNew: true);
            }
        }

        $this->json(['ok' => true]);
    }
```

- [ ] **Step 3: Replace the `resetPassword()` method with `sendResetEmail()`**

Replace the entire `resetPassword()` method (lines 90-102) with:

```php
    public function sendResetEmail(): void
    {
        $this->requirePermission('canAddUsers');
        $this->verifyCsrf();

        $id   = (int)$this->post('id');
        $user = UserModel::byId($id);
        if (!$user) {
            $this->json(['error' => 'משתמש לא נמצא'], 404);
        }
        if (empty($user['email'])) {
            $this->json(['error' => 'למשתמש זה אין כתובת אימייל'], 400);
        }

        $sent = $this->dispatchResetEmail($id, $user['email'], $user['first_name'], isNew: false);

        $label = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        ActivityLog::log('user.password_reset_email_sent', 'user', $id, $label);

        if (!$sent) {
            $this->json(['error' => 'שגיאה בשליחת המייל'], 500);
        }
        $this->json(['ok' => true]);
    }

    private function dispatchResetEmail(int $userId, string $email, string $firstName, bool $isNew): bool
    {
        $token = bin2hex(random_bytes(32)); // 64 hex chars
        \Core\DB::execute(
            'INSERT INTO password_reset_tokens (user_id, token, expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 HOUR))',
            [$userId, $token]
        );

        $base = rtrim(CFG['app']['url'], '/');
        $url  = $base . '/set-password?token=' . $token;

        return Mailer::sendPasswordReset($email, $firstName, $url, $isNew);
    }
```

- [ ] **Step 4: Commit**

```bash
git add v2/src/Controllers/UserController.php
git commit -m "feat: replace direct password reset with send-reset-email flow"
```

---

## Task 7: Update users/index.php

**Files:**
- Modify: `v2/views/pages/users/index.php`

Changes:
1. Remove the "סיסמה" tab button from the modal header
2. Remove the entire `<div id="tab-security">` block
3. Add "שלח איפוס סיסמא" button per row in the table
4. Remove password-related JS (`f-password`, `f-newpass`, password validation in `saveUser()`)
5. Add `sendResetEmail()` JS function

- [ ] **Step 1: Replace the tab bar in the modal (remove the security tab)**

Find:
```php
    <div style="display:flex;border-bottom:1px solid var(--border);padding:0 20px;">
      <button class="mtab active" onclick="switchTab('details')" data-tab="details">פרטים</button>
      <button class="mtab" onclick="switchTab('security')" data-tab="security">סיסמה</button>
    </div>
```

Replace with:
```php
    <div style="display:flex;border-bottom:1px solid var(--border);padding:0 20px;">
      <button class="mtab active" onclick="switchTab('details')" data-tab="details">פרטים</button>
    </div>
```

- [ ] **Step 2: Remove the security tab content**

Find and delete the entire block:
```php
      <div id="tab-security" style="display:none;">
        <div id="new-user-pass">
          <label class="flabel">סיסמה *</label>
          <input id="f-password" type="password" class="finput" placeholder="לפחות 6 תווים">
        </div>
        <div id="reset-pass-section" style="display:none;">
          <div class="alert alert-info" style="margin-bottom:14px;">השאר ריק לאי-שינוי</div>
          <label class="flabel">סיסמה חדשה</label>
          <input id="f-newpass" type="password" class="finput" placeholder="השאר ריק לאי-שינוי">
        </div>
      </div>
```

- [ ] **Step 3: Add "שלח איפוס סיסמא" button to each table row**

Find the table action cell:
```php
      <td style="padding:9px 12px;">
        <button class="btn btn-ghost" style="padding:4px 10px;font-size:12px;"
                onclick="openModal(<?= (int)$u['id'] ?>)">עריכה</button>
      </td>
```

Replace with:
```php
      <td style="padding:9px 12px;display:flex;gap:6px;align-items:center;">
        <button class="btn btn-ghost" style="padding:4px 10px;font-size:12px;"
                onclick="openModal(<?= (int)$u['id'] ?>)">עריכה</button>
        <?php if (!empty($u['email'])): ?>
        <button class="btn btn-ghost" style="padding:4px 10px;font-size:12px;color:var(--accent);"
                onclick="sendResetEmail(<?= (int)$u['id'] ?>, '<?= View::e($u['first_name']) ?>')"
                title="שלח קישור איפוס סיסמא">🔑 איפוס</button>
        <?php endif; ?>
      </td>
```

- [ ] **Step 4: Replace the `saveUser()` JS function**

Find the entire `async function saveUser()` block and replace with:

```javascript
async function saveUser() {
  const fName = document.getElementById('f-fname').value.trim();
  if (!fName) { showErr('שם פרטי חובה'); return; }
  const isNew = !currentUserId;
  if (isNew && !document.getElementById('f-email').value.trim()) {
    showErr('אימייל חובה למשתמש חדש — יישלח קישור לקביעת סיסמא');
    return;
  }

  const body = new URLSearchParams({
    _csrf: CSRF, id: currentUserId || '',
    fName, lName: document.getElementById('f-lname').value.trim(),
    email: document.getElementById('f-email').value.trim(),
    phoneNum: document.getElementById('f-phone').value.trim(),
    depart: document.getElementById('f-depart').value,
    permissionGroupID: document.getElementById('f-group').value,
    mvoiceid: document.getElementById('f-mvoice').value.trim(),
    sipVoice: document.getElementById('f-sip').value.trim(),
    userNote: document.getElementById('f-note').value.trim(),
    active: document.getElementById('f-active').checked ? '1' : '0',
  });

  const res  = await fetch(`${BASE_URL}/users/save`, { method:'POST', body });
  const data = await res.json();
  if (data.ok) {
    closeModal();
    location.reload();
  } else {
    showErr(data.error || 'שגיאה');
  }
}
```

- [ ] **Step 5: Add `sendResetEmail()` JS function** (add before the closing `</script>` tag)

```javascript
async function sendResetEmail(userId, userName) {
  if (!confirm(`לשלוח קישור לאיפוס סיסמא למשתמש ${userName}?`)) return;
  const res  = await fetch(`${BASE_URL}/users/send-reset-email`, {
    method: 'POST',
    body: new URLSearchParams({ _csrf: CSRF, id: userId })
  });
  const data = await res.json();
  if (data.ok) {
    alert(`קישור איפוס סיסמא נשלח למשתמש ${userName}.`);
  } else {
    alert('שגיאה: ' + (data.error || 'לא ניתן לשלוח'));
  }
}
```

- [ ] **Step 6: Remove password-related code in `openModal()`**

Find inside `openModal()`:
```javascript
      document.getElementById('f-newpass').value = '';
```
Delete that line.

Find:
```javascript
  document.getElementById('new-user-pass').style.display      = id ? 'none' : 'block';
  document.getElementById('reset-pass-section').style.display = id ? 'block' : 'none';
```
Delete both lines (these referenced elements no longer exist).

Find in the new-user branch of openModal the clear loop:
```javascript
    ['f-id','f-fname','f-lname','f-email','f-phone','f-mvoice','f-sip','f-note','f-password']
      .forEach(id => { const el = document.getElementById(id); if (el) el.value=''; });
```
Replace with:
```javascript
    ['f-id','f-fname','f-lname','f-email','f-phone','f-mvoice','f-sip','f-note']
      .forEach(id => { const el = document.getElementById(id); if (el) el.value=''; });
```

- [ ] **Step 7: Remove `switchTab` function references for security tab** (the function itself can stay, just the security tab button is gone — no action needed beyond Step 1).

- [ ] **Step 8: Commit**

```bash
git add v2/views/pages/users/index.php
git commit -m "feat: replace password fields with send-reset-email button in user management"
```

---

## Task 8: Update login.php — "שכחתי סיסמא"

**Files:**
- Modify: `v2/views/pages/login.php`

- [ ] **Step 1: Add forgot-password styles** (add inside the `<style>` block, before closing `</style>`)

```css
.forgot-link { text-align: center; margin-top: 14px; font-size: 13px; color: var(--text3); cursor: pointer; }
.forgot-link:hover { color: var(--text2); }
.forgot-notice { background: rgba(79,127,255,.08); border: 1px solid rgba(79,127,255,.2);
                 color: #8baeff; border-radius: 8px; padding: 10px 14px; font-size: 13px;
                 margin-top: 14px; display: none; text-align: center; }
```

- [ ] **Step 2: Add the link and notice after the submit button** (after `<button type="submit" ...>כניסה</button>`)

```html
    <div class="forgot-link" onclick="showForgot()">שכחתי סיסמא</div>
    <div class="forgot-notice" id="forgot-notice">
      לאיפוס סיסמא יש לפנות למנהל/ת המערכת שישלח/תשלח קישור חדש לאימייל שלך.
    </div>
```

- [ ] **Step 3: Add JS** (before closing `</body>`)

```html
<script>
function showForgot() {
  document.getElementById('forgot-notice').style.display = 'block';
}
</script>
```

- [ ] **Step 4: Commit**

```bash
git add v2/views/pages/login.php
git commit -m "feat: add forgot-password notice on login page"
```

---

## Task 9: Final Verification

- [ ] **Step 1: Check all routes exist**

```bash
grep -n "set-password\|send-reset-email" v2/config/routes.php
```

Expected: 3 lines (GET set-password, POST set-password, POST send-reset-email).

- [ ] **Step 2: Check reset-password route is gone**

```bash
grep "reset-password" v2/config/routes.php
```

Expected: no output.

- [ ] **Step 3: Verify DB table exists**

```bash
php -r "require 'v2/config/bootstrap.php'; \$r = \Core\DB::row('SHOW TABLES LIKE \"password_reset_tokens\"'); var_dump(\$r);"
```

Expected: array with table name.

- [ ] **Step 4: Commit final state if any loose files remain**

```bash
git status
```
