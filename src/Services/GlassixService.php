<?php
declare(strict_types=1);

namespace Services;

use Core\DB;

/**
 * GlassixService — WhatsApp API דרך Glassix.
 *
 * ה-API keys מוגדרים לפי מחלקה.
 * tokens נשמרים ב-glassix_token (טבלת v1) ומתחדשים אוטומטית.
 */
class GlassixService
{
    private const BASE_URL      = 'https://bug-multisystem.glassix.com/api/v1.2';
    private const FALLBACK_EMAIL = 'alon@leonai.io';

    // API keys לפי slug מה-dropdown (support / service / sales)
    private const DEPT_KEYS = [
        'service' => ['key' => 'dc8650f0-e623-4af7-9373-a0fcb2ec04f3',
                      'secret' => 'kfMtTovNuOUnxfYS90MxiY3aYHO5WgVarSaHLFwrAhBAjGL3MIPtcvVMlvULaz1O345eFRG4DfxBX30R6VsNRl857NfZvkRKqxHpKqy5CVp68Tzs5CPEGn6R5Ah1wqId'],
        'support' => ['key' => 'de9c2466-b773-4169-bbb4-e3719ed6f60e',
                      'secret' => 'CM6uWCPdezDRK5TeAufyEFXp87ohQDeDQJAphTYHkpFqYhxuitEVqox6zRwYXrMaU9AB5o7qWYOZAqQbJ49h9JXlVD0Ph9t2coVZgwy4vX9SaY6IAUPdrobLlZp1yrJW'],
        'sales'   => ['key' => 'e8aa8e86-3057-40ea-9c93-2c2fde4d3e9d',
                      'secret' => 'd0ldkjtCfmTr7Pw29NbFPrhmq6AAuCeR2eNddQsHhZM9fBh5EH5Bq7gwS8xojsRdICzcBSZtfUizehGoYE6nGck48aKfdEhyIt1dLF4vsMgWZrSv1IPfwnWpb5ekI3Qb'],
    ];

    private string $deptSlug;
    private string $userEmail;
    private int    $userId;

    public function __construct(string $deptSlug, string $userEmail, int $userId)
    {
        $this->deptSlug  = $deptSlug;
        $this->userEmail = $userEmail;
        $this->userId    = $userId;
    }

    /**
     * מחזיר tickets שנפתחו/עודכנו ב-12 ימים האחרונים עבור מספר טלפון.
     * מחזיר ['ok' => true, 'data' => [...]] או ['ok' => false, 'error' => '...']
     */
    public function getTicketsByPhone(string $phone): array
    {
        $phone = $this->normalizePhone($phone);
        if (!$phone) {
            return ['ok' => false, 'error' => 'מספר טלפון לא תקין'];
        }

        [$token, $tokenErr] = $this->getToken();
        if (!$token) {
            return ['ok' => false, 'error' => 'לא ניתן לקבל token', 'debug' => $tokenErr];
        }

        $since = date('Y-m-d\TH:i:s', strtotime('-12 days'));
        $res = $this->curl('GET', '/tickets/all?' . http_build_query([
            'phoneNumber' => $phone,
            'sinceDate'   => $since,
            'pageSize'    => 50,
        ]), [], $token);

        if (!is_array($res)) {
            return ['ok' => false, 'error' => 'תגובה לא תקינה מ-Glassix'];
        }

        $tickets = $res['data'] ?? $res ?? [];
        if (!is_array($tickets)) {
            $tickets = [];
        }

        $result = [];
        foreach ($tickets as $t) {
            $agent = '';
            $dept  = '';
            foreach ($t['participants'] ?? [] as $p) {
                if (($p['type'] ?? '') === 'Agent') {
                    $agent = $p['name'] ?? '';
                }
                if (($p['type'] ?? '') === 'Department') {
                    $dept = $p['name'] ?? '';
                }
            }
            if (!is_array($t)) continue;
            $ticketId = $t['id'] ?? $t['uniqueId'] ?? $t['ticketId'] ?? $t['ticket_id'] ?? '';
            $result[] = [
                'id'         => $ticketId,
                '_raw_keys'  => array_keys($t),
                'subject'    => $t['field1'] ?? $t['subject'] ?? '',
                'state'      => $t['state'] ?? '',
                'created_at' => isset($t['dateCreated']) ? date('d/m/Y H:i', strtotime($t['dateCreated'])) : '',
                'updated_at' => isset($t['dateModified']) ? date('d/m/Y H:i', strtotime($t['dateModified'])) : '',
                'agent'      => $agent,
                'dept'       => $dept,
                'channel'    => $t['protocolType'] ?? '',
            ];
        }

        usort($result, fn($a, $b) => strcmp($b['updated_at'], $a['updated_at']));
        return ['ok' => true, 'data' => $result];
    }

