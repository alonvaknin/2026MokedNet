<?php
use Core\View;
$base    = rtrim(CFG['app']['url'], '/');
$appName = CFG['app']['name'];

$errorMap  = ['missing' => 'יש למלא שם משתמש וסיסמה.', 'invalid' => 'שם משתמש או סיסמה שגויים.'];
$reasonMap = ['session' => 'פג תוקף החיבור. יש להתחבר מחדש.'];
$msg = $errorMap[$error ?? ''] ?? $reasonMap[$reason ?? ''] ?? null;

// Pick a random local background (guaranteed fallback)
$bgImages = ['bg1.jpg','bg2.jpg','bg3.jpg','bg4.jpg','bg5.jpg'];
$localBg  = $base . '/img/backgrounds/' . $bgImages[array_rand($bgImages)];

// Content panel: pick a random type each page load
$contentTypes = ['tehilim', 'joke', 'pasuk'];
$chosenType   = $contentTypes[array_rand($contentTypes)];

$contentBadge  = '';
$contentText   = '';
$contentSource = '';

if (!function_exists('fetchJson')) {
    function fetchJson(string $url): ?array {
        $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
        $raw = @file_get_contents($url, false, $ctx);
        if (!$raw) return null;
        return json_decode($raw, true) ?: null;
    }
}

if (!function_exists('cleanHebrewText')) {
    function cleanHebrewText(string $text): string {
        // decode HTML entities first
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // strip any remaining HTML tags
        $text = strip_tags($text);
        // remove Hebrew cantillation marks (טעמים) U+0591–U+05AF
        $text = preg_replace('/[\x{0591}-\x{05AF}]/u', '', $text);
        // remove other diacritics that clutter: U+05BD (meteg), U+05C0 (paseq), U+05C3 (sof pasuq), U+05C4, U+05C5, U+05C7
        $text = preg_replace('/[\x{05BD}\x{05C0}\x{05C3}\x{05C4}\x{05C5}\x{05C7}]/u', '', $text);
        // collapse multiple spaces / thin spaces
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }
}

if ($chosenType === 'tehilim') {
    $chapter = random_int(1, 150);
    $data    = fetchJson("https://www.sefaria.org/api/texts/Psalms.{$chapter}?context=0&pad=0&language=he");
    if ($data && !empty($data['he'])) {
        $verses        = array_slice($data['he'], 0, 3);
        $cleaned       = array_map(fn($v) => cleanHebrewText($v), $verses);
        $contentText   = htmlspecialchars(implode(' ', $cleaned), ENT_QUOTES, 'UTF-8');
        $contentSource = "תהילים פרק {$chapter}";
        $contentBadge  = '📖 תהילים';
    }
}

