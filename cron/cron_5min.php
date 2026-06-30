<?php
// רץ כל 5 דקות — שולח מייל על שינויי זמינות חנויות
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\DB;

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
        $storeNum   = get_store_num($row['entity_id']);
        $storeName  = $row['entity_label'];
        $user       = $row['user_name'] ?? 'לא ידוע';
        $action     = ($row['new_value'] === '1')
            ? "<span style='color:green;'>פעילה</span>"
            : "<span style='color:red;'>לא פעילה</span>";
        $temp      .= "חנות: {$storeName}" . ($storeNum ? " - {$storeNum}" : '') . " עברה ל: <strong>{$action}</strong> ע''י {$user}<br>";
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
    $mbody .= '<a href="https://alon.alexisdeveloping.com/stores" style="display:block;padding:12px 28px;color:#fff;font-size:15px;font-weight:600;text-decoration:none;">רשימת זמינות חנויות עדכנית ←</a>';
    $mbody .= '</td></tr></table>';

    $message = mailWrap($subject, $mbody);
    $headers  = "From: מוקד-נט <moked-net-noreply@alexisdeveloping.com>\r\n";
    $headers .= "Reply-To: gild@bug.co.il\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";

    $sent = mail($to, $subject, $message, $headers);
    $summary = "שינויי חנות: {$changeCount}" . ($sent ? '' : ' | שליחת מייל נכשלה');
    cron_log('run', $sent ? 'ok' : 'error', $summary);
    activity_log_run($new_check_time, $summary);
} else {
    cron_log('run', 'ok', 'אין שינויים');
    activity_log_run($new_check_time, 'אין שינויים');
}

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
