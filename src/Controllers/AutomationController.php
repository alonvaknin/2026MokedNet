<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\ActivityLog;
use Models\AutomationModel;

class AutomationController extends Controller
{
    // ── Page ─────────────────────────────────────────────────────────────────

    public function index(): void
    {
        $this->requireAuth();
        $canViewAll = Auth::can('automation.viewAll');
        $agents     = $canViewAll ? AutomationModel::agents() : [];

        $this->view('pages/automation/index', compact('canViewAll', 'agents'));
    }

    // ── API: list ─────────────────────────────────────────────────────────────

    public function apiList(): void
    {
        $this->requireAuth();

        $userId     = (int) $_SESSION['user_id'];
        $canViewAll = Auth::can('automation.viewAll');
        $all        = $canViewAll && ($this->get('all', '0') === '1');
        $agentId    = $canViewAll ? (int) $this->get('agent', 0) : 0;
        $search     = trim($this->get('q', ''));
        $status     = $this->get('status', '');
        $offset     = max(0, (int) $this->get('offset', 0));
        $limit      = min(200, max(1, (int) $this->get('limit', 50)));

        $result = AutomationModel::paginated(
            $userId, $all, $offset, $limit,
            agentId: $agentId,
            search:  $search,
            status:  $status,
        );

        $rows = array_map(function (array $row): array {
            $row['typeLabel']       = AutomationModel::typeLabel($row['typeOfJob']);
            $row['conditionLabel']  = $row['conditionOfType']
                ? AutomationModel::translateStatus($row['conditionOfType'])
                : '';
            $row['addJobTimeFmt']   = $this->fmtDate($row['addJobTime']);
            $row['upToDateFmt']     = $this->fmtDate($row['UptoDate']);
            $row['statusChangeFmt'] = $this->fmtDate($row['statusChangeTime']);
            return $row;
        }, $result['rows']);

        $this->json(['rows' => $rows, 'total' => $result['total']]);
    }

    // ── API: statuses ─────────────────────────────────────────────────────────