    /**
     * מחזיר הודעות של ticket ספציפי.
     */
    public function getTicketMessages(string|int $ticketId): array
    {
        [$token, $tokenErr] = $this->getToken();
        if (!$token) {
            return ['ok' => false, 'error' => 'לא ניתן לקבל token', 'debug' => $tokenErr];
        }

        $res = $this->curl('GET', "/tickets/{$ticketId}/messages", [], $token);

        if (!is_array($res)) {
            return ['ok' => false, 'error' => 'תגובה לא תקינה'];
        }

        $messages = $res['data'] ?? $res ?? [];
        if (!is_array($messages)) {
            $messages = [];
        }

        $result = [];
        foreach ($messages as $m) {
            $result[] = [
                'id'        => $m['id'] ?? '',
                'type'      => $m['type'] ?? 'text',
                'text'      => $m['text'] ?? $m['html'] ?? '',
                'sender'    => $m['participantName'] ?? '',
                'sender_type' => $m['participantType'] ?? '',
                'time'      => isset($m['dateCreated']) ? date('d/m/Y H:i', strtotime($m['dateCreated'])) : '',
                'media_url' => $m['fileUrl'] ?? '',
            ];
        }

        return ['ok' => true, 'data' => $result];
    }

    /**
     * שלח WhatsApp ללקוח.
     * מחזיר ['ok' => true] או ['ok' => false, 'error' => '...']
     */
    public function sendWhatsApp(string $phone, string $customerName, string $note = '', bool $assign = true): array
    {
        // נרמל טלפון: 050xxxxxxx → 97250xxxxxxx
        $phone = $this->normalizePhone($phone);
        if (!$phone) {
            return ['ok' => false, 'error' => 'מספר טלפון לא תקין'];
        }

        [$token, $tokenErr] = $this->getToken();
        if (!$token) {
            return ['ok' => false, 'error' => 'לא ניתן לקבל token מ-Glassix', 'debug' => $tokenErr];
        }

        // יצירת ticket
        $ticket = $this->createTicket($token, 'WhatsApp', $phone, $customerName);

        if ($ticket['error'] === 0) {
            $ticketId = $ticket['ticket_id'];
            if ($assign) {
                $this->setOwner($token, $ticketId, $this->userEmail);
            }
        } elseif (isset($ticket['ticket_number'])) {
            $ticketId = $ticket['ticket_number'];
            if ($assign) {
                $this->setOwner($token, $ticketId, $this->userEmail);
            }
        } else {
            return ['ok' => false, 'error' => 'לא ניתן ליצור ticket'];
        }

        if ($note) {
            $this->addNote($token, $ticketId, $note);
        }

        $send = $this->sendTemplate($token, $ticketId);
        if ($send['error'] !== 0) {
            return ['ok' => false, 'error' => $send['data'] ?? 'שגיאת שליחה'];
        }

        $this->log('sendWA', $phone);

        $ticketUrl = 'https://bug-multisystem.glassix.com/app/tickets/' . $ticketId;
        return ['ok' => true, 'ticket_url' => $ticketUrl, 'ticket_id' => $ticketId];
    }

    // ── Private methods ──────────────────────────────────────

