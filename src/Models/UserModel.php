<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class UserModel
{
    /* ══════════════════════════════════════════════════════════════
       קטגוריות הרשאות — מוצגות בעמוד ניהול הרשאות
       מפתח = permission_key,  ערך = תיאור עברי
       ══════════════════════════════════════════════════════════════ */
    public const PERM_CATEGORIES = [
        'מרכזיה וטלפוניה' => [
            'pbxSearch'            => 'חיפוש שיחות מרכזיה',
            'pbxRecordings'        => 'האזנה להקלטות',
            'canSeeCallRec'        => 'צפייה בהקלטות (V1)',
            'canSearchCall'        => 'חיפוש שיחות (V1)',
            'canSearchStoresCalls' => 'חיפוש שיחות חנות',
            'canViewCallsStats'    => 'סטטיסטיקת שיחות',
            'canupdatemycall'      => 'עדכון שיחה',
        ],
        'מעבדה' => [
            'GetCanSearchLabCalls' => 'חיפוש קריאות מעבדה',
            'canEditLabInv'        => 'עריכת מלאי מעבדה',
            'canTakeLabItems'      => 'הוצאת פריטי מעבדה',
        ],
        'חנויות ולקוחות' => [
            'canEditStore'                   => 'עריכת חנויות',
            'allowSmsFromCrm'                => 'שליחת SMS/WA',
            'allowToAddCallerNoteFromMain'   => 'הוספת הערת מתקשר',
            'canReportPelephon'              => 'דיווח פלאפון',
        ],
        'מוקד ושירות' => [
            'canViewMokedData'  => 'צפייה סיסמאות מוקד',
            'canEditMokedData'  => 'עריכת סיסמאות מוקד',
            'canAddAccounts'    => 'הוספת סיסמאות מוקד',
            'canSeeFloatMsg'    => 'הודעות צפות',
            'canAddReport'      => 'הוספת דיווח',
            'canEditSupportPro' => 'עריכת מוצרי תמיכה',
            'canManageDuty'     => 'ניהול תורנות',
        ],
        'כספים' => [
            'canEditBonus'           => 'עריכת בונוס',
            'canUseInvoiceChangeName'=> 'שינוי שם חשבונית',
        ],
        'ניהול מערכת' => [
            'canAddUsers'          => 'הוספת משתמשים',
            'canEditDB'            => 'עריכת DB / Nav',
            'canAddAutomation'     => 'הוספת אוטומציה',
            'automation.viewAll'   => 'צפייה בכל האוטומציות (כל נציגים)',
            'canFormatter'         => 'עריכת פורמטור',
            'canOrianorder'        => 'הזמנות אוריאן',
            'canViewLogs'          => 'צפייה בלוג פעולות',
            'task_settings.manage' => 'ניהול הגדרות משימות',
            'tasks.viewAll'        => 'צפייה בכל המשימות (כל נציגים)',
        ],
    ];

    /* flat map — backward compat + savePermGroup */
    public const PERM_LABELS = [
        'pbxSearch'                    => 'חיפוש שיחות מרכזיה',
        'pbxRecordings'                => 'האזנה להקלטות',
        'canSeeCallRec'                => 'צפייה בהקלטות (V1)',
        'canSearchCall'                => 'חיפוש שיחות (V1)',
        'allowSmsFromCrm'              => 'שליחת SMS/WA',
        'GetCanSearchLabCalls'         => 'חיפוש קריאות מעבדה',
        'allowToAddCallerNoteFromMain' => 'הוספת הערת מתקשר',
        'canReportPelephon'            => 'דיווח פלאפון',
        'canSeeFloatMsg'               => 'הודעות צפות',
        'canAddAutomation'             => 'הוספת אוטומציה',
        'automation.viewAll'           => 'צפייה בכל האוטומציות (כל נציגים)',
        'canAddReport'                 => 'הוספת דיווח',
        'canEditDB'                    => 'עריכת DB / Nav',
        'canViewMokedData'             => 'צפייה בסיסמאות מוקד',
        'canEditMokedData'             => 'עריכת סיסמאות מוקד',
        'canAddAccounts'               => 'הוספת סיסמאות מוקד',
        'canAddUsers'                  => 'הוספת משתמשים',
        'canEditSupportPro'            => 'עריכת מוצרי תמיכה',
        'canEditStore'                 => 'עריכת חנויות',
        'canSearchStoresCalls'         => 'חיפוש שיחות חנות',
        'canViewCallsStats'            => 'סטטיסטיקת שיחות',
        'canupdatemycall'              => 'עדכון שיחה',
        'canEditBonus'                 => 'עריכת בונוס',
        'canUseInvoiceChangeName'      => 'שינוי שם חשבונית',
        'canEditLabInv'                => 'עריכת מלאי מעבדה',
        'canTakeLabItems'              => 'הוצאת פריטי מעבדה',
        'canFormatter'                 => 'עריכת פורמטור',
        'canOrianorder'                => 'הזמנות אוריאן',
        'canViewLogs'                  => 'צפייה בלוג פעולות',
        'canManageDuty'                => 'ניהול תורנות',
        'task_settings.manage'         => 'ניהול הגדרות משימות',
        'tasks.viewAll'                => 'צפייה בכל המשימות (כל נציגים)',
    ];

    /* ══ CRUD ══════════════════════════════════════════════════════ */

    public static function all(): array
    {
        return DB::query(
            'SELECT u.id, u.first_name, u.last_name, u.email,
                    u.phone, u.department_id, u.is_active, u.last_login,
                    u.created_at, u.must_change_password,
                    u.permission_group_id,
                    d.name_heb  AS dept_name,
                    pg.name_heb AS group_name
             FROM users u
             LEFT JOIN departments       d  ON d.id  = u.department_id
             LEFT JOIN permission_groups pg ON pg.id = u.permission_group_id
             ORDER BY u.created_at DESC, u.last_login DESC'
        );
    }

    public static function byId(int $id): ?array
    {
        return DB::row(
            'SELECT u.*, d.name_heb AS dept_name, pg.name_heb AS group_name
             FROM users u
             LEFT JOIN departments       d  ON d.id  = u.department_id
             LEFT JOIN permission_groups pg ON pg.id = u.permission_group_id
             WHERE u.id = ?',
            [$id]
        );
    }

    public static function search(string $q): array
    {
        $like = '%' . trim($q) . '%';
        return DB::query(
            "SELECT u.id, u.first_name, u.last_name, u.phone, u.email,
                    d.name_heb  AS dept_name,
                    pg.name_heb AS group_name
             FROM users u
             LEFT JOIN departments       d  ON d.id  = u.department_id
             LEFT JOIN permission_groups pg ON pg.id = u.permission_group_id
             WHERE u.is_active=1
               AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?
                    OR CONCAT(u.first_name,' ',u.last_name) LIKE ?)
             ORDER BY u.first_name ASC, u.last_name ASC
             LIMIT 30",
            [$like, $like, $like, $like, $like]
        );
    }

    public static function setTempPassword(int $id, string $password): void
    {
        DB::execute(
            'UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?',
            [password_hash($password, PASSWORD_BCRYPT), $id]
        );
    }

    public static function save(array $d): void
    {
        if ($d['id']) {
            DB::execute(
                'UPDATE users SET
                    first_name=?, last_name=?, email=?,
                    phone=?, department_id=?, is_active=?,
                    permission_group_id=?, note=?,
                    mvoice_id=?, sip_voice=?
                 WHERE id=?',
                [
                    $d['first_name'], $d['last_name'], $d['email'],
                    $d['phone'], $d['department_id'] ?: null,
                    $d['is_active'] ? 1 : 0,
                    $d['permission_group_id'] ?: null,
                    $d['note'],
                    $d['mvoice_id'] ?: null,
                    $d['sip_voice']  ?: null,
                    $d['id'],
                ]
            );
        } else {
            $hash = password_hash($d['password'] ?? bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
            DB::execute(
                'INSERT INTO users
                    (first_name, last_name, email, phone, department_id,
                     is_active, permission_group_id, note, password_hash, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,NOW())',
                [
                    $d['first_name'], $d['last_name'], $d['email'],
                    $d['phone'], $d['department_id'] ?: null,
                    $d['is_active'] ? 1 : 0,
                    $d['permission_group_id'] ?: null,
                    $d['note'], $hash,
                ]
            );
        }
    }

    public static function resetPassword(int $id, string $newPass): void
    {
        DB::execute(
            'UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?',
            [password_hash($newPass, PASSWORD_BCRYPT), $id]
        );
    }

    public static function toggleActive(int $id): int
    {
        DB::execute('UPDATE users SET is_active = 1 - is_active WHERE id = ?', [$id]);
        return (int)DB::value('SELECT is_active FROM users WHERE id = ?', [$id]);
    }

    public static function departments(): array
    {
        return DB::query('SELECT id, name_heb AS `desc` FROM departments ORDER BY id');
    }

    public static function permGroups(): array
    {
        return DB::query(
            'SELECT id, name_heb AS permmisionsGroupHeb FROM permission_groups ORDER BY id'
        );
    }

    /**
     * שולף קבוצות + הרשאות + מספר משתמשים לכל קבוצה
     */
    public static function allPermGroups(): array
    {
        $groups = DB::query(
            'SELECT pg.id, pg.name_heb, pg.name_eng,
                    COUNT(u.id) AS user_count
             FROM permission_groups pg
             LEFT JOIN users u ON u.permission_group_id = pg.id AND u.is_active = 1
             GROUP BY pg.id
             ORDER BY pg.id'
        );

        $grants = DB::query('SELECT group_id, permission_key, granted FROM permission_group_grants');

        $grantMap = [];
        foreach ($grants as $g) {
            $grantMap[$g['group_id']][$g['permission_key']] = (int)$g['granted'];
        }

        foreach ($groups as &$grp) {
            $gid = $grp['id'];
            $grp['permmisionsGroupHeb'] = $grp['name_heb'];
            $grp['permmisionsGroupID']  = $gid;
            // כל ההרשאות הידועות + כל מה שב-DB (דינאמי)
            $allKeys = array_unique(array_merge(
                array_keys(self::PERM_LABELS),
                array_keys($grantMap[$gid] ?? [])
            ));
            foreach ($allKeys as $key) {
                $grp[$key] = $grantMap[$gid][$key] ?? 0;
            }
        }
        return $groups;
    }

    /**
     * שומר הרשאות לקבוצה — שומר את כל ה-PERM_LABELS + כל מה שנשלח
     */
    public static function savePermGroup(int $groupId, array $perms): void
    {
        if (!$groupId) return;

        $pdo = DB::get();

        // מחק הכל ורשום מחדש
        $pdo->prepare('DELETE FROM permission_group_grants WHERE group_id = ?')
            ->execute([$groupId]);

        $stmt = $pdo->prepare(
            'INSERT INTO permission_group_grants (group_id, permission_key, granted)
             VALUES (?, ?, ?)'
        );

        foreach (self::PERM_LABELS as $key => $_) {
            // $perms[נקודה.מפתח] מגיע תקין כי שלחנו JSON
            $granted = isset($perms[$key]) && ($perms[$key] == 1) ? 1 : 0;
            $stmt->execute([$groupId, $key, $granted]);
        }
    }

    public static function customerServiceUsers(): array
    {
        return DB::query(
            "SELECT u.id, CONCAT(u.first_name,' ',u.last_name) AS name
             FROM users u
             JOIN departments d ON d.id = u.department_id
             WHERE u.is_active = 1
               AND (d.name_heb LIKE '%שירות%' OR d.name_heb LIKE '%מוקד%')
             ORDER BY u.first_name ASC"
        );
    }
}