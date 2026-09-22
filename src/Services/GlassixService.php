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
    private const FALLBACK_EMAIL = 'gild@bug.co.il';

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

        // Glassix API אינו תומך בחיפוש לפי טלפון — שולפים לפי טווח תאריכים ומסננים
        $since = gmdate('d/m/Y H:i:s:00', strtotime('-30 days'));
        $until = gmdate('d/m/Y H:i:s:00');

        $matchedTickets = [];
        $res = $this->curl('GET', '/tickets/list?' . http_build_query([
            'since'     => $since,
            'until'     => $until,
            'sortOrder' => 'DESC',
        ]), [], $token);

        $batch = is_array($res['tickets'] ?? null) ? $res['tickets'] : [];

        foreach ($batch as $t) {
            if (!is_array($t)) continue;
            foreach ($t['participants'] ?? [] as $p) {
                if (($p['type'] ?? '') === 'Client') {
                    $pPhone = preg_replace('/\D/', '', $p['identifier'] ?? '');
                    if ($pPhone === $phone) {
                        $matchedTickets[] = $t;
                        break;
                    }
                }
            }
        }

        $result = [];
        foreach ($matchedTickets as $t) {
            $agent = '';
            $dept  = '';
            foreach ($t['participants'] ?? [] as $p) {
                if (($p['type'] ?? '') === 'Agent')      $agent = $p['name'] ?? '';
                if (($p['type'] ?? '') === 'Department') $dept  = $p['name'] ?? '';
            }
            $ticketId = $t['id'] ?? $t['uniqueId'] ?? $t['ticketId'] ?? $t['ticket_id'] ?? '';
            $result[] = [
                'id'         => $ticketId,
                'subject'    => $t['field1'] ?? $t['subject'] ?? '',
                'state'      => $t['state'] ?? '',
                'created_at' => isset($t['dateCreated'])  ? date('d/m/Y H:i', strtotime($t['dateCreated']))  : '',
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

    /**
     * מחזיר ספירת טיקטים פתוחים (state != Closed) לפי נציג, על פני כל המחלקות.
     * מחזיר ['ok' => true, 'data' => [['agent' => .., 'count' => .., 'byDept' => [...]], ...]]
     */
    public const STATS_VERSION = 'v26-' . '2026-09-17-23';
    private const STATS_DAYS   = 30;
    private const STATS_MAX    = 550;
    private const PAGE_CAP     = 100;
    private const CACHE_TTL    = 600; // שניות — התוצאה משותפת לכל המשתמשים
    private const MAX_CALLS     = 40; // בקשות מקסימום למחלקה
    private const THROTTLE_US   = 120000; // 0.12 שניות בין בקשות
    private const COOLDOWN      = 900; // שניות המתנה אחרי rate limit

    private const DEPT_LABELS = [
        'service' => 'שירות',
        'support' => 'תמיכה',
        'sales'   => 'מכירות',
    ];

    /**
     * מחזיר את הסטטיסטיקה. כברירת מחדל מגיש מהמטמון גם אם פג תוקפו (stale-while-revalidate)
     * ומסמן `stale` כדי שה-frontend יבקש רענון ברקע.
     */
    public static function getOpenTicketCountsByAgent(string $userEmail, int $userId, bool $force = false): array
    {
        [$cached, $isFresh] = self::readStatsCache();

        // הצינון נבדק תמיד ומדווח תמיד — אחרת ה-frontend מציג כפתור עדכון
        // שהשרת ידחה, והמשתמש מקבל "חריגה ממכסה" רק אחרי לחיצה
        $cooldownUntil = self::cooldownUntil();

        if ($cached !== null && (!$force || $cooldownUntil !== null)) {
            $cached['stale']          = !$isFresh;
            $cached['ttl']            = self::CACHE_TTL;
            $cached['errors']         = [];
            $cached['cooldown_until'] = $cooldownUntil;
            return $cached;
        }

        if ($cooldownUntil !== null) {
            return [
                'ok'             => true,
                'version'        => self::STATS_VERSION,
                'depts'          => [],
                'errors'         => [],
                'cached_at'      => date('c'),
                'stale'          => true,
                'cooldown_until' => $cooldownUntil,
                'ttl'            => self::CACHE_TTL,
            ];
        }

        $depts  = [];
        $errors = [];

        foreach (array_keys(self::DEPT_KEYS) as $deptSlug) {
            $service = new self($deptSlug, $userEmail, $userId);
            [$token, $tokenErr] = $service->getToken();
            if (!$token) {
                $errors[] = ['dept' => $deptSlug, 'error' => $tokenErr];
                continue;
            }

            [$dept, $err] = $service->collectOpenTickets($deptSlug, $token);
            if ($dept === null) {
                $errors[] = ['dept' => $deptSlug, 'error' => $err];
                continue;
            }
            $depts[] = $dept;
        }

        foreach ($errors as $e) {
            if (is_string($e['error'] ?? null) && stripos($e['error'], 'rate limit') !== false) {
                self::startCooldown();
                break;
            }
        }

        $result = [
            'ok'             => true,
            'version'        => self::STATS_VERSION,
            'depts'          => $depts,
            'errors'         => $errors,
            'cached_at'      => date('c'),
            'stale'          => false,
            'ttl'            => self::CACHE_TTL,
            'cooldown_until' => self::cooldownUntil(),
        ];

        // שמירה גם כשחלק מהמחלקות נכשלו — אחרת כשלון חלקי (rate limit) גורם
        // לסריקה מלאה בכל טעינה ומנציח את הכשלון
        if ($depts) {
            $result['partial_fetch'] = (bool)$errors;

            // cooldown_until הוא מצב רגעי — לא נשמר במטמון כדי שלא יוגש מיושן
            $toCache = $result;
            unset($toCache['cooldown_until']);
            self::writeStatsCache($toCache);

            return $result;
        }

        // לא התקבל דבר — עדיף להגיש מטמון ישן מאשר מסך ריק
        [$cached] = self::readStatsCache(true);
        if ($cached !== null && !empty($cached['depts'])) {
            $cached['stale']         = true;
            $cached['refresh_error'] = true;
            $cached['errors']        = $errors;
            $cached['ttl']           = self::CACHE_TTL;
            return $cached;
        }

        return $result;
    }

    /**
     * סורק את חלון הזמן למחלקה אחת. מתחיל בחלון רחב ומצמצם רק כשנתקלים בתקרת ה-100,
     * כדי לצמצם את מספר הבקשות ל-API (rate limit).
     *
     * ⚠️ הספירה אינה מדויקת ואינה תואמת את הדוח של Glassix.
     * מה שנבדק מול ה-API (17/09/2026):
     *   - /tickets/list מחייב since+until ומסנן לפי *פעילות*, לא לפי מצב
     *   - הפרמטר state מתעלם לחלוטין (Open/Opened/1 — תוצאה זהה)
     *   - page אסור יחד עם since/until/sortOrder; תקרה קשיחה של 100 לבקשה
     *   - כל עמוד מוחזר לפי פעילות אחרונה ולכן ~94% ממנו Closed
     *   - סריקה של 30 יום החזירה 5 טיקטים פתוחים בלבד מול 993 סגורים,
     *     בעוד שבממשק Glassix מוצגים עשרות פתוחים לנציג
     *   - /tickets/search, /tickets/count, /reports/tickets — לא קיימים ב-v1.2
     * המסקנה: טיקטים פתוחים שלא נגעו בהם לאחרונה לא מוחזרים כלל, ולכן
     * הגישה הזו לא יכולה לתת את המספר הנכון. נדרש endpoint אחר או מעקב
     * מקומי אחרי webhook. עד אז המסך מושהה.
     *
     * @return array{0: ?array, 1: ?string}
     */
    private function collectOpenTickets(string $deptSlug, string $token): array
    {
        $now = time();

        // תורים של [from, to] לסריקה; מתחילים בחלון שלם
        $queue  = [[strtotime('-' . self::STATS_DAYS . ' days'), $now]];
        $seen   = [];
        $agents = [];
        $total  = 0;
        $capped = false;
        $failed = null;
        $calls  = 0;

        while ($queue && $total < self::STATS_MAX && $calls < self::MAX_CALLS) {
            [$from, $to] = array_shift($queue);
            if ($from >= $to) continue;

            if ($calls > 0) usleep(self::THROTTLE_US); // ריווח בין בקשות — מניעת rate limit

            $calls++;
            // state לא נתמך בפועל ב-endpoint הזה (מוחזרים גם Closed) — הסינון בקוד
            $res = $this->curl('GET', '/tickets/list?' . http_build_query([
                'since'     => gmdate('d/m/Y H:i:s:00', $from),
                'until'     => gmdate('d/m/Y H:i:s:00', $to),
                'sortOrder' => 'DESC',
            ]), [], $token);

            if (isset($res['message'])) {
                $failed = $res['message'];
                break;
            }

            $batch = is_array($res['tickets'] ?? null) ? $res['tickets'] : [];

            // התקרה נגעה — סופרים את מה שהתקבל ובנוסף מפצלים את הטווח כדי
            // להשלים את מה שנחתך. seen מונע ספירה כפולה של אותם טיקטים
            if (count($batch) >= self::PAGE_CAP && ($to - $from) > 900) {
                $mid = intdiv($from + $to, 2);
                array_unshift($queue, [$from, $mid], [$mid, $to]);
                $capped = true;
            }

            foreach ($batch as $t) {
                if (!is_array($t)) continue;

                $ticketId = $t['id'] ?? null;
                if ($ticketId === null || isset($seen[$ticketId])) continue;
                $seen[$ticketId] = true;

                // ה-API מתעלם מ-state ומחזיר הכל לפי פעילות, לכן מסננים כאן.
                // Snoozed/Pending הם טיקטים שטרם נסגרו ולכן נספרים גם הם
                if (!in_array($t['state'] ?? '', ['Open', 'Snoozed', 'Pending'], true)) continue;
                if (($t['owner']['type'] ?? '') === 'BOT') continue;

                $agent = self::agentName($t);
                $agents[$agent] = ($agents[$agent] ?? 0) + 1;
                $total++;
            }
        }

        if ($failed !== null && $total === 0) {
            return [null, $failed];
        }

        arsort($agents);

        $agentList = [];
        foreach ($agents as $name => $count) {
            $agentList[] = ['agent' => $name, 'count' => $count];
        }

        return [[
            'slug'    => $deptSlug,
            'label'   => self::DEPT_LABELS[$deptSlug] ?? $deptSlug,
            'total'   => $total,
            'agents'  => $agentList,
            'partial' => ($capped && $queue) || $total >= self::STATS_MAX || $failed !== null,
        ], null];
    }

    /** מחזיר את זמן סיום הצינון (ISO) או null אם אין צינון פעיל */
    private static function cooldownUntil(): ?string
    {
        try {
            $val = DB::value(
                'SELECT expires_at FROM glassix_stats_cache
                  WHERE cache_key = ? AND expires_at > NOW() LIMIT 1',
                ['cooldown']
            );
        } catch (\Throwable) {
            return null;
        }

        return $val ? date('c', strtotime((string)$val)) : null;
    }

    private static function startCooldown(): void
    {
        try {
            DB::execute(
                'INSERT INTO glassix_stats_cache (cache_key, payload, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
                 ON DUPLICATE KEY UPDATE expires_at = VALUES(expires_at)',
                ['cooldown', '1', self::COOLDOWN]
            );
        } catch (\Throwable) {}
    }

    /**
     * @param bool $allowStale האם להחזיר גם רשומה שפג תוקפה
     * @return array{0: ?array, 1: bool} [payload, isFresh]
     */
    private static function readStatsCache(bool $allowStale = true): array
    {
        try {
            $row = DB::row(
                'SELECT payload, expires_at > NOW() AS is_fresh
                   FROM glassix_stats_cache
                  WHERE cache_key = ? LIMIT 1',
                [self::STATS_VERSION]
            );
        } catch (\Throwable) {
            return [null, false];
        }

        if (!$row) return [null, false];

        $isFresh = (bool)($row['is_fresh'] ?? false);
        if (!$isFresh && !$allowStale) return [null, false];

        $data = json_decode((string)$row['payload'], true);
        if (!is_array($data)) return [null, false];

        $data['from_cache'] = true;
        return [$data, $isFresh];
    }

    private static function writeStatsCache(array $result): void
    {
        try {
            DB::execute(
                'INSERT INTO glassix_stats_cache (cache_key, payload, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
                 ON DUPLICATE KEY UPDATE payload = VALUES(payload), expires_at = VALUES(expires_at)',
                [self::STATS_VERSION, json_encode($result, JSON_UNESCAPED_UNICODE), self::CACHE_TTL]
            );
        } catch (\Throwable) {}
    }

    // ── Private methods ──────────────────────────────────────

    /**
     * שם הנציג של הטיקט. owner מחזיק רק מייל, השם המלא נמצא ב-participants לפי אותו id.
     */
    private static function agentName(array $ticket): string
    {
        $ownerId = $ticket['owner']['id'] ?? '';
        if ($ownerId) {
            foreach ($ticket['participants'] ?? [] as $p) {
                if (($p['identifier'] ?? '') === $ownerId) {
                    $name = trim((string)($p['name'] ?? ''));
                    if ($name !== '') return $name;
                }
            }
        }

        return trim((string)($ticket['owner']['UserName'] ?? '')) ?: 'לא משויך';
    }

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
