<?php
/**
 * api/crm/search-calls.php
 * GET ?phone=0501234567
 * Returns calls in the last 6 days for the given phone number.
 * Also returns caller_name if found in crm_caller_notes.
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);

define('ROOT', dirname(__DIR__, 2));
define('SRC',  ROOT . '/src');
require ROOT . '/config/bootstrap.php';
use Core\Auth;
use Core\DB;

header('Content-Type: application/json; charset=utf-8');

if (!Auth::user()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$phone = preg_replace('/\D/', '', $_GET['phone'] ?? '');
if (strlen($phone) < 7) {
    echo json_encode(['ok' => false, 'error' => 'invalid phone', 'data' => [], 'caller_name' => '']);
    exit;
}

if (str_starts_with($phone, '972')) {
    $phone = '0' . substr($phone, 3);
}

$canRec = Auth::can('pbxRecording');

try {
    $pdo = DB::get();

    /*
     * ┌─────────────────────────────────────────────────────────────────────┐
     * │  Adjust table/column names to match your PBX call-log schema.      │
     * │  Expected columns:                                                  │
     * │   call_time, duration, agent, dept, direction, recording_url(opt)  │
     * └─────────────────────────────────────────────────────────────────────┘
     */
    $recCol = $canRec ? ", c.recording_url" : ", NULL AS recording_url";

    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(c.call_time, '%d/%m/%Y %H:%i') AS call_time,
            SEC_TO_TIME(c.duration_sec)                 AS duration,
            u.full_name                                 AS agent,
            d.dept_name                                 AS dept,
            c.direction
            {$recCol}
        FROM   pbx_calls c
        LEFT JOIN users       u ON u.user_id  = c.agent_id
        LEFT JOIN departments d ON d.dept_id  = c.dept_id
        WHERE  c.caller_phone = :phone
          AND  c.call_time >= DATE_SUB(NOW(), INTERVAL 6 DAY)
        ORDER  BY c.call_time DESC
        LIMIT  50
    ");
    $stmt->execute([':phone' => $phone]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Caller name from notes table
    $nameStmt = $pdo->prepare("
        SELECT customer_name
        FROM   crm_caller_notes
        WHERE  phone = :phone
          AND  customer_name IS NOT NULL
          AND  customer_name != ''
        ORDER  BY created_at DESC
        LIMIT  1
    ");
    $nameStmt->execute([':phone' => $phone]);
    $callerName = $nameStmt->fetchColumn() ?: '';

    // Also surface any critical note
    $critStmt = $pdo->prepare("
        SELECT note
        FROM   crm_caller_notes
        WHERE  phone = :phone
          AND  is_critical = 1
        ORDER  BY created_at DESC
        LIMIT  1
    ");
    $critStmt->execute([':phone' => $phone]);
    $criticalNote = $critStmt->fetchColumn() ?: '';

    echo json_encode([
        'ok'            => true,
        'data'          => $rows,
        'caller_name'   => $callerName,
        'critical_note' => $criticalNote,
    ]);

} catch (Throwable $ex) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db error', 'data' => [], 'caller_name' => '']);
}