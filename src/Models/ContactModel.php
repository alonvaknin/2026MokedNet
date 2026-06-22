<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class ContactModel
{
    private const LOG_FIELDS = [
        'first_name','last_name','email','phone','phone2',
        'website','role','department','contact_type',
        'address','tags','note','is_active','is_contacts_list',
    ];

    public static function byId(int $id): ?array
    {
        return DB::row('SELECT * FROM contacts WHERE id=? LIMIT 1', [$id]);
    }

    /** snapshot רק שדות log — לדיף נקי ללא timestamps */
    public static function logSnapshot(int $id): array
    {
        $row = self::byId($id);
        if (!$row) return [];
        return array_intersect_key($row, array_flip(self::LOG_FIELDS));
    }

    /** Full-text search */
    public static function search(string $q, string $dept = '', string $type = ''): array
    {
        $like   = '%' . trim($q) . '%';
        $sql    = "SELECT * FROM contacts WHERE is_active=1
                   AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?
                        OR phone LIKE ? OR phone2 LIKE ? OR role LIKE ?
                        OR department LIKE ? OR contact_type LIKE ?
                        OR tags LIKE ? OR address LIKE ?
                        OR CONCAT(first_name,' ',last_name) LIKE ?)";
        $params = [$like,$like,$like,$like,$like,$like,$like,$like,$like,$like,$like];
        if ($dept) { $sql .= ' AND department=?'; $params[] = $dept; }
        if ($type) { $sql .= ' AND contact_type=?'; $params[] = $type; }
        return DB::query($sql . ' ORDER BY first_name ASC LIMIT 80', $params);
    }

    /** פעילים בלבד */
    public static function all(string $type = ''): array
    {
        $sql    = "SELECT * FROM contacts WHERE is_active=1";
        $params = [];
        if ($type) { $sql .= ' AND contact_type=?'; $params[] = $type; }
        return DB::query($sql . ' ORDER BY first_name ASC, last_name ASC', $params);
    }

    /** כולל לא-פעילים — לתצוגת טבלה */
    public static function allIncludingInactive(string $type = ''): array
    {
        $sql    = "SELECT * FROM contacts";
        $params = [];
        if ($type) { $sql .= ' WHERE contact_type=?'; $params[] = $type; }
        return DB::query($sql . ' ORDER BY is_active DESC, first_name ASC, last_name ASC', $params);
    }

    public static function departments(): array
    {
        return DB::query(
            "SELECT DISTINCT department FROM contacts
             WHERE is_active=1 AND department IS NOT NULL AND department!=''
             ORDER BY department"
        );
    }

    public static function types(): array
    {
        return ['נותן שירות', 'פנים ארגוני', 'ספק', 'תמיכה טכנית', 'איש קשר', 'אחר'];
    }

    /** אנשי קשר מסומנים לתכתובות אוטומטיות */
    public static function contactsList(): array
    {
        return DB::query(
            "SELECT id, first_name, last_name, email, role, department, 'contact' AS source
             FROM contacts
             WHERE is_active=1 AND is_contacts_list=1
               AND email IS NOT NULL AND email != ''
             UNION ALL
             SELECT id, first_name, last_name, email, '' AS role, '' AS department, 'user' AS source
             FROM users
             WHERE is_active=1
               AND email IS NOT NULL AND email != ''
             ORDER BY first_name ASC, last_name ASC"
        );
    }

    public static function create(array $d): int
    {
        return DB::insert(
            "INSERT INTO contacts
             (first_name,last_name,email,phone,phone2,website,role,department,
              contact_type,address,tags,note,is_active,is_contacts_list)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                trim($d['first_name']),
                trim($d['last_name']    ?? ''),
                trim($d['email']        ?? '') ?: null,
                trim($d['phone']        ?? '') ?: null,
                trim($d['phone2']       ?? '') ?: null,
                trim($d['website']      ?? '') ?: null,
                trim($d['role']         ?? '') ?: null,
                trim($d['department']   ?? '') ?: null,
                trim($d['contact_type'] ?? 'איש קשר'),
                trim($d['address']      ?? '') ?: null,
                trim($d['tags']         ?? '') ?: null,
                trim($d['note']         ?? '') ?: null,
                ($d['is_active']        ?? 1) ? 1 : 0,
                ($d['is_contacts_list'] ?? 0) ? 1 : 0,
            ]
        );
    }

    public static function update(int $id, array $d): void
    {
        DB::execute(
            "UPDATE contacts SET
             first_name=?,last_name=?,email=?,phone=?,phone2=?,website=?,
             role=?,department=?,contact_type=?,address=?,tags=?,note=?,
             is_active=?,is_contacts_list=?,updated_at=NOW()
             WHERE id=?",
            [
                trim($d['first_name']),
                trim($d['last_name']    ?? ''),
                trim($d['email']        ?? '') ?: null,
                trim($d['phone']        ?? '') ?: null,
                trim($d['phone2']       ?? '') ?: null,
                trim($d['website']      ?? '') ?: null,
                trim($d['role']         ?? '') ?: null,
                trim($d['department']   ?? '') ?: null,
                trim($d['contact_type'] ?? 'איש קשר'),
                trim($d['address']      ?? '') ?: null,
                trim($d['tags']         ?? '') ?: null,
                trim($d['note']         ?? '') ?: null,
                ($d['is_active']        ?? 1) ? 1 : 0,
                ($d['is_contacts_list'] ?? 0) ? 1 : 0,
                $id,
            ]
        );
    }

    public static function toggleActive(int $id): int
    {
        DB::execute("UPDATE contacts SET is_active=1-is_active WHERE id=?", [$id]);
        return (int)DB::value("SELECT is_active FROM contacts WHERE id=?", [$id]);
    }
}
