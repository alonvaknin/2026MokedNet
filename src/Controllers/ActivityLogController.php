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

        $showCron = $this->get('cron', '') === '1';

        $page   = max(1, (int)$this->get('page', 1));
        $limit  = 50;
        $offset = ($page - 1) * $limit;

        if ($showCron) {
            $filters = [
                'cron_name' => $this->get('cron_name', ''),
                'action'    => $this->get('action', ''),
                'status'    => $this->get('status', ''),
                'from'      => $this->get('from', ''),
                'to'        => $this->get('to', ''),
            ];
            $filters = array_filter($filters, fn($v) => $v !== '');

            [$logs, $total] = $this->fetchCronLogs($filters, $limit, $offset);
            $pages = (int)ceil($total / $limit);

            $cronNames = \Core\DB::query('SELECT DISTINCT cron_name FROM cron_log ORDER BY cron_name ASC');
            $cronActions = \Core\DB::query('SELECT DISTINCT action FROM cron_log ORDER BY action ASC');

            $this->view('pages/activity-log/index',
                compact('logs','total','page','pages','limit','filters','showCron','cronNames','cronActions'));
            return;
        }

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

        $logs  = ActivityLog::fetch($filters, $limit, $offset);
        $total = ActivityLog::count($filters);
        $pages = (int)ceil($total / $limit);

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

        $showCron = false;
        $this->view('pages/activity-log/index',
            compact('logs','total','page','pages','limit','filters','users','actions','entities','showCron'));
    }

    private function fetchCronLogs(array $filters, int $limit, int $offset): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['cron_name'])) { $where[] = 'cron_name = ?'; $params[] = $filters['cron_name']; }
        if (!empty($filters['action']))    { $where[] = 'action = ?';    $params[] = $filters['action']; }
        if (!empty($filters['status']))    { $where[] = 'status = ?';    $params[] = $filters['status']; }
        if (!empty($filters['from']))      { $where[] = 'created_at >= ?'; $params[] = $filters['from']; }
        if (!empty($filters['to']))        { $where[] = 'created_at <= ?'; $params[] = $filters['to']; }

        $sql = 'SELECT * FROM cron_log WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $rows  = \Core\DB::query($sql, array_merge($params, [$limit, $offset]));
        $total = (int)\Core\DB::value('SELECT COUNT(*) FROM cron_log WHERE ' . implode(' AND ', $where), $params);

        return [$rows, $total];
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
