<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class DutyModel
{
    private static array $DEPTS = ['שירות לקוחות', 'תמיכה טכנית', 'אינטרנט ותוכן'];

    // ── Representatives ──────────────────────────────────────────────────────
    public static function allReps(): array
    {
        return DB::query(
            "SELECT dr.*, CONCAT(u.first_name,' ',u.last_name) AS system_username
             FROM duty_representatives dr
             LEFT JOIN users u ON u.id = dr.user_id
             WHERE dr.is_active = 1
             ORDER BY dr.department ASC, dr.total_duties ASC, dr.name ASC"
        );
    }

    public static function allUsers(): array
    {
        return DB::query(
            "SELECT id, CONCAT(first_name,' ',last_name) AS full_name FROM users WHERE is_active=1 ORDER BY first_name ASC"
        );
    }

    public static function createRep(array $d): int
    {
        return DB::insert(
            'INSERT INTO duty_representatives (name, department, user_id) VALUES (?, ?, ?)',
            [$d['name'], $d['department'], $d['user_id']]
        );
    }

    public static function updateRep(int $id, array $d): void
    {
        DB::execute(
            'UPDATE duty_representatives SET name=?, department=?, user_id=? WHERE id=?',
            [$d['name'], $d['department'], $d['user_id'], $id]
        );
    }

    public static function deleteRep(int $id): void
    {
        DB::execute('UPDATE duty_representatives SET is_active=0 WHERE id=?', [$id]);
    }

    // ── Schedule ─────────────────────────────────────────────────────────────
    // שורה אחת לשבוע — נציג אחד תורן לכל המוקד
    public static function scheduleWeeks(): array
    {
        return DB::query(
            "SELECT ds.id, ds.week_start, ds.department, ds.status, ds.notes,
                    dr.id AS rep_id, dr.name AS rep_name
             FROM duty_schedule ds
             JOIN duty_representatives dr ON dr.id = ds.representative_id
             ORDER BY ds.week_start DESC
             LIMIT 30"
        );
    }

    // הסבב: מחלקה אחרי מחלקה לפי הסדר
    public static function nextDept(): string
    {
        $last = DB::row(
            "SELECT department FROM duty_schedule ORDER BY week_start DESC LIMIT 1"
        );
        if (!$last) return self::$DEPTS[0];
        $idx = array_search($last['department'], self::$DEPTS, true);
        return self::$DEPTS[($idx + 1) % count(self::$DEPTS)];
    }

    public static function autoAssignNextWeek(): array
    {
        // מוצא את השבוע הריק הבא (השבוע הראשון אחרי כל המשובצים, או השבוע הקרוב)
        $day = (int)date('w');
        $daysUntil = $day === 0 ? 7 : (7 - $day);
        $nextSunday = date('Y-m-d', strtotime("+{$daysUntil} days"));

        $last = DB::row('SELECT MAX(week_start) AS last FROM duty_schedule WHERE week_start >= ?', [$nextSunday]);
        $targetSunday = ($last && $last['last'])
            ? date('Y-m-d', strtotime($last['last'] . ' +7 days'))
            : $nextSunday;

        $dept = self::nextDept();

        // הנציג עם הכי פחות תורנויות מהמחלקה הנוכחית
        $rep = DB::row(
            'SELECT id, name FROM duty_representatives
             WHERE department=? AND is_active=1
             ORDER BY total_duties ASC, id ASC LIMIT 1',
            [$dept]
        );
        if (!$rep) {
            return ['ok' => false, 'message' => "אין נציגים פעילים במחלקה: $dept"];
        }

        DB::execute(
            'INSERT INTO duty_schedule (week_start, department, representative_id) VALUES (?,?,?)',
            [$targetSunday, $dept, $rep['id']]
        );
        DB::execute(
            'UPDATE duty_representatives SET total_duties=total_duties+1 WHERE id=?',
            [$rep['id']]
        );

        return ['ok' => true, 'week' => $targetSunday, 'dept' => $dept, 'rep' => $rep['name']];
    }

    public static function manualAssign(string $weekStart, int $repId): array
    {
        $rep = DB::row('SELECT id, name, department FROM duty_representatives WHERE id=? AND is_active=1', [$repId]);
        if (!$rep) return ['ok' => false, 'message' => 'נציג לא נמצא'];

        $exists = DB::row('SELECT id FROM duty_schedule WHERE week_start=? LIMIT 1', [$weekStart]);
        if ($exists) {
            // עדכון קיים
            $old = DB::row('SELECT representative_id FROM duty_schedule WHERE week_start=?', [$weekStart]);
            if ($old && (int)$old['representative_id'] !== $repId) {
                DB::execute('UPDATE duty_representatives SET total_duties=GREATEST(total_duties-1,0) WHERE id=?', [$old['representative_id']]);
                DB::execute('UPDATE duty_representatives SET total_duties=total_duties+1 WHERE id=?', [$repId]);
            }
            DB::execute(
                'UPDATE duty_schedule SET representative_id=?, department=? WHERE week_start=?',
                [$repId, $rep['department'], $weekStart]
            );
        } else {
            DB::execute(
                'INSERT INTO duty_schedule (week_start, department, representative_id) VALUES (?,?,?)',
                [$weekStart, $rep['department'], $repId]
            );
            DB::execute('UPDATE duty_representatives SET total_duties=total_duties+1 WHERE id=?', [$repId]);
        }

        return ['ok' => true];
    }

    public static function deleteSchedule(int $id): array
    {
        $row = DB::row('SELECT week_start, representative_id FROM duty_schedule WHERE id=? LIMIT 1', [$id]);
        if (!$row) return ['ok' => false, 'error' => 'לא נמצא'];
        if ($row['week_start'] <= date('Y-m-d')) return ['ok' => false, 'error' => 'לא ניתן למחוק שבוע שעבר'];
        DB::execute('UPDATE duty_representatives SET total_duties=GREATEST(total_duties-1,0) WHERE id=?', [$row['representative_id']]);
        DB::execute('DELETE FROM duty_schedule WHERE id=?', [$id]);
        return ['ok' => true];
    }

    public static function updateSchedule(int $id, int $repId, string $status, string $notes): void
    {
        $old = DB::row('SELECT representative_id FROM duty_schedule WHERE id=? LIMIT 1', [$id]);
        if ($old && (int)$old['representative_id'] !== $repId) {
            DB::execute('UPDATE duty_representatives SET total_duties=GREATEST(total_duties-1,0) WHERE id=?', [$old['representative_id']]);
            DB::execute('UPDATE duty_representatives SET total_duties=total_duties+1 WHERE id=?', [$repId]);
        }
        $rep = DB::row('SELECT department FROM duty_representatives WHERE id=?', [$repId]);
        DB::execute(
            'UPDATE duty_schedule SET representative_id=?, department=?, status=?, notes=? WHERE id=?',
            [$repId, $rep['department'] ?? '', $status, $notes, $id]
        );
    }

    // ── Daily Guidance ───────────────────────────────────────────────────────
    public static function allGuidance(): array
    {
        return DB::query(
            "SELECT * FROM duty_daily_guidance
             ORDER BY FIELD(day_of_week,'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')"
        );
    }

    public static function saveGuidance(string $day, string $guidance): void
    {
        DB::execute(
            'INSERT INTO duty_daily_guidance (day_of_week, guidance) VALUES (?,?)
             ON DUPLICATE KEY UPDATE guidance=?',
            [$day, $guidance, $guidance]
        );
    }

    // ── Current week (dashboard + signage) ──────────────────────────────────
    public static function currentWeek(): array
    {
        $day    = (int)date('w');
        $sunday = date('Y-m-d', strtotime("-{$day} days"));
        $today  = date('l');

        $schedule = DB::row(
            "SELECT ds.department, ds.status, ds.notes, dr.name AS rep_name
             FROM duty_schedule ds
             JOIN duty_representatives dr ON dr.id = ds.representative_id
             WHERE ds.week_start = ? LIMIT 1",
            [$sunday]
        );

        $guidance = DB::row(
            'SELECT guidance FROM duty_daily_guidance WHERE day_of_week=? LIMIT 1',
            [$today]
        );

        return [
            'week_start'     => $sunday,
            'schedule'       => $schedule,
            'today_guidance' => $guidance['guidance'] ?? null,
        ];
    }
}
