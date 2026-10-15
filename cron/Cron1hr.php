<?php
// רץ כל שעה — מעבד אוטומציות פעילות מ-alon_db2
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\DB;
use Core\MailTemplate as Mail;

// חייב להיות למעלה: const מתבצע בזמן ריצה לפי הסדר, והקוד הראשי רץ לפני סוף הקובץ
const MAIL_BADGE = 'התראה אוטומטית';

$pdo = DB::get(); // alon_db2

$runLog = [];

closeExpiredJobs($pdo, $runLog);
deactivateMaxRunJobs($pdo, $runLog);
processActiveJobs($pdo, $runLog);
notifyOverdueTasks($pdo, $runLog);

// רץ פעם ביום בלבד — בשעה 11:00, כדי לא להציף מייל בכל ריצת שעה
if ((int) date('H') === 11) {
    notifyStaleStoreAlerts($pdo, $runLog);
}

// כל רשומה שמכילה "נכשל"/"שגיאה" מסמנת את הריצה כ-error ב-cron_log
$hasError = !empty(preg_grep('/נכשל|שגיאה|שגיאות/u', $runLog));
cronLog($pdo, 'run', $hasError ? 'error' : 'ok', !empty($runLog) ? implode(' | ', $runLog) : 'אין פעולות');

// ── סגירת משימות שעבר תאריך תפוגתן ─────────────────────────────────────────

