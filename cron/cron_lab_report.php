<?php
// רץ לפי לוח זמנים — שולח דוח מעבדה למקט 123456 ב-7 ימים האחרונים
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$toDate   = date('d/m/Y');
$fromDate = date('d/m/Y', strtotime('-1 week'));
$url      = "https://bug.wizenet.co.il/wizeapi/?func=wizeApp_getBICalls&dateFrom={$fromDate}&dateTo={$toDate}&Pmakat=123456&token=ABUltIBvgMHYUg6NPaZ4cWA2p5467Jb";

$data = json_decode(file_get_contents($url), true);

if (empty($data)) {
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

$sent = sendReport($table, count($data));
exit(json_encode(['mail_send' => $sent, 'calls_count' => count($data)]));

function strip(string $val): string
{
    return str_replace(['\\', '/', '"'], '', $val);
}

function sendReport(string $tableHtml, int $count): bool
{
    $css = '<style>
        table { direction:RTL; font-family:Tahoma,Arial; border-collapse:collapse; width:100%; }
        th, td { border:1px solid #ddd; text-align:right; padding:8px; }
        th { background-color:#f2f2f2; }
        .opener { font-size:8px; }
    </style>';

    $message  = "<html lang='HE' dir='rtl'><head>{$css}</head><body style='text-align:right;direction:rtl;'>";
    $message .= '<p>מציג דוח למקט 123456 ב-7 ימים האחרונים</p>';
    $message .= $tableHtml;
    $message .= '</body></html>';

    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
    $headers .= 'From: מוקד-נט <no_reply@bug.co.il>' . "\r\n";
    $headers .= 'Reply-To: no_reply@bug.co.il' . "\r\n";

    return mail(
        'gild@bug.co.il, chaim@modan.co.il',
        '[דוח אוטומטי] מקט 123456 ' . date('d/m/y H:i') . ' בשבוע האחרון',
        $message,
        $headers
    );
}
