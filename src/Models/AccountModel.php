<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class AccountModel
{
    public static function all(): array
    {
        return DB::query(
            'SELECT * FROM mokedAccounts WHERE isactive = 1 ORDER BY appName'
        );
    }

    public static function create(array $data): int
    {
        return DB::insert(
            'INSERT INTO mokedAccounts
                (appName, appUser, appPass, appNote, userID,
                 created_by_id, created_by_name)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['appName'], $data['appUser'], $data['appPass'],
                $data['appNote'], $data['userID'],
                $data['created_by_id'], $data['created_by_name'],
            ]
        );
    }

    public static function update(int $id, array $data): bool
    {
        return DB::execute(
            'UPDATE mokedAccounts
             SET appName = ?, appUser = ?, appPass = ?, appNote = ?,
                 updated_by_id = ?, updated_by_name = ?, updated_at = NOW()
             WHERE id = ? AND isactive = 1',
            [
                $data['appName'], $data['appUser'], $data['appPass'], $data['appNote'],
                $data['updated_by_id'], $data['updated_by_name'],
                $id,
            ]
        ) > 0;
    }

    public static function markUpdated(int $id, int $userId, string $userName): void
    {
        DB::execute(
            'UPDATE mokedAccounts SET updated_by_id = ?, updated_by_name = ?, updated_at = NOW() WHERE id = ?',
            [$userId, $userName, $id]
        );
    }

    public static function delete(int $id): bool
    {
        return DB::execute(
            'UPDATE mokedAccounts SET isactive = 0 WHERE id = ?',
            [$id]
        ) > 0;
    }
}
