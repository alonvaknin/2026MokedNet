<?php
/**
 * GET  /api/crm/glassix-history?phone=05XXXXXXXX
 * POST /api/crm/glassix-history  { action: 'messages', ticket_id: '...', dept: 'service' }
 *
 * מחזיר היסטוריית שיחות Glassix לפי מספר טלפון (12 ימים אחרונים)
 * או הודעות של ticket ספציפי.
 */
define('ROOT', dirname(__DIR__, 2));
define('SRC',  ROOT . '/src');
require ROOT . '/config/bootstrap.php';

use Core\Auth;
use Services\GlassixService;

header('Content-Type: application/json; charset=utf-8');

$user = Auth::user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId    = (int)($user['id'] ?? 0);
$userEmail = $user['email'] ?? '';

// POST → קבל הודעות של ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $ticketId = trim($body['ticket_id'] ?? '');
    $dept     = trim($body['dept'] ?? 'service');

    if (!$ticketId) {
        echo json_encode(['ok' => false, 'error' => 'חסר ticket_id']);
        exit;
    }

    $svc = new GlassixService($dept, $userEmail, $userId);
    echo json_encode($svc->getTicketMessages($ticketId));
    exit;
}

// GET → tickets לפי טלפון
$phone = preg_replace('/\D/', '', $_GET['phone'] ?? '');
if (strlen($phone) < 7) {
    echo json_encode(['ok' => false, 'error' => 'invalid phone', 'data' => []]);
    exit;
}

// מנסה כל המחלקות ומאגד תוצאות
$depts   = ['service', 'support', 'sales'];
$all     = [];
$errors  = [];

foreach ($depts as $dept) {
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
}

// מיין לפי תאריך עדכון יורד
usort($all, fn($a, $b) => strcmp($b['updated_at'], $a['updated_at']));

echo json_encode([
    'ok'     => true,
    'data'   => $all,
    'errors' => $errors,
]);
