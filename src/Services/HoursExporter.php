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
        'sick' => 'מחלה', 'duplicate_delete' => 'למחוק דיווחים כפולים',
        'duplicate_in' => 'כניסה כפולה', 'duplicate_out' => 'יציאה כפולה',
        'other' => 'אחר',
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

    /**
     * בונה קובץ xlsx אמיתי (ZIP של חלקי XML) ומחזיר את תוכנו הבינארי.
     * דורש את הרחבת zip, שמגיעה עם PHP כברירת מחדל אך ניתנת לכיבוי —
     * לכן supportsXlsx() נבדקת לפני השימוש, ויש נפילה חזרה ל-SpreadsheetML.
     *
     * @param array<int,array<string,mixed>> $rows
     */
    public static function buildXlsx(array $rows): string
    {
        $sheet = self::sheetXml($rows);
        $n     = count($rows) + 1;   // כולל שורת הכותרת

        $files = [
            '[Content_Types].xml' =>
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                . '</Types>',

            '_rels/.rels' =>
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',

            'xl/_rels/workbook.xml.rels' =>
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                . '</Relationships>',

            'xl/workbook.xml' =>
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
                . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="' . self::esc('דיווח שעות') . '" sheetId="1" r:id="rId1"/></sheets>'
                . '</workbook>',

            // גופן, מילוי אפור ומודגש לשורת הכותרת
            'xl/styles.xml' =>
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<fonts count="2"><font><sz val="11"/><name val="Arial"/></font>'
                . '<font><b/><sz val="11"/><name val="Arial"/></font></fonts>'
                . '<fills count="3"><fill><patternFill patternType="none"/></fill>'
                . '<fill><patternFill patternType="gray125"/></fill>'
                . '<fill><patternFill patternType="solid"><fgColor rgb="FFDDDDDD"/>'
                . '<bgColor indexed="64"/></patternFill></fill></fills>'
                . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                . '<cellXfs count="2">'
                . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
                . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
                . '</cellXfs></styleSheet>',

            'xl/worksheets/sheet1.xml' => $sheet,
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'hx');
        if ($tmp === false) {
            throw new \RuntimeException('לא ניתן ליצור קובץ זמני');
        }

        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new \RuntimeException('לא ניתן ליצור ארכיון xlsx');
        }
        foreach ($files as $path => $content) {
            $zip->addFromString($path, $content);
        }
        $zip->close();

        $bin = (string)file_get_contents($tmp);
        @unlink($tmp);
        return $bin;
    }

    public static function supportsXlsx(): bool
    {
        return class_exists('\ZipArchive');
    }

    /** גיליון ה-xlsx: כל התאים כמחרוזות inline, ללא טבלת sharedStrings */
    private static function sheetXml(array $rows): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
           . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
           . '<sheetViews><sheetView rightToLeft="1" workbookViewId="0"/></sheetViews>'
           . '<cols>'
           . '<col min="1" max="1" width="22" customWidth="1"/>'
           . '<col min="2" max="4" width="12" customWidth="1"/>'
           . '<col min="5" max="5" width="18" customWidth="1"/>'
           . '<col min="6" max="7" width="10" customWidth="1"/>'
           . '<col min="8" max="8" width="32" customWidth="1"/>'
           . '</cols><sheetData>';

        $x .= self::rowXml(1, self::HEAD, 1);

        $n = 1;
        foreach ($rows as $r) {
            $x .= self::rowXml(++$n, self::cells($r), 0);
        }

        return $x . '</sheetData></worksheet>';
    }

    /** @param array<int,string> $values */
    private static function rowXml(int $rowNum, array $values, int $styleId): string
    {
        $x = '<row r="' . $rowNum . '">';
        $col = 0;
        foreach ($values as $v) {
            $ref = self::colLetter($col++) . $rowNum;
            $x  .= '<c r="' . $ref . '" t="inlineStr"'
                 . ($styleId ? ' s="' . $styleId . '"' : '') . '>'
                 . '<is><t xml:space="preserve">' . self::esc((string)$v) . '</t></is></c>';
        }
        return $x . '</row>';
    }

    private static function colLetter(int $i): string
    {
        $s = '';
        for ($n = $i + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $s = chr(65 + (($n - 1) % 26)) . $s;
        }
        return $s;
    }

    /** ערכי שורה אחת, באותו סדר של HEAD */
    private static function cells(array $r): array
    {
        $date = (string)($r['work_date'] ?? '');
        $ts   = strtotime($date) ?: time();
        $type = (string)($r['entry_type'] ?? '');

        return [
            (string)($r['full_name'] ?? ''),
            date('d/m/Y', $ts),
            self::DAYS[(int)date('w', $ts)],
            Holidays::label(Holidays::dayType($date)),
            self::TYPES[$type] ?? $type,
            substr((string)($r['time_in'] ?? ''), 0, 5),
            substr((string)($r['time_out'] ?? ''), 0, 5),
            (string)($r['note'] ?? ''),
        ];
    }
}
