# דיווח שעות נציגים — מסמך עיצוב

**תאריך:** 2026-09-22
**מודול:** Hours Reporting (`/hours`)

---

## 1. מטרה

מסך מהיר לדיווח שעות כניסה/יציאה של נציגים, עם ממשק ניהולי נפרד שבו מנהל
דיווח יוצר דרישות דיווח, עוקב אחרי החודש כולו, ומייצא את השורות המאושרות
לקובץ XLS.

שני סוגי משתמשים:

- **נציג** (`canReportHours`) — רואה את השורות שלו, ממלא ושומר כל שורה בנפרד.
- **מנהל דיווח** (`canManageHours`) — לוח חודשי של כל העובדים, יצירת דרישות,
  סימון לייצוא, הורדת XLS.

רק משתמשי המערכת (`users`) מדווחים — אין ישות "עובד" נפרדת.

---

## 2. מודל הנתונים

### 2.1 ישות אחת: `hours_entries`

כל שורה בטבלה היא גם "דרישת דיווח" וגם "דיווח בפועל" — אותה רשומה, בשני
מצבים. מנהל שיוצר שורה עם שעות ריקות יוצר דרישה; הנציג שממלא אותה הופך
אותה לדיווח.

ההחלטה הזו נבחרה על פני הפרדה ל־`hours_requests` + `hours_entries` כי:

- "כמה שורות לאותו תאריך" עובד מעצמו — כל שורה עצמאית.
- ספירת ההתראה בדאשבורד היא `COUNT(*) WHERE user_id=? AND status='requested'`,
  בלי JOIN.
- מקרה השימוש שהנחה את ההחלטה — המנהל מזין שעת כניסה ורוצה רק את שעת
  היציאה — הוא שורה אחת שממולאת בשני שלבים, לא דרישה שמתפצלת.

### 2.2 סכמה

הטבלה נוצרה ב־`alon_db2` (V2):

```sql
CREATE TABLE `hours_entries` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`           INT UNSIGNED NOT NULL,
  `work_date`         DATE NOT NULL,
  `entry_type`        ENUM('regular','vacation','reserve','sick','duplicate_delete','other')
                        NOT NULL DEFAULT 'regular',
  `time_in`           TIME NULL DEFAULT NULL,
  `time_out`          TIME NULL DEFAULT NULL,
  `requires`          ENUM('both','in','out') NOT NULL DEFAULT 'both',
  `note`              VARCHAR(500) NULL DEFAULT NULL,
  `status`            ENUM('requested','filled') NOT NULL DEFAULT 'requested',
  `marked_for_export` TINYINT(1) NOT NULL DEFAULT 0,
  `exported_at`       DATETIME NULL DEFAULT NULL,
  `created_by`        INT UNSIGNED NULL DEFAULT NULL,
  `filled_by`         INT UNSIGNED NULL DEFAULT NULL,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_date`   (`user_id`, `work_date`),
  KEY `idx_work_date`   (`work_date`),
  KEY `idx_user_status` (`user_id`, `status`),
  KEY `idx_export`      (`marked_for_export`, `exported_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

ללא `FOREIGN KEY`, בעקביות עם `tasks` ו־`lab_inventory_movements` בפרויקט.

### 2.3 שדה `requires`

מגדיר **מה חייבים כדי לסגור את השורה** — לא מה מותר למלא:

| ערך | משמעות | השורה נסגרת כאשר |
|---|---|---|
| `both` | דרושות כניסה ויציאה | שני השדות מלאים |
| `in` | דרושה שעת כניסה בלבד | `time_in` מלא |
| `out` | דרושה שעת יציאה בלבד | `time_out` מלא |

שני כללים נלווים:

- **שדות ריקים תמיד פתוחים לנציג.** אם המנהל דרש יציאה בלבד והשאיר את
  הכניסה ריקה, הנציג רשאי למלא גם אותה — היא פשוט לא חוסמת את הסגירה.
  ננעלים **רק** שדות שהמנהל עצמו מילא.
