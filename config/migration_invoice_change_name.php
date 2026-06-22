<?php
declare(strict_types=1);
/**
 * הרץ פעם אחת:  php config/migration_invoice_change_name.php
 */
define('ROOT', __DIR__ . '/..');
$cfg = require ROOT . '/config/config.php';
$db  = $cfg['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}",
    $db['user'], $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function run(PDO $pdo, string $desc, string $sql): void {
    try {
        $pdo->exec($sql);
        echo "✓ $desc\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'already exists')) {
            echo "– $desc (כבר קיים)\n";
        } else {
            echo "✗ $desc: " . $e->getMessage() . "\n";
        }
    }
}

run($pdo, 'invoice_change_name table',
    "CREATE TABLE IF NOT EXISTS invoice_change_name (
        id                 INT AUTO_INCREMENT PRIMARY KEY,
        open_by_id         INT NOT NULL,
        open_by_name       VARCHAR(100) NOT NULL DEFAULT '',
        new_name           VARCHAR(100) NOT NULL,
        invoice_sap_number VARCHAR(20)  NOT NULL,
        invoice_note       VARCHAR(500) NOT NULL DEFAULT '',
        customer_phone     VARCHAR(30)  NOT NULL DEFAULT '',
        customer_mail      VARCHAR(150) NOT NULL DEFAULT '',
        customer_name      VARCHAR(100) NOT NULL DEFAULT '',
        status             VARCHAR(30)  NOT NULL DEFAULT 'פתוחה',
        care_by            VARCHAR(100) NOT NULL DEFAULT '',
        time_added         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        time_change_status DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        isActive           TINYINT      NOT NULL DEFAULT 1,
        INDEX idx_status (status),
        INDEX idx_invoice (invoice_sap_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

echo "\nהמיגרציה הושלמה.\n";