function closeExpiredJobs(PDO $pdo, array &$runLog): void
{
    $stmt = $pdo->prepare(
        "SELECT id, user_name, user_mail, mailto, cc_mail, msg_from_user,
                value_of_type, type_of_job
         FROM automations
         WHERE upto_date < NOW() AND is_active = 1"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $closed = 0;
    foreach ($rows as $row) {
        $pdo->prepare(
            "UPDATE automations
             SET is_active = 0, status_of_job = 'משימה נסגרה ללא ביצוע - עבר תאריך התפוגה',
                 status_change_time = NOW()
             WHERE id = ?"
        )->execute([$row['id']]);

        sendMailExpired($row);
        $closed++;
    }
    if ($closed > 0) $runLog[] = "פג תוקף: {$closed}";
}

// ── סגירת משימות שהגיעו ל-maxRun ────────────────────────────────────────────

function deactivateMaxRunJobs(PDO $pdo, array &$runLog): void
{
    $affected = $pdo->exec(
        "UPDATE automations
         SET is_active = 0, status_of_job = 'נסגר לאחר ביצוע', status_change_time = NOW()
         WHERE count_run >= max_run AND is_active = 1"
    );
    if ($affected > 0) $runLog[] = "maxRun: {$affected}";
}

// ── עיבוד משימות פעילות ──────────────────────────────────────────────────────

function processActiveJobs(PDO $pdo, array &$runLog): void
{
    $stmt = $pdo->prepare(
        "SELECT id, user_name, user_mail, mailto, cc_mail, msg_from_user,
                type_of_job, condition_of_type, value_of_type,
                created_at, run_even_diff, current_save_value
         FROM automations
         WHERE is_active = 1"
    );
    $stmt->execute();
    $jobs = $stmt->fetchAll();

    $sent = 0; $errors = 0;

    foreach ($jobs as $job) {
        error_clear_last(); // כדי ש-error_get_last() ב-jobFailMsg יתייחס רק לאוטומציה הנוכחית
        // שגיאה באוטומציה אחת (API תקוע, מייל וכו') לא עוצרת את שאר האוטומציות
        try {
        switch ($job['type_of_job']) {

            case 'notifyOnChangeTo':
                $currentStatus = getCallStatus($job['value_of_type']);
                if ($currentStatus === false) break;
                if ((int) $job['run_even_diff'] === 1) {
                    if ($job['current_save_value'] !== $currentStatus) {
                        $savedLabel   = translateStatus((string) $job['current_save_value']);
                        $currentLabel = translateStatus($currentStatus);
                        if (sendMailNotifyOnChange($job, $savedLabel, $currentLabel)) {
                            incrementRunCount($pdo, $job['id']);
                            updateSavedValue($pdo, $job['id'], $currentStatus);
                            $sent++;
                        } else { $errors++; $runLog[] = jobFailMsg($job, 'שליחת מייל נכשלה'); }
                    }
                } else {
                    if ($job['condition_of_type'] === $currentStatus) {
                        if (sendMailNotifyOnChange($job, '', translateStatus($currentStatus))) {
                            incrementRunCount($pdo, $job['id']);
                            setJobDone($pdo, $job['id']);
                            $sent++;
                        } else { $errors++; $runLog[] = jobFailMsg($job, 'שליחת מייל נכשלה'); }
                    }
                }
                break;

            case 'openCaseByPhone':
                $calls = getCallsByPhone($job['value_of_type'], $job['created_at']);
                if ($calls !== false) {
                    if (sendMailOpenByPhone($job, implode(', ', array_keys($calls)))) {
                        incrementRunCount($pdo, $job['id']);
                        $sent++;
                    } else { $errors++; $runLog[] = jobFailMsg($job, 'שליחת מייל נכשלה'); }
                }
                break;

            case 'techCare':
                $techAnswer = getTechCare($job['value_of_type']);
                // תשובה זהה ל-baseline שנשמר ביצירה = לא התעדכן כלום מאז
                if ($techAnswer !== false && $techAnswer !== (string) ($job['current_save_value'] ?? '')) {
                    if (sendMailTechCare($job, $techAnswer)) {
                        incrementRunCount($pdo, $job['id']);
                        setJobDone($pdo, $job['id']);
                        $sent++;
                    } else { $errors++; $runLog[] = jobFailMsg($job, 'שליחת מייל נכשלה'); }
                }
                break;

            // chechOrderNote — לא פעיל, API לא תקין
        }
        } catch (\Throwable $e) {
            $errors++;
            $runLog[] = jobFailMsg($job, get_class($e) . ': ' . $e->getMessage() . ' @' . basename($e->getFile()) . ':' . $e->getLine());
            error_log("[Cron1Hr] automation {$job['id']}: " . $e->getMessage());
        }
    }

    if ($sent > 0)   $runLog[] = "מיילים: {$sent}";
    if ($errors > 0) $runLog[] = "שגיאות שליחה: {$errors}";
}

/** שורת לוג לכשל באוטומציה: מזהה, סוג, ערך, סיבה (+ שגיאת PHP האחרונה אם יש) */
function jobFailMsg(array $job, string $reason): string
{
    $err = error_get_last();
    $msg = "אוטומציה #{$job['id']} ({$job['type_of_job']}, {$job['value_of_type']}): {$reason}";
    if ($err && !empty($err['message'])) {
        $msg .= ' | PHP: ' . $err['message'];
    }
    return mb_substr($msg, 0, 400);
}

// ── Wizenet API ──────────────────────────────────────────────────────────────

/** GET ל-Wizenet עם timeout. מחזיר מערך, או null בכשל רשת / תשובה לא תקינה */
function wizeGet(string $url): ?array
{
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'method' => 'GET']]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || $raw === '') {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function getCallStatus(string $callId): string|false
{
    $data = wizeGet("https://bug.wizenet.co.il/wizeapi/?func=wizeApp_getBICalls&token=ABUltIBvgMHYUg6NPaZ4cWA2p5467Jb&callid={$callId}");
    return !empty($data) && isset($data[0]['statusID']) ? (string) $data[0]['statusID'] : false;
}

function getTechCare(string $callId): string|false
{
    return \Models\AutomationModel::latestTechAnswer($callId);
}

function getCallsByPhone(string $phone, string $since): array|false
{
    $dateFrom = (new DateTime($since))->format('d/m/Y');
    $dateTo   = date('d/m/Y');
    $data     = wizeGet("https://bug.wizenet.co.il/wizeapi/?func=wizeApp_getBICalls&token=ABUltIBvgMHYUg6NPaZ4cWA2p5467Jb&ccell={$phone}&dateFrom={$dateFrom}&dateTo={$dateTo}");
    if (empty($data)) {
        return false;
    }
    $result = [];
    foreach ($data as $row) {
        $result[$row['CallID']] = ['stId' => $row['statusID'], 'stName' => $row['statusName']];
    }
    return $result;
}

// ── cron_log ─────────────────────────────────────────────────────────────────

function cronLog(PDO $pdo, string $action, string $status = 'ok', string $details = ''): void
{
    $pdo->prepare(
        "INSERT INTO cron_log (cron_name, action, status, details) VALUES ('cron_1hr', ?, ?, ?)"
    )->execute([$action, $status, $details]);
}

// ── DB helpers ───────────────────────────────────────────────────────────────

function incrementRunCount(PDO $pdo, int $id): void
{
    $pdo->prepare('UPDATE automations SET count_run = count_run + 1 WHERE id = ?')->execute([$id]);
}

function setJobDone(PDO $pdo, int $id): void
{
    $pdo->prepare(
        "UPDATE automations SET is_active = 0, status_of_job = 'נסגר לאחר ביצוע', status_change_time = NOW() WHERE id = ?"
    )->execute([$id]);
}

function updateSavedValue(PDO $pdo, int $id, string $value): void
{
    $pdo->prepare('UPDATE automations SET current_save_value = ? WHERE id = ?')->execute([$value, $id]);
}

function translateStatus(string $statusId): string
{
    return (string) (DB::v1Value('SELECT statusDesc FROM callStatus WHERE statuscallid = ? LIMIT 1', [$statusId]) ?: $statusId);
}

// ── שליחת מיילים (תבנית + שליחה משותפות: Core\MailTemplate) ───────────────────

/** תיבת "הודעה" מהמשתמש — רק אם הוזנה */
function userMsgBox(array $job): string
{
    return !empty($job['msg_from_user']) ? Mail::messageBox('הודעה', (string) $job['msg_from_user']) : '';
}

function sendMailNotifyOnChange(array $job, string $savedLabel, string $currentLabel): bool
{
    $subject = '[התראה אוטומטית] השתנה סטטוס עבור: ' . $job['value_of_type'];

    $body  = Mail::greeting($job['user_name'], 'אוטומציית התראה על שינוי סטטוס הופעלה.');
    $body .= Mail::details(
        Mail::row('מספר קריאה', Mail::e($job['value_of_type'])),
        $savedLabel ? Mail::row('סטטוס קודם', Mail::e($savedLabel)) : '',
        Mail::row('סטטוס נוכחי', '<span style="color:#4f7fff;">' . Mail::e($currentLabel) . '</span>')
    );
    $body .= userMsgBox($job);

    return Mail::send(
        [$job['user_mail'], $job['mailto']], $subject,
        Mail::wrap($subject, MAIL_BADGE, $body),
        ['cc' => $job['cc_mail']]
    );
}

function sendMailOpenByPhone(array $job, string $callsList): bool
{
    $subject = '[התראה אוטומטית] נפתחה קריאה עפ"י טלפון: ' . $job['value_of_type'];

    $body  = Mail::greeting($job['user_name'], 'אוטומציית התראה על פתיחת קריאה לפי טלפון הופעלה.');
    $body .= Mail::details(
        Mail::row('טלפון', Mail::e($job['value_of_type'])),
        Mail::row('קריאות שנפתחו', '<span style="color:#4f7fff;">' . Mail::e($callsList) . '</span>')
    );
    $body .= userMsgBox($job);

    return Mail::send($job['user_mail'], $subject, Mail::wrap($subject, MAIL_BADGE, $body), ['cc' => $job['cc_mail']]);
}

function sendMailTechCare(array $job, string $techAnswer): bool
{
    $link    = 'https://bug.wizenet.co.il/serviceControl.aspx?control=modulesCustom%2fbug%2fCallDetailsTech&CallID=' . urlencode($job['value_of_type']);
    $subject = '[התראה אוטומטית] טכנאי עדכן טיפול עבור: ' . $job['value_of_type'];

    $body  = Mail::greeting($job['user_name'], 'אוטומציית התראה על עדכון טכנאי הופעלה.');
    $body .= Mail::details(
        Mail::row('מספר קריאה', '<a href="' . Mail::e($link) . '" style="color:#4f7fff;text-decoration:none;">' . Mail::e($job['value_of_type']) . '</a>'),
        Mail::row('עדכון הטכנאי', Mail::e($techAnswer))
    );
    $body .= userMsgBox($job);

    return Mail::send($job['user_mail'], $subject, Mail::wrap($subject, MAIL_BADGE, $body), ['cc' => $job['cc_mail']]);
}

function sendMailExpired(array $job): bool
{
    $subject = '[התראה אוטומטית] ' . $job['value_of_type'] . ' — משימה נסגרה ללא ביצוע';

    $body  = Mail::greeting($job['user_name'], 'אוטומציה עבורך הסתיימה ללא ביצוע.');
    $body .= Mail::details(
        Mail::row('אוטומציה', Mail::e($job['value_of_type'])),
        Mail::row('סיבה', '<span style="color:#e74c3c;">עבר תאריך התפוגה</span>')
    );
    $body .= userMsgBox($job);

    return Mail::send($job['mailto'], $subject, Mail::wrap($subject, MAIL_BADGE, $body), ['cc' => $job['cc_mail']]);
}

// ── SLA Notifications ────────────────────────────────────────────────────────

function notifyOverdueTasks(PDO $pdo, array &$runLog): void
{
    $stmt = $pdo->query("
        SELECT t.id, t.title, t.sla_days, t.created_at,
               t.assigned_user_id,
               u.email  AS assignee_email,
               CONCAT(u.first_name,' ',u.last_name) AS assignee_name
        FROM tasks t
        JOIN users u ON u.id = t.assigned_user_id
        WHERE t.is_active = 1
          AND t.sla_notified_at IS NULL
          AND DATE_ADD(t.created_at, INTERVAL t.sla_days DAY) < NOW()
    ");
    $tasks = $stmt->fetchAll();

    if (empty($tasks)) return;

    $appUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://moked-net.co.il';

    foreach ($tasks as $task) {
        $taskId   = (int)$task['id'];
        $title    = $task['title'];
        $slaDays  = (int)$task['sla_days'];
        $created  = date('d/m/Y', strtotime($task['created_at']));

        // Collect watcher emails
        $watchers = $pdo->prepare("
            SELECT u.email FROM task_watchers tw
            JOIN users u ON u.id = tw.user_id
            WHERE tw.task_id = ? AND u.is_active = 1 AND u.email != ''
        ");
        $watchers->execute([$taskId]);
        $watcherEmails = $watchers->fetchAll(PDO::FETCH_COLUMN);

        $subject = "[SLA] משימה #{$taskId} עברה את מועד הטיפול";

        $mbody  = Mail::greeting($task['assignee_name'], 'המשימה הבאה עברה את יעד ה-SLA ומחכה לטיפול.');
        $mbody .= Mail::details(
            Mail::row('משימה', '#' . $taskId . ' — ' . Mail::e($title)),
            Mail::row('יעד SLA', $slaDays . ' ימים'),
            Mail::row('נפתחה ב', $created)
        );
        $mbody .= Mail::button($appUrl . '/tasks?filter=overdue', 'לצפייה במשימות שעברו SLA ←');

        Mail::send(
            $task['assignee_email'], $subject,
            Mail::wrap($subject, MAIL_BADGE, $mbody),
            ['cc' => array_unique(array_filter($watcherEmails))]
        );

        $pdo->prepare("UPDATE tasks SET sla_notified_at=NOW() WHERE id=?")->execute([$taskId]);
    }

    $runLog[] = "SLA: " . count($tasks);
}

// ── התראות חנויות שנשארו פתוחות מעל שבועיים ─────────────────────────────────

function notifyStaleStoreAlerts(PDO $pdo, array &$runLog): void
{
    $stmt = $pdo->query("
        SELECT id, store_num, name, alert_note, alert_updated_at
        FROM stores
        WHERE is_active = 1
          AND alert_note IS NOT NULL AND alert_note != ''
          AND alert_updated_at IS NOT NULL
          AND alert_updated_at < DATE_SUB(NOW(), INTERVAL 14 DAY)
        ORDER BY alert_updated_at ASC
    ");
    $stores = $stmt->fetchAll();

    if (empty($stores)) return;

    $ids = array_column($stores, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = $pdo->prepare(
        "SELECT a1.entity_id, a1.user_name
         FROM activity_log a1
         INNER JOIN (
             SELECT entity_id, MAX(id) AS max_id
             FROM activity_log
             WHERE entity_type='store' AND action='store.update'
               AND diff_json LIKE '%\"alert_note\"%'
               AND entity_id IN ($placeholders)
             GROUP BY entity_id
         ) latest ON latest.entity_id = a1.entity_id AND latest.max_id = a1.id"
    );
    $rows->execute($ids);
    $byId = array_column($rows->fetchAll(), 'user_name', 'entity_id');

    $appUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://alon.alexisdeveloping.com';

    $f  = 'font-family:Tahoma,Arial,sans-serif;text-align:right;';
    $td = $f . 'padding:10px 8px;border-bottom:1px solid #eceef2;font-size:13px;vertical-align:top;';
    $th = $f . 'padding:0 8px 8px;font-size:12px;color:#8a8fa3;border-bottom:2px solid #dcdfe6;font-weight:600;';

    $rowsHtml = '';
    foreach ($stores as $s) {
        $days      = (int) floor((time() - strtotime($s['alert_updated_at'])) / 86400);
        $storeName = $s['name'] . ($s['store_num'] ? ' #' . $s['store_num'] : '');
        $storeLink = $appUrl . '/stores/id/' . (int) $s['id'];
        $author    = $byId[$s['id']] ?? 'לא ידוע';
        $note      = mb_substr((string) $s['alert_note'], 0, 120) . (mb_strlen((string) $s['alert_note']) > 120 ? '…' : '');

        $rowsHtml .= '<tr>'
            . '<td align="right" dir="rtl" style="' . $td . 'font-weight:600;"><a href="' . Mail::e($storeLink) . '" style="color:#4f7fff;text-decoration:none;">' . Mail::e($storeName) . '</a></td>'
            . '<td align="right" dir="rtl" style="' . $td . 'color:#5a5e78;">' . Mail::e($note) . '</td>'
            . '<td align="right" dir="rtl" style="' . $td . 'color:#8a8fa3;white-space:nowrap;">' . Mail::e($author) . '</td>'
            . '<td align="right" dir="rtl" style="' . $td . 'color:#e74c3c;font-weight:600;white-space:nowrap;">' . $days . ' ימים</td>'
            . '<td align="right" dir="rtl" style="' . $td . 'white-space:nowrap;"><a href="' . Mail::e($storeLink) . '" style="color:#4f7fff;text-decoration:none;font-weight:600;">עריכה ←</a></td>'
            . '</tr>';
    }

    $subject = '[התראה אוטומטית] ' . count($stores) . ' התראות חנויות פתוחות מעל שבועיים';

    $mbody  = Mail::heading(
        'התראות חנויות שממתינות לטיפול',
        'ל-' . count($stores) . ' חנויות יש התראה פעילה (שדה "התראה" בכרטיס החנות) שלא עודכנה כבר מעל שבועיים. ייתכן שהנושא כבר טופל ופשוט נשכח לסגור את ההתראה — מומלץ לבדוק ולשקול לסגור את מי שכבר לא רלוונטי.'
    );
    $mbody .= '<table role="presentation" dir="rtl" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;margin:0 0 24px;">'
        . '<tr>'
        . '<td align="right" dir="rtl" style="' . $th . '">חנות</td>'
        . '<td align="right" dir="rtl" style="' . $th . '">התראה</td>'
        . '<td align="right" dir="rtl" style="' . $th . '">ע"י</td>'
        . '<td align="right" dir="rtl" style="' . $th . '">משך זמן נוכחי</td>'
        . '<td align="right" dir="rtl" style="' . $th . '"></td>'
        . '</tr>'
        . $rowsHtml
        . '</table>';
    $mbody .= Mail::button($appUrl . '/dashboard', 'לדשבורד — צפייה בהתראות ←');

    Mail::send(
        'eyal@bug.co.il,gild@bug.co.il', $subject,
        Mail::wrap($subject, MAIL_BADGE, $mbody, 680),
        ['bcc' => 'aloncomputer@gmail.com']
    );

    $runLog[] = "התראות ישנות: " . count($stores);
}