    public function apiStatuses(): void
    {
        $this->requireAuth();
        $this->json(AutomationModel::callStatuses());
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId   = (int) $_SESSION['user_id'];
        $userName = $_SESSION['full_name'] ?? '';
        $userMail = Auth::user()['email']  ?? '';

        $type    = trim($this->post('typeOfJob', ''));
        $mailTo  = trim($this->post('mailTo', $userMail));
        $ccMail  = trim($this->post('Ccmail', ''));
        $msgUser = trim($this->post('msgUser', ''));
        $runEven = $this->post('runevenvaluechange', 'false') === 'true' ? 1 : 0;

        $base = [
            'userID'    => $userId,
            'userName'  => $userName,
            'userMail'  => $userMail,
            'mailto'    => $mailTo,
            'toCcmail'  => $ccMail ?: null,
            'msgFromUser'=> $msgUser ?: null,
            'maxRun'    => 1,
        ];

        try {
            switch ($type) {

                case AutomationModel::TYPE_NOTIFY_STATUS:
                    $caseNum  = trim($this->post('valueOfType', ''));
                    $statusId = trim($this->post('conditionOfType', ''));
                    if (!preg_match('/^\d{6}$/', $caseNum)) {
                        $this->json(['ok'=>false,'msg'=>'מספר קריאה חייב להיות בן 6 ספרות'], 422);
                    }
                    if (!$statusId) {
                        $this->json(['ok'=>false,'msg'=>'נא לבחור סטטוס'], 422);
                    }
                    $currentStatus = $this->fetchWizeStatus($caseNum);
                    if ($currentStatus === null) {
                        $this->json(['ok'=>false,'msg'=>'קריאה לא קיימת ב-Wizenet'], 404);
                    }
                    $newId = AutomationModel::create($base + [
                        'typeOfJob'              => $type,
                        'valueOfType'            => $caseNum,
                        'conditionOfType'        => $statusId,
                        'UptoDate'               => AutomationModel::expiryDate('month', 1),
                        'runevenvalueOfTypeDiff' => $runEven,
                        'currentSaveValue'       => $currentStatus,
                    ]);
                    break;

                case AutomationModel::TYPE_TECH_CARE:
                    $caseNum = trim($this->post('valueOfType', ''));
                    if (!preg_match('/^\d{6}$/', $caseNum)) {
                        $this->json(['ok'=>false,'msg'=>'מספר קריאה חייב להיות בן 6 ספרות'], 422);
                    }
                    if ($this->fetchWizeStatus($caseNum) === null) {
                        $this->json(['ok'=>false,'msg'=>'קריאה לא קיימת ב-Wizenet'], 404);
                    }
                    $newId = AutomationModel::create($base + [
                        'typeOfJob'   => $type,
                        'valueOfType' => $caseNum,
                        'UptoDate'    => AutomationModel::expiryDate('month', 1),
                    ]);
                    break;

                case AutomationModel::TYPE_OPEN_BY_PHONE:
                    $phone = preg_replace('/\D/', '', $this->post('cosCellNum', ''));
                    if (strlen($phone) !== 10) {
                        $this->json(['ok'=>false,'msg'=>'מספר טלפון חייב להיות בן 10 ספרות'], 422);
                    }
                    $newId = AutomationModel::create($base + [
                        'typeOfJob'   => $type,
                        'valueOfType' => $phone,
                        'UptoDate'    => AutomationModel::expiryDate('week', 1),
                    ]);
                    break;

                case AutomationModel::TYPE_ORDER_NOTE:
                    $orderNum = preg_replace('/\D/', '', $this->post('orderNum', ''));
                    if (strlen($orderNum) !== 6) {
                        $this->json(['ok'=>false,'msg'=>'מספר הזמנה חייב להיות בן 6 ספרות'], 422);
                    }
                    $noteData = $this->fetchOrderNote($orderNum);
                    if ($noteData['error'] !== 0) {
                        $this->json(['ok'=>false,'msg'=>$noteData['errorMsg']??'שגיאה בשליפת הזמנה'], 422);
                    }
                    $newId = AutomationModel::create($base + [
                        'typeOfJob'       => $type,
                        'valueOfType'     => $orderNum,
                        'conditionOfType' => $noteData['order_note'],
                        'UptoDate'        => AutomationModel::expiryDate('day', 3),
                    ]);
                    ActivityLog::log('automation.create', 'automation', $newId,
                        AutomationModel::typeLabel($type), "מייל: {$mailTo}");
                    $this->json(['ok'=>true,'msg'=>'משימה נוספה בהצלחה','order_note'=>$noteData['order_note']]);

                default:
                    $this->json(['ok'=>false,'msg'=>'סוג משימה לא תקין'], 422);
            }

        } catch (\Throwable $e) {
            error_log('[AutomationController::create] ' . $e->getMessage());
            $this->json(['ok'=>false,'msg'=>'שגיאת שרת: ' . $e->getMessage()], 500);
        }

        ActivityLog::log('automation.create', 'automation', $newId ?? null,
            AutomationModel::typeLabel($type), "מייל: {$mailTo}");
        $this->json(['ok'=>true,'msg'=>'משימה נוספה בהצלחה']);
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    public function cancel(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId  = (int) $_SESSION['user_id'];
        $isAdmin = Auth::can('automation.viewAll');
        $ok      = AutomationModel::cancel((int)$id, $userId, $isAdmin);

        if ($ok) {
            ActivityLog::log('automation.cancel', 'automation', (int)$id, "משימה #{$id}");
        }
        $this->json(['ok'=>$ok, 'msg'=>$ok?'משימה בוטלה':'לא ניתן לבטל']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function fetchWizeStatus(string $callId): ?string
    {
        try {
            $url = 'https://bug.wizenet.co.il/wizeapi/?' . http_build_query([
                'func'   => 'wizeApp_getBICalls',
                'token'  => 'ABUltIBvgMHYUg6NPaZ4cWA2p5467Jb',
                'callid' => $callId,
            ]);
            $ctx = stream_context_create(['http'=>['timeout'=>10,'method'=>'GET']]);
            $raw = @file_get_contents($url, false, $ctx);
            if (!$raw) return null;
            $data = json_decode($raw, true);
            return isset($data[0]['statusID']) ? (string)$data[0]['statusID'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function fetchOrderNote(string $orderNum): array
    {
        try {
            $url  = "https://monitor.alexisdeveloping.com/API/?request=2&accesstoken=1SsxFAsH42S&ordernumber=$orderNum";
            $json = @file_get_contents($url);
            if (!$json) return ['error'=>1,'errorMsg'=>'שגיאת תקשורת'];
            $data = json_decode($json, true);
            if (($data['response']??'') !== 'OK') return ['error'=>1,'errorMsg'=>$data['response']??'שגיאה'];
            return ['error'=>0,'order_note'=>base64_decode($data['order'][0]['MANAGERCOMMENTS']??'')];
        } catch (\Throwable $e) {
            return ['error'=>1,'errorMsg'=>$e->getMessage()];
        }
    }

    private function fmtDate(?string $d): string
    {
        if (!$d || $d === '0000-00-00 00:00:00') return '—';
        try { return (new \DateTime($d))->format('d/m/Y H:i'); }
        catch (\Throwable) { return $d; }
    }
}
