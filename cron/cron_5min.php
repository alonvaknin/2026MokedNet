<?php
// רץ כל 5 דקות — שולח מייל על שינויי זמינות חנויות
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\DB;

$pdo    = DB::v1();
$pdoLog = DB::get(); // alon_db2 — cron_log

$stmt = $pdo->prepare(
    "SELECT logValue FROM logger
     WHERE logAction = 'LAST_READ_CRON_CHANGE'
     ORDER BY logtime DESC LIMIT 1"
);
$stmt->execute();
$last_check_time = $stmt->fetchColumn();

if (!$last_check_time) {
    $last_check_time = date('Y-m-d H:i:s', strtotime('-1 hour'));
}

$new_check_time = date('Y-m-d H:i:s');

$stmt = $pdo->prepare(
    "SELECT * FROM logger
     WHERE logAction = 'change-store-display'
     AND logtime > :last_check
     AND logtime <= :new_check"
);
$stmt->execute([':last_check' => $last_check_time, ':new_check' => $new_check_time]);
$result = $stmt->fetchAll();

$changeCount = count($result);

if ($changeCount > 0) {
    $temp = '';
    foreach ($result as $row) {
        $val        = explode("@!", $row['logValue']);
        $sNum       = $val[0];
        $user       = $row['userName'];
        $action     = ($val[1] === 'on')
            ? "<span style='color:green;'>פעילה</span>"
            : "<span style='color:red;'>לא פעילה</span>";
        $store_name = get_store_name($pdo, $sNum);
        $temp      .= "חנות: {$store_name} - {$sNum} עברה ל: <strong>{$action}</strong> ע''י {$user}<br>";
    }

    $to      = 'gild@bug.co.il,eyal@bug.co.il,web8@bug.co.il,web4@bug.co.il,amir@bug.co.il,sharone@bug.co.il,sagih@bug.co.il,roei@bug.co.il,nissimh@bug.co.il,haim@bug.co.il,oritc@bug.co.il,modan@modan.co.il,mebah@bug.co.il,talb@bug.co.il,alex@bug.co.il,yehonatan@bug.co.il,gal@bug.co.il,ayman@bug.co.il,avitala@bug.co.il';
    $subject = 'בוצע עדכון זמינות לחנות\יות באג ' . date('d/m/yy H:i');

    $mbody  = '<p style="font-size:16px;font-weight:700;color:#e8eaf0;margin:0 0 8px;">עדכוני זמינות חנויות</p>';
    $mbody .= '<p style="font-size:14px;color:#b0b3c6;margin:0 0 20px;">ב-5 דקות האחרונות בוצעו ' . $changeCount . ' שינויים:</p>';
    $mbody .= '<div style="background:#1e2435;border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:16px;margin:0 0 20px;line-height:2;">';
    $mbody .= $temp;
    $mbody .= '</div>';
    $mbody .= '<table cellpadding="0" cellspacing="0" style="margin:0 0 8px;">';
    $mbody .= '<tr><td style="background:#4f7fff;border-radius:8px;padding:0;">';
    $mbody .= '<a href="https://alon.alexisdeveloping.com/test/stores_availability.test.php" style="display:block;padding:12px 28px;color:#fff;font-size:15px;font-weight:600;text-decoration:none;">רשימת זמינות חנויות עדכנית ←</a>';
    $mbody .= '</td></tr></table>';

    $message = mailWrap($subject, $mbody);
    $headers  = "From: מוקד-נט <moked-net-noreply@alexisdeveloping.com>\r\n";
    $headers .= "Reply-To: gild@bug.co.il\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";

    $sent = mail($to, $subject, $message, $headers);
    cron_log($pdoLog, 'run', $sent ? 'ok' : 'error', "שינויי חנות: {$changeCount}" . ($sent ? '' : ' | שליחת מייל נכשלה'));
} else {
    cron_log($pdoLog, 'run', 'ok', 'אין שינויים');
}

$pdo->prepare(
    "INSERT INTO logger (logAction, logValue, userId, userName, logWhereChange, SEVERITY)
     VALUES ('LAST_READ_CRON_CHANGE', :logval, 0, 'cron', 'system', 'INFO')"
)->execute([':logval' => $new_check_time]);

function mailWrap(string $title, string $body): string
{
    return '<!DOCTYPE html>'
        . '<html lang="he" dir="rtl">'
        . '<head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title></head>'
        . '<body style="font-family:Tahoma,Arial,sans-serif;background:#0f1117;color:#e8eaf0;direction:rtl;text-align:right;margin:0;padding:0;">'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#0f1117;padding:32px 0;">'
        . '<tr><td align="center">'
        . '<table width="520" cellpadding="0" cellspacing="0" style="background:#181b23;border:1px solid rgba(255,255,255,.08);border-radius:12px;overflow:hidden;">'
        . '<tr><td style="background:#4f7fff;padding:24px 32px;text-align:right;">'
        . '<span style="font-size:24px;font-weight:700;color:#fff;">מוקד-נט</span>'
        . '<span style="font-size:14px;color:rgba(255,255,255,.75);margin-right:12px;">עדכון אוטומטי</span>'
        . '</td></tr>'
        . '<tr><td style="padding:32px;">' . $body . '</td></tr>'
        . '<tr><td style="background:#13161e;padding:16px 32px;text-align:right;">'
        . '<span style="font-size:12px;color:#5a5e78;">מופעל באמצעות מערכת מוקד-נט</span>'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

function cron_log(PDO $pdo, string $action, string $status = 'ok', string $details = ''): void
{
    $pdo->prepare(
        "INSERT INTO cron_log (cron_name, action, status, details) VALUES ('cron_5min', ?, ?, ?)"
    )->execute([$action, $status, $details]);
}

function get_store_name(PDO $pdo, string $sNum): string
{
    $stmt = $pdo->prepare(
        'SELECT sName FROM stores WHERE sNum = ? AND active = 1 LIMIT 1'
    );
    $stmt->execute([$sNum]);
    return (string) ($stmt->fetchColumn() ?: $sNum);
}
