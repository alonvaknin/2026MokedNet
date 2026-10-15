<?php
// רץ כל שעה — בדיקת קווי טלפון בחנויות מול mvoice
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\DB;
use Core\MailTemplate as Mail;

$debug = isset($_GET['debug']);

$raw  = fetchPhoneLines();
$json = json_decode($raw, true);

$statusReq = $json['responses'][0];

if ((int)$statusReq['code'] !== 200) {
    if ($debug) { die("שגיאת API: קוד " . htmlspecialchars((string)$statusReq['code'], ENT_QUOTES, 'UTF-8')); }
    cronLog('run', 'error', "mvoice החזיר קוד {$statusReq['code']}");
    exit;
}

$phones = $json['data'];
if (empty($phones)) {
    if ($debug) { die("לא התקבלו קווי טלפון מה-API"); }
    cronLog('run', 'error', 'לא התקבלו קווי טלפון מה-API');
    exit;
}

$failLines    = [];
$mailErrors   = 0;
$unresolved   = [];
foreach ($phones as $phone) {
    if ((int)$phone['registered'] === 0 && (int)$phone['expect_registered'] === 1) {
        $line  = $phone['name'];
        $store = getStoreByLine($line);

        $sMail   = $store['email']     ?? '';
        $sNum    = $store['store_num'] ?? 0;
        $desc    = preg_replace('/[\r\n\0]/', '', $phone['description'] ?? '');
        $subject = '[מוקד-נט] שלוחת טלפון - ' . $desc . ' לא מחוברת';
        $subject = preg_replace('/[\r\n\0]/', '', $subject);

        if ($sMail === '') {
            $unresolved[] = ['line' => $line, 'desc' => $desc, 'store_num' => $sNum];
            if (!$debug) {
                continue;
            }
        }

        $recipients = array_filter([$sMail]);

        if ($debug) {
            error_clear_last();
            $sent = sendAlertMail(implode(',', $recipients), $subject);
            $err  = error_get_last();
            $failLines[] =
                "<b>" . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . "</b> (" . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . ")\n"
                . "חנות: " . ($sNum ?: 'לא נמצא') . "\n"
                . "מייל יעד: '" . htmlspecialchars(implode(',', $recipients) ?: '—', ENT_QUOTES, 'UTF-8') . "'\n"
                . "mail() החזיר: " . ($sent ? 'true (נשלח)' : 'false (נכשל)') . "\n"
                . "PHP error_get_last: " . htmlspecialchars($err['message'] ?? 'אין', ENT_QUOTES, 'UTF-8') . "\n"
                . "sendmail_from php.ini: " . htmlspecialchars(ini_get('sendmail_from') ?: '—', ENT_QUOTES, 'UTF-8') . "\n"
                . "sendmail_path php.ini: " . htmlspecialchars(ini_get('sendmail_path') ?: '—', ENT_QUOTES, 'UTF-8');
            continue;
        }

        $sent = sendAlertMail(implode(',', $recipients), $subject);
        $failLines[] = $desc;
        if (!$sent) {
            $mailErrors++;
            $err = error_get_last();
            cronLog('mail', 'error', "שליחה נכשלה | תיאור: {$desc} | חנות: " . ($sNum ?: 'לא נמצא')
                . " | נמענים: '" . implode(',', $recipients) . "'"
                . " | mail() error: " . ($err['message'] ?? 'אין מידע'));
        }
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
    if (!empty($unresolved)) {
        echo '<pre style="font-family:monospace;line-height:1.8;direction:rtl;text-align:right;">';
        echo "FALLBACK — " . count($unresolved) . " שלוחות ללא חנות/מייל, ישלח סיכום ל-gild@bug.co.il:\n\n";
        echo htmlspecialchars(buildUnresolvedSummary($unresolved), ENT_QUOTES, 'UTF-8');
        echo '</pre>';
    }
    exit;
}

$fallbackSent = null;
if (!empty($unresolved)) {
    $fallbackSent = sendFallbackMail($unresolved);
    if (!$fallbackSent) {
        $err = error_get_last();
        cronLog('mail-fallback', 'error', "שליחת סיכום נכשלה | mail() error: " . ($err['message'] ?? 'אין מידע'));
    } else {
        cronLog('mail-fallback', 'ok', count($unresolved) . " שלוחות ללא חנות/מייל נשלחו ל-gild@bug.co.il");
    }
}

$totalLines = count($phones);
if (!empty($failLines)) {
    $details = "נבדקו: {$totalLines} | כשלו: " . implode(', ', $failLines);
    if ($mailErrors > 0) $details .= " | שגיאות שליחה: {$mailErrors}";
    cronLog('run', $mailErrors > 0 ? 'error' : 'ok', $details);
} else {
    cronLog('run', 'ok', "נבדקו: {$totalLines} | כל הקווים תקינים");
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

function getStoreByLine(string $line): array
{
    return DB::row(
        "SELECT email, store_num FROM stores
         WHERE type = 'סניף באג' AND FIND_IN_SET(?, telephone_line_num) AND is_active = 1 LIMIT 1",
        [$line]
    ) ?? [];
}


function cronLog(string $action, string $status = 'ok', string $details = ''): void
{
    DB::execute(
        "INSERT INTO cron_log (cron_name, action, status, details) VALUES ('cron_telephone', ?, ?, ?)",
        [$action, $status, $details]
    );
}

// ── מייל ─────────────────────────────────────────────────────────────────────

function buildUnresolvedSummary(array $unresolved): string
{
    $lines = [];
    foreach ($unresolved as $u) {
        $lines[] = "שלוחה: {$u['line']} | תיאור: {$u['desc']} | חנות: " . ($u['store_num'] ?: 'לא נמצא');
    }
    return implode("\n", $lines);
}

function sendFallbackMail(array $unresolved): bool
{
    $subject = '[מוקד-נט] שלוחות טלפון ללא חנות/מייל מקושרים';
    $subject = preg_replace('/[\r\n\0]/', '', $subject);

    $f  = 'font-family:Tahoma,Arial,sans-serif;text-align:right;';
    $td = $f . 'padding:10px 8px;border-bottom:1px solid #eceef2;font-size:13px;color:#20232e;';
    $th = $f . 'padding:0 8px 8px;font-size:12px;color:#8a8fa3;border-bottom:2px solid #dcdfe6;font-weight:600;';

    $rows = '';
    foreach ($unresolved as $u) {
        $rows .= '<tr>'
            . '<td align="right" dir="rtl" style="' . $td . '">' . Mail::e($u['line']) . '</td>'
            . '<td align="right" dir="rtl" style="' . $td . '">' . Mail::e($u['desc']) . '</td>'
            . '<td align="right" dir="rtl" style="' . $td . '">' . Mail::e((string) ($u['store_num'] ?: 'לא נמצא')) . '</td>'
            . '</tr>';
    }

    $body  = Mail::heading(
        'שלוחות טלפון לא מחוברות ללא חנות/מייל מזוהים',
        'לא נמצאה חנות פעילה עם מייל עבור השלוחות הבאות, ולכן לא נשלחה אליהן התראה ישירה:'
    );
    $body .= '<table role="presentation" dir="rtl" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">'
        . '<tr>'
        . '<td align="right" dir="rtl" style="' . $th . '">שלוחה</td>'
        . '<td align="right" dir="rtl" style="' . $th . '">תיאור</td>'
        . '<td align="right" dir="rtl" style="' . $th . '">חנות</td>'
        . '</tr>' . $rows . '</table>';

    return Mail::send('gild@bug.co.il', $subject, Mail::wrap($subject, 'מערכת ניהול פנים-ארגונית', $body));
}

function sendAlertMail(string $to, string $subject): bool
{
    $f = 'font-family:Tahoma,Arial,sans-serif;text-align:right;';

    $body = Mail::heading(
        'שלוחת הטלפון בחנות לא מצליחה להתחבר למרכזייה',
        'לוודא אם שלושת הנוריות פועלות באופן קבוע על גבי ה-GATEWAY כמתואר בתמונה.'
    );

    // כרטיסית אזהרה — נוריות דלוקות
    $body .= Mail::card('warn', 'אם הנוריות דלוקות קבוע', 'התקלה ככל הנראה בציוד/תשתית האינטרנט — נא לדבר עם ולדימיר.');

    // כרטיסית שגיאה — נורית כבויה
    $body .= Mail::card('error', 'אם אחת הנוריות אינה דלוקה באופן קבוע', 'יש לנתק את ה-GATEWAY מהחשמל ולהחזיר לאחר 15 שניות. להמתין 2 דקות ולנסות להוציא/לקבל. אם עדיין אין קו — התקשר אל גיל דגון.');

    $body .= '<p style="' . $f . 'margin:8px 0 20px;"><img src="https://i.imgur.com/co1fjRx.png" height="150" alt="gateway" style="border-radius:8px;"></p>';

    // CTA — גיל דגון
    $body .= Mail::card(
        'info',
        'אם הנ"ל לא עובד — נא להתקשר אל גיל דגון',
        '<span dir="ltr" style="' . $f . 'font-size:20px;font-weight:700;color:#3a63d8;letter-spacing:1px;">054-4744758</span>'
    );

    return Mail::send($to, $subject, Mail::wrap($subject, 'מערכת ניהול פנים-ארגונית', $body));
}
