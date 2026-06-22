<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\ActivityLog;

class ActivityLogController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('canViewLogs');

        $filters = [
            'action'      => $this->get('action', ''),
            'entity_type' => $this->get('entity', ''),
            'user_id'     => (int)$this->get('user', 0) ?: null,
            'ip'          => $this->get('ip', ''),
            'from'        => $this->get('from', ''),
            'to'          => $this->get('to', ''),
            'q'           => $this->get('q', ''),
        ];
        $filters = array_filter($filters, fn($v) => $v !== '' && $v !== null);

        $page   = max(1, (int)$this->get('page', 1));
        $limit  = 50;
        $offset = ($page - 1) * $limit;

        $logs  = ActivityLog::fetch($filters, $limit, $offset);
        $total = ActivityLog::count($filters);
        $pages = (int)ceil($total / $limit);

        // רשימות לפילטרים
        $users = \Core\DB::query(
            'SELECT DISTINCT user_id, user_name FROM activity_log
             WHERE user_id IS NOT NULL ORDER BY user_name ASC'
        );
        $actions = \Core\DB::query(
            'SELECT DISTINCT action FROM activity_log ORDER BY action ASC'
        );
        $entities = \Core\DB::query(
            'SELECT DISTINCT entity_type FROM activity_log WHERE entity_type IS NOT NULL ORDER BY entity_type ASC'
        );

        $this->view('pages/activity-log/index',
            compact('logs','total','page','pages','limit','filters','users','actions','entities'));
    }

    public function apiList(): void
    {
        $this->requirePermission('canViewLogs');

        $filters = [
            'action'      => $this->get('action', ''),
            'entity_type' => $this->get('entity', ''),
            'user_id'     => (int)$this->get('user', 0) ?: null,
            'ip'          => $this->get('ip', ''),
            'from'        => $this->get('from', ''),
            'to'          => $this->get('to', ''),
            'q'           => $this->get('q', ''),
        ];
        $filters = array_filter($filters, fn($v) => $v !== '' && $v !== null);

        $offset = max(0, (int)$this->get('offset', 0));
        $limit  = min(100, (int)$this->get('limit', 50));

        $this->json([
            'rows'  => ActivityLog::fetch($filters, $limit, $offset),
            'total' => ActivityLog::count($filters),
        ]);
    }
}