    private function normalizePhone(string $phone): ?string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) === 10 && str_starts_with($phone, '0')) {
            $phone = '972' . substr($phone, 1);
        }
        return strlen($phone) === 12 ? $phone : null;
    }

    private function getToken(): array  // [token|null, debug_info]
    {
        // נסה DB קודם — cache לפי user+dept
        $row = DB::row(
            'SELECT token FROM glassix_token
             WHERE dept_slug = ? AND expires_in > NOW() LIMIT 1',
            [$this->deptSlug]
        );
        if ($row) return [$row['token'], null];

        // קבל token חדש מ-Glassix עם המייל הנוכחי
        $creds = self::DEPT_KEYS[$this->deptSlug] ?? self::DEPT_KEYS['service'];
        $res   = $this->curl('POST', '/token/get', [
            'apiKey'    => $creds['key'],
            'apiSecret' => $creds['secret'],
            'userName'  => $this->userEmail,
        ]);

        // אם נכשל — נסה עם fallback email
        if (!isset($res['access_token']) && $this->userEmail !== self::FALLBACK_EMAIL) {
            $res = $this->curl('POST', '/token/get', [
                'apiKey'    => $creds['key'],
                'apiSecret' => $creds['secret'],
                'userName'  => self::FALLBACK_EMAIL,
            ]);
        }

        if (!isset($res['access_token'])) {
            return [null, ['slug' => $this->deptSlug, 'email' => $this->userEmail, 'response' => $res]];
        }

        $token   = $res['access_token'];
        $expires = date('Y-m-d H:i:s', time() + (int)($res['expires_in'] ?? 3600));

        DB::execute(
            'INSERT INTO glassix_token (user_id, user_mail, dept_slug, token, expires_in)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE token = VALUES(token), expires_in = VALUES(expires_in)',
            [$this->userId, $this->userEmail, $this->deptSlug, $token, $expires]
        );

        return [$token, null];
    }

    private function createTicket(string $token, string $type, string $phone, string $name): array
    {
        $res = $this->curl('POST', '/tickets/create', [
            'culture'              => 'he-IL',
            'state'                => 'Open',
            'getAvailableUser'     => false,
            'addIntroductionMessage' => false,
            'enableWebhook'        => false,
            'markAsRead'           => true,
            'field1'               => 'פנייה ללקוח',
            'participants'         => [[
                'name'         => $name,
                'type'         => 'Client',
                'protocolType' => $type,
                'isActive'     => true,
                'isDeleted'    => false,
                'identifier'   => $phone,
            ]],
        ], $token);

        if (isset($res['id'])) {
            return ['error' => 0, 'ticket_id' => $res['id']];
        }
        // ticket קיים
        if (isset($res['message'])) {
            preg_match_all('/\d+/', $res['message'], $m);
            return ['error' => 1, 'ticket_number' => $m[0][0] ?? null];
        }
        return ['error' => 1];
    }

    private function setOwner(string $token, string|int $ticketNum, string $email): void
    {
        $this->curl(
            'PUT',
            "/tickets/setowner/{$ticketNum}?keepCurrentOwnerInConversation=false&nextOwnerUserName={$email}",
            [],
            $token
        );
    }

    private function addNote(string $token, string|int $ticketId, string $note): void
    {
        $this->curl('POST', "/tickets/addnote/{$ticketId}", [
            'html' => "<b style='color:red;'>" . htmlspecialchars($note) . "</b>",
        ], $token);
    }

    private function sendTemplate(string $token, string|int $ticketId): array
    {
        $res = $this->curl('POST', "/tickets/send/{$ticketId}", [
            'enableFreeTextInput' => false,
            'text' => 'שלום 👋, לצורך התחלת התכתבות עם נציגנו *נא ללחוץ על הכפתור מטה* \\ לשלוח לנו הודעה כלשהיא, אחרת לא נוכל לכתוב לכם. תודה',
        ], $token);

        return ['error' => 0, 'data' => $res];
    }

    private function curl(string $method, string $endpoint, array $body = [], string $token = ''): array
    {
        $ch = curl_init();
        $headers = ['accept: application/json', 'content-type: application/json'];
        if ($token) $headers[] = "authorization: Bearer {$token}";

        $opts = [
            CURLOPT_URL            => self::BASE_URL . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
        ];
        if ($body) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        }

        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $err      = curl_errno($ch);
        curl_close($ch);

        if ($err) return [];
        return json_decode($response, true) ?? [];
    }

    private function log(string $action, string $value): void
    {
        try {
            DB::execute(
                'INSERT INTO logger (userId, userName, logWhereChange, logAction, logValue, ipaddress)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$this->userId, $this->userEmail, 'CRM', $action, $value, $_SERVER['REMOTE_ADDR'] ?? '']
            );
        } catch (\Throwable) {}
    }
}
