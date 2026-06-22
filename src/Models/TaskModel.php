<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class TaskModel
{
    public static function forUser(int $userId, bool $closed = false): array
    {
        return DB::query(
            'SELECT t.id, t.title, t.description, t.sla_days,
                    t.created_at, t.status_changed_at, t.is_active,
                    CONCAT(u.first_name," ",u.last_name) AS opened_by_name
             FROM tasks t
             LEFT JOIN users u ON u.id = t.open_by
             WHERE t.assigned_user_id = ? AND t.is_active = ?
             ORDER BY t.created_at DESC',
            [$userId, $closed ? 0 : 1]
        );
    }

    public static function create(array $data): int
    {
        return DB::insert(
            'INSERT INTO tasks
                (open_by, assigned_user_id, title, description,
                 sla_days, status_id, assigned_dept_id, created_at, is_active)
             VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), 1)',
            [
                $data['open_by'],
                $data['assigned_user_id'],
                $data['title'],
                $data['description']      ?? '',
                $data['sla_days']         ?? 3,
                $data['assigned_dept_id'] ?? null,
            ]
        );
    }

    public static function close(int $id, int $byUserId): bool
    {
        return DB::execute(
            'UPDATE tasks SET is_active = 0, status_changed_at = NOW()
             WHERE id = ? AND (assigned_user_id = ? OR open_by = ?)',
            [$id, $byUserId, $byUserId]
        ) > 0;
    }
}
