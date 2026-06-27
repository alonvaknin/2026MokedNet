<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\DB;
use Services\GlassixService;

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

        // mvoice משתמש בסמיקולון כמפריד, ו-caller_number לחיפוש לפי מתקשר
        $now   = time();
        $range = $_GET['range'] ?? 'last1week';
        $start = match($range) {
            '1MonthOld'   => strtotime('-1 month'),
            'halfYearOld' => strtotime('-6 months'),
            '1YearOld'    => strtotime('-1 year'),
            default       => strtotime('-1 week'),
        };
        $mvoice      = CFG['mvoice'] ?? [];
        $phoneSearch = ltrim($phone, '0'); // mvoice מצפה ללא 0 מוביל
        $auth        = 'auth_username='.$mvoice['user'].';auth_password='.$mvoice['pass'].';';

        // שיחות נכנסות (caller_number = המתקשר)
        $inUrl  = self::MVOICE_BASE.'?'.$auth.'start='.$start.';end='.$now.';caller_number='.$phoneSearch;
        // שיחות יוצאות (called_number = היעד שחייגו אליו)
        $outUrl = self::MVOICE_BASE.'?'.$auth.'start='.$start.';end='.$now.';called_number='.$phoneSearch;

        $fetch = function(string $url): array {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15, CURLOPT_SSL_VERIFYPEER=>false]);
            $raw = curl_exec($ch);
            $err = curl_errno($ch);
            curl_close($ch);
            if ($err || $raw === false) return ['ok'=>false,'err'=>$err];
            $j = json_decode($raw, true);
            return ['ok'=>true,'data'=>$j['data']??[]];
        };

        $inRes  = $fetch($inUrl);
        $outRes = $fetch($outUrl);

        if (!$inRes['ok'] && !$outRes['ok']) {
            echo json_encode(['ok'=>false,'error'=>'curl error','data'=>[],'caller_name'=>$callerName,'critical_note'=>$criticalNote]);
            return;
        }

        // מיזוג שיחות נכנסות ויוצאות
        $allCalls = array_merge($inRes['data'] ?? [], $outRes['data'] ?? []);
        usort($allCalls, fn($a, $b) => ($b['start'] ?? 0) <=> ($a['start'] ?? 0));

        // בניית מפה mvoice_id → שם נציג מטבלת users
        $agentMap = [];
        try {
            $agentRows = DB::query("SELECT mvoice_id, CONCAT(first_name,' ',last_name) AS name FROM users WHERE mvoice_id IS NOT NULL AND mvoice_id != ''");
            foreach ($agentRows as $ar) {
                $agentMap[trim($ar['mvoice_id'])] = trim($ar['name']);
            }
        } catch (\Throwable) {}

        // מנקה שם נציג ממספר מוביל (mvoice מחזיר "123 שם" או רק "123")
        $cleanAgent = function(string $raw): string {
            $raw = trim($raw);
            if ($raw === '' || preg_match('/^\d+$/', $raw)) return '';
            return trim(preg_replace('/^\d+\s*/', '', $raw));
        };

        $rows = array_map(function($c) use ($agentMap, $auth, $fetch, $cleanAgent) {
            $uniqueid = $c['uniqueid'] ?? '';
            $snumber  = $c['snumber']  ?? '';
            $dnumber  = $c['dnumber']  ?? '';
            $status   = $c['status']   ?? '';

            // כיוון: uniqueid מכיל "out" לשיחות יוצאות, "in" לנכנסות
            if (str_contains($uniqueid, 'out')) {
                $direction    = 'out';
                $agentKey     = $snumber;
                $agentDisplay = $cleanAgent($c['snumber_display'] ?? '');
            } else {
                $direction    = 'in';
                $agentKey     = $dnumber;
                $agentDisplay = $cleanAgent($c['dnumber_display'] ?? $c['snumber_display'] ?? '');

                // לשיחה נכנסת שנענתה — שלוף cnumber_display (הנציג שבפועל ענה)
                $answeredByAgent = false;
                if ($status === 'answer' && $uniqueid) {
                    $legUrl = self::MVOICE_BASE.'?'.$auth.'callid='.urlencode($uniqueid).';status=answer;dtype=phone';
                    $legs   = $fetch($legUrl);
                    $cn = $cleanAgent(($legs['data'][0] ?? [])['cnumber_display'] ?? '');
                    if ($cn) {
                        $agentDisplay    = $cn;
                        $agentKey        = ($legs['data'][0]['cnumber'] ?? '') ?: $agentKey;
                        $answeredByAgent = true;
                    }
                }
                // ענה IVR/תור אבל לא נציג
                if ($status === 'answer' && !$answeredByAgent) {
                    $status = 'ivr';
                }
            }

            // צלב עם טבלת users לפי mvoice_id אם אין שם תצוגה ישיר
            if (!$agentDisplay) {
                $agentDisplay = $agentMap[$agentKey] ?? $agentMap[ltrim($agentKey, '0')] ?? '';
            }

            // קישור הקלטה
            $recUrl = $c['recording_url'] ?? '';
            if (!$recUrl && $uniqueid) {
                $recUrl = self::MVOICE_BASE.'?'.$auth.'callid='.urlencode($uniqueid).';status=answer;dtype=phone';
            }

            $totaltime = (int)($c['totaltime'] ?? 0);
            return [
                'call_time'     => date('d/m/Y H:i', $c['start'] ?? 0),
                'duration'      => gmdate('H:i:s', $totaltime),
                'duration_sec'  => $totaltime,
                'agent_line'    => $agentKey,
                'agent_name'    => $agentDisplay,
                'dept'          => $dnumber,
                'direction'     => $direction,
                'status'        => $status,
                'uniqueid'      => $uniqueid,
                'recording_url' => $recUrl,
            ];
        }, $allCalls);

        echo json_encode([
            'ok'            => true,
            'data'          => $rows,
            'caller_name'   => $callerName,
            'critical_note' => $criticalNote,
        ]);
    }

    // ── GET /api/crm/calls/recording?uniqueid=XXX ───────────────────────────
    public function apiCallRecording(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $uniqueid = trim($_GET['uniqueid'] ?? '');
        if (!$uniqueid) {
            echo json_encode(['ok'=>false,'error'=>'missing uniqueid']);
            return;
        }

        $mvoice = CFG['mvoice'] ?? [];
        $auth   = 'auth_username='.$mvoice['user'].';auth_password='.$mvoice['pass'].';';
        $url    = self::MVOICE_BASE.'?'.$auth.'callid='.urlencode($uniqueid).';status=answer;dtype=phone';

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>false]);
        $raw = curl_exec($ch);
        curl_close($ch);

        $j = json_decode($raw, true);
        $recUrl = $j['data'][0]['recording_url'] ?? '';

        echo json_encode(['ok'=>(bool)$recUrl, 'url'=>$recUrl]);
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

    // ── GET /api/crm/glassix-history?phone=05XXXXXXXX ───────────────────────
    public function apiGlassixHistory(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $user  = Auth::user();
        $phone = preg_replace('/\D/', '', $_GET['phone'] ?? '');
        if (strlen($phone) < 7) {
            echo json_encode(['ok' => false, 'error' => 'invalid phone', 'data' => []]);
            return;
        }

        $userId    = (int)($user['id']    ?? 0);
        $userEmail = $user['email']        ?? '';
        $depts     = ['service', 'support', 'sales'];
        $all       = [];
        $errors    = [];

        foreach ($depts as $dept) {
            try {
                $svc = new GlassixService($dept, $userEmail, $userId);
                $res = $svc->getTicketsByPhone($phone);
                if ($res['ok'] && !empty($res['data'])) {
                    foreach ($res['data'] as $t) {
                        $t['dept_slug'] = $dept;
                        $all[] = $t;
                    }
                } elseif (!$res['ok']) {
                    $errors[] = $dept . ': ' . ($res['error'] ?? '');
                }
            } catch (\Throwable $ex) {
                $errors[] = $dept . ': ' . $ex->getMessage();
            }
        }

        usort($all, fn($a, $b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));
        // אם אין תוצאות בכלל ויש שגיאות — החזר debug
        echo json_encode([
            'ok'     => true,
            'data'   => $all,
            'errors' => $errors,
            'debug'  => empty($all) && !empty($errors) ? ['userEmail' => $userEmail] : null,
        ]);
    }

    // ── POST /api/crm/glassix-messages ──────────────────────────────────────
    public function apiGlassixMessages(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $user = Auth::user();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $ticketId = trim($body['ticket_id'] ?? '');
        $dept     = trim($body['dept']      ?? 'service');

        if (!$ticketId) {
            echo json_encode(['ok' => false, 'error' => 'חסר ticket_id']);
            return;
        }

        $svc = new GlassixService($dept, $user['email'] ?? '', (int)($user['id'] ?? 0));
        echo json_encode($svc->getTicketMessages($ticketId));
    }
}