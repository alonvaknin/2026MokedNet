<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Models\NavManagerModel;

class NavManagerController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('canEditDB');
        $items      = NavManagerModel::allItems();
        $permGroups = NavManagerModel::permGroups();
        $this->view('pages/nav-manager/index', compact('items', 'permGroups'));
    }

    public function save(): void
    {
        $this->requirePermission('canEditDB');
        $this->verifyCsrf();

        $id = (int)$this->post('id', 0);

        // מחיקה
        if ($this->post('_delete') === '1' && $id) {
            NavManagerModel::delete($id);
            $this->json(['ok' => true]);
            return;
        }

        $data = [
            'id'            => $id,
            'label_he'      => trim($this->post('label_he', '')),
            'icon'          => trim($this->post('icon', '')),
            'link'          => trim($this->post('link', '')),
            'link_type'     => $this->post('link_type', 'route'),
            'parent_id'     => (int)$this->post('parent_id', 0) ?: null,
            'ordering'      => (int)$this->post('ordering', 0),
            'is_active'     => (bool)$this->post('is_active', 1),
            'open_in_blank' => (bool)$this->post('open_in_blank', 0),
            'perm_groups'   => array_filter(array_map('intval', (array)($_POST['perm_groups'] ?? []))),
        ];

        if (!$data['label_he']) {
            $this->json(['error' => 'שם הפריט חובה'], 400);
        }

        $newId = NavManagerModel::save($data);
        $this->json(['ok' => true, 'id' => $newId]);
    }

    public function toggle(): void
    {
        $this->requirePermission('canEditDB');
        $this->verifyCsrf();
        $id     = (int)$this->post('id');
        $active = NavManagerModel::toggle($id);
        $this->json(['ok' => true, 'is_active' => $active]);
    }

    public function reorder(): void
    {
        $this->requirePermission('canEditDB');
        $this->verifyCsrf();
        // מקבל זוגות id:ordering מה-JS
        $pairs = $_POST['pairs'] ?? [];
        if (!empty($pairs)) {
            NavManagerModel::reorderPairs((array)$pairs);
        } else {
            // fallback: רשימת IDs לפי סדר
            $order = array_map('intval', (array)($_POST['order'] ?? []));
            NavManagerModel::reorder($order);
        }
        $this->json(['ok' => true]);
    }
}
