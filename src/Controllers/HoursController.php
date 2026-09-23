<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\DB;
use Models\HoursModel;

class HoursController extends Controller
{
    /** ערכי entry_type המותרים — חייב להתאים ל-ENUM בטבלה */
    private const TYPES = ['regular','vacation','reserve','sick','duplicate_delete',
                           'duplicate_in','duplicate_out','other'];

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

    /**
     * גישה לדיווח שעות נקבעת לפי הדגל הפרטני users.hours_reports,
     * ולא לפי קבוצת הרשאות. מנהל דיווח (canManageHours) תמיד רשאי.
     */
    private function requireReporter(): void
    {
        $this->requireAuth();
        if ($this->isReporter()) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $this->json(['error' => 'אינך מוגדר לדיווח שעות'], 403);
            return;
        }
        http_response_code(403);
        $this->view('pages/403', [], 'layouts/main');
        exit;
    }

    private function isReporter(): bool
    {
        if (Auth::can('canManageHours')) return true;
        $u = Auth::user();
        return !empty($u) && (int)DB::value(
            'SELECT hours_reports FROM users WHERE id = ?', [(int)$u['id']]
        ) === 1;
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
        $this->requireReporter();
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
        $this->requireReporter();
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

        // created_by/user_id נדרשים כדי ש-isComplete תזהה שורה עצמית,
        // שנסגרת גם עם שעה אחת בלבד
        $candidate = ['entry_type' => $type, 'time_in' => $in, 'time_out' => $out,
                      'requires'   => $row['requires'],
                      'created_by' => $row['created_by'],
                      'user_id'    => $row['user_id']];
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
        $this->requireReporter();
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

    /* ── התראת הדאשבורד ── */

    /**
     * נקרא מכל עמוד במערכת דרך ה-layout, ולכן אינו נכשל למי שאין לו את ההרשאה —
     * מחזיר 0 בשקט כדי שהפעמון פשוט יישאר מוסתר.
     */
    public function apiPendingCount(): void
    {
        $this->requireAuth();
        if (!$this->isReporter()) {
            $this->json(['count' => 0]);
            return;
        }
        $this->json(['count' => HoursModel::pendingCount((int)Auth::user()['id'])]);
    }

    public function apiPendingList(): void
    {
        $this->requireReporter();
        $rows = HoursModel::pendingForUser((int)Auth::user()['id']);

        // View::component() מדפיסה ומחזירה void — לוכדים את הפלט לבאפר
        ob_start();
        \Core\View::component('hours-table', ['rows' => $rows, 'context' => 'modal']);
        $html = (string)ob_get_clean();

        $this->json(['html' => $html]);
    }

    /**
     * מחיקת שורה שהנציג הוסיף בעצמו.
     * מותרת רק כאשר: השורה שלו, הוא זה שיצר אותה, היא עדיין לא הושלמה,
     * ולא נסגרה. שורה שהמנהל יצר אינה ניתנת למחיקה בידי הנציג — זו
     * דרישה שהופנתה אליו.
     */
    public function deleteOwnEntry(string $id): void
    {
        $this->requireReporter();
        $this->verifyCsrf();

        $uid = (int)Auth::user()['id'];
        $row = HoursModel::find((int)$id);

        if (!$row || (int)$row['user_id'] !== $uid) {
            $this->json(['error' => 'אין הרשאה לשורה זו'], 403);
            return;
        }
        if ((int)$row['created_by'] !== $uid) {
            $this->json(['error' => 'שורה שנוצרה על ידי המנהל אינה ניתנת למחיקה'], 403);
            return;
        }
        if (!empty($row['exported_at'])) {
            $this->json(['error' => 'השורה נסגרה ואינה ניתנת למחיקה'], 409);
            return;
        }
        if ($row['status'] === 'filled') {
            $this->json(['error' => 'שורה שהושלמה אינה ניתנת למחיקה'], 409);
            return;
        }

        HoursModel::deleteEntry((int)$id);
        \Core\ActivityLog::log('מחיקת שורת דיווח עצמית', 'hours_entry', (int)$id, null,
                                'נציג מחק שורה שהוסיף');
        $this->json(['ok' => true]);
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
            'list'        => HoursModel::monthList($month),
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

        $row = HoursModel::find((int)$id);
        if (!$row) {
            $this->json(['error' => 'שורה לא נמצאה'], 404);
            return;
        }
        // שורה שנסגרה (יוצאה לדיווח) נעולה עד לפתיחה מחדש
        if (!empty($row['exported_at'])) {
            $this->json(['error' => 'השורה נסגרה ואינה ניתנת לעריכה'], 409);
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

    /**
     * GET /hours/export — הורדת השורות המסומנות כ-SpreadsheetML.
     * זהו GET (הורדה ישירה מהדפדפן) ולכן אין טוקן CSRF לאמת.
     */
    /** סגירת שורות שנבחרו, ללא הורדת קובץ */
    public function closeRows(): void
    {
        $this->requirePermission('canManageHours');
        $this->verifyCsrf();

        $ids = $this->jsonBody()['ids'] ?? [];
        if (!is_array($ids) || !$ids) {
            $this->json(['error' => 'לא נבחרו שורות'], 400);
            return;
        }
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) { $this->json(['error' => 'לא נבחרו שורות'], 400); return; }

        $n = HoursModel::closeRows($ids);
        \Core\ActivityLog::log('סגירת שורות דיווח שעות', 'hours_entry', null, null,
                                "נסגרו $n שורות");
        $this->json(['ok' => true, 'closed' => $n, 'marked' => HoursModel::markedCount()]);
    }

    /** פתיחה מחדש של שורה שנסגרה */
    public function reopenRows(): void
    {
        $this->requirePermission('canManageHours');
        $this->verifyCsrf();

        $ids = $this->jsonBody()['ids'] ?? [];
        if (!is_array($ids) || !$ids) {
            $this->json(['error' => 'לא נבחרו שורות'], 400);
            return;
        }
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) { $this->json(['error' => 'לא נבחרו שורות'], 400); return; }

        $n = HoursModel::reopenRows($ids);
        \Core\ActivityLog::log('פתיחת שורות דיווח שעות', 'hours_entry', null, null,
                                "נפתחו $n שורות");
        $this->json(['ok' => true, 'reopened' => $n, 'marked' => HoursModel::markedCount()]);
    }

    /**
     * הורדת השורות המסומנות.
     * ?close=1 סוגר אותן לאחר ההורדה; בלעדיו הקובץ יורד והשורות
     * נשארות פתוחות וניתנות לעריכה.
     *
     * הפורמט הוא xlsx אמיתי כשהרחבת zip זמינה, ואחרת SpreadsheetML
     * בסיומת xls — כדי שההורדה לא תיכשל בשרת ללא ZipArchive.
     */
    public function exportXls(): void
    {
        $this->requirePermission('canManageHours');

        $rows = HoursModel::markedRows();
        if (!$rows) {
            $this->json(['error' => 'לא נבחרו שורות לדיווח'], 400);
            return;
        }

        $close = $this->get('close') === '1';

        if (\Services\HoursExporter::supportsXlsx()) {
            $body = \Services\HoursExporter::buildXlsx($rows);
            $name = 'hours-' . date('Y-m-d') . '.xlsx';
            $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $body = \Services\HoursExporter::build($rows);
            $name = 'hours-' . date('Y-m-d') . '.xls';
            $mime = 'application/vnd.ms-excel; charset=UTF-8';
        }

        if ($close) {
            HoursModel::stampExported(array_column($rows, 'id'));
        }
        \Core\ActivityLog::log('ייצוא דיווח שעות', 'hours_entry', null, null,
                               'הורדו ' . count($rows) . ' שורות'
                               . ($close ? ' ונסגרו' : ' ללא סגירה'));

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . strlen($body));
        header('Cache-Control: no-store');
        echo $body;
        exit;
    }
}
