<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('SRC',  ROOT . '/src');

require ROOT . '/config/bootstrap.php';

use Core\DB;

// ── APP_URL constant ────────────────────────────────────────────────────────
if (!defined('APP_URL')) {
    define('APP_URL', CFG['app']['url'] ?? 'https://moked-net.co.il');
}
