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
        $tasks = TaskModel::forUser($_SESSION['user_id'], false);
        $this->view('pages/tasks/index', compact('tasks'));
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

        ActivityLog::create('task', $newId, trim($this->post('title','')));
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
}
