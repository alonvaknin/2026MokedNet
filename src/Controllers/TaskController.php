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

        $show  = $this->get('show', 'open');   // 'open' | 'closed'
        $scope = $this->get('scope', 'mine');  // 'mine' | 'all'
        $filter = $this->get('filter', '');    // 'overdue' (legacy)

        $showClosed  = ($show === 'closed');
        $scopeAll    = ($scope === 'all') && $canViewAll;
        $overdueOnly = ($filter === 'overdue');

        $tasks = TaskModel::forQuery($userId, $showClosed, $scopeAll, $overdueOnly);

        // Build status lists indexed by task_type_id for the JS dropdown
        $statusesByType = [];
        foreach ($tasks as $t) {
            $tid = (int)($t['task_type_id'] ?? 0);
            if ($tid && !isset($statusesByType[$tid])) {
                $rows = \Core\DB::query(
                    'SELECT id, name, color, is_closed FROM task_statuses WHERE task_type_id = ? ORDER BY sort_order',
                    [$tid]
                );
                $statusesByType[$tid] = $rows;
            }
        }

        $users = \Core\DB::query(
            'SELECT id, name, last_login FROM users WHERE is_active = 1 ORDER BY last_login IS NULL, last_login DESC',
            []
        );

        $this->view('pages/tasks/index', compact(
            'tasks', 'statusesByType', 'filter',
            'showClosed', 'scopeAll', 'canViewAll', 'users'
        ));
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

        $newId = TaskModel::create([
            'open_by'          => $openBy,
            'assigned_user_id' => $assignedUserId,
            'title'            => trim($this->post('title', '')),
            'description'      => trim($this->post('description', '')),
            'sla_days'         => (int)$this->post('sla_days', 3),
            'assigned_dept_id' => $assignedDept ?: null,
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
