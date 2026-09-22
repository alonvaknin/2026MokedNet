<?php
declare(strict_types=1);

namespace Services;

use Core\Holidays;

/**
 * מפיק SpreadsheetML 2003 — קובץ .xls שהוא XML.
 * נבחר על פני PhpSpreadsheet (אין Composer בפרויקט) ועל פני CSV
 * (שמפרק עמודות בעברית באקסל).
 *
 * build() היא פונקציה טהורה — ללא גישת DB, כדי שניתן יהיה לבדוק אותה בנפרד.
 */
class HoursExporter
{
    private const TYPES = [
        'regular' => 'רגיל', 'vacation' => 'חופש', 'reserve' => 'מילואים',
        'sick' => 'מחלה', 'duplicate_delete' => 'למחוק דיווחים כפולים', 'other' => 'אחר',
    ];
    private const DAYS = ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];
    private const HEAD = ['שם עובד','תאריך','יום','סוג יום','סוג דיווח','כניסה','יציאה','הערה'];

    /** @param array<int,array<string,mixed>> $rows */
    public static function build(array $rows): string
    {
        $x  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $x .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
            . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $x .= '<Styles>'
            . '<Style ss:ID="h"><Font ss:Bold="1"/>'
            . '<Interior ss:Color="#DDDDDD" ss:Pattern="Solid"/></Style>'
            . '</Styles>' . "\n";
        $x .= '<Worksheet ss:Name="דיווח שעות"><Table>' . "\n";

        $x .= '<Row>';
        foreach (self::HEAD as $h) {
            $x .= '<Cell ss:StyleID="h"><Data ss:Type="String">' . self::esc($h) . '</Data></Cell>';
        }
        $x .= '</Row>' . "\n";

        foreach ($rows as $r) {
            $date = (string)($r['work_date'] ?? '');
            $ts   = strtotime($date) ?: time();
            $type = (string)($r['entry_type'] ?? '');

            $cells = [
                (string)($r['full_name'] ?? ''),
                date('d/m/Y', $ts),
                self::DAYS[(int)date('w', $ts)],
                Holidays::label(Holidays::dayType($date)),
                self::TYPES[$type] ?? $type,
                substr((string)($r['time_in'] ?? ''), 0, 5),
                substr((string)($r['time_out'] ?? ''), 0, 5),
                (string)($r['note'] ?? ''),
            ];

            $x .= '<Row>';
            foreach ($cells as $c) {
                $x .= '<Cell><Data ss:Type="String">' . self::esc($c) . '</Data></Cell>';
            }
            $x .= '</Row>' . "\n";
        }

        return $x . '</Table></Worksheet>' . "\n" . '</Workbook>';
    }

    /** בריחת XML מלאה — אקסל מסרב לפתוח SpreadsheetML לא תקין */
    private static function esc(string $s): string
    {
        // תווי בקרה אינם חוקיים ב-XML 1.0 ויגרמו לאקסל לסרב לפתוח את הקובץ.
        // הערה שהגיעה דרך API (ולא מהדפדפן) עלולה להכיל אותם.
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s) ?? $s;
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
