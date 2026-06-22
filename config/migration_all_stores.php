<?php
declare(strict_types=1);
/**
 * migration_all_stores.php
 *
 * מעביר מ-alon_db.stores (active=1) ל-alon_db2:
 * → stores   : סניף באג, נקודת מודן, פנים ב, פנים מודן
 * → contacts : נותן שירות, רכש, תפעול, וכל השאר
 *
 * sNum נשמר רק ל: סניף באג, נקודת מודן
 * פנים ב / פנים מודן → stores ללא store_num
 *
 * הרצה:
 * php config/migration_all_stores.php          ← dry-run
 * php config/migration_all_stores.php --run    ← אמיתי
 *
 * בטוח לריצה חוזרת.
 */

define('ROOT', __DIR__ . '/..');
$cfg    = require ROOT . '/config/config.php';
$dryRun = false;

if ($dryRun) echo "\n⚠️  DRY-RUN — לא נכתב דבר. הוסף --run להרצה אמיתית.\n";

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

echo "\nמקור : {$cfg['db_v1']['name']}.stores\n";
echo "יעד   : {$cfg['db']['name']}.stores + contacts\n\n";

/* ════════════════════════════════════════════════════════
   סיווג sType
   ════════════════════════════════════════════════════════ */

// → stores (עם sNum)
const TO_STORES_WITH_SNUM = ['סניף באג', 'נקודת מודן'];

// → stores (ללא sNum)
const TO_STORES_NO_SNUM   = ['פנים ב', 'פנים מודן'];

// מיפוי sType → type בV2
function mapStoreType(string $t): string {
    return match($t) {
        'סניף באג'    => 'סניף באג',
        'נקודת מודן'  => 'נקודת מודן',
        'פנים ב'      => 'פנים ארגוני',
        'פנים מודן'   => 'פנים ארגוני',
        default        => $t,
    };
}

// → contacts
function mapContactType(string $t): string {
    return match($t) {
        'נותן שירות' => 'נותן שירות',
        'תפעול'      => 'פנים ארגוני',
        'רכש'        => 'אחר',
        default       => 'אחר',
    };
}

/* ════════════════════════════════════════════════════════
   שליפת כל הפעילים מV1
   ════════════════════════════════════════════════════════ */
$all = $v1->query(
    "SELECT * FROM stores WHERE active=1 ORDER BY CAST(sNum AS UNSIGNED) ASC"
)->fetchAll();

echo "נמצאו " . count($all) . " רשומות פעילות.\n\n";

/* ════════════════════════════════════════════════════════
   קיימים ב-V2 (למניעת כפילויות)
   ════════════════════════════════════════════════════════ */

// stores — לפי store_num (רק כשיש)
$existStoreNums = array_flip(
    $v2->query("SELECT store_num FROM stores WHERE store_num IS NOT NULL AND store_num != ''")
    ->fetchAll(PDO::FETCH_COLUMN));

// stores — לפי שם+עיר (לפנים ב/מודן שאין להם sNum)
$existStoreNames = array_flip(
    $v2->query("SELECT CONCAT(name,'|',IFNULL(city,'')) FROM stores")
    ->fetchAll(PDO::FETCH_COLUMN));

// contacts — לפי sNum בתגיות
$existContactTags = $v2->query(
    "SELECT tags FROM contacts WHERE tags LIKE '%sNum:%'"
)->fetchAll(PDO::FETCH_COLUMN);
$existContactSnums = [];
foreach ($existContactTags as $tagStr) {
    foreach (explode(',', $tagStr) as $tag) {
        $tag = trim($tag);
        if (str_starts_with($tag, 'sNum:')) $existContactSnums[substr($tag, 5)] = true;
    }
}

// contacts ללא sNum — לפי שם+מייל
$existContactNames = array_flip(
    $v2->query("SELECT CONCAT(first_name,' ',IFNULL(last_name,''),'|',IFNULL(email,'')) FROM contacts")
    ->fetchAll(PDO::FETCH_COLUMN)
);

echo "קיים ב-stores  : " . count($existStoreNums) . " (לפי store_num)\n";
echo "קיים ב-contacts: " . count($existContactSnums) . " (לפי sNum בתגיות)\n\n";

/* ════════════════════════════════════════════════════════
   Prepared statements
   ════════════════════════════════════════════════════════ */
