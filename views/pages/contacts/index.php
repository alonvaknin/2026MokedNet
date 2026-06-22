<?php
/**
 * @var array[] $contacts
 * @var array[] $depts
 * @var string[] $types
 * @var string   $q
 * @var string   $dept
 * @var string   $type
 * @var bool     $canEdit
 */
use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';

// כולל גם לא-פעילים (Controller מחזיר רק פעילים, אבל טבלה מציגה הכל)
$typeColors = [
    'נותן שירות'  => ['color'=>'#10b981','bg'=>'rgba(16,185,129,.12)','border'=>'rgba(16,185,129,.3)'],
    'פנים ארגוני' => ['color'=>'#5b8dee','bg'=>'rgba(91,141,238,.12)','border'=>'rgba(91,141,238,.3)'],
    'ספק'         => ['color'=>'#f59e0b','bg'=>'rgba(245,158,11,.12)','border'=>'rgba(245,158,11,.3)'],
    'תמיכה טכנית'=> ['color'=>'#06b6d4','bg'=>'rgba(6,182,212,.12)', 'border'=>'rgba(6,182,212,.3)'],
    'איש קשר'     => ['color'=>'#8b5cf6','bg'=>'rgba(139,92,246,.12)','border'=>'rgba(139,92,246,.3)'],
    'אחר'         => ['color'=>'#7c829c','bg'=>'rgba(124,130,156,.1)','border'=>'rgba(124,130,156,.25)'],
];
$avatarColors = ['#5b8dee','#8b5cf6','#10b981','#f59e0b','#ec4899','#06b6d4','#f97316'];
?>

