<?php
// רץ כל שעה — מעבד אוטומציות פעילות מ-alon_db2
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\DB;

$pdo   = DB::get();   // alon_db2 — טבלת automations
$pdoV1 = DB::v1();    // alon_db  — callStatus

closeExpiredJobs($pdo);
deactivateMaxRunJobs($pdo);
processActiveJobs($pdo, $pdoV1);
notifyOverdueTasks($pdo);

// ── סגירת משימות שעבר תאריך תפוגתן ─────────────────────────────────────────

function closeExpiredJobs(PDO $pdo): void
{
    $stmt = $pdo->prepare(
        "SELECT id, user_name, user_mail, mailto, cc_mail, msg_from_user,
                value_of_type, type_of_job
         FROM automations
         WHERE upto_date < NOW() AND is_active = 1"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $pdo->prepare(
            "UPDATE automations
             SET is_active = 0, status_of_job = 'משימה נסגרה ללא ביצוע - עבר תאריך התפוגה',
                 status_change_time = NOW()
             WHERE id = ?"
        )->execute([$row['id']]);

        sendMailExpired($row);
    }
}

// ── סגירת משימות שהגיעו ל-maxRun ────────────────────────────────────────────

function deactivateMaxRunJobs(PDO $pdo): void
{
    $pdo->exec(
        "UPDATE automations
         SET is_active = 0, status_of_job = 'נסגר לאחר ביצוע', status_change_time = NOW()
         WHERE count_run >= max_run AND is_active = 1"
    );
}

// ── עיבוד משימות פעילות ──────────────────────────────────────────────────────

function processActiveJobs(PDO $pdo, PDO $pdoV1): void
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

    foreach ($jobs as $job) {
        switch ($job['type_of_job']) {

            case 'notifyOnChangeTo':
                $currentStatus = getCallStatus($job['value_of_type']);
                if ($currentStatus === false) {
                    break;
                }
                if ((int) $job['run_even_diff'] === 1) {
                    // התרעה על כל שינוי סטטוס
                    if ($job['current_save_value'] !== $currentStatus) {
                        $savedLabel   = translateStatus($pdoV1, (string) $job['current_save_value']);
                        $currentLabel = translateStatus($pdoV1, $currentStatus);
                        if (sendMailNotifyOnChange($job, $savedLabel, $currentLabel)) {
                            incrementRunCount($pdo, $job['id']);
                            updateSavedValue($pdo, $job['id'], $currentStatus);
                        }
                    }
                } else {
                    // התרעה כשמגיע לסטטוס ספציפי
                    if ($job['condition_of_type'] === $currentStatus) {
                        if (sendMailNotifyOnChange($job, '', translateStatus($pdoV1, $currentStatus))) {
                            incrementRunCount($pdo, $job['id']);
                            setJobDone($pdo, $job['id']);
                        }
                    }
                }
                break;

            case 'openCaseByPhone':
                $calls = getCallsByPhone($job['value_of_type'], $job['created_at']);
                if ($calls !== false) {
                    $callsList = implode(', ', array_keys($calls));
                    if (sendMailOpenByPhone($job, $callsList)) {
                        incrementRunCount($pdo, $job['id']);
                    }
                }
                break;

            case 'techCare':
                $techAnswer = getTechCare($job['value_of_type']);
                if ($techAnswer !== false) {
                    if (sendMailTechCare($job, $techAnswer)) {
                        incrementRunCount($pdo, $job['id']);
                        setJobDone($pdo, $job['id']);
                    }
                }
                break;

            // chechOrderNote — לא פעיל, API לא תקין
        }
    }
}

// ── Wizenet API ──────────────────────────────────────────────────────────────

function getCallStatus(string $callId): string|false
{
    $url  = "https://bug.wizenet.co.il/wizeapi/?func=wizeApp_getBICalls&token=ABUltIBvgMHYUg6NPaZ4cWA2p5467Jb&callid={$callId}";
    $data = json_decode(file_get_contents($url), true);
    return !empty($data) ? (string) $data[0]['statusID'] : false;
}

function getTechCare(string $callId): string|false
{
    $url  = "https://bug.wizenet.co.il/wizeapi/?func=wizeApp_getBICalls&token=ABUltIBvgMHYUg6NPaZ4cWA2p5467Jb&callid={$callId}";
    $data = json_decode(file_get_contents($url), true);
    if (empty($data)) {
        return false;
    }
    $techCare = json_decode(str_replace('\\', '-', $data[0]['CallSubjectList']));
    foreach ($techCare as $row) {
        if (!empty($row->CSLdesc)) {
            return $row->CSLdesc;
        }
    }
    return false;
}

function getCallsByPhone(string $phone, string $since): array|false
{
    $dateFrom = (new DateTime($since))->format('d/m/Y');
    $dateTo   = date('d/m/Y');
    $url      = "https://bug.wizenet.co.il/wizeapi/?func=wizeApp_getBICalls&token=ABUltIBvgMHYUg6NPaZ4cWA2p5467Jb&ccell={$phone}&dateFrom={$dateFrom}&dateTo={$dateTo}";
    $data     = json_decode(file_get_contents($url), true);
    if (empty($data)) {
        return false;
    }
    $result = [];
    foreach ($data as $row) {
        $result[$row['CallID']] = ['stId' => $row['statusID'], 'stName' => $row['statusName']];
    }
    return $result;
}

// ── DB helpers ───────────────────────────────────────────────────────────────

