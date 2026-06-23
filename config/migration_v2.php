<?php
declare(strict_types=1);
/**
 * migration_v2.php — הרץ פעם אחת מה-CLI:
 *   php config/migration_v2.php
 *
 * בטוח לריצה חוזרת — כל שינוי מוגן ב-IF NOT EXISTS / IGNORE.
 */

define('ROOT', __DIR__ . '/..');
define('SRC',  ROOT . '/src');
$cfg = require ROOT . '/config/config.php';

$db  = $cfg['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}",
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
        if (str_contains($e->getMessage(), 'Duplicate column') ||
            str_contains($e->getMessage(), 'already exists')) {
            $steps[] = "– $desc (כבר קיים)";
        } else {
            $steps[] = "✗ $desc: " . $e->getMessage();
        }
    }
}

// ── accounts ───────────────────────────────────────────────
run($pdo, 'accounts.lastLogin',
    "ALTER TABLE accounts ADD COLUMN lastLogin DATETIME NULL DEFAULT NULL");

// ── tasks ──────────────────────────────────────────────────
run($pdo, 'tasks.tasksToken',
    "ALTER TABLE tasks ADD COLUMN tasksToken VARCHAR(64) NULL DEFAULT NULL");

// ── glassix_token ──────────────────────────────────────────
run($pdo, 'glassix_token',
    "CREATE TABLE IF NOT EXISTS glassix_token (
        user_id    INT NOT NULL,
        user_mail  VARCHAR(255) NOT NULL,
        token      TEXT NOT NULL,
        expires_in DATETIME NOT NULL,
        PRIMARY KEY (user_mail),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── v2_sessions ────────────────────────────────────────────
run($pdo, 'v2_sessions',
    "CREATE TABLE IF NOT EXISTS v2_sessions (
        id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        ip         VARCHAR(45) NOT NULL DEFAULT '',
        user_agent VARCHAR(255) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_seen  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── lab_inventory_items ────────────────────────────────────
run($pdo, 'lab_inventory_items',
    "CREATE TABLE IF NOT EXISTS lab_inventory_items (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        item_name   VARCHAR(255) NOT NULL,
        sku         VARCHAR(100) NULL,
        qty         INT NOT NULL DEFAULT 0,
        min_qty     INT NOT NULL DEFAULT 0,
        category_id INT NULL,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

run($pdo, 'lab_inventory_movements',
    "CREATE TABLE IF NOT EXISTS lab_inventory_movements (
        id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        item_id    INT UNSIGNED NOT NULL,
        qty        INT NOT NULL,
        direction  ENUM('in','out') NOT NULL,
        note       VARCHAR(255) NULL,
        user_id    INT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_item (item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── lab_inventory_items: rebuild to match V1 schema ───────
// ⚠️  DROP מושבת בכוונה — גרם למחיקת נתונים בעבר.
// run($pdo, 'lab_inventory_items.DROP_OLD', "DROP TABLE IF EXISTS lab_inventory_items");
run($pdo, 'lab_inventory_items.CREATE',
    "CREATE TABLE IF NOT EXISTS lab_inventory_items (
        id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        part_number      VARCHAR(100) NULL,
        barcode          VARCHAR(100) NULL,
        product_name_en  VARCHAR(255) NOT NULL DEFAULT '',
        tags             VARCHAR(500) NULL,
        compatibility    TEXT         NULL,
        model            VARCHAR(150) NULL,
        manufacturer     VARCHAR(100) NULL,
        location         VARCHAR(150) NULL,
        price_store      DECIMAL(10,2) NULL,
        qty              INT NOT NULL DEFAULT 0,
        incoming_qty     INT NOT NULL DEFAULT 0,
        min_qty          INT NOT NULL DEFAULT 0,
        created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_part   (part_number),
        INDEX idx_barcode(barcode)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ⚠️  DROP מושבת בכוונה — גרם למחיקת נתונים בעבר.
// run($pdo, 'lab_inventory_movements.DROP_OLD', "DROP TABLE IF EXISTS lab_inventory_movements");
run($pdo, 'lab_inventory_movements.CREATE',
    "CREATE TABLE IF NOT EXISTS lab_inventory_movements (
        id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        item_id         INT UNSIGNED NOT NULL,
        direction       ENUM('IN','OUT') NOT NULL DEFAULT 'OUT',
        qty             INT NOT NULL DEFAULT 0,
        user_id         INT NULL,
        technician_id   INT NULL,
        service_call_id VARCHAR(50) NULL,
        notes           VARCHAR(500) NULL,
        serial_number   VARCHAR(150) NULL,
        status          ENUM('pending','approved') NOT NULL DEFAULT 'approved',
        movement_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_item  (item_id),
        INDEX idx_date  (movement_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── supportIssues: active column ──────────────────────────
run($pdo, 'supportIssues.active',
    "ALTER TABLE supportIssues ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1");

// ── v2_nav_overrides ──────────────────────────────────────
run($pdo, 'v2_nav_overrides',
    "CREATE TABLE IF NOT EXISTS v2_nav_overrides (
        nav_id  INT NOT NULL,
        v2_link VARCHAR(255) NOT NULL,
        PRIMARY KEY (nav_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── Done ─────────────────────────────────────────────────
echo "\n=== Migration v2 ===\n";
foreach ($steps as $s) echo $s . "\n";
echo "\nסה\"כ " . count($steps) . " שלבים.\n\n";
