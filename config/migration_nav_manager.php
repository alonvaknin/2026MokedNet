<?php
declare(strict_types=1);
/**
 * migration_nav_manager.php
 * יוצר טבלאות ניהול navbar ב-alon_db2
 * הרץ פעם אחת: php config/migration_nav_manager.php
 */

define('ROOT', __DIR__ . '/..');
$cfg = require ROOT . '/config/config.php';

// מחבר ל-alon_db2
$db = $cfg['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname=alon_db2;charset=utf8mb4",
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
        $steps[] = str_contains($e->getMessage(), 'already exists')
            ? "– $desc (כבר קיים)"
            : "✗ $desc: " . $e->getMessage();
    }
}

// ── nav_items ─────────────────────────────────────────────────
// מחסן את כל פריטי הניווט (v2 — מחליף navBar של v1)
run($pdo, 'nav_items', "CREATE TABLE IF NOT EXISTS nav_items (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label_he      VARCHAR(60)  NOT NULL,
    icon          VARCHAR(80)  NULL,
    link          VARCHAR(255) NULL,
    link_type     ENUM('route','external','parent') NOT NULL DEFAULT 'route',
    parent_id     INT UNSIGNED NULL,
    ordering      INT          NOT NULL DEFAULT 0,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    open_in_blank TINYINT(1)   NOT NULL DEFAULT 0,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parent (parent_id),
    INDEX idx_order  (ordering)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── nav_permissions ───────────────────────────────────────────
// קשר בין פריט navbar לקבוצת הרשאה
// אם אין שורה לפריט → גלוי לכולם
// אם יש שורות → גלוי רק לקבוצות הרשומות
run($pdo, 'nav_permissions', "CREATE TABLE IF NOT EXISTS nav_permissions (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nav_item_id      INT UNSIGNED NOT NULL,
    perm_group_id    INT          NOT NULL,
    INDEX idx_item  (nav_item_id),
    INDEX idx_group (perm_group_id),
    UNIQUE KEY uq_item_group (nav_item_id, perm_group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "\n=== Migration NavManager ===\n";
foreach ($steps as $s) echo $s . "\n";
echo "\nסה\"כ " . count($steps) . " שלבים.\n\n";
