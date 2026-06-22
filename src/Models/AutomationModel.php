<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class AutomationModel
{
    public const TYPE_NOTIFY_STATUS = 'notifyOnChangeTo';
    public const TYPE_TECH_CARE     = 'techCare';
    public const TYPE_OPEN_BY_PHONE = 'openCaseByPhone';
    public const TYPE_ORDER_NOTE    = 'chechOrderNote';

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_NOTIFY_STATUS => 'התראה בשינוי סטטוס קריאה',
            self::TYPE_TECH_CARE     => 'התראה כשטכנאי מעדכן קריאה',
            self::TYPE_OPEN_BY_PHONE => 'התראה כשלקוח פותח קריאה',
            self::TYPE_ORDER_NOTE    => 'התראה בשינוי הערות הזמנה',
            default                  => $type,
        };
    }

    // ── Queries ──────────────────────────────────────────────────────────────

    public static function paginated(
        int    $userId,
        bool   $all,
        int    $offset,
        int    $limit,
        int    $agentId = 0,
        string $search  = '',
        string $status  = ''
    ): array {
        $where  = [];
        $params = [];

        if (!$all) {
            $where[]  = 'user_id = ?';
            $params[] = $userId;
        } elseif ($agentId > 0) {
            $where[]  = 'user_id = ?';
            $params[] = $agentId;
        }

        if ($search !== '') {
            $where[]  = '(value_of_type LIKE ? OR user_name LIKE ? OR mailto LIKE ? OR msg_from_user LIKE ?)';
            $like     = '%' . $search . '%';
            $params   = array_merge($params, [$like, $like, $like, $like]);
        }

        if ($status === 'active') {
            $where[] = 'is_active = 1';
        } elseif ($status === 'closed') {
            $where[] = 'is_active = 0';
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $pdo      = DB::get();

        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM automations $whereSQL");
        $stmtC->execute($params);
        $total = (int) $stmtC->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT id, created_at AS addJobTime, user_name AS userName,
                    type_of_job AS typeOfJob, condition_of_type AS conditionOfType,
                    value_of_type AS valueOfType, mailto, cc_mail AS toCcmail,
                    msg_from_user AS msgFromUser, status_of_job AS statusOfJob,
                    is_active AS isactive, upto_date AS UptoDate,
                    status_change_time AS statusChangeTime
             FROM automations $whereSQL
             ORDER BY is_active DESC, created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute(array_merge($params, [$limit, $offset]));

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public static function create(array $d): int
    {
        return DB::insert(
            'INSERT INTO automations
                (user_id, user_name, user_mail, type_of_job,
                 value_of_type, condition_of_type,
                 mailto, cc_mail, msg_from_user,
                 max_run, run_even_diff, current_save_value, upto_date)
             VALUES (?,?,?,?, ?,?, ?,?,?, ?,?,?,?)',
            [
                $d['userID'],
                $d['userName'],
                $d['userMail'],
                $d['typeOfJob'],
                $d['valueOfType']            ?? null,
                $d['conditionOfType']        ?? null,
                $d['mailto'],
                $d['toCcmail']               ?? null,
                $d['msgFromUser']            ?? null,
                $d['maxRun']                 ?? 1,
                $d['runevenvalueOfTypeDiff'] ?? 0,
                $d['currentSaveValue']       ?? null,
                $d['UptoDate'],
            ]
        );
    }

    // ── Cancel ───────────────────────────────────────────────────────────────

    public static function cancel(int $id, int $userId, bool $isAdmin = false): bool
    {
        if ($isAdmin) {
            return DB::execute(
                "UPDATE automations SET is_active=0, status_of_job='בוטל',
                  status_change_time=NOW() WHERE id=?",
                [$id]
            ) > 0;
        }
        return DB::execute(
            "UPDATE automations SET is_active=0, status_of_job='בוטל',
              status_change_time=NOW() WHERE id=? AND user_id=?",
            [$id, $userId]
        ) > 0;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** סטטוסי קריאות — מ-alon_db (callStatus) */
    public static function callStatuses(): array
    {
        $stmt = DB::v1()->prepare(
            'SELECT statuscallid AS id, statusDesc AS label
             FROM callStatus ORDER BY statusDesc'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** תרגום statusId → תיאור עברי */
    public static function translateStatus(string $statusId): string
    {
        $stmt = DB::v1()->prepare(
            'SELECT statusDesc FROM callStatus WHERE statuscallid=? LIMIT 1'
        );
        $stmt->execute([$statusId]);
        return (string) $stmt->fetchColumn();
    }

    /** חישוב תאריך תפוגה */
    public static function expiryDate(string $unit, int $qty): string
    {
        $dt = new \DateTime();
        $dt->modify("+{$qty} {$unit}");
        return $dt->format('Y-m-d H:i:s');
    }

    /** רשימת נציגים לפילטר */
    public static function agents(): array
    {
        return DB::query(
            'SELECT DISTINCT user_id AS userID, user_name AS userName
             FROM automations WHERE user_name != \'\' ORDER BY user_name ASC'
        );
    }
}
