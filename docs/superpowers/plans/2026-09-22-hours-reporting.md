# Hours Reporting Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** מודול דיווח שעות כניסה/יציאה לנציגים, עם לוח ניהול חודשי, דרישות דיווח, התראה בדאשבורד וייצוא XLS.

**Architecture:** MVC מותאם אישית של הפרויקט — `HoursController` קורא ל־`HoursModel`, שמחזיר data, והקונטרולר מעביר ל־View. טבלה אחת (`hours_entries`) משמשת גם כדרישת דיווח וגם כדיווח בפועל. מפת החגים נחלצת מהווידג'ט ל־`src/Core/Holidays.php` כמקור אמת יחיד לשרת וללקוח. הייצוא מופרד ל־`HoursExporter` המפיק SpreadsheetML 2003.

**Tech Stack:** PHP 8.1+ (`declare(strict_types=1)`), PDO דרך `Core\DB`, vanilla JS + `fetch()`, ללא Composer וללא framework.

**Spec:** `docs/superpowers/specs/2026-09-22-hours-reporting-design.md`

## Global Constraints

- `declare(strict_types=1);` בראש כל קובץ PHP חדש.
- **אין Composer ואין תלויות חיצוניות.** אין `vendor/`. כל ספרייה חדשה אסורה.
- Autoload ידני ב־`config/bootstrap.php` — מחלקות ב־`src/` נטענות לפי namespace (`Core\`, `Models\`, `Controllers\`, `Services\`).
- כל גישת DB דרך `Core\DB` בלבד (`DB::query`, `DB::row`, `DB::value`, `DB::execute`, `DB::insert`) עם prepared statements. **לעולם לא string interpolation של קלט לתוך SQL.**
- הטבלה `hours_entries` **כבר נוצרה** ב־`alon_db2` — אין להריץ DDL.
- **אין PHP CLI ואין גישת DB במכונת הפיתוח.** `php` אינו ב-PATH, ו-`alon_db2`
  נגיש מהשרת בלבד. **החלטת המשתמש: לא נכתבים סקריפטי בדיקה אוטומטיים** —
  האימות כולו ידני בדפדפן, מול השרת.
- **מכאן נובע שאי אפשר להריץ קוד PHP מקומית כלל** — לא טסטים ולא בדיקת תחביר.
  לכן, בכל משימה:
  - קרא בעיון כל קובץ שאתה נוגע בו לפני העריכה; אין רשת ביטחון שתתפוס שגיאת
    הקלדה או שם מתודה שגוי.
  - **אל תסמן שלב אימות כבוצע בלי פלט אמיתי מהדפדפן.** "נראה תקין" אינו אימות.
  - דווח במפורש על כל שלב שלא הצלחת לאמת, במקום להניח שהוא עובד.
- **הלוגיקה הרגישה ביותר חסרת כיסוי אוטומטי:** `HoursModel::isComplete()` (מטריצת
  `requires`) ו-`Holidays::dayType()` (שבת/שישי גוברים על חג). שלבי הבדיקה הידנית
  של Tasks 1, 3 ו-4 מפרטים את המקרים המדויקים שחייבים להיבדק בדפדפן.
- **CSRF:** `verifyCsrf()` קורא `$_POST['_csrf']` או את ה־header `X-CSRF-TOKEN` בלבד — **אינו קורא גוף JSON**. לכן כל `fetch()` POST במודול חייב לשלוח את הטוקן ב־header `X-CSRF-TOKEN: window.__CSRF`.
- כל ה־UI בעברית, `dir="rtl"`, צבעים מ־CSS variables הקיימים (`--bg`, `--accent`, `--text2`, `--text3`).
- הודעות משתמש דרך `showToast(msg, 'success'|'error'|'warning')`.
- `View::e()` על כל פלט משתנה ב־views.
- `ActivityLog::log($action, $entityType, $entityId, $entityLabel, $detail)` על: יצירת
  דרישה, מחיקת שורה, ייצוא XLS. **`write()` היא `private` ומקבלת array — אל תקרא לה.**
- מפתחות הרשאה: `canReportHours` (נציג), `canManageHours` (מנהל). `canManageHours` מקנה עריכה של כל שורה של כל עובד, כולל של עצמו.
- **אכיפת בעלות:** במסלולי נציג, `user_id` נלקח תמיד מ־`Auth::user()['id']` — לעולם לא מפרמטר בקשה.
- ערכי `entry_type`: `regular`, `vacation`, `reserve`, `sick`, `duplicate_delete`, `other`.
- ערכי `requires`: `both`, `in`, `out`. ערכי `status`: `requested`, `filled`.

---

### Task 1: מחלקת החגים `Core\Holidays`

מחלץ את מפת החגים מהווידג'ט למקור אמת יחיד בצד השרת, ומוסיף "ערב חג" נגזר.

**Files:**
- Create: `src/Core/Holidays.php`
- Temporary (נמחק ב-Step 3): `public/_hol_check.php`

**Interfaces:**
- Consumes: כלום.
- Produces:
  - `Holidays::all(): array` — מפה `'YYYY-MM-DD' => ['n' => string, 't' => string]`, כולל ערבי חג שנגזרו (`t='e'`).
  - `Holidays::get(string $date): ?array` — רשומת חג לתאריך, או `null`.
  - `Holidays::dayType(string $date): string` — אחד מ־`work|fri|sat|hol|erev|chol`.
  - `Holidays::label(string $dayType): string` — תווית עברית: `רגיל|שישי|שבת|חג|ערב חג|חול המועד`.

- [ ] **Step 1: כתוב את המימוש**

צור `src/Core/Holidays.php`. העתק את מפת התאריכים **בדיוק** מ־`views/components/calendar-widget.php` שורות 174–241 (המשתנה `_CHOLS`), והמר מתחביר JS למערך PHP — אותם תאריכים, אותם שמות, אותם סוגים (`h`/`c`/`i`/`r`).

```php
<?php
declare(strict_types=1);

namespace Core;

/**
 * מקור אמת יחיד לחגים — משרת גם את הלוחות בצד השרת וגם את ווידג'ט היומן.
 * המפה הועברה לכאן מ-calendar-widget.php כדי שהייצוא יוכל לסווג ימים.
 */
class Holidays
{
    /** סוגים: h=חג  c=חול המועד  i=עצמאות  r=זיכרון  e=ערב חג (נגזר) */
    private const RAW = [
        '2024-10-02' => ['n' => 'ראש השנה', 't' => 'h'],
        // ... כל שאר הרשומות מ-calendar-widget.php:174-241, ללא שינוי ...
        '2027-10-21' => ['n' => 'שמיני עצרת', 't' => 'h'],
    ];

    private static ?array $cache = null;

    /** המפה המלאה, כולל ערבי חג שנגזרו */
    public static function all(): array
    {
        if (self::$cache !== null) return self::$cache;

        $map = self::RAW;
        foreach (self::RAW as $date => $info) {
            if ($info['t'] !== 'h') continue;
            $prev = date('Y-m-d', strtotime($date . ' -1 day'));
            // ערב חג רק אם היום שלפני אינו עצמו חג או חול המועד
            if (isset($map[$prev])) continue;
            $map[$prev] = ['n' => 'ערב ' . $info['n'], 't' => 'e'];
        }
        ksort($map);
        return self::$cache = $map;
    }

    public static function get(string $date): ?array
    {
        return self::all()[$date] ?? null;
    }

    /** work|fri|sat|hol|erev|chol — שבת ושישי גוברים על סיווג החג */
    public static function dayType(string $date): string
    {
        $dow = (int)date('w', strtotime($date)); // 0=ראשון .. 6=שבת
        if ($dow === 6) return 'sat';
        if ($dow === 5) return 'fri';

        $h = self::get($date);
        if ($h === null) return 'work';

        return match ($h['t']) {
            'h', 'i' => 'hol',
            'e'      => 'erev',
            'c'      => 'chol',
            default  => 'work', // r (יום זיכרון) הוא יום עבודה לצורך דיווח שעות
        };
    }

    public static function label(string $dayType): string
    {
        return [
            'work' => 'רגיל', 'fri' => 'שישי', 'sat' => 'שבת',
            'hol'  => 'חג',   'erev' => 'ערב חג', 'chol' => 'חול המועד',
        ][$dayType] ?? 'רגיל';
    }
}
```

**אומת:** חגי 2026 קיימים במפה המקורית (`calendar-widget.php:212-226`), כולל
`'2026-09-21' => יום כיפור (t='h')`, ולכן `2026-09-20` ייגזר כערב חג.

- [ ] **Step 2: אמת בדפדפן מול השרת**

העלה את הקובץ לשרת וצור זמנית `public/_hol_check.php`:

```php
<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
use Core\Holidays;
header('Content-Type: text/plain; charset=utf-8');

$cases = [
    ['2026-09-21', 'hol',  'יום כיפור (שני) — חג באמצע השבוע'],
    ['2026-09-20', 'erev', 'ערב יום כיפור (ראשון) — נגזר אוטומטית'],
    ['2026-09-12', 'sat',  'ראש השנה נופל בשבת — שבת גוברת'],
    ['2026-09-18', 'fri',  'שישי רגיל'],
    ['2026-09-15', 'work', 'שלישי רגיל'],
    ['2026-09-28', 'chol', 'חוה״מ סוכות (שני)'],
];
$fail = 0;
foreach ($cases as [$date, $want, $desc]) {
    $got = Holidays::dayType($date);
    $ok  = $got === $want;
    if (!$ok) $fail++;
    printf("%s  %s → %-5s (ציפינו %-5s)  %s
", $ok ? 'PASS' : 'FAIL', $date, $got, $want, $desc);
}
echo "
שם החג ב-12/09: " . (Holidays::get('2026-09-12')['n'] ?? '(אין)') . "
";
echo "תאריך ללא חג 15/09: " . var_export(Holidays::get('2026-09-15'), true) . "
";
echo $fail ? "
$fail נכשלו
" : "
הכל עבר
";
```

גש ל-`https://alon.alexisdeveloping.com/_hol_check.php`.
Expected: כל השורות `PASS`, שם החג "ראש השנה", ותאריך ללא חג `NULL`.

**אם `2026-09-20` אינו מחזיר `erev`** — בדוק ש-`2026-09-21` קיים ב-`RAW` עם
`t='h'`, ושלולאת הגזירה ב-`all()` רצה לפני ה-`ksort`.

- [ ] **Step 3: מחק את קובץ הבדיקה מהשרת**

```bash
rm public/_hol_check.php
```

**אל תשאיר אותו בשרת ואל תכניס אותו ל-git** — הוא חושף מבנה פנימי.

- [ ] **Step 4: Commit**

```bash
git add src/Core/Holidays.php
git commit -m "feat: מחלקת חגים משותפת עם גזירת ערב חג"
```

---

### Task 2: חיבור הווידג'ט למקור האמת החדש

הווידג'ט מפסיק להחזיק עותק משלו ומקבל את המפה מה־PHP. **התנהגותו הנראית לעין לא משתנה.**

**Files:**
- Modify: `views/components/calendar-widget.php:174-241` (החלפת בלוק `_CHOLS`)

**Interfaces:**
- Consumes: `Holidays::all()` מ־Task 1.
- Produces: משתנה JS גלובלי `_CHOLS` באותו מבנה בדיוק כמו קודם (`{n, t}`), כעת כולל גם רשומות `t='e'`.

- [ ] **Step 1: החלף את הבלוק הקשיח בהזרקה מה-PHP**

ב־`views/components/calendar-widget.php`, החלף את כל השורות 174–241 (מ־`var _CHOLS = {` ועד ה־`};` הסוגר) בשורה:

```php
var _CHOLS = <?= json_encode(\Core\Holidays::all(), JSON_UNESCAPED_UNICODE) ?>;
```

- [ ] **Step 2: הוסף תווית לערב חג במקרא ובחישובים**

באותו קובץ, שורה ~243, הוסף את הסוג החדש למפות הצבע והתווית:

```js
var _CHCOL={h:'#f59e0b',c:'#d97706',i:'#06b6d4',r:'#8b5cf6',e:'#fbbf24'};
var _CHLBL={h:'חג',c:'חול המועד',i:'עצמאות',r:'זיכרון',e:'ערב חג'};
```

- [ ] **Step 3: ודא שסיווג הימים בווידג'ט לא נשבר**

בשורה ~266 הפונקציה `_cCat` מסווגת `h`/`i` כ־`hol` ו־`c` כ־`chol`. ערב חג (`e`) אינו מופיע שם, ולכן ייפול ל־`work` — **זו ההתנהגות הרצויה**: ערב חג הוא יום עבודה לצורך ספירת ימי עסקים ביומן. אל תשנה את `_cCat`.

- [ ] **Step 4: בדיקה ידנית בדפדפן**

פתח את `/dashboard`, הפעל את כפתור "✡ חגים" בווידג'ט היומן, ונווט לספטמבר 2026.
Expected: החגים מסומנים כמו קודם; ספירת ימי העסקים זהה למה שהייתה לפני השינוי; אין שגיאות ב־console.

- [ ] **Step 5: Commit**

```bash
git add views/components/calendar-widget.php
git commit -m "refactor: ווידג'ט היומן צורך את מפת החגים מ-Core\\Holidays"
```

---

### Task 3: `HoursModel` — שליפות הנציג וספירת ההתראה

שכבת ה־DB לצד הנציג. נבנית ראשונה כי מסך הנציג וההתראה תלויים בה.

**Files:**
- Create: `src/Models/HoursModel.php`
- Temporary (נמחק ב-Task 9): `public/_hours_check.php`

**Interfaces:**
- Consumes: `Core\DB`.
- Produces:
  - `HoursModel::forUserMonth(int $userId, string $month): array` — `$month` בפורמט `YYYY-MM`; שורות ממוינות `work_date ASC, id ASC`.
  - `HoursModel::pendingCount(int $userId): int`
  - `HoursModel::pendingForUser(int $userId): array` — שורות `status='requested'` בלבד.
  - `HoursModel::find(int $id): ?array`
  - `HoursModel::isComplete(array $row): bool` — האם השורה עומדת בדרישת `requires`.
  - `HoursModel::createEntry(array $d): int` — מפתחות: `user_id`, `work_date`, `entry_type`, `time_in`, `time_out`, `requires`, `note`, `created_by`.
  - `HoursModel::updateEntry(int $id, array $d): void` — מפתחות: `entry_type`, `time_in`, `time_out`, `note`, `status`, `filled_by`.

- [ ] **Step 1: כתוב את המימוש**

צור `src/Models/HoursModel.php`:

```php
<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class HoursModel
{
    private const SELECT = 'SELECT id, user_id, work_date, entry_type, time_in, time_out,
                                   requires, note, status, marked_for_export, exported_at,
                                   created_by, filled_by, created_at, updated_at
                            FROM hours_entries';

    public static function find(int $id): ?array
    {
        return DB::row(self::SELECT . ' WHERE id = ?', [$id]);
    }

    /** $month בפורמט YYYY-MM */
    public static function forUserMonth(int $userId, string $month): array
    {
        return DB::query(
            self::SELECT . " WHERE user_id = ? AND DATE_FORMAT(work_date, '%Y-%m') = ?
                             ORDER BY work_date ASC, id ASC",
            [$userId, $month]
        );
    }

    public static function pendingCount(int $userId): int
    {
        return (int)DB::value(
            "SELECT COUNT(*) FROM hours_entries WHERE user_id = ? AND status = 'requested'",
            [$userId]
        );
    }

    public static function pendingForUser(int $userId): array
    {
        return DB::query(
            self::SELECT . " WHERE user_id = ? AND status = 'requested'
                             ORDER BY work_date ASC, id ASC",
            [$userId]
        );
    }

    /**
     * האם השורה עומדת בדרישה שהוגדרה לה.
     * סיבת היעדרות (כל entry_type שאינו regular) סוגרת כל שורה.
     */
    public static function isComplete(array $row): bool
    {
        if (($row['entry_type'] ?? 'regular') !== 'regular') return true;

        $in  = !empty($row['time_in']);
        $out = !empty($row['time_out']);

        return match ($row['requires'] ?? 'both') {
            'in'    => $in,
            'out'   => $out,
            default => $in && $out,
        };
    }

    public static function createEntry(array $d): int
    {
        $status = self::isComplete($d) ? 'filled' : 'requested';
        return DB::insert(
            'INSERT INTO hours_entries
               (user_id, work_date, entry_type, time_in, time_out, requires, note,
                status, created_by, filled_by)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [
                $d['user_id'], $d['work_date'], $d['entry_type'] ?? 'regular',
                $d['time_in'] ?: null, $d['time_out'] ?: null, $d['requires'] ?? 'both',
                $d['note'] ?? null, $status, $d['created_by'] ?? null,
                $status === 'filled' ? ($d['created_by'] ?? null) : null,
            ]
        );
    }

    public static function updateEntry(int $id, array $d): void
    {
        DB::execute(
            'UPDATE hours_entries
                SET entry_type = ?, time_in = ?, time_out = ?, note = ?,
                    status = ?, filled_by = ?
              WHERE id = ?',
            [
                $d['entry_type'] ?? 'regular', $d['time_in'] ?: null, $d['time_out'] ?: null,
                $d['note'] ?? null, $d['status'] ?? 'requested', $d['filled_by'] ?? null, $id,
            ]
        );
    }
}
```

- [ ] **Step 2: אמת את מטריצת `isComplete` בדפדפן**

`isComplete()` היא הלוגיקה הרגישה ביותר במודול והיא טהורה (ללא DB), ולכן
**חייבת** אימות מפורש. צור זמנית `public/_hours_check.php`:

```php
<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
use Models\HoursModel;
header('Content-Type: text/plain; charset=utf-8');

$base = ['entry_type' => 'regular', 'time_in' => null, 'time_out' => null, 'requires' => 'both'];
$cases = [
    [$base,                                                       false, 'both + ריק'],
    [['time_in' => '09:00:00'] + $base,                           false, 'both + כניסה בלבד'],
    [['time_in' => '09:00:00', 'time_out' => '17:00:00'] + $base, true,  'both + שתיהן'],
    [['requires' => 'out', 'time_out' => '17:00:00'] + $base,     true,  'out + יציאה'],
    [['requires' => 'out', 'time_in'  => '09:00:00'] + $base,     false, 'out + כניסה בלבד'],
    [['requires' => 'in',  'time_in'  => '09:00:00'] + $base,     true,  'in + כניסה'],
    [['entry_type' => 'reserve'] + $base,                         true,  'מילואים סוגר'],
    [['entry_type' => 'vacation'] + $base,                        true,  'חופש סוגר גם ב-both'],
];
$fail = 0;
foreach ($cases as [$row, $want, $desc]) {
    $got = HoursModel::isComplete($row);
    $ok  = $got === $want;
    if (!$ok) $fail++;
    printf("%s  %-28s → %s (ציפינו %s)\n", $ok ? 'PASS' : 'FAIL', $desc,
           var_export($got, true), var_export($want, true));
}
echo $fail ? "\n$fail נכשלו\n" : "\nהכל עבר\n";
```

גש ל-`https://alon.alexisdeveloping.com/_hours_check.php`.
Expected: כל 8 השורות `PASS`. **השאר את הקובץ בשרת** — Task 6 מרחיב אותו.

- [ ] **Step 3: Commit**

```bash
git add src/Models/HoursModel.php
git commit -m "feat: HoursModel — שליפות נציג, ספירת ממתינים ולוגיקת סגירת שורה"
```

---

### Task 4: רכיב הטבלה המשותף + מסך הנציג

הרכיב המשותף למסך הנציג ולמודל הדאשבורד, והעמוד `/hours` סביבו.

**Files:**
- Create: `views/components/hours-table.php`
- Create: `views/pages/hours/index.php`
- Create: `src/Controllers/HoursController.php`
- Modify: `config/routes.php` (הוספת routes)

**Interfaces:**
- Consumes: `HoursModel::forUserMonth`, `HoursModel::find`, `HoursModel::updateEntry`, `HoursModel::createEntry`, `HoursModel::isComplete`, `Holidays::dayType`, `Holidays::label`.
- Produces:
  - `View::component('hours-table', ['rows' => array, 'context' => 'page'|'modal'])` —
    **מדפיסה ישירות ומחזירה `void`** (ראה `src/Core/View.php:64`). קוראים לה כ־
    `<?php View::component(...); ?>`, לעולם לא עם `<?=` או `echo`.
  - פונקציית JS גלובלית `hoursSaveRow(entryId)` — שומרת שורה בודדת.
  - `HoursController@index`, `@saveEntry`, `@addOwnEntry`.

- [ ] **Step 1: כתוב את רכיב הטבלה**

צור `views/components/hours-table.php`:

```php
<?php
declare(strict_types=1);
use Core\View;
use Core\Holidays;

/** @var array $rows */
/** @var string $context */
$context = $context ?? 'page';
$TYPES = [
    'regular' => 'רגיל', 'vacation' => 'חופש', 'reserve' => 'מילואים',
    'sick' => 'מחלה', 'duplicate_delete' => 'למחוק דיווחים כפולים', 'other' => 'אחר',
];
$DAYS = ['א׳','ב׳','ג׳','ד׳','ה׳','ו׳','ש׳'];
?>
<table class="hours-table" data-context="<?= View::e($context) ?>">
  <thead>
    <tr>
      <th>תאריך</th><th>יום</th><th>סוג</th><th>כניסה</th><th>יציאה</th><th>הערה</th><th></th>
    </tr>
  </thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="7" class="ht-empty">אין שורות לדיווח</td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $r):
      $dt      = Holidays::dayType($r['work_date']);
      $hol     = Holidays::get($r['work_date']);
      $ts      = strtotime($r['work_date']);
      $done    = $r['status'] === 'filled';
      // ננעלים רק שדות שכבר מולאו בידי המנהל
      $lockIn  = !empty($r['time_in'])  && $r['created_by'] != $r['user_id'] && !$done;
      $lockOut = !empty($r['time_out']) && $r['created_by'] != $r['user_id'] && !$done;
      $reqIn   = in_array($r['requires'], ['both','in'],  true);
      $reqOut  = in_array($r['requires'], ['both','out'], true);
  ?>
    <tr class="ht-row ht-day-<?= View::e($dt) ?><?= $done ? ' ht-done' : '' ?>"
        data-id="<?= (int)$r['id'] ?>">
      <td><?= View::e(date('d/m', $ts)) ?></td>
      <td title="<?= View::e($hol['n'] ?? Holidays::label($dt)) ?>">
        <?= View::e($DAYS[(int)date('w', $ts)]) ?>
      </td>
      <td>
        <select class="ht-type">
          <?php foreach ($TYPES as $k => $lbl): ?>
            <option value="<?= View::e($k) ?>" <?= $r['entry_type'] === $k ? 'selected' : '' ?>>
              <?= View::e($lbl) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </td>
      <td>
        <input type="time" class="ht-in<?= $reqIn ? ' ht-req' : '' ?>"
               value="<?= View::e(substr((string)$r['time_in'], 0, 5)) ?>"
               <?= $lockIn ? 'readonly' : '' ?>>
      </td>
      <td>
        <input type="time" class="ht-out<?= $reqOut ? ' ht-req' : '' ?>"
               value="<?= View::e(substr((string)$r['time_out'], 0, 5)) ?>"
               <?= $lockOut ? 'readonly' : '' ?>>
      </td>
      <td><input type="text" class="ht-note" maxlength="500"
                 value="<?= View::e((string)$r['note']) ?>"></td>
      <td>
        <button type="button" class="ht-save" onclick="hoursSaveRow(<?= (int)$r['id'] ?>)">
          <?= $done ? '✓' : 'שמור' ?>
        </button>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<script>
/* נטען פעם אחת בלבד — הרכיב עשוי להופיע גם בעמוד וגם במודל */
if (!window.hoursSaveRow) {
window.hoursSaveRow = function (id) {
    var tr = document.querySelector('.hours-table tr[data-id="' + id + '"]');
    if (!tr) return;
    var btn = tr.querySelector('.ht-save');
    btn.disabled = true;

    fetch(window.__V2_BASE + '/hours/entry/' + id + '/save', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.__CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({
            entry_type: tr.querySelector('.ht-type').value,
            time_in:    tr.querySelector('.ht-in').value,
            time_out:   tr.querySelector('.ht-out').value,
            note:       tr.querySelector('.ht-note').value
        })
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        btn.disabled = false;
        if (d.error) { showToast(d.error, 'error'); return; }
        showToast('נשמר', 'success');
        if (d.status === 'filled') { tr.classList.add('ht-done'); btn.textContent = '✓'; }
        if (typeof hoursRefreshBadge === 'function') hoursRefreshBadge();
    })
    .catch(function () { btn.disabled = false; showToast('שגיאת רשת', 'error'); });
};

/* בחירת סיבת היעדרות מנטרלת את שדות השעות */
document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('ht-type')) return;
    var tr = e.target.closest('tr');
    var off = e.target.value !== 'regular';
    ['.ht-in', '.ht-out'].forEach(function (sel) {
        var f = tr.querySelector(sel);
        f.disabled = off;
        if (off) f.value = '';
    });
});
}
</script>
```

- [ ] **Step 2: כתוב את עמוד הנציג**

צור `views/pages/hours/index.php`:

```php
<?php
declare(strict_types=1);
use Core\View;
/** @var array $rows */
/** @var string $month */
/** @var string $monthLabel */
?>
<div class="page-head">
  <h1>דיווח שעות</h1>
  <div class="hours-nav">
    <a class="btn" href="<?= View::e(CFG['app']['url']) ?>/hours?month=<?= View::e($prevMonth) ?>">▶</a>
    <span class="hours-month"><?= View::e($monthLabel) ?></span>
    <a class="btn" href="<?= View::e(CFG['app']['url']) ?>/hours?month=<?= View::e($nextMonth) ?>">◀</a>
  </div>
</div>

<?php View::component('hours-table', ['rows' => $rows, 'context' => 'page']); ?>

<div class="hours-add">
  <input type="date" id="ha-date" value="<?= View::e(date('Y-m-d')) ?>">
  <button type="button" class="btn btn-primary" onclick="hoursAddOwn()">+ הוסף שורה</button>
</div>

<script>
function hoursAddOwn() {
    var d = document.getElementById('ha-date').value;
    if (!d) { showToast('בחר תאריך', 'warning'); return; }
    fetch(window.__V2_BASE + '/hours/entry/add', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.__CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({ work_date: d })
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (res.error) { showToast(res.error, 'error'); return; }
        location.reload();
    });
}
</script>

<style>
.hours-table{width:100%;border-collapse:collapse;margin-top:16px}
.hours-table th,.hours-table td{padding:8px 10px;text-align:right;border-bottom:1px solid var(--border,#2a2a3a)}
.hours-table th{color:var(--text3);font-weight:600;font-size:13px}
.hours-table input,.hours-table select{background:var(--bg2,#1a1a24);color:var(--text,#e6e6f0);
  border:1px solid var(--border,#2a2a3a);border-radius:6px;padding:5px 8px;font-family:inherit}
.hours-table input[readonly]{opacity:.5;cursor:not-allowed}
.hours-table input:disabled,.hours-table select:disabled{opacity:.35}
.ht-req{border-color:var(--accent,#7c5cff)!important}
.ht-day-fri,.ht-day-sat{background:rgba(255,255,255,.03)}
.ht-day-hol{background:rgba(245,158,11,.10)}
.ht-day-erev{background:rgba(251,191,36,.06)}
.ht-day-chol{background:rgba(217,119,6,.06)}
.ht-done{opacity:.65}
.ht-empty{text-align:center;color:var(--text3);padding:24px}
.ht-save{background:var(--accent,#7c5cff);color:#fff;border:0;border-radius:6px;
  padding:6px 14px;cursor:pointer;font-family:inherit}
.ht-save:disabled{opacity:.5;cursor:wait}
.hours-nav{display:flex;align-items:center;gap:12px}
.hours-month{font-weight:600;min-width:120px;text-align:center}
.hours-add{margin-top:20px;display:flex;gap:10px;align-items:center}
</style>
```

- [ ] **Step 3: כתוב את הקונטרולר**

צור `src/Controllers/HoursController.php`:

```php
<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\HoursModel;

class HoursController extends Controller
{
    private const MONTHS = ['','ינואר','פברואר','מרץ','אפריל','מאי','יוני',
                            'יולי','אוגוסט','ספטמבר','אוקטובר','נובמבר','דצמבר'];

    /** קורא גוף JSON — הקונטרולרים הקיימים קוראים $_POST, כאן ה-fetch שולח JSON */
    private function jsonBody(): array
    {
        static $body = null;
        if ($body !== null) return $body;
        $raw = file_get_contents('php://input') ?: '';
        $d = json_decode($raw, true);
        return $body = is_array($d) ? $d : [];
    }

    private function normalizeMonth(?string $m): string
    {
        return ($m && preg_match('/^\d{4}-\d{2}$/', $m)) ? $m : date('Y-m');
    }

    private function monthLabel(string $month): string
    {
        [$y, $m] = explode('-', $month);
        return self::MONTHS[(int)$m] . ' ' . $y;
    }

    public function index(): void
    {
        $this->requirePermission('canReportHours');
        $uid   = (int)Auth::user()['id'];
        $month = $this->normalizeMonth($this->get('month'));
        $ts    = strtotime($month . '-01');

        $this->view('pages/hours/index', [
            'rows'       => HoursModel::forUserMonth($uid, $month),
            'month'      => $month,
            'monthLabel' => $this->monthLabel($month),
            'prevMonth'  => date('Y-m', strtotime('-1 month', $ts)),
            'nextMonth'  => date('Y-m', strtotime('+1 month', $ts)),
        ]);
    }

    public function saveEntry(string $id): void
    {
        $this->requirePermission('canReportHours');
        $this->verifyCsrf();

        $uid = (int)Auth::user()['id'];
        $row = HoursModel::find((int)$id);

        // אכיפת בעלות — נציג נוגע רק בשורות שלו
        if (!$row || (int)$row['user_id'] !== $uid) {
            $this->json(['error' => 'אין הרשאה לשורה זו'], 403);
            return;
        }

        $b    = $this->jsonBody();
        $type = $b['entry_type'] ?? 'regular';
        $in   = $type === 'regular' ? ($b['time_in']  ?: null) : null;
        $out  = $type === 'regular' ? ($b['time_out'] ?: null) : null;

        if ($in && $out && $out <= $in) {
            $this->json(['error' => 'שעת יציאה מוקדמת משעת כניסה'], 400);
            return;
        }

        // שדות שהמנהל נעל אינם ניתנים לדריסה
        if (!empty($row['time_in'])  && (int)$row['created_by'] !== $uid) $in  = $row['time_in'];
        if (!empty($row['time_out']) && (int)$row['created_by'] !== $uid) $out = $row['time_out'];

        $candidate = ['entry_type' => $type, 'time_in' => $in, 'time_out' => $out,
                      'requires' => $row['requires']];
        $status = HoursModel::isComplete($candidate) ? 'filled' : 'requested';

        HoursModel::updateEntry((int)$id, [
            'entry_type' => $type, 'time_in' => $in, 'time_out' => $out,
            'note' => $b['note'] ?? null, 'status' => $status,
            'filled_by' => $status === 'filled' ? $uid : null,
        ]);

        $this->json(['ok' => true, 'status' => $status]);
    }

    public function addOwnEntry(): void
    {
        $this->requirePermission('canReportHours');
        $this->verifyCsrf();

        $uid  = (int)Auth::user()['id'];
        $date = $this->jsonBody()['work_date'] ?? '';

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->json(['error' => 'תאריך לא תקין'], 400);
            return;
        }
        if ($date > date('Y-m-d')) {
            $this->json(['error' => 'לא ניתן לדווח על תאריך עתידי'], 400);
            return;
        }

        $id = HoursModel::createEntry([
            'user_id' => $uid, 'work_date' => $date, 'entry_type' => 'regular',
            'time_in' => null, 'time_out' => null, 'requires' => 'both',
            'note' => null, 'created_by' => $uid,
        ]);

        $this->json(['ok' => true, 'id' => $id]);
    }
}
```

- [ ] **Step 4: הוסף routes**

ב־`config/routes.php`, לפני שורות ה־catch-all של `/stores/{sNum}` אם יש כאלה, הוסף בסוף הקובץ:

```php
// Hours Reporting — נציג
$router->get ('/hours',                  'Controllers\\HoursController@index');
$router->post('/hours/entry/add',        'Controllers\\HoursController@addOwnEntry');
$router->post('/hours/entry/{id}/save',  'Controllers\\HoursController@saveEntry');
```

- [ ] **Step 5: בדיקה ידנית**

1. הענק לעצמך `canReportHours` דרך `/users/perm-groups`.
2. פתח `/hours` — אמורה להופיע טבלה ריקה עם "אין שורות לדיווח".
3. לחץ "+ הוסף שורה" עם תאריך היום → השורה מופיעה.
4. מלא כניסה 09:00 ויציאה 17:00, לחץ שמור → טוסט "נשמר", הכפתור הופך ל־✓.
5. הוסף שורה נוספת לאותו תאריך → **שתי שורות מוצגות בנפרד** (ספק 3 מהמפרט).
6. בחר "מילואים" בשורה → שדות השעות מתאפסים ומנוטרלים; שמור → ✓.
7. נסה תאריך עתידי → טוסט שגיאה "לא ניתן לדווח על תאריך עתידי".
8. הרץ `curl` עם `id` של שורה של משתמש אחר → 403.

- [ ] **Step 6: Commit**

```bash
git add views/components/hours-table.php views/pages/hours/index.php \
        src/Controllers/HoursController.php config/routes.php
git commit -m "feat: מסך דיווח שעות לנציג עם שמירת שורה בודדת"
```

---

### Task 5: התראת הדאשבורד

אייקון עם badge ב־topbar, ומודל שמשתמש באותו רכיב טבלה.

**Files:**
- Create: `views/components/hours-modal.php`
- Modify: `views/layouts/main.php` (הוספת האייקון ל־topbar + include למודל)
- Modify: `src/Controllers/HoursController.php` (הוספת `apiPendingCount`, `apiPendingList`)
- Modify: `config/routes.php`

**Interfaces:**
- Consumes: `HoursModel::pendingCount`, `HoursModel::pendingForUser`, רכיב `hours-table` מ־Task 4.
- Produces:
  - `GET /api/hours/pending-count` → `{count: int}`
  - `GET /api/hours/pending` → `{html: string}` (הטבלה מרונדרת בשרת)
  - פונקציות JS גלובליות: `hoursRefreshBadge()`, `hoursOpenModal()`.

- [ ] **Step 1: הוסף את ה-endpoints לקונטרולר**

הוסף ל־`src/Controllers/HoursController.php`:

```php
    public function apiPendingCount(): void
    {
        $this->requireAuth();
        if (!\Core\Auth::can('canReportHours')) { $this->json(['count' => 0]); return; }
        $this->json(['count' => HoursModel::pendingCount((int)Auth::user()['id'])]);
    }

    public function apiPendingList(): void
    {
        $this->requirePermission('canReportHours');
        $rows = HoursModel::pendingForUser((int)Auth::user()['id']);

        // component() מדפיסה ומחזירה void — לוכדים את הפלט
        ob_start();
        \Core\View::component('hours-table', ['rows' => $rows, 'context' => 'modal']);
        $html = (string)ob_get_clean();

        $this->json(['html' => $html]);
    }
```

- [ ] **Step 2: הוסף routes**

```php
$router->get ('/api/hours/pending-count', 'Controllers\\HoursController@apiPendingCount');
$router->get ('/api/hours/pending',       'Controllers\\HoursController@apiPendingList');
```

- [ ] **Step 3: כתוב את המודל**

צור `views/components/hours-modal.php`:

```php
<?php declare(strict_types=1); ?>
<div id="hours-modal" class="hm-overlay" onclick="if(event.target===this)hoursCloseModal()">
  <div class="hm-box">
    <div class="hm-head">
      <h2>שעות לעדכון</h2>
      <button type="button" class="hm-x" onclick="hoursCloseModal()">✕</button>
    </div>
    <div class="hm-body" id="hours-modal-body"></div>
  </div>
</div>

<style>
.hm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9000;
  align-items:flex-start;justify-content:center;padding-top:60px}
.hm-overlay.open{display:flex}
.hm-box{background:var(--bg,#12121a);border:1px solid var(--border,#2a2a3a);border-radius:12px;
  width:min(920px,94vw);max-height:80vh;overflow:auto;padding:20px}
.hm-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.hm-x{background:none;border:0;color:var(--text3);font-size:20px;cursor:pointer}
#hours-bell{position:relative;background:none;border:0;cursor:pointer;font-size:18px;color:var(--text2);display:none}
#hours-bell.on{display:inline-block}
#hours-badge{position:absolute;top:-4px;left:-6px;background:#ef4444;color:#fff;border-radius:9px;
  font-size:10px;min-width:16px;height:16px;line-height:16px;text-align:center;padding:0 4px}
</style>

<script>
function hoursRefreshBadge() {
    fetch(window.__V2_BASE + '/api/hours/pending-count')
      .then(function (r) { return r.json(); })
      .then(function (d) {
          var bell = document.getElementById('hours-bell');
          if (!bell) return;
          if (d.count > 0) {
              bell.classList.add('on');
              document.getElementById('hours-badge').textContent = d.count;
          } else {
              bell.classList.remove('on');
          }
      });
}

function hoursOpenModal() {
    var box = document.getElementById('hours-modal-body');
    box.innerHTML = '<div style="text-align:center;padding:30px;color:var(--text3)">טוען…</div>';
    document.getElementById('hours-modal').classList.add('open');

    fetch(window.__V2_BASE + '/api/hours/pending')
      .then(function (r) { return r.json(); })
      .then(function (d) {
          box.innerHTML = d.html || '';
          /* ה-HTML שהוזרק מכיל <script> שלא רץ אוטומטית — הרכיב כבר נטען
             בעמוד, ולכן hoursSaveRow זמינה גלובלית ואין צורך להריץ אותו שוב */
      });
}

function hoursCloseModal() {
    document.getElementById('hours-modal').classList.remove('open');
    hoursRefreshBadge();
}

document.addEventListener('DOMContentLoaded', hoursRefreshBadge);
</script>
```

- [ ] **Step 4: חבר ל-layout**

ב־`views/layouts/main.php`:

1. מצא את אזור ה־topbar (שם נמצא החיפוש הכללי) והוסף לפני אזור המשתמש:

```php
<button type="button" id="hours-bell" onclick="hoursOpenModal()" title="שעות לעדכון">
  🕐<span id="hours-badge">0</span>
</button>
```

2. לפני `</body>`, הוסף:

```php
<?php View::component('hours-modal', []); ?>
```

**חשוב:** הרכיב `hours-table` מכיל `<script>` שלא ירוץ כשמזריקים אותו דרך `innerHTML`. לכן המודל מסתמך על כך ש־`hoursSaveRow` כבר הוגדרה. כדי שזה יעבוד גם בדאשבורד (שבו הרכיב אינו נוכח בעמוד), הוסף את אותו בלוק JS של `hoursSaveRow` ומאזין ה־`change` גם ל־`hours-modal.php` — ההגנה `if (!window.hoursSaveRow)` מונעת הגדרה כפולה.

- [ ] **Step 5: בדיקה ידנית**

1. מנהל (או `curl`) יוצר שורה `requested` עבורך.
2. רענן את `/dashboard` → אייקון 🕐 עם badge "1".
3. לחץ → נפתח מודל עם השורה הפתוחה בלבד.
4. מלא ושמור בתוך המודל → טוסט, השורה מקבלת ✓.
5. סגור את המודל → ה־badge נעלם (0 שורות פתוחות).
6. משתמש ללא `canReportHours` → האייקון לא מוצג כלל.

- [ ] **Step 6: Commit**

```bash
git add views/components/hours-modal.php views/layouts/main.php \
        src/Controllers/HoursController.php config/routes.php
git commit -m "feat: התראת שעות לעדכון בדאשבורד עם מודל מילוי"
```

---

### Task 6: שליפות המנהל ב-`HoursModel`

הרשת החודשית ופעולות הניהול, בשכבת ה־DB.

**Files:**
- Modify: `src/Models/HoursModel.php`
- Modify: `public/_hours_check.php` (זמני)

**Interfaces:**
- Consumes: `Core\DB`.
- Produces:
  - `HoursModel::activeUsers(): array` — `[['id' => int, 'full_name' => string], ...]`, פעילים בלבד.
  - `HoursModel::monthGrid(string $month): array` — `[userId => [ 'YYYY-MM-DD' => [rows...] ]]`.
  - `HoursModel::forUserDate(int $userId, string $date): array`
  - `HoursModel::managerUpdate(int $id, array $d): void` — כמו `updateEntry` אך גם `requires`.
  - `HoursModel::deleteEntry(int $id): void`
  - `HoursModel::setMark(int $id, bool $on): void`
  - `HoursModel::markedCount(): int`
  - `HoursModel::markedRows(): array` — כולל `full_name` של העובד, ממוין לפי שם ואז תאריך.
  - `HoursModel::stampExported(array $ids): void`

- [ ] **Step 1: הוסף את המתודות ל-HoursModel**

```php
    public static function activeUsers(): array
    {
        return DB::query(
            "SELECT id, CONCAT(first_name,' ',last_name) AS full_name
             FROM users WHERE is_active = 1 ORDER BY first_name ASC, last_name ASC"
        );
    }

    /** [userId => ['YYYY-MM-DD' => [rows...]]] */
    public static function monthGrid(string $month): array
    {
        $rows = DB::query(
            self::SELECT . " WHERE DATE_FORMAT(work_date, '%Y-%m') = ?
                             ORDER BY work_date ASC, id ASC",
            [$month]
        );
        $grid = [];
        foreach ($rows as $r) {
            $grid[(int)$r['user_id']][$r['work_date']][] = $r;
        }
        return $grid;
    }

    public static function forUserDate(int $userId, string $date): array
    {
        return DB::query(
            self::SELECT . ' WHERE user_id = ? AND work_date = ? ORDER BY id ASC',
            [$userId, $date]
        );
    }

    /** עדכון בידי מנהל — כולל requires, שהנציג אינו רשאי לשנות */
    public static function managerUpdate(int $id, array $d): void
    {
        $status = self::isComplete($d) ? 'filled' : 'requested';
        DB::execute(
            'UPDATE hours_entries
                SET entry_type = ?, time_in = ?, time_out = ?, requires = ?,
                    note = ?, status = ?, filled_by = ?
              WHERE id = ?',
            [
                $d['entry_type'] ?? 'regular', $d['time_in'] ?: null, $d['time_out'] ?: null,
                $d['requires'] ?? 'both', $d['note'] ?? null, $status,
                $status === 'filled' ? ($d['filled_by'] ?? null) : null, $id,
            ]
        );
    }

    public static function deleteEntry(int $id): void
    {
        DB::execute('DELETE FROM hours_entries WHERE id = ?', [$id]);
    }

    public static function setMark(int $id, bool $on): void
    {
        DB::execute('UPDATE hours_entries SET marked_for_export = ? WHERE id = ?', [$on ? 1 : 0, $id]);
    }

    public static function markedCount(): int
    {
        return (int)DB::value('SELECT COUNT(*) FROM hours_entries WHERE marked_for_export = 1');
    }

    public static function markedRows(): array
    {
        return DB::query(
            "SELECT h.*, CONCAT(u.first_name,' ',u.last_name) AS full_name
             FROM hours_entries h
             LEFT JOIN users u ON u.id = h.user_id
             WHERE h.marked_for_export = 1
             ORDER BY u.first_name ASC, h.work_date ASC, h.id ASC"
        );
    }

    public static function stampExported(array $ids): void
    {
        if (!$ids) return;
        $ph = implode(',', array_fill(0, count($ids), '?'));
        DB::execute("UPDATE hours_entries SET exported_at = NOW() WHERE id IN ($ph)",
                    array_map('intval', $ids));
    }
```

- [ ] **Step 2: אמת את הרשת החודשית בדפדפן**

המתודות האלה נבדקות בפועל דרך לוח המנהל (Task 7), אך `monthGrid()` מחזירה
מבנה מקונן שקל לטעות בו. הוסף בסוף `public/_hours_check.php` שנוצר ב-Task 3:

```php
echo "\n── שכבת המנהל ──\n";
$users = HoursModel::activeUsers();
printf("משתמשים פעילים: %d, לדוגמה: %s\n", count($users), $users[0]['full_name'] ?? '(אין)');

$month = date('Y-m');
$grid  = HoursModel::monthGrid($month);
printf("רשת %s: %d עובדים עם שורות\n", $month, count($grid));
foreach ($grid as $uid => $byDate) {
    $first = array_key_first($byDate);
    printf("  עובד #%d: %d תאריכים, ראשון=%s עם %d שורות\n",
           $uid, count($byDate), $first, count($byDate[$first]));
    break;
}
printf("מסומנים לייצוא: %d\n", HoursModel::markedCount());
```

גש לקובץ שוב.
Expected: רשימת משתמשים אמיתית. אם כבר יש שורות בחודש הנוכחי — הרשת מציגה
`עובד #N: X תאריכים` והשורות מקוננות נכון תחת התאריך. אם אין עדיין שורות,
`רשת: 0 עובדים` תקין — חזור לאמת אחרי Task 7.

- [ ] **Step 3: Commit**

```bash
git add src/Models/HoursModel.php
git commit -m "feat: שכבת המנהל ב-HoursModel — רשת חודשית, סימון וייצוא"
```

---

### Task 7: לוח המנהל

רשת עובדים × ימים, מודל תא, ויצירת דרישות (כולל בחירה מרובה).

**Files:**
- Create: `views/pages/hours/manage.php`
- Modify: `src/Controllers/HoursController.php`
- Modify: `config/routes.php`

**Interfaces:**
- Consumes: כל מתודות המנהל מ־Task 6, `Holidays::dayType`, `Holidays::label`.
- Produces:
  - `HoursController@manage` — העמוד.
  - `GET /api/hours/cell?user_id&date` → `{rows: [...]}`
  - `POST /hours/request/add` — גוף: `{user_id, work_date, requires, time_in, time_out, note}`
  - `POST /hours/request/bulk` — גוף: `{cells: [{user_id, work_date}], requires, note}`
  - `POST /hours/entry/{id}/update`, `POST /hours/entry/{id}/delete`, `POST /hours/mark`

- [ ] **Step 1: הוסף את מתודות הניהול לקונטרולר**

```php
    public function manage(): void
    {
        $this->requirePermission('canManageHours');
        $month = $this->normalizeMonth($this->get('month'));
        $ts    = strtotime($month . '-01');
        $days  = (int)date('t', $ts);

        $this->view('pages/hours/manage', [
            'month'       => $month,
            'monthLabel'  => $this->monthLabel($month),
            'prevMonth'   => date('Y-m', strtotime('-1 month', $ts)),
            'nextMonth'   => date('Y-m', strtotime('+1 month', $ts)),
            'days'        => $days,
            'users'       => HoursModel::activeUsers(),
            'grid'        => HoursModel::monthGrid($month),
            'markedCount' => HoursModel::markedCount(),
        ]);
    }

    public function apiCell(): void
    {
        $this->requirePermission('canManageHours');
        $uid  = (int)$this->get('user_id', 0);
        $date = (string)$this->get('date', '');
        if (!$uid || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->json(['error' => 'פרמטרים חסרים'], 400);
            return;
        }
        $this->json(['rows' => HoursModel::forUserDate($uid, $date)]);
    }

    public function addRequest(): void
    {
        $this->requirePermission('canManageHours');
        $this->verifyCsrf();
        $b = $this->jsonBody();

        $uid  = (int)($b['user_id'] ?? 0);
        $date = (string)($b['work_date'] ?? '');
        if (!$uid || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->json(['error' => 'פרמטרים חסרים'], 400);
            return;
        }

        $in  = $b['time_in']  ?: null;
        $out = $b['time_out'] ?: null;
        if ($in && $out && $out <= $in) {
            $this->json(['error' => 'שעת יציאה מוקדמת משעת כניסה'], 400);
            return;
        }

        $id = HoursModel::createEntry([
            'user_id' => $uid, 'work_date' => $date,
            'entry_type' => $b['entry_type'] ?? 'regular',
            'time_in' => $in, 'time_out' => $out,
            'requires' => in_array($b['requires'] ?? 'both', ['both','in','out'], true)
                            ? $b['requires'] : 'both',
            'note' => $b['note'] ?? null,
            'created_by' => (int)Auth::user()['id'],
        ]);

        \Core\ActivityLog::log('יצירת דרישת דיווח שעות', 'hours_entry', $id, $date,
                                 "דרישת דיווח לעובד #$uid בתאריך $date");
        $this->json(['ok' => true, 'id' => $id]);
    }

    public function addBulkRequests(): void
    {
        $this->requirePermission('canManageHours');
        $this->verifyCsrf();
        $b     = $this->jsonBody();
        $cells = $b['cells'] ?? [];
        if (!is_array($cells) || !$cells) {
            $this->json(['error' => 'לא נבחרו תאים'], 400);
            return;
        }

        $req = in_array($b['requires'] ?? 'both', ['both','in','out'], true) ? $b['requires'] : 'both';
        $me  = (int)Auth::user()['id'];
        $n   = 0;

        foreach ($cells as $c) {
            $uid  = (int)($c['user_id'] ?? 0);
            $date = (string)($c['work_date'] ?? '');
            if (!$uid || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;
            HoursModel::createEntry([
                'user_id' => $uid, 'work_date' => $date, 'entry_type' => 'regular',
                'time_in' => null, 'time_out' => null, 'requires' => $req,
                'note' => $b['note'] ?? null, 'created_by' => $me,
            ]);
            $n++;
        }

        \Core\ActivityLog::log('יצירת דרישות דיווח מרובות', 'hours_entry', null, null,
                                 "נוצרו $n דרישות דיווח");
        $this->json(['ok' => true, 'created' => $n]);
    }

    public function managerUpdate(string $id): void
    {
        $this->requirePermission('canManageHours');
        $this->verifyCsrf();
        $b = $this->jsonBody();

        if (!HoursModel::find((int)$id)) { $this->json(['error' => 'שורה לא נמצאה'], 404); return; }

        $type = $b['entry_type'] ?? 'regular';
        $in   = $type === 'regular' ? ($b['time_in']  ?: null) : null;
        $out  = $type === 'regular' ? ($b['time_out'] ?: null) : null;
        if ($in && $out && $out <= $in) {
            $this->json(['error' => 'שעת יציאה מוקדמת משעת כניסה'], 400);
            return;
        }

        HoursModel::managerUpdate((int)$id, [
            'entry_type' => $type, 'time_in' => $in, 'time_out' => $out,
            'requires' => in_array($b['requires'] ?? 'both', ['both','in','out'], true)
                            ? $b['requires'] : 'both',
            'note' => $b['note'] ?? null, 'filled_by' => (int)Auth::user()['id'],
        ]);

        $this->json(['ok' => true]);
    }

    public function deleteEntry(string $id): void
    {
        $this->requirePermission('canManageHours');
        $this->verifyCsrf();
        HoursModel::deleteEntry((int)$id);
        \Core\ActivityLog::log('מחיקת שורת דיווח שעות', 'hours_entry', (int)$id, null,
                                 "נמחקה שורת דיווח #$id");
        $this->json(['ok' => true]);
    }

    public function toggleMark(): void
    {
        $this->requirePermission('canManageHours');
        $this->verifyCsrf();
        $b = $this->jsonBody();
        HoursModel::setMark((int)($b['id'] ?? 0), (bool)($b['on'] ?? false));
        $this->json(['ok' => true, 'marked' => HoursModel::markedCount()]);
    }
```

- [ ] **Step 2: הוסף routes**

```php
// Hours Reporting — מנהל
$router->get ('/hours/manage',            'Controllers\\HoursController@manage');
$router->get ('/api/hours/cell',          'Controllers\\HoursController@apiCell');
$router->post('/hours/request/add',       'Controllers\\HoursController@addRequest');
$router->post('/hours/request/bulk',      'Controllers\\HoursController@addBulkRequests');
$router->post('/hours/entry/{id}/update', 'Controllers\\HoursController@managerUpdate');
$router->post('/hours/entry/{id}/delete', 'Controllers\\HoursController@deleteEntry');
$router->post('/hours/mark',              'Controllers\\HoursController@toggleMark');
```

- [ ] **Step 3: כתוב את עמוד הלוח**

צור `views/pages/hours/manage.php`:

```php
<?php
declare(strict_types=1);
use Core\View;
use Core\Holidays;
/** @var array $users @var array $grid @var int $days @var string $month */
$DAYS = ['א','ב','ג','ד','ה','ו','ש'];
?>
<div class="page-head">
  <h1>ניהול דיווח שעות</h1>
  <div class="hours-nav">
    <a class="btn" href="<?= View::e(CFG['app']['url']) ?>/hours/manage?month=<?= View::e($prevMonth) ?>">▶</a>
    <span class="hours-month"><?= View::e($monthLabel) ?></span>
    <a class="btn" href="<?= View::e(CFG['app']['url']) ?>/hours/manage?month=<?= View::e($nextMonth) ?>">◀</a>
  </div>
</div>

<div class="hm-bar">
  <span>נבחרו <b id="hm-marked"><?= (int)$markedCount ?></b> שורות לדיווח</span>
  <button type="button" class="btn btn-primary" onclick="hmExport()">הורד XLS</button>
  <span class="hm-hint">גרור על תאים לבחירה מרובה</span>
</div>

<div class="hm-scroll">
<table class="hm-grid">
  <thead>
    <tr>
      <th class="hm-name">עובד</th>
      <?php for ($d = 1; $d <= $days; $d++):
          $date = sprintf('%s-%02d', $month, $d);
          $dt   = Holidays::dayType($date);
          $hol  = Holidays::get($date);
      ?>
        <th class="hm-d hm-day-<?= View::e($dt) ?>"
            title="<?= View::e($hol['n'] ?? Holidays::label($dt)) ?>">
          <span class="hm-dn"><?= $d ?></span>
          <span class="hm-dw"><?= View::e($DAYS[(int)date('w', strtotime($date))]) ?></span>
        </th>
      <?php endfor; ?>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td class="hm-name"><?= View::e($u['full_name']) ?></td>
      <?php for ($d = 1; $d <= $days; $d++):
          $date = sprintf('%s-%02d', $month, $d);
          $dt   = Holidays::dayType($date);
          $rows = $grid[(int)$u['id']][$date] ?? [];
      ?>
        <td class="hm-cell hm-day-<?= View::e($dt) ?>"
            data-user="<?= (int)$u['id'] ?>" data-date="<?= View::e($date) ?>"
            onclick="hmOpenCell(<?= (int)$u['id'] ?>, '<?= View::e($date) ?>')">
          <?php foreach ($rows as $r):
              $done = $r['status'] === 'filled';
              $cls  = $r['entry_type'] !== 'regular' ? 'hm-abs' : ($done ? 'hm-ok' : 'hm-wait');
              if ($r['entry_type'] !== 'regular') {
                  $txt = ['vacation'=>'חופ׳','reserve'=>'מיל׳','sick'=>'מחל׳',
                          'duplicate_delete'=>'כפל׳','other'=>'אחר'][$r['entry_type']] ?? '—';
              } else {
                  $txt = (substr((string)$r['time_in'], 0, 5) ?: '?') . '-' .
                         (substr((string)$r['time_out'], 0, 5) ?: '?');
              }
          ?>
            <span class="hm-chip <?= $cls ?>"><?= View::e($txt) ?></span>
          <?php endforeach; ?>
        </td>
      <?php endfor; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- מודל התא -->
<div id="hm-modal" class="hm-overlay" onclick="if(event.target===this)hmClose()">
  <div class="hm-box">
    <div class="hm-head"><h2 id="hm-title">—</h2>
      <button type="button" class="hm-x" onclick="hmClose()">✕</button></div>
    <div id="hm-rows"></div>
    <hr>
    <h3>הוסף דרישה</h3>
    <div class="hm-form">
      <label>דרוש:
        <select id="hm-req">
          <option value="both">כניסה ויציאה</option>
          <option value="in">כניסה בלבד</option>
          <option value="out">יציאה בלבד</option>
        </select>
      </label>
      <label>כניסה: <input type="time" id="hm-in"></label>
      <label>יציאה: <input type="time" id="hm-out"></label>
      <label>הערה: <input type="text" id="hm-note" maxlength="500"></label>
      <button type="button" class="btn btn-primary" onclick="hmAddRequest()">הוסף</button>
    </div>
  </div>
</div>

<script>
var HM_CTX = { user: 0, date: '', sel: [], dragging: false };
var HM_TYPES = { regular:'רגיל', vacation:'חופש', reserve:'מילואים', sick:'מחלה',
                 duplicate_delete:'למחוק דיווחים כפולים', other:'אחר' };

function hmPost(url, body) {
    return fetch(window.__V2_BASE + url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.__CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    }).then(function (r) { return r.json(); });
}

/* ── מודל תא ── */
function hmOpenCell(uid, date) {
    if (HM_CTX.sel.length > 1) return;           /* בחירה מרובה פעילה */
    HM_CTX.user = uid; HM_CTX.date = date;
    document.getElementById('hm-title').textContent = date;
    document.getElementById('hm-modal').classList.add('open');
    hmLoadRows();
}

function hmLoadRows() {
    var box = document.getElementById('hm-rows');
    box.innerHTML = 'טוען…';
    fetch(window.__V2_BASE + '/api/hours/cell?user_id=' + HM_CTX.user + '&date=' + HM_CTX.date)
      .then(function (r) { return r.json(); })
      .then(function (d) {
          if (!d.rows || !d.rows.length) { box.innerHTML = '<p class="hm-none">אין שורות ליום זה</p>'; return; }
          box.innerHTML = d.rows.map(function (r) {
              return '<div class="hm-r" data-id="' + r.id + '">' +
                '<select class="r-type">' + Object.keys(HM_TYPES).map(function (k) {
                    return '<option value="' + k + '"' + (r.entry_type === k ? ' selected' : '') + '>' +
                           HM_TYPES[k] + '</option>'; }).join('') + '</select>' +
                '<input type="time" class="r-in"  value="' + (r.time_in  || '').slice(0,5) + '">' +
                '<input type="time" class="r-out" value="' + (r.time_out || '').slice(0,5) + '">' +
                '<select class="r-req">' +
                  ['both','in','out'].map(function (k) {
                      var lbl = { both:'שתיהן', in:'כניסה', out:'יציאה' }[k];
                      return '<option value="' + k + '"' + (r.requires === k ? ' selected' : '') + '>' +
                             lbl + '</option>'; }).join('') + '</select>' +
                '<input type="text" class="r-note" value="' + (r.note || '') + '" placeholder="הערה">' +
                '<label class="r-mark"><input type="checkbox" class="r-chk"' +
                   (r.marked_for_export == 1 ? ' checked' : '') + '> לדיווח</label>' +
                '<button type="button" onclick="hmSaveRow(' + r.id + ')">שמור</button>' +
                '<button type="button" class="r-del" onclick="hmDelRow(' + r.id + ')">🗑</button>' +
                '</div>';
          }).join('');

          box.querySelectorAll('.r-chk').forEach(function (chk) {
              chk.addEventListener('change', function () {
                  var id = parseInt(chk.closest('.hm-r').dataset.id, 10);
                  hmPost('/hours/mark', { id: id, on: chk.checked }).then(function (res) {
                      if (res.marked !== undefined)
                          document.getElementById('hm-marked').textContent = res.marked;
                  });
              });
          });
      });
}

function hmSaveRow(id) {
    var r = document.querySelector('.hm-r[data-id="' + id + '"]');
    hmPost('/hours/entry/' + id + '/update', {
        entry_type: r.querySelector('.r-type').value,
        time_in:    r.querySelector('.r-in').value,
        time_out:   r.querySelector('.r-out').value,
        requires:   r.querySelector('.r-req').value,
        note:       r.querySelector('.r-note').value
    }).then(function (d) {
        if (d.error) { showToast(d.error, 'error'); return; }
        showToast('נשמר', 'success');
    });
}

function hmDelRow(id) {
    if (!confirm('למחוק את השורה?')) return;
    hmPost('/hours/entry/' + id + '/delete', {}).then(function () {
        showToast('נמחק', 'success'); hmLoadRows();
    });
}

function hmAddRequest() {
    var body = {
        requires: document.getElementById('hm-req').value,
        time_in:  document.getElementById('hm-in').value,
        time_out: document.getElementById('hm-out').value,
        note:     document.getElementById('hm-note').value
    };

    /* בחירה מרובה → bulk */
    if (HM_CTX.sel.length > 1) {
        body.cells = HM_CTX.sel;
        hmPost('/hours/request/bulk', body).then(function (d) {
            if (d.error) { showToast(d.error, 'error'); return; }
            showToast('נוצרו ' + d.created + ' דרישות', 'success');
            location.reload();
        });
        return;
    }

    body.user_id = HM_CTX.user; body.work_date = HM_CTX.date;
    hmPost('/hours/request/add', body).then(function (d) {
        if (d.error) { showToast(d.error, 'error'); return; }
        showToast('נוספה דרישה', 'success');
        location.reload();
    });
}

function hmClose() {
    document.getElementById('hm-modal').classList.remove('open');
    hmClearSel();
}

/* ── בחירה מרובה בגרירה ── */
function hmClearSel() {
    HM_CTX.sel = [];
    document.querySelectorAll('.hm-cell.sel').forEach(function (c) { c.classList.remove('sel'); });
}

document.addEventListener('mousedown', function (e) {
    var c = e.target.closest('.hm-cell');
    if (!c) return;
    HM_CTX.dragging = true;
    hmClearSel();
    hmAddSel(c);
});

document.addEventListener('mouseover', function (e) {
    if (!HM_CTX.dragging) return;
    var c = e.target.closest('.hm-cell');
    if (c) hmAddSel(c);
});

document.addEventListener('mouseup', function () {
    if (!HM_CTX.dragging) return;
    HM_CTX.dragging = false;
    if (HM_CTX.sel.length > 1) {
        document.getElementById('hm-title').textContent = 'נבחרו ' + HM_CTX.sel.length + ' תאים';
        document.getElementById('hm-rows').innerHTML =
            '<p class="hm-none">בחירה מרובה — ניתן להוסיף דרישה לכולם</p>';
        document.getElementById('hm-modal').classList.add('open');
    }
});

function hmAddSel(cell) {
    if (cell.classList.contains('sel')) return;
    cell.classList.add('sel');
    HM_CTX.sel.push({ user_id: parseInt(cell.dataset.user, 10), work_date: cell.dataset.date });
}

function hmExport() {
    var n = parseInt(document.getElementById('hm-marked').textContent, 10);
    if (!n) { showToast('לא נבחרו שורות לדיווח', 'warning'); return; }
    location.href = window.__V2_BASE + '/hours/export';
}
</script>

<style>
.hm-bar{display:flex;align-items:center;gap:14px;margin:14px 0;padding:10px 14px;
  background:var(--bg2,#1a1a24);border-radius:8px}
.hm-hint{color:var(--text3);font-size:12px;margin-inline-start:auto}
.hm-scroll{overflow-x:auto;border:1px solid var(--border,#2a2a3a);border-radius:8px}
.hm-grid{border-collapse:collapse;font-size:12px}
.hm-grid th,.hm-grid td{border:1px solid var(--border,#2a2a3a);padding:2px 4px;text-align:center}
.hm-name{position:sticky;right:0;background:var(--bg,#12121a);text-align:right!important;
  min-width:130px;white-space:nowrap;z-index:2}
.hm-d{min-width:46px}
.hm-dn{display:block;font-weight:700}
.hm-dw{display:block;font-size:10px;color:var(--text3)}
.hm-cell{min-width:46px;height:34px;cursor:pointer;vertical-align:top;user-select:none}
.hm-cell:hover{outline:1px solid var(--accent,#7c5cff)}
.hm-cell.sel{background:rgba(124,92,255,.25)!important}
.hm-chip{display:block;font-size:10px;border-radius:3px;padding:1px 2px;margin:1px 0;white-space:nowrap}
.hm-ok{background:rgba(34,197,94,.20);color:#86efac}
.hm-wait{background:rgba(245,158,11,.20);color:#fcd34d}
.hm-abs{background:rgba(59,130,246,.20);color:#93c5fd}
.hm-day-fri,.hm-day-sat{background:rgba(255,255,255,.04)}
.hm-day-hol{background:rgba(245,158,11,.12)}
.hm-day-erev{background:rgba(251,191,36,.07)}
.hm-day-chol{background:rgba(217,119,6,.07)}
.hm-r{display:flex;gap:6px;align-items:center;margin-bottom:6px;flex-wrap:wrap}
.hm-r input[type=text]{flex:1;min-width:120px}
.hm-form{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
.hm-form label{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--text3)}
.hm-none{color:var(--text3);font-size:13px}
.r-del{background:none;border:0;cursor:pointer;font-size:14px}
</style>
```

- [ ] **Step 4: בדיקה ידנית**

1. הענק לעצמך `canManageHours`, פתח `/hours/manage`.
2. הרשת מציגה את כל המשתמשים הפעילים × ימי החודש; שישי/שבת/חג צבועים; ריחוף על כותרת חג מציג את שמו.
3. חיצים ◀ ▶ מעבירים חודש והכתובת מתעדכנת ל־`?month=`.
4. לחיצה על תא → מודל עם שורות היום ואזור "הוסף דרישה".
5. הוסף דרישה `requires=out` **בלי** למלא שעות → chip כתום `?-?` מופיע בתא.
6. **גרור** על 5 תאים → המודל נפתח עם "נבחרו 5 תאים"; "הוסף" → 5 דרישות נוצרות.
7. סמן צ'קבוקס "לדיווח" → המונה בפס העליון עולה.
8. מחק שורה → נעלמת מהמודל.

- [ ] **Step 5: Commit**

```bash
git add views/pages/hours/manage.php src/Controllers/HoursController.php config/routes.php
git commit -m "feat: לוח ניהול דיווח שעות חודשי עם דרישות ובחירה מרובה"
```

---

### Task 8: ייצוא XLS

הפקת SpreadsheetML 2003 מהשורות המסומנות.

**Files:**
- Create: `src/Services/HoursExporter.php`
- Modify: `src/Controllers/HoursController.php`
- Modify: `config/routes.php`
- Temporary (נמחק ב-Step 3): `public/_xls_check.php`

**Interfaces:**
- Consumes: `HoursModel::markedRows`, `HoursModel::stampExported`, `Holidays::dayType`, `Holidays::label`.
- Produces:
  - `HoursExporter::build(array $rows): string` — מחזיר XML מלא של SpreadsheetML.
  - `HoursController@exportXls` — `GET /hours/export`.

- [ ] **Step 1: כתוב את המימוש**

צור `src/Services/HoursExporter.php`:

```php
<?php
declare(strict_types=1);

namespace Services;

use Core\Holidays;

/**
 * מפיק SpreadsheetML 2003 — קובץ .xls שהוא XML.
 * נבחר על פני PhpSpreadsheet (אין Composer בפרויקט) ועל פני CSV
 * (שמפרק עמודות בעברית באקסל).
 */
class HoursExporter
{
    private const TYPES = [
        'regular' => 'רגיל', 'vacation' => 'חופש', 'reserve' => 'מילואים',
        'sick' => 'מחלה', 'duplicate_delete' => 'למחוק דיווחים כפולים', 'other' => 'אחר',
    ];
    private const DAYS = ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];
    private const HEAD = ['שם עובד','תאריך','יום','סוג יום','סוג דיווח','כניסה','יציאה','הערה'];

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
            $ts   = strtotime($r['work_date']);
            $cells = [
                (string)($r['full_name'] ?? ''),
                date('d/m/Y', $ts),
                self::DAYS[(int)date('w', $ts)],
                Holidays::label(Holidays::dayType($r['work_date'])),
                self::TYPES[$r['entry_type']] ?? $r['entry_type'],
                substr((string)$r['time_in'], 0, 5),
                substr((string)$r['time_out'], 0, 5),
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

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
```

- [ ] **Step 2: אמת את תקינות ה-XML בדפדפן**

אקסל נופל על XML שבור, ולכן זה חייב אימות לפני חיבור ה-endpoint. צור זמנית
`public/_xls_check.php`:

```php
<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
use Services\HoursExporter;
header('Content-Type: text/plain; charset=utf-8');

$xml = HoursExporter::build([
    ['full_name' => 'ישראל ישראלי', 'work_date' => '2026-09-21', 'entry_type' => 'regular',
     'time_in' => '09:00:00', 'time_out' => '17:00:00', 'note' => 'הערה & בדיקה <tag>'],
    ['full_name' => 'דנה כהן', 'work_date' => '2026-09-18', 'entry_type' => 'reserve',
     'time_in' => null, 'time_out' => null, 'note' => null],
]);

$prev  = libxml_use_internal_errors(true);
$valid = simplexml_load_string($xml) !== false;
libxml_use_internal_errors($prev);

$checks = [
    'XML תקין (קריטי)'       => $valid,
    'שם עובד מופיע'          => str_contains($xml, 'ישראל ישראלי'),
    'סוג יום חג (21/09)'     => str_contains($xml, 'חג'),
    'סוג יום שישי (18/09)'   => str_contains($xml, 'שישי'),
    'מילואים מתורגם'          => str_contains($xml, 'מילואים'),
    'שעה מפורמטת 09:00'       => str_contains($xml, '09:00'),
    'אמפרסנד מבורח'           => str_contains($xml, '&amp;'),
    'אין אמפרסנד חשוף'        => !preg_match('/&(?!amp;|lt;|gt;|quot;|apos;|#)/', $xml),
    'workbook נסגר'           => str_ends_with(trim($xml), '</Workbook>'),
];
$fail = 0;
foreach ($checks as $d => $ok) { if (!$ok) $fail++; printf("%s  %s\n", $ok ? 'PASS' : 'FAIL', $d); }
echo $fail ? "\n$fail נכשלו\n" : "\nהכל עבר\n";
echo "\n── 400 תווים ראשונים ──\n" . substr($xml, 0, 400) . "\n";
```

גש ל-`https://alon.alexisdeveloping.com/_xls_check.php`.
Expected: כל השורות `PASS`. **"XML תקין" הוא חוסם** — אם הוא נכשל, אל תמשיך.

- [ ] **Step 3: מחק את קובץ הבדיקה**

```bash
rm public/_xls_check.php
```

- [ ] **Step 4: חבר את ה-endpoint**

הוסף ל־`src/Controllers/HoursController.php`:

```php
    public function exportXls(): void
    {
        $this->requirePermission('canManageHours');

        $rows = HoursModel::markedRows();
        if (!$rows) {
            $this->json(['error' => 'לא נבחרו שורות לדיווח'], 400);
            return;
        }

        $xml = \Services\HoursExporter::build($rows);
        HoursModel::stampExported(array_column($rows, 'id'));
        \Core\ActivityLog::log('ייצוא דיווח שעות', 'hours_entry', null, null,
                                 'יוצאו ' . count($rows) . ' שורות');

        $name = 'hours-' . date('Y-m-d') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . strlen($xml));
        echo $xml;
        exit;
    }
```

הוסף route:

```php
$router->get('/hours/export', 'Controllers\\HoursController@exportXls');
```

- [ ] **Step 5: בדיקה ידנית**

1. ב־`/hours/manage`, סמן 3 שורות "לדיווח".
2. לחץ "הורד XLS" → הקובץ יורד.
3. פתח באקסל → **עברית תקינה, עמודות נפרדות**, כותרות מודגשות, עמודת "סוג יום" מציגה חג/שישי/רגיל נכון.
4. בדוק ב־DB ש־`exported_at` הוחתם על 3 השורות.
5. נקה את כל הסימונים ולחץ "הורד XLS" → טוסט אזהרה, **ללא הורדת קובץ ריק**.

- [ ] **Step 6: Commit**

```bash
git add src/Services/HoursExporter.php src/Controllers/HoursController.php config/routes.php
git commit -m "feat: ייצוא שעות ל-XLS בפורמט SpreadsheetML"
```

---

### Task 9: ניווט, הרשאות ותיעוד

חיבור אחרון: פריטי ניווט, רישום ההרשאות, ועדכון CLAUDE.md.

**Files:**
- Modify: `CLAUDE.md`
- DB: הוספת שורות ל־`nav_items` ול־`permission_group_grants` דרך הממשק

**Interfaces:**
- Consumes: הכל מהמשימות הקודמות.
- Produces: מודול נגיש מהתפריט ומתועד.

- [ ] **Step 1: הוסף פריטי ניווט**

דרך `/nav-manager` (או ישירות ב־`nav_items`), הוסף שני פריטים:

| כותרת | נתיב | אייקון | הרשאה |
|---|---|---|---|
| דיווח שעות | `/hours` | `bi-clock` | `canReportHours` |
| ניהול דיווח שעות | `/hours/manage` | `bi-calendar3` | `canManageHours` |

בדוק את מבנה `nav_items` ב־`src/Models/NavManagerModel.php` והתאם את שמות העמודות.

- [ ] **Step 2: רשום את ההרשאות**

דרך `/users/perm-groups`, הוסף את `canReportHours` ואת `canManageHours` לקבוצות הרלוונטיות.

- [ ] **Step 3: עדכן את CLAUDE.md**

בטבלת המודולים ב־`CLAUDE.md`, הוסף שתי שורות אחרי שורת "תורנות (Duty)":

```markdown
| דיווח שעות | `/hours` | HoursController | מסך נציג, שמירת שורה בודדת, מודל התראה בדאשבורד |
| ניהול דיווח שעות | `/hours/manage` | HoursController | לוח חודשי עובדים×ימים, דרישות דיווח, ייצוא XLS |
```

בסעיף "טבלאות נוספות (hardcoded, לא ב-CFG)", הוסף `hours_entries` לרשימה.

הוסף סעיף קצר תחת "Frontend":

```markdown
### חגים — `src/Core/Holidays.php`

מקור אמת יחיד לחגי ישראל. המפה הייתה קשיחה ב-JS בתוך `calendar-widget.php`
והועברה ל-PHP כדי שגם השרת יוכל לסווג ימים (נדרש לייצוא דיווח השעות).
ווידג'ט היומן צורך אותה כעת דרך `json_encode(Holidays::all())`.
`Holidays::dayType($date)` מחזיר `work|fri|sat|hol|erev|chol`; "ערב חג"
נגזר אוטומטית מהיום שלפני כל חג. **המפה מכסה ~2025–2027 וצריכה עדכון ידני.**
```

- [ ] **Step 4: מחק את קבצי הבדיקה הזמניים מהשרת**

```bash
rm -f public/_hol_check.php public/_hours_check.php public/_xls_check.php
```

ודא ששלושתם מחזירים 404, ושאף אחד מהם לא נכנס ל-git:

```bash
git status --porcelain public/
git log --stat | grep -c "_check.php"
```

Expected: אין פלט מהראשון, ו-`0` מהשני.

- [ ] **Step 5: מעבר מקצה לקצה**

1. משתמש א׳ (מנהל) פותח `/hours/manage`, גורר על 3 תאים של משתמש ב׳, מוסיף דרישה `requires=out`.
2. משתמש ב׳ מתחבר → badge "3" בדאשבורד.
3. לוחץ → ממלא יציאה בשורה אחת ושומר → badge יורד ל־"2".
4. בוחר "מילואים" בשורה שנייה → נסגרת, badge "1".
5. משתמש א׳ מרענן את הלוח → רואה ירוק וכחול בתאים.
6. מסמן "לדיווח" ומוריד XLS → הקובץ נפתח באקסל תקין.

- [ ] **Step 6: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: תיעוד מודול דיווח שעות ומחלקת החגים"
```

---

## Self-Review

**כיסוי המפרט:**

| סעיף במפרט | משימה |
|---|---|
| §2 מודל נתונים, `isComplete` | Task 3 |
| §2.3 `requires` + סיבת היעדרות כחלופה | Task 3 (לוגיקה), Task 4 (UI) |
| §3 `Core\Holidays` + ערב חג | Task 1, Task 2 |
| §4.1 מסך הנציג | Task 4 |
| §4.2 לוח המנהל + בחירה מרובה | Task 6, Task 7 |
| §4.3 סימון וייצוא XLS | Task 7 (סימון), Task 8 (ייצוא) |
| §4.4 התראת הדאשבורד | Task 5 |
| §5 מבנה הקוד | פרוס על Tasks 1–8 |
| §6 Routes | Tasks 4, 5, 7, 8 |
| §7 הרשאות ואכיפת בעלות | Task 4 (אכיפה), Task 9 (רישום) |
| §8 טיפול בשגיאות | Tasks 4, 7, 8 |
| §9 תרחישי בדיקה | שלבי הבדיקה הידנית בכל משימה + Task 9 |

**נקודות שאומתו מול הקוד לפני כתיבת התוכנית** (הממצאים כבר משוקללים בגוף המשימות):

1. **`View::component()` מדפיסה ומחזירה `void`** (`src/Core/View.php:64`) — לכן
   נקראת כ־`<?php View::component(...); ?>` ונלכדת ב־`ob_start()` כשצריך מחרוזת.
2. **`ActivityLog::write()` היא `private` ומקבלת array** — ה־API הציבורי הוא
   `ActivityLog::log($action, $entityType, $entityId, $entityLabel, $detail)`
   (`src/Core/ActivityLog.php:47`). כל הקריאות בתוכנית משתמשות בו.
3. **חגי 2026 קיימים** ב־`calendar-widget.php:212-226`. תאריכי הטסט נבחרו כך
   ששבת ושישי לא ידרסו את הסיווג: יום כיפור 21/09/2026 (שני) כחג,
   20/09 (ראשון) כערב חג, וראש השנה 12/09 (שבת) כמקרה הדריסה.
4. **`verifyCsrf()` אינו קורא גוף JSON** (`src/Core/Controller.php:81`) — לכן כל
   `fetch()` שולח `X-CSRF-TOKEN` ב-header, והקונטרולר קורא את הגוף ב־`jsonBody()`.
5. **אין PHP CLI במכונה** — ראה Global Constraints לדרכי הרצת הטסטים.

**נקודה אחת שנותרה לאימות בזמן היישום:**

- מבנה `nav_items` (שמות עמודות, שדה ההרשאה) — משפיע על Task 9 Step 1 בלבד.
  בדוק ב־`src/Models/NavManagerModel.php` לפני הוספת פריטי הניווט.
