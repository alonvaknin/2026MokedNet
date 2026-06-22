<?php
declare(strict_types=1);

namespace Core;

class Auth
{
    private const PERM_CACHE_KEY = '_perm_cache';

    public static function attempt(string $identifier, string $password): bool
    {
        $user = DB::row(
            'SELECT id, first_name, last_name, password_hash, auth_token,
                    permission_group_id, department_id, is_active
             FROM users
             WHERE (email = ? OR id = ?)
             AND is_active = 1
             LIMIT 1',
            [$identifier, $identifier]
        );

        if (!$user) {
            ActivityLog::login(0, $identifier, false);
            return false;
        }

        $verified = false;

        if (password_verify($password, $user['password_hash'])) {
            $verified = true;
        } elseif (hash_equals($user['password_hash'], md5($password))) {
            $verified = true;
            DB::execute(
                'UPDATE users SET password_hash = ? WHERE id = ?',
                [password_hash($password, PASSWORD_BCRYPT), $user['id']]
            );
        }

        if (!$verified) {
            ActivityLog::login($user['id'], $identifier, false);
            return false;
        }

        $token = bin2hex(random_bytes(32));
        DB::execute(
            'UPDATE users SET auth_token = ?, last_login = NOW() WHERE id = ?',
            [$token, $user['id']]
        );

        session_regenerate_id(true);
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['auth_token']    = $token;
        $_SESSION['perm_group']    = $user['permission_group_id'];
        $_SESSION['department_id'] = $user['department_id'];
        $_SESSION['full_name']     = $user['first_name'] . ' ' . $user['last_name'];
        self::loadAllPerms();

        ActivityLog::login($user['id'], $identifier, true);
        return true;
    }

    public static function logout(): void
    {
        if (!empty($_SESSION['user_id'])) {
            ActivityLog::logout((int)$_SESSION['user_id'], $_SESSION['full_name'] ?? '');
            DB::execute('UPDATE users SET auth_token = "" WHERE id = ?', [$_SESSION['user_id']]);
        }
        session_destroy();
    }

    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) return null;
        static $cache = null;
        if ($cache === null) {
            $cache = DB::row(
                'SELECT u.id, u.first_name, u.last_name, u.email,
                        u.department_id, u.permission_group_id,
                        d.name_heb  AS dept_name,
                        pg.name_heb AS group_name
                 FROM users u
                 LEFT JOIN departments       d  ON d.id  = u.department_id
                 LEFT JOIN permission_groups pg ON pg.id = u.permission_group_id
                 WHERE u.id = ? AND u.is_active = 1',
                [$_SESSION['user_id']]
            );
        }
        return $cache;
    }

    public static function check(): bool
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['auth_token'])) return false;
        $token = DB::value(
            'SELECT auth_token FROM users WHERE id = ? AND is_active = 1',
            [$_SESSION['user_id']]
        );
        return $token !== false && $token !== '' && hash_equals((string)$token, $_SESSION['auth_token']);
    }

    public static function can(string $permissionKey): bool
    {
        $groupId = $_SESSION['perm_group'] ?? null;
        if (!$groupId) return false;

        $cache = $_SESSION[self::PERM_CACHE_KEY] ?? null;
        if ($cache !== null && array_key_exists($permissionKey, $cache)) {
            return (bool)$cache[$permissionKey];
        }

        $val = DB::value(
            'SELECT granted FROM permission_group_grants
             WHERE group_id = ? AND permission_key = ? LIMIT 1',
            [$groupId, $permissionKey]
        );

        $result = (bool)$val;
        $_SESSION[self::PERM_CACHE_KEY][$permissionKey] = $result;
        return $result;
    }

    public static function loadAllPerms(): void
    {
        $groupId = $_SESSION['perm_group'] ?? null;
        if (!$groupId) {
            $_SESSION[self::PERM_CACHE_KEY] = [];
            return;
        }

        $grants = DB::query(
            'SELECT permission_key, granted FROM permission_group_grants WHERE group_id = ?',
            [$groupId]
        );

        $_SESSION[self::PERM_CACHE_KEY] = [];
        foreach ($grants as $g) {
            $_SESSION[self::PERM_CACHE_KEY][$g['permission_key']] = (bool)$g['granted'];
        }
    }

    public static function clearPermCache(): void
    {
        unset($_SESSION[self::PERM_CACHE_KEY]);
    }
}
