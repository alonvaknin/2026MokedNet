<?php
declare(strict_types=1);
/**
 * migration_modan_emails.php
 *
 * מוסיף עמודת email לטבלת stores בalon_db2 (אם לא קיימת),
 * ומעתיק מיילים של נקודות מודן בלבד מalon_db.stores → alon_db2.stores
 * בהתאמה לפי store_num.
 *
 * הרצה:
 *   php config/migration_modan_emails.php          ← dry-run
 *   php config/migration_modan_emails.php --run    ← אמיתי
 */

define('ROOT', __DIR__ . '/..');
$cfg    = require ROOT . '/config/config.php';
$dryRun = false;

if ($dryRun) echo "\n⚠️  DRY-RUN — לא נכתב דבר. הוסף --run להרצה אמיתית.\n\n";

/* ── חיבורים ── */
$v1 = new PDO(
    "mysql:host={$cfg['db_v1']['host']};port={$cfg['db_v1']['port']};dbname={$cfg['db_v1']['name']};charset=utf8mb4",
    $cfg['db_v1']['user'], $cfg['db_v1']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$v2 = new PDO(
    "mysql:host={$cfg['db']['host']};port={$cfg['db']['port']};dbname={$cfg['db']['name']};charset=utf8mb4",
    $cfg['db']['user'], $cfg['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

echo "מקור : {$cfg['db_v1']['name']}.stores (sType='נקודת מודן')\n";
echo "יעד   : {$cfg['db']['name']}.stores.email\n\n";

/* ── הוספת עמודת email אם לא קיימת ── */
if (!$dryRun) {
    try {
        $v2->exec("ALTER TABLE stores ADD COLUMN email VARCHAR(255) NULL DEFAULT NULL AFTER phone_cell");
        echo "✓ נוספה עמודת email לטבלת stores\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column') || str_contains($e->getMessage(), 'already exists')) {
            echo "– עמודת email כבר קיימת\n";
        } else {
            throw $e;
        }
    }
} else {
    echo "DRY-RUN: יתווסף ALTER TABLE stores ADD COLUMN email (אם לא קיים)\n";
}

/* ── שליפת נקודות מודן עם מייל מV1 ── */
$modan = $v1->query(
    "SELECT sNum, sName, sMail FROM stores
      WHERE active=1 AND sType='נקודת מודן' AND sMail IS NOT NULL AND sMail != ''
      ORDER BY CAST(sNum AS UNSIGNED) ASC"
)->fetchAll();

echo "\nנמצאו " . count($modan) . " נקודות מודן עם מייל ב-{$cfg['db_v1']['name']}.\n\n";

/* ── עדכון ── */
$stmt = $dryRun ? null : $v2->prepare(
    "UPDATE stores SET email=? WHERE store_num=? AND type='נקודת מודן'"
);

$updated  = 0;
$notFound = 0;
$log      = [];

foreach ($modan as $row) {
    $sNum  = trim($row['sNum']  ?? '');
    $sName = trim($row['sName'] ?? '');
    $email = trim($row['sMail'] ?? '');

    if (!$sNum || !$email) continue;

    if ($dryRun) {
        // בדיקה אם קיים בV2
        $exists = $v2->prepare("SELECT 1 FROM stores WHERE store_num=? AND type='נקודת מודן'");
        $exists->execute([$sNum]);
        if ($exists->fetchColumn()) {
            $log[] = "  + [{$sNum}] {$sName} → {$email}";
            $updated++;
        } else {
            $log[] = "  ? [{$sNum}] {$sName} — לא נמצא בV2";
            $notFound++;
        }
    } else {
        $stmt->execute([$email, $sNum]);
        if ($stmt->rowCount() > 0) {
            $log[] = "  ✓ [{$sNum}] {$sName} → {$email}";
            $updated++;
        } else {
            $log[] = "  ? [{$sNum}] {$sName} — לא נמצא ב-{$cfg['db']['name']}.stores";
            $notFound++;
        }
    }
}

/* ── פלט ── */
foreach ($log as $l) echo $l . "\n";

echo "\n";
echo ($dryRun ? "יעודכנו" : "עודכנו") . ": {$updated}\n";
echo "לא נמצאו בV2: {$notFound}\n";

if ($dryRun) {
    echo "\n✅ Dry-run הסתיים. להרצה אמיתית:\n";
    echo "   php config/migration_modan_emails.php --run\n\n";
} else {
    echo "\n✅ הסתיים!\n\n";
}
