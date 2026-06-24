<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\ActivityLog;
use Models\InvoiceChangeNameModel;
use Models\TaskModel;
use Models\UserModel;

class InvoiceChangeNameController extends Controller
{
    private const MAIL_TO      = 'eyal@bug.co.il;bat-el@bug.co.il';
    private const FROM_ADDRESS = 'moked-net-noreply@alexisdeveloping.com';
    private const FROM_NAME    = 'מוקד-נט';

    public function index(): void
    {
        $this->requirePermission('canUseInvoiceChangeName');
        $users = UserModel::customerServiceUsers();
        $this->view('pages/invoice-change-name/index', compact('users'));
    }

    public function apiList(): void
    {
        $this->requirePermission('canUseInvoiceChangeName');
        $rows  = InvoiceChangeNameModel::all();
        $users = UserModel::customerServiceUsers();
        $this->json(compact('rows', 'users'));
    }

    public function create(): void
    {
        $this->requirePermission('canUseInvoiceChangeName');
        $this->verifyCsrf();

        $invoiceNum   = trim($this->post('invoice_sap_number', ''));
        $newName      = trim($this->post('new_name', ''));
        $note         = trim($this->post('invoice_note', ''));
        $phone        = trim($this->post('customer_phone', ''));
        $mail         = trim($this->post('customer_mail', ''));
        $customerName = trim($this->post('customer_name', ''));

        if (!ctype_digit($invoiceNum) || strlen($invoiceNum) !== 9) {
            $this->json(['error' => true, 'msg' => 'מספר חשבונית לא תקין — חייב להיות 9 ספרות'], 422);
            return;
        }
        if (mb_strlen($newName) > 50 || $newName === '') {
            $this->json(['error' => true, 'msg' => 'שם חדש חייב להיות בין 1-50 תווים'], 422);
            return;
        }
        if (!ctype_digit($phone)) {
            $this->json(['error' => true, 'msg' => 'מספר טלפון לא תקין'], 422);
            return;
        }
        if ($mail === '') {
            $this->json(['error' => true, 'msg' => 'נא למלא מייל לקוח'], 422);
            return;
        }
        if (InvoiceChangeNameModel::checkDuplicate($invoiceNum)) {
            $this->json(['error' => true, 'msg' => 'קיימת בקשה פתוחה לחשבונית ' . $invoiceNum], 409);
            return;
        }

        $user = Auth::user();
        $id   = InvoiceChangeNameModel::create([
            'open_by_id'         => $user['id'],
            'open_by_name'       => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'new_name'           => $newName,
            'invoice_sap_number' => $invoiceNum,
            'invoice_note'       => $note,
            'customer_phone'     => $phone,
            'customer_mail'      => $mail,
            'customer_name'      => $customerName,
        ]);

        $this->sendCreateMail($invoiceNum, $newName, $note, $customerName, $phone, $mail, $user);

        ActivityLog::create('invoice_change_name', $id, "חשבונית {$invoiceNum} → {$newName}");

        $taskType = TaskModel::typeByName('שינוי שם בחשבונית');
        if ($taskType) {
            TaskModel::createFromSource(
                (int)$taskType['id'],
                'invoice_change_name',
                $id,
                (int)$user['id'],
                "שינוי שם בחש' {$invoiceNum} → {$newName}"
            );
        }

        $this->json(['error' => false, 'msg' => 'בקשת שינוי שם נוספה בהצלחה', 'id' => $id]);
    }

    public function updateStatus(string $id): void
    {
        $this->requirePermission('canUseInvoiceChangeName');
        $this->verifyCsrf();

        $status  = trim($this->post('status', ''));
        $careBy  = trim($this->post('care_by', ''));

        $allowed = ['פתוחה', 'בהמתנה', 'טופלה + מייל', 'סגורה', 'תקלה בפרטים'];
        if (!in_array($status, $allowed, true)) {
            $this->json(['error' => true, 'msg' => 'סטטוס לא תקין'], 422);
            return;
        }

        $ok = InvoiceChangeNameModel::updateStatus((int)$id, $status, $careBy);
        if (!$ok) {
            $this->json(['error' => true, 'msg' => 'לא נמצאה רשומה'], 404);
            return;
        }

        if ($status === 'טופלה + מייל') {
            $row = InvoiceChangeNameModel::byId((int)$id);
            if ($row) {
                $this->sendStatusMail($row, $careBy);
            }
        }

        ActivityLog::log(
            'invoice_change_name.status',
            'invoice_change_name',
            (int)$id,
            "חשבונית #{$id}",
            "סטטוס → {$status}" . ($careBy ? " | מטפל: {$careBy}" : '')
        );

        $this->json(['error' => false, 'msg' => 'סטטוס עודכן: ' . $status]);
    }

