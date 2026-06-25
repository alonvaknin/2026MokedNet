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
    $message = '<html lang="HE" dir="rtl"><head></head><body style="text-align:right; direction:rtl;">';
    $message .= 'ב-5 דקות האחרונות בוצעו שינויים:<br>';
    $message .= "{$temp}<br>";
    $message .= "<a href='https://alon.alexisdeveloping.com/test/stores_availability.test.php'>רשימת זמינות חנויות עדכנית</a><br>";
    $message .= '<p><span style="color: #999999;">מופעל באמצעות מערכת מוקד-נט</span></p>';
    $message .= '</body></html>';

    $headers  = "From: <NoReply@NoReply.co.il>\r\n";
    $headers .= "Reply-To: gild@bug.co.il\r\n";
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

    $sent = mail($to, $subject, $message, $headers);
    cron_log($pdoLog, 'run', $sent ? 'ok' : 'error', "שינויי חנות: {$changeCount}" . ($sent ? '' : ' | שליחת מייל נכשלה'));
}

$pdo->prepare(
    "INSERT INTO logger (logAction, logValue, userId, userName, logWhereChange, SEVERITY)
     VALUES ('LAST_READ_CRON_CHANGE', :logval, 0, 'cron', 'system', 'INFO')"
)->execute([':logval' => $new_check_time]);

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
