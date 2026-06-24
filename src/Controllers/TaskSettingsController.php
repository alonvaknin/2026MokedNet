<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\DB;

class TaskSettingsController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('task_settings.manage');

        $types = DB::query('SELECT * FROM task_types ORDER BY id');

        $statusesByType = [];
        foreach ($types as $t) {
            $statusesByType[$t['id']] = DB::query(
                'SELECT * FROM task_statuses WHERE task_type_id=? ORDER BY sort_order',
                [$t['id']]
            );
        }

        $users = DB::query(
            'SELECT id, CONCAT(first_name," ",last_name) AS name
             FROM users WHERE is_active=1 ORDER BY first_name, last_name'
        );

        $this->view('pages/admin/task-settings', compact('types', 'statusesByType', 'users'));
    }

    public function createType(): void
    {
        $this->requirePermission('task_settings.manage');
        $this->verifyCsrf();

        $name    = trim($this->post('name', ''));
        $slaDays = max(1, (int)$this->post('sla_days', 3));
        $assigneeIds = $this->parseIds($this->post('assignee_ids', '[]'));

        if ($name === '') {
            $this->json(['error' => true, 'msg' => 'שם לא יכול להיות ריק'], 422);
        }

        $id = DB::insert(
            'INSERT INTO task_types (name, sla_days, default_assignee_ids, default_watcher_ids)
             VALUES (?, ?, ?, ?)',
            [$name, $slaDays, json_encode($assigneeIds, JSON_UNESCAPED_UNICODE), json_encode([0], JSON_UNESCAPED_UNICODE)]
        );

        $this->json(['error' => false, 'id' => $id, 'msg' => 'סוג נוצר']);
    }

    public function updateType(string $id): void
    {
        $this->requirePermission('task_settings.manage');
        $this->verifyCsrf();

        $fields = [];
        $params = [];

        $name = $this->post('name');
        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                $this->json(['error' => true, 'msg' => 'שם לא יכול להיות ריק'], 422);
            }
            $fields[] = 'name = ?';
            $params[] = $name;
        }

        $slaDays = $this->post('sla_days');
        if ($slaDays !== null) {
            $fields[] = 'sla_days = ?';
            $params[] = max(1, (int)$slaDays);
        }

        $assigneeIds = $this->post('assignee_ids');
        if ($assigneeIds !== null) {
            $fields[] = 'default_assignee_ids = ?';
            $params[] = json_encode($this->parseIds($assigneeIds), JSON_UNESCAPED_UNICODE);
        }

        if (empty($fields)) {
            $this->json(['error' => true, 'msg' => 'אין שדות לעדכון'], 422);
        }

        $params[] = (int)$id;
        DB::execute('UPDATE task_types SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
        $this->json(['error' => false, 'msg' => 'עודכן']);
    }

    public function deleteType(string $id): void
    {
        $this->requirePermission('task_settings.manage');
        $this->verifyCsrf();

        $openCount = (int)DB::value(
            'SELECT COUNT(*) FROM tasks WHERE task_type_id=? AND is_active=1',
            [(int)$id]
        );

        if ($openCount > 0) {
            $this->json(['error' => true, 'msg' => "לא ניתן למחוק — קיימות {$openCount} משימות פתוחות לסוג זה"], 409);
        }

        DB::execute('DELETE FROM task_types WHERE id=?', [(int)$id]);
        $this->json(['error' => false, 'msg' => 'נמחק']);
    }

    public function createStatus(): void
    {
        $this->requirePermission('task_settings.manage');
        $this->verifyCsrf();

        $typeId    = (int)$this->post('task_type_id', 0);
        $name      = trim($this->post('name', ''));
        $color     = $this->sanitizeColor($this->post('color', '#4f7fff'));
        $sortOrder = (int)$this->post('sort_order', 0);

        if ($typeId <= 0 || $name === '') {
            $this->json(['error' => true, 'msg' => 'נתונים חסרים'], 422);
        }

        $id = DB::insert(
            'INSERT INTO task_statuses (task_type_id, name, color, sort_order) VALUES (?,?,?,?)',
            [$typeId, $name, $color, $sortOrder]
        );

        $this->json(['error' => false, 'id' => $id, 'msg' => 'סטטוס נוצר']);
    }

    public function updateStatus(string $id): void
    {
        $this->requirePermission('task_settings.manage');
        $this->verifyCsrf();

        $fields = [];
        $params = [];

        $name = $this->post('name');
        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                $this->json(['error' => true, 'msg' => 'שם לא יכול להיות ריק'], 422);
            }
            $fields[] = 'name = ?';
            $params[] = $name;
        }

        $color = $this->post('color');
        if ($color !== null) {
            $fields[] = 'color = ?';
            $params[] = $this->sanitizeColor($color);
        }

        $sortOrder = $this->post('sort_order');
        if ($sortOrder !== null) {
            $fields[] = 'sort_order = ?';
            $params[] = (int)$sortOrder;
        }

        if (empty($fields)) {
            $this->json(['error' => true, 'msg' => 'אין שדות לעדכון'], 422);
        }

        $params[] = (int)$id;
        DB::execute('UPDATE task_statuses SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
        $this->json(['error' => false, 'msg' => 'עודכן']);
    }

    public function deleteStatus(string $id): void
    {
        $this->requirePermission('task_settings.manage');
        $this->verifyCsrf();

        DB::execute('DELETE FROM task_statuses WHERE id=?', [(int)$id]);
        $this->json(['error' => false, 'msg' => 'נמחק']);
    }

    private function parseIds(string $json): array
    {
        $arr = json_decode($json, true);
        if (!is_array($arr)) return [];
        return array_values(array_filter(array_map('intval', $arr), fn($v) => $v > 0));
    }

    private function sanitizeColor(string $color): string
    {
        $color = trim($color);
        return preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? $color : '#4f7fff';
    }
}
