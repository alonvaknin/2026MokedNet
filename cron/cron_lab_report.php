<?php
// רץ לפי לוח זמנים — שולח דוח מעבדה למקט 123456 ב-7 ימים האחרונים
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\DB;

$pdo = DB::get();

$toDate   = date('d/m/Y');
$fromDate = date('d/m/Y', strtotime('-1 week'));
$url      = "https://bug.wizenet.co.il/wizeapi/?func=wizeApp_getBICalls&dateFrom={$fromDate}&dateTo={$toDate}&Pmakat=123456&token=ABUltIBvgMHYUg6NPaZ4cWA2p5467Jb";

$data = json_decode(file_get_contents($url), true);

if (empty($data)) {
    cron_log($pdo, 'run', 'error', 'לא התקבלו נתונים מה-API');
    mail('gild@bug.co.il', 'לא מצליח לשלוח LAB CRON ' . date('d/m/y'), '', '');
    exit(json_encode(['mail_send' => false, 'calls_count' => 0]));
}

$wizeUrl = 'https://bug.wizenet.co.il/serviceControl.aspx?control=modulesCustom/bug/CallDetailsTech&CallID=';
$rows    = '';

foreach ($data as $call) {
    $createDate = date_create_from_format('d/m/Y H:i:s', $call['createDate'])->format('d/m/y');
    $callNum    = strip($call['CallID']);
    $callNote   = strip($call['comments']);
    $Cemail     = strip($call['Cemail']);
    $Cname      = strip($call['Cname']);

    $rows .= '<tr>'
        . "<td>{$createDate}</td>"
        . "<td><a href='{$wizeUrl}{$callNum}' target='_blank'>{$callNum}</a></td>"
        . "<td>{$callNote}</td>"
        . "<td class='opener'><a href='mailto:{$Cemail}?subject=לא הוספת מקט להערות בקריאה {$callNum} נא לשלוח מקט דחוף'>{$Cname}</a></td>"
        . '</tr>';
}

$table = '<table><thead><tr><th>פתיחה</th><th>קריאה</th><th>הערות</th><th>פותח</th></tr></thead>'
    . "<tbody>{$rows}</tbody></table>";

$callsCount = count($data);
$sent = sendReport($table, $callsCount);
cron_log($pdo, 'run', $sent ? 'ok' : 'error', "קריאות: {$callsCount}" . ($sent ? '' : ' | שליחת מייל נכשלה'));
exit(json_encode(['mail_send' => $sent, 'calls_count' => $callsCount]));

function cron_log(PDO $pdo, string $action, string $status = 'ok', string $details = ''): void
{
    $pdo->prepare(
        "INSERT INTO cron_log (cron_name, action, status, details) VALUES ('cron_lab_report', ?, ?, ?)"
    )->execute([$action, $status, $details]);
}

function strip(string $val): string
{
    return str_replace(['\\', '/', '"'], '', $val);
}

function sendReport(string $tableHtml, int $callsCount): bool
{
    $subject = '[דוח אוטומטי] מקט 123456 — ' . date('d/m/y H:i') . ' — שבוע אחרון';

    $css = '<style>'
        . 'table.data-tbl { direction:RTL; border-collapse:collapse; width:100%; }'
        . 'table.data-tbl th { background:#1e2435; color:#b0b3c6; font-size:12px; font-weight:600; text-align:right; padding:10px 12px; border-bottom:1px solid rgba(255,255,255,.1); }'
        . 'table.data-tbl td { font-size:13px; color:#e8eaf0; text-align:right; padding:10px 12px; border-bottom:1px solid rgba(255,255,255,.05); }'
        . 'table.data-tbl td a { color:#4f7fff; text-decoration:none; }'
        . 'table.data-tbl .opener { font-size:11px; }'
        . '</style>';

    $body  = $css;
    $body .= '<p style="font-size:16px;font-weight:700;color:#e8eaf0;margin:0 0 8px;">דוח מקט 123456</p>';
    $body .= '<p style="font-size:14px;color:#b0b3c6;margin:0 0 20px;">נמצאו <b>' . $callsCount . '</b> קריאות ב-7 ימים האחרונים.</p>';
    $body .= str_replace('<table>', '<table class="data-tbl">', $tableHtml);

    $message = mailWrap($subject, $body);

    $headers  = "From: מוקד-נט <moked-net-noreply@alexisdeveloping.com>\r\n";
    $headers .= "Reply-To: no_reply@bug.co.il\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";

    return mail('gild@bug.co.il, chaim@modan.co.il', $subject, $message, $headers);
}

function mailWrap(string $title, string $body): string
{
    return '<!DOCTYPE html>'
        . '<html lang="he" dir="rtl">'
        . '<head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title></head>'
        . '<body style="font-family:Tahoma,Arial,sans-serif;background:#0f1117;color:#e8eaf0;direction:rtl;text-align:right;margin:0;padding:0;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#0f1117;padding:32px 0;">'
        . '<tr><td align="center">'
        . '<table width="600" cellpadding="0" cellspacing="0" style="background:#181b23;border:1px solid rgba(255,255,255,.08);border-radius:12px;overflow:hidden;">'
        . '<tr><td style="background:#4f7fff;padding:24px 32px;text-align:right;">'
        . '<span style="font-size:24px;font-weight:700;color:#fff;">מוקד-נט</span>'
        . '<span style="font-size:14px;color:rgba(255,255,255,.75);margin-right:12px;">דוח אוטומטי</span>'
        . '</td></tr>'
        . '<tr><td style="padding:32px;">' . $body . '</td></tr>'
        . '<tr><td style="background:#13161e;padding:16px 32px;text-align:right;">'
        . '<span style="font-size:12px;color:#5a5e78;">מופעל באמצעות מערכת מוקד-נט</span>'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}
