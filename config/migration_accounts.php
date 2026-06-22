<?php
declare(strict_types=1);
/**
 * migration_accounts.php
 *
 * יוצר טבלת mokedAccounts ב-alon_db2
 * (אם קיימת — מוסיף עמודות חסרות בלבד)
 *
 * php config/migration_accounts.php
 */

define('ROOT', __DIR__ . '/..');
$cfg = require ROOT . '/config/config.php';
$db  = $cfg['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
    $db['user'], $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$steps = [];
function run(PDO $pdo, string $desc, string $sql): void {
    global $steps;
    try {
        $pdo->exec($sql);
        $steps[] = "✓ $desc";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (str_contains($msg,'Duplicate column')||str_contains($msg,'already exists')||str_contains($msg,'Duplicate key name')) {
            $steps[] = "– $desc (כבר קיים)";
        } else {
            $steps[] = "✗ $desc: $msg";
        }
    }
}

run($pdo, 'mokedAccounts — CREATE TABLE', "
CREATE TABLE IF NOT EXISTS mokedAccounts (
    id           INT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    appName      VARCHAR(200)    NOT NULL DEFAULT '',
    appUser      VARCHAR(200)    NOT NULL DEFAULT '',
    appPass      VARCHAR(200)    NOT NULL DEFAULT '',
    appNote      TEXT            NOT NULL,
    userID       INT             NOT NULL DEFAULT 0,
    isactive     TINYINT(1)      NOT NULL DEFAULT 1,
    createtime   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by_id    INT             NULL,
    created_by_name  VARCHAR(120)    NULL,
    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_id    INT             NULL,
    updated_by_name  VARCHAR(120)    NULL,
    updated_at       DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (isactive),
    INDEX idx_name   (appName)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
");

run($pdo, 'mokedAccounts.appNote — TEXT',        "ALTER TABLE mokedAccounts MODIFY COLUMN appNote TEXT NOT NULL");
run($pdo, 'mokedAccounts.isactive — index',      "ALTER TABLE mokedAccounts ADD INDEX idx_active (isactive)");
run($pdo, 'mokedAccounts.created_by_id',         "ALTER TABLE mokedAccounts ADD COLUMN created_by_id   INT          NULL AFTER isactive");
run($pdo, 'mokedAccounts.created_by_name',       "ALTER TABLE mokedAccounts ADD COLUMN created_by_name VARCHAR(120) NULL AFTER created_by_id");
run($pdo, 'mokedAccounts.created_at',            "ALTER TABLE mokedAccounts ADD COLUMN created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_by_name");
run($pdo, 'mokedAccounts.updated_by_id',         "ALTER TABLE mokedAccounts ADD COLUMN updated_by_id   INT          NULL AFTER created_at");
run($pdo, 'mokedAccounts.updated_by_name',       "ALTER TABLE mokedAccounts ADD COLUMN updated_by_name VARCHAR(120) NULL AFTER updated_by_id");
run($pdo, 'mokedAccounts.updated_at',            "ALTER TABLE mokedAccounts ADD COLUMN updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP AFTER updated_by_name");

echo "\n=== Migration: mokedAccounts @ {$db['name']} ===\n";
foreach ($steps as $s) echo $s . "\n";
echo "\nסה\"כ " . count($steps) . " שלבים.\n\n";
