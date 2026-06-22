<?php
declare(strict_types=1);
/**
 * migration_automations.php
 * יוצר טבלת automations ב-alon_db2 (במקום CronJob ב-alon_db)
 *
 * php config/migration_automations.php
 */

define('ROOT', __DIR__ . '/..');
$cfg = require ROOT . '/config/config.php';
$db  = $cfg['db']; // alon_db2

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
    $db['user'], $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "\nמתחבר ל: {$db['name']}\n\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS automations (
    id                      INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
    created_at              DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- מי פתח
    user_id                 INT              NOT NULL,
    user_name               VARCHAR(120)     NOT NULL DEFAULT '',
    user_mail               VARCHAR(255)     NOT NULL DEFAULT '',

    -- סוג המשימה
    type_of_job             VARCHAR(64)      NOT NULL,
    value_of_type           VARCHAR(64)      NULL,   -- מספר קריאה / טלפון / הזמנה
    condition_of_type       VARCHAR(255)     NULL,   -- statusID / הערת הזמנה

    -- מיילים
    mailto                  VARCHAR(255)     NOT NULL DEFAULT '',
    cc_mail                 VARCHAR(512)     NULL,
    msg_from_user           TEXT             NULL,

    -- הגדרות
    max_run                 TINYINT UNSIGNED NOT NULL DEFAULT 1,
    run_even_diff           TINYINT(1)       NOT NULL DEFAULT 0,
    current_save_value      VARCHAR(64)      NULL,   -- snapshot סטטוס בזמן יצירה
    upto_date               DATETIME         NOT NULL,

    -- סטטוס
    is_active               TINYINT(1)       NOT NULL DEFAULT 1,
    status_of_job           VARCHAR(32)      NOT NULL DEFAULT 'פעיל',
    count_run               SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status_change_time      DATETIME         NULL,

    INDEX idx_auto_user   (user_id),
    INDEX idx_auto_active (is_active),
    INDEX idx_auto_type   (type_of_job),
    INDEX idx_auto_upto   (upto_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "✓ טבלת automations נוצרה (או כבר קיימת)\n\n";
echo "סה\"כ: הרצה הסתיימה.\n\n";
