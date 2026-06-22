<?php
declare(strict_types=1);
/**
 * migration_glassix_token.php
 *
 * יוצר טבלת glassix_token ב-alon_db2
 * (אם קיימת — מוסיף עמודות חסרות בלבד)
 *
 * php v2/config/migration_glassix_token.php
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

run($pdo, 'glassix_token — CREATE TABLE', "
CREATE TABLE IF NOT EXISTS glassix_token (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    user_mail  VARCHAR(120) NOT NULL,
    dept_slug  VARCHAR(30)  NOT NULL DEFAULT 'service',
    token      TEXT         NOT NULL,
    expires_in DATETIME     NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_dept (user_mail, dept_slug)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
");

// עמודה לטבלה קיימת (אם כבר נוצרה בלי dept_slug)
run($pdo, 'glassix_token.dept_slug — ADD COLUMN',
    "ALTER TABLE glassix_token ADD COLUMN dept_slug VARCHAR(30) NOT NULL DEFAULT 'service' AFTER user_mail");
run($pdo, 'glassix_token — DROP old unique key',
    "ALTER TABLE glassix_token DROP INDEX uq_user_mail");
run($pdo, 'glassix_token — ADD new unique key',
    "ALTER TABLE glassix_token ADD UNIQUE KEY uq_user_dept (user_mail, dept_slug)");

echo "\n=== Migration: glassix_token @ {$db['name']} ===\n";
foreach ($steps as $s) echo $s . "\n";
echo "\nסה\"כ " . count($steps) . " שלבים.\n\n";
