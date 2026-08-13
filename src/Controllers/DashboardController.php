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

        $alertStores = array_values(array_filter($allStores, fn($s) => !empty($s['alert_note'])));

        $stats = [
            'open_tasks'   => (int) DB::value(
                'SELECT COUNT(*) FROM tasks WHERE assigned_user_id=? AND is_active=1',
                [$_SESSION['user_id']]
            ),
            'stores_total' => count($stores),
            'stores_alert' => count($alertStores),
        ];

        $alerts = $this->buildAlertsList($alertStores);

        $this->view('pages/dashboard', compact('user','stores','modanStores','cities','stats','areaManagers','alerts'));
    }

    /** מוסיף לכל חנות עם התראה את שם המשתמש שעדכן אחרון את alert_note (מ-ActivityLog) */
    private function buildAlertsList(array $alertStores): array
    {
        if (empty($alertStores)) return [];

        $ids = array_column($alertStores, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $rows = DB::query(
            "SELECT a1.entity_id, a1.user_name
             FROM activity_log a1
             INNER JOIN (
                 SELECT entity_id, MAX(id) AS max_id
                 FROM activity_log
                 WHERE entity_type='store' AND action='store.update'
                   AND diff_json LIKE '%\"alert_note\"%'
                   AND entity_id IN ($placeholders)
                 GROUP BY entity_id
             ) latest ON latest.entity_id = a1.entity_id AND latest.max_id = a1.id",
            $ids
        );
        $byId = array_column($rows, 'user_name', 'entity_id');

        foreach ($alertStores as &$s) {
            $s['alert_by'] = $byId[$s['id']] ?? 'לא ידוע';
        }
        unset($s);

        return $alertStores;
    }
}
