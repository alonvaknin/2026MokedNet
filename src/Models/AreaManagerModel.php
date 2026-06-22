<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class AreaManagerModel
{
    public static function all(): array
    {
        return DB::query(
            "SELECT am.id, am.name, am.phone, am.email, am.source_type, am.source_id,
                    am.is_active, COUNT(ams.store_id) AS store_count
             FROM area_managers am
             LEFT JOIN area_manager_stores ams ON am.id = ams.area_manager_id
             GROUP BY am.id
             ORDER BY am.name ASC"
        );
    }

    public static function byId(int $id): ?array
    {
        return DB::row(
            'SELECT * FROM area_managers WHERE id=? LIMIT 1',
            [$id]
        );
    }

    public static function create(array $d): int
    {
        return DB::insert(
            'INSERT INTO area_managers
                (name, phone, email, source_type, source_id, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())',
            [
                trim($d['name']),
                trim($d['phone'] ?? '') !== '' ? trim($d['phone']) : null,
                trim($d['email'] ?? '') !== '' ? trim($d['email']) : null,
                $d['source_type'],
                $d['source_id'],
            ]
        );
    }

    public static function update(int $id, array $d): void
    {
        DB::execute(
            'UPDATE area_managers
             SET name=?, phone=?, email=?, source_type=?, source_id=?, updated_at=NOW()
             WHERE id=?',
            [
                trim($d['name']),
                trim($d['phone'] ?? '') !== '' ? trim($d['phone']) : null,
                trim($d['email'] ?? '') !== '' ? trim($d['email']) : null,
                $d['source_type'],
                $d['source_id'],
                $id,
            ]
        );
    }

    public static function toggleActive(int $id): int
    {
        DB::execute(
            'UPDATE area_managers SET is_active=1-is_active, updated_at=NOW() WHERE id=?',
            [$id]
        );
        return (int) DB::value('SELECT is_active FROM area_managers WHERE id=?', [$id]);
    }

    public static function storesForManager(int $managerId): array
    {
        return DB::query(
            "SELECT s.id, s.store_num, s.name, s.city
             FROM stores s
             INNER JOIN area_manager_stores ams ON s.id = ams.store_id
             WHERE ams.area_manager_id = ?
             ORDER BY CAST(s.store_num AS UNSIGNED) ASC, s.name ASC",
            [$managerId]
        );
    }

    public static function managersForStore(int $storeId): array
    {
        return DB::query(
            "SELECT am.id, am.name, am.phone, am.email, am.source_type
             FROM area_managers am
             INNER JOIN area_manager_stores ams ON am.id = ams.area_manager_id
             WHERE ams.store_id = ? AND am.is_active = 1
             ORDER BY am.name ASC",
            [$storeId]
        );
    }

    public static function delete(int $id): void
    {
        DB::execute('DELETE FROM area_manager_stores WHERE area_manager_id = ?', [$id]);
        DB::execute('DELETE FROM area_managers WHERE id = ?', [$id]);
    }

    public static function reassignStores(int $fromId, int $toId): void
    {
        // INSERT IGNORE to skip stores already assigned to $toId
        DB::execute(
            'INSERT IGNORE INTO area_manager_stores (area_manager_id, store_id, created_at)
             SELECT ?, store_id, NOW() FROM area_manager_stores WHERE area_manager_id = ?',
            [$toId, $fromId]
        );
    }

    public static function assignStore(int $managerId, int $storeId): void
    {
        DB::execute(
            'INSERT IGNORE INTO area_manager_stores
                (area_manager_id, store_id, created_at)
             VALUES (?, ?, NOW())',
            [$managerId, $storeId]
        );
    }

    public static function unassignStore(int $managerId, int $storeId): void
    {
        DB::execute(
            'DELETE FROM area_manager_stores
             WHERE area_manager_id = ? AND store_id = ?',
            [$managerId, $storeId]
        );
    }

    public static function allStoresWithManagerFlags(int $managerId): array
    {
        $stores = DB::query(
            "SELECT s.id, s.store_num, s.name, s.city,
                    (CASE WHEN ams.area_manager_id IS NOT NULL THEN 1 ELSE 0 END) AS is_assigned
             FROM stores s
             LEFT JOIN area_manager_stores ams ON s.id = ams.store_id AND ams.area_manager_id = ?
             WHERE s.is_active = 1 AND s.type = 'סניף באג'
             ORDER BY CAST(s.store_num AS UNSIGNED) ASC, s.name ASC",
            [$managerId]
        );

        if (!$stores) return [];

        $storeIdValues = array_column($stores, 'id');
        $placeholders  = implode(',', array_fill(0, count($storeIdValues), '?'));
        $otherManagers = DB::query(
            "SELECT ams.store_id, am.id, am.name
             FROM area_managers am
             INNER JOIN area_manager_stores ams ON am.id = ams.area_manager_id
             WHERE am.is_active = 1 AND am.id != ?
               AND ams.store_id IN ($placeholders)",
            [$managerId, ...$storeIdValues]
        );

        $otherManagersByStore = [];
        foreach ($otherManagers as $om) {
            $storeId = $om['store_id'];
            if (!isset($otherManagersByStore[$storeId])) {
                $otherManagersByStore[$storeId] = [];
            }
            $otherManagersByStore[$storeId][] = ['id' => $om['id'], 'name' => $om['name']];
        }

        foreach ($stores as &$store) {
            $store['is_assigned'] = (bool) $store['is_assigned'];
            $store['other_managers'] = $otherManagersByStore[$store['id']] ?? [];
        }

        return $stores;
    }

    public static function managersForStoresBulk(array $storeIds): array
    {
        if (empty($storeIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($storeIds), '?'));
        $results = DB::query(
            "SELECT am.id, am.name, am.phone, am.email, am.source_type, ams.store_id
             FROM area_managers am
             INNER JOIN area_manager_stores ams ON am.id = ams.area_manager_id
             WHERE ams.store_id IN ($placeholders) AND am.is_active = 1
             ORDER BY am.name ASC",
            $storeIds
        );

        $grouped = [];
        foreach ($storeIds as $storeId) {
            $grouped[$storeId] = [];
        }

        foreach ($results as $row) {
            $storeId = $row['store_id'];
            unset($row['store_id']);
            $grouped[$storeId][] = $row;
        }

        return $grouped;
    }
}
