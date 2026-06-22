<?php
declare(strict_types=1);

namespace Core;

/**
 * ActivityLog — לוגים של פעולות משתמשים במערכת
 *
 * שימוש בסיסי:
 *   ActivityLog::log('store.update', 'store', $storeId, 'סניף תל אביב #42');
 *
 * עם diff:
 *   ActivityLog::diff('store.update', 'store', $storeId, 'סניף תל אביב', $before, $after);
 *
 * שדה בודד:
 *   ActivityLog::field('store.alert', 'store', $id, 'alert_note', $old, $new);
 *
 * קיצורים מהירים:
 *   ActivityLog::create('contact', $id, 'שם איש קשר');
 *   ActivityLog::update('store',   $id, 'סניף X', $before, $after);
 *   ActivityLog::delete('user',    $id, 'שם משתמש');
 *   ActivityLog::login($userId, $identifier, true);
 */
class ActivityLog
{
    // ── Actions נפוצים ────────────────────────────────────────
    public const LOGIN_OK      = 'auth.login_ok';
    public const LOGIN_FAIL    = 'auth.login_fail';
    public const LOGOUT        = 'auth.logout';
    public const CREATE        = '{entity}.create';
    public const UPDATE        = '{entity}.update';
    public const DELETE        = '{entity}.delete';
    public const TOGGLE        = '{entity}.toggle';
    public const CANCEL        = '{entity}.cancel';
    public const VIEW          = '{entity}.view';

    // ── שדות שמסוננים תמיד מה-diff (סיסמאות וכו') ───────────
    private const SENSITIVE_FIELDS = [
        'password','password_hash','token','auth_token','secret','csrf_token','_csrf',
    ];

    // ── API ראשי ────────────────────────────────────────────────

    /**
     * כתיבת לוג בסיסי (ללא diff)
     */
    public static function log(
        string  $action,
        ?string $entityType  = null,
        ?int    $entityId    = null,
        ?string $entityLabel = null,
        ?string $detail      = null
    ): void {
        self::write([
            'action'       => $action,
            'entity_type'  => $entityType,
            'entity_id'    => $entityId,
            'entity_label' => $entityLabel,
            'detail'       => $detail,
        ]);
    }

    /**
     * diff מלא בין שני arrays (לפני ואחרי)
     * מסנן שדות זהים ושדות רגישים
     */
    public static function diff(
        string  $action,
        string  $entityType,
        int     $entityId,
        string  $entityLabel,
        array   $before,
        array   $after,
        ?string $detail = null
    ): void {
        $changed = self::buildDiff($before, $after);
        if (empty($changed)) return; // אין שינוי — לא כותבים

        self::write([
            'action'       => $action,
            'entity_type'  => $entityType,
            'entity_id'    => $entityId,
            'entity_label' => $entityLabel,
            'diff_json'    => json_encode($changed, JSON_UNESCAPED_UNICODE),
            'detail'       => $detail,
        ]);
    }

    /**
     * שדה בודד שהשתנה
     */
    public static function field(
        string  $action,
        string  $entityType,
        int     $entityId,
        string  $entityLabel,
        string  $fieldName,
        mixed   $oldValue,
        mixed   $newValue,
        ?string $detail = null
    ): void {
        if (in_array($fieldName, self::SENSITIVE_FIELDS, true)) return;
        if ((string)$oldValue === (string)$newValue) return;

        self::write([
            'action'       => $action,
            'entity_type'  => $entityType,
            'entity_id'    => $entityId,
            'entity_label' => $entityLabel,
            'field_name'   => $fieldName,
            'old_value'    => self::truncate((string)$oldValue),
            'new_value'    => self::truncate((string)$newValue),
            'detail'       => $detail,
        ]);
    }

    // ── קיצורים ─────────────────────────────────────────────────

