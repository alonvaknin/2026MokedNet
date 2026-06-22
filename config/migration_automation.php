<?php
declare(strict_types=1);
/**
 * migration_automation.php — הרץ פעם אחת מה-CLI:
 *   php config/migration_automation.php
 *
 * עובד על alon_db (אותו DB כמו V1, מוגדר ב-local.php).
 * הטבלה CronJob כבר קיימת — הסקריפט רק מוסיף עמודות
 * ו-indexes חסרים. בטוח לריצה חוזרת.
 */

define('ROOT', __DIR__ . '/..');
$cfg = require ROOT . '/config/config.php';

// migration רץ על alon_db (ה-DB של V1) — לא על alon_db2
$db  = $cfg['db_v1'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}",
    $db['user'], $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "\nמתחבר ל: {$db['name']}\n";

$steps = [];

function run(PDO $pdo, string $desc, string $sql): void {
    global $steps;
    try {
        $pdo->exec($sql);
        $steps[] = "✓ $desc";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (
            str_contains($msg, 'Duplicate column')  ||
            str_contains($msg, 'already exists')     ||
            str_contains($msg, 'Duplicate key name')
        ) {
            $steps[] = "– $desc (כבר קיים)";
        } else {
            $steps[] = "✗ $desc: " . $msg;
        }
    }
}

// ══════════════════════════════════════════════════════════════
// 1. ADD COLUMN
//    עמודות שנוספו בהמשך הפיתוח של V1 ועלולות להיות חסרות.
//    אם כבר קיימות — שגיאת Duplicate column נתפסת בשקט.
// ══════════════════════════════════════════════════════════════

run($pdo, 'CronJob.runevenvalueOfTypeDiff',
    "ALTER TABLE CronJob
     ADD COLUMN runevenvalueOfTypeDiff TINYINT(1) NOT NULL DEFAULT 0
     AFTER statusOfJob");

run($pdo, 'CronJob.currentSaveValue',
    "ALTER TABLE CronJob
     ADD COLUMN currentSaveValue VARCHAR(64) NULL
     AFTER runevenvalueOfTypeDiff");

run($pdo, 'CronJob.countJob',
    "ALTER TABLE CronJob
     ADD COLUMN countJob SMALLINT UNSIGNED NOT NULL DEFAULT 0
     AFTER maxRun");

run($pdo, 'CronJob.statusChangeTime',
    "ALTER TABLE CronJob
     ADD COLUMN statusChangeTime DATETIME NULL
     AFTER countJob");

// ══════════════════════════════════════════════════════════════
// 2. INDEXES
//    שמות עם prefix idx_cj_ כדי למנוע התנגשות עם indexes ישנים.
// ══════════════════════════════════════════════════════════════

run($pdo, 'CronJob — index userID',
    "ALTER TABLE CronJob ADD INDEX idx_cj_user   (userID)");

run($pdo, 'CronJob — index isactive',
    "ALTER TABLE CronJob ADD INDEX idx_cj_active (isactive)");

run($pdo, 'CronJob — index typeOfJob',
    "ALTER TABLE CronJob ADD INDEX idx_cj_type   (typeOfJob)");

run($pdo, 'CronJob — index UptoDate',
    "ALTER TABLE CronJob ADD INDEX idx_cj_upto   (UptoDate)");

// ══════════════════════════════════════════════════════════════
echo "\n=== Migration: Automation (CronJob) @ {$db['name']} ===\n";
foreach ($steps as $s) echo $s . "\n";
echo "\nסה\"כ " . count($steps) . " שלבים.\n\n";
