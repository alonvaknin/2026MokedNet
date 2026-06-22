<?php
/**
 * api/crm/search-service.php
 * GET ?phone=0501234567
 * Returns open service tickets for the given phone in the last 180 days.
 */
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
    echo json_encode(['ok' => false, 'error' => 'invalid phone', 'data' => []]);
    exit;
}

// Strip leading country code if present (972 → 0)
if (str_starts_with($phone, '972')) {
    $phone = '0' . substr($phone, 3);
}

try {
    $pdo = DB::get();

    /*
     * ┌─────────────────────────────────────────────────────────────────────┐
     * │  Adjust table/column names to match your schema.                   │
     * │  Expected columns returned:                                         │
     * │   ticket_id, open_date, subject/description, status, dept, agent   │
     * └─────────────────────────────────────────────────────────────────────┘
     */
    $stmt = $pdo->prepare("
        SELECT
            t.ticket_id,
            DATE_FORMAT(t.open_date, '%d/%m/%Y %H:%i') AS open_date,
            t.subject                                   AS description,
            t.status,
            d.dept_name                                 AS dept,
            u.full_name                                 AS agent
        FROM   crm_tickets t
        LEFT JOIN departments d ON d.dept_id  = t.dept_id
        LEFT JOIN users       u ON u.user_id  = t.agent_id
        WHERE  t.customer_phone = :phone
          AND  t.open_date >= DATE_SUB(NOW(), INTERVAL 180 DAY)
        ORDER  BY t.open_date DESC
        LIMIT  30
    ");
    $stmt->execute([':phone' => $phone]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'data' => $rows]);

} catch (Throwable $ex) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db error', 'data' => []]);
}