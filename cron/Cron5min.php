<?php
// רץ כל 5 דקות — שולח מייל על שינויי זמינות חנויות
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\DB;
use Core\MailTemplate as Mail;

$pdo = DB::get(); // alon_db2

$last_check_time = DB::value(
    "SELECT detail FROM activity_log
     WHERE action = 'cron.run' AND entity_label = 'cron_5min'
     ORDER BY created_at DESC LIMIT 1"
);

if (!$last_check_time) {
    $last_check_time = date('Y-m-d H:i:s', strtotime('-1 hour'));
}

$new_check_time = date('Y-m-d H:i:s');

$result = DB::query(
    "SELECT al.entity_id, al.entity_label, al.user_name, al.new_value, al.created_at
     FROM activity_log al
     WHERE al.action = 'store.toggle'
     AND al.created_at > :last_check
     AND al.created_at <= :new_check
     ORDER BY al.created_at ASC",
    [':last_check' => $last_check_time, ':new_check' => $new_check_time]
);

$changeCount = count($result);

if ($changeCount > 0) {
    $temp = '';
    foreach ($result as $row) {
        $storeNum   = get_store_num((int) $row['entity_id']);
        $storeName  = Mail::e($row['entity_label']);
        $user       = Mail::e($row['user_name'] ?? 'לא ידוע');
        $action     = ($row['new_value'] === '1')
            ? '<span style="color:#2e9e5b;font-weight:700;">פעילה</span>'
            : '<span style="color:#d13c3c;font-weight:700;">לא פעילה</span>';
        $temp .= '<tr><td align="right" dir="rtl" style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.7;color:#20232e;padding:10px 0;border-bottom:1px solid #eef0f4;text-align:right;">'
            . "חנות: <b>{$storeName}</b>" . ($storeNum ? ' - ' . Mail::e($storeNum) : '') . " עברה ל: {$action} ע\"י {$user}"
            . '</td></tr>';
    }

    $to      = 'gild@bug.co.il,eyal@bug.co.il,web8@bug.co.il,web4@bug.co.il,amir@bug.co.il,sharone@bug.co.il,sagih@bug.co.il,roei@bug.co.il,nissimh@bug.co.il,haim@bug.co.il,oritc@bug.co.il,modan@modan.co.il,mebah@bug.co.il,talb@bug.co.il,alex@bug.co.il,yehonatan@bug.co.il,gal@bug.co.il,ayman@bug.co.il,avitala@bug.co.il';
    $subject = 'בוצע עדכון זמינות לחנות\יות באג ' . date('d/m/y H:i');

    $mbody  = Mail::heading('עדכוני זמינות חנויות', 'ב-5 הדקות האחרונות בוצעו ' . $changeCount . ' שינויים:');
    $mbody .= '<table role="presentation" dir="rtl" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0 0 24px;">' . $temp . '</table>';
    $mbody .= Mail::button('https://alon.alexisdeveloping.com/stores', 'רשימת זמינות חנויות עדכנית ←');

    $sent = Mail::send($to, $subject, Mail::wrap($subject, 'עדכון אוטומטי', $mbody), ['replyTo' => 'gild@bug.co.il']);
    $summary = "שינויי חנות: {$changeCount}" . ($sent ? '' : ' | שליחת מייל נכשלה');
    cron_log('run', $sent ? 'ok' : 'error', $summary);
    // activity_log_run($new_check_time, $summary);
} else {
    cron_log('run', 'ok', 'אין שינויים');
    // activity_log_run($new_check_time, 'אין שינויים');
}

function cron_log(string $action, string $status = 'ok', string $details = ''): void
{
    DB::execute(
        "INSERT INTO cron_log (cron_name, action, status, details) VALUES ('cron_5min', ?, ?, ?)",
        [$action, $status, $details]
    );
}

function activity_log_run(string $checkedUntil, string $summary): void
{
    DB::execute(
        "INSERT INTO activity_log (action, entity_type, entity_label, detail, new_value)
         VALUES ('cron.run', 'cron', 'cron_5min', ?, ?)",
        [$checkedUntil, $summary]
    );
}

function get_store_num(int $id): string
{
    return (string) (DB::value('SELECT store_num FROM stores WHERE id = ? LIMIT 1', [$id]) ?: '');
}
