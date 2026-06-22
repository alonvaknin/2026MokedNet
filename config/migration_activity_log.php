<?php
declare(strict_types=1);
/**
 * migration_activity_log.php
 *
 * יוצר טבלת activity_log מלאה ב-alon_db2
 * (אם קיימת כבר — מוסיף עמודות חסרות בלבד)
 *
 * php config/migration_activity_log.php
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

// ══ יצירת הטבלה ══════════════════════════════════════════════
run($pdo, 'activity_log — CREATE TABLE', "
CREATE TABLE IF NOT EXISTS activity_log (
    id            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,

    -- מי
    user_id       INT              NULL,                        -- NULL = אנונימי / מערכת
    user_name     VARCHAR(120)     NULL,                        -- snapshot של שם בזמן הפעולה
    ip            VARCHAR(45)      NOT NULL DEFAULT '',         -- IPv4 / IPv6
    user_agent    VARCHAR(512)     NULL,

    -- מה
    action        VARCHAR(80)      NOT NULL,                    -- 'store.update', 'contact.create', ...
    entity_type   VARCHAR(60)      NULL,                        -- 'store', 'contact', 'user', ...
    entity_id     INT UNSIGNED     NULL,                        -- id של הרשומה המושפעת
    entity_label  VARCHAR(200)     NULL,                        -- תיאור קריא: 'סניף תל אביב #42'

    -- שינוי ערכים
    field_name    VARCHAR(100)     NULL,                        -- שם השדה שהשתנה (או NULL לפעולה כוללת)
    old_value     TEXT             NULL,                        -- ערך לפני
    new_value     TEXT             NULL,                        -- ערך אחרי
    diff_json     JSON             NULL,                        -- diff מלא: {field:[old,new],...}

    -- context
    detail        TEXT             NULL,                        -- טקסט חופשי / reason
    request_url   VARCHAR(512)     NULL,                        -- ה-URL שהופעל
    request_method VARCHAR(10)     NULL,                        -- GET/POST
    session_id    VARCHAR(64)      NULL,                        -- לזיהוי session

    -- תזמון
    created_at    DATETIME(3)      NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    -- indexes
    INDEX idx_log_user    (user_id, created_at),
    INDEX idx_log_action  (action, created_at),
    INDEX idx_log_entity  (entity_type, entity_id),
    INDEX idx_log_created (created_at),
    INDEX idx_log_ip      (ip)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
");

// ══ עמודות שעלולות להיות חסרות בטבלה ישנה ══════════════════
run($pdo, 'activity_log.user_name',     "ALTER TABLE activity_log ADD COLUMN user_name    VARCHAR(120) NULL AFTER user_id");
run($pdo, 'activity_log.user_agent',    "ALTER TABLE activity_log ADD COLUMN user_agent   VARCHAR(512) NULL AFTER ip");
run($pdo, 'activity_log.entity_type',   "ALTER TABLE activity_log ADD COLUMN entity_type  VARCHAR(60)  NULL AFTER action");
run($pdo, 'activity_log.entity_id',     "ALTER TABLE activity_log ADD COLUMN entity_id    INT UNSIGNED NULL AFTER entity_type");
run($pdo, 'activity_log.entity_label',  "ALTER TABLE activity_log ADD COLUMN entity_label VARCHAR(200) NULL AFTER entity_id");
run($pdo, 'activity_log.field_name',    "ALTER TABLE activity_log ADD COLUMN field_name   VARCHAR(100) NULL AFTER entity_label");
run($pdo, 'activity_log.old_value',     "ALTER TABLE activity_log ADD COLUMN old_value    TEXT         NULL AFTER field_name");
run($pdo, 'activity_log.new_value',     "ALTER TABLE activity_log ADD COLUMN new_value    TEXT         NULL AFTER old_value");
run($pdo, 'activity_log.diff_json',     "ALTER TABLE activity_log ADD COLUMN diff_json    JSON         NULL AFTER new_value");
run($pdo, 'activity_log.request_url',   "ALTER TABLE activity_log ADD COLUMN request_url  VARCHAR(512) NULL AFTER detail");
run($pdo, 'activity_log.request_method',"ALTER TABLE activity_log ADD COLUMN request_method VARCHAR(10) NULL AFTER request_url");
run($pdo, 'activity_log.session_id',    "ALTER TABLE activity_log ADD COLUMN session_id   VARCHAR(64)  NULL AFTER request_method");
run($pdo, 'activity_log — idx_entity',  "ALTER TABLE activity_log ADD INDEX idx_log_entity (entity_type, entity_id)");
run($pdo, 'activity_log — idx_ip',      "ALTER TABLE activity_log ADD INDEX idx_log_ip (ip)");

echo "\n=== Migration: activity_log @ {$db['name']} ===\n";
foreach ($steps as $s) echo $s . "\n";
echo "\nסה\"כ " . count($steps) . " שלבים.\n\n";