    public static function create(string $entity, int $id, string $label, ?array $data = null): void
    {
        self::write([
            'action'       => "{$entity}.create",
            'entity_type'  => $entity,
            'entity_id'    => $id,
            'entity_label' => $label,
            'diff_json'    => $data ? json_encode(self::sanitize($data), JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public static function update(string $entity, int $id, string $label, array $before, array $after, ?string $detail = null): void
    {
        self::diff("{$entity}.update", $entity, $id, $label, $before, $after, $detail);
    }

    public static function delete(string $entity, int $id, string $label, ?string $detail = null): void
    {
        self::log("{$entity}.delete", $entity, $id, $label, $detail);
    }

    public static function toggle(string $entity, int $id, string $label, bool $newState): void
    {
        self::write([
            'action'       => "{$entity}.toggle",
            'entity_type'  => $entity,
            'entity_id'    => $id,
            'entity_label' => $label,
            'field_name'   => 'is_active',
            'old_value'    => $newState ? '0' : '1',
            'new_value'    => $newState ? '1' : '0',
        ]);
    }

    public static function login(int $userId, string $identifier, bool $success): void
    {
        self::write([
            'action'       => $success ? self::LOGIN_OK : self::LOGIN_FAIL,
            'entity_type'  => 'user',
            'entity_id'    => $userId ?: null,
            'entity_label' => $identifier,
            'detail'       => $success ? null : 'כניסה נכשלה',
        ]);
    }

    public static function logout(int $userId, string $userName): void
    {
        self::write([
            'action'       => self::LOGOUT,
            'entity_type'  => 'user',
            'entity_id'    => $userId,
            'entity_label' => $userName,
        ]);
    }

    // ── Query helpers ────────────────────────────────────────────

    /**
     * שליפת לוגים עם פילטור
     */
    public static function fetch(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = ?'; $params[] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where[] = 'action LIKE ?'; $params[] = '%' . $filters['action'] . '%';
        }
        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = ?'; $params[] = $filters['entity_type'];
        }
        if (!empty($filters['entity_id'])) {
            $where[] = 'entity_id = ?'; $params[] = $filters['entity_id'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'created_at >= ?'; $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'created_at <= ?'; $params[] = $filters['to'];
        }
        if (!empty($filters['ip'])) {
            $where[] = 'ip = ?'; $params[] = $filters['ip'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(entity_label LIKE ? OR detail LIKE ? OR new_value LIKE ? OR old_value LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        $whereSQL = implode(' AND ', $where);
        $params[] = $limit;
        $params[] = $offset;

        return DB::query(
            "SELECT * FROM activity_log WHERE $whereSQL
             ORDER BY created_at DESC LIMIT ? OFFSET ?",
            $params
        );
    }

    public static function count(array $filters = []): int
    {
        // אותו לוגיק בלי LIMIT
        $where  = ['1=1'];
        $params = [];
        if (!empty($filters['user_id']))    { $where[] = 'user_id = ?';        $params[] = $filters['user_id']; }
        if (!empty($filters['action']))     { $where[] = 'action LIKE ?';      $params[] = '%'.$filters['action'].'%'; }
        if (!empty($filters['entity_type'])){ $where[] = 'entity_type = ?';    $params[] = $filters['entity_type']; }
        if (!empty($filters['entity_id']))  { $where[] = 'entity_id = ?';      $params[] = $filters['entity_id']; }
        if (!empty($filters['from']))       { $where[] = 'created_at >= ?';    $params[] = $filters['from']; }
        if (!empty($filters['to']))         { $where[] = 'created_at <= ?';    $params[] = $filters['to']; }
        if (!empty($filters['ip']))         { $where[] = 'ip = ?';             $params[] = $filters['ip']; }
        if (!empty($filters['q'])) {
            $where[] = '(entity_label LIKE ? OR detail LIKE ? OR new_value LIKE ? OR old_value LIKE ?)';
            $like = '%'.$filters['q'].'%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        return (int) DB::value("SELECT COUNT(*) FROM activity_log WHERE " . implode(' AND ', $where), $params);
    }

    // ── Private ──────────────────────────────────────────────────

    private static function write(array $data): void
    {
        try {
            $userId   = $_SESSION['user_id']  ?? null;
            $userName = $_SESSION['full_name'] ?? null;
            $ip       = self::resolveIp();
            $ua       = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);
            $url      = substr(($_SERVER['REQUEST_URI']    ?? ''), 0, 512);
            $method   = $_SERVER['REQUEST_METHOD'] ?? null;
            $sid      = session_id() ?: null;

            DB::execute(
                "INSERT INTO activity_log
                    (user_id, user_name, ip, user_agent,
                     action, entity_type, entity_id, entity_label,
                     field_name, old_value, new_value, diff_json,
                     detail, request_url, request_method, session_id)
                 VALUES
                    (?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?)",
                [
                    $userId,
                    $userName,
                    $ip,
                    $ua,

                    $data['action'],
                    $data['entity_type']  ?? null,
                    $data['entity_id']    ?? null,
                    $data['entity_label'] ?? null,

                    $data['field_name'] ?? null,
                    $data['old_value']  ?? null,
                    $data['new_value']  ?? null,
                    $data['diff_json']  ?? null,

                    $data['detail']  ?? null,
                    $url,
                    $method,
                    $sid,
                ]
            );
        } catch (\Throwable $e) {
            // לוגים לא יפילו את המערכת — אבל כן נרשום
            error_log('[ActivityLog::write] ' . $e->getMessage() . ' | action=' . ($data['action'] ?? '?'));
        }
    }

    private static function buildDiff(array $before, array $after): array
    {
        $changed = [];
        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));

        foreach ($allKeys as $key) {
            if (in_array($key, self::SENSITIVE_FIELDS, true)) continue;

            $oldVal = (string)($before[$key] ?? '');
            $newVal = (string)($after[$key]  ?? '');

            if ($oldVal === $newVal) continue;

            $changed[$key] = [
                'old' => self::truncate($oldVal),
                'new' => self::truncate($newVal),
            ];
        }

        return $changed;
    }

    private static function sanitize(array $data): array
    {
        return array_filter(
            $data,
            fn($k) => !in_array($k, self::SENSITIVE_FIELDS, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    private static function truncate(string $val, int $max = 1000): string
    {
        return mb_strlen($val) > $max ? mb_substr($val, 0, $max) . '…' : $val;
    }

    private static function resolveIp(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $h) {
            $val = $_SERVER[$h] ?? '';
            if ($val) {
                // X-Forwarded-For יכול להכיל רשימה — קח את הראשון
                return trim(explode(',', $val)[0]);
            }
        }
        return '';
    }
}
