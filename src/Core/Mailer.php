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
        $message .= '<body style="font-family:Tahoma,Arial,sans-serif;background:#0f1117;color:#e8eaf0;';
        $message .= 'direction:rtl;text-align:right;margin:0;padding:0;">';
        $message .= '<table width="100%" cellpadding="0" cellspacing="0" style="background:#0f1117;padding:32px 0;">';
        $message .= '<tr><td align="center">';
        $message .= '<table width="520" cellpadding="0" cellspacing="0" style="background:#181b23;';
        $message .= 'border:1px solid rgba(255,255,255,.08);border-radius:12px;overflow:hidden;">';

        // Header
        $message .= '<tr><td style="background:#4f7fff;padding:24px 32px;text-align:right;">';
        $message .= '<span style="font-size:24px;font-weight:700;color:#fff;">' . htmlspecialchars($appName) . '</span>';
        $message .= '<span style="font-size:14px;color:rgba(255,255,255,.75);margin-right:12px;">מערכת ניהול פנים-ארגונית</span>';
        $message .= '</td></tr>';

        // Body
        $message .= '<tr><td style="padding:32px;">';
        $message .= '<p style="font-size:16px;margin:0 0 12px;">שלום, <b>' . htmlspecialchars($toName) . '</b></p>';
        $message .= '<p style="font-size:14px;color:#b0b3c6;margin:0 0 24px;">' . $greeting . '</p>';
        $message .= '<p style="font-size:14px;color:#b0b3c6;margin:0 0 24px;">';
        $message .= 'לחץ/י על הכפתור הבא ל' . $actionLabel . '. הקישור בתוקף למשך <b>שעתיים</b>.</p>';

        // CTA Button
        $message .= '<table cellpadding="0" cellspacing="0" style="margin:0 0 28px;">';
        $message .= '<tr><td style="background:#4f7fff;border-radius:8px;padding:0;">';
        $message .= '<a href="' . htmlspecialchars($resetUrl) . '" ';
        $message .= 'style="display:block;padding:12px 28px;color:#fff;font-size:15px;';
        $message .= 'font-weight:600;text-decoration:none;">' . $actionLabel . ' ←</a>';
        $message .= '</td></tr></table>';

        // Fallback URL
        $message .= '<p style="font-size:12px;color:#5a5e78;margin:0 0 8px;">אם הכפתור לא עובד, העתק את הקישור הבא:</p>';
        $message .= '<p style="font-size:12px;color:#4f7fff;word-break:break-all;margin:0 0 24px;">';
        $message .= htmlspecialchars($resetUrl) . '</p>';

        $message .= '<hr style="border:none;border-top:1px solid rgba(255,255,255,.08);margin:0 0 20px;">';
        $message .= '<p style="font-size:12px;color:#5a5e78;margin:0;">אם לא ביקשת פעולה זו, ניתן להתעלם ממייל זה.</p>';
        $message .= '</td></tr>';

        // Footer
        $message .= '<tr><td style="background:#13161e;padding:16px 32px;text-align:right;">';
        $message .= '<span style="font-size:12px;color:#5a5e78;">מופעל באמצעות מערכת ' . htmlspecialchars($appName) . '</span>';
        $message .= '</td></tr>';

        $message .= '</table></td></tr></table></body></html>';

        $headers  = 'From: ' . self::FROM_NAME . ' <' . self::FROM_ADDRESS . ">\r\n";
        $headers .= 'Reply-To: ' . self::FROM_ADDRESS . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";

        return mail($toEmail, $subject, $message, $headers);
    }
}
