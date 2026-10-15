<?php
// רץ לפי לוח זמנים — שולח דוח מעבדה למקט 123456 ב-7 ימים האחרונים
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\DB;
use Core\MailTemplate as Mail;

$toDate   = date('d/m/Y');
$fromDate = date('d/m/Y', strtotime('-1 week'));
$url      = "https://bug.wizenet.co.il/wizeapi/?func=wizeApp_getBICalls&dateFrom={$fromDate}&dateTo={$toDate}&Pmakat=123456&token=ABUltIBvgMHYUg6NPaZ4cWA2p5467Jb";

$data = json_decode(file_get_contents($url), true);

if (empty($data)) {
    cron_log('run', 'error', 'לא התקבלו נתונים מה-API');

    $headers  = 'From: ' . mimeHeader('מוקד-נט') . " <moked-net-noreply@alexisdeveloping.com>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n";

    mail(
        'gild@bug.co.il',
        mimeHeader('לא מצליח לשלוח LAB CRON ' . date('d/m/y')),
        chunk_split(base64_encode('לא התקבלו נתונים מה-API')),
        $headers
    );

    exit(json_encode(['mail_send' => false, 'calls_count' => 0]));
}

$wizeUrl = 'https://bug.wizenet.co.il/serviceControl.aspx?control=modulesCustom/bug/CallDetailsTech&CallID=';
$rows    = '';
$rowIdx  = 0;

foreach ($data as $call) {
    $createDate = date_create_from_format('d/m/Y H:i:s', $call['createDate'])->format('d/m/y');
    $callNum    = strip((string) $call['CallID']);
    $callNote   = strip((string) $call['comments']);
    $Cemail     = strip((string) $call['Cemail']);
    $Cname      = strip((string) $call['Cname']);

    $callNumHtml = htmlspecialchars($callNum, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $callNoteHtml = htmlspecialchars($callNote, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $CemailHtml = htmlspecialchars($Cemail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $CnameHtml = htmlspecialchars($Cname, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $callUrl = $wizeUrl . rawurlencode($callNum);
    $mailSubject = rawurlencode('לא הוספת מקט להערות בקריאה ' . $callNum . ' נא לשלוח מקט דחוף');

    $td = 'font-family:Tahoma,Arial,sans-serif;font-size:13px;color:#2b2e3b;text-align:right;padding:10px 8px;border-bottom:1px solid #eceef2;vertical-align:top;';
    $bg = ($rowIdx++ % 2) ? 'background:#f7f8fa;' : 'background:#ffffff;';
    $rows .= '<tr>'
        . '<td align="right" dir="rtl" style="' . $td . $bg . 'white-space:nowrap;">' . htmlspecialchars($createDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>'
        . '<td align="right" dir="rtl" style="' . $td . $bg . '"><a href="' . $callUrl . '" target="_blank" style="color:#3a63d8;text-decoration:none;font-weight:600;">' . $callNumHtml . '</a></td>'
        . '<td align="right" dir="rtl" style="' . $td . $bg . '">' . $callNoteHtml . '</td>'
        . '<td align="right" dir="rtl" style="' . $td . $bg . 'font-size:12px;"><a href="mailto:' . $CemailHtml . '?subject=' . $mailSubject . '" style="color:#3a63d8;text-decoration:none;">' . $CnameHtml . '</a></td>'
        . '</tr>';
}

$th = 'font-family:Tahoma,Arial,sans-serif;background:#eef0f5;color:#4a4f66;font-size:12px;font-weight:600;text-align:right;padding:10px 8px;border-bottom:1px solid #dcdfe6;';
$table = '<table role="presentation" dir="rtl" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">'
    . '<tr>'
    . '<td align="right" dir="rtl" style="' . $th . '">פתיחה</td>'
    . '<td align="right" dir="rtl" style="' . $th . '">קריאה</td>'
    . '<td align="right" dir="rtl" style="' . $th . '">הערות</td>'
    . '<td align="right" dir="rtl" style="' . $th . '">פותח</td>'
    . '</tr>' . $rows . '</table>';

$callsCount = count($data);
$sent = sendReport($table, $callsCount);

cron_log(
    'run',
    $sent ? 'ok' : 'error',
    "קריאות: {$callsCount}" . ($sent ? '' : ' | שליחת מייל נכשלה')
);

exit(json_encode(['mail_send' => $sent, 'calls_count' => $callsCount]));

function cron_log(string $action, string $status = 'ok', string $details = ''): void
{
    DB::execute(
        "INSERT INTO cron_log (cron_name, action, status, details) VALUES ('cron_lab_report', ?, ?, ?)",
        [$action, $status, $details]
    );
}

function strip(string $val): string
{
    return str_replace(['\\', '/', '"'], '', $val);
}

function mimeHeader(string $value): string
{
    return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
}

function sendReport(string $tableHtml, int $callsCount): bool
{
    $subject = '[דוח אוטומטי] מקט 123456 — ' . date('d/m/y H:i') . ' — שבוע אחרון';

    $body  = Mail::heading(
        'דוח מקט 123456',
        'נמצאו <b>' . $callsCount . '</b> קריאות ב-7 ימים האחרונים.'
    );
    $body .= $tableHtml;

    return Mail::send(
        'gild@bug.co.il, chaim@modan.co.il',
        $subject,
        Mail::wrap($subject, 'דוח אוטומטי', $body, 680),
        ['replyTo' => 'no_reply@bug.co.il']
    );
}