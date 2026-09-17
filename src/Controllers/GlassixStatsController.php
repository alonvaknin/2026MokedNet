<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\ActivityLog;
use Services\GlassixService;

class GlassixStatsController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        ActivityLog::log('glassix_stats.view', 'glassix_stats', null, 'טיקטים פתוחים לפי נציג');
        $this->view('pages/glassix-stats/index', []);
    }

    // ── GET /api/glassix/agent-stats ─────────────────────────────────
    public function apiAgentStats(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $user  = Auth::user();
        $email = $user['email'] ?? '';
        $uid   = (int)($user['id'] ?? 0);

        $force  = ($_GET['refresh'] ?? '') === '1';
        $result = GlassixService::getOpenTicketCountsByAgent($email, $uid, $force);
        echo json_encode($result);
    }
}
