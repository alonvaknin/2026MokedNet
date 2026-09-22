<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\HoursModel;

class HoursController extends Controller
{
    /** ערכי entry_type המותרים — חייב להתאים ל-ENUM בטבלה */
    private const TYPES = ['regular','vacation','reserve','sick','duplicate_delete','other'];

    private const MONTHS = ['','ינואר','פברואר','מרץ','אפריל','מאי','יוני',
                            'יולי','אוגוסט','ספטמבר','אוקטובר','נובמבר','דצמבר'];

    /** קורא גוף JSON — הקונטרולרים הקיימים קוראים $_POST, כאן ה-fetch שולח JSON */
    private function jsonBody(): array
    {
        static $body = null;
        if ($body !== null) return $body;
        $raw = file_get_contents('php://input') ?: '';
        $d = json_decode($raw, true);
        return $body = is_array($d) ? $d : [];
    }

    private function normalizeMonth(mixed $m): string
    {
        return (is_string($m) && preg_match('/^\d{4}-\d{2}$/', $m)) ? $m : date('Y-m');
    }

    private function monthLabel(string $month): string
    {
        [$y, $m] = explode('-', $month);
        return self::MONTHS[(int)$m] . ' ' . $y;
    }

    public function index(): void
    {
        $this->requirePermission('canReportHours');
        $uid   = (int)Auth::user()['id'];
        $month = $this->normalizeMonth($this->get('month'));
        $ts    = strtotime($month . '-01');

        $this->view('pages/hours/index', [
            'rows'       => HoursModel::forUserMonth($uid, $month),
            'month'      => $month,
            'monthLabel' => $this->monthLabel($month),
            'prevMonth'  => date('Y-m', strtotime('-1 month', $ts)),
            'nextMonth'  => date('Y-m', strtotime('+1 month', $ts)),
        ]);
    }

    public function saveEntry(string $id): void
    {
        $this->requirePermission('canReportHours');
        $this->verifyCsrf();

        $uid = (int)Auth::user()['id'];
        $row = HoursModel::find((int)$id);

        // אכיפת בעלות — נציג נוגע רק בשורות שלו. user_id לעולם אינו מגיע מהבקשה.
        if (!$row || (int)$row['user_id'] !== $uid) {
            $this->json(['error' => 'אין הרשאה לשורה זו'], 403);
            return;
        }

        $b    = $this->jsonBody();
        $type = (string)($b['entry_type'] ?? 'regular');
        if (!in_array($type, self::TYPES, true)) {
            $this->json(['error' => 'סוג דיווח לא תקין'], 400);
            return;
        }
        $in   = $type === 'regular' ? (($b['time_in']  ?? null) ?: null) : null;
        $out  = $type === 'regular' ? (($b['time_out'] ?? null) ?: null) : null;

        if ($in && $out && $out <= $in) {
            $this->json(['error' => 'שעת יציאה מוקדמת משעת כניסה'], 400);
            return;
        }

        // שדות שהמנהל נעל אינם ניתנים לדריסה — הערך השמור נשמר כמות שהוא
        if (!empty($row['time_in'])  && (int)$row['created_by'] !== $uid) $in  = $row['time_in'];
        if (!empty($row['time_out']) && (int)$row['created_by'] !== $uid) $out = $row['time_out'];

        $candidate = ['entry_type' => $type, 'time_in' => $in, 'time_out' => $out,
                      'requires' => $row['requires']];
        $status = HoursModel::isComplete($candidate) ? 'filled' : 'requested';

        HoursModel::updateEntry((int)$id, [
            'entry_type' => $type, 'time_in' => $in, 'time_out' => $out,
            'note' => $b['note'] ?? null, 'status' => $status,
            'filled_by' => $uid,
        ]);

        $this->json(['ok' => true, 'status' => $status]);
    }

