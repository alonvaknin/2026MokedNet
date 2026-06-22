<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('SRC',  ROOT . '/src');

require_once ROOT . '/config/bootstrap.php';

use Core\DB;

try {
    DB::execute("
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id    INT UNSIGNED NOT NULL,
            token      CHAR(64)     NOT NULL UNIQUE,
            expires_at DATETIME     NOT NULL,
            used_at    DATETIME     NULL DEFAULT NULL,
            created_at DATETIME     NOT NULL DEFAULT NOW(),
            INDEX idx_token (token),
            INDEX idx_user  (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Migration complete: password_reset_tokens created.\n";
} catch (\Throwable $e) {
    echo "Migration FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
