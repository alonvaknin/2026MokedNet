<?php
declare(strict_types=1);

namespace Controllers;

use Core\ActivityLog;
use Core\Controller;
use Core\Auth;
use Core\DB;
use Models\AreaManagerModel;

class AreaManagerController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $managers = AreaManagerModel::all();
        $canEdit  = Auth::can('canEditStore');
        $this->view('pages/area-managers/index', compact('managers', 'canEdit'));
    }

    public function save(): void
    {
        $this->requirePermission('canEditStore');
        $this->verifyCsrf();

        $id    = (int)$this->post('id', 0);
        $name  = $this->post('name', '');
        $phone = $this->post('phone', '');
        $email = $this->post('email', '');
        $source_type = $this->post('source_type', '');
        $source_id   = (int)$this->post('source_id', 0);

        if (!$name) {
            $this->json(['error' => 'name required'], 400);
            return;
        }

        if ($source_id <= 0) {
            $this->json(['error' => 'source_id required'], 400);
            return;
        }

        if (!in_array($source_type, ['contact', 'user'], true)) {
            $this->json(['error' => 'source_type invalid'], 400);
            return;
        }

        $data = [
            'name'        => $name,
            'phone'       => $phone,
            'email'       => $email,
            'source_type' => $source_type,
            'source_id'   => $source_id,
        ];

        if ($id) {
            $before = AreaManagerModel::byId($id) ?? [];
            AreaManagerModel::update($id, $data);
            ActivityLog::update('area_manager', $id, $name, $before, $data);
        } else {
            $id = AreaManagerModel::create($data);
            ActivityLog::create('area_manager', $id, $name, $data);
        }

        $this->json(['ok' => true, 'id' => $id]);
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('canEditStore');
        $this->verifyCsrf();
        $mgr    = AreaManagerModel::byId((int)$id);
        $active = AreaManagerModel::toggleActive((int)$id);
        ActivityLog::toggle('area_manager', (int)$id, $mgr['name'] ?? $id, (bool)$active);
        $this->json(['ok' => true, 'is_active' => $active]);
    }

    public function delete(string $id): void
    {
        $this->requirePermission('canEditStore');
        $this->verifyCsrf();

        $mgrId     = (int)$id;
        $mgr       = AreaManagerModel::byId($mgrId);
        if (!$mgr) {
            $this->json(['error' => 'not found'], 404);
            return;
        }

        $transferTo = (int)$this->post('transfer_to', 0);

        if ($transferTo > 0 && $transferTo !== $mgrId) {
            $target = AreaManagerModel::byId($transferTo);
            AreaManagerModel::reassignStores($mgrId, $transferTo);
            ActivityLog::log('area_manager.delete', 'area_manager', $mgrId, $mgr['name'],
                'מחיקה עם העברת חנויות אל: ' . ($target['name'] ?? $transferTo));
        } else {
            ActivityLog::log('area_manager.delete', 'area_manager', $mgrId, $mgr['name'],
                'מחיקה עם הסרת שיוך חנויות');
        }

        AreaManagerModel::delete($mgrId);
        $this->json(['ok' => true]);
    }

    public function apiList(): void
    {
        $this->requireAuth();
        $this->json(AreaManagerModel::all());
    }

    public function apiStores(string $id): void
    {
        $this->requireAuth();
        $this->json(AreaManagerModel::allStoresWithManagerFlags((int)$id));
    }

    public function apiAssign(string $id): void
    {
        $this->requirePermission('canEditStore');
        $this->verifyCsrf();

        $store_id = (int)$this->post('store_id', 0);

        if ($store_id <= 0) {
            $this->json(['error' => 'store_id required'], 400);
            return;
        }

        AreaManagerModel::assignStore((int)$id, $store_id);
        $mgr   = AreaManagerModel::byId((int)$id);
        $store = DB::row('SELECT name, store_num FROM stores WHERE id=? LIMIT 1', [$store_id]);
        $storeLabel = $store ? ($store['store_num'] . ' ' . $store['name']) : (string)$store_id;
        ActivityLog::log('area_manager.assign', 'area_manager', (int)$id,
            $mgr['name'] ?? (string)$id, 'שיוך חנות: ' . $storeLabel);
        $this->json(['ok' => true]);
    }

    public function apiUnassign(string $id): void
    {
        $this->requirePermission('canEditStore');
        $this->verifyCsrf();

        $store_id = (int)$this->post('store_id', 0);

        if ($store_id <= 0) {
            $this->json(['error' => 'store_id required'], 400);
            return;
        }

        AreaManagerModel::unassignStore((int)$id, $store_id);
        $mgr   = AreaManagerModel::byId((int)$id);
        $store = DB::row('SELECT name, store_num FROM stores WHERE id=? LIMIT 1', [$store_id]);
        $storeLabel = $store ? ($store['store_num'] . ' ' . $store['name']) : (string)$store_id;
        ActivityLog::log('area_manager.unassign', 'area_manager', (int)$id,
            $mgr['name'] ?? (string)$id, 'הסרת חנות: ' . $storeLabel);
        $this->json(['ok' => true]);
    }

    public function apiForStore(string $storeId): void
    {
        $this->requireAuth();
        $this->json(AreaManagerModel::managersForStore((int)$storeId));
    }
}
