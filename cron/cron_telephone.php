<?php
// רץ כל שעה — בדיקת קווי טלפון בחנויות מול mvoice
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\DB;

$pdo = DB::v1(); // alon_db — mvoice_telephoneline_fail, stores, logger

$json      = json_decode(fetchPhoneLines(), true);
$statusReq = $json['responses'][0];

if ($statusReq['code'] !== 200) {
    logEntry($pdo, 'mvoice-server-error');
    exit;
}

$phones = $json['data'];
if (empty($phones)) {
    logEntry($pdo, 'no-phones-in-response');
    exit;
}

foreach ($phones as $phone) {
    if ($phone['registered'] === 0 && $phone['expect_registered'] === 1) {
        $line  = $phone['name'];
        $store = getStoreByLine($pdo, $line);

        $sMail = $store['sMail'] ?? '';
        $sNum  = $store['sNum']  ?? 0;
        $desc  = $phone['description'];
        $subject = '[מוקד-נט] שלוחת טלפון - ' . $desc . ' לא מחוברת';

        insertFailLog($pdo, $line, (string) $sNum);

        $sent = sendAlertMail($sMail . ';gild@bug.co.il', $subject);
        $logValue = "store:|{$desc}|telephoneline:|{$line}|mail:|{$sMail}";
        logEntry($pdo, $sent ? 'send-mail-to-STORE' : 'mailer-fail', $logValue);
    }
}

// ── API ───────────────────────────────────────────────────────────────────────

function fetchPhoneLines(): string
{
    return file_get_contents(
        'https://app.mvoice.co.il/api/json/phones/list/panel/?auth_username=alonv@bug.co.il&auth_password=WFUN3CL6MM'
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
