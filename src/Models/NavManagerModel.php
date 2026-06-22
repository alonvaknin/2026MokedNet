<?php
declare(strict_types=1);

namespace Models;

use Core\DB;
use PDO;

class NavManagerModel
{
    // ── Schema map — שמות עמודות אמיתיים ─────────────────────
    // nav_items: id, name_heb, name_eng, icon, link, link_type(url|jsfunction),
    //            open_blank, permission, parent_id, ordering, is_active

    private static function db2(): \PDO
    {
        static $pdo = null;
        if ($pdo === null) {
            $c = CFG['db'];
            $pdo = new \PDO(
                "mysql:host={$c['host']};port={$c['port']};dbname=alon_db2;charset=utf8mb4",
                $c['user'], $c['pass'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                 \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]
            );
        }
        return $pdo;
    }

    // ── קריאה ────────────────────────────────────────────────

    public static function allItems(): array
    {
        $pdo = self::db2();

        $items = $pdo->query(
            'SELECT * FROM nav_items ORDER BY ISNULL(parent_id) DESC, ordering ASC, id ASC'
        )->fetchAll();

        $permMap = [];
        try {
            $perms = $pdo->query(
                'SELECT nav_item_id, perm_group_id FROM nav_permissions'
            )->fetchAll();
            foreach ($perms as $p) {
                $permMap[$p['nav_item_id']][] = (int)$p['perm_group_id'];
            }
        } catch (\Throwable) {}

        foreach ($items as &$item) {
            $item['label_he']      = $item['name_heb']   ?? '';
            $item['label_en']      = $item['name_eng']   ?? '';
            $item['open_in_blank'] = $item['open_blank'] ?? 0;
            $item['link_type']     = $item['link_type']  ?? 'url';
            $item['perm_groups']   = $permMap[$item['id']] ?? [];
        }
        return $items;
    }

    public static function permGroups(): array
    {
        return DB::query('SELECT id, name_heb AS permmisionsGroupHeb FROM permission_groups ORDER BY id');
    }

    // ── כתיבה ────────────────────────────────────────────────

    public static function save(array $data): int
    {
        $pdo = self::db2();

        // link_type: הטבלה מכירה רק 'url' | 'jsfunction'
        $linkType = in_array($data['link_type'] ?? '', ['url','jsfunction']) ? $data['link_type'] : 'url';

        if (!empty($data['id'])) {
            $stmt = $pdo->prepare(
                'UPDATE nav_items SET
                    name_heb=?, icon=?, link=?, link_type=?,
                    parent_id=?, ordering=?, is_active=?, open_blank=?
                 WHERE id=?'
            );
            $stmt->execute([
                $data['label_he'] ?? $data['name_heb'],
                $data['icon'] ?: null,
                $data['link'] ?: null,
                $linkType,
                $data['parent_id'] ?: null,
                (int)($data['ordering'] ?? 0),
                ($data['is_active'] ?? 1) ? 1 : 0,
                ($data['open_in_blank'] ?? $data['open_blank'] ?? 0) ? 1 : 0,
                (int)$data['id'],
            ]);
            $id = (int)$data['id'];
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO nav_items (name_heb, name_eng, icon, link, link_type, parent_id, ordering, is_active, open_blank)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $data['label_he'] ?? $data['name_heb'],
                $data['label_en'] ?? $data['name_eng'] ?? '',
                $data['icon'] ?: null,
                $data['link'] ?: null,
                $linkType,
                $data['parent_id'] ?: null,
                (int)($data['ordering'] ?? 0),
                ($data['is_active'] ?? 1) ? 1 : 0,
                ($data['open_in_blank'] ?? $data['open_blank'] ?? 0) ? 1 : 0,
            ]);
            $id = (int)$pdo->lastInsertId();
        }

        self::setPermissions($id, $data['perm_groups'] ?? []);
        return $id;
    }

    public static function toggle(int $id): bool
    {
        $pdo = self::db2();
        $pdo->prepare('UPDATE nav_items SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        return (bool)$pdo->query("SELECT is_active FROM nav_items WHERE id=$id")->fetchColumn();
    }

    public static function reorder(array $order): void
    {
        $pdo  = self::db2();
        $stmt = $pdo->prepare('UPDATE nav_items SET ordering=? WHERE id=?');
        foreach ($order as $pos => $id) {
            $stmt->execute([$pos + 1, (int)$id]);
        }
    }

    public static function reorderPairs(array $pairs): void
    {
        // $pairs = ['id:ordering', 'id:ordering', ...]
        $pdo  = self::db2();
        $stmt = $pdo->prepare('UPDATE nav_items SET ordering=? WHERE id=?');
        foreach ($pairs as $pair) {
            [$id, $ordering] = explode(':', (string)$pair, 2);
            $stmt->execute([(int)$ordering, (int)$id]);
        }
    }

    public static function delete(int $id): void
    {
        $pdo = self::db2();
        $pdo->prepare('DELETE FROM nav_permissions WHERE nav_item_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM nav_items WHERE id=?')->execute([$id]);
    }

    private static function setPermissions(int $itemId, array $groupIds): void
    {
        $pdo = self::db2();
        $pdo->prepare('DELETE FROM nav_permissions WHERE nav_item_id=?')->execute([$itemId]);
        if (empty($groupIds)) return;
        $stmt = $pdo->prepare('INSERT INTO nav_permissions (nav_item_id, perm_group_id) VALUES (?,?)');
        foreach ($groupIds as $gid) {
            if ((int)$gid > 0) $stmt->execute([$itemId, (int)$gid]);
        }
    }

    public static function getNavForGroup(int $groupId): array
    {
        $pdo = self::db2();

        // שלוף הכל בשלושה queries פשוטות במקום correlated subqueries
        $items = $pdo->query(
            'SELECT *, name_heb AS label_he, name_eng AS label_en, open_blank AS open_in_blank
             FROM nav_items
             WHERE is_active = 1
             ORDER BY ISNULL(parent_id) DESC, ordering ASC'
        )->fetchAll();

        if (empty($items)) return [];

        // שלוף את כל ההרשאות בquery אחד
        $permRows = $pdo->query('SELECT nav_item_id FROM nav_permissions')->fetchAll(\PDO::FETCH_COLUMN);
        $itemsWithPerms = array_flip(array_unique(array_map('intval', $permRows)));

        $stmt = $pdo->prepare('SELECT nav_item_id FROM nav_permissions WHERE perm_group_id = ?');
        $stmt->execute([$groupId]);
        $allowedIds = array_flip(array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN)));

        return array_values(array_filter($items, function($item) use ($itemsWithPerms, $allowedIds) {
            $id = (int)$item['id'];
            // אם אין הרשאות רשום לפריט — גלוי לכולם
            if (!isset($itemsWithPerms[$id])) return true;
            // אם יש הרשאות — בדוק אם הקבוצה מורשית
            return isset($allowedIds[$id]);
        }));
    }
}
