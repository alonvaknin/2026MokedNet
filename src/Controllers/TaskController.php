<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\ActivityLog;
use Models\TaskModel;
use Models\TaskCommentModel;

class TaskController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $userId     = $_SESSION['user_id'];
        $canViewAll = \Core\Auth::can('tasks.viewAll');

        $scope = $this->get('scope', 'mine');  // 'mine' | 'all'
        $filter = $this->get('filter', '');    // 'overdue' (legacy)

        $scopeAll    = ($scope === 'all') && $canViewAll;
        $overdueOnly = ($filter === 'overdue');

        // The page always shows open tasks, with recently-closed tasks collapsed below.
        $showClosed   = false;
        $tasks        = TaskModel::forQuery($userId, false, $scopeAll, $overdueOnly);
        $recentClosed = TaskModel::recentClosed($userId, $scopeAll);

        // Build status lists for ALL types (needed for new-task modal + existing tasks)
        $allTypes = \Core\DB::query('SELECT id, name FROM task_types ORDER BY name');
        $statusesByType = [];
        foreach ($allTypes as $type) {
            $tid = (int)$type['id'];
            $statusesByType[$tid] = \Core\DB::query(
                'SELECT id, name, color, is_closed FROM task_statuses WHERE task_type_id = ? ORDER BY sort_order',
                [$tid]
            );
        }

        $users = \Core\DB::query(
            "SELECT id, CONCAT(first_name,' ',last_name) AS name, last_login FROM users WHERE is_active = 1 ORDER BY last_login IS NULL, last_login DESC",
            []
        );

        $this->view('pages/tasks/index', compact(
            'tasks', 'recentClosed', 'statusesByType', 'allTypes', 'filter',
            'showClosed', 'scopeAll', 'canViewAll', 'users'
        ));
    }

    public function apiSearch(): void
    {
        $this->requireAuth();
        $q = trim($this->get('q', ''));
        if (mb_strlen($q) < 2) {
            $this->json([]);
            return;
        }

        $userId = $_SESSION['user_id'];
        $canAll = \Core\Auth::can('tasks.viewAll');

        $results = TaskModel::search($q);
        if (!$canAll) {
            $results = array_values(array_filter(
                $results,
                fn($t) => (int)($t['assigned_user_id'] ?? 0) === $userId
                       || (int)($t['open_by'] ?? 0) === $userId
            ));
        }

        $this->json($results);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $assignedUserId = (int)$this->post('for_user', $_SESSION['user_id']);
        $openBy         = $_SESSION['user_id'];

        // Resolve department: assigned user's dept, else opener's dept
        $assignedDept = \Core\DB::value(
            'SELECT department_id FROM users WHERE id = ?',
            [$assignedUserId]
        );
        if (!$assignedDept) {
            $assignedDept = \Core\DB::value(
                'SELECT department_id FROM users WHERE id = ?',
                [$openBy]
            );
        }

        $taskTypeId = (int)$this->post('task_type_id', 0) ?: null;

        // Auto-assign the first non-closed status of the type as the default "open" status
        $defaultStatusId = null;
        if ($taskTypeId) {
            $defaultStatusId = \Core\DB::value(
                'SELECT id FROM task_statuses WHERE task_type_id = ? AND is_closed = 0 ORDER BY sort_order ASC LIMIT 1',
                [$taskTypeId]
            ) ?: null;
        }

        $newId = TaskModel::create([
            'open_by'          => $openBy,
            'assigned_user_id' => $assignedUserId,
            'title'            => trim($this->post('title', '')),
            'description'      => trim($this->post('description', '')),
            'sla_days'         => (int)$this->post('sla_days', 3),
            'assigned_dept_id' => $assignedDept ?: null,
            'task_type_id'     => $taskTypeId,
            'status_id'        => $defaultStatusId,
        ]);

        ActivityLog::create('task', $newId, trim($this->post('title', '')));
        $this->redirect('/tasks');
    }

    public function close(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        TaskModel::close((int)$id, $_SESSION['user_id']);
        ActivityLog::log('task.close', 'task', (int)$id, "משימה #{$id}");
        $this->redirect('/tasks');
    }

    public function updateStatus(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $statusId = (int)$this->post('status_id', 0);
        if ($statusId <= 0) {
            $this->json(['error' => true, 'msg' => 'סטטוס לא תקין'], 422);
            return;
        }

        $ok = TaskModel::updateStatus((int)$id, $statusId, $_SESSION['user_id']);
        if (!$ok) {
            $this->json(['error' => true, 'msg' => 'לא נמצאה משימה או אין הרשאה'], 404);
            return;
        }

        ActivityLog::log('task.status', 'task', (int)$id, "משימה #{$id}", "status_id → {$statusId}");
        $this->json(['error' => false, 'msg' => 'סטטוס עודכן']);
    }

    public function updateTitle(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $title = trim($this->post('title', ''));
        if ($title === '') {
            $this->json(['error' => true, 'msg' => 'כותרת לא יכולה להיות ריקה'], 422);
            return;
        }

        $ok = TaskModel::updateTitle((int)$id, $title, $_SESSION['user_id']);
        if (!$ok) {
            $this->json(['error' => true, 'msg' => 'לא נמצאה משימה או אין הרשאה'], 404);
            return;
        }

        $this->json(['error' => false, 'msg' => 'כותרת עודכנה']);
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $taskId = (int)$id;
        $userId = $_SESSION['user_id'];
        $canAll = \Core\Auth::can('tasks.viewAll');

        $task = \Core\DB::row(
            "SELECT t.id, t.title, t.description, t.sla_days,
                    t.created_at, t.status_changed_at, t.is_active,
                    t.status_id, t.task_type_id,
                    t.assigned_user_id, t.assigned_dept_id, t.open_by,
                    CONCAT(opener.first_name,' ',opener.last_name) AS opened_by_name,
                    CONCAT(assignee.first_name,' ',assignee.last_name) AS assigned_to_name,
                    CONCAT(changer.first_name,' ',changer.last_name) AS changed_by_name,
                    ts.name    AS status_name,
                    ts.color   AS status_color,
                    tt.name    AS type_name,
                    dept.name_heb AS dept_name
             FROM tasks t
             LEFT JOIN users opener    ON opener.id   = t.open_by
             LEFT JOIN users assignee  ON assignee.id = t.assigned_user_id
             LEFT JOIN users changer   ON changer.id  = t.status_changed_by
             LEFT JOIN task_statuses ts ON ts.id      = t.status_id
             LEFT JOIN task_types    tt ON tt.id      = t.task_type_id
             LEFT JOIN departments   dept ON dept.id  = t.assigned_dept_id
             WHERE t.id = ?",
            [$taskId]
        );

        if (!$task) {
            $this->json(['error' => true, 'msg' => 'משימה לא נמצאה'], 404);
            return;
        }

        if (!$canAll && (int)$task['assigned_user_id'] !== $userId && (int)$task['open_by'] !== $userId) {
            $this->json(['error' => true, 'msg' => 'אין הרשאה'], 403);
            return;
        }

        $comments = TaskCommentModel::forTask($taskId);
        $logs     = \Core\ActivityLog::fetch(['entity_type' => 'task', 'entity_id' => $taskId], 100, 0);

        $this->json([
            'error'    => false,
            'task'     => $task,
            'comments' => $comments,
            'logs'     => $logs,
        ]);
    }

    public function getComments(string $id): void
    {
        $this->requireAuth();
        $taskId  = (int)$id;
        $userId  = $_SESSION['user_id'];
        $canAll  = \Core\Auth::can('tasks.viewAll');

        if (!TaskCommentModel::canAccess($taskId, $userId, $canAll)) {
            $this->json(['error' => true, 'msg' => 'אין הרשאה'], 403);
            return;
        }

        $this->json(TaskCommentModel::forTask($taskId));
    }

    public function addComment(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $taskId = (int)$id;
        $userId = $_SESSION['user_id'];
        $canAll = \Core\Auth::can('tasks.viewAll');

        if (!TaskCommentModel::canAccess($taskId, $userId, $canAll)) {
            $this->json(['error' => true, 'msg' => 'אין הרשאה'], 403);
            return;
        }

        $body = trim($this->post('body', ''));
        if ($body === '') {
            $this->json(['error' => true, 'msg' => 'תוכן לא יכול להיות ריק'], 422);
            return;
        }
        if (mb_strlen($body) > 2000) {
            $this->json(['error' => true, 'msg' => 'תוכן ארוך מדי (מקס 2000 תווים)'], 422);
            return;
        }

        $comment = TaskCommentModel::add($taskId, $userId, $body);
        $this->json(['ok' => true, 'comment' => $comment]);
    }
}
