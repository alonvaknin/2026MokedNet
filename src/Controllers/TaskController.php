<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\ActivityLog;
use Models\TaskModel;

class TaskController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $userId = $_SESSION['user_id'];
        $tasks  = TaskModel::forUser($userId, false);

        // Build status lists indexed by task_type_id for the JS dropdown
        $statusesByType = [];
        foreach ($tasks as $t) {
            $tid = (int)($t['task_type_id'] ?? 0);
            if ($tid && !isset($statusesByType[$tid])) {
                $rows = \Core\DB::query(
                    'SELECT id, name, color FROM task_statuses WHERE task_type_id = ? ORDER BY sort_order',
                    [$tid]
                );
                $statusesByType[$tid] = $rows;
            }
        }

        $this->view('pages/tasks/index', compact('tasks', 'statusesByType'));
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $newId = TaskModel::create([
            'open_by'          => $_SESSION['user_id'],
            'assigned_user_id' => (int)$this->post('for_user', $_SESSION['user_id']),
            'title'            => trim($this->post('title', '')),
            'description'      => trim($this->post('description', '')),
            'sla_days'         => (int)$this->post('sla_days', 3),
            'assigned_dept_id' => (int)$this->post('depart_id', 0) ?: null,
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
}
