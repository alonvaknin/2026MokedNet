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
}
