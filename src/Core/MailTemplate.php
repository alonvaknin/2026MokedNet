<?php
// src/Core/MailTemplate.php
declare(strict_types=1);

namespace Core;

/**
 * תבנית מייל אחידה (RTL, Tahoma) + שליחה תקנית לכל ה-CRON.
 *
 * למה base64: כל ה-HTML נבנה כשורה אחת ארוכה. SMTP מגביל שורה ל-998 תווים,
 * וה-MTA שובר אותה באמצע — לפעמים בתוך תגית ("</tr>" → "< /tr>"), והלקוח
 * מציג את שארית התגית כטקסט. קידוד base64 עם chunk_split מונע את זה לגמרי.
 */
class MailTemplate
{
    public const FROM_NAME    = 'מוקד-נט';
    public const FROM_ADDRESS = 'moked-net-noreply@alexisdeveloping.com';

    private const FONT = "font-family:Tahoma,Arial,sans-serif;";

    // ── Layout ───────────────────────────────────────────────────────────────

    public static function wrap(string $title, string $badge, string $body, int $width = 560): string
    {
        $f = self::FONT;

        return '<!DOCTYPE html>'
            . '<html lang="he" dir="rtl">'
            . '<head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . self::e($title) . '</title>'
            . '<style>body,table,td,th,p,div,span,a,b,strong{' . $f . '}</style></head>'
            . '<body dir="rtl" style="margin:0;padding:0;background:#f4f5f7;' . $f . 'color:#2b2e3b;direction:rtl;text-align:right;">'
            . '<table role="presentation" dir="rtl" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f5f7" style="background:#f4f5f7;">'
            . '<tr><td align="center" style="padding:32px 12px;">'
            . '<table role="presentation" dir="rtl" width="' . $width . '" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" '
            . 'style="width:100%;max-width:' . $width . 'px;background:#ffffff;border:1px solid #e3e5ea;border-radius:12px;">'

            // Header
            . '<tr><td align="right" dir="rtl" bgcolor="#4f7fff" style="background:#4f7fff;padding:22px 32px;text-align:right;border-radius:12px 12px 0 0;">'
            . '<span style="' . $f . 'font-size:22px;font-weight:700;color:#ffffff;">' . self::e(self::FROM_NAME) . '</span>'
            . '<span style="' . $f . 'font-size:13px;color:#dbe6ff;margin-right:12px;">' . self::e($badge) . '</span>'
            . '</td></tr>'

            // Body
            . '<tr><td align="right" dir="rtl" style="padding:32px 32px 28px;text-align:right;' . $f . '">' . $body . '</td></tr>'

            // Footer
            . '<tr><td align="right" dir="rtl" bgcolor="#f7f8fa" style="background:#f7f8fa;padding:14px 32px;text-align:right;border-top:1px solid #eceef2;border-radius:0 0 12px 12px;">'
            . '<span style="' . $f . 'font-size:12px;color:#8a8fa3;">מופעל באמצעות מערכת ' . self::e(self::FROM_NAME) . '</span>'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    // ── Building blocks (כל הערכים הדינמיים — לברוח מראש עם e()) ─────────────

    public static function heading(string $text, string $sub = ''): string
    {
        $f   = self::FONT;
        $out = '<p style="' . $f . 'font-size:18px;font-weight:700;color:#20232e;margin:0 0 8px;text-align:right;">' . $text . '</p>';
        if ($sub !== '') {
            $out .= '<p style="' . $f . 'font-size:14px;line-height:1.7;color:#5a5e78;margin:0 0 22px;text-align:right;">' . $sub . '</p>';
        }
        return $out;
    }

    public static function greeting(string $name, string $sub = ''): string
    {
        $f   = self::FONT;
        $out = '<p style="' . $f . 'font-size:16px;color:#20232e;margin:0 0 6px;text-align:right;">שלום, <b>' . self::e($name) . '</b></p>';
        if ($sub !== '') {
            $out .= '<p style="' . $f . 'font-size:14px;line-height:1.7;color:#5a5e78;margin:0 0 22px;text-align:right;">' . $sub . '</p>';
        }
        return $out;
    }

    /** שורת "תווית: ערך" — לשימוש בתוך details() */
    public static function row(string $label, string $valueHtml): string
    {
        $f = self::FONT;
        return '<tr>'
            . '<td align="right" dir="rtl" valign="top" width="120" style="' . $f . 'width:120px;font-size:13px;color:#8a8fa3;padding:10px 0;border-bottom:1px solid #eef0f4;text-align:right;">' . $label . '</td>'
            . '<td align="right" dir="rtl" valign="top" style="' . $f . 'font-size:14px;color:#20232e;font-weight:600;padding:10px 0;border-bottom:1px solid #eef0f4;text-align:right;">' . $valueHtml . '</td>'
            . '</tr>';
    }

    /** טבלת פרטים מרשימת שורות (תוצאות row()) */
    public static function details(string ...$rows): string
    {
        return '<table role="presentation" dir="rtl" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0 0 24px;">'
            . implode('', $rows) . '</table>';
    }

    /** תיבת הודעה אפורה עם כותרת קטנה. $text מטקסט גולמי — נעשה בו escape + שבירת שורות */
    public static function messageBox(string $label, string $text): string
    {
        $f = self::FONT;
        return '<table role="presentation" dir="rtl" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0 0 8px;">'
            . '<tr><td align="right" dir="rtl" bgcolor="#eef0f5" style="background:#eef0f5;border:1px solid #dcdfe6;border-radius:8px;padding:14px 16px;text-align:right;' . $f . '">'
            . '<p style="' . $f . 'font-size:12px;color:#8a8fa3;margin:0 0 6px;text-align:right;">' . self::e($label) . '</p>'
            . '<p style="' . $f . 'font-size:15px;line-height:1.7;color:#20232e;margin:0;text-align:right;">' . nl2br(self::e($text)) . '</p>'
            . '</td></tr></table>';
    }

    /** כרטיס צבעוני עם פס בצד ימין. $tone: warn | error | info */
    public static function card(string $tone, string $title, string $textHtml): string
    {
        $f = self::FONT;
        [$bg, $bar, $ttl] = match ($tone) {
            'error' => ['#fdeeee', '#e74c3c', '#c0392b'],
            'warn'  => ['#fdf3e3', '#e67e22', '#b5680f'],
            default => ['#eef0f5', '#4f7fff', '#3a63d8'],
        };
        return '<table role="presentation" dir="rtl" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0 0 12px;">'
            . '<tr><td align="right" dir="rtl" bgcolor="' . $bg . '" style="background:' . $bg . ';border-right:4px solid ' . $bar . ';border-radius:8px;padding:14px 16px;text-align:right;' . $f . '">'
            . '<p style="' . $f . 'font-size:14px;font-weight:700;color:' . $ttl . ';margin:0 0 6px;text-align:right;">' . $title . '</p>'
            . '<p style="' . $f . 'font-size:13px;line-height:1.7;color:#5a5e78;margin:0;text-align:right;">' . $textHtml . '</p>'
            . '</td></tr></table>';
    }

    public static function button(string $url, string $text): string
    {
        $f = self::FONT;
        return '<table role="presentation" dir="rtl" align="right" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 8px;">'
            . '<tr><td align="center" bgcolor="#4f7fff" style="background:#4f7fff;border-radius:8px;">'
            . '<a href="' . self::e($url) . '" style="' . $f . 'display:block;padding:12px 28px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;">' . $text . '</a>'
            . '</td></tr></table>';
    }

    // ── Sending ──────────────────────────────────────────────────────────────

    /**
     * @param string|array $to   כתובת/ות (מופרדות ב-, או ; או מערך)
     * @param array        $opts cc, bcc, replyTo
     */
    public static function send(string|array $to, string $subject, string $html, array $opts = []): bool
    {
        $to = self::addresses($to);
        if ($to === '') {
            return false;
        }

        $replyTo = self::clean($opts['replyTo'] ?? self::FROM_ADDRESS);
        $cc      = self::addresses($opts['cc']  ?? '');
        $bcc     = self::addresses($opts['bcc'] ?? '');

        $headers  = 'From: ' . self::mime(self::FROM_NAME) . ' <' . self::FROM_ADDRESS . ">\r\n";
        $headers .= "Reply-To: {$replyTo}\r\n";
        if ($cc  !== '') $headers .= "Cc: {$cc}\r\n";
        if ($bcc !== '') $headers .= "Bcc: {$bcc}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";

        return mail(
            $to,
            self::mime(self::clean($subject)),
            chunk_split(base64_encode($html)),
            $headers
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public static function e(?string $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function mime(string $v): string
    {
        return mb_encode_mimeheader($v, 'UTF-8', 'B', "\r\n");
    }

    private static function clean(string $v): string
    {
        return trim(preg_replace('/[\r\n\0]+/', ' ', $v));
    }

    /** מנרמל רשימת כתובות לפורמט "a@x, b@y" (מקבל גם ';') וזורק ריקים */
    private static function addresses(string|array $v): string
    {
        $list = is_array($v) ? $v : preg_split('/[;,]+/', $v);
        $list = array_filter(array_map(fn($a) => self::clean((string) $a), $list));
        return implode(', ', $list);
    }
}
