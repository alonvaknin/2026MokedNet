<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class HoursModel
{
    private const SELECT = 'SELECT id, user_id, work_date, entry_type, time_in, time_out,
                                   requires, note, status, marked_for_export, exported_at,
                                   created_by, filled_by, created_at, updated_at
                            FROM hours_entries';

    public static function find(int $id): ?array
    {
        return DB::row(self::SELECT . ' WHERE id = ?', [$id]);
    }

    /** $month בפורמט YYYY-MM */
    public static function forUserMonth(int $userId, string $month): array
    {
        return DB::query(
            self::SELECT . " WHERE user_id = ? AND DATE_FORMAT(work_date, '%Y-%m') = ?
                             ORDER BY work_date ASC, id ASC",
            [$userId, $month]
        );
    }

    public static function pendingCount(int $userId): int
    {
        return (int)DB::value(
            "SELECT COUNT(*) FROM hours_entries WHERE user_id = ? AND status = 'requested'",
            [$userId]
        );
    }

    public static function pendingForUser(int $userId): array
    {
        return DB::query(
            self::SELECT . " WHERE user_id = ? AND status = 'requested'
                             ORDER BY work_date ASC, id ASC",
            [$userId]
        );
    }

    /**
     * האם השורה עומדת בדרישה שהוגדרה לה.
     * סיבת היעדרות (כל entry_type שאינו regular) סוגרת כל שורה.
     */
    public static function isComplete(array $row): bool
    {
        if (($row['entry_type'] ?? 'regular') !== 'regular') return true;

        $in  = !empty($row['time_in']);
        $out = !empty($row['time_out']);

        return match ($row['requires'] ?? 'both') {
            'in'    => $in,
            'out'   => $out,
            default => $in && $out,
        };
    }

    public static function createEntry(array $d): int
    {
        $status = self::isComplete($d) ? 'filled' : 'requested';
        return DB::insert(
            'INSERT INTO hours_entries
               (user_id, work_date, entry_type, time_in, time_out, requires, note,
                status, created_by, filled_by)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [
                $d['user_id'], $d['work_date'], $d['entry_type'] ?? 'regular',
                ($d['time_in'] ?? null) ?: null, ($d['time_out'] ?? null) ?: null,
                $d['requires'] ?? 'both',
                $d['note'] ?? null, $status, $d['created_by'] ?? null,
                $status === 'filled' ? ($d['created_by'] ?? null) : null,
            ]
        );
    }

    public static function updateEntry(int $id, array $d): void
    {
        $status = $d['status'] ?? 'requested';
        DB::execute(
            'UPDATE hours_entries
                SET entry_type = ?, time_in = ?, time_out = ?, note = ?,
                    status = ?, filled_by = ?
              WHERE id = ?',
            [
                $d['entry_type'] ?? 'regular',
                ($d['time_in'] ?? null) ?: null, ($d['time_out'] ?? null) ?: null,
                $d['note'] ?? null, $status,
                // filled_by נמחק כששורה חוזרת ל-requested, כדי שלא יישאר ערך מטעה
                $status === 'filled' ? ($d['filled_by'] ?? null) : null,
                $id,
            ]
        );
    }

    /* ── שכבת המנהל ── */

    /**
     * מי שמסומן לדיווח שעות בכרטיס המשתמש (users.hours_reports).
     * הסימון פרטני ואינו נגזר מקבוצת ההרשאות.
     */
    public static function activeUsers(): array
    {
        return DB::query(
            "SELECT id, CONCAT(first_name,' ',last_name) AS full_name
             FROM users
             WHERE is_active = 1 AND hours_reports = 1
             ORDER BY first_name ASC, last_name ASC"
        );
    }

    /** [userId => ['YYYY-MM-DD' => [rows...]]] */
    /** שורות סגורות (יוצאו לדיווח) אינן מוצגות ברשת החודשית */
    public static function monthGrid(string $month): array
    {
        $rows = DB::query(
            self::SELECT . " WHERE DATE_FORMAT(work_date, '%Y-%m') = ?
                               AND exported_at IS NULL
                             ORDER BY work_date ASC, id ASC",
            [$month]
        );
        $grid = [];
        foreach ($rows as $r) {
            $grid[(int)$r['user_id']][$r['work_date']][] = $r;
        }
        return $grid;
    }

    /**
     * כל שורות החודש כרשימה שטוחה, עם שם העובד — עבור טבלת הדרישות
     * המרכזת בלוח המנהל. ממוין: ממתינות קודם, ואז לפי תאריך.
     */
    public static function monthList(string $month): array
    {
        return DB::query(
            "SELECT h.*, CONCAT(u.first_name,' ',u.last_name) AS full_name
             FROM hours_entries h
             LEFT JOIN users u ON u.id = h.user_id
             WHERE DATE_FORMAT(h.work_date, '%Y-%m') = ?
             ORDER BY (h.status = 'requested') DESC, h.work_date ASC, u.first_name ASC, h.id ASC",
            [$month]
        );
    }

    /**
     * שורות התא במודל — ללא שורות סגורות, בעקבות הרשת.
     * שורה סגורה עדיין נראית (וניתנת לפתיחה מחדש) בטבלת הדרישות.
     */
    public static function forUserDate(int $userId, string $date): array
    {
        return DB::query(
            self::SELECT . ' WHERE user_id = ? AND work_date = ?
                               AND exported_at IS NULL
                             ORDER BY id ASC',
            [$userId, $date]
        );
    }

    /** עדכון בידי מנהל — כולל requires, שהנציג אינו רשאי לשנות */
    public static function managerUpdate(int $id, array $d): void
    {
        $status = self::isComplete($d) ? 'filled' : 'requested';
        DB::execute(
            'UPDATE hours_entries
                SET entry_type = ?, time_in = ?, time_out = ?, requires = ?,
                    note = ?, status = ?, filled_by = ?
              WHERE id = ?',
            [
                $d['entry_type'] ?? 'regular',
                ($d['time_in'] ?? null) ?: null, ($d['time_out'] ?? null) ?: null,
                $d['requires'] ?? 'both', $d['note'] ?? null, $status,
                $status === 'filled' ? ($d['filled_by'] ?? null) : null, $id,
            ]
        );
    }

    public static function deleteEntry(int $id): void
    {
        DB::execute('DELETE FROM hours_entries WHERE id = ?', [$id]);
    }

    public static function setMark(int $id, bool $on): void
    {
        DB::execute('UPDATE hours_entries SET marked_for_export = ? WHERE id = ?', [$on ? 1 : 0, $id]);
    }

    public static function markedCount(): int
    {
        return (int)DB::value(
            'SELECT COUNT(*) FROM hours_entries
              WHERE marked_for_export = 1 AND exported_at IS NULL'
        );
    }

    public static function markedRows(): array
    {
        return DB::query(
            "SELECT h.*, CONCAT(u.first_name,' ',u.last_name) AS full_name
             FROM hours_entries h
             LEFT JOIN users u ON u.id = h.user_id
             WHERE h.marked_for_export = 1
               AND h.exported_at IS NULL
             ORDER BY u.first_name ASC, h.work_date ASC, h.id ASC"
        );
    }

    /**
     * "סגירת" שורות: מחתים exported_at ומנקה את סימון הייצוא, כדי ששורה
     * סגורה לא תיכלל שוב בקובץ הבא. שורה סגורה נעולה לעריכה בממשק.
     */
    public static function stampExported(array $ids): void
    {
        if (!$ids) return;
        $ph = implode(',', array_fill(0, count($ids), '?'));
        DB::execute(
            "UPDATE hours_entries
                SET exported_at = NOW(), marked_for_export = 0
              WHERE id IN ($ph)",
            array_map('intval', array_values($ids))
        );
    }

    /** סגירה ידנית של שורות שנבחרו, ללא הורדת קובץ */
    public static function closeRows(array $ids): int
    {
        if (!$ids) return 0;
        $ph = implode(',', array_fill(0, count($ids), '?'));
        return DB::execute(
            "UPDATE hours_entries
                SET exported_at = NOW(), marked_for_export = 0
              WHERE id IN ($ph) AND exported_at IS NULL",
            array_map('intval', array_values($ids))
        );
    }

    /** פתיחה מחדש של שורה שנסגרה */
    public static function reopenRows(array $ids): int
    {
        if (!$ids) return 0;
        $ph = implode(',', array_fill(0, count($ids), '?'));
        return DB::execute(
            "UPDATE hours_entries SET exported_at = NULL WHERE id IN ($ph)",
            array_map('intval', array_values($ids))
        );
    }
}
