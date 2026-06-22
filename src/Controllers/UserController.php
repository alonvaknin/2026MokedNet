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
                $sent = $this->dispatchResetEmail((int)$newUser['id'], $data['email'], $data['first_name'], isNew: true);
                if (!$sent) {
                    $this->json(['ok' => true, 'warn' => 'המשתמש נוצר אך שליחת המייל נכשלה. שלח/י קישור ידנית.']);
                }
            }
        }

        $this->json(['ok' => true]);
    }

    public function toggle(): void
    {
        $this->requirePermission('canAddUsers');
        $this->verifyCsrf();
        $id     = (int)$this->post('id');
        $user   = UserModel::byId($id);
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
}