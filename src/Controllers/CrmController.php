<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\DB;

class CrmController extends Controller
{
    private const WIZE_BASE    = 'https://bug.wizenet.co.il/wizeapi/';
    private const WIZE_TOKEN   = 'ABUltIBvgMHYUg6NPaZ4cWA2p5467Jb';
    private const MVOICE_BASE   = 'https://app.mvoice.co.il/api/json/cdrs/list/';
    private const IGNORE_PHONES = ['0523122212'];

    public function index(): void
    {
        $this->requireAuth();
        $this->redirect('/dashboard');
    }

    // ── GET /api/crm/calls?phone=05XXXXXXXX ─────────────────────────────────
    public function apiCalls(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $phone = preg_replace('/\D/', '', $_GET['phone'] ?? '');
        if (strlen($phone) < 7) {
            echo json_encode(['ok' => false, 'error' => 'invalid phone', 'data' => [], 'caller_name' => '']);
            return;
        }
        if (str_starts_with($phone, '972')) {
            $phone = '0' . substr($phone, 3);
        }
        if (in_array($phone, self::IGNORE_PHONES)) {
            echo json_encode(['ok' => true, 'data' => [], 'caller_name' => '', 'critical_note' => '']);
            return;
        }

        // שם לקוח + הערה קריטית מ-DB המקומי
        try {
            $callerName = DB::value(
                "SELECT customer_name FROM crm_caller_notes
                 WHERE phone = ? AND customer_name IS NOT NULL AND customer_name != ''
                 ORDER BY created_at DESC LIMIT 1",
                [$phone]
            ) ?: '';
            $criticalNote = DB::value(
                "SELECT note FROM crm_caller_notes
                 WHERE phone = ? AND is_critical = 1
                 ORDER BY created_at DESC LIMIT 1",
                [$phone]
            ) ?: '';
        } catch (\Throwable) {
            $callerName   = '';
            $criticalNote = '';
        }

        // שיחות מ-mvoice לפי cnumber (מספר המתקשר)
        $now   = time();
        $range = $_GET['range'] ?? 'last1week';
        $start = match($range) {
            '1MonthOld'   => strtotime('-1 month'),
            'halfYearOld' => strtotime('-6 months'),
            '1YearOld'    => strtotime('-1 year'),
            default       => strtotime('-1 week'),
        };
        $mvoice = CFG['mvoice'] ?? [];
        $url   = self::MVOICE_BASE . '?' . http_build_query([
            'auth_username' => $mvoice['user'] ?? '',
            'auth_password' => $mvoice['pass'] ?? '',
            'direction'     => 'in',
            'start'         => $start,
            'end'           => $now,
            'callerid'      => $phone,
        ]);

        $ctx = stream_context_create(['http' => ['timeout' => 12]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            echo json_encode(['ok' => false, 'error' => 'שגיאת חיבור ל-mvoice', 'data' => [], 'caller_name' => $callerName, 'critical_note' => $criticalNote]);
            return;
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            echo json_encode(['ok' => false, 'error' => 'mvoice bad response', 'raw' => $raw, 'data' => [], 'caller_name' => $callerName]);
            return;
        }
        $resp = $json['responses'][0] ?? [];
        if (($resp['code'] ?? '') !== '200') {
            echo json_encode(['ok' => false, 'error' => 'mvoice error', 'response' => $resp, 'data' => [], 'caller_name' => $callerName]);
            return;
        }
        $allCalls = $json['data'] ?? [];

        // סינון לפי מספר המתקשר — mvoice לא מסנן בצד שלו
        $phoneDigits = preg_replace('/\D/', '', $phone);
        $calls = array_filter($allCalls, function($c) use ($phoneDigits) {
            $cid = preg_replace('/\D/', '', $c['callerid_internal'] ?? $c['callerid'] ?? '');
            return str_ends_with($cid, substr($phoneDigits, -9)) || str_ends_with($phoneDigits, substr($cid, -9));
        });

        $rows = array_map(fn($c) => [
            'call_time' => date('d/m/Y H:i', $c['start'] ?? 0),
            'duration'  => gmdate('H:i:s', $c['totaltime'] ?? 0),
            'agent'     => $c['callerid_internal'] ?? '',
            'dept'      => $c['dnumber'] ?? '',
            'direction' => 'in',
            'status'    => $c['status'] ?? '',
        ], $calls);

        echo json_encode([
            'ok'            => true,
            'data'          => $rows,
            'caller_name'   => $callerName,
            'critical_note' => $criticalNote,
        ]);
    }

    // ── GET /api/crm/service?phone=05XXXXXXXX ───────────────────────────────
    // קריאות שירות מ-Wizenet API לפי טלפון, טווח 6 חודשים
    public function apiService(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $phone = preg_replace('/\D/', '', $_GET['phone'] ?? '');
        if (strlen($phone) < 9) {
            echo json_encode(['ok' => false, 'error' => 'invalid phone', 'data' => []]);
            return;
        }
        if (str_starts_with($phone, '972')) {
            $phone = '0' . substr($phone, 3);
        }

        $params = [
            'func'     => 'wizeApp_getBICalls',
            'token'    => self::WIZE_TOKEN,
            'ccell'    => $phone,
            'dateFrom' => date('d/m/Y', strtotime('-6 months')),
            'dateTo'   => date('d/m/Y'),
        ];

        $url = self::WIZE_BASE . '?' . http_build_query($params);
        $raw = self::wizeGet($url);

        if ($raw === null) {
            echo json_encode(['ok' => false, 'error' => 'שגיאת חיבור ל-Wizenet', 'data' => []]);
            return;
        }

        $data = array_map(fn($c) => self::normalizeWizeCall($c), $raw);

        echo json_encode(['ok' => true, 'data' => $data]);
    }

    // ── POST /api/crm/note ───────────────────────────────────────────────────
    public function apiSaveNote(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $input    = json_decode(file_get_contents('php://input'), true) ?? [];
        $phone    = preg_replace('/\D/', '', $input['phone'] ?? '');
        $name     = trim($input['name']  ?? '');
        $note     = trim($input['note']  ?? '');
        $critical = !empty($input['critical']);
        $sendMail = !empty($input['email']);

        if (strlen($phone) < 7) {
            echo json_encode(['ok' => false, 'error' => 'invalid phone']); return;
        }
        if (!$name && !$note) {
            echo json_encode(['ok' => false, 'error' => 'nothing to save']); return;
        }
        if (str_starts_with($phone, '972')) {
            $phone = '0' . substr($phone, 3);
        }

        $user  = Auth::user();
        $agent = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $uid   = $user['id'] ?? 0;

        try {
            DB::execute(
                "INSERT INTO crm_caller_notes
                    (phone, customer_name, note, is_critical, agent_id, agent_name, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [$phone, $name ?: null, $note ?: null, $critical ? 1 : 0, $uid, $agent]
            );

            if ($sendMail && !empty($user['email'])) {
                $subject  = "תיעוד שיחה — {$phone}" . ($name ? " ({$name})" : '');
                $mailBody = "נציג: {$agent}\nטלפון: {$phone}\n"
                          . ($name ? "שם: {$name}\n" : '')
                          . ($note ? "\nהערה:\n{$note}" : '');
                // mail($user['email'], $subject, $mailBody);
            }

            echo json_encode(['ok' => true]);

        } catch (\Throwable $ex) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'db error: ' . $ex->getMessage()]);
        }
    }

    // ── POST /api/crm/wa ─────────────────────────────────────────────────────
    public function apiSendWa(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $input    = json_decode(file_get_contents('php://input'), true) ?? [];
        $phone    = preg_replace('/\D/', '', $input['phone'] ?? '');
        $name     = trim($input['name'] ?? '');
        $note     = trim($input['note'] ?? '');
        $deptSlug = trim($input['dept'] ?? '');
        $assign   = (bool)($input['assign'] ?? true);

        if (strlen($phone) < 7) {
            echo json_encode(['ok' => false, 'error' => 'invalid phone']); return;
        }
        if (str_starts_with($phone, '0')) {
            $phone = '972' . substr($phone, 1);
        }

        try {
            $user  = Auth::user();
            $email = $user['email'] ?? '';
            $uid   = (int)($user['id'] ?? 0);

            $service = new \Services\GlassixService($deptSlug, $email, $uid);
            $result  = $service->sendWhatsApp($phone, $name, $note, $assign);
            echo json_encode($result);

        } catch (\Throwable $ex) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $ex->getMessage()]);
        }
    }

    // ── Wizenet helpers ──────────────────────────────────────────────────────

    private static function wizeGet(string $url): ?array
    {
        $ctx = stream_context_create(['http' => [
            'timeout' => 12,
            'method'  => 'GET',
            'header'  => "Accept: application/json\r\n",
        ]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) return null;
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private static function normalizeWizeCall(array $c): array
    {
        return [
            'ticket_id'   => $c['CallID']      ?? '',
            'open_date'   => $c['createDate']   ?? '',
            'description' => $c['Pname']        ?? $c['CallTypeName'] ?? '',
            'status'      => $c['statusName']   ?? '',
            'dept'        => $c['OriginName']   ?? '',
            'agent'       => $c['techName']     ?? '',
            'company'     => $c['Ccompany']     ?? '',
            'branch'      => $c['Cname']        ?? '',
            'contact'     => $c['ContctName']   ?? '',
        ];
    }

    // ── GET /api/crm/notes?phone=05XXXXXXXX ─────────────────────────────────
    public function apiNotes(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $phone = preg_replace('/\D/', '', $_GET['phone'] ?? '');
        if (strlen($phone) < 7) {
            echo json_encode(['ok' => false, 'data' => []]);
            return;
        }
        if (str_starts_with($phone, '972')) {
            $phone = '0' . substr($phone, 3);
        }

        try {
            $rows = DB::query(
                "SELECT id, phone, customer_name, note, is_critical,
                        agent_name, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS created_at
                 FROM   crm_caller_notes
                 WHERE  phone = ?
                 ORDER  BY created_at DESC
                 LIMIT  20",
                [$phone]
            );
            echo json_encode(['ok' => true, 'data' => $rows]);
        } catch (\Throwable $ex) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'data' => []]);
        }
    }
}