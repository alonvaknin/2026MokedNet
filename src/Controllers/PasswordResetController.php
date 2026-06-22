<?php
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
            $error = 'הקישור אינו תקף או שפג תוקפו. פנה/י למנהל המערכת לשליחת קישור חדש.';
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
