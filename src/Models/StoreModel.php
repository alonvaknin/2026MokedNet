<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class StoreModel
{
    // ── Log-safe field list — כל השדות שרלוונטיים לdiff ──────────
    private const LOG_FIELDS = [
        'store_num','name','type','city','address',
        'phone_main','phone_cell','email','manager_name','manager_cell',
        'mvoice_queue','telephone_line_num','alert_note','note',
        'tags','is_active','is_display',
    ];

    public static function allBugStores(): array
    {
        return DB::query(
            "SELECT id,store_num,name,type,city,address,phone_main,phone_cell,email,
                    mvoice_queue,telephone_line_num,alert_note,alert_updated_at,
                    manager_name,manager_cell,is_active,tags,note,work_hours
             FROM stores WHERE is_active=1 AND type='סניף באג' ORDER BY CAST(store_num AS UNSIGNED) ASC"
        );
    }

    public static function allModanStores(): array
    {
        return DB::query(
            "SELECT id,store_num,name,type,city,address,phone_main,phone_cell,email,
                    mvoice_queue,telephone_line_num,alert_note,alert_updated_at,
                    manager_name,manager_cell,is_active,tags,note,work_hours
             FROM stores WHERE is_active=1 AND type='נקודת מודן' ORDER BY name ASC"
        );
    }

    public static function types(): array
    {
        return DB::query(
            'SELECT DISTINCT type FROM stores WHERE is_active=1 AND type IS NOT NULL ORDER BY type'
        );
    }

    public static function allCities(): array
    {
        return DB::query(
            "SELECT DISTINCT city FROM stores
             WHERE is_active=1 AND city IS NOT NULL AND city!=''
             ORDER BY city"
        );
    }

    public static function search(string $q, string $type = '', string $city = ''): array
    {
        $like   = '%' . trim($q) . '%';
        $sql    = "SELECT id,store_num,name,type,city,phone_main,phone_cell,
                          manager_name,manager_cell,mvoice_queue,telephone_line_num,
                          alert_note,is_active,is_display,tags
                   FROM stores
                   WHERE is_active=1
                     AND (name LIKE ? OR store_num LIKE ? OR phone_main LIKE ?
                          OR phone_cell LIKE ? OR manager_name LIKE ? OR city LIKE ?)";
        $params = [$like,$like,$like,$like,$like,$like];
        if ($type) { $sql .= ' AND type=?';  $params[] = $type; }
        if ($city) { $sql .= ' AND city=?';  $params[] = $city; }
        return DB::query($sql . ' ORDER BY store_num ASC LIMIT 100', $params);
    }

    public static function byNum(string $sNum): ?array
    {
        return DB::row('SELECT * FROM stores WHERE store_num=? LIMIT 1', [$sNum]);
    }

    public static function byId(int $id): ?array
    {
        return DB::row('SELECT * FROM stores WHERE id=? LIMIT 1', [$id]);
    }

    /** מחזיר רק שדות הlog — לdiff מדויק ונקי */
    public static function logSnapshot(int $id): array
    {
        $row = self::byId($id);
        if (!$row) return [];
        return array_intersect_key($row, array_flip(self::LOG_FIELDS));
    }

    public static function byType(string $type): array
    {
        return DB::query(
            'SELECT id,store_num,name,phone_main,mvoice_queue,telephone_line_num
             FROM stores WHERE type=? AND is_active=1 ORDER BY store_num',
            [$type]
        );
    }

    public static function create(array $d): int
    {
        return DB::insert(
            'INSERT INTO stores
                (store_num,name,type,city,address,
                 phone_main,phone_cell,email,manager_name,manager_cell,
                 mvoice_queue,telephone_line_num,
                 alert_note,note,tags,
                 is_active,is_display,
                 created_at,updated_at)
             VALUES (?,?,?,?,?, ?,?,?,?,?, ?,?, ?,?,?, ?,?, NOW(),NOW())',
            [
                $d['store_num']         ?: null,
                $d['name'],
                $d['type']              ?: 'סניף באג',
                $d['city']              ?: null,
                $d['address']           ?: null,
                $d['phone_main']        ?: null,
                $d['phone_cell']        ?: null,
                $d['email']             ?: null,
                $d['manager_name']      ?: null,
                $d['manager_cell']      ?: null,
                $d['mvoice_queue']      ?: null,
                $d['telephone_line_num']?: null,
                $d['alert_note']        ?: null,
                $d['note']              ?: null,
                $d['tags']              ?: null,
                ($d['is_active']  ?? true)  ? 1 : 0,
                ($d['is_display'] ?? true)  ? 1 : 0,
            ]
        );
    }

    public static function update(int $id, array $d): void
    {
        // alert_updated_at — מתעדכן רק אם alert_note השתנה
        $prev = self::byId($id);
        $alertChanged = ($prev['alert_note'] ?? '') !== ($d['alert_note'] ?? '');

        DB::execute(
            'UPDATE stores SET
                store_num=?,name=?,type=?,city=?,address=?,
                phone_main=?,phone_cell=?,email=?,manager_name=?,manager_cell=?,
                mvoice_queue=?,telephone_line_num=?,
                alert_note=?,note=?,tags=?,
                is_active=?,is_display=?,
                alert_updated_at=' . ($alertChanged ? 'NOW()' : 'alert_updated_at') . ',
                updated_at=NOW()
             WHERE id=?',
            [
                $d['store_num']         ?: null,
                $d['name'],
                $d['type']              ?: 'סניף באג',
                $d['city']              ?: null,
                $d['address']           ?: null,
                $d['phone_main']        ?: null,
                $d['phone_cell']        ?: null,
                $d['email']             ?: null,
                $d['manager_name']      ?: null,
                $d['manager_cell']      ?: null,
                $d['mvoice_queue']      ?: null,
                $d['telephone_line_num']?: null,
                $d['alert_note']        ?: null,
                $d['note']              ?: null,
                $d['tags']              ?: null,
                ($d['is_active']  ?? true)  ? 1 : 0,
                ($d['is_display'] ?? true)  ? 1 : 0,
                $id,
            ]
        );
    }

    public static function updateWorkHours(string $storeNum, string $workHours): int
    {
        return DB::execute(
            "UPDATE stores SET work_hours=?, updated_at=NOW()
             WHERE store_num=? AND type='סניף באג' AND is_active=1",
            [$workHours, $storeNum]
        );
    }

    public static function toggleActive(int $id): int
    {
        DB::execute(
            'UPDATE stores SET is_active=1-is_active, updated_at=NOW() WHERE id=?',
            [$id]
        );
        return (int) DB::value('SELECT is_active FROM stores WHERE id=?', [$id]);
    }
}
