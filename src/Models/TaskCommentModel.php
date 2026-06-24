<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class TaskCommentModel
{
    /**
     * Get all comments for a task, ordered by creation date.
     */
    public static function forTask(int $taskId): array
    {
        return DB::query(
            "SELECT tc.id, tc.body, tc.created_at,
                    CONCAT(u.first_name,' ',u.last_name) AS user_name
             FROM task_comments tc
             JOIN users u ON u.id = tc.user_id
             WHERE tc.task_id = ?
             ORDER BY tc.created_at ASC",
            [$taskId]
        );
    }

    /**
     * Add a new comment to a task and return the saved row.
     */
    public static function add(int $taskId, int $userId, string $body): array
    {
        $id = DB::insert(
            'INSERT INTO task_comments (task_id, user_id, body, created_at)
             VALUES (?, ?, ?, NOW())',
            [$taskId, $userId, $body]
        );

        $row = DB::row(
            "SELECT tc.id, tc.body, tc.created_at,
                    CONCAT(u.first_name,' ',u.last_name) AS user_name
             FROM task_comments tc
             JOIN users u ON u.id = tc.user_id
             WHERE tc.id = ?",
            [$id]
        );

        if ($row) {
            return $row;
        }

        return [
            'id'        => $id,
            'body'      => $body,
            'created_at' => date('Y-m-d H:i:s'),
            'user_name' => '',
        ];
    }

    /**
     * Check if user can access comments for a task.
     * If $canViewAll is true, user can access any task.
     * Otherwise, user must be assigned to or have opened the task.
     */
    public static function canAccess(int $taskId, int $userId, bool $canViewAll): bool
    {
        if ($canViewAll) {
            $value = DB::value(
                'SELECT 1 FROM tasks WHERE id = ?',
                [$taskId]
            );
        } else {
            $value = DB::value(
                'SELECT 1 FROM tasks WHERE id = ? AND (assigned_user_id = ? OR open_by = ?)',
                [$taskId, $userId, $userId]
            );
        }

        return (bool)$value;
    }
}