if ($chosenType === 'joke' || $contentText === '') {
    $hebrewJokes = [
        ['text' => 'המורה שואל את דני: "אם נותן לך שני כלבים ועוד שני כלבים, כמה כלבים יש לך?", דני עונה: "חמישה.", המורה אומר: "לא, ארבעה.", דני מחייך: "אבל יש לי כבר כלב בבית!"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'מה אמר הכדור לשער? נתראה בפנים!', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'המורה: "מושיקו, אנחנו בשיעור חשבון, תפסיק להסתכל החוצה" מושיקו: "אני סופר עננים"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'ילד: אמא, הדג שלי יודע לדבר! אמא: באמת? מה הוא אמר? ילד: כלום. אמא: אז איך אתה יודע שהוא יודע לדבר? ילד: כי כל פעם שאני מדבר אליו, הוא פותח את הפה כאילו הוא רוצה לענות, ואז מתחרט', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'איש אחד נכנס למסעדה ואמר למלצר: "יש לכם מרק בלי כלום?" המלצר אמר: "בטח." אחרי דקה חזר עם קערה ריקה. האיש טעם ואמר: "טעים, אבל חסר קצת מלח."', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'איש אחד עם שלוש שערות הלך לספר. "תעשה לי צמה", ביקש. הספר ניסה, אבל לפתע נתלשה שערה אחת. "טוב, אז תגלגל לי את שתי השערות", אמר הלקוח. הספר גלגל, ואז שוב נתלשה שערה. "אתה יודע מה?" אמר הלקוח, "תשאיר פזור."', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'אתה לא באמת מודע לכח שיש לך בידיים, עד שמשחת השיניים עומדת להיגמר....', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'ילד חוזר מהיום האחרון בלימודים, אמא: "איפה התעודה?" ילד: "חבר שלי לקח אותה כדי להפחיד את אמא שלו"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => '"אתה יודע... התינוק שלנו בן שנתים והוא הולך כבר מגיל שנה" "וואו אז הוא בטח ממש עייף"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'המורה שאל: "מה עושים כשלא יודעים את התשובה?" יוסי ענה: "מרימים יד בביטחון ומקווים שמישהו אחר ידבר קודם"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'ילדה אמרה לאמא שלה: "היום הייתי מאוד מסודרת". אמא שאלה: "באמת? סידרת את החדר?" הילדה ענתה: "לא. סידרתי לעצמי תירוץ למה לא הספקתי"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'אבא שאל: "למה אתה עומד ליד המקרר כבר עשר דקות?" הבן ענה: "אני מחכה שהוא יציע לי משהו"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'המורה שאל: "מי יכול לומר לי מה ההבדל בין פיל לעכבר?" דני הצביע ואמר: "המורה, אם אתה לא רואה את ההבדל – אני לא מתקרב לשיעור טבע יותר"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'למה הדג קיבל 100 במבחן? כי הוא שוחה בחומר', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'מה אמר הענן לשמש? סליחה, אני רק עובר רגע...', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'מורה אמר לתלמיד: “למה איחרת?” התלמיד ענה: “חלמתי שאני בדרך לבית הספר, אז החלטתי להמשיך לישון כדי לא לאחר"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'שני עפרונות נפגשו בקלמר. אחד אמר לשני: “אתה נראה קצר היום”. השני ענה: “כן, עבר עליי יום מחודד”', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'ילד אמר לאמא שלו: “אמא, אני לא רוצה ללכת לבית הספר היום”. אמא שאלה: “למה?” הוא אמר: “כי כולם שם שואלים שאלות”. אמא ענתה: “מי שואל?” הילד: “הנה, גם את מתחילה"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'ילד נכנס לחנות צעצועים ושואל: “יש לכם רובוט שעושה שיעורי בית?” המוכר עונה: “לא”. הילד נאנח: “אז בשביל מה המציאו רובוטים?"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'מה אמר הענן לשמש? “אל תדאגי, אני רק עובר”', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'מה אמרה הנעל השמאלית לימנית? לא תגיעי רחוק בלעדיי', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'ילד אחד אמר: “אני לא מאחר לבית ספר”. “אז למה הגעת בשעה עשר?” “כי באתי מוקדם להפסקה”', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'ילד אחד אמר לאבא: “קיבלתי 100!” אבא: “באמת?” הילד: “כן, 40 בחשבון ו-60 בעברית"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'מה אמר המחק לעיפרון? “אל תדאג, אני תמיד מאחוריך”', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'ילד: “אמא, היום למדנו על חשמל”. אמא: “ומה הבנת?” ילד: “שאם לא מבינים – מקבלים זרם של שיעורי בית"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'תלמיד: “אפשר להיעדר מחר?” המורה: “למה?” תלמיד: “אפשר להביא תירוץ יותר מאוחר?”', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'חולה: "דוקטור, כולם מתעלמים ממני", רופא: "הבא בתור..."', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'הרגע הזה שאתה שקוע בשיחת טלפון ופתאום אתה מוצא את עצמך מחפש גרביים במקרר...', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'איש אחד שאל את הרופא כיצד להרזות. אמר לו הרופא: "תאכל חצי ותחשוב שאכלת הכל". בסוף הפגישה שילם האיש לרופא 50 שקלים, אמר לו הרופא: "המחיר הוא 100", אמר לו האיש: "תחשוב שקבלת הכל"', 'source' => 'https://kids.hidabroot.org/jokes'],
        ['text' => 'מה אמרה הבריכה לילדים? “קפצו לבקר!"', 'source' => 'https://kids.hidabroot.org/jokes'],
                ['text' => 'המורה שואל את דני: "אם נותן לך שני כלבים ועוד שני כלבים, כמה כלבים יש לך?", דני עונה: "חמישה.", המורה אומר: "לא, ארבעה.", דני מחייך: "אבל יש לי כבר כלב בבית!"', 'source' => ''],
        ['text' => 'מה אמר הכדור לשער? נתראה בפנים!', 'source' => ''],
        ['text' => 'המורה: "מושיקו, אנחנו בשיעור חשבון, תפסיק להסתכל החוצה" מושיקו: "אני סופר עננים"', 'source' => ''],
        ['text' => 'ילד: אמא, הדג שלי יודע לדבר! אמא: באמת? מה הוא אמר? ילד: כלום. אמא: אז איך אתה יודע שהוא יודע לדבר? ילד: כי כל פעם שאני מדבר אליו, הוא פותח את הפה כאילו הוא רוצה לענות, ואז מתחרט', 'source' => ''],
        ['text' => 'איש אחד נכנס למסעדה ואמר למלצר: "יש לכם מרק בלי כלום?" המלצר אמר: "בטח." אחרי דקה חזר עם קערה ריקה. האיש טעם ואמר: "טעים, אבל חסר קצת מלח."', 'source' => ''],
        ['text' => 'איש אחד עם שלוש שערות הלך לספר. "תעשה לי צמה", ביקש. הספר ניסה, אבל לפתע נתלשה שערה אחת. "טוב, אז תגלגל לי את שתי השערות", אמר הלקוח. הספר גלגל, ואז שוב נתלשה שערה. "אתה יודע מה?" אמר הלקוח, "תשאיר פזור."', 'source' => ''],
        ['text' => 'אתה לא באמת מודע לכח שיש לך בידיים, עד שמשחת השיניים עומדת להיגמר....', 'source' => ''],
        ['text' => 'ילד חוזר מהיום האחרון בלימודים, אמא: "איפה התעודה?" ילד: "חבר שלי לקח אותה כדי להפחיד את אמא שלו"', 'source' => ''],
        ['text' => '"אתה יודע... התינוק שלנו בן שנתים והוא הולך כבר מגיל שנה" "וואו אז הוא בטח ממש עייף"', 'source' => ''],
        ['text' => 'המורה שאל: "מה עושים כשלא יודעים את התשובה?" יוסי ענה: "מרימים יד בביטחון ומקווים שמישהו אחר ידבר קודם"', 'source' => ''],
        ['text' => 'ילדה אמרה לאמא שלה: "היום הייתי מאוד מסודרת". אמא שאלה: "באמת? סידרת את החדר?" הילדה ענתה: "לא. סידרתי לעצמי תירוץ למה לא הספקתי"', 'source' => ''],
        ['text' => 'אבא שאל: "למה אתה עומד ליד המקרר כבר עשר דקות?" הבן ענה: "אני מחכה שהוא יציע לי משהו"', 'source' => ''],
        ['text' => 'המורה שאל: "מי יכול לומר לי מה ההבדל בין פיל לעכבר?" דני הצביע ואמר: "המורה, אם אתה לא רואה את ההבדל – אני לא מתקרב לשיעור טבע יותר"', 'source' => ''],
        ['text' => 'למה הדג קיבל 100 במבחן? כי הוא שוחה בחומר', 'source' => ''],
        ['text' => 'מה אמר הענן לשמש? סליחה, אני רק עובר רגע...', 'source' => ''],
        ['text' => 'מורה אמר לתלמיד: “למה איחרת?” התלמיד ענה: “חלמתי שאני בדרך לבית הספר, אז החלטתי להמשיך לישון כדי לא לאחר"', 'source' => ''],
        ['text' => 'שני עפרונות נפגשו בקלמר. אחד אמר לשני: “אתה נראה קצר היום”. השני ענה: “כן, עבר עליי יום מחודד”', 'source' => ''],
        ['text' => 'ילד אמר לאמא שלו: “אמא, אני לא רוצה ללכת לבית הספר היום”. אמא שאלה: “למה?” הוא אמר: “כי כולם שם שואלים שאלות”. אמא ענתה: “מי שואל?” הילד: “הנה, גם את מתחילה"', 'source' => ''],
        ['text' => 'ילד נכנס לחנות צעצועים ושואל: “יש לכם רובוט שעושה שיעורי בית?” המוכר עונה: “לא”. הילד נאנח: “אז בשביל מה המציאו רובוטים?"', 'source' => ''],
        ['text' => 'מה אמר הענן לשמש? “אל תדאגי, אני רק עובר”', 'source' => ''],
        ['text' => 'מה אמרה הנעל השמאלית לימנית? לא תגיעי רחוק בלעדיי', 'source' => ''],
        ['text' => 'ילד אחד אמר: “אני לא מאחר לבית ספר”. “אז למה הגעת בשעה עשר?” “כי באתי מוקדם להפסקה”', 'source' => ''],
        ['text' => 'ילד אחד אמר לאבא: “קיבלתי 100!” אבא: “באמת?” הילד: “כן, 40 בחשבון ו-60 בעברית"', 'source' => ''],
        ['text' => 'מה אמר המחק לעיפרון? “אל תדאג, אני תמיד מאחוריך”', 'source' => ''],
        ['text' => 'ילד: “אמא, היום למדנו על חשמל”. אמא: “ומה הבנת?” ילד: “שאם לא מבינים – מקבלים זרם של שיעורי בית"', 'source' => ''],
        ['text' => 'תלמיד: “אפשר להיעדר מחר?” המורה: “למה?” תלמיד: “אפשר להביא תירוץ יותר מאוחר?”', 'source' => ''],
        ['text' => 'חולה: "דוקטור, כולם מתעלמים ממני", רופא: "הבא בתור..."', 'source' => ''],
        ['text' => 'הרגע הזה שאתה שקוע בשיחת טלפון ופתאום אתה מוצא את עצמך מחפש גרביים במקרר...', 'source' => ''],
        ['text' => 'איש אחד שאל את הרופא כיצד להרזות. אמר לו הרופא: "תאכל חצי ותחשוב שאכלת הכל". בסוף הפגישה שילם האיש לרופא 50 שקלים, אמר לו הרופא: "המחיר הוא 100", אמר לו האיש: "תחשוב שקבלת הכל"', 'source' => ''],
        ['text' => 'מה אמרה הבריכה לילדים? “קפצו לבקר!"', 'source' => ''],
        ['text' => 'למה השמש לומדת תורה בבוקר? כדי להאיר את היום', 'source' => ''],
        ['text' => 'מה אמר הארטיק למקפיא? “מזל שאתה שומר על קור רוח"', 'source' => ''],
        ['text' => 'למה המקרר אף פעם לא רב עם אף אחד? כי הוא תמיד שומר על קור רוח', 'source' => ''],
        ['text' => 'למה הילד שם סוכר מתחת לכרית? כדי שיהיו לו חלומות מתוקים', 'source' => ''],
        ['text' => 'מה עושה ביצה במסיבה? מתגלגלת מצחוק', 'source' => ''],
        ['text' => 'מה מכניסים לפה, מברכים עליו ולא אוכלים אותו? תשובה: שופר', 'source' => ''],
        ['text' => 'מה אמר העפרון למחק? אל תדאג, כולנו טועים לפעמים!', 'source' => ''],
        ['text' => 'מה הקשר בין סודות לכסף? על שניים קשה לשמור.', 'source' => ''],
        ['text' => 'איך יתכן ש-100 אנשים עמדו מתחת למטריה אחת ואף אחד לא נרטב? תשובה: לא ירד גשם', 'source' => ''],
        ['text' => 'מתי קמצן מחתן את הילד שלו? כשנשברת לו כוס', 'source' => ''],
        ['text' => 'מה יותר רחוק - הירח או צרפת? ברור שצרפת... כי אותה לא רואים ואת הירח כן', 'source' => ''],
        ['text' => 'מה אמר הרעשן לילד? די! יש לי סחרחורת', 'source' => ''],
        ['text' => 'שאלו את דני למה הוא לא התחפש לכלום. דני ענה: "אני התחפשתי לאבא שלי, שחזר מהעבודה עייף ורק רוצה לישון!"', 'source' => ''],
        ['text' => 'למה הילד התחפש לרוח רפאים? כי לא היה לו כוח להשקיע - הוא פשוט לקח סדין וקרא לזה תחפושת.', 'source' => ''],
        ['text' => 'איך קוראים לאיש שמוכר מטריות בקיץ? אופטימי', 'source' => ''],
        ['text' => 'מה אמר ה-GPS לנהג שנסע לכיוון האגם? "בעוד 100 מטרים תתחיל לשחות!"', 'source' => ''],
        ['text' => 'מה אמר הקטשופ לצ\'יפס כשהוא נגמר? "זהו, אני כבר לא יכול \'לכסות\' עליך יותר."', 'source' => ''],
        ['text' => 'קובי: "היום שרפתי 2000 קלוריות. זו הפעם האחרונה שאני משאיר עוגיות בתנור והולך לנמנם"', 'source' => ''],
        ['text' => 'קובי: "ראיתי סקר שמראה שארבעה מכל חמישה אנשים סובלים מצינון בחורף. זה אומר שאחד מחמישה נהנה מזה?"', 'source' => ''],
        ['text' => 'מה אומר איש שלג לאיש השלג השני? - אתה מריח גזר?', 'source' => ''],
        ['text' => 'מה אוכל איש שלג במסעדה? - פתיתים קפואים!', 'source' => ''],
        ['text' => 'מושיקו: "אני מעדיף לא לחשוב לפני שאני מדבר. אני אוהב להיות מופתע ממה שאמרתי".', 'source' => ''],
        ['text' => 'מושיקו: "אני אוהב מאוד לאכול ביצים חיות. אני רק מוסיף להם קמח, שמן, אבקת אפייה, אבקת קקאו, פצפוצי שוקולד, ומכניס לתנור."', 'source' => ''],
        ['text' => 'ילד פוגש את השכן שלו "כשאנחנו רצים בבית זה מפריע לך?" השכן: "בטח!" הילד: "אז תגיד לאבא שלי להפסיק!"', 'source' => ''],
        ['text' => 'המורה :"אני אשאל אותך רק שאלה אחת: איפה נמצאת יפן?" התלמיד: "שם" המורה: "איפה זה שם?" התלמיד: "זו כבר שאלה שנייה!"', 'source' => ''],
        ['text' => 'איך 10 תפוחי אדמה יכולים להשביע 30 איש? - עושים מהם מרק!', 'source' => ''],
        ['text' => 'מי החיה שהאריה הכי מפחד ממנה? - הלביאה!', 'source' => ''],
        ['text' => 'מה הקשר בין כרוב לכרובית? - הם כרובי משפחה', 'source' => ''],
        ['text' => 'איש אחד הלך הלך הלך.... התעייף, התחיל לרוץ', 'source' => ''],
        ['text' => 'למה הדג לא הולך לבית ספר? כי הוא כבר שוחה בחומר', 'source' => ''],
        ['text' => 'מה ההבדל בין עכבר לפיל? תשובה: אמא לא מפחדת שהפיל יכנס הביתה.', 'source' => ''],
        ['text' => 'מה אומר הדג לחבר שלו? בוא נזרום עם זה', 'source' => ''],
        ['text' => 'למה דבורה הולכת לים המלח? בשביל לצוף.', 'source' => ''],
        ['text' => 'מה צריך הבן של הקנגורו לשלם לאימו? דמי כיס', 'source' => ''],
        ['text' => 'אדם נכנס לחנות חיות ורואה תוכי יפהפה. הוא שואל את המוכר: "התוכי הזה מדבר?" המוכר עונה: "בטח, הוא יודע שלוש שפות!" הקונה מתלהב ושואל את התוכי: "נו, אז איך אומרים \'שלום\' בצרפתית?" התוכי מסתכל עליו ועונה: "מה אני, גוגל טרנסלייט?"', 'source' => ''],
        ['text' => 'למה הפרה הולכת לבית ספר? תשובה: כדי ללמוד מווו-זיקה!', 'source' => ''],
        ['text' => 'למה זברה תמיד נראית עייפה? כי היא מסתובבת כל היום בפיג\'מה!', 'source' => ''],
        ['text' => 'לאיזו חיה מוחאים כפיים כשהיא נכנסת לחדר? ליתוש', 'source' => ''],
        ['text' => 'איך קוראים לכבשה שמתופפת בלהקה? מההההה-תופפת!', 'source' => ''],
        ['text' => 'מה אמר החילזון כשהוא עלה על הגב של הצב? "וווווו-אאאאא-ווו, איזו מהירות!"', 'source' => ''],
        ['text' => 'מוכר במכולת ללקוח: "למה הבאת סולם?" לקוח: "כי אמרו לי שהמחירים בשמיים"', 'source' => ''],
        ['text' => 'אילו בדיחות אוהב חשמלאי? מדליקות!', 'source' => ''],
        ['text' => 'איש אחד בא למשרד התעסוקה. האיש: "משרד התעסוקה?", המזכיר: "כן", האיש: "משעמם לי"', 'source' => ''],
        ['text' => 'סוס מתיישב בבר ומזמין קולה, הברמן שואל אותו: "עם קש?". משיב הסוס: עם הרבה קש.', 'source' => ''],
        ['text' => 'נקניקיה וסטייק רבים מי יותר עשיר, פתאום נכנס בשר טחון.', 'source' => ''],
        ['text' => 'למה חלב לא יכול לשחק כדורגל? כי הוא מחמיץ.', 'source' => ''],
        ['text' => 'תמיד כשאומרים לי שאני כוכב אני מאדים, ובצדק.', 'source' => ''],
        ['text' => 'יש לי המון בדיחות על אבטלה, אבל אף אחת מהן לא עובדת.', 'source' => ''],
        ['text' => 'אם תקני ציוד, אז רק ציוד תקני.', 'source' => ''],
        ['text' => 'ראיתי בחנות כלי בית סכין מריחה – מדהים איך הטכנולוגיה כיום מאפשרת לסכינים להריח.', 'source' => ''],
        ['text' => 'למה עצים לא הולכים לבית ספר? כי הם מפחדים מהחטיבה.', 'source' => ''],
        ['text' => 'סוכן ביטוח בודק כל לילה שהילדים שלו מכוסים?', 'source' => ''],
        ['text' => 'שמשון לא אהב את הדייסה שהכינה אשתו. היא הייתה דלילה.', 'source' => ''],
        ['text' => 'מה אמרה כוס התה לביסקוויט שנטבל והתפורר בתוכה? "היית לי כעך".', 'source' => ''],
        ['text' => 'וייטנאמית מטפחת את הגינה בשביל הנוי?', 'source' => ''],
        ['text' => 'ישו נכנס לבר. הברמן שואל: "מה תשתה?". ישו: "נראה לי שאלך על מים".', 'source' => ''],
        ['text' => 'אם אומרים על שואב רובוטי שהוא "משאיר אבק למתחרים", זה טוב או לא?', 'source' => ''],
        ['text' => 'כלבים אוהבים את השפה העברית בזכות שמות העצם.', 'source' => ''],
        ['text' => 'מעניין אם בסוף של חופה רפורמית, אומרים לזו שחיתנה אותך ״תודה רבה״', 'source' => ''],
        ['text' => 'העיר שנותנת תקווה לתתרנים: יריחו', 'source' => ''],
        ['text' => 'מי שעובד במפעל של סוגת אורז מלא אורז מלא', 'source' => ''],
        ['text' => "זוג מתכונן לארח חברים לארוחת ערב. הטלפון מצלצל, האישה עונה ואחרי ה\"הלו\" הראשון, מכה בראשה ואומרת: \"אשר יגורתי בא!\".\nבעלה המופתע צועק לה: \"מי זה?! אני לא זוכר שהזמנו אותו\".", 'source' => ''],
        ['text' => 'הכרובית והכרוב – הם כרובי משפחה?', 'source' => ''],
        ['text' => 'שילמתי לו 100 שקלים על משחק המילים, והוא אסף השטר.', 'source' => ''],
        ['text' => 'למה עורכי דין יודעים טוב גיאומטריה? כי הם למדו משפטים.', 'source' => ''],
        ['text' => "איש אחד רואה ברחוב את הרב שלו. הוא צועק לו: רבי, רבי!\nאז הוא ירה בו.\nלוּ רק ידע עברית.", 'source' => ''],
        ['text' => "חברה שלי השאירה לי פתק על המקרר, \"זה לא עובד! נמאס לי, אני עוזבת…\"\n\"איזו סתומה! פתחתי את המקרר, עובד מצוין. סתם הלכה!\"", 'source' => ''],
        ['text' => 'הערב אני עושה על האש עם עשרות פרופסורים. יש לי אסכלה רחבה למרות שאנטריקוט ירד השנייה מהמנגל, הוא בא-קר.', 'source' => ''],
        ['text' => "פלוני: \"אני לא יכול להירדם אחרי כוס קפה\".\nאלמוני: \"אצלי זה בדיוק הפוך, אני לא יכול לשתות קפה אחרי שאני נרדם\".", 'source' => ''],
        ['text' => "איש אחד הרגיש לא טוב והלך לרופא.\nבדק אותו הרופא ואמר לו: \"אתה בסדר, רק תיטול ויטמין בי-12\".\nענה החבר: \"בסדר, אבל איזה ויטמין אני צריך ליטול ב-12?\"", 'source' => ''],
        ['text' => "חסיד אחד נכנס לתחנת דלק.\nהמתדלק שואל אותו: \"אשראי?\"\nענה לו החסיד \"אשריך, אשריך\".", 'source' => ''],
        ['text' => "אדם עולה לאוטובוס.\n\"האוטובוס הזה ישיר?\"\nהנהג: \"בשבילך הוא גם ישיר וגם ירקוד\".", 'source' => ''],
        ['text' => 'למה היתוש החליף את הכיסא? כי הוא חרק.', 'source' => ''],
        ['text' => "\"תגיד, אתה יודע אם יש מילה ספציפית ל\'נעשה צעיר יותר\'?\"\n\"מצטער.\"\n\"אין על מה, אם אתה לא יודע אתה לא יודע\".", 'source' => ''],
        ['text' => "עכביש א\': \"וואו הרשת יצאה לי מה זה עקומה\"\nעכביש ב\': \"אל תדאג, זה קורה לטווים ביותר\"", 'source' => ''],
        ['text' => 'זרקו על רותם סלע ורק אסי עזר פה.', 'source' => ''],
        ['text' => "כשהאר\"י נסע לטיולים שנתיים בבית הספר הוא תמיד ישב בספסל האחורי.\nכי הוא מהמקובלים.", 'source' => ''],
        ['text' => 'שמעתם על הטי שירט שנתקעה מתחת לסלע? היא חולצה', 'source' => ''],
        ['text' => 'שמעתם על כתף הבקר בחנות החיות? היא אומצה.', 'source' => ''],
        ['text' => 'למה האבנים בנחל אף פעם לא מסכימות ביניהן? כי הם חלוקים.', 'source' => ''],
        ['text' => 'איך מארק צוקרברג מתנקם? סוגר חשבון.', 'source' => ''],
        ['text' => "בחור מיואש בא לרב כי לא הולך לו בדייטים.\nהרב: \"אני אתן לך סגולה!\"\nהבחור: \"לא, אבל אני רוצה בלונדינית.\"", 'source' => ''],
        ['text' => 'נגה, יש לך נפש חמה. כשאני לידך אני מאדים, ובצדק.', 'source' => ''],
        ['text' => "אדם התיישב על ספסל ליד גן ילדים.\nיוצאת הגננת ושואלת אותו: \"אתה מצפה לילד?\"\nענה האיש: \"לא, אני סתם שמן.\"", 'source' => ''],
        ['text' => "למה בנו בכביש מעגל תנועה?\nכי קר.", 'source' => ''],
        ['text' => 'אם רופא מכין קפה, זה נס רפואי?', 'source' => ''],
        ['text' => 'הוא אמר שרק הבוס מחליט והוא לא שם חוט פה.', 'source' => ''],
        ['text' => "שמעתם על ההוא שזרק על גאולה אבן, השליך על רותם סלע ועל אסף גרניט?\nאם לא הבנתם את השטויות שלי, לא נורא, חיים יבין.", 'source' => ''],
        ['text' => 'איש אחד נכנס לבית מרקחת עם חוט ביד. שאל הרוקח אם ישו לו קרם לחוט.', 'source' => ''],
        ['text' => "כרובית וגזר ערכו קרב עד המוות.\nכשהגזר עמד להפסיד הוא התחנן לחייו, אך הכרובית אינה חסה.", 'source' => ''],
        ['text' => 'שניצל וסטייק מתווכחים מי מהם יותר עשיר, ואז בא הבשר הטחון.', 'source' => ''],
        ['text' => "שני עלים יושבים על ענף. פתאום רואים שצומח עלה נוסף.\n\"מי אתה?\"\n\"אני עלה חדש\"\n*לקרוא במבטא רוסי*", 'source' => ''],
        ['text' => 'אם מישהו רכב על סוס ועזב, אז הוא פרש או לא?', 'source' => ''],
        ['text' => 'להודות למישהו בפה מלא, זה מנומס או לא?', 'source' => ''],
        ['text' => 'קיבלת משכורת? כל השאר זה בונוס.', 'source' => ''],
        ['text' => 'איך חב"דניק יודע מה לעשות כשמישהו אומר לו "תניח לי"?', 'source' => ''],
        ['text' => 'אם ארכיאולוג העלה חרס בידו, זה סימן שהוא הצליח או לא?', 'source' => ''],
        ['text' => 'האם למי שעובד במפעל לספרי קודש יש מדי יום סידורים?', 'source' => ''],
        ['text' => 'מצחיק שלאלה שהדליקו את המנורה קוראים מכבים.', 'source' => ''],
        ['text' => 'באיסטנבול לא סוגרים דלתות. הם טורקים.', 'source' => ''],
        ['text' => 'מה עושים הגויים כשהם ישנים? נוכרים.', 'source' => ''],
        ['text' => 'שקלתי הרבה לפני שהחלטתי לעשות דיאטה.', 'source' => ''],
        ['text' => 'חיפשתי את עצמי בויקיפדיה ולא מצאתי. מרגיש חסר ערך.', 'source' => ''],
        ['text' => 'יש לי חבר שלא יודע מאיפה מגיעים מי מעיין. זה נובע מבורות.', 'source' => ''],
        ['text' => "רציתי להפתיע את החברה שלי בעבודה בפרחים, אבל במקום שהיא עובדת יש שלט שאוסר כניסה לזרים.\nאז הכנסתי פרח פרח.", 'source' => ''],
        ['text' => 'אם קניתי ממישהו חתול בשק והשק היה ריק, אז קניתי חתול בשק או לא?', 'source' => ''],
        ['text' => 'מילואימניק חוזר הביתה לאחר שנתבשר על הולדת בנו וצועק לאישתו: "תראי לי את הילד, תראי לי את הילד" אז היא הרעילה את הילד…', 'source' => ''],
        ['text' => 'סבא קרוקודיל אמר לסבתא קרוקודילה: "את באה לבקר ת\'נינים?".', 'source' => ''],
        ['text' => 'צעקתי על הטבח שלי שייקח את האוכל שלו לכל ארוחות.', 'source' => ''],
        ['text' => 'זה לא הוגן שיש רק גרעינים לבנים, לבנות לא מגיע?', 'source' => ''],
        ['text' => 'האופה עובד קשה מהבוקר ולא מבקש אף-עוגה.', 'source' => ''],
        ['text' => 'למה כלבים אוהבים את השפה העברית? בגלל שמות העצם.', 'source' => ''],
        ['text' => 'קצת מוזר לראות ראש משפחת פשע תובע, חשבתי שהם יודעים לסחוט.', 'source' => ''],
        ['text' => 'כשמדען אוהב מדענית הוא מציע לה ניסויים?', 'source' => ''],
        ['text' => 'אם רוצים לשאול מה השעה, כדאי ללכת לחנות שעונים.', 'source' => ''],
        ['text' => 'הלכתי ברחוב וקראתי ספר. נתקעתי בעמוד הראשון.', 'source' => ''],
        ['text' => "מה כתוב בכניסה לחדר שינה של פולנים?\n\"פה לנים\"", 'source' => ''],
        ['text' => "צ\'יף אינדיאני נכנס לבר ומבקש כוס קפה.\nהברמן: לקחת או לשבת?\nהצ\'יף: לקחת לשבט.", 'source' => ''],
        ['text' => 'שניצל משקה את הגינה. פתאום עוברת קולה ושואלת אותו: "אתה משקה?". הוא עונה לה: "לא, אני שניצל".', 'source' => ''],
        ['text' => 'לכבל מאריך שמקצר, אפשר לקרוא סתם כבל?', 'source' => ''],
        ['text' => 'אם מתופף טובע, מה יעזור לו? מצילה.', 'source' => ''],
        ['text' => 'איך אני נדהם מאיש צבא? "או מג"ד".', 'source' => ''],
        ['text' => 'איך נעיר ירק שנרדם? "בזילי, קום!"', 'source' => ''],
        ['text' => 'מי שלא מצליח למצוא דפי כיסוי לסושי שלו: אובד אצות.', 'source' => ''],
        ['text' => 'קל לזהות פוליתאיסט. זה מובן מאליו.', 'source' => ''],
        ['text' => 'איך קיר מדבר? בטון מאיים.', 'source' => ''],
        ['text' => 'אנשים נעלמים בתוכו. לכן קוראים לזה – פוף.', 'source' => ''],
        ['text' => 'באיסטנבול לא סוגרים דלתות, הם טורקים.', 'source' => ''],
        ['text' => 'בן גוריון ניגש לגולדה מאיר ואומר לה: "גולדה, איזו צמה יפה יש לך!". עונה לו גולדה: "תודה, זה זלמן שזר".', 'source' => ''],
        ['text' => 'שני בייגלך ישבו במכולת, עד שנכנס לקוח וקנה אחד מהם. השני התחיל להתייפח בלי הפסקה. שאל המוכר: מה קרה בייגלה? ענה הבייגלה: היינו כעכים.', 'source' => ''],
        ['text' => 'אני לא אוהב עופות דורסים, הם ממש בזים לך.', 'source' => ''],
        ['text' => 'ראש הממשלה טס למדינה של קניבלים. הנשיא קיבל את פניו והשרים את שאר החלקים.', 'source' => ''],
        ['text' => 'אמרו לי "גש הלום", אז ניגשתי והלמתי.', 'source' => ''],
        ['text' => 'איך זוג צרפתים נפרדים? בגט.', 'source' => ''],
        ['text' => 'אם פנו אליי מגבעתי, כפיר וגולני, זה אומר שקיבלתי הצעות מחי"ר?', 'source' => ''],
        ['text' => 'אם אורי גלר היה קוף, הוא היה מכופף הבננות.', 'source' => ''],
        ['text' => 'הנכד של יפת היה נינוח.', 'source' => ''],
        ['text' => 'תדהמה בעולם הרכיבה על סוסים! אלוף העולם פרש!', 'source' => ''],
        ['text' => 'מצחיק שלאלה שהדליקו את המנורה קוראים מכבים.', 'source' => ''],
        ['text' => 'מה עושים גויים כשהם ישנים? נוכרים.', 'source' => ''],
        ['text' => 'שקלתי הרבה לפני שהחלטתי לעשות דיאטה.', 'source' => ''],
        ['text' => 'חיפשתי את עצמי בוויקיפדיה ולא מצאתי. מרגיש חסר ערך.', 'source' => ''],
        ['text' => 'הבוקר קניתי במאפייה קרואסון ובורקס. הקרואסון לא היה טעים אבל הבורקס פיצה.', 'source' => ''],
        ['text' => 'אם אתם רואים אגוז משונה, אל תלעגו לו. אולי אתם לא מודעים לקשיו.', 'source' => ''],
        ['text' => 'יש לי חבר שלא יודע מאיפה מגיעים מי מעיין. זה נובע מבורות.', 'source' => ''],

    ];
    $joke          = $hebrewJokes[array_rand($hebrewJokes)];
    $contentText   = htmlspecialchars($joke['text'], ENT_QUOTES, 'UTF-8');
    $contentSource = '';
    $contentBadge  = '😄 בדיחה';
    $chosenType    = 'joke';
}

if ($chosenType === 'pasuk' || $contentText === '') {
    $data = fetchJson('https://api.quotable.io/random?maxLength=120');
    if ($data && isset($data['content'])) {
        $contentText   = htmlspecialchars($data['content'], ENT_QUOTES, 'UTF-8');
        $contentSource = htmlspecialchars($data['author'] ?? '', ENT_QUOTES, 'UTF-8');
        $contentBadge  = '✨ ציטוט';
        $chosenType    = 'pasuk';
    }
}

// Hardcoded fallback if all APIs failed
if ($contentText === '') {
    $contentBadge  = '📖 תהילים';
    $contentText   = "ה' רֹעִי לֹא אֶחְסָר. בִּנְאוֹת דֶּשֶׁא יַרְבִּיצֵנִי, עַל-מֵי מְנֻחוֹת יְנַהֲלֵנִי.";
    $contentSource = 'תהילים כ״ג, א-ב';
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>כניסה — <?= View::e($appName) ?></title>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0' stop-color='%235b8dee'/%3E%3Cstop offset='1' stop-color='%237c5ce8'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='64' height='64' rx='14' fill='url(%23g)'/%3E%3Ctext x='32' y='46' font-family='Arial,sans-serif' font-size='36' font-weight='700' fill='white' text-anchor='middle'%3E%D7%9E%3C/text%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;600&family=Frank+Ruhl+Libre:wght@400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg-card-light: rgba(255,255,255,0.10);  
  --bg-card-dark: rgba(12,15,25,0.70);
  --divider: rgba(255,255,255,.07);
  --border: rgba(255,255,255,.10);
  --radius: 24px;
  --accent: #4f7fff;
  --accent2: #3d6be8;
  --text: #e8eaf0;
  --text2: #8b8fa8;
  --text3: #5a5e78;
}

body {
  font-family: 'Heebo', sans-serif;
  background: #0a0c14;
  color: var(--text);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  position: relative;
  overflow: hidden;
}

.bg-image {
  position: fixed;
  inset: 0;
  background-size: cover;
  background-position: center;
  filter: brightness(0.45);
  z-index: 0;
}

.unified-card {
  display: flex;
  flex-direction: row;
  width: 100%;
  max-width: 820px;
  background: var(--bg-card-dark);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  position: relative;
  z-index: 1;
}

.content-panel {
  flex: 1;
  padding: 44px 36px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  border-left: 1px solid var(--divider);
  text-align: right;
}

.content-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(79,127,255,.10);
  border: 1px solid rgba(79,127,255,.22);
  color: #8baeff;
  border-radius: 20px;
  padding: 4px 12px;
  font-size: 12px;
  margin-bottom: 20px;
  width: fit-content;
}

