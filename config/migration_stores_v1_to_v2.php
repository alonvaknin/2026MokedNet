<?php
declare(strict_types=1);
/**
 * migration_stores_v1_to_v2.php
 *
 * מעביר חנויות פעילות (active=1) מ-alon_db.stores
 * אל alon_db2.stores (מבנה V2)
 *
 * הרצה:
 *   php config/migration_stores_v1_to_v2.php          ← dry-run (בדיקה בלבד)
 *   php config/migration_stores_v1_to_v2.php --run    ← הרצה אמיתית
 *
 * בטוח לריצה חוזרת — מזהה כפילויות לפי store_num.
 */

define('ROOT', __DIR__ . '/..');
$cfg    = require ROOT . '/config/config.php';
$dryRun = !in_array('--run', $argv ?? []);

if ($dryRun) {
    echo "\n⚠️  DRY-RUN — לא נכתב דבר ל-DB. הוסף --run להרצה אמיתית.\n\n";
}

/* ── חיבורים ── */
$v1cfg = $cfg['db_v1'];  // alon_db  (מקור)
$v2cfg = $cfg['db'];     // alon_db2 (יעד)

$v1 = new PDO(
    "mysql:host={$v1cfg['host']};port={$v1cfg['port']};dbname={$v1cfg['name']};charset=utf8mb4",
    $v1cfg['user'], $v1cfg['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$v2 = new PDO(
    "mysql:host={$v2cfg['host']};port={$v2cfg['port']};dbname={$v2cfg['name']};charset=utf8mb4",
    $v2cfg['user'], $v2cfg['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

echo "מקור : {$v1cfg['name']}.stores\n";
echo "יעד   : {$v2cfg['name']}.stores\n\n";

/* ── שליפת חנויות פעילות מ-V1 ── */
$v1Stores = $v1->query(
    "SELECT * FROM stores WHERE active = 1 ORDER BY CAST(sNum AS UNSIGNED) ASC"
)->fetchAll();

echo "נמצאו " . count($v1Stores) . " חנויות פעילות ב-{$v1cfg['name']}.\n";

/* ── store_nums קיימים ב-V2 ── */
$existing = $v2->query(
    "SELECT store_num FROM stores"
)->fetchAll(PDO::FETCH_COLUMN);
$existingNums = array_flip($existing);

echo "כבר קיימים ב-{$v2cfg['name']}.stores: " . count($existingNums) . " רשומות.\n\n";

/* ── סטטיסטיקות לפי סוג ── */
$byType = [];
foreach ($v1Stores as $s) {
    $t = trim($s['sType'] ?? 'לא מוגדר') ?: 'לא מוגדר';
    $byType[$t] = ($byType[$t] ?? 0) + 1;
}
echo "התפלגות לפי סוג (sType):\n";
foreach ($byType as $t => $cnt) echo "  {$t}: {$cnt}\n";
echo "\n";

/* ── מיפוי sType → type בV2 ── */
function mapType(string $sType): string {
    return match(strtolower(trim($sType))) {
        'bug', 'סניף', 'סניף באג', 'bug store' => 'סניף באג',
        'modan', 'מודן', 'נקודת מודן', 'modan store' => 'נקודת מודן',
        default => trim($sType) ?: 'סניף באג',
    };
}

/* ── INSERT ── */
$insertSQL = "INSERT INTO stores
    (store_num, name, type, city, address,
     phone_main, phone_cell, manager_name, manager_cell,
     mvoice_queue, telephone_line_num,
     alert_note, note, tags,
     is_active, is_display,
     created_at, updated_at)
VALUES
    (?, ?, ?, ?, ?,
     ?, ?, ?, ?,
     ?, ?,
     ?, ?, ?,
     1, 1,
     NOW(), NOW())";

$stmt = $dryRun ? null : $v2->prepare($insertSQL);

$inserted = 0;
$skipped  = 0;
$log      = [];

foreach ($v1Stores as $s) {
    $sNum = trim($s['sNum'] ?? '');

    /* דילוג אם קיים */
    if (isset($existingNums[$sNum])) {
        $skipped++;
        $log[] = "  – SKIP  [{$sNum}] " . trim($s['sName'] ?? '') . " (כבר קיים)";
        continue;
    }

    /* שם */
    $name = trim($s['sName'] ?? '');
    if (!$name) {
        $log[] = "  ✗ SKIP  [{$sNum}] ללא שם — מדולג";
        $skipped++;
        continue;
    }

    /* טלפון */
    $phoneMain = trim($s['sPhone']   ?? '')
              ?: trim($s['sCell']    ?? '')
              ?: trim($s['sManCell'] ?? '');
    $phoneCell = '';
    if (trim($s['sPhone'] ?? '') && trim($s['sCell'] ?? '')) {
        $phoneCell = trim($s['sCell']);
    } elseif (!trim($s['sPhone'] ?? '') && trim($s['sCell'] ?? '') && trim($s['sManCell'] ?? '')) {
        $phoneCell = trim($s['sManCell']);
    }

    /* כתובת */
    $addrParts = array_filter([trim($s['sAdd'] ?? ''), trim($s['sCity'] ?? '')]);
    $address   = implode(', ', $addrParts);
    $city      = trim($s['sCity'] ?? '');

    /* מנהל */
    $managerName = trim($s['sManName'] ?? '');
    $managerCell = trim($s['sManCell'] ?? '');

    /* mvoice */
    $mvoiceQueue       = is_numeric($s['mvoiceQueue']) ? (int)$s['mvoiceQueue'] : null;
    $telephoneLineNum  = trim($s['telephoneLineNum'] ?? '') ?: null;

    /* הערות */
    $alertNote = trim($s['alertNote'] ?? '') ?: null;

    $noteParts = [];
    if ($s['sNote'])     $noteParts[] = trim((string)$s['sNote']);
    if ($s['workHours']) $noteParts[] = "⏰ שעות פעילות:\n" . trim($s['workHours']);
    if ($s['inMyStore']) $noteParts[] = "🏪 ציוד בסניף:\n" . trim((string)$s['inMyStore']);
    $note = implode("\n\n", array_filter($noteParts)) ?: null;

    /* tags: מהV1 + אזור */
    $tagParts = [];
    if ($s['tags'])  $tagParts = array_map('trim', explode(',', $s['tags']));
    if ($s['sArea']) $tagParts[] = trim($s['sArea']);
    if ($s['sAreaMan']) $tagParts[] = "אמ:" . trim($s['sAreaMan']);
    $tagParts = array_filter(array_unique($tagParts));
    $tags = $tagParts ? implode(', ', $tagParts) : null;

    /* סוג */
    $type = mapType($s['sType'] ?? '');

    $params = [
        $sNum, $name, $type, $city, $address,
        $phoneMain ?: null, $phoneCell ?: null, $managerName ?: null, $managerCell ?: null,
        $mvoiceQueue, $telephoneLineNum,
        $alertNote, $note, $tags,
    ];

    if ($dryRun) {
        $log[] = sprintf(
            "  + [%s] %-30s | %s | %s | %s | queue:%s",
            str_pad($sNum, 5),
            $name,
            $type,
            $city ?: '—',
            $phoneMain ?: '—',
            $mvoiceQueue ?? '—'
        );
    } else {
        try {
            $stmt->execute($params);
            $log[] = "  ✓ [{$sNum}] {$name}";
        } catch (PDOException $e) {
            $log[] = "  ✗ [{$sNum}] {$name}: " . $e->getMessage();
        }
    }

    $inserted++;
    $existingNums[$sNum] = true;
}

/* ── פלט ── */
echo ($dryRun ? "🔍 יבוצעו הפעולות הבאות:\n" : "📋 תוצאות:\n");
foreach ($log as $line) echo $line . "\n";

echo "\n";
echo ($dryRun ? "יוכנסו" : "הוכנסו") . ": {$inserted}\n";
echo "דולגו (כבר קיים / ללא שם): {$skipped}\n";

if ($dryRun) {
    echo "\n✅ Dry-run הסתיים. להרצה אמיתית:\n";
    echo "   php config/migration_stores_v1_to_v2.php --run\n\n";
} else {
    echo "\n✅ הסתיים בהצלחה!\n\n";
}