- **סיבת היעדרות היא תמיד חלופה של הנציג.** בכל שורה, ללא קשר ל־
  `requires`, הנציג יכול לבחור `entry_type` שאינו `regular`
  (חופש/מילואים/מחלה/מחיקת כפולים/אחר) במקום למלא שעות. הבחירה מאפסת את
  שדות השעות וסוגרת את השורה. המנהל לעולם אינו "דורש" סיבת היעדרות.

המנהל אינו חייב למלא דבר כדי ליצור דרישה: תאריך + `requires` מספיקים.

### 2.4 אין ישות "חודש"

הלוח החודשי של המנהל הוא תצוגה נגזרת — רשת של משתמשים × ימי החודש הנבנית
בצד השרת מטווח תאריכים. מעבר חודש הוא פרמטר `?month=YYYY-MM` בלבד.

---

## 3. חגים — `src/Core/Holidays.php`

### 3.1 המצב הקיים

`views/components/calendar-widget.php` מחזיק מפת חגים קשיחה ב־JavaScript
(`_CHOLS`, שורות ~200–241): `'YYYY-MM-DD' => {n: שם, t: סוג}` עם הסוגים
`h` (חג), `c` (חול המועד), `i` (עצמאות), `r` (זיכרון). מכסה בקירוב
2025–2027. אין בה "ערב חג", והיא זמינה בצד הלקוח בלבד.

### 3.2 השינוי

המפה נחלצת ל־`src/Core/Holidays.php` כמערך PHP — מקור אמת יחיד, זמין גם
לשרת (נדרש לייצוא ה־XLS) וגם ללקוח.

- הווידג'ט הקיים מקבל את המפה מוזרקת מה־PHP במקום להחזיק עותק משלו.
  **התנהגותו הנראית לעין לא משתנה.**
- נוסף סוג `e` (ערב חג), **נגזר אוטומטית** — היום שלפני כל תאריך `t:'h'`,
  אלא אם הוא עצמו חג או חול המועד.
- API של המחלקה: `Holidays::all()`, `Holidays::get(string $date): ?array`,
  `Holidays::dayType(string $date): string` המחזיר
  `work|fri|sat|hol|erev|chol`.

עדכון ידני כל כמה שנים הוא המחיר המקובל; אין Composer בפרויקט ולא נוספת
תלות רשת.

---

## 4. ממשקים

### 4.1 מסך הנציג — `/hours`

טבלת החודש הנוכחי, שורה לכל דיווח, ממוינת לפי תאריך:

| תאריך | יום | סוג | כניסה | יציאה | הערה | |
|---|---|---|---|---|---|---|
| 10/09 | ה׳ | רגיל | `09:00` נעול | `[____]` | `[____]` | שמור |
| 11/09 | ו׳ (שישי) | `[מילואים ▾]` | — | — | `[____]` | שמור |

- שדות שהמנהל כבר מילא מוצגים `readonly` ומאופרים — הנציג רואה אך לא משנה.
- שדות ריקים פתוחים תמיד; השדה שנדרש לפי `requires` מודגש כחובה, השאר
  אופציונליים.
- בחירת סוג שאינו "רגיל" מאפרת את שדות השעות וסוגרת את השורה.
- **כל שורה נשמרת בנפרד:** כפתור שמור משלה → `POST /hours/entry/{id}/save`
  → טוסט → השורה מקבלת סימון ✓ ועוברת ל־`filled`.
- כפתור "+ הוסף שורה" לאותו תאריך, לדיווח משמרת שנייה.
- רקע צבעוני לשישי/שבת/חג/ערב חג לפי `Holidays::dayType()`.

### 4.2 לוח המנהל — `/hours/manage`

רשת **עובדים בשורות × ימי החודש בעמודות** — לוח נוכחות קלאסי, קומפקטי,
נותן תמונה מיידית של מי חסר. ניווט `?month=YYYY-MM` עם חיצים ◀ ▶.

- כותרות עמודות צבועות לפי סוג היום, שם החג ב־tooltip.
- תא מציג את דיווחי אותו יום: `09:00-17:00` ירוק (הושלם), `09:00-?` כתום
  (ממתין), `מיל׳` כחול, ריק = אין שורה.
- **לחיצה על תא** פותחת מודל צד: כל שורות אותו עובד+תאריך, עריכה, ו־
  "+ הוסף דרישה" עם השדות כניסה / יציאה / `requires` / הערה; המנהל אינו
  חייב למלא שעות כלל. מנהל רשאי גם **להשלים** שורה קיימת בשם העובד.