    public function addOwnEntry(): void
    {
        $this->requirePermission('canReportHours');
        $this->verifyCsrf();

        $uid  = (int)Auth::user()['id'];
        $date = (string)($this->jsonBody()['work_date'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->json(['error' => 'תאריך לא תקין'], 400);
            return;
        }
        if ($date > date('Y-m-d')) {
            $this->json(['error' => 'לא ניתן לדווח על תאריך עתידי'], 400);
            return;
        }

        $id = HoursModel::createEntry([
            'user_id' => $uid, 'work_date' => $date, 'entry_type' => 'regular',
            'time_in' => null, 'time_out' => null, 'requires' => 'both',
            'note' => null, 'created_by' => $uid,
        ]);

        $this->json(['ok' => true, 'id' => $id]);
    }

    /* ── שכבת המנהל ── */

    public function manage(): void
    {
        $this->requirePermission('canManageHours');
        $month = $this->normalizeMonth($this->get('month'));
        $ts    = strtotime($month . '-01');
        $days  = (int)date('t', $ts);

        $this->view('pages/hours/manage', [
            'month'       => $month,
            'monthLabel'  => $this->monthLabel($month),
            'prevMonth'   => date('Y-m', strtotime('-1 month', $ts)),
            'nextMonth'   => date('Y-m', strtotime('+1 month', $ts)),
            'days'        => $days,
            'users'       => HoursModel::activeUsers(),
            'grid'        => HoursModel::monthGrid($month),
            'markedCount' => HoursModel::markedCount(),
        ]);
    }

    public function apiCell(): void
    {
        $this->requirePermission('canManageHours');
        $uid  = (int)$this->get('user_id', 0);
        $date = (string)$this->get('date', '');
        if (!$uid || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->json(['error' => 'פרמטרים חסרים'], 400);
            return;
        }
        $this->json(['rows' => HoursModel::forUserDate($uid, $date)]);
    }

    public function addRequest(): void
    {
        $this->requirePermission('canManageHours');
        $this->verifyCsrf();
        $b = $this->jsonBody();

        $uid  = (int)($b['user_id'] ?? 0);
        $date = (string)($b['work_date'] ?? '');
        if (!$uid || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->json(['error' => 'פרמטרים חסרים'], 400);
            return;
        }

        $type = (string)($b['entry_type'] ?? 'regular');
        if (!in_array($type, self::TYPES, true)) {
            $this->json(['error' => 'סוג דיווח לא תקין'], 400);
            return;
        }

        $in  = $type === 'regular' ? (($b['time_in']  ?? null) ?: null) : null;
        $out = $type === 'regular' ? (($b['time_out'] ?? null) ?: null) : null;
        if ($in && $out && $out <= $in) {
            $this->json(['error' => 'שעת יציאה מוקדמת משעת כניסה'], 400);
            return;
        }

        $req = (string)($b['requires'] ?? 'both');
        if (!in_array($req, ['both','in','out'], true)) $req = 'both';

        $id = HoursModel::createEntry([
            'user_id' => $uid, 'work_date' => $date, 'entry_type' => $type,
            'time_in' => $in, 'time_out' => $out, 'requires' => $req,
            'note' => ($b['note'] ?? null) ?: null,
            'created_by' => (int)Auth::user()['id'],
        ]);

        \Core\ActivityLog::log('יצירת דרישת דיווח שעות', 'hours_entry', $id, $date,
                               "דרישת דיווח לעובד #$uid בתאריך $date");
        $this->json(['ok' => true, 'id' => $id]);
    }

    public function addBulkRequests(): void
    {
        $this->requirePermission('canManageHours');
        $this->verifyCsrf();
        $b     = $this->jsonBody();
        $cells = $b['cells'] ?? [];
        if (!is_array($cells) || !$cells) {
            $this->json(['error' => 'לא נבחרו תאים'], 400);
            return;
        }

        $req = (string)($b['requires'] ?? 'both');
        if (!in_array($req, ['both','in','out'], true)) $req = 'both';
        $note = ($b['note'] ?? null) ?: null;
        $me   = (int)Auth::user()['id'];
        $n    = 0;

        foreach ($cells as $c) {
            if (!is_array($c)) continue;
            $uid  = (int)($c['user_id'] ?? 0);
            $date = (string)($c['work_date'] ?? '');
            if (!$uid || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;
            HoursModel::createEntry([
                'user_id' => $uid, 'work_date' => $date, 'entry_type' => 'regular',
                'time_in' => null, 'time_out' => null, 'requires' => $req,
                'note' => $note, 'created_by' => $me,
            ]);
            $n++;
        }

        \Core\ActivityLog::log('יצירת דרישות דיווח מרובות', 'hours_entry', null, null,
                               "נוצרו $n דרישות דיווח");
        $this->json(['ok' => true, 'created' => $n]);
    }

    public function managerUpdate(string $id): void
    {
        $this->requirePermission('canManageHours');
        $this->verifyCsrf();
        $b = $this->jsonBody();

        if (!HoursModel::find((int)$id)) {
            $this->json(['error' => 'שורה לא נמצאה'], 404);
            return;
        }

        $type = (string)($b['entry_type'] ?? 'regular');
        if (!in_array($type, self::TYPES, true)) {
            $this->json(['error' => 'סוג דיווח לא תקין'], 400);
            return;
        }

        $in  = $type === 'regular' ? (($b['time_in']  ?? null) ?: null) : null;
        $out = $type === 'regular' ? (($b['time_out'] ?? null) ?: null) : null;
        if ($in && $out && $out <= $in) {
            $this->json(['error' => 'שעת יציאה מוקדמת משעת כניסה'], 400);
            return;
        }

        $req = (string)($b['requires'] ?? 'both');
        if (!in_array($req, ['both','in','out'], true)) $req = 'both';

        HoursModel::managerUpdate((int)$id, [
            'entry_type' => $type, 'time_in' => $in, 'time_out' => $out,
            'requires' => $req, 'note' => ($b['note'] ?? null) ?: null,
            'filled_by' => (int)Auth::user()['id'],
        ]);

        $this->json(['ok' => true]);
    }

    public function deleteEntry(string $id): void
    {
        $this->requirePermission('canManageHours');
        $this->verifyCsrf();
        HoursModel::deleteEntry((int)$id);
        \Core\ActivityLog::log('מחיקת שורת דיווח שעות', 'hours_entry', (int)$id, null,
                               "נמחקה שורת דיווח #$id");
        $this->json(['ok' => true]);
    }

    public function toggleMark(): void
    {
        $this->requirePermission('canManageHours');
        $this->verifyCsrf();
        $b  = $this->jsonBody();
        $id = (int)($b['id'] ?? 0);
        if (!$id) {
            $this->json(['error' => 'מזהה חסר'], 400);
            return;
        }
        HoursModel::setMark($id, (bool)($b['on'] ?? false));
        $this->json(['ok' => true, 'marked' => HoursModel::markedCount()]);
    }
}
