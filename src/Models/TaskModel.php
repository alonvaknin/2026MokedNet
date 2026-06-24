<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class TaskModel
{
    public static function forUser(int $userId, bool $closed = false, bool $overdueOnly = false): array
    {
        $where = 'WHERE t.assigned_user_id = ? AND t.is_active = ?';
        $params = [$userId, $closed ? 0 : 1];

        if ($overdueOnly) {
            $where .= ' AND DATE_ADD(t.created_at, INTERVAL t.sla_days DAY) < NOW()';
        }

        return DB::query(
            "SELECT t.id, t.title, t.description, t.sla_days,
                    t.created_at, t.status_changed_at, t.is_active,
                    t.source_type, t.source_id,
                    CONCAT(u.first_name,\" \",u.last_name) AS opened_by_name,
                    ts.name  AS status_name,
                    ts.color AS status_color,
                    tt.name  AS type_name,
                    tt.id    AS task_type_id,
                    t.status_id
             FROM tasks t
             LEFT JOIN users u         ON u.id  = t.open_by
             LEFT JOIN task_statuses ts ON ts.id = t.status_id
             LEFT JOIN task_types    tt ON tt.id = t.task_type_id
             {$where}
             ORDER BY t.created_at DESC",
            $params
        );
    }

    public static function forQuery(
        int  $userId,
        bool $closed      = false,
        bool $allUsers    = false,
        bool $overdueOnly = false
    ): array {
        $conditions = ['t.is_active = ?'];
        $params     = [$closed ? 0 : 1];

        if (!$allUsers) {
            $conditions[] = 't.assigned_user_id = ?';
            $params[]     = $userId;
        }

        if ($overdueOnly) {
            $conditions[] = 'DATE_ADD(t.created_at, INTERVAL t.sla_days DAY) < NOW()';
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        return DB::query(
            "SELECT t.id, t.title, t.description, t.sla_days,
                    t.created_at, t.status_changed_at, t.is_active,
                    t.source_type, t.source_id,
                    t.status_id, t.task_type_id,
                    t.assigned_user_id, t.assigned_dept_id,
                    t.status_changed_by,
                    CONCAT(opener.first_name,' ',opener.last_name) AS opened_by_name,
                    CONCAT(changer.first_name,' ',changer.last_name) AS changed_by_name,
                    ts.name    AS status_name,
                    ts.color   AS status_color,
                    ts.is_closed AS status_is_closed,
                    tt.name    AS type_name,
                    dept.name_heb AS dept_name
             FROM tasks t
             LEFT JOIN users opener    ON opener.id  = t.open_by
             LEFT JOIN users changer   ON changer.id = t.status_changed_by
             LEFT JOIN task_statuses ts ON ts.id     = t.status_id
             LEFT JOIN task_types    tt ON tt.id     = t.task_type_id
             LEFT JOIN departments   dept ON dept.id = t.assigned_dept_id
             {$where}
             ORDER BY t.created_at DESC",
            $params
        );
    }

    public static function create(array $data): int
    {
        return DB::insert(
            'INSERT INTO tasks
                (open_by, assigned_user_id, title, description,
                 sla_days, status_id, assigned_dept_id,
                 task_type_id, source_type, source_id,
                 created_at, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)',
            [
                $data['open_by'],
                $data['assigned_user_id'],
                $data['title'],
                $data['description']      ?? '',
                $data['sla_days']         ?? 3,
                $data['status_id']        ?? null,
                $data['assigned_dept_id'] ?? null,
                $data['task_type_id']     ?? null,
                $data['source_type']      ?? null,
                $data['source_id']        ?? null,
            ]
        );
    }

    /**
     * Create a task automatically from a source entity (e.g. invoice_change_name).
     * Loads assignees and watchers from task_types defaults.
     * Watcher id 0 means "self" (the user who opened the request) and is replaced
     * with $openBy before inserting.
     */
    public static function createFromSource(
        int    $taskTypeId,
        string $sourceType,
        int    $sourceId,
        int    $openBy,
        string $title
    ): int {
        $type = DB::row(
            'SELECT tt.sla_days, tt.default_assignee_ids, tt.default_watcher_ids,
                    ts.id AS first_status_id
             FROM task_types tt
             LEFT JOIN task_statuses ts ON ts.task_type_id = tt.id
             WHERE tt.id = ?
             ORDER BY ts.sort_order ASC
             LIMIT 1',
            [$taskTypeId]
        );

        if (!$type) {
            return 0;
        }

        $assigneeIds = json_decode($type['default_assignee_ids'] ?? '[]', true) ?: [];
        $watcherIds  = json_decode($type['default_watcher_ids']  ?? '[]', true) ?: [];

        // Replace sentinel 0 with the actual opener
        $watcherIds = array_map(fn($id) => ($id === 0 ? $openBy : $id), $watcherIds);

        $assignedTo = $assigneeIds[0] ?? $openBy;

        $taskId = self::create([
            'open_by'          => $openBy,
            'assigned_user_id' => $assignedTo,
            'title'            => $title,
            'description'      => '',
            'sla_days'         => $type['sla_days'],
            'status_id'        => $type['first_status_id'] ?: null,
            'task_type_id'     => $taskTypeId,
            'source_type'      => $sourceType,
            'source_id'        => $sourceId,
        ]);

        foreach ($watcherIds as $uid) {
            if ($uid > 0) {
                self::addWatcher($taskId, $uid);
            }
        }

        return $taskId;
    }

    public static function addWatcher(int $taskId, int $userId): void
    {
        DB::execute(
            'INSERT IGNORE INTO task_watchers (task_id, user_id) VALUES (?, ?)',
            [$taskId, $userId]
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

    public static function updateStatus(int $id, int $statusId, int $byUserId): bool
    {
        // Check if the target status is a closing status
        $isClosed = (int)DB::value(
            'SELECT is_closed FROM task_statuses WHERE id = ?',
            [$statusId]
        );

        return DB::execute(
            'UPDATE tasks
             SET status_id          = ?,
                 status_changed_by  = ?,
                 status_changed_at  = NOW(),
                 is_active          = ?
             WHERE id = ? AND (assigned_user_id = ? OR open_by = ?)',
            [$statusId, $byUserId, $isClosed ? 0 : 1, $id, $byUserId, $byUserId]
        ) > 0;
    }

    public static function updateTitle(int $id, string $title, int $byUserId): bool
    {
        $title = trim($title);
        if ($title === '') {
            return false;
        }
        return DB::execute(
            'UPDATE tasks SET title = ?
             WHERE id = ? AND (assigned_user_id = ? OR open_by = ?)',
            [$title, $id, $byUserId, $byUserId]
        ) > 0;
    }

    public static function typeByName(string $name): ?array
    {
        return DB::row(
            'SELECT * FROM task_types WHERE name = ? LIMIT 1',
            [$name]
        );
    }
}