- **בחירה מרובה:** גרירה על פני תאים (אותו דפוס range-select שקיים
  בווידג'ט היומן) → "הוסף דרישה לכל הנבחרים", ליצירת דרישה לעשרה עובדים
  באותו תאריך בפעולה אחת.

### 4.3 סימון לייצוא והורדת XLS

- צ'קבוקס "לדיווח" על כל שורה במודל התא → `marked_for_export`.
- פס עליון קבוע בלוח: "נבחרו N שורות לדיווח" + כפתור **הורד XLS**.
- הייצוא מפיק **SpreadsheetML 2003** (קובץ `.xls` שהוא XML) — נפתח נקי
  באקסל, תומך עברית UTF-8, עמודות אמיתיות, אפס תלויות. נבחר על פני
  PhpSpreadsheet (אין Composer) ועל פני CSV (עמודות לא אמינות בעברית).
- עמודות: שם עובד, תאריך, יום, סוג יום (חג/ערב חג/שישי/שבת/רגיל), סוג
  דיווח, כניסה, יציאה, הערה.
- לאחר הורדה מוצלחת מוחתם `exported_at` על השורות שיוצאו.

### 4.4 ההתראה בדאשבורד

- ב־topbar של `views/layouts/main.php`: אייקון שעון עם badge = מספר השורות
  ב־`requested` של המשתמש המחובר.
- **אין שורות פתוחות → האייקון לא מוצג כלל.**
- לחיצה פותחת מודל עם **אותו רכיב טבלה של מסך הנציג**, מסונן לשורות
  הפתוחות בלבד; אפשר למלא ולשמור בלי לעזוב את הדאשבורד.
- הספירה מ־`GET /api/hours/pending-count`.

---

## 5. מבנה הקוד

```
src/Core/Holidays.php            ← מפת חגים (נחלצת מהווידג'ט)
src/Models/HoursModel.php        ← כל גישת ה־DB
src/Controllers/HoursController.php
src/Services/HoursExporter.php   ← הפקת SpreadsheetML
views/pages/hours/index.php      ← מסך הנציג
views/pages/hours/manage.php     ← לוח המנהל
views/components/hours-table.php ← רכיב הטבלה המשותף (נציג + מודל דאשבורד)
views/components/hours-modal.php ← מודל ההתראה בדאשבורד
```

`hours-table.php` הוא הרכיב המשותף שמונע כפילות בין מסך הנציג למודל
הדאשבורד — אותו HTML, אותו JS, מקבל את מערך השורות כפרמטר.

`HoursExporter` מופרד מה־Controller כדי שהפקת ה־XML תהיה ניתנת לבדיקה
עצמאית, בדפוס של `GlassixService`.

---

## 6. Routes

```php
// עמודים
$router->get ('/hours',                     'Controllers\HoursController@index');
$router->get ('/hours/manage',              'Controllers\HoursController@manage');

// נציג
$router->post('/hours/entry/{id}/save',     'Controllers\HoursController@saveEntry');
$router->post('/hours/entry/add',           'Controllers\HoursController@addOwnEntry');
$router->get ('/api/hours/pending-count',   'Controllers\HoursController@apiPendingCount');
$router->get ('/api/hours/pending',         'Controllers\HoursController@apiPendingList');

// מנהל
$router->get ('/api/hours/month',           'Controllers\HoursController@apiMonth');
$router->get ('/api/hours/cell',            'Controllers\HoursController@apiCell');
$router->post('/hours/request/add',         'Controllers\HoursController@addRequest');
$router->post('/hours/request/bulk',        'Controllers\HoursController@addBulkRequests');
$router->post('/hours/entry/{id}/update',   'Controllers\HoursController@managerUpdate');
$router->post('/hours/entry/{id}/delete',   'Controllers\HoursController@deleteEntry');
$router->post('/hours/mark',                'Controllers\HoursController@toggleMark');
$router->get ('/hours/export',              'Controllers\HoursController@exportXls');
```

---

## 7. הרשאות

שני מפתחות חדשים בדפוס הקיים (`permission_group_grants`, ניתנים דרך
`/users/perm-groups`):

| מפתח | מקנה |
|---|---|
| `canReportHours` | `/hours`, שמירת שורות עצמיות, מודל הדאשבורד |
| `canManageHours` | `/hours/manage`, יצירת דרישות, סימון, ייצוא |

`canManageHours` מקנה עריכה והשלמה של **כל שורה של כל עובד, כולל של המנהל
עצמו** — מנהל יכול למלא שעות בשם נציג, ולדווח על עצמו, מתוך אותו לוח. אין
צורך ב־`canReportHours` בנוסף; היא נדרשת רק למי שמדווח ואינו מנהל.

**אכיפה קריטית:** במסלולי ה**נציג** בלבד, השאילתה מסננת
`WHERE user_id = ?` מתוך `Auth::user()` — לעולם לא מתוך פרמטר של הבקשה.
נציג לא יכול לקרוא או לשנות שורה של נציג אחר גם אם ינחש `id`. מסלולי
המנהל (`canManageHours`) פטורים מהסינון הזה במכוון.

---

## 8. טיפול בשגיאות

- **ולידציית זמנים:** `time_out` מוקדם מ־`time_in` באותה שורה → שגיאה
  "שעת יציאה מוקדמת משעת כניסה". אין משמרות לילה במוקד, כך שכל שורה
  מתחילה ומסתיימת באותו יום.
- **תאריך עתידי:** דיווח על תאריך עתידי נחסם לנציג; המנהל כן יכול ליצור
  דרישה מראש.
- **בעלות:** ניסיון גישה לשורה של משתמש אחר → 403.
- **CSRF:** `verifyCsrf()` בכל POST, לפי הדפוס הקיים.
- **ייצוא ריק:** לחיצה על "הורד XLS" ללא שורות מסומנות → טוסט אזהרה, לא
  קובץ ריק.
- **ActivityLog::write()** על: יצירת דרישה, מחיקת שורה, ייצוא XLS.

---

## 9. בדיקות

הפרויקט ללא תשתית בדיקות אוטומטית. הבדיקה ידנית, לפי התרחישים:

1. מנהל יוצר דרישה `requires='out'` עם `time_in=09:00` → הנציג רואה כניסה
   נעולה ויציאה פעילה בלבד.
2. הנציג ממלא יציאה ושומר → השורה `filled`, הספירה בדאשבורד יורדת ב־1.
3. הנציג מוסיף שורה שנייה לאותו תאריך → שתי השורות מוצגות ונשמרות בנפרד.
4. בחירת "מילואים" בשורה שנדרשה בה יציאה → שדות השעות מאופרים, השורה
   `filled` למרות שלא מולאה יציאה.
4a. מנהל יוצר דרישה `requires='out'` **בלי** למלא כניסה → הנציג רואה שני
   שדות פתוחים, יציאה מודגשת כחובה; מילוי יציאה בלבד סוגר את השורה.
4b. מנהל משלים שעות בשם נציג מתוך הלוח → השורה `filled`,
   `filled_by` = המנהל.
5. גרירה על 5 עובדים באותו תאריך → 5 דרישות נוצרות.
6. סימון 3 שורות והורדת XLS → הקובץ נפתח באקסל עם עברית תקינה, ו־
   `exported_at` מוחתם.
7. ספטמבר 2026: 18/09 (ערב ראש השנה) מסומן "ערב חג", 19/09 "חג".
8. נציג ללא `canManageHours` הניגש ל־`/hours/manage` → נחסם.
9. נציג המנסה לשמור `entry_id` של נציג אחר → 403.

---

## 10. מחוץ לגבולות

לא נכלל בגרסה זו:

- חישוב שכר, שעות נוספות, או צבירת ימי חופשה.
- אישור/דחייה של דיווח על ידי המנהל (מעבר לסימון "לדיווח").
- מסך ניהול חגים ידני — `Holidays.php` מתעדכן בקוד. אם יתברר צורך לערוך
  ידנית (יום גשר, חופשה מרוכזת), זו שדרוגית לטבלת `holidays` ב־DB.
- ייבוא שעות ממערכת נוכחות חיצונית.