<!-- ── Page header ── -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <div class="page-title" style="margin-bottom:4px;">מאגר אנשי קשר ונותני שירות</div>
    <div style="font-size:13px;color:var(--text3);"><?= count($contacts) ?> רשומות</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <?php if ($canEdit): ?>
    <button class="btn btn-primary" onclick="openCtEdit(null)">
      <i class="bi bi-person-plus-fill"></i> חדש
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- ── Toolbar ── -->
<div class="card" style="padding:12px 14px;margin-bottom:14px;">
  <!-- Row 1: search + dept + view toggle -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:10px;">
    <div class="ct-srch">
      <i class="bi bi-search"></i>
      <input type="text" id="ct-search" placeholder="חיפוש שם, טלפון, תפקיד, תגית..."
             value="<?= View::e($q) ?>" oninput="ctFilter()">
      <button id="ct-clear" onclick="ctClearSearch()" style="display:<?= $q?'block':'none' ?>;">✕</button>
    </div>
    <select id="ct-dept-filter" class="ct-sel" onchange="ctFilter()">
      <option value="">כל המחלקות</option>
      <?php foreach ($depts as $d): ?>
        <option value="<?= View::e($d['department']) ?>" <?= $dept===$d['department']?'selected':'' ?>>
          <?= View::e($d['department']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <div style="flex:1;"></div>
    <div id="ct-result-count" style="font-size:12px;color:var(--text3);"></div>
    <div class="view-toggle">
      <button id="ct-btn-grid"  onclick="ctSetView('grid')"  title="גריד"><i class="bi bi-grid-3x3-gap-fill"></i></button>
      <button id="ct-btn-table" onclick="ctSetView('table')" title="טבלה"><i class="bi bi-list-ul"></i></button>
    </div>
  </div>
  <!-- Row 2: type pills (multi-toggle) -->
  <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
    <span style="font-size:11px;color:var(--text3);flex-shrink:0;">סינון לפי סוג:</span>
    <?php foreach ($typeColors as $tn => $tc):
      $cnt = count(array_filter($contacts, fn($c) => ($c['contact_type']??'איש קשר') === $tn));
      if (!$cnt) continue;
    ?>
    <button class="ct-type-pill"
            data-type="<?= View::e($tn) ?>"
            style="--pill-color:<?= $tc['color'] ?>;--pill-bg:<?= $tc['bg'] ?>;--pill-border:<?= $tc['border'] ?>;"
            onclick="ctToggleType(this)">
      <?= View::e($tn) ?> <span class="ct-pill-cnt"><?= $cnt ?></span>
    </button>
    <?php endforeach; ?>
    <button id="ct-type-clear" onclick="ctClearTypes()"
            style="display:none;font-size:11px;padding:3px 8px;background:none;border:1px solid var(--border);
                   border-radius:10px;color:var(--text3);cursor:pointer;font-family:var(--font);">
      נקה סינון
    </button>
  </div>
</div>

<!-- ── Grid view ── -->
<div id="ct-grid" class="ct-grid">
<?php foreach ($contacts as $c):
  $ctype   = $c['contact_type'] ?? 'איש קשר';
  $tc      = $typeColors[$ctype] ?? $typeColors['אחר'];
  $initials= mb_substr($c['first_name']??'?',0,1).mb_substr($c['last_name']??'',0,1);
  $acolor  = $avatarColors[abs(crc32(($c['first_name']??'').($c['last_name']??''))) % count($avatarColors)];
  $tags    = array_filter(array_map('trim', explode(',', $c['tags'] ?? '')));
?>
<div class="ct-card"
     data-id="<?= (int)$c['id'] ?>"
     data-name="<?= strtolower(View::e(($c['first_name']??'').' '.($c['last_name']??''))) ?>"
     data-phone="<?= View::e($c['phone']??'') ?>"
     data-type="<?= View::e($ctype) ?>"
     data-dept="<?= View::e($c['department']??'') ?>"
     data-tags="<?= strtolower(View::e($c['tags']??'')) ?>"
     data-role="<?= strtolower(View::e($c['role']??'')) ?>"
     onclick="openCtView(<?= (int)$c['id'] ?>)"
     style="border-right:3px solid <?= $tc['color'] ?>;">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
    <span class="ct-type-badge" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>;border-color:<?= $tc['border'] ?>;">
      <?= View::e($ctype) ?>
    </span>
    <?php if ($canEdit): ?>
    <button class="ct-edit-btn" onclick="event.stopPropagation();openCtEdit(<?= (int)$c['id'] ?>)" title="ערוך">
      <i class="bi bi-pencil-fill"></i>
    </button>
    <?php endif; ?>
  </div>
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
    <div class="ct-avatar" style="background:<?= $acolor ?>;"><?= View::e($initials) ?></div>
    <div style="min-width:0;">
      <div style="font-weight:700;font-size:14px;line-height:1.3;"><?= View::e(($c['first_name']??'').' '.($c['last_name']??'')) ?></div>
      <?php if ($c['role']): ?>
        <div style="font-size:11px;color:var(--text3);"><?= View::e($c['role']) ?></div>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($c['phone']): ?>
    <a href="tel:<?= View::e($c['phone']) ?>" onclick="event.stopPropagation()" class="ct-meta ct-phone"><i class="bi bi-telephone-fill"></i><?= View::e($c['phone']) ?></a>
  <?php endif; ?>
  <?php if ($c['phone2']): ?>
    <a href="tel:<?= View::e($c['phone2']) ?>" onclick="event.stopPropagation()" class="ct-meta ct-phone" style="opacity:.7;"><i class="bi bi-telephone"></i><?= View::e($c['phone2']) ?></a>
  <?php endif; ?>
  <?php if ($c['email']): ?>
    <a href="mailto:<?= View::e($c['email']) ?>" onclick="event.stopPropagation()" class="ct-meta" style="color:var(--text2);font-size:11px;"><i class="bi bi-envelope-fill"></i><?= View::e($c['email']) ?></a>
  <?php endif; ?>
  <?php if ($c['address']): ?>
    <div class="ct-meta" style="color:var(--text3);font-size:11px;"><i class="bi bi-geo-alt-fill"></i><?= View::e($c['address']) ?></div>
  <?php endif; ?>
  <?php if ($tags): ?>
    <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:8px;">
      <?php foreach (array_slice($tags,0,4) as $tag): ?>
        <span class="ct-tag"><?= View::e($tag) ?></span>
      <?php endforeach; ?>
      <?php if (count($tags)>4): ?><span class="ct-tag" style="opacity:.5;">+<?= count($tags)-4 ?></span><?php endif; ?>
    </div>
  <?php endif; ?>
  <?php if ($c['note']): ?>
    <div style="font-size:11px;color:var(--text3);margin-top:7px;border-top:1px solid var(--border);padding-top:6px;line-height:1.4;">
      <?= View::e(mb_substr($c['note'],0,65)) ?><?= mb_strlen($c['note'])>65?'…':'' ?>
    </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<!-- ── Table view ── -->
<div id="ct-table" style="display:none;">
  <div class="card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
      <thead>
        <tr style="background:var(--bg3);">
          <th class="cth">שם</th>
          <th class="cth">סוג</th>
          <th class="cth">טלפון</th>
          <th class="cth">תפקיד / מחלקה</th>
          <th class="cth">תגיות</th>
          <th class="cth">תכתובות</th>
          <th class="cth">סטטוס</th>
          <?php if ($canEdit): ?><th class="cth" style="min-width:110px;"></th><?php endif; ?>
        </tr>
      </thead>
      <tbody id="ct-tbody">
      <?php
      // הטבלה מציגה הכל — כולל לא-פעילים (Controller מחזיר all)
      // נסדר: פעילים קודם, אז לא-פעילים
      $sorted = array_merge(
        array_filter($contacts, fn($c) => (int)($c['is_active']??1) === 1),
        array_filter($contacts, fn($c) => (int)($c['is_active']??1) === 0)
      );
      foreach ($sorted as $c):
        $ctype    = $c['contact_type'] ?? 'איש קשר';
        $tc       = $typeColors[$ctype] ?? $typeColors['אחר'];
        $tags     = array_filter(array_map('trim', explode(',', $c['tags'] ?? '')));
        $initials = mb_substr($c['first_name']??'?',0,1).mb_substr($c['last_name']??'',0,1);
        $acolor   = $avatarColors[abs(crc32(($c['first_name']??'').($c['last_name']??''))) % count($avatarColors)];
        $isActive = (int)($c['is_active'] ?? 1) === 1;
        $isCl     = (int)($c['is_contacts_list'] ?? 0) === 1;
      ?>
      <tr class="ct-row <?= $isActive?'':'ct-row-inactive' ?>"
          data-id="<?= (int)$c['id'] ?>"
          data-name="<?= strtolower(View::e(($c['first_name']??'').' '.($c['last_name']??''))) ?>"
          data-phone="<?= View::e($c['phone']??'') ?>"
          data-type="<?= View::e($ctype) ?>"
          data-dept="<?= View::e($c['department']??'') ?>"
          data-tags="<?= strtolower(View::e($c['tags']??'')) ?>"
          data-role="<?= strtolower(View::e($c['role']??'')) ?>"
          data-active="<?= $isActive?'1':'0' ?>"
          onclick="openCtView(<?= (int)$c['id'] ?>)">
        <td class="ctd">
          <div style="display:flex;align-items:center;gap:10px;">
            <div class="ct-avatar" style="background:<?= $acolor ?>;width:34px;height:34px;font-size:12px;flex-shrink:0;"><?= View::e($initials) ?></div>
            <div>
              <div style="font-weight:600;font-size:14px;"><?= View::e(($c['first_name']??'').' '.($c['last_name']??'')) ?></div>
              <?php if ($c['email']): ?>
                <div style="font-size:11px;color:var(--text3);direction:ltr;text-align:right;"><?= View::e($c['email']) ?></div>
              <?php endif; ?>
            </div>
          </div>
        </td>
        <td class="ctd">
          <span class="ct-type-badge" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>;border-color:<?= $tc['border'] ?>;"><?= View::e($ctype) ?></span>
        </td>
        <td class="ctd" style="direction:ltr;text-align:right;white-space:nowrap;">
          <?php if ($c['phone']): ?>
            <a href="tel:<?= View::e($c['phone']) ?>" onclick="event.stopPropagation()"
               style="color:var(--accent);text-decoration:none;font-size:14px;font-weight:500;"><?= View::e($c['phone']) ?></a>
          <?php else: ?><span style="color:var(--text3);">—</span><?php endif; ?>
          <?php if ($c['phone2']): ?>
            <div style="font-size:12px;color:var(--text3);"><?= View::e($c['phone2']) ?></div>
          <?php endif; ?>
        </td>
        <td class="ctd" style="color:var(--text2);">
          <?php if ($c['role']): ?><div style="font-size:14px;"><?= View::e($c['role']) ?></div><?php endif; ?>
          <?php if ($c['department']): ?><div style="font-size:12px;color:var(--text3);"><?= View::e($c['department']) ?></div><?php endif; ?>
          <?php if (!$c['role'] && !$c['department']): ?>—<?php endif; ?>
        </td>
        <td class="ctd">
          <div style="display:flex;flex-wrap:wrap;gap:3px;">
            <?php foreach (array_slice($tags,0,3) as $tag): ?>
              <span class="ct-tag"><?= View::e($tag) ?></span>
            <?php endforeach; ?>
            <?php if (count($tags)>3): ?><span class="ct-tag" style="opacity:.5;">+<?= count($tags)-3 ?></span><?php endif; ?>
          </div>
        </td>
        <td class="ctd" style="text-align:center;">
          <?php if ($isCl): ?>
            <span title="איש קשר לתכתובות"
                  style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;
                         color:#10b981;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);
                         border-radius:12px;padding:2px 8px;">
              <i class="bi bi-envelope-check-fill"></i> פעיל
            </span>
          <?php else: ?>
            <span style="color:var(--border2);font-size:18px;">—</span>
          <?php endif; ?>
        </td>
        <td class="ctd" style="text-align:center;">
          <?php if ($isActive): ?>
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;
                         color:#22c55e;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);
                         border-radius:12px;padding:2px 9px;">
              <i class="bi bi-circle-fill" style="font-size:7px;"></i> פעיל
            </span>
          <?php else: ?>
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;
                         color:var(--text3);background:var(--bg4);border:1px solid var(--border);
                         border-radius:12px;padding:2px 9px;">
              <i class="bi bi-circle" style="font-size:7px;"></i> לא פעיל
            </span>
          <?php endif; ?>
        </td>
        <?php if ($canEdit): ?>
        <td class="ctd" onclick="event.stopPropagation()">
          <div style="display:flex;gap:6px;">
            <button class="row-act" onclick="openCtEdit(<?= (int)$c['id'] ?>)" title="ערוך">
              <i class="bi bi-pencil-fill"></i> ערוך
            </button>
            <button class="row-act <?= $isActive?'row-act-danger':'' ?>"
                    onclick="doCtToggle(<?= (int)$c['id'] ?>)"
                    title="<?= $isActive?'השבת':'הפעל' ?>">
              <i class="bi bi-toggle-<?= $isActive?'on':'off' ?>"></i>
              <?= $isActive?'השבת':'הפעל' ?>
            </button>
          </div>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="ct-no-results" style="display:none;text-align:center;padding:50px;color:var(--text3);">
  <i class="bi bi-search" style="font-size:32px;display:block;margin-bottom:10px;opacity:.35;"></i>לא נמצאו תוצאות
</div>

<!-- ── View modal ── -->
<div id="ctv-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:400;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:480px;max-height:88vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg2);z-index:1;">
      <div id="ctv-title" style="font-size:16px;font-weight:700;display:flex;align-items:center;gap:10px;"></div>
      <div style="display:flex;gap:8px;align-items:center;">
        <?php if ($canEdit): ?>
          <button id="ctv-edit-btn" class="btn btn-ghost" style="padding:5px 10px;font-size:12px;" onclick="switchToCtEdit()">
            <i class="bi bi-pencil-fill"></i> ערוך
          </button>
        <?php endif; ?>
        <button onclick="closeCtView()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
      </div>
    </div>
    <div id="ctv-body" style="padding:20px;"></div>
  </div>
</div>

<!-- ── Edit modal ── -->
<?php if ($canEdit): ?>
<div id="cte-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:560px;max-height:92vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg2);z-index:1;">
      <div id="cte-title" style="font-size:16px;font-weight:700;"></div>
      <button onclick="closeCtEdit()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;">✕</button>
    </div>
    <div style="padding:20px;">
      <input type="hidden" id="cte-id">

      <div class="mf-section" style="--mc:#5b8dee;">
        <div class="mf-title"><i class="bi bi-person-fill"></i> פרטים אישיים</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div><label class="flabel">שם פרטי *</label><input id="cte-fname" type="text" class="finput"></div>
          <div><label class="flabel">שם משפחה</label><input id="cte-lname" type="text" class="finput"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div>
            <label class="flabel">סוג איש קשר</label>
            <select id="cte-type" class="finput">
              <?php foreach ($types as $t): ?>
                <option value="<?= View::e($t) ?>"><?= View::e($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="display:flex;align-items:flex-end;padding-bottom:2px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" id="cte-active" checked style="accent-color:var(--accent);width:16px;height:16px;">
              <span style="font-size:14px;">פעיל</span>
            </label>
          </div>
        </div>
      </div>

      <!-- is_contacts_list toggle -->
      <div style="background:var(--bg4);border:1px solid var(--border);border-radius:8px;
                  padding:10px 14px;margin-bottom:12px;
                  display:flex;align-items:center;justify-content:space-between;">
        <div>
          <div style="font-size:13px;font-weight:600;color:var(--text);">
            <i class="bi bi-envelope-check-fill" style="color:var(--accent);margin-left:5px;"></i>
            איש קשר לתכתובות
          </div>
          <div style="font-size:11px;color:var(--text3);margin-top:2px;">יופיע ברשימת המיילים לשליחת התראות אוטומטיות (חובה מייל)</div>
        </div>
        <label style="cursor:pointer;position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0;">
          <input type="checkbox" id="cte-contacts-list" style="opacity:0;width:0;height:0;position:absolute;">
          <span id="cte-cl-track" style="display:block;width:100%;height:100%;background:var(--border2);border-radius:12px;transition:background .2s;position:relative;">
            <span id="cte-cl-thumb" style="position:absolute;top:3px;right:3px;width:18px;height:18px;
              background:#fff;border-radius:50%;transition:right .2s;box-shadow:0 1px 4px rgba(0,0,0,.3);"></span>
          </span>
        </label>
      </div>

      <div class="mf-section" style="--mc:#10b981;">
        <div class="mf-title"><i class="bi bi-telephone-fill"></i> יצירת קשר</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div><label class="flabel">טלפון ראשי</label><input id="cte-phone" type="text" class="finput" dir="ltr"></div>
          <div><label class="flabel">טלפון נוסף</label><input id="cte-phone2" type="text" class="finput" dir="ltr"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div><label class="flabel">מייל</label><input id="cte-email" type="text" class="finput" dir="ltr"></div>
          <div><label class="flabel">אתר</label><input id="cte-website" type="text" class="finput" dir="ltr" placeholder="https://..."></div>
        </div>
      </div>

      <div class="mf-section" style="--mc:#f59e0b;">
        <div class="mf-title"><i class="bi bi-building"></i> תפקיד ומיקום</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div><label class="flabel">תפקיד</label><input id="cte-role" type="text" class="finput"></div>
          <div><label class="flabel">מחלקה / חברה</label><input id="cte-dept" type="text" class="finput"></div>
        </div>
        <div><label class="flabel">כתובת</label><input id="cte-address" type="text" class="finput"></div>
      </div>

      <div class="mf-section" style="--mc:#06b6d4;">
        <div class="mf-title"><i class="bi bi-tags-fill"></i> תגיות</div>
        <div style="font-size:11px;color:var(--text3);margin-bottom:6px;">הפרד בפסיקים. לדוגמה: תמיכה, ספק אינטרנט, חשמל</div>
        <input id="cte-tags" type="text" class="finput" placeholder="תגית1, תגית2, תגית3">
        <div id="cte-tags-preview" style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px;"></div>
      </div>

      <div class="mf-section" style="--mc:#8b5cf6;">
        <div class="mf-title"><i class="bi bi-sticky-fill"></i> הערה</div>
        <textarea id="cte-note" rows="2" class="finput" style="resize:vertical;"></textarea>
      </div>

      <div id="cte-error" style="color:var(--danger);font-size:13px;margin-bottom:10px;display:none;padding:8px 12px;background:rgba(239,68,68,.1);border-radius:6px;"></div>
      <div style="display:flex;gap:10px;">
        <button class="btn btn-primary" style="flex:1;" onclick="saveCtContact()"><i class="bi bi-check-lg"></i> שמור</button>
        <button class="btn btn-ghost" onclick="closeCtEdit()">ביטול</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<style>
/* ── Form ── */
.flabel{display:block;font-size:12px;color:var(--text2);margin-bottom:5px;font-weight:500}
.finput{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--text);font-size:14px;font-family:var(--font);outline:none;transition:border-color .15s}
.finput:focus{border-color:var(--accent)}
.mf-section{background:var(--bg3);border:1px solid var(--border);border-right:3px solid var(--mc,var(--accent));border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:12px}
.mf-title{font-size:11px;font-weight:700;color:var(--mc,var(--accent));text-transform:uppercase;letter-spacing:.07em;margin-bottom:12px;display:flex;align-items:center;gap:6px}
/* ── Toolbar ── */
.ct-srch{display:flex;align-items:center;gap:7px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0 10px;flex:1;min-width:180px;max-width:320px;transition:border-color .15s}
.ct-srch:focus-within{border-color:var(--accent)}
.ct-srch i{color:var(--text3);font-size:13px;flex-shrink:0}
.ct-srch input{background:none;border:none;outline:none;color:var(--text);font-family:var(--font);font-size:13px;padding:7px 0;width:100%}
.ct-srch input::placeholder{color:var(--text3)}
#ct-clear{background:none;border:none;color:var(--text3);cursor:pointer;font-size:13px;padding:0}
.ct-sel{background:var(--bg3);border:1px solid var(--border);border-radius:7px;padding:7px 10px;color:var(--text);font-size:12px;font-family:var(--font);outline:none}
/* ── Type pills ── */
.ct-type-pill{
  display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;
  border:1px solid var(--border);background:var(--bg3);color:var(--text3);
  cursor:pointer;font-size:12px;font-family:var(--font);font-weight:500;
  transition:all .15s;
}
.ct-type-pill:hover{border-color:var(--pill-color);color:var(--pill-color);}
.ct-type-pill.active{
  background:var(--pill-bg);border-color:var(--pill-border);color:var(--pill-color);font-weight:700;
}
.ct-pill-cnt{font-size:11px;opacity:.75;}
/* ── View toggle ── */
.view-toggle{display:flex;gap:3px;background:var(--bg3);border:1px solid var(--border);border-radius:7px;padding:3px}
.view-toggle button{background:none;border:none;padding:4px 8px;border-radius:5px;color:var(--text2);cursor:pointer;font-size:15px;transition:background .13s,color .13s}
.view-toggle button.active{background:var(--accent-dim);color:var(--accent)}
/* ── Grid ── */
.ct-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}
.ct-card{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:14px;cursor:pointer;transition:transform .18s,box-shadow .18s,border-color .15s;position:relative}
.ct-card:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,.4);border-color:var(--border2)}
.ct-avatar{width:40px;height:40px;border-radius:50%;display:grid;place-items:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0}
.ct-type-badge{display:inline-block;font-size:11px;font-weight:600;padding:2px 9px;border-radius:12px;border:1px solid;white-space:nowrap}
.ct-meta{font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none;padding:2px 0;color:var(--text2);margin-top:3px}
.ct-phone{color:var(--accent)!important}
.ct-phone:hover{text-decoration:underline}
.ct-tag{font-size:10px;background:var(--bg4);border:1px solid var(--border2);border-radius:10px;padding:1px 7px;color:var(--text3)}
.ct-edit-btn{background:var(--bg4);border:1px solid var(--border);border-radius:5px;padding:3px 7px;cursor:pointer;font-size:11px;color:var(--text3);transition:all .13s}
.ct-edit-btn:hover{background:var(--accent-dim);color:var(--accent)}
/* ── Table ── */
.cth{padding:10px 14px;text-align:right;font-weight:600;font-size:12px;border-bottom:1px solid var(--border);color:var(--text2);white-space:nowrap}
.ctd{padding:11px 14px;vertical-align:middle}
.ct-row{cursor:pointer;border-bottom:1px solid var(--border);transition:background .1s}
.ct-row:hover td{background:var(--bg3);}
.ct-row-inactive td{opacity:.5;}
.ct-row-inactive:hover td{opacity:.75;}
.row-act{
  display:inline-flex;align-items:center;gap:5px;
  background:var(--bg3);border:1px solid var(--border);border-radius:6px;
  padding:5px 12px;cursor:pointer;font-size:13px;font-family:var(--font);
  color:var(--text2);transition:all .13s;white-space:nowrap;
}
.row-act:hover{background:var(--accent-dim);color:var(--accent);border-color:rgba(91,141,238,.4);}
.row-act-danger:hover{background:rgba(239,68,68,.1);color:#ef4444;border-color:rgba(239,68,68,.3);}
/* ── view modal ── */
.ctv-sec{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:10px}
.ctv-sec-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;display:flex;align-items:center;gap:5px}
@media(max-width:600px){.ct-grid{grid-template-columns:1fr 1fr}}
</style>

