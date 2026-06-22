<?php
declare(strict_types=1);

// ── Autoloader ──────────────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $file = SRC . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// ── Config ──────────────────────────────────────────────────────────────────
$cfg = require ROOT . '/config/config.php';
define('CFG', $cfg);

// ── Timezone ────────────────────────────────────────────────────────────────
date_default_timezone_set(CFG['app']['timezone']);

// ── Error handling ──────────────────────────────────────────────────────────
if (CFG['app']['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    set_exception_handler(function (Throwable $e): void {
        error_log($e->getMessage());
        http_response_code(500);
        echo '500 — שגיאת שרת פנימית';
        exit;
    });
}

// ── Session ─────────────────────────────────────────────────────────────────
$sc = CFG['session'];
session_set_cookie_params([
    'lifetime' => $sc['lifetime'],
    'path'     => '/',
    'secure'   => $sc['secure'],
    'httponly' => $sc['httponly'],
    'samesite' => $sc['samesite'],
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
