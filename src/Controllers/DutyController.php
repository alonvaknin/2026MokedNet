<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Models\DutyModel;

class DutyController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('canManageDuty');
        $this->view('pages/duty/index');
    }

    // ── Representatives ─────────────────────────────────────
    public function apiRepsList(): void
    {
        $this->requirePermission('canManageDuty');
        $this->json(DutyModel::allReps());
    }

    public function apiRepsCreate(): void
    {
        $this->requirePermission('canManageDuty');
        $this->verifyCsrf();
        $name   = trim($this->post('name', ''));
        $dept   = $this->post('department', '');
        $userId = (int)$this->post('user_id', 0) ?: null;
        if (!$name || !$dept) { $this->json(['error' => 'שם ומחלקה חובה'], 400); return; }
        $id = DutyModel::createRep(['name' => $name, 'department' => $dept, 'user_id' => $userId]);
        $this->json(['ok' => true, 'id' => $id]);
    }

    public function apiRepsUpdate(string $id): void
    {
        $this->requirePermission('canManageDuty');
        $this->verifyCsrf();
        $name   = trim($this->post('name', ''));
        $dept   = $this->post('department', '');
        $userId = (int)$this->post('user_id', 0) ?: null;
        if (!$name || !$dept) { $this->json(['error' => 'שם ומחלקה חובה'], 400); return; }
        DutyModel::updateRep((int)$id, ['name' => $name, 'department' => $dept, 'user_id' => $userId]);
        $this->json(['ok' => true]);
    }

    public function apiRepsDelete(string $id): void
    {
        $this->requirePermission('canManageDuty');
        $this->verifyCsrf();
        DutyModel::deleteRep((int)$id);
        $this->json(['ok' => true]);
    }

    // ── Schedule ─────────────────────────────────────────────
    public function apiScheduleList(): void
    {
        $this->requireAuth();
        $this->json(DutyModel::scheduleWeeks());
    }

    public function apiScheduleAuto(): void
    {
        $this->requirePermission('canManageDuty');
        $this->verifyCsrf();
        $this->json(DutyModel::autoAssignNextWeek());
    }

    public function apiScheduleManual(): void
    {
        $this->requirePermission('canManageDuty');
        $this->verifyCsrf();
        $weekStart = $this->post('week_start', '');
        $repId     = (int)$this->post('representative_id', 0);
        if (!$weekStart || !$repId) { $this->json(['error' => 'חסרים פרטים'], 400); return; }
        $this->json(DutyModel::manualAssign($weekStart, $repId));
    }

    public function apiScheduleDelete(string $id): void
    {
        $this->requirePermission('canManageDuty');
        $this->verifyCsrf();
        $result = DutyModel::deleteSchedule((int)$id);
        $this->json($result);
    }

    public function apiScheduleSave(string $id): void
    {
        $this->requirePermission('canManageDuty');
        $this->verifyCsrf();
        $repId  = (int)$this->post('representative_id', 0);
        $status = $this->post('status', 'active');
        $notes  = $this->post('notes', '');
        if (!$repId) { $this->json(['error' => 'נציג חובה'], 400); return; }
        DutyModel::updateSchedule((int)$id, $repId, $status, $notes);
        $this->json(['ok' => true]);
    }

    public function apiUsersList(): void
    {
        $this->requirePermission('canManageDuty');
        $this->json(DutyModel::allUsers());
    }

    // ── Daily Guidance ───────────────────────────────────────
    public function apiGuidanceList(): void
    {
        $this->requireAuth();
        $this->json(DutyModel::allGuidance());
    }

    public function apiGuidanceSave(): void
    {
        $this->requirePermission('canManageDuty');
        $this->verifyCsrf();
        $day  = $this->post('day_of_week', '');
        $text = $this->post('guidance', '');
        if (!$day) { $this->json(['error' => 'יום חובה'], 400); return; }
        DutyModel::saveGuidance($day, $text);
        $this->json(['ok' => true]);
    }

    // ── Daily roles (גלאס / שיחות / שניהם) ───────────────────
    public function apiDailyRolesList(): void
    {
        $this->requireAuth();
        $week = $this->get('week', '') ?: date('Y-m-d');
        $weekStart = DutyModel::weekStartOf($week);
        $this->json([
            'week_start' => $weekStart,
            'reps'       => DutyModel::allReps(),
            'roles'      => DutyModel::weekRoles($weekStart),
        ]);
    }

    public function apiDailyRolesSave(): void
    {
        $this->requirePermission('canManageDuty');
        $this->verifyCsrf();
        $repId = (int)$this->post('representative_id', 0);
        $date  = $this->post('duty_date', '');
        $role  = $this->post('role', '');

        if (!$repId || !$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->json(['error' => 'חסרים פרטים'], 400);
            return;
        }
        if ($role === '') {
            DutyModel::clearDailyRole($repId, $date);
            $this->json(['ok' => true, 'cleared' => true]);
            return;
        }
        if (!in_array($role, DutyModel::ROLES, true)) {
            $this->json(['error' => 'תפקיד לא תקין'], 400);
            return;
        }
        DutyModel::saveDailyRole($repId, $date, $role);
        $this->json(['ok' => true]);
    }

    /** התפקיד של המשתמש המחובר להיום — לכרטיס בדשבורד */
    public function apiMyRole(): void
    {
        $this->requireAuth();
        $user = \Core\Auth::user();
        $this->json(DutyModel::myRoleToday((int)($user['id'] ?? 0)) ?? ['role' => null]);
    }

    // ── Current week (for dashboard / digital signage) ───────
    public function apiCurrentWeek(): void
    {
        $this->json(DutyModel::currentWeek());
    }

    // ── Digital Signage screen (standalone, no layout wrapper) ─
    public function signage(): void
    {
        $this->view('pages/duty/signage', [], null);
    }
}