    public function editField(string $id): void
    {
        $this->requirePermission('canUseInvoiceChangeName');
        $this->verifyCsrf();

        $field = trim($this->post('field', ''));
        $value = trim($this->post('value', ''));

        $row = InvoiceChangeNameModel::byId((int)$id);
        $oldValue = $row[$field] ?? '';

        $ok = InvoiceChangeNameModel::editField((int)$id, $field, $value);
        if (!$ok) {
            $this->json(['error' => true, 'msg' => 'עדכון נכשל — שדה לא חוקי או רשומה לא נמצאה'], 422);
            return;
        }

        ActivityLog::field(
            'invoice_change_name.update',
            'invoice_change_name',
            (int)$id,
            "חשבונית #" . ($row['invoice_sap_number'] ?? $id),
            $field,
            $oldValue,
            $value
        );

        $this->json(['error' => false, 'msg' => 'שדה עודכן בהצלחה']);
    }

    // ── Private mail helpers ────────────────────────────────────────────────

    private function sendCreateMail(
        string $invoiceNum, string $newName, string $note,
        string $customerName, string $phone, string $mail,
        array $user
    ): void {
        $replyTo = preg_replace('/[\r\n\0]/', '', $user['email'] ?? '');
        $subject = preg_replace('/[\r\n\0]/', '', "[שינוי שם בחש] לחש: {$invoiceNum} נא לשנות לשם {$newName}");
        $body    = $this->buildMailBody(
            'בקשה לשינוי שם',
            $invoiceNum, $newName, $note, $customerName, $phone, $mail
        );
        $headers  = "From: " . self::FROM_NAME . " <" . self::FROM_ADDRESS . ">\r\n";
        $headers .= "Reply-To: {$replyTo}\r\n";
        $headers .= "CC: {$replyTo}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        mail(self::MAIL_TO, $subject, $body, $headers);
    }

    private function sendStatusMail(array $row, string $careByUserId): void
    {
        $user    = Auth::user();
        $replyTo = str_replace(["\r", "\n"], '', $user['email'] ?? '');
        $openerMail = \Core\DB::value(
            'SELECT email FROM users WHERE id = ?', [$row['open_by_id']]
        ) ?? '';

        $to      = $openerMail;
        $subject = preg_replace('/[\r\n]/', '', "[שינוי שם בחש] לחש: {$row['invoice_sap_number']} בוצע");
        $body    = $this->buildMailBody(
            'בוצעה בקשה לשינוי שם',
            $row['invoice_sap_number'], $row['new_name'], $row['invoice_note'],
            $row['customer_name'], $row['customer_phone'], $row['customer_mail']
        );
        $headers  = "From: " . self::FROM_NAME . " <" . self::FROM_ADDRESS . ">\r\n";
        $headers .= "Reply-To: {$replyTo}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        mail($to, $subject, $body, $headers);
    }

    private function buildMailBody(
        string $title,
        string $invoiceNum, string $newName, string $note,
        string $customerName, string $phone, string $mail
    ): string {
        $t  = htmlspecialchars($title);
        $in = htmlspecialchars($invoiceNum);
        $nn = htmlspecialchars($newName);
        $nt = htmlspecialchars($note);
        $cn = htmlspecialchars($customerName);
        $ph = htmlspecialchars($phone);
        $em = htmlspecialchars($mail);

        return <<<HTML
<html lang="he" dir="rtl">
<head><meta charset="utf-8"></head>
<body style="font-family:Tahoma,Arial,sans-serif;text-align:right;direction:rtl;">
  <b style="font-size:18pt;">{$t}</b>
  <br>נא לשנות בחשבונית: <span style="font-size:15pt;">{$in}</span>
  <br>חשבונית חדשה על שם: <span style="font-size:15pt;">{$nn}</span>
  <br>הערת נציג: <span style="font-size:15pt;">{$nt}</span>
  <br><br>
  <p>פרטי לקוח/ה:<br>
    שם: <b>{$cn}</b><br>
    טלפון: <b>{$ph}</b><br>
    מייל: <b>{$em}</b>
  </p>
  <br><span style="color:#999;">מופעל באמצעות מערכת מוקד-נט</span>
</body>
</html>
HTML;
    }
}
