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
}
