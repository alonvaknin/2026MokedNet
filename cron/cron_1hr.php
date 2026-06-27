<?php
// רץ כל שעה — מעבד אוטומציות פעילות מ-alon_db2
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\DB;

$pdo   = DB::get();   // alon_db2 — טבלת automations
$pdoV1 = DB::v1();    // alon_db  — callStatus

$runLog = [];

closeExpiredJobs($pdo, $runLog);
deactivateMaxRunJobs($pdo, $runLog);
processActiveJobs($pdo, $pdoV1, $runLog);
notifyOverdueTasks($pdo, $runLog);

cronLog($pdo, 'run', 'ok', !empty($runLog) ? implode(' | ', $runLog) : 'אין פעולות');

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

function processActiveJobs(PDO $pdo, PDO $pdoV1, array &$runLog): void
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
        switch ($job['type_of_job']) {

            case 'notifyOnChangeTo':
                $currentStatus = getCallStatus($job['value_of_type']);
                if ($currentStatus === false) break;
                if ((int) $job['run_even_diff'] === 1) {
                    if ($job['current_save_value'] !== $currentStatus) {
                        $savedLabel   = translateStatus($pdoV1, (string) $job['current_save_value']);
                        $currentLabel = translateStatus($pdoV1, $currentStatus);
                        if (sendMailNotifyOnChange($job, $savedLabel, $currentLabel)) {
                            incrementRunCount($pdo, $job['id']);
                            updateSavedValue($pdo, $job['id'], $currentStatus);
                            $sent++;
                        } else { $errors++; }
                    }
                } else {
                    if ($job['condition_of_type'] === $currentStatus) {
                        if (sendMailNotifyOnChange($job, '', translateStatus($pdoV1, $currentStatus))) {
                            incrementRunCount($pdo, $job['id']);
                            setJobDone($pdo, $job['id']);
                            $sent++;
                        } else { $errors++; }
                    }
                }
                break;

            case 'openCaseByPhone':
                $calls = getCallsByPhone($job['value_of_type'], $job['created_at']);
                if ($calls !== false) {
                    if (sendMailOpenByPhone($job, implode(', ', array_keys($calls)))) {
                        incrementRunCount($pdo, $job['id']);
                        $sent++;
                    } else { $errors++; }
                }
                break;

            case 'techCare':
                $techAnswer = getTechCare($job['value_of_type']);
                if ($techAnswer !== false) {
                    if (sendMailTechCare($job, $techAnswer)) {
                        incrementRunCount($pdo, $job['id']);
                        setJobDone($pdo, $job['id']);
                        $sent++;
                    } else { $errors++; }
                }
                break;

            // chechOrderNote — לא פעיל, API לא תקין
        }
    }

    if ($sent > 0)   $runLog[] = "מיילים: {$sent}";
    if ($errors > 0) $runLog[] = "שגיאות שליחה: {$errors}";
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

    $raw = $data[0]['resolution'] ?? '';
    if (empty($raw)) {
        return false;
    }

    // Split by ## markers (same logic as WizenetController::normalizeCall)
    $parts = preg_split('/##+/', $raw);
    $parts = array_values(array_filter(
        array_map(fn($p) => trim(preg_replace('/\s+/', ' ', $p)), $parts),
        fn($p) => strlen(preg_replace('/[\s:;,#]/', '', $p)) > 4
    ));

    // Walk entries newest-first, return first non-empty תשובה:
    foreach (array_reverse($parts) as $entry) {
        if (preg_match('/תשובה:\s*([^,;]+)/', $entry, $m)) {
            $answer = trim($m[1]);
            if ($answer !== '') {
                return $answer;
            }
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

function translateStatus(PDO $pdoV1, string $statusId): string
{
    $stmt = $pdoV1->prepare('SELECT statusDesc FROM callStatus WHERE statuscallid = ? LIMIT 1');
    $stmt->execute([$statusId]);
    return (string) ($stmt->fetchColumn() ?: $statusId);
}

// ── שליחת מיילים ─────────────────────────────────────────────────────────────

function buildHeaders(?string $cc = ''): string
{
    $h  = "From: מוקד-נט <moked-net-noreply@alexisdeveloping.com>\r\n";
    $h .= "Reply-To: moked-net-noreply@alexisdeveloping.com\r\n";
    $h .= "MIME-Version: 1.0\r\n";
    $h .= "Content-Type: text/html; charset=utf-8\r\n";
    if (!empty($cc)) {
        $h .= "Cc: {$cc}\r\n";
    }
    return $h;
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
        . '<span style="font-size:14px;color:rgba(255,255,255,.75);margin-right:12px;">התראה אוטומטית</span>'
        . '</td></tr>'
        . '<tr><td style="padding:32px;">' . $body . '</td></tr>'
        . '<tr><td style="background:#13161e;padding:16px 32px;text-align:right;">'
        . '<span style="font-size:12px;color:#5a5e78;">מופעל באמצעות מערכת מוקד-נט</span>'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

function row(string $label, string $value): string
{
    return '<tr>'
        . '<td style="font-size:13px;color:#5a5e78;padding:6px 0;width:120px;">' . $label . '</td>'
        . '<td style="font-size:14px;color:#e8eaf0;padding:6px 0;font-weight:600;">' . $value . '</td>'
        . '</tr>';
}

function sendMailNotifyOnChange(array $job, string $savedLabel, string $currentLabel): bool
{
    $to      = $job['user_mail'] . ';' . $job['mailto'];
    $subject = '[התראה אוטומטית] השתנה סטטוס עבור: ' . $job['value_of_type'];

    $body  = '<p style="font-size:16px;margin:0 0 4px;">שלום, <b>' . htmlspecialchars($job['user_name']) . '</b></p>';
    $body .= '<p style="font-size:14px;color:#b0b3c6;margin:0 0 24px;">אוטומציית התראה על שינוי סטטוס הופעלה.</p>';
    $body .= '<table cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 24px;">';
    $body .= row('מספר קריאה', htmlspecialchars($job['value_of_type']));
    if ($savedLabel) $body .= row('סטטוס קודם', htmlspecialchars($savedLabel));
    $body .= row('סטטוס נוכחי', '<span style="color:#4f7fff;">' . htmlspecialchars($currentLabel) . '</span>');
    $body .= '</table>';
    if (!empty($job['msg_from_user'])) {
        $body .= '<div style="background:#1e2435;border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:14px 16px;">';
        $body .= '<p style="font-size:12px;color:#5a5e78;margin:0 0 6px;">הודעה</p>';
        $body .= '<p style="font-size:15px;color:#e8eaf0;margin:0;">' . htmlspecialchars($job['msg_from_user']) . '</p>';
        $body .= '</div>';
    }

    return mail($to, $subject, mailWrap($subject, $body), buildHeaders($job['cc_mail']));
}

function sendMailOpenByPhone(array $job, string $callsList): bool
{
    $to      = $job['user_mail'];
    $subject = '[התראה אוטומטית] נפתחה קריאה עפ"י טלפון: ' . $job['value_of_type'];

    $body  = '<p style="font-size:16px;margin:0 0 4px;">שלום, <b>' . htmlspecialchars($job['user_name']) . '</b></p>';
    $body .= '<p style="font-size:14px;color:#b0b3c6;margin:0 0 24px;">אוטומציית התראה על פתיחת קריאה לפי טלפון הופעלה.</p>';
    $body .= '<table cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 24px;">';
    $body .= row('טלפון', htmlspecialchars($job['value_of_type']));
    $body .= row('קריאות שנפתחו', '<span style="color:#4f7fff;">' . htmlspecialchars($callsList) . '</span>');
    $body .= '</table>';
    if (!empty($job['msg_from_user'])) {
        $body .= '<div style="background:#1e2435;border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:14px 16px;">';
        $body .= '<p style="font-size:12px;color:#5a5e78;margin:0 0 6px;">הודעה</p>';
        $body .= '<p style="font-size:15px;color:#e8eaf0;margin:0;">' . htmlspecialchars($job['msg_from_user']) . '</p>';
        $body .= '</div>';
    }

    return mail($to, $subject, mailWrap($subject, $body), buildHeaders($job['cc_mail']));
}

function sendMailTechCare(array $job, string $techAnswer): bool
{
    $to      = $job['user_mail'];
    $link    = "https://bug.wizenet.co.il/serviceControl.aspx?control=modulesCustom%2fbug%2fCallDetailsTech&CallID=" . urlencode($job['value_of_type']);
    $subject = '[התראה אוטומטית] טכנאי עדכן טיפול עבור: ' . $job['value_of_type'];

    $body  = '<p style="font-size:16px;margin:0 0 4px;">שלום, <b>' . htmlspecialchars($job['user_name']) . '</b></p>';
    $body .= '<p style="font-size:14px;color:#b0b3c6;margin:0 0 24px;">אוטומציית התראה על עדכון טכנאי הופעלה.</p>';
    $body .= '<table cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 24px;">';
    $body .= row('מספר קריאה', '<a href="' . htmlspecialchars($link) . '" style="color:#4f7fff;text-decoration:none;">' . htmlspecialchars($job['value_of_type']) . '</a>');
    $body .= row('עדכון הטכנאי', htmlspecialchars($techAnswer));
    $body .= '</table>';
    if (!empty($job['msg_from_user'])) {
        $body .= '<div style="background:#1e2435;border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:14px 16px;">';
        $body .= '<p style="font-size:12px;color:#5a5e78;margin:0 0 6px;">הודעה</p>';
        $body .= '<p style="font-size:15px;color:#e8eaf0;margin:0;">' . htmlspecialchars($job['msg_from_user']) . '</p>';
        $body .= '</div>';
    }

    return mail($to, $subject, mailWrap($subject, $body), buildHeaders($job['cc_mail']));
}

function sendMailExpired(array $job): bool
{
    $to      = $job['mailto'];
    $subject = '[התראה אוטומטית] ' . $job['value_of_type'] . ' — משימה נסגרה ללא ביצוע';

    $body  = '<p style="font-size:16px;margin:0 0 4px;">שלום, <b>' . htmlspecialchars($job['user_name']) . '</b></p>';
    $body .= '<p style="font-size:14px;color:#b0b3c6;margin:0 0 24px;">אוטומציה עבורך הסתיימה ללא ביצוע.</p>';
    $body .= '<table cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 24px;">';
    $body .= row('אוטומציה', htmlspecialchars($job['value_of_type']));
    $body .= row('סיבה', '<span style="color:#e74c3c;">עבר תאריך התפוגה</span>');
    $body .= '</table>';
    if (!empty($job['msg_from_user'])) {
        $body .= '<div style="background:#1e2435;border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:14px 16px;">';
        $body .= '<p style="font-size:12px;color:#5a5e78;margin:0 0 6px;">הודעה</p>';
        $body .= '<p style="font-size:15px;color:#e8eaf0;margin:0;">' . htmlspecialchars($job['msg_from_user']) . '</p>';
        $body .= '</div>';
    }

    return mail($to, $subject, mailWrap($subject, $body), buildHeaders($job['cc_mail']));
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
        $mbody  = '<p style="font-size:16px;margin:0 0 4px;">שלום, <b>' . htmlspecialchars($task['assignee_name']) . '</b></p>';
        $mbody .= '<p style="font-size:14px;color:#b0b3c6;margin:0 0 24px;">המשימה הבאה עברה את יעד ה-SLA ומחכה לטיפול.</p>';
        $mbody .= '<table cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 24px;">';
        $mbody .= row('משימה', '#' . $taskId . ' — ' . htmlspecialchars($title));
        $mbody .= row('יעד SLA', $slaDays . ' ימים');
        $mbody .= row('נפתחה ב', $created);
        $mbody .= '</table>';
        $mbody .= '<table cellpadding="0" cellspacing="0" style="margin:0 0 8px;">';
        $mbody .= '<tr><td style="background:#4f7fff;border-radius:8px;padding:0;">';
        $mbody .= '<a href="' . htmlspecialchars($appUrl) . '/tasks?filter=overdue" style="display:block;padding:12px 28px;color:#fff;font-size:15px;font-weight:600;text-decoration:none;">לצפייה במשימות שעברו SLA ←</a>';
        $mbody .= '</td></tr></table>';

        $ccList = implode(',', array_unique(array_filter($watcherEmails)));
        mail($task['assignee_email'], $subject, mailWrap($subject, $mbody), buildHeaders($ccList));

        $pdo->prepare("UPDATE tasks SET sla_notified_at=NOW() WHERE id=?")->execute([$taskId]);
    }

    $runLog[] = "SLA: " . count($tasks);
}
