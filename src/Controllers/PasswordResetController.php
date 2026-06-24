<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\DB;
use Core\Auth;
use Core\ActivityLog;
use Models\UserModel;

class PasswordResetController extends Controller
{
    /** GET /set-password */
    public function showForm(): void
    {
        // Session-based forced change (temp password set by admin)
        if (!empty($_SESSION['must_change_password']) && !empty($_SESSION['user_id'])) {
            $token = null;
            $error = null;
            $forcedChange = true;
            $this->view('pages/set-password', compact('token', 'error', 'forcedChange'), null);
            return;
        }

        $token = trim($_GET['token'] ?? '');
        $row   = $this->findValidToken($token);
        $error = null;
        $forcedChange = false;

        if (!$row) {
            $error = 'הקישור אינו תקף או שפג תוקפו. פנה/י למנהל המערכת לשליחת קישור חדש.';
        }

        $this->view('pages/set-password', compact('token', 'error', 'forcedChange'), null);
    }

    /** POST /set-password */
    public function processForm(): void
    {
        $pass  = $_POST['password']  ?? '';
        $pass2 = $_POST['password2'] ?? '';
        $error = null;

        // Session-based forced change (temp password)
        $forcedChange = !empty($_SESSION['must_change_password']) && !empty($_SESSION['user_id']);
        if ($forcedChange) {
            $token = null;
            $userId = (int)$_SESSION['user_id'];

            if (strlen($pass) < 6) {
                $error = 'הסיסמה חייבת להכיל לפחות 6 תווים.';
                $this->view('pages/set-password', compact('token', 'error', 'forcedChange'), null);
                return;
            }
            if ($pass !== $pass2) {
                $error = 'הסיסמאות אינן תואמות.';
                $this->view('pages/set-password', compact('token', 'error', 'forcedChange'), null);
                return;
            }

            UserModel::resetPassword($userId, $pass);
            unset($_SESSION['must_change_password']);

            $user  = UserModel::byId($userId);
            $label = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            ActivityLog::log('user.password_changed_forced', 'user', $userId, $label);

            $this->redirect('/dashboard');
            return;
        }

        // Token-based (email link)
        $token = trim($_POST['token'] ?? '');
        $row   = $this->findValidToken($token);

        if (!$row) {
            $error = 'הקישור אינו תקף או שפג תוקפו. פנה/י למנהל המערכת לשליחת קישור חדש.';
            $this->view('pages/set-password', compact('token', 'error', 'forcedChange'), null);
            return;
        }

        if (strlen($pass) < 6) {
            $error = 'הסיסמה חייבת להכיל לפחות 6 תווים.';
            $this->view('pages/set-password', compact('token', 'error', 'forcedChange'), null);
            return;
        }

        if ($pass !== $pass2) {
            $error = 'הסיסמאות אינן תואמות.';
            $this->view('pages/set-password', compact('token', 'error', 'forcedChange'), null);
            return;
        }

        UserModel::resetPassword((int)$row['user_id'], $pass);
        DB::execute(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?',
            [$row['id']]
        );

        $user  = UserModel::byId((int)$row['user_id']);
        $label = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        ActivityLog::log('user.password_set_via_link', 'user', (int)$row['user_id'], $label);

        $success = true;
        $this->view('pages/set-password', compact('token', 'success', 'forcedChange'), null);
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
