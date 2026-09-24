<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\ActivityLog;
use Models\UserModel;
use Core\Mailer;

class UserController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('canAddUsers');
        $users      = UserModel::all();
        $permGroups = UserModel::permGroups();
        $depts      = UserModel::departments();
        $this->view('pages/users/index', compact('users', 'permGroups', 'depts'));
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $user = UserModel::byId((int)$id);
        if (!$user) { http_response_code(404); echo '404'; return; }
        $wantsJson = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
        if ($wantsJson) {
            $this->json($user);
            return;
        }
        $isOwnProfile = ((int)$_SESSION['user_id']) === (int)$id;
        $this->view('pages/users/show', compact('user', 'isOwnProfile'));
    }

    public function save(): void
    {
        $this->requirePermission('canAddUsers');
        $this->verifyCsrf();

        $data = $this->userFormData();
        $id   = $data['id'];

        if (!$data['first_name']) {
            $this->json(['error' => 'שם פרטי חובה'], 400);
        }
        if (!$id && !$data['email']) {
            $this->json(['error' => 'אימייל חובה למשתמש חדש'], 400);
        }

        // users.email is UNIQUE. If the address is taken - most often by a
        // deactivated user, invisible in the default table view - say who has
        // it, so the UI can offer to supersede them instead of just failing.
        if (!$id) {
            $existing = UserModel::byEmail($data['email']);
            if ($existing) {
                $this->json([
                    'error'    => 'exists',
                    'field'    => 'email',
                    'existing' => [
                        'id'        => (int)$existing['id'],
                        'name'      => trim($existing['first_name'] . ' ' . $existing['last_name']),
                        'is_active' => (int)$existing['is_active'],
                    ],
                ], 409);
            }
            // users.phone is UNIQUE as well, and a returning employee keeps
            // their number, so it collides in exactly the same way.
            $byPhone = UserModel::byPhone($data['phone']);
            if ($byPhone) {
                $this->json([
                    'error'    => 'exists',
                    'field'    => 'phone',
                    'existing' => [
                        'id'        => (int)$byPhone['id'],
                        'name'      => trim($byPhone['first_name'] . ' ' . $byPhone['last_name']),
                        'is_active' => (int)$byPhone['is_active'],
                    ],
                ], 409);
            }
        }

        // An archived user is retired for good: they may still be edited, but
        // never switched back on, and their address stays released.
        if ($id) {
            $current = UserModel::byId($id);
            if ($current && (int)($current['is_archived'] ?? 0) === 1) {
                $data['is_active'] = false;
                $data['email']     = $current['email']; // stays NULL
            }
        }

        try {
            $savedId = UserModel::save($data);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'uq_email')) {
                $this->json(['error' => 'כתובת האימייל כבר קיימת במערכת'], 409);
            }
            throw $e;
        }
        $label = trim($data['first_name'] . ' ' . $data['last_name']);

        if ($id) {
            ActivityLog::log('user.update', 'user', $id, $label);
        } else {
            ActivityLog::log('user.create', 'user', $savedId, $label);
            $sent = $this->dispatchResetEmail($savedId, $data['email'], $data['first_name'], isNew: true);
            if (!$sent) {
                $this->json(['ok' => true, 'warn' => 'המשתמש נוצר אך שליחת המייל נכשלה. שלח/י קישור ידנית.']);
            }
        }

        $this->json(['ok' => true]);
    }

    /** Read the user modal's fields into a UserModel::save() payload. */
    private function userFormData(): array
    {
        return [
            'id'                  => (int)$this->post('id', 0),
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
            'hours_reports'       => (int)$this->post('hoursReports', 0),
        ];
    }

    /**
     * A returning employee: archive the old user and create a brand new row
     * for them. The two employments stay separate entities, so activity log,
     * tasks and hours are never mixed between them. The old row is retired
     * permanently - it releases the email address and cannot be reactivated.
     */
    public function supersede(): void
    {
        $this->requirePermission('canAddUsers');
        $this->verifyCsrf();

        $data  = $this->userFormData();
        $oldId = (int)$this->post('existingId', 0);

        if (!$oldId) {
            $this->json(['error' => 'חסר מזהה משתמש קודם'], 400);
        }
        if (!$data['first_name']) {
            $this->json(['error' => 'שם פרטי חובה'], 400);
        }
        if (!$data['email']) {
            $this->json(['error' => 'אימייל חובה למשתמש חדש'], 400);
        }

        $old = UserModel::byId($oldId);
        if (!$old) {
            $this->json(['error' => 'משתמש לא נמצא'], 404);
        }
        if ((int)($old['is_archived'] ?? 0) === 1) {
            $this->json(['error' => 'המשתמש הקודם כבר בארכיון'], 400);
        }
        // The old user must actually be the one blocking us - by email or by
        // phone, whichever of the two UNIQUE columns clashed.
        $ownsEmail = strcasecmp((string)($old['email'] ?? ''), $data['email']) === 0;
        $ownsPhone = $data['phone'] !== ''
            && (string)($old['phone'] ?? '') === $data['phone'];
        if (!$ownsEmail && !$ownsPhone) {
            $this->json(['error' => 'הפרטים שהוזנו אינם שייכים למשתמש שנבחר'], 409);
        }

        // Anything still held by a third user would break the INSERT.
        $clashEmail = UserModel::byEmail($data['email']);
        if ($clashEmail && (int)$clashEmail['id'] !== $oldId) {
            $this->json(['error' => 'כתובת האימייל כבר קיימת במערכת'], 409);
        }
        $clashPhone = UserModel::byPhone($data['phone']);
        if ($clashPhone && (int)$clashPhone['id'] !== $oldId) {
            $this->json(['error' => 'מספר הטלפון כבר קיים במערכת'], 409);
        }

        $data['id'] = 0; // force an INSERT - new identity, new id

        // Free the address first: users.email is UNIQUE, so the new row cannot
        // take it while the old row still holds it.
        UserModel::archive($oldId);
        try {
            $newId = UserModel::save($data);
        } catch (\Throwable $e) {
            // Creating the replacement failed - give the old user their address
            // back rather than leaving them archived for nothing.
            UserModel::restore($oldId);
            throw $e;
        }
        UserModel::archive($oldId, $newId);

        $oldLabel = trim(($old['first_name'] ?? '') . ' ' . ($old['last_name'] ?? ''));
        $label    = trim($data['first_name'] . ' ' . $data['last_name']);
        ActivityLog::log('user.archive', 'user', $oldId, $oldLabel);
        ActivityLog::log('user.create', 'user', $newId, $label);

        $sent = $this->dispatchResetEmail($newId, $data['email'], $data['first_name'], isNew: true);
        if (!$sent) {
            $this->json(['ok' => true, 'warn' => 'המשתמש נוצר אך שליחת המייל נכשלה. שלח/י קישור ידנית.']);
        }

        $this->json(['ok' => true]);
    }

    public function toggle(): void
    {
        $this->requirePermission('canAddUsers');
        $this->verifyCsrf();
        $id     = (int)$this->post('id');
        $user   = UserModel::byId($id);
        if (!$user) {
            $this->json(['error' => 'משתמש לא נמצא'], 404);
        }
        // Archived users are retired permanently - they cannot be switched on.
        if ((int)($user['is_archived'] ?? 0) === 1 && (int)$user['is_active'] === 0) {
            $this->json(['error' => 'משתמש בארכיון אינו ניתן להפעלה מחדש'], 400);
        }
        $active = UserModel::toggleActive($id);
        $label  = trim(($user['first_name']??'') . ' ' . ($user['last_name']??''));
        ActivityLog::toggle('user', $id, $label, (bool)$active);
        $this->json(['ok' => true, 'active' => $active]);
    }

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
        if ((int)($user['is_archived'] ?? 0) === 1) {
            $this->json(['error' => 'משתמש בארכיון אינו ניתן לניהול סיסמא'], 400);
        }
        if ((int)$user['is_active'] !== 1) {
            $this->json(['error' => 'לא ניתן לשלוח איפוס למשתמש לא פעיל'], 400);
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

    public function setTempPassword(): void
    {
        $this->requirePermission('canAddUsers');
        $this->verifyCsrf();

        $id   = (int)$this->post('id');
        $pass = $this->post('password', '');

        if (strlen($pass) < 6) {
            $this->json(['error' => 'הסיסמא חייבת להכיל לפחות 6 תווים'], 400);
        }

        $user = UserModel::byId($id);
        if (!$user) {
            $this->json(['error' => 'משתמש לא נמצא'], 404);
        }
        if ((int)($user['is_archived'] ?? 0) === 1) {
            $this->json(['error' => 'משתמש בארכיון אינו ניתן לניהול סיסמא'], 400);
        }
        if ((int)$user['is_active'] !== 1) {
            $this->json(['error' => 'לא ניתן לקבוע סיסמא למשתמש לא פעיל'], 400);
        }

        UserModel::setTempPassword($id, $pass);

        $label = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        ActivityLog::log('user.temp_password_set', 'user', $id, $label);

        $this->json(['ok' => true]);
    }

    public function permGroups(): void
    {
        $this->requirePermission('canEditDB');
        $groups     = UserModel::allPermGroups();
        $categories = UserModel::PERM_CATEGORIES;
        $labels     = UserModel::PERM_LABELS;
        $this->view('pages/users/perm-groups', compact('groups', 'categories', 'labels'));
    }

    public function savePermGroup(): void
    {
        $this->requirePermission('canEditDB');

        // CSRF מ-header (JSON request לא שולח _POST)
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            $this->json(['error' => 'CSRF invalid'], 419);
        }

        // קריאת JSON body
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true);
        if (!$body || !isset($body['permmisionsGroupID'], $body['perms'])) {
            $this->json(['error' => 'נתונים לא תקינים'], 400);
        }

        UserModel::savePermGroup((int)$body['permmisionsGroupID'], $body['perms']);
        Auth::clearPermCache();
        $this->json(['ok' => true]);
    }

    public function apiSearch(): void
    {
        $this->requireAuth();
        $q = trim($this->get('q', ''));
        if (mb_strlen($q) < 2) $this->json([]);
        $this->json(UserModel::search($q));
    }

    public function apiActiveList(): void
    {
        $this->requirePermission('task_settings.manage');
        $users = \Core\DB::query(
            'SELECT id, CONCAT(first_name," ",last_name) AS name
             FROM users WHERE is_active=1 ORDER BY first_name, last_name'
        );
        $this->json($users);
    }
}