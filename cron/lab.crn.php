<?php
// רץ לפי לוח זמנים — שולח דוח מעבדה למקט 123456 ב-7 ימים האחרונים
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\DB;

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

    $rows .= '<tr>'
        . '<td>' . htmlspecialchars($createDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>'
        . "<td><a href=\"{$callUrl}\" target=\"_blank\">{$callNumHtml}</a></td>"
        . "<td>{$callNoteHtml}</td>"
        . "<td class=\"opener\"><a href=\"mailto:{$CemailHtml}?subject={$mailSubject}\">{$CnameHtml}</a></td>"
        . '</tr>';
}

$table = '<table><thead><tr><th>פתיחה</th><th>קריאה</th><th>הערות</th><th>פותח</th></tr></thead>'
    . "<tbody>{$rows}</tbody></table>";

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

    $css = '<style>'
        . 'table.data-tbl { direction:RTL; border-collapse:collapse; width:90%; margin:0 auto; }'
        . 'table.data-tbl th { background:#eef0f5; color:#4a4f66; font-size:12px; font-weight:600; text-align:right; padding:10px 12px; border-bottom:1px solid #dcdfe6; }'
        . 'table.data-tbl td { font-size:13px; color:#2b2e3b; text-align:right; padding:10px 12px; border-bottom:1px solid #eceef2; }'
        . 'table.data-tbl tbody tr:nth-child(even) { background:#f7f8fa; }'
        . 'table.data-tbl tbody tr:nth-child(odd) { background:#ffffff; }'
        . 'table.data-tbl td a { color:#3a63d8; text-decoration:none; }'
        . 'table.data-tbl .opener { font-size:11px; }'
        . '</style>';

    $body  = $css;
    $body .= '<p style="font-size:16px;font-weight:700;color:#20232e;margin:0 0 8px;">דוח מקט 123456</p>';
    $body .= '<p style="font-size:14px;color:#5a5e78;margin:0 0 20px;">נמצאו <b>'
        . $callsCount
        . '</b> קריאות ב-7 ימים האחרונים.</p>';
    $body .= str_replace('<table>', '<table class="data-tbl">', $tableHtml);

    $message = mailWrap($subject, $body);

    $headers  = 'From: ' . mimeHeader('מוקד-נט') . " <moked-net-noreply@alexisdeveloping.com>\r\n";
    $headers .= "Reply-To: no_reply@bug.co.il\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n";

    return mail(
        'gild@bug.co.il, chaim@modan.co.il',
        mimeHeader($subject),
        chunk_split(base64_encode($message)),
        $headers
    );
}

function mailWrap(string $title, string $body): string
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return '<!DOCTYPE html>'
        . '<html lang="he" dir="rtl">'
        . '<head><meta charset="utf-8"><title>' . $safeTitle . '</title></head>'
        . '<body style="font-family:Tahoma,Arial,sans-serif;background:#f4f5f7;color:#2b2e3b;direction:rtl;text-align:right;margin:0;padding:0;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:32px 0;">'
        . '<tr><td align="center">'
        . '<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #e3e5ea;border-radius:12px;overflow:hidden;">'
        . '<tr><td style="background:#4f7fff;padding:24px 32px;text-align:right;">'
        . '<span style="font-size:24px;font-weight:700;color:#fff;">מוקד-נט</span>'
        . '<span style="font-size:14px;color:rgba(255,255,255,.85);margin-right:12px;">דוח אוטומטי</span>'
        . '</td></tr>'
        . '<tr><td style="padding:32px;">' . $body . '</td></tr>'
        . '<tr><td style="background:#f7f8fa;padding:16px 32px;text-align:right;border-top:1px solid #eceef2;">'
        . '<span style="font-size:12px;color:#8a8fa3;">מופעל באמצעות מערכת מוקד-נט</span>'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}