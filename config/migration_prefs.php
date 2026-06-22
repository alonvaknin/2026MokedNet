<?php
/**
 * migration_prefs.php — מריץ פעם אחת
 * יוצר טבלת user_preferences ב-alon_db2
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
    try { $pdo->exec($sql); $steps[] = "✓ $desc"; }
    catch (PDOException $e) {
        $steps[] = (str_contains($e->getMessage(),'already exists') || str_contains($e->getMessage(),'Duplicate'))
            ? "– $desc (כבר קיים)"
            : "✗ $desc: " . $e->getMessage();
    }
}

run($pdo, 'user_preferences',
    "CREATE TABLE IF NOT EXISTS user_preferences (
        user_id     INT UNSIGNED NOT NULL,
        pref_key    VARCHAR(50)  NOT NULL,
        pref_value  TEXT         NOT NULL,
        updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, pref_key),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "\n=== Migration prefs ===\n";
foreach ($steps as $s) echo $s . "\n";
echo "\n";
