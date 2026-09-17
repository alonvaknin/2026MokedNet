<?php
// רץ כל שעה — בדיקת קווי טלפון בחנויות מול mvoice
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\DB;

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

function mailWrap(string $title, string $body): string
{
    return '<!DOCTYPE html>'
        . '<html lang="he" dir="rtl">'
        . '<head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title></head>'
        . '<body style="font-family:Tahoma,Arial,sans-serif;background:#f4f5f7;color:#2b2e3b;direction:rtl;text-align:right;margin:0;padding:0;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:32px 0;">'
        . '<tr><td align="center">'
        . '<table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #e3e5ea;border-radius:12px;overflow:hidden;">'
        . '<tr><td style="background:#4f7fff;padding:24px 32px;text-align:right;">'
        . '<span style="font-size:24px;font-weight:700;color:#fff;">מוקד-נט</span>'
        . '<span style="font-size:14px;color:rgba(255,255,255,.85);margin-right:12px;">מערכת ניהול פנים-ארגונית</span>'
        . '</td></tr>'
        . '<tr><td style="padding:32px;">' . $body . '</td></tr>'
        . '<tr><td style="background:#f7f8fa;padding:16px 32px;text-align:right;border-top:1px solid #eceef2;">'
        . '<span style="font-size:12px;color:#8a8fa3;">מופעל באמצעות מערכת מוקד-נט</span>'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

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

    $rows = '';
    foreach ($unresolved as $u) {
        $rows .= '<tr>'
            . '<td style="padding:8px 12px;border-bottom:1px solid #eceef2;">' . htmlspecialchars($u['line'], ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td style="padding:8px 12px;border-bottom:1px solid #eceef2;">' . htmlspecialchars($u['desc'], ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td style="padding:8px 12px;border-bottom:1px solid #eceef2;">' . htmlspecialchars((string)($u['store_num'] ?: 'לא נמצא'), ENT_QUOTES, 'UTF-8') . '</td>'
            . '</tr>';
    }

    $body  = '<p style="font-size:18px;font-weight:700;color:#20232e;margin:0 0 16px;">שלוחות טלפון לא מחוברות ללא חנות/מייל מזוהים</p>';
    $body .= '<p style="font-size:14px;color:#5a5e78;margin:0 0 16px;">לא נמצאה חנות פעילה עם מייל עבור השלוחות הבאות, ולכן לא נשלחה אליהן התראה ישירה:</p>';
    $body .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;color:#2b2e3b;">';
    $body .= '<tr style="background:#f7f8fa;"><th style="padding:8px 12px;text-align:right;">שלוחה</th><th style="padding:8px 12px;text-align:right;">תיאור</th><th style="padding:8px 12px;text-align:right;">חנות</th></tr>';
    $body .= $rows;
    $body .= '</table>';

    $message = mailWrap($subject, $body);
    $headers  = "From: מוקד-נט <moked-net-noreply@alexisdeveloping.com>\r\n";
    $headers .= "Reply-To: moked-net-noreply@alexisdeveloping.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";

    return mail('gild@bug.co.il', $subject, $message, $headers);
}

function sendAlertMail(string $to, string $subject): bool
{
    $body  = '<p style="font-size:18px;font-weight:700;color:#20232e;margin:0 0 16px;">שלוחת הטלפון בחנות לא מצליחה להתחבר למרכזייה</p>';
    $body .= '<p style="font-size:14px;color:#5a5e78;margin:0 0 16px;">לוודא אם שלושת הנוריות פועלות באופן קבוע על גבי ה-GATEWAY כמתואר בתמונה.</p>';

    // כרטיסית אזהרה — נוריות דלוקות
    $body .= '<div style="background:#fdf3e3;border-right:4px solid #e67e22;border-radius:8px;padding:14px 16px;margin:0 0 12px;">';
    $body .= '<p style="font-size:14px;color:#b5680f;font-weight:700;margin:0 0 6px;">אם הנוריות דלוקות קבוע</p>';
    $body .= '<p style="font-size:13px;color:#5a5e78;margin:0;">התקלה ככל הנראה בציוד/תשתית האינטרנט — נא לדבר עם ולדימיר.</p>';
    $body .= '</div>';

    // כרטיסית שגיאה — נורית כבויה
    $body .= '<div style="background:#fdeeee;border-right:4px solid #e74c3c;border-radius:8px;padding:14px 16px;margin:0 0 20px;">';
    $body .= '<p style="font-size:14px;color:#c0392b;font-weight:700;margin:0 0 6px;">אם אחת הנוריות אינה דלוקה באופן קבוע</p>';
    $body .= '<p style="font-size:13px;color:#5a5e78;margin:0;">יש לנתק את ה-GATEWAY מהחשמל ולהחזיר לאחר 15 שניות. להמתין 2 דקות ולנסות להוציא/לקבל. אם עדיין אין קו — התקשר אל גיל דגון.</p>';
    $body .= '</div>';

    $body .= '<p style="margin:0 0 20px;"><img src="https://i.imgur.com/co1fjRx.png" height="150" alt="gateway" style="border-radius:8px;"></p>';

    // CTA — גיל דגון
    $body .= '<div style="background:#eef0f5;border:1px solid #dcdfe6;border-radius:8px;padding:14px 16px;">';
    $body .= '<p style="font-size:15px;font-weight:700;color:#b5680f;margin:0;">אם הנ"ל לא עובד — נא להתקשר אל גיל דגון</p>';
    $body .= '<p style="font-size:20px;font-weight:700;color:#3a63d8;margin:6px 0 0;letter-spacing:1px;">054-4744758</p>';
    $body .= '</div>';

    $message = mailWrap($subject, $body);
    $headers  = "From: מוקד-נט <moked-net-noreply@alexisdeveloping.com>\r\n";
    $headers .= "Reply-To: moked-net-noreply@alexisdeveloping.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";

    return mail($to, $subject, $message, $headers);
}
