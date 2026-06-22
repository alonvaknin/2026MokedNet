<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\DB;
use Models\StoreModel;
use Models\AreaManagerModel;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $user        = Auth::user();
        $stores      = StoreModel::allBugStores();
        $modanStores = StoreModel::allModanStores();

        // All cities from both sets
        $allStores = array_merge($stores, $modanStores);
        $allStoreIds  = array_column($allStores, 'id');
        $areaManagers = AreaManagerModel::managersForStoresBulk($allStoreIds);
        $cities    = array_values(array_unique(array_filter(array_column($allStores, 'city'))));
        sort($cities);

        $stats = [
            'open_tasks'   => (int) DB::value(
                'SELECT COUNT(*) FROM tasks WHERE assigned_user_id=? AND is_active=1',
                [$_SESSION['user_id']]
            ),
            'stores_total' => count($stores),
            'stores_alert' => count(array_filter($allStores, fn($s) => !empty($s['alert_note']))),
        ];

        $this->view('pages/dashboard', compact('user','stores','modanStores','cities','stats','areaManagers'));
    }
}