<script>
const CT_BASE = typeof BASE !== 'undefined' ? BASE : '<?= $base ?>';
const CT_CSRF = '<?= View::e($csrf) ?>';
const CT_ALL  = <?= json_encode(array_values($contacts), JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?: '[]' ?>;

const CT_TYPE_COL = {
  'נותן שירות':'#10b981','פנים ארגוני':'#5b8dee','ספק':'#f59e0b',
  'תמיכה טכנית':'#06b6d4','איש קשר':'#8b5cf6','אחר':'#7c829c'
};

/* ── View prefs ── */
const CT_PK = 'v2_ct';
function ctPref(k,d){try{const p=JSON.parse(localStorage.getItem(CT_PK)||'{}');return k in p?p[k]:d;}catch{return d;}}
function ctSetPref(k,v){try{const p=JSON.parse(localStorage.getItem(CT_PK)||'{}');p[k]=v;localStorage.setItem(CT_PK,JSON.stringify(p));}catch{}}

let _ctView = ctPref('view','grid');
function ctSetView(v) {
  _ctView = v; ctSetPref('view', v);
  document.getElementById('ct-grid').style.display  = v==='grid'  ? 'grid'  : 'none';
  document.getElementById('ct-table').style.display = v==='table' ? 'block' : 'none';
  document.getElementById('ct-btn-grid').classList.toggle('active',  v==='grid');
  document.getElementById('ct-btn-table').classList.toggle('active', v==='table');
}
ctSetView(_ctView);

/* ── Type pills (multi-toggle) ── */
let _activeTypes = new Set(); // ריק = הכל מוצג

function ctToggleType(btn) {
  const t = btn.dataset.type;
  if (_activeTypes.has(t)) {
    _activeTypes.delete(t);
    btn.classList.remove('active');
  } else {
    _activeTypes.add(t);
    btn.classList.add('active');
  }
  document.getElementById('ct-type-clear').style.display = _activeTypes.size ? 'inline-block' : 'none';
  ctFilter();
}

function ctClearTypes() {
  _activeTypes.clear();
  document.querySelectorAll('.ct-type-pill').forEach(b => b.classList.remove('active'));
  document.getElementById('ct-type-clear').style.display = 'none';
  ctFilter();
}

/* ── Filter ── */
function ctFilter() {
  const q    = (document.getElementById('ct-search').value || '').toLowerCase().trim();
  const dept = document.getElementById('ct-dept-filter').value;
  document.getElementById('ct-clear').style.display = q ? 'block' : 'none';

  let vis = 0;
  document.querySelectorAll('.ct-card, .ct-row').forEach(el => {
    const mq   = !q || el.dataset.name.includes(q) || el.dataset.phone.includes(q) ||
                 (el.dataset.tags||'').includes(q) || (el.dataset.role||'').includes(q);
    const md   = !dept || el.dataset.dept === dept;
    const mt   = _activeTypes.size === 0 || _activeTypes.has(el.dataset.type);
    const show = mq && md && mt;
    el.style.display = show ? '' : 'none';
    if (show) vis++;
  });

  const total = CT_ALL.length;
  document.getElementById('ct-result-count').textContent =
    vis < total ? `מציג ${vis} מתוך ${total}` : total + ' רשומות';
  document.getElementById('ct-no-results').style.display = vis === 0 ? 'block' : 'none';
}

function ctClearSearch() { document.getElementById('ct-search').value = ''; ctFilter(); }
ctFilter();

/* ── View modal ── */
let _ctvId = null;
function openCtView(id) {
  _ctvId = id;
  const c = CT_ALL.find(x => x.id == id); if (!c) return;
  const col     = CT_TYPE_COL[c.contact_type || 'איש קשר'] || '#8b5cf6';
  const initials= (c.first_name||'?').charAt(0) + (c.last_name||'').charAt(0);
  const fullName= (c.first_name||'') + ' ' + (c.last_name||'');
  const aColors = ['#5b8dee','#8b5cf6','#10b981','#f59e0b','#ec4899','#06b6d4','#f97316'];
  const acolor  = aColors[Math.abs(ctHash(fullName)) % aColors.length];
  const tags    = (c.tags||'').split(',').map(t=>t.trim()).filter(Boolean);

  document.getElementById('ctv-title').innerHTML =
    `<div style="width:34px;height:34px;border-radius:50%;background:${acolor};display:grid;
      place-items:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;">${E(initials)}</div>
     <div>
       <div style="display:flex;align-items:center;gap:8px;">
         ${E(fullName)}
         ${!parseInt(c.is_active||1)?'<span style="font-size:11px;background:var(--bg4);border:1px solid var(--border);border-radius:8px;padding:1px 7px;color:var(--text3);">לא פעיל</span>':''}
       </div>
       <span style="font-size:11px;font-weight:400;color:${col};">${E(c.contact_type||'איש קשר')}</span>
       ${parseInt(c.is_contacts_list||0)?'<span style="font-size:11px;color:#10b981;margin-right:6px;"><i class="bi bi-envelope-check-fill"></i> לתכתובות</span>':''}
     </div>`;

  let html = '';
  if (c.phone||c.phone2||c.email||c.website) {
    html += `<div class="ctv-sec"><div class="ctv-sec-title" style="color:#10b981;"><i class="bi bi-telephone-fill"></i> יצירת קשר</div><div style="display:grid;gap:8px;">`;
    if(c.phone) html+=`<a href="tel:${E(c.phone)}" style="display:flex;align-items:center;gap:8px;font-size:15px;font-weight:700;color:var(--accent);text-decoration:none;background:var(--accent-dim);padding:7px 12px;border-radius:7px;border:1px solid rgba(91,141,238,.25);"><i class="bi bi-telephone-fill"></i>${E(c.phone)}</a>`;
    if(c.phone2)html+=`<a href="tel:${E(c.phone2)}" style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--accent);text-decoration:none;"><i class="bi bi-telephone"></i>${E(c.phone2)}</a>`;
    if(c.email) html+=`<a href="mailto:${E(c.email)}" style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2);text-decoration:none;"><i class="bi bi-envelope-fill"></i>${E(c.email)}</a>`;
    if(c.website)html+=`<a href="${E(c.website)}" target="_blank" style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text3);text-decoration:none;"><i class="bi bi-globe"></i>${E(c.website)}</a>`;
    html += `</div></div>`;
  }
  if (c.role||c.department||c.address) {
    html += `<div class="ctv-sec"><div class="ctv-sec-title" style="color:#f59e0b;"><i class="bi bi-building"></i> פרטים</div><div style="display:grid;gap:6px;font-size:13px;">`;
    if(c.role)      html+=`<div><span style="font-size:10px;color:var(--text3);">תפקיד</span><div style="font-weight:600;">${E(c.role)}</div></div>`;
    if(c.department)html+=`<div><span style="font-size:10px;color:var(--text3);">מחלקה / חברה</span><div>${E(c.department)}</div></div>`;
    if(c.address)   html+=`<div><span style="font-size:10px;color:var(--text3);">כתובת</span><div>${E(c.address)}</div></div>`;
    html += `</div></div>`;
  }
  if (tags.length) {
    html += `<div class="ctv-sec"><div class="ctv-sec-title" style="color:#06b6d4;"><i class="bi bi-tags-fill"></i> תגיות</div><div style="display:flex;flex-wrap:wrap;gap:6px;">`;
    tags.forEach(t => html+=`<span style="font-size:12px;background:var(--bg4);border:1px solid var(--border2);border-radius:12px;padding:3px 10px;color:var(--text2);">${E(t)}</span>`);
    html += `</div></div>`;
  }
  if (c.note) {
    html += `<div class="ctv-sec"><div class="ctv-sec-title" style="color:#8b5cf6;"><i class="bi bi-sticky-fill"></i> הערה</div>
      <p style="font-size:13px;color:var(--text2);margin:0;line-height:1.6;">${E(c.note).replace(/\n/g,'<br>')}</p></div>`;
  }
  document.getElementById('ctv-body').innerHTML = html || '<div style="color:var(--text3);text-align:center;padding:20px;">אין פרטים נוספים</div>';
  document.getElementById('ctv-modal').style.display = 'flex';
}
function closeCtView() { document.getElementById('ctv-modal').style.display = 'none'; }
function switchToCtEdit() { closeCtView(); openCtEdit(_ctvId); }
document.getElementById('ctv-modal').addEventListener('click', e => { if(e.target===document.getElementById('ctv-modal')) closeCtView(); });

/* ── Edit modal ── */
<?php if ($canEdit): ?>
const _cteTagsInput = document.getElementById('cte-tags');
if (_cteTagsInput) {
  _cteTagsInput.addEventListener('input', () => {
    const tags = _cteTagsInput.value.split(',').map(t=>t.trim()).filter(Boolean);
    const preview = document.getElementById('cte-tags-preview');
    if (preview) preview.innerHTML = tags.map(t=>`<span style="font-size:11px;background:var(--bg4);border:1px solid var(--border2);border-radius:10px;padding:2px 8px;color:var(--text2);">${E(t)}</span>`).join('');
  });
}

function openCtEdit(id) {
  const c = id ? CT_ALL.find(x=>x.id==id) : null;
  document.getElementById('cte-title').textContent = c ? `עריכה: ${c.first_name} ${c.last_name||''}` : 'איש קשר חדש';
  document.getElementById('cte-id').value       = c?.id       || '';
  document.getElementById('cte-fname').value    = c?.first_name|| '';
  document.getElementById('cte-lname').value    = c?.last_name || '';
  document.getElementById('cte-phone').value    = c?.phone     || '';
  document.getElementById('cte-phone2').value   = c?.phone2    || '';
  document.getElementById('cte-email').value    = c?.email     || '';
  document.getElementById('cte-website').value  = c?.website   || '';
  document.getElementById('cte-role').value     = c?.role      || '';
  document.getElementById('cte-dept').value     = c?.department|| '';
  document.getElementById('cte-type').value     = c?.contact_type || 'איש קשר';
  document.getElementById('cte-address').value  = c?.address   || '';
  document.getElementById('cte-tags').value     = c?.tags      || '';
  document.getElementById('cte-note').value     = c?.note      || '';
  document.getElementById('cte-active').checked = c ? !!parseInt(c.is_active) : true;
  const clCb  = document.getElementById('cte-contacts-list');
  const clVal = c ? !!parseInt(c.is_contacts_list||0) : false;
  if (clCb) { clCb.checked = clVal; _ctUpdateToggle(clVal); }
  document.getElementById('cte-error').style.display = 'none';
  if (_cteTagsInput) _cteTagsInput.dispatchEvent(new Event('input'));
  document.getElementById('cte-modal').style.display = 'flex';
  setTimeout(() => document.getElementById('cte-fname').focus(), 50);
}
function closeCtEdit() { document.getElementById('cte-modal').style.display = 'none'; }

async function saveCtContact() {
  const fname = document.getElementById('cte-fname').value.trim();
  const errEl = document.getElementById('cte-error');
  if (!fname) { errEl.textContent = 'שם פרטי חובה'; errEl.style.display = 'block'; return; }
  errEl.style.display = 'none';
  const body = new URLSearchParams({
    _csrf:            CT_CSRF,
    id:               document.getElementById('cte-id').value,
    first_name:       fname,
    last_name:        document.getElementById('cte-lname').value.trim(),
    phone:            document.getElementById('cte-phone').value.trim(),
    phone2:           document.getElementById('cte-phone2').value.trim(),
    email:            document.getElementById('cte-email').value.trim(),
    website:          document.getElementById('cte-website').value.trim(),
    role:             document.getElementById('cte-role').value.trim(),
    department:       document.getElementById('cte-dept').value.trim(),
    contact_type:     document.getElementById('cte-type').value,
    address:          document.getElementById('cte-address').value.trim(),
    tags:             document.getElementById('cte-tags').value.trim(),
    note:             document.getElementById('cte-note').value.trim(),
    is_active:        document.getElementById('cte-active').checked ? '1' : '0',
    is_contacts_list: document.getElementById('cte-contacts-list')?.checked ? '1' : '0',
  });
  const res  = await fetch(CT_BASE + '/contacts/save', { method:'POST', body });
  const data = await res.json();
  if (data.ok) { closeCtEdit(); location.reload(); }
  else { errEl.textContent = data.error || 'שגיאה בשמירה'; errEl.style.display = 'block'; }
}

async function doCtToggle(id) {
  if (!confirm('לשנות סטטוס?')) return;
  const res  = await fetch(CT_BASE + '/contacts/' + id + '/toggle', { method:'POST', body:new URLSearchParams({_csrf:CT_CSRF}) });
  const data = await res.json();
  if (data.ok) location.reload();
}

document.getElementById('cte-modal')?.addEventListener('click', e => {
  if (e.target === document.getElementById('cte-modal')) closeCtEdit();
});
<?php endif; ?>

/* ── Helpers ── */
function ctHash(s){let h=0;for(let i=0;i<s.length;i++){h=Math.imul(31,h)+s.charCodeAt(i)|0;}return h;}
function E(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

function _ctUpdateToggle(on) {
  const track = document.getElementById('cte-cl-track');
  const thumb = document.getElementById('cte-cl-thumb');
  if (!track || !thumb) return;
  track.style.background = on ? 'var(--accent)' : 'var(--border2)';
  thumb.style.right = on ? 'calc(100% - 21px)' : '3px';
}
document.addEventListener('change', e => {
  if (e.target.id === 'cte-contacts-list') _ctUpdateToggle(e.target.checked);
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    closeCtView();
    <?php if ($canEdit): ?>closeCtEdit();<?php endif; ?>
  }
});
</script>