function incrementRunCount(PDO $pdo, int $id): void
{
    $pdo->prepare('UPDATE automations SET run_count = run_count + 1 WHERE id = ?')->execute([$id]);
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

function translateStatus(PDO $pdoV1, string $statusId): string
{
    $stmt = $pdoV1->prepare('SELECT statusDesc FROM callStatus WHERE statuscallid = ? LIMIT 1');
    $stmt->execute([$statusId]);
    return (string) ($stmt->fetchColumn() ?: $statusId);
}

// ── שליחת מיילים ─────────────────────────────────────────────────────────────

function buildHeaders(string $cc = ''): string
{
    $h  = "From: מוקד-נט <moked-net-noreply@alexisdeveloping.com>\r\n";
    $h .= "Reply-To: moked-net-noreply@alexisdeveloping.com\r\n";
    $h .= 'MIME-Version: 1.0' . "\r\n";
    $h .= 'Content-type: text/html; charset=utf-8' . "\r\n";
    if (!empty($cc)) {
        $h .= "Cc: {$cc}\r\n";
    }
    return $h;
}

function bodyWrap(string $content): string
{
    return '<html lang="HE" dir="rtl" style="font-family:Tahoma,Arial;"><head></head>'
        . '<body style="text-align:right;direction:rtl;">'
        . $content
        . '<p><span style="color:#999;">מופעל באמצעות מערכת מוקד-נט</span></p>'
        . '</body></html>';
}

function sendMailNotifyOnChange(array $job, string $savedLabel, string $currentLabel): bool
{
    $to      = $job['user_mail'] . ';' . $job['mailto'];
    $subject = '[התראה אוטומטית] השתנה סטטוס עבור: ' . $job['value_of_type'];
    $body    = "<p><b>{$job['user_name']}</b>, בחר\\ה: אוטומציית התראה על שינוי סטטוס"
        . " עבור: <b>{$job['value_of_type']}</b></p>"
        . ($savedLabel ? "<p>סטטוס קודם: <b>{$savedLabel}</b></p>" : '')
        . "<p>סטטוס נוכחי: <b>{$currentLabel}</b></p>"
        . "<p>הודעה: <span style='font-size:15pt;'>{$job['msg_from_user']}</span></p>";
    return mail($to, $subject, bodyWrap($body), buildHeaders($job['cc_mail']));
}

function sendMailOpenByPhone(array $job, string $callsList): bool
{
    $to      = $job['user_mail'];
    $subject = '[התראה אוטומטית] נפתחה קריאה עפ"י טלפון: ' . $job['value_of_type'];
    $body    = "<p><b>{$job['user_name']}</b>, בחר\\ה: אוטומציית התראה על פתיחת קריאה"
        . " עבור: {$job['value_of_type']}</p>"
        . "<p>נפתחו קריאות שירות: <b>{$callsList}</b></p>"
        . "<p>הודעה: <span style='font-size:15pt;'>{$job['msg_from_user']}</span></p>";
    return mail($to, $subject, bodyWrap($body), buildHeaders($job['cc_mail']));
}

function sendMailTechCare(array $job, string $techAnswer): bool
{
    $to      = $job['user_mail'];
    $link    = "https://bug.wizenet.co.il/serviceControl.aspx?control=modulesCustom%2fbug%2fCallDetailsTech&CallID={$job['value_of_type']}";
    $subject = '[התראה אוטומטית] טכנאי עדכן טיפול עבור: ' . $job['value_of_type'];
    $body    = "<p><b>{$job['user_name']}</b>, בחר\\ה: אוטומציית התראה על עדכון טכנאי"
        . " עבור: <a href='{$link}'>{$job['value_of_type']}</a></p>"
        . "<p>עדכון הטכנאי: {$techAnswer}</p>"
        . "<p>הודעה: <span style='font-size:15pt;'>{$job['msg_from_user']}</span></p>";
    return mail($to, $subject, bodyWrap($body), buildHeaders($job['cc_mail']));
}

function sendMailExpired(array $job): bool
{
    $to      = $job['mailto'];
    $subject = '[התראה אוטומטית] ' . $job['value_of_type'] . ' משימה נסגרה ללא ביצוע';
    $body    = "<p><b>{$job['user_name']}</b>, אוטומציה עבור: {$job['value_of_type']}</p>"
        . "<p>המשימה נסגרה אוטומטית — עבר תאריך התפוגה.</p>"
        . "<p>הודעה: <span style='font-size:15pt;'>{$job['msg_from_user']}</span></p>";
    return mail($to, $subject, bodyWrap($body), buildHeaders($job['cc_mail']));
}

// ── SLA Notifications ────────────────────────────────────────────────────────

function notifyOverdueTasks(PDO $pdo): void
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
        $body    = "<p>שלום <b>{$task['assignee_name']}</b>,</p>"
                 . "<p>המשימה <b>\"" . htmlspecialchars($title, ENT_QUOTES) . "\"</b>"
                 . " עברה את יעד ה-SLA ({$slaDays} ימים).</p>"
                 . "<p>נפתחה ב: {$created}</p>"
                 . "<p><a href=\"{$appUrl}/tasks?filter=overdue\" style=\"color:#5b8dee;\">לצפייה ולטיפול במשימות שעברו SLA</a></p>";

        $ccList = implode(',', array_unique(array_filter($watcherEmails)));
        mail($task['assignee_email'], $subject, bodyWrap($body), buildHeaders($ccList));

        $pdo->prepare("UPDATE tasks SET sla_notified_at=NOW() WHERE id=?")->execute([$taskId]);
    }

    echo "✓ SLA notifications sent: " . count($tasks) . "\n";
}
