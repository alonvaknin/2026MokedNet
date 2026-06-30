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

$userId = (int)($_SESSION['user_id'] ?? 0);

try {
    $top5 = DB::query(
        'SELECT u.first_name, u.last_name, gs.score, gs.user_id
         FROM game_scores gs
         JOIN users u ON u.id = gs.user_id
         ORDER BY gs.score DESC
         LIMIT 5',
        []
    );

    $myScore = (int)DB::value(
        'SELECT score FROM game_scores WHERE user_id = ?',
        [$userId]
    );

    $result = array_map(function ($row) use ($userId) {
        $first = $row['first_name'] ?? '';
        $last  = $row['last_name']  ?? '';
        $name  = $first . (mb_strlen($last) ? ' ' . mb_substr($last, 0, 1) . '\'' : '');
        return [
            'name'  => $name,
            'score' => (int)$row['score'],
            'is_me' => (int)$row['user_id'] === $userId,
        ];
    }, $top5 ?: []);

    echo json_encode(['ok' => true, 'top5' => $result, 'my_score' => $myScore]);
} catch (Throwable $ex) {
    error_log('[game/leaderboard] ' . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server error']);
}
