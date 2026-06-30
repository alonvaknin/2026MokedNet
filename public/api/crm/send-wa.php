<?php
/**
 * api/crm/send-wa.php
 * POST JSON { phone, name, dept, note }
 *
 * Glassix integration stub.
 * Fill in GLASSIX_API_KEY + GLASSIX_CHANNEL_ID from your v1 config.
 */
define('ROOT', dirname(__DIR__, 2));
define('SRC',  ROOT . '/src');
require ROOT . '/config/bootstrap.php';
use Core\Auth;

header('Content-Type: application/json; charset=utf-8');

if (!Auth::user()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method not allowed']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true);
$phone = preg_replace('/\D/', '', $body['phone'] ?? '');
$name  = trim($body['name']  ?? '');
$dept  = trim($body['dept']  ?? '');
$note  = trim($body['note']  ?? '');

if (strlen($phone) < 7) {
    echo json_encode(['ok' => false, 'error' => 'invalid phone']);
    exit;
}

// Normalize to international format for WA
if (str_starts_with($phone, '0')) {
    $phone = '972' . substr($phone, 1);
}

/* ── Glassix config ─────────────────────────────────────── */
// TODO: move to CFG or .env
$GLASSIX_API_KEY    = CFG['glassix']['api_key']    ?? '';
$GLASSIX_APP_KEY    = CFG['glassix']['app_key']    ?? '';
$GLASSIX_CHANNEL_ID = CFG['glassix']['channel_id'] ?? '';
$GLASSIX_BASE       = 'https://app.glassix.com/api/v1.2';

if (!$GLASSIX_API_KEY) {
    // Not configured yet — return stub success so UI flow works
    // Remove this block once Glassix credentials are added
    error_log("[CRM/WA] Glassix not configured. Would send to {$phone}: {$note}");
    echo json_encode(['ok' => true, '_stub' => true]);
    exit;
}

/* ── Auth token ── */
try {
    $authRes = glassixPost("{$GLASSIX_BASE}/token/get", [
        'apiKey' => $GLASSIX_API_KEY,
        'appKey' => $GLASSIX_APP_KEY,
    ]);
    $token = $authRes['access_token'] ?? '';
    if (!$token) throw new RuntimeException('No token');

    /* ── Create / find conversation ── */
    $convRes = glassixPost("{$GLASSIX_BASE}/conversations/", [
        'channelId'   => $GLASSIX_CHANNEL_ID,
        'participants' => [[
            'phoneNumber' => $phone,
            'name'        => $name ?: $phone,
        ]],
    ], $token);

    $convId = $convRes['id'] ?? '';
    if (!$convId) throw new RuntimeException('No conversation id');

    /* ── Send message ── */
    $deptLabel = match($dept) {
        'support' => 'תמיכה טכנית',
        'sales'   => 'מכירות',
        'service' => 'שירות לקוחות',
        default   => $dept,
    };

    $msgText = $note ?: "שלום" . ($name ? " {$name}" : '') . ", נציג {$deptLabel} יחזור אליך בקרוב.";

    glassixPost("{$GLASSIX_BASE}/conversations/{$convId}/messages/send", [
        'text' => $msgText,
    ], $token);

    echo json_encode(['ok' => true, 'conv_id' => $convId]);

} catch (Throwable $ex) {
    http_response_code(500);
    error_log('[CRM/WA] Error: ' . $ex->getMessage());
    echo json_encode(['ok' => false, 'error' => 'שגיאת שרת פנימית']);
}

/* ── Helper ── */
function glassixPost(string $url, array $data, string $token = ''): array {
    $headers = ['Content-Type: application/json'];
    if ($token) $headers[] = "Authorization: Bearer {$token}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$res) throw new RuntimeException("Glassix HTTP error {$code}");
    return json_decode($res, true) ?? [];
}