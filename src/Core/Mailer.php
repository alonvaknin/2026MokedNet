<?php
// src/Core/Mailer.php
declare(strict_types=1);

namespace Core;

class Mailer
{
    private const FROM_NAME    = 'מוקד-נט';
    private const FROM_ADDRESS = 'moked-net-noreply@alexisdeveloping.com';

    /**
     * Send a password-set/reset link to a user.
     *
     * @param string $toEmail   Recipient email
     * @param string $toName    Recipient display name
     * @param string $resetUrl  Full URL with token
     * @param bool   $isNew     true = new user (קביעת סיסמא), false = reset
     */
    public static function sendPasswordReset(
        string $toEmail,
        string $toName,
        string $resetUrl,
        bool $isNew = false
    ): bool {
        $appName = str_replace(["\r", "\n"], '', CFG['app']['name'] ?? 'מוקד-נט');
        $subject = $isNew
            ? "[{$appName}] קביעת סיסמא למשתמש חדש"
            : "[{$appName}] איפוס סיסמא";

        $actionLabel = $isNew ? 'קביעת סיסמא' : 'איפוס סיסמא';
        $safeAppName = htmlspecialchars($appName);
        $greeting    = $isNew
            ? "חשבון משתמש חדש נוצר עבורך במערכת <b>{$safeAppName}</b>."
            : "קיבלנו בקשה לאיפוס הסיסמא שלך במערכת <b>{$safeAppName}</b>.";

        $message  = '<!DOCTYPE html>';
        $message .= '<html lang="he" dir="rtl">';
        $message .= '<head><meta charset="utf-8"><title>' . htmlspecialchars($subject) . '</title></head>';
        $message .= '<body style="font-family:Tahoma,Arial,sans-serif;background:#f4f5f7;color:#2b2f38;';
        $message .= 'direction:rtl;text-align:right;margin:0;padding:0;">';
        $message .= '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:32px 0;">';
        $message .= '<tr><td align="center">';
        $message .= '<table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;';
        $message .= 'border:1px solid #e2e4e9;border-radius:8px;overflow:hidden;">';

        // Header
        $message .= '<tr><td style="padding:28px 32px 20px;border-bottom:1px solid #e2e4e9;text-align:right;">';
        $message .= '<span style="font-size:20px;font-weight:700;color:#1f2430;">' . htmlspecialchars($appName) . '</span>';
        $message .= '<span style="font-size:13px;color:#7a8090;margin-right:10px;">מערכת ניהול פנים-ארגונית</span>';
        $message .= '</td></tr>';

        // Body
        $message .= '<tr><td style="padding:32px;">';
        $message .= '<p style="font-size:16px;margin:0 0 12px;color:#1f2430;">שלום <b>' . htmlspecialchars($toName) . '</b>,</p>';
        $message .= '<p style="font-size:14px;line-height:1.6;color:#4a4f5c;margin:0 0 24px;">' . $greeting . '</p>';
        $message .= '<p style="font-size:14px;line-height:1.6;color:#4a4f5c;margin:0 0 24px;">';
        $message .= 'לחץ/י על הכפתור הבא כדי לבצע ' . $actionLabel . '. הקישור בתוקף למשך <b>שעתיים</b>.</p>';

        // CTA Button
        $message .= '<table cellpadding="0" cellspacing="0" style="margin:0 0 28px;">';
        $message .= '<tr><td style="background:#2f5fd8;border-radius:6px;padding:0;">';
        $message .= '<a href="' . htmlspecialchars($resetUrl) . '" ';
        $message .= 'style="display:block;padding:12px 28px;color:#ffffff;font-size:15px;';
        $message .= 'font-weight:600;text-decoration:none;">' . $actionLabel . '</a>';
        $message .= '</td></tr></table>';

        // Fallback URL
        $message .= '<p style="font-size:12px;color:#8a90a0;margin:0 0 6px;">אם הכפתור לא עובד, ניתן להעתיק את הקישור הבא לדפדפן:</p>';
        $message .= '<p style="font-size:12px;color:#2f5fd8;word-break:break-all;margin:0 0 24px;">';
        $message .= htmlspecialchars($resetUrl) . '</p>';

        $message .= '<hr style="border:none;border-top:1px solid #e2e4e9;margin:0 0 16px;">';
        $message .= '<p style="font-size:12px;color:#8a90a0;margin:0;">אם לא ביקשת פעולה זו, ניתן להתעלם ממייל זה — הסיסמא הנוכחית תישאר בתוקף.</p>';
        $message .= '</td></tr>';

        // Footer
        $message .= '<tr><td style="background:#f8f9fb;padding:16px 32px;text-align:right;border-top:1px solid #e2e4e9;">';
        $message .= '<span style="font-size:12px;color:#9096a3;">מופעל באמצעות מערכת ' . htmlspecialchars($appName) . '</span>';
        $message .= '</td></tr>';

        $message .= '</table></td></tr></table></body></html>';

        $headers  = 'From: ' . self::FROM_NAME . ' <' . self::FROM_ADDRESS . ">\r\n";
        $headers .= 'Reply-To: ' . self::FROM_ADDRESS . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";

        return mail($toEmail, $subject, $message, $headers);
    }
}