$stmtStore = $dryRun ? null : $v2->prepare(
    "INSERT INTO stores
        (store_num, name, type, city, address,
         phone_main, phone_cell, manager_name, manager_cell,
         mvoice_queue, telephone_line_num,
         alert_note, note, tags,
         is_active, is_display, created_at, updated_at)
     VALUES (?,?,?,?,?, ?,?,?,?, ?,?, ?,?,?, 1,1,NOW(),NOW())"
);

$stmtContact = $dryRun ? null : $v2->prepare(
    "INSERT INTO contacts
        (first_name, last_name, email, phone, phone2,
         role, department, address, tags, note,
         contact_type, is_active, is_contacts_list,
         created_at, updated_at)
     VALUES (?,?,?,?,?, ?,?,?,?,?, ?,1,0,NOW(),NOW())"
);

/* ════════════════════════════════════════════════════════
   עיבוד
   ════════════════════════════════════════════════════════ */
$stats = ['stores_inserted'=>0,'stores_skipped'=>0,'contacts_inserted'=>0,'contacts_skipped'=>0];
$log   = ['stores'=>[], 'contacts'=>[]];

foreach ($all as $s) {
    $sType = trim($s['sType'] ?? '');
    $sNum  = trim($s['sNum']  ?? '');
    $sName = trim($s['sName'] ?? '');

    if (!$sName) continue;

    /* ── שדות משותפים ── */
    $phoneMain = trim($s['sPhone']   ?? '')
              ?: trim($s['sCell']    ?? '')
              ?: trim($s['sManCell'] ?? '');
    $phoneCell = '';
    if (trim($s['sPhone']??'') && trim($s['sCell']??'')) {
        $phoneCell = trim($s['sCell']);
    } elseif (!trim($s['sPhone']??'') && trim($s['sCell']??'') && trim($s['sManCell']??'')) {
        $phoneCell = trim($s['sManCell']);
    }

    $city    = trim($s['sCity'] ?? '');
    $address = implode(', ', array_filter([trim($s['sAdd']??''), $city]));

    $noteParts = array_filter([
        trim((string)($s['sNote']     ?? '')),
        $s['workHours'] ? "⏰ שעות:\n" . trim($s['workHours']) : '',
        $s['inMyStore'] ? "🏪 ציוד:\n" . trim((string)$s['inMyStore']) : '',
    ]);
    $note = implode("\n\n", $noteParts) ?: null;

    $alertNote = trim($s['alertNote'] ?? '') ?: null;

    /* ════════════════════════════════════════
       → STORES
       ════════════════════════════════════════ */
    if (in_array($sType, TO_STORES_WITH_SNUM) || in_array($sType, TO_STORES_NO_SNUM)) {

        $useStoreNum  = in_array($sType, TO_STORES_WITH_SNUM);
        $storeNumVal  = ($useStoreNum && $sNum !== '') ? $sNum : null;
        $v2Type       = mapStoreType($sType);

        // בדיקת כפילות
        $dupKey = $storeNumVal
            ? (isset($existStoreNums[$storeNumVal]) ? 'store_num' : null)
            : (isset($existStoreNames["{$sName}|{$city}"]) ? 'name+city' : null);

        if ($dupKey) {
            $stats['stores_skipped']++;
            $log['stores'][] = '  – SKIP  [' . $sType . '] [' . ($storeNumVal ?? '—') . '] ' . $sName . ' (כפילות לפי ' . $dupKey . ')';
            continue;
        }

        // tags
        $tagParts = array_filter(array_unique(array_merge(
            $s['tags'] ? array_map('trim', explode(',', $s['tags'])) : [],
            $s['sArea']    ? [trim($s['sArea'])]               : [],
            $s['sAreaMan'] ? ['אמ:' . trim($s['sAreaMan'])]    : [],
        )));
        $tags = $tagParts ? implode(', ', $tagParts) : null;

        $mvoice  = is_numeric($s['mvoiceQueue']     ?? null) ? (int)$s['mvoiceQueue']     : null;
        $telLine = trim($s['telephoneLineNum'] ?? '') ?: null;

        $params = [
            $storeNumVal,
            $sName, $v2Type, $city, $address,
            $phoneMain ?: null, $phoneCell ?: null,
            trim($s['sManName'] ?? '') ?: null,
            trim($s['sManCell'] ?? '') ?: null,
            $mvoice, $telLine,
            $alertNote, $note, $tags,
        ];

        if ($dryRun) {
            $log['stores'][] = sprintf(
                "  + [%s] %-5s %-30s | %s | %s | queue:%s",
                $sType, $storeNumVal??'—', $sName, $city, $phoneMain??'—', $mvoice??'—'
            );
        } else {
            try {
                $stmtStore->execute($params);
                $log['stores'][] = "  ✓ [{$sType}] [" . ($storeNumVal ?? '—') . "] {$sName}";
            } catch (PDOException $e) {
                $log['stores'][] = "  ✗ [{$sType}] {$sName}: " . $e->getMessage();
            }
        }

        $stats['stores_inserted']++;
        if ($storeNumVal) $existStoreNums[$storeNumVal] = true;
        $existStoreNames["{$sName}|{$city}"] = true;

    /* ════════════════════════════════════════
       → CONTACTS
       ════════════════════════════════════════ */
    } else {

        $contactType = mapContactType($sType);

        // בדיקת כפילות — לפי sNum בtags (אם יש) או שם+מייל
        $email = trim($s['sMail'] ?? '');
        if ($sNum && isset($existContactSnums[$sNum])) {
            $stats['contacts_skipped']++;
            $log['contacts'][] = "  – SKIP  [{$sType}] [{$sNum}] {$sName} (sNum קיים בtags)";
            continue;
        }
        $nameEmailKey = "{$sName}|{$email}";
        if (!$sNum && isset($existContactNames[$nameEmailKey])) {
            $stats['contacts_skipped']++;
            $log['contacts'][] = "  – SKIP  [{$sType}] {$sName} (שם+מייל קיים)";
            continue;
        }

        // tags
        $tagParts = [];
        if ($sNum)          $tagParts[] = "sNum:{$sNum}";
        if ($s['tags'])     array_push($tagParts, ...array_map('trim', explode(',', $s['tags'])));
        if ($s['sArea'])    $tagParts[] = trim($s['sArea']);
        if ($s['sAreaMan']) $tagParts[] = 'אמ:' . trim($s['sAreaMan']);
        $tagParts = array_filter(array_unique($tagParts));
        $tags = $tagParts ? implode(', ', $tagParts) : null;

        $params = [
            $sName,                               // first_name
            trim($s['sManName'] ?? '') ?: null,   // last_name (מנהל)
            $email ?: null,
            $phoneMain ?: null,
            $phoneCell ?: null,
            trim($s['sType'] ?? '') ?: null,      // role
            trim($s['sArea'] ?? '') ?: null,      // department
            $address ?: null,
            $tags,
            $note,
            $contactType,
        ];

        if ($dryRun) {
            $log['contacts'][] = sprintf(
                "  + [%s] %-30s | %s | %s",
                $sType, $sName, $email??'—', $phoneMain??'—'
            );
        } else {
            try {
                $stmtContact->execute($params);
                $log['contacts'][] = "  ✓ [{$sType}] {$sName}";
            } catch (PDOException $e) {
                $log['contacts'][] = "  ✗ [{$sType}] {$sName}: " . $e->getMessage();
            }
        }

        $stats['contacts_inserted']++;
        if ($sNum) $existContactSnums[$sNum] = true;
        $existContactNames[$nameEmailKey] = true;
    }
}

/* ════════════════════════════════════════════════════════
   פלט
   ════════════════════════════════════════════════════════ */
echo "─── stores (" . ($dryRun?'יוכנסו':'הוכנסו') . ": {$stats['stores_inserted']}, דולגו: {$stats['stores_skipped']}) ───\n";
foreach ($log['stores'] as $l) echo $l . "\n";

echo "\n─── contacts (" . ($dryRun?'יוכנסו':'הוכנסו') . ": {$stats['contacts_inserted']}, דולגו: {$stats['contacts_skipped']}) ───\n";
foreach ($log['contacts'] as $l) echo $l . "\n";

echo "\n";
echo "stores  → " . ($dryRun?'יוכנסו':'הוכנסו') . ": {$stats['stores_inserted']}, דולגו: {$stats['stores_skipped']}\n";
echo "contacts→ " . ($dryRun?'יוכנסו':'הוכנסו') . ": {$stats['contacts_inserted']}, דולגו: {$stats['contacts_skipped']}\n";

if ($dryRun) {
    echo "\n✅ Dry-run הסתיים. להרצה אמיתית:\n";
    echo "   php config/migration_all_stores.php --run\n\n";
} else {
    echo "\n✅ הסתיים!\n\n";
}