<?php
/**
 * api/crm/save-note.php
 * POST JSON { phone, name, note, critical, email }
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';
use Core\Auth;
use Core\DB;

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

$body = json_decode(file_get_contents('php://input'), true);

$phone    = preg_replace('/\D/', '', $body['phone']    ?? '');
$name     = trim($body['name']     ?? '');
$note     = trim($body['note']     ?? '');
$critical = !empty($body['critical']);
$sendMail = !empty($body['email']);

if (strlen($phone) < 7) {
    echo json_encode(['ok' => false, 'error' => 'invalid phone']);
    exit;
}
if (!$name && !$note) {
    echo json_encode(['ok' => false, 'error' => 'nothing to save']);
    exit;
}

if (str_starts_with($phone, '972')) {
    $phone = '0' . substr($phone, 3);
}

$user  = Auth::user();
$agent = $user['full_name'] ?? '';
$uid   = $user['user_id']   ?? 0;

try {
    $pdo = DB::get();

    $stmt = $pdo->prepare("
        INSERT INTO crm_caller_notes
            (phone, customer_name, note, is_critical, agent_id, agent_name, created_at)
        VALUES
            (:phone, :name, :note, :critical, :agent_id, :agent_name, NOW())
    ");
    $stmt->execute([
        ':phone'      => $phone,
        ':name'       => $name ?: null,
        ':note'       => $note ?: null,
        ':critical'   => $critical ? 1 : 0,
        ':agent_id'   => $uid,
        ':agent_name' => $agent,
    ]);

    // Optional: send documentation email to agent
    if ($sendMail && !empty($user['email'])) {
        $subject = "תיעוד שיחה — {$phone}" . ($name ? " ({$name})" : '');
        $body = "נציג: {$agent}\nטלפון: {$phone}\n" . ($name ? "שם: {$name}\n" : '') . ($note ? "\nהערה:\n{$note}" : '');
        // Use your mailer here, e.g.:
        // mail($user['email'], $subject, $body);
    }

    echo json_encode(['ok' => true]);

} catch (Throwable $ex) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db error: ' . $ex->getMessage()]);
}
