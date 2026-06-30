<?php
declare(strict_types=1);
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

$csrfHeader  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$csrfSession = $_SESSION['csrf_token'] ?? '';
if (!$csrfHeader || !hash_equals($csrfSession, $csrfHeader)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true);
$score = (int)($body['score'] ?? 0);

if ($score <= 0 || $score > 99999) {
    echo json_encode(['ok' => false, 'error' => 'invalid score']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'no user']);
    exit;
}

try {
    $current = (int)DB::value(
        'SELECT score FROM game_scores WHERE user_id = ?',
        [$userId]
    );

    if ($score <= $current) {
        echo json_encode(['ok' => true, 'saved' => false]);
        exit;
    }

    DB::execute(
        'INSERT INTO game_scores (user_id, score, played_at)
         VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE score = VALUES(score), played_at = NOW()',
        [$userId, $score]
    );

    echo json_encode(['ok' => true, 'saved' => true]);
} catch (Throwable $ex) {
    error_log('[game/score] ' . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server error']);
}
