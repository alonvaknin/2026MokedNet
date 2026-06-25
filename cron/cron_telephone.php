<?php
// רץ כל שעה — בדיקת קווי טלפון בחנויות מול mvoice
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\DB;

$pdo    = DB::v1(); // alon_db — mvoice_telephoneline_fail, stores, logger
$pdoLog = DB::get(); // alon_db2 — cron_log

$debug = isset($_GET['debug']);

$raw  = fetchPhoneLines();
$json = json_decode($raw, true);

$statusReq = $json['responses'][0];

if ((int)$statusReq['code'] !== 200) {
    if ($debug) { die("שגיאת API: קוד {$statusReq['code']}"); }
    logEntry($pdo, 'mvoice-server-error');
    cronLog($pdoLog, 'run', 'error', "mvoice החזיר קוד {$statusReq['code']}");
    exit;
}

$phones = $json['data'];
if (empty($phones)) {
    if ($debug) { die("לא התקבלו קווי טלפון מה-API"); }
    logEntry($pdo, 'no-phones-in-response');
    cronLog($pdoLog, 'run', 'error', 'לא התקבלו קווי טלפון מה-API');
    exit;
}

$failLines  = [];
$mailErrors = 0;
foreach ($phones as $phone) {
    if ((int)$phone['registered'] === 0 && (int)$phone['expect_registered'] === 1) {
        $line  = $phone['name'];
        $store = getStoreByLine($pdo, $line);

        $sMail   = $store['sMail'] ?? '';
        $sNum    = $store['sNum']  ?? 0;
        $desc    = $phone['description'];
        $subject = '[מוקד-נט] שלוחת טלפון - ' . $desc . ' לא מחוברת';

        if ($debug) {
            $failLines[] = "<b>{$desc}</b> ({$line})\nחנות: " . ($sNum ?: 'לא נמצא') . " | מייל: " . ($sMail ?: '—');
            continue;
        }

        insertFailLog($pdo, $line, (string) $sNum);
        $sent     = sendAlertMail($sMail . ';gild@bug.co.il', $subject);
        logEntry($pdo, $sent ? 'send-mail-to-STORE' : 'mailer-fail', "store:|{$desc}|telephoneline:|{$line}|mail:|{$sMail}");
        $failLines[] = $desc;
        if (!$sent) $mailErrors++;
    }
}

if ($debug) {
    if (empty($failLines)) {
        echo '<pre>✓ כל הקווים תקינים</pre>';
    } else {
        echo '<pre style="font-family:monospace;line-height:1.8;direction:rtl;text-align:right;">';
        echo count($failLines) . " קווים כשלו:\n\n";
        echo implode("\n\n", $failLines);
        echo '</pre>';
    }
    exit;
}

if (!empty($failLines)) {
    $details = "קווים כשלו: " . implode(', ', $failLines);
    if ($mailErrors > 0) $details .= " | שגיאות שליחה: {$mailErrors}";
    cronLog($pdoLog, 'run', $mailErrors > 0 ? 'error' : 'ok', $details);
}

// ── API ───────────────────────────────────────────────────────────────────────

function fetchPhoneLines(): string
{
    $user = CFG['mvoice']['user'];
    $pass = CFG['mvoice']['pass'];
    return file_get_contents(
        'https://app.mvoice.co.il/api/json/phones/list/panel/?'
        . http_build_query(['auth_username' => $user, 'auth_password' => $pass])
    );
}

// ── DB ────────────────────────────────────────────────────────────────────────

function getStoreByLine(PDO $pdo, string $line): array
{
    $stmt = $pdo->prepare(
        "SELECT sMail, sNum FROM stores
         WHERE sType = 'סניף באג' AND FIND_IN_SET(?, telephoneLineNum) AND active = 1 LIMIT 1"
    );
    $stmt->execute([$line]);
    return $stmt->fetch() ?: [];
}

function insertFailLog(PDO $pdo, string $line, string $storeNum): void
{
    $pdo->prepare(
        'INSERT INTO mvoice_telephoneline_fail (telephoneline_number, store_num) VALUES (?, ?)'
    )->execute([$line, $storeNum]);
}

function cronLog(PDO $pdo, string $action, string $status = 'ok', string $details = ''): void
{
    $pdo->prepare(
        "INSERT INTO cron_log (cron_name, action, status, details) VALUES ('cron_telephone', ?, ?, ?)"
    )->execute([$action, $status, $details]);
}

function logEntry(PDO $pdo, string $action, string $value = ''): void
{
    $pdo->prepare(
        "INSERT INTO logger (userId, logWhereChange, logAction, logValue, ipaddress)
         VALUES (-1, 'CRON-chk-telephoneline', ?, ?, 'cron')"
    )->execute([$action, $value]);
}

// ── מייל ─────────────────────────────────────────────────────────────────────

function sendAlertMail(string $to, string $subject): bool
{
    $message  = '<html lang="HE" style="font-family:Tahoma,Arial;" dir="rtl"><head></head><body style="text-align:right;direction:rtl;">';
    $message .= "<p><span style='font-size:15pt;'>שלוחת הטלפון בחנות לא מצליחה להתחבר למרכזייה</span></p>";
    $message .= '<p>לוודא אם שלושת הנוריות פועלות באופן קבוע על גבי ה-GATEWAY כמתואר בתמונה.<br>'
        . '<span style="color:#e67e22"><strong>אם הנוריות דלוקות קבוע</strong></span> — התקלה ככל הנראה בציוד/תשתית האינטרנט, נא לדבר עם ולדימיר.</p>'
        . '<p><strong><span style="color:#e74c3c">אם אחת הנוריות אינה דלוקה באופן קבוע</span></strong> — יש לנתק את ה-GATEWAY מהחשמל ולהחזיר לאחר 15 שניות. להמתין 2 דקות ולנסות להוציא/לקבל. אם עדיין אין קו נא להתקשר אל גיל דגון.</p>';
    $message .= '<p><img src="https://i.imgur.com/co1fjRx.png" height="150" alt="gateway"></p>';
    $message .= "<p><b><span style='font-size:16pt;color:orange;'>אם הנ\"ל לא עובד — נא להתקשר אל גיל דגון 054-4744758</span></b></p>";
    $message .= '<p><span style="color:#999;">מופעל באמצעות מערכת מוקד-נט</span></p>';
    $message .= '</body></html>';

    $headers  = "From: מוקד-נט <moked-net-noreply@alexisdeveloping.com>\r\n";
    $headers .= "Reply-To: moked-net-noreply@alexisdeveloping.com\r\n";
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

    return mail($to, $subject, $message, $headers);
}
