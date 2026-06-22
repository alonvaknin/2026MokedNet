<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;

class WizenetController extends Controller
{
    private const API_BASE  = 'https://bug.wizenet.co.il/wizeapi/';
    private const API_TOKEN = 'ABUltIBvgMHYUg6NPaZ4cWA2p5467Jb';

    // ── helpers ──────────────────────────────────────────────

    private static function apiUrl(array $params): string
    {
        $params['func']  = 'wizeApp_getBICalls';
        $params['token'] = self::API_TOKEN;
        return self::API_BASE . '?' . http_build_query($params);
    }

    private static function fetch(string $url): ?array
    {
        $ctx = stream_context_create(['http' => [
            'timeout' => 12,
            'method'  => 'GET',
            'header'  => "Accept: application/json\r\n",
        ]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) return null;
        $data = json_decode($raw, true);
        return (is_array($data) && !empty($data)) ? $data : [];
    }

    private static function dateRange(string $from = '', string $to = ''): array
    {
        // תמיד בפורמט d/m/Y
        return [
            'dateFrom' => $from ?: date('d/m/Y', strtotime('-2 months')),
            'dateTo'   => $to   ?: date('d/m/Y'),
        ];
    }

    private static function normalizeCall(array $c): array
    {
        // Parse subjects — using v1 method (string manipulation, more robust)
        $subjects = [];
        if (!empty($c['CallSubjectList'])) {
            // Try JSON first
            $parsed = json_decode($c['CallSubjectList'], true);
            if (is_array($parsed) && !empty($parsed)) {
                $subjects = array_map(fn($s) => [
                    'name' => trim($s['csname']  ?? ''),
                    'desc' => trim($s['CSLdesc'] ?? $s['CSLdesc '] ?? ''),
                ], $parsed);
            } else {
                // v1 fallback: strip {  } [  ] " and split by comma
                $str  = str_replace(str_split('{}[]"'), '', $c['CallSubjectList']);
                $parts = explode(',', $str);
                $csname = '';
                foreach ($parts as $key => $value) {
                    if ($key % 2 === 0) {
                        $csname = trim(str_replace('csname: ', '', $value));
                    } else {
                        $desc = trim(str_replace(['CSLdesc : ','CSLdesc: '], '', $value));
                        if ($csname !== '') {
                            $subjects[] = ['name' => $csname, 'desc' => $desc];
                        }
                    }
                }
            }
        }

        // Parse resolution — split by #/## markers, clean whitespace
        $resolution = [];
        if (!empty($c['resolution'])) {
            $parts = preg_split('/##+/', $c['resolution']);
            $resolution = array_values(array_filter(
                array_map(fn($p) => trim(preg_replace('/\s+/', ' ', $p)), $parts),
                fn($p) => strlen(preg_replace('/[\s:;,#]/','', $p)) > 4
            ));
        }

        return [
            'callId'      => $c['CallID']       ?? '',
            'makat'       => $c['Pmakat']        ?? '',
            'serial'      => $c['Pserial']       ?? '',
            'product'     => $c['Pname']         ?? '',
            'statusId'    => $c['statusID']      ?? '',
            'statusName'  => $c['statusName']    ?? '',
            'callType'    => $c['CallTypeName']  ?? '',
            'origin'      => $c['OriginName']    ?? '',
            'company'     => $c['Ccompany']      ?? '',
            'branch'      => $c['Cname']         ?? '',
            'address'     => trim(($c['Caddress']??'').' '.($c['Ccity']??'')),
            'companyPhone'=> $c['companyphone']  ?? $c['Cphone'] ?? '',
            'contactName' => $c['ContctName']    ?? '',
            'contactCell' => $c['ContctCell']    ?? $c['Ccell'] ?? '',
            'contactEmail'=> $c['ContctEmail']   ?? '',
            'techName'    => $c['techName']      ?? '',
            'createDate'  => $c['createDate']    ?? '',
            'updateDate'  => $c['CallUpdate']    ?? '',
            'endDate'     => $c['EndDate']       ?? '',
            'comments'    => $c['comments']      ?? '',
            'subjects'    => $subjects,
            'resolution'  => $resolution,
            'filesUrl'    => $c['callFiles']     ?? '',
            'sla'         => $c['CSLdesc']       ?? '',
        ];
    }

    // ── endpoints ────────────────────────────────────────────

    /**
     * GET /api/wize/call?id=123456
     * חיפוש קריאה בודדת לפי מספר — ללא תאריכים
     */
    public function getCall(): void
    {
        $this->requireAuth();
        $id = preg_replace('/\D/', '', $this->get('id', ''));
        if (!$id) $this->json(['error' => 'מספר קריאה חסר'], 400);

        $data = self::fetch(self::apiUrl(['callid' => $id]));
        if ($data === null) $this->json(['error' => 'שגיאת חיבור לwizenet'], 503);
        if (empty($data))   $this->json(['error' => "קריאה $id לא נמצאה", 'callId' => $id], 404);

        $this->json(['ok' => true] + self::normalizeCall($data[0]));
    }

    /**
     * GET /api/wize/search
     *   ?q=...          — ערך חיפוש
     *   ?from=dd/mm/yyyy — תאריך התחלה (ברירת מחדל: חודשיים אחורה)
     *   ?to=dd/mm/yyyy   — תאריך סוף    (ברירת מחדל: היום)
     *
     * לוגיקת בחירת param לפי אורך הקלט:
     *   ספרות בלבד:
     *     ≤6  → callid (מספר קריאה)
     *     10  → Cphone (טלפון)
     *     15  → Pserial (IMEI)
     *     אחר → Cphone (מנסה טלפון חלקי)
     *   טקסט → Ccompany (שם חברה/לקוח)
     */
    public function search(): void
    {
        $this->requireAuth();

        $q    = trim($this->get('q', ''));
        $from = trim($this->get('from', ''));
        $to   = trim($this->get('to', ''));

        if (mb_strlen($q) < 2) $this->json(['error' => 'קלט קצר מדי'], 400);

        $dates = self::dateRange($from, $to);
        $clean = preg_replace('/\D/', '', $q); // digits only
        $len   = strlen($clean);

        // ── Validation: מניעת חיפוש כללי מדי ──────────────────────────────
        //
        // חיפוש לפי טלפון (Ccell) או שם חברה (Ccompany) בלי קריטריון מספיק:
        //   • טלפון חלקי — פחות מ-9 ספרות שאינן callid/imei = חיפוש מסוכן
        //   • חיפוש לפי שם חברה עם תאריכים — מוגבל ל-3 ימים מקסימום
        //   • חיפוש לפי טלפון עם תאריכים — מוגבל ל-7 ימים
        $isTextSearch  = ($clean !== $q);               // שם חברה
        $isPhoneSearch = ($clean === $q && $len >= 9 && $len <= 11);
        $isImeiSearch  = ($clean === $q && $len >= 14);
        $isCallId      = ($clean === $q && $len >= 3 && $len <= 6);

        // Parse date range for validation
        $parseDateDMY = static function (string $s): ?\DateTime {
            if (!$s) return null;
            $dt = \DateTime::createFromFormat('d/m/Y', $s);
            return $dt ?: null;
        };
        $resolvedDates = self::dateRange($from, $to); // already resolved
        $dtFrom = $parseDateDMY($resolvedDates['dateFrom']);
        $dtTo   = $parseDateDMY($resolvedDates['dateTo']);
        $rangeDays = ($dtFrom && $dtTo) ? (int)$dtTo->diff($dtFrom)->days : 0;

        if ($isTextSearch) {
            // שם חברה — מקסימום 3 ימים
            if ($rangeDays > 3) {
                $this->json([
                    'error' => 'חיפוש לפי שם לקוח מוגבל ל-3 ימים לכל היותר. צמצם את טווח התאריכים.',
                    'rangeDays' => $rangeDays,
                ], 400);
            }
        } elseif ($isPhoneSearch) {
            // טלפון — מקסימום 360 ימים
            if ($rangeDays > 360) {
                $this->json([
                    'error' => 'חיפוש לפי טלפון מוגבל ל-360 ימים לכל היותר. צמצם את טווח התאריכים.',
                    'rangeDays' => $rangeDays,
                ], 400);
            }
        } elseif ($isImeiSearch) {
            // IMEI — מקסימום 360 ימים (סביר)
            if ($rangeDays > 360) {
                $this->json([
                    'error' => 'חיפוש לפי IMEI מוגבל ל-360 ימים לכל היותר.',
                    'rangeDays' => $rangeDays,
                ], 400);
            }
        }
        // callid: ללא הגבלת תאריכים (לא שולח תאריכים בכלל)
        // ─────────────────────────────────────────────────────────────────

        // בחירת param לפי אורך
        if ($clean === $q) {
            // כל הקלט ספרות
            if ($len <= 6 && $len >= 3) {
                // מספר קריאה — ללא תאריכים (יותר מדויק)
                $params = ['callid' => $clean];
            } elseif ($len >= 9 && $len <= 11) {
                // טלפון — Ccell
                $params = ['ccell' => $clean] + $dates;
            } elseif ($len >= 14) {
                // IMEI — 15 ספרות בד"כ
                $params = ['Pserial' => $clean] + $dates;
            } else {
                $params = ['ccell' => $clean] + $dates;
            }
        } else {
            // טקסט חופשי — שם חברה/לקוח
            $params = ['Ccompany' => $q] + $dates;
        }

        $url  = self::apiUrl($params);
        $data = self::fetch($url);

        if ($data === null) $this->json(['error' => 'שגיאת חיבור לwizenet'], 503);

        $results = array_map(fn($c) => self::normalizeCall($c), $data);

        $this->json([
            'results'   => $results,
            'count'     => count($results),
            'searchType'=> array_key_first($params),
            'dateFrom'  => $dates['dateFrom'] ?? '',
            'dateTo'    => $dates['dateTo']   ?? '',
        ]);
    }
}