.content-text {
  font-size: 26px;
  line-height: 2.0;
  color: #d8dcea;
  font-weight: 400;
  font-family: heebo, sans-serif;
  margin-bottom: 14px;
}

.content-source {
  font-size: 12px;
  color: var(--text3);
}

.refresh-hint {
  margin-top: 28px;
  font-size: 11px;
  color: #8baeff;
  text-align: center;
  border-top: 1px solid rgba(255,255,255,.04);
  padding-top: 16px;
}

.login-panel {
  flex: 1;
  padding: 44px 36px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.brand { text-align: center; margin-bottom: 32px; }
.brand-icon {
  width: 54px; height: 54px;
  background: var(--accent);
  border-radius: 14px;
  display: grid; place-items: center;
  font-size: 22px; font-weight: 700; color: #fff;
  margin: 0 auto 14px;
}
.brand-name { font-size: 27 px; font-weight: 600; }
.brand-sub  { font-size: 16px; color: var(--text3); margin-top: 4px; }

.field { margin-bottom: 16px; }
.field label { display: block; font-size: 13px; font-weight: 500; color: var(--text2); margin-bottom: 6px; }
.field input {
  width: 100%;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.09);
  border-radius: 8px;
  padding: 11px 14px;
  font-size: 15px;
  font-family: 'Heebo', sans-serif;
  color: var(--text);
  outline: none;
  transition: border-color .15s;
  direction: ltr;
  text-align: right;
}
.field input:focus { border-color: var(--accent); }
.field input::placeholder { color: #2e3248; }

.btn-login {
  width: 100%;
  background: var(--accent);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 12px;
  font-size: 15px;
  font-weight: 500;
  font-family: 'Heebo', sans-serif;
  cursor: pointer;
  margin-top: 8px;
  transition: background .15s, transform .1s;
}
.btn-login:hover  { background: var(--accent2); }
.btn-login:active { transform: scale(.98); }

.alert-err {
  background: rgba(224,85,85,.10);
  border: 1px solid rgba(224,85,85,.25);
  color: #ef9090;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.forgot-link {
  text-align: center;
  margin-top: 14px;
  font-size: 13px;
  color: var(--text3);
  cursor: pointer;
  transition: color .15s;
}
.forgot-link:hover { color: var(--text2); }

.forgot-notice {
  background: rgba(79,127,255,.08);
  border: 1px solid rgba(79,127,255,.2);
  color: #8baeff;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  margin-top: 14px;
  display: none;
  text-align: center;
}

@media (max-width: 640px) {
  .unified-card { flex-direction: column; }
  .content-panel { border-left: none; border-bottom: 1px solid var(--divider); }
}
</style>
</head>
<body>

<div class="bg-image" id="bg-image" style="background-image:url('<?= $localBg ?>')"></div>

<div class="unified-card">

  <div class="login-panel">
    <div class="brand">
      <div class="brand-name"><?= View::e($appName) ?></div>
      <div class="brand-sub">מערכת ניהול מוקד פנים-ארגונית</div>
    </div>

    <?php if ($msg): ?>
      <div class="alert-err">⚠ <?= View::e($msg) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $base ?>/login">
      <div class="field">
        <label for="identifier">אימייל</label>
        <input type="text" id="identifier" name="identifier"
               placeholder="email@domain.com"
               autocomplete="username" autofocus>
      </div>
      <div class="field">
        <label for="password">סיסמה</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••"
               autocomplete="current-password">
      </div>
      <button type="submit" class="btn-login">כניסה</button>
    </form>

    <div class="forgot-link" onclick="showForgot()">שכחתי סיסמא</div>
    <div class="forgot-notice" id="forgot-notice">
      מטעמי אבטחה, לא ניתן לשחזר סיסמה עצמאית. יש לפנות למנהל המערכת ליצירת סיסמה חדשה.
    </div>
  </div>

    <div class="content-panel">
    <div class="content-badge"><?= htmlspecialchars($contentBadge, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="content-text"><?= $contentText ?></div>
    <?php if ($contentSource): ?>
      <div class="content-source">— <?= htmlspecialchars($contentSource, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <div class="refresh-hint">↻ רענן לקבלת תוכן חדש</div>
  </div>

</div>

<script>
function showForgot() {
  document.getElementById('forgot-notice').style.display = 'block';
}

// Try to upgrade to a fresh remote background; local fallback is already set
(function () {
  var seed = Math.floor(Math.random() * 1000);
  var remote = 'https://picsum.photos/1600/900?random=' + seed;
  var img = new Image();
  var timer = setTimeout(function () { img.src = ''; }, 4000);
  img.onload = function () {
    clearTimeout(timer);
    document.getElementById('bg-image').style.backgroundImage = "url('" + remote + "')";
  };
  img.src = remote;
})();
</script>
<?php require __DIR__ . '/../components/splash.php'; ?>
</body>
</html>
