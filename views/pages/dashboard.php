<?php
use Core\View;
use Core\Auth;
/** @var array[] $stores      סניפי באג */
/** @var array[] $modanStores נקודות מודן */
/** @var string[] $cities     ערים */
/** @var array   $stats       open_tasks, stores_total, stores_alert */
/** @var array   $user        משתמש מחובר */
$base       = rtrim(CFG['app']['url'], '/');
$csrf       = $_SESSION['csrf_token'] ?? '';
$canEdit    = Auth::can('canEditStore');
$storeTypes = ['סניף באג','נקודת מודן','מחסן','אחר'];
$newBugCount   = count(array_filter($stores,      fn($s) => !empty($s['created_at']) && strtotime($s['created_at']) >= strtotime('-7 days')));
$newModanCount = count(array_filter($modanStores, fn($s) => !empty($s['created_at']) && strtotime($s['created_at']) >= strtotime('-7 days')));

?>

<!-- Header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <div class="page-title" style="margin-bottom:4px;">שלום, <?= View::e($user['first_name'] ?? '') ?> 👋</div>
    <div style="font-size:13px;color:var(--text3);">
      <?= date('d/m/Y l') ?>
      <span id="dw-inline" style="display:none;"> · תורן: <?php if (Auth::can('canManageDuty')): ?><a id="dw-link" href="<?= $base ?>/duty" style="color:inherit;text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'"><?php endif; ?><span id="dw-name"></span>, <span id="dw-dept"></span> (<span id="dw-week"></span>)<?php if (Auth::can('canManageDuty')): ?></a><?php endif; ?></span>
    </div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <div class="stat-pill">
      <i class="bi bi-bug-fill" style="color:var(--accent);"></i>
      <span id="stat-count"><?= count($stores) ?> סניפי באג</span>
      <?php if ($newBugCount > 0): ?>
        <span class="stat-new-badge"><?= $newBugCount ?> חדש<?= $newBugCount > 1 ? 'ים' : '' ?></span>
      <?php endif; ?>
    </div>
    <?php if (!empty($modanStores)): ?>
    <div class="stat-pill" id="stat-modan-pill" style="display:none;">
      <i class="bi bi-building" style="color:#8b5cf6;"></i>
      <span id="stat-modan-count"><?= count($modanStores) ?> נקודות מודן</span>
      <?php if ($newModanCount > 0): ?>
        <span class="stat-new-badge modan"><?= $newModanCount ?> חדש<?= $newModanCount > 1 ? 'ות' : '' ?></span>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($stats['stores_alert']>0): ?>
    <div class="stat-pill" style="border-color:rgba(245,158,11,.3);color:var(--warning);">
      <i class="bi bi-exclamation-triangle-fill"></i><span><?= (int)$stats['stores_alert'] ?> התראות</span></div>
    <?php endif; ?>
    <?php if ($stats['open_tasks']>0): ?>
    <div class="stat-pill" style="border-color:rgba(91,141,238,.3);color:var(--accent);">
      <i class="bi bi-check2-square"></i><span><?= (int)$stats['open_tasks'] ?> משימות</span></div>
    <?php endif; ?>
  </div>
</div>

<!-- Stores section -->
<div class="collapse-section card" id="stores-section" style="padding:0;overflow:hidden;">
  <div class="collapse-header" onclick="toggleSection()">
    <div style="display:flex;align-items:center;gap:10px;">
      <i class="bi bi-shop" style="color:var(--accent);font-size:16px;"></i>
      <span style="font-size:15px;font-weight:600;">רשימת סניפים</span>
      <span id="section-count" class="badge badge-info" style="font-size:11px;"></span>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
      <label class="hdr-tog" onclick="event.stopPropagation()">
        <input type="checkbox" id="show-modan" onchange="onModanChange()">
        <span class="hdr-tog-inner modan-ti"><i class="bi bi-building"></i> מודן</span>
      </label>
      <?php if ($canEdit): ?>
      <label class="hdr-tog" onclick="event.stopPropagation()">
        <input type="checkbox" id="edit-mode" onchange="onEditModeChange()">
        <span class="hdr-tog-inner edit-ti"><i class="bi bi-pencil-fill"></i> עריכה</span>
      </label>
      <?php endif; ?>
      <i class="bi bi-chevron-up collapse-arrow"></i>
    </div>
  </div>

  <div id="section-body" class="section-body">
    <!-- Toolbar -->
    <div class="stores-toolbar">
      <div class="s-srch">
        <i class="bi bi-search"></i>
        <input type="text" id="store-search" placeholder="חיפוש שם, עיר, טלפון..." oninput="filterStores()">
        <button id="search-clear" onclick="clearSearch()">✕</button>
      </div>
      <select id="city-filter" class="s-sel" onchange="filterStores()">
        <option value="">כל הערים</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= View::e($c) ?>"><?= View::e($c) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="sort-select" class="s-sel" onchange="sortStores()">
        <option value="num">מיון: מספר</option>
        <option value="name">מיון: שם</option>
        <option value="city">מיון: עיר</option>
      </select>
      <div style="flex:1;"></div>
      <?php if ($canEdit): ?>
      <button id="add-btn" class="btn btn-primary" style="padding:5px 12px;font-size:12px;display:none;"
              onclick="openStoreForm(null)">
        <i class="bi bi-plus-lg"></i> הוסף
      </button>
      <button id="sync-hours-btn" class="btn btn-secondary" style="padding:5px 12px;font-size:12px;display:none;"
              onclick="syncBugWorkHours()">
        <i class="bi bi-clock-history"></i> סנכרון שעות
      </button>
      <?php endif; ?>
      <div class="view-toggle">
        <button id="btn-grid"    onclick="setView('grid')"    title="גריד מלא"><i class="bi bi-grid-3x3-gap-fill"></i></button>
        <button id="btn-compact" onclick="setView('compact')" title="גריד מצומצם"><i class="bi bi-grid-fill"></i></button>
        <button id="btn-table"   onclick="setView('table')"   title="טבלה"><i class="bi bi-list-ul"></i></button>
      </div>
    </div>
    <div id="result-count" style="font-size:12px;color:var(--text3);padding:0 2px 10px;"></div>

    <!-- Grid view — Bug stores -->
    <div id="stores-grid" class="stores-grid">
    <?php foreach ($stores as $s):
      $hasAlert = !empty($s['alert_note']);
      $isNew = !empty($s['created_at']) && strtotime($s['created_at']) >= strtotime('-7 days');
    ?>
    <div class="store-card c-bug<?= $hasAlert?' has-alert':'' ?><?= $isNew?' is-new':'' ?>"
         data-id="<?= (int)$s['id'] ?>"
         data-num="<?= View::e($s['store_num']??'') ?>"
         data-name="<?= strtolower(View::e($s['name'])) ?>"
         data-city="<?= View::e($s['city']??'') ?>"
         data-phone="<?= View::e($s['phone_main']??'') ?>"
         data-type="<?= View::e($s['type']??'') ?>"
         onclick="openStoreView(<?= (int)$s['id'] ?>)">
      <?php if ($canEdit): ?>
      <div class="cedit-bar">
        <button class="cedit-btn" onclick="event.stopPropagation();openStoreForm(<?= htmlspecialchars(json_encode($s, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES) ?>)" title="עריכה"><i class="bi bi-pencil-fill"></i></button>
        <button class="cedit-btn dng" onclick="event.stopPropagation();doToggle(<?= (int)$s['id'] ?>)" title="השבת/הפעל"><i class="bi bi-toggle-<?= $s['is_active']?'on':'off' ?>"></i></button>
      </div>
      <?php endif; ?>
      <span class="card-type-icon card-type-bug" title="סניף באג"><i class="bi bi-bug-fill"></i></span>
      <div class="card-content">
        <div class="sc-num num-bug"><?= View::e($s['store_num']??'') ?></div>
        <div class="sc-name"><?= View::e($s['name']) ?><?php if ($isNew): ?><span class="new-badge">חדש</span><?php endif; ?></div>
        <?php if ($s['city']): ?>
          <div class="sc-city"><i class="bi bi-geo-alt-fill" style="color:var(--accent);font-size:11px;"></i><?= View::e($s['city']) ?></div>
        <?php endif; ?>
        <?php if ($s['phone_main']): ?>
          <div class="sc-meta sc-phone d-inline-flex align-items-center" onclick="event.stopPropagation();copyText('<?= View::e($s['phone_main']) ?>')">
            <i class="bi bi-telephone-fill me-2"></i>
            <span><?= View::e($s['phone_main']) ?></span>
          </div>
        <?php endif; ?>
        <?php if ($hasAlert): ?>
          <div class="sc-alert" title="<?= View::e($s['alert_note']) ?>">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span class="sc-alert-text"><?= View::e(mb_substr($s['alert_note'],0,38)) ?><?= mb_strlen($s['alert_note'])>38?'…':'' ?></span>
            <?php if (!empty($s['alert_updated_at'])): ?><span class="sc-alert-ts"><?= date('d/m',strtotime($s['alert_updated_at'])) ?></span><?php endif; ?>
          </div>
        <?php endif; ?>
        <span class="sc-dot <?= $s['is_active']?'on':'off' ?>"></span>
      </div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Grid view — Modan section -->
    <div id="modan-section" style="display:none;margin-top:20px;">
      <div class="section-divider">
        <i class="bi bi-building" style="color:#8b5cf6;"></i>
        <span>נקודות מודן</span>
        <span id="modan-count" style="font-size:12px;color:var(--text3);font-weight:400;"></span>
      </div>
      <div id="stores-grid-modan" class="stores-grid">
      <?php foreach ($modanStores as $s):
        $hasAlert = !empty($s['alert_note']);
        $isNew = !empty($s['created_at']) && strtotime($s['created_at']) >= strtotime('-7 days');
      ?>
      <div class="store-card c-modan is-modan<?= $hasAlert?' has-alert':'' ?><?= $isNew?' is-new':'' ?>"
           data-id="<?= (int)$s['id'] ?>"
           data-num="<?= View::e($s['store_num']??'') ?>"
           data-name="<?= strtolower(View::e($s['name'])) ?>"
           data-city="<?= View::e($s['city']??'') ?>"
           data-phone="<?= View::e($s['phone_main']??'') ?>"
           data-type="<?= View::e($s['type']??'') ?>"
           onclick="openStoreView(<?= (int)$s['id'] ?>)">
        <?php if ($canEdit): ?>
        <div class="cedit-bar">
          <button class="cedit-btn" onclick="event.stopPropagation();openStoreForm(<?= htmlspecialchars(json_encode($s, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES) ?>)" title="עריכה"><i class="bi bi-pencil-fill"></i></button>
          <button class="cedit-btn dng" onclick="event.stopPropagation();doToggle(<?= (int)$s['id'] ?>)" title="השבת/הפעל"><i class="bi bi-toggle-<?= $s['is_active']?'on':'off' ?>"></i></button>
        </div>
        <?php endif; ?>
        <span class="card-type-icon card-type-modan" title="נקודת מודן">M</span>
        <div class="card-content">
          <?php if (!empty($s['store_num']) && $s['store_num'] !== '0'): ?><div class="sc-num num-modan"><?= View::e($s['store_num']) ?></div><?php endif; ?>
          <div class="sc-name"><?= View::e($s['name']) ?><?php if ($isNew): ?><span class="new-badge">חדש</span><?php endif; ?></div>
          <?php if ($s['city']): ?>
            <div class="sc-city"><i class="bi bi-geo-alt-fill" style="color:#8b5cf6;font-size:11px;"></i><?= View::e($s['city']) ?></div>
          <?php endif; ?>
          <?php if ($s['phone_main']): ?>
          <div class="sc-meta sc-phone d-inline-flex align-items-center" onclick="event.stopPropagation();copyText('<?= View::e($s['phone_main']) ?>')">
            <i class="bi bi-telephone-fill me-2"></i>
            <span><?= View::e($s['phone_main']) ?></span>
          </div>
          <?php endif; ?>
          <?php if ($hasAlert): ?>
            <div class="sc-alert" title="<?= View::e($s['alert_note']) ?>">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <span class="sc-alert-text"><?= View::e(mb_substr($s['alert_note'],0,38)) ?><?= mb_strlen($s['alert_note'])>38?'…':'' ?></span>
              <?php if (!empty($s['alert_updated_at'])): ?><span class="sc-alert-ts"><?= date('d/m',strtotime($s['alert_updated_at'])) ?></span><?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ($s['mvoice_queue']): ?>
            <div class="sc-meta" style="color:var(--text3);"><i class="bi bi-headset"></i><?= View::e($s['mvoice_queue']) ?></div>
          <?php endif; ?>
          <span class="sc-dot <?= $s['is_active']?'on':'off' ?>"></span>
        </div>
      </div>
      <?php endforeach; ?>
      </div>
    </div>

    <!-- Table view -->
    <div id="stores-table" style="display:none;overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:13px;min-width:800px;">
        <thead>
          <tr style="background:var(--bg3);">
            <th class="th srt" data-col="num">מספר</th>
            <th class="th srt" data-col="name">שם</th>
            <th class="th srt" data-col="city">עיר</th>
            <th class="th">טלפון ראשי</th>
            <th class="th">נייד</th>
            <th class="th">אימייל</th>
            <th class="th">מנהל</th>
            <th class="th">נייד מנהל</th>
            <th class="th">מנהל אזור</th>
            <th class="th">שעות פעילות</th>
            <th class="th">סוג</th>
            <th class="th">התראה</th>
            <?php if ($canEdit): ?><th class="th edit-col"></th><?php endif; ?>
          </tr>
        </thead>
        <tbody id="bug-tbody">
        <?php foreach ($stores as $s):
          $hasAlert = !empty($s['alert_note']);
          $isNew = !empty($s['created_at']) && strtotime($s['created_at']) >= strtotime('-7 days');
        ?>
        <tr class="s-row<?= $isNew?' is-new':'' ?>"
            data-id="<?= (int)$s['id'] ?>"
            data-num="<?= View::e($s['store_num']??'') ?>"
            data-name="<?= strtolower(View::e($s['name'])) ?>"
            data-city="<?= View::e($s['city']??'') ?>"
            data-phone="<?= View::e($s['phone_main']??'') ?>"
            data-type="<?= View::e($s['type']??'') ?>"
            style="border-bottom:1px solid var(--border);<?= $hasAlert?'background:rgba(245,158,11,.03);':''?>"
            onclick="openStoreView(<?= (int)$s['id'] ?>)">
          <td class="td" style="font-weight:800;font-size:17px;color:var(--accent);"><?= View::e($s['store_num']??'—') ?></td>
          <td class="td" style="font-weight:600;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= View::e($s['name']) ?>
            <?php if ($isNew): ?><span class="new-badge">חדש</span><?php endif; ?>
            <?php if ($hasAlert): ?>
              <i class="bi bi-exclamation-triangle-fill" style="color:var(--warning);font-size:11px;margin-right:4px;" title="<?= View::e($s['alert_note']) ?>"></i>
            <?php endif; ?>
          </td>
          <td class="td" style="color:var(--text2);"><?= View::e($s['city']??'—') ?></td>
          <td class="td" onclick="event.stopPropagation()">
            <?php if ($s['phone_main']): ?>
              <span class="copy-val" onclick="copyText('<?= View::e($s['phone_main']) ?>')"><?= View::e($s['phone_main']) ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="td" onclick="event.stopPropagation()">
            <?php if ($s['phone_cell']??''): ?>
              <span class="copy-val" onclick="copyText('<?= View::e($s['phone_cell']) ?>')"><?= View::e($s['phone_cell']) ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="td" onclick="event.stopPropagation()">
            <?php if ($s['email']??''): ?>
              <a href="mailto:<?= View::e($s['email']) ?>" onclick="event.stopPropagation()" style="color:var(--accent);text-decoration:none;font-size:12px;"><?= View::e($s['email']) ?></a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="td" style="color:var(--text2);"><?= View::e($s['manager_name']??'—') ?></td>
          <td class="td" onclick="event.stopPropagation()">
            <?php if ($s['manager_cell']??''): ?>
              <span class="copy-val" onclick="copyText('<?= View::e($s['manager_cell']) ?>')"><?= View::e($s['manager_cell']) ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="td" onclick="event.stopPropagation()">
            <?php foreach ($areaManagers[$s['id']] ?? [] as $am): ?>
              <span class="am-badge" onclick="showAmDetail(<?= htmlspecialchars(json_encode($am, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES) ?>)">
                <?= View::e($am['name']) ?>
              </span>
            <?php endforeach; ?>
          </td>
          <?php
            $wh = !empty($s['work_hours']) ? (is_string($s['work_hours']) ? json_decode($s['work_hours'], true) : $s['work_hours']) : null;
            $whText = '';
            if ($wh) {
              $days = ['א'=>'א\'','ב'=>'ב\'','ג'=>'ג\'','ד'=>'ד\'','ה'=>'ה\'','ו'=>'ו\'','ש'=>'ש\''];
              $parts = [];
              foreach ($wh as $k => $v) { if ($v) $parts[] = ($days[$k]??$k).': '.$v; }
              $whText = implode(' | ', $parts);
            }
          ?>
          <td class="td" style="font-size:11px;color:var(--text2);white-space:nowrap;max-width:180px;overflow:hidden;text-overflow:ellipsis;" title="<?= View::e($whText) ?>"><?= $whText ? View::e(mb_substr($whText,0,40)).(mb_strlen($whText)>40?'…':'') : '—' ?></td>
          <td class="td"><span class="badge badge-info" style="font-size:11px;">באג</span></td>
          <td class="td">
            <?php if ($hasAlert): ?>
              <div style="font-size:11px;color:var(--warning);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= View::e($s['alert_note']) ?>"><?= View::e(mb_substr($s['alert_note'],0,30)) ?><?= mb_strlen($s['alert_note'])>30?'…':''?></div>
            <?php endif; ?>
          </td>
          <?php if ($canEdit): ?>
          <td class="td edit-col" onclick="event.stopPropagation()">
            <div style="display:flex;gap:4px;">
              <button class="row-act" onclick="openStoreForm(<?= htmlspecialchars(json_encode($s, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES) ?>)"><i class="bi bi-pencil-fill"></i></button>
              <button class="row-act" onclick="doToggle(<?= (int)$s['id'] ?>)"><i class="bi bi-toggle-<?= $s['is_active']?'on':'off' ?>"></i></button>
            </div>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tbody id="modan-tbody" style="display:none;">
          <tr class="modan-thead-row">
            <td colspan="<?= $canEdit?12:11 ?>" style="padding:8px 12px 6px;background:var(--bg3);border-top:2px solid rgba(139,92,246,.3);border-bottom:1px solid var(--border);">
              <span style="font-size:11px;font-weight:700;color:#8b5cf6;text-transform:uppercase;letter-spacing:.07em;display:flex;align-items:center;gap:5px;">
                <i class="bi bi-building"></i> נקודות מודן
                <span id="modan-table-count" style="font-weight:400;opacity:.7;"></span>
              </span>
            </td>
          </tr>
        <?php foreach ($modanStores as $s):
          $hasAlert = !empty($s['alert_note']);
          $isNew = !empty($s['created_at']) && strtotime($s['created_at']) >= strtotime('-7 days');
        ?>
        <tr class="s-row is-modan<?= $isNew?' is-new':'' ?>"
            data-id="<?= (int)$s['id'] ?>"
            data-num="<?= View::e($s['store_num']??'') ?>"
            data-name="<?= strtolower(View::e($s['name'])) ?>"
            data-city="<?= View::e($s['city']??'')?>"
            data-phone="<?= View::e($s['phone_main']??'')?>"
            data-type="<?= View::e($s['type']??'')?>"
            style="border-bottom:1px solid var(--border);background:rgba(139,92,246,.02);<?= $hasAlert?'background:rgba(245,158,11,.03);':''?>"
            onclick="openStoreView(<?= (int)$s['id'] ?>)">
          <td class="td" style="font-weight:700;font-size:12px;color:#8b5cf6;white-space:nowrap;"><i class="bi bi-building" style="opacity:.6;font-size:10px;"></i> מודן</td>
          <td class="td" style="font-weight:600;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= View::e($s['name']) ?>
            <?php if ($isNew): ?><span class="new-badge">חדש</span><?php endif; ?>
            <?php if ($hasAlert): ?>
              <i class="bi bi-exclamation-triangle-fill" style="color:var(--warning);font-size:11px;margin-right:4px;" title="<?= View::e($s['alert_note']) ?>"></i>
            <?php endif; ?>
          </td>
          <td class="td" style="color:var(--text2);"><?= View::e($s['city']??'—') ?></td>
          <td class="td" onclick="event.stopPropagation()">
            <?php if ($s['phone_main']): ?>
              <span class="copy-val" style="color:#8b5cf6;" onclick="copyText('<?= View::e($s['phone_main']) ?>')"><?= View::e($s['phone_main']) ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="td" onclick="event.stopPropagation()">
            <?php if ($s['phone_cell']??''): ?>
              <span class="copy-val" onclick="copyText('<?= View::e($s['phone_cell']) ?>')"><?= View::e($s['phone_cell']) ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="td" onclick="event.stopPropagation()">
            <?php if ($s['email']??''): ?>
              <a href="mailto:<?= View::e($s['email']) ?>" onclick="event.stopPropagation()" style="color:#8b5cf6;text-decoration:none;font-size:12px;"><?= View::e($s['email']) ?></a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="td" style="color:var(--text2);"><?= View::e($s['manager_name']??'—') ?></td>
          <td class="td" onclick="event.stopPropagation()">
            <?php if ($s['manager_cell']??''): ?>
              <span class="copy-val" onclick="copyText('<?= View::e($s['manager_cell']) ?>')"><?= View::e($s['manager_cell']) ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="td" onclick="event.stopPropagation()">
            <?php foreach ($areaManagers[$s['id']] ?? [] as $am): ?>
              <span class="am-badge" onclick="showAmDetail(<?= htmlspecialchars(json_encode($am, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES) ?>)">
                <?= View::e($am['name']) ?>
              </span>
            <?php endforeach; ?>
          </td>
          <?php
            $wh = !empty($s['work_hours']) ? (is_string($s['work_hours']) ? json_decode($s['work_hours'], true) : $s['work_hours']) : null;
            $whText = '';
            if ($wh) {
              $days = ['א'=>'א\'','ב'=>'ב\'','ג'=>'ג\'','ד'=>'ד\'','ה'=>'ה\'','ו'=>'ו\'','ש'=>'ש\''];
              $parts = [];
              foreach ($wh as $k => $v) { if ($v) $parts[] = ($days[$k]??$k).': '.$v; }
              $whText = implode(' | ', $parts);
            }
          ?>
          <td class="td" style="font-size:11px;color:var(--text2);white-space:nowrap;max-width:180px;overflow:hidden;text-overflow:ellipsis;" title="<?= View::e($whText) ?>"><?= $whText ? View::e(mb_substr($whText,0,40)).(mb_strlen($whText)>40?'…':'') : '—' ?></td>
          <td class="td"><span class="badge badge-purple" style="font-size:11px;">מודן</span></td>
          <td class="td">
            <?php if ($hasAlert): ?>
              <div style="font-size:11px;color:var(--warning);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= View::e($s['alert_note']) ?>"><?= View::e(mb_substr($s['alert_note'],0,30)) ?><?= mb_strlen($s['alert_note'])>30?'…':''?></div>
            <?php endif; ?>
          </td>
          <?php if ($canEdit): ?>
          <td class="td edit-col" onclick="event.stopPropagation()">
            <div style="display:flex;gap:4px;">
              <button class="row-act" onclick="openStoreForm(<?= htmlspecialchars(json_encode($s, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES) ?>)"><i class="bi bi-pencil-fill"></i></button>
              <button class="row-act" onclick="doToggle(<?= (int)$s['id'] ?>)"><i class="bi bi-toggle-<?= $s['is_active']?'on':'off' ?>"></i></button>
            </div>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div id="no-results" style="display:none;text-align:center;padding:40px;color:var(--text3);">
      <i class="bi bi-search" style="font-size:32px;display:block;margin-bottom:10px;opacity:.4;"></i>לא נמצאו סניפים
    </div>
  </div>
</div>

<!-- View modal -->
<div id="sv-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:400;align-items:center;justify-content:center;padding:20px;">
  <div id="sv-inner" style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-height:88vh;overflow-y:auto;transition:max-width .15s;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg2);z-index:1;gap:8px;">
      <div id="sv-title" style="font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;flex:1;min-width:0;"></div>
      <div style="display:flex;gap:4px;align-items:center;flex-shrink:0;">
        <div style="display:flex;gap:2px;background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:2px;">
          <button id="sv-size-s" onclick="svSetSize('s')" style="padding:2px 7px;font-size:11px;font-weight:600;border:none;border-radius:4px;cursor:pointer;background:none;color:var(--text2);">S</button>
          <button id="sv-size-m" onclick="svSetSize('m')" style="padding:2px 7px;font-size:11px;font-weight:600;border:none;border-radius:4px;cursor:pointer;background:none;color:var(--text2);">M</button>
          <button id="sv-size-l" onclick="svSetSize('l')" style="padding:2px 7px;font-size:11px;font-weight:600;border:none;border-radius:4px;cursor:pointer;background:none;color:var(--text2);">L</button>
        </div>
        <?php if ($canEdit): ?>
          <button id="sv-edit-btn" class="btn btn-ghost" style="padding:5px 10px;font-size:12px;" onclick="switchToEdit()"><i class="bi bi-pencil-fill"></i> ערוך</button>
        <?php endif; ?>
        <button onclick="closeSV()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;line-height:1;">✕</button>
      </div>
    </div>
    <div id="sv-body" style="padding:20px;"></div>
  </div>
</div>

<!-- Copy toast -->
<div id="copy-toast" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
     background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:8px 18px;
     font-size:13px;color:var(--text);box-shadow:0 8px 30px rgba(0,0,0,.4);z-index:9999;
     pointer-events:none;transition:opacity .2s;">
  <i class="bi bi-clipboard-check" style="color:#10b981;margin-left:6px;"></i>
  <span id="copy-toast-val"></span>
</div>

<!-- Edit modal -->
<?php if ($canEdit): ?>
<div id="store-edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:center;justify-content:center;padding:20px;">
  <?php include __DIR__ . '/stores/_store_form.php'; ?>
</div>
<?php endif; ?>

<div id="am-detail-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:600;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:340px;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--border);">
      <div style="font-weight:700;font-size:14px;">פרטי מנהל אזור</div>
      <button onclick="document.getElementById('am-detail-modal').style.display='none'" style="background:none;border:none;color:var(--text2);font-size:20px;cursor:pointer;">✕</button>
    </div>
    <div id="am-detail-body" style="padding:18px;"></div>
  </div>
</div>

<style>
.stat-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:13px;font-weight:500;background:var(--bg3);border:1px solid var(--border);color:var(--text2)}
.stat-new-badge{font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;letter-spacing:.03em;animation:new-pulse 2.5s ease-in-out infinite;}
.stat-new-badge.modan{background:linear-gradient(135deg,#8b5cf6,#7c3aed);}
.collapse-section{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius)}
.collapse-header{display:flex;align-items:center;justify-content:space-between;padding:13px 16px;cursor:pointer;user-select:none;transition:background .13s}
.collapse-header:hover{background:var(--bg3)}
.collapse-arrow{font-size:13px;color:var(--text2);transition:transform .25s}
.section-body{padding:12px 14px 16px;border-top:1px solid var(--border)}
body.section-closed .section-body{display:none}
body.section-closed .collapse-arrow{transform:rotate(180deg)}
.hdr-tog{cursor:pointer}
.hdr-tog input{display:none}
.hdr-tog-inner{display:flex;align-items:center;gap:4px;padding:4px 9px;border-radius:6px;font-size:12px;font-weight:500;color:var(--text3);background:var(--bg3);border:1px solid var(--border);transition:all .13s}
.hdr-tog:has(input:checked) .modan-ti{color:#8b5cf6;background:rgba(139,92,246,.12);border-color:rgba(139,92,246,.3)}
.hdr-tog:has(input:checked) .edit-ti{color:#f59e0b;background:rgba(245,158,11,.12);border-color:rgba(245,158,11,.3)}
.stores-toolbar{display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap}
.s-srch{display:flex;align-items:center;gap:7px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0 10px;flex:1;min-width:160px;max-width:300px;transition:border-color .15s}
.s-srch:focus-within{border-color:var(--accent)}
.s-srch i{color:var(--text3);font-size:13px;flex-shrink:0}
.s-srch input{background:none;border:none;outline:none;color:var(--text);font-family:var(--font);font-size:13px;padding:7px 0;width:100%}
.s-srch input::placeholder{color:var(--text3)}
#search-clear{background:none;border:none;color:var(--text3);cursor:pointer;font-size:13px;padding:0;display:none}
.s-sel{background:var(--bg3);border:1px solid var(--border);border-radius:7px;padding:7px 10px;color:var(--text);font-size:12px;font-family:var(--font);outline:none}
.view-toggle{display:flex;gap:3px;background:var(--bg3);border:1px solid var(--border);border-radius:7px;padding:3px}
.view-toggle button{background:none;border:none;padding:4px 8px;border-radius:5px;color:var(--text2);cursor:pointer;font-size:15px;transition:background .13s,color .13s}
.view-toggle button.active{background:var(--accent-dim);color:var(--accent)}
/* copy-val */
.copy-val{color:var(--accent);cursor:pointer;direction:ltr;display:inline-block;transition:opacity .1s;font-family:monospace;}
.copy-val:hover{opacity:.7;text-decoration:underline;}
/* Grid */
.stores-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(178px,1fr));gap:10px}
body.grid-compact .stores-grid{grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:5px}
body.grid-compact .cedit-bar{display:none}
body.grid-compact .card-content{padding:14px 8px 10px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:5px}
body.grid-compact .sc-num{font-size:30px;font-weight:800;text-align:center;font-family:var(--num-font);font-variant-numeric:tabular-nums;transition:transform .15s}
body.grid-compact .sc-name{font-size:16px;font-weight:600;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%}
body.grid-compact .sc-city,body.grid-compact .sc-meta,body.grid-compact .sc-phone{display:none}
body.grid-compact .c-modan .sc-city{display:flex;justify-content:center;font-size:11px;font-weight:600;color:#8b5cf6;gap:3px}
body.grid-compact .c-modan .sc-num{font-size:11px;font-weight:700;text-align:center;color:#8b5cf6;opacity:.8;letter-spacing:0}
body.grid-compact .c-modan .sc-name{font-size:13px;}
body.grid-compact .c-modan{min-height:85px}
body.grid-compact .sc-alert .sc-alert-text,body.grid-compact .sc-alert .sc-alert-ts{display:none}
body.grid-compact .sc-alert{background:none;padding:2px 0;justify-content:center;font-size:13px}
body.grid-compact .sc-dot{bottom:6px;left:8px}
.store-card{border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;position:relative;overflow:hidden;transition:box-shadow .18s,border-color .15s;transform-style:preserve-3d}
.store-card:hover{box-shadow:0 12px 40px rgba(0,0,0,.45)}
.c-bug{background:linear-gradient(145deg,var(--bg3) 60%,rgba(91,141,238,.1));border-color:rgba(91,141,238,.22)}
.c-bug:hover{border-color:var(--accent);background:linear-gradient(145deg,var(--bg3) 40%,rgba(91,141,238,.14))}
.c-modan{background:linear-gradient(145deg,var(--bg3) 60%,rgba(139,92,246,.1));border-color:rgba(139,92,246,.22)}
.c-modan:hover{border-color:#8b5cf6;background:linear-gradient(145deg,var(--bg3) 40%,rgba(139,92,246,.14))}
.has-alert{border-right:3px solid var(--warning)!important}
.has-alert:hover{box-shadow:0 12px 40px rgba(245,158,11,.2)}
.store-card::after{content:'';position:absolute;inset:0;border-radius:var(--radius);opacity:0;transition:opacity .18s;pointer-events:none}
.c-bug::after{box-shadow:inset 0 0 0 1px rgba(91,141,238,.4)}
.c-bug:hover::after{opacity:1}
.c-modan::after{box-shadow:inset 0 0 0 1px rgba(139,92,246,.4)}
.c-modan:hover::after{opacity:1}
/* glare layer */
.store-card .pk-glare{position:absolute;inset:0;border-radius:var(--radius);pointer-events:none;opacity:0;transition:opacity .2s;z-index:3;}
.card-content{padding:13px 15px;display:flex;flex-direction:column;gap:5px}
.cedit-bar{position:absolute;top:7px;left:7px;display:none;gap:4px;z-index:2}
body.edit-mode .cedit-bar{display:flex}
body.edit-mode .store-card,.edit-mode .card-content{cursor:default}
.sc-num{font-family:var(--num-font,'Assistant',sans-serif);font-size:26px;font-weight:800;letter-spacing:-.5px;line-height:1;margin-bottom:3px;font-variant-numeric:tabular-nums;transition:transform .15s}
.store-card:hover .sc-num{transform:scale(1.04)}
.num-bug{color:var(--accent)}
.num-modan{color:#8b5cf6}
.sc-name{font-size:14px;font-weight:700;color:var(--text);line-height:1.3;transition:color .15s;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.store-card:hover .sc-name{color:#fff}
.num-modan-label{font-size:11px;font-weight:600;letter-spacing:0;opacity:.75;margin-bottom:2px;}
.c-modan .sc-name{font-size:15px;font-weight:800;}
.c-modan .sc-city{font-size:12px;font-weight:600;color:#8b5cf6;margin-top:1px;}
.section-divider{display:flex;align-items:center;gap:8px;padding:8px 2px 12px;font-size:13px;font-weight:700;color:var(--text2);border-bottom:2px solid rgba(139,92,246,.25);margin-bottom:12px;}
.sc-city{font-size:12px;color:var(--text2);display:flex;align-items:center;gap:4px;transition:color .15s}
.store-card:hover .sc-city{color:var(--text)}
.sc-phone{cursor:pointer;}
.sc-phone:hover{opacity:.75;}
.sc-alert{font-size:11px;color:var(--warning);background:rgba(245,158,11,.1);border-radius:3px;padding:3px 6px;line-height:1.4;display:flex;align-items:center;gap:4px}
.sc-alert-ts{margin-right:auto;color:var(--text3);font-size:10px}
.card-type-icon{position:absolute;top:7px;left:9px;font-size:13px;line-height:1;pointer-events:none;z-index:1;}
.card-type-bug{opacity:.28;filter:grayscale(1);}
.card-type-modan{font-size:12px;font-weight:900;color:#8b5cf6;opacity:.35;font-family:var(--num-font,'Assistant',sans-serif);letter-spacing:-.5px;}
.store-card:hover .card-type-icon{opacity:.55;}
body.grid-compact .card-type-icon{top:5px;left:6px;font-size:11px;}
.sc-dot{position:absolute;bottom:9px;left:11px;width:7px;height:7px;border-radius:50%;transition:transform .18s,box-shadow .18s}
.sc-dot.on{background:var(--success)}
.sc-dot.off{background:var(--danger)}
.store-card:hover .sc-dot.on{transform:scale(1.3);box-shadow:0 0 6px var(--success)}
.store-card:hover .sc-dot.off{transform:scale(1.3);box-shadow:0 0 6px var(--danger)}
.cedit-btn{background:var(--bg4);border:1px solid var(--border2);border-radius:5px;padding:4px 7px;cursor:pointer;font-size:12px;color:var(--text2);transition:all .13s}
.cedit-btn:hover{background:var(--accent-dim);color:var(--accent);transform:scale(1.05)}
.cedit-btn.dng:hover{background:rgba(239,68,68,.15);color:var(--danger)}
/* Table */
.th{padding:9px 12px;text-align:right;font-weight:600;font-size:12px;border-bottom:1px solid var(--border);white-space:nowrap;color:var(--text2)}
.th.srt{cursor:pointer;user-select:none}
.th.srt:hover{color:var(--text);background:var(--bg4)}
.th.srt.asc::after{content:' ↑';color:var(--accent);font-size:10px}
.th.srt.desc::after{content:' ↓';color:var(--accent);font-size:10px}
.td{padding:9px 12px}
.s-row{cursor:pointer}.s-row:hover{background:var(--bg3)!important}
body.edit-mode .s-row{cursor:default}
.is-modan{opacity:.9}
.edit-col,.th.edit-col{display:none}
body.edit-mode .edit-col,body.edit-mode .th.edit-col{display:table-cell}
.row-act{background:none;border:1px solid var(--border);border-radius:5px;padding:3px 7px;cursor:pointer;font-size:12px;color:var(--text2);transition:all .13s}
.row-act:hover{background:var(--accent-dim);color:var(--accent);border-color:var(--accent)}
.sv-cat{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px}
.sv-cat-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px;display:flex;align-items:center;gap:6px}
@media(max-width:600px){.stores-grid{grid-template-columns:repeat(auto-fill,minmax(148px,1fr));gap:8px}}
.am-badge{display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;background:rgba(91,141,238,.15);color:var(--accent);border:1px solid rgba(91,141,238,.3);cursor:pointer;margin:1px 2px;transition:background .13s;}
.am-badge:hover{background:rgba(91,141,238,.28);}
/* New-store badge */
.new-badge{display:inline-block;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:1px 5px;border-radius:4px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;margin-right:5px;vertical-align:middle;box-shadow:0 0 0 0 rgba(16,185,129,.5);animation:new-pulse 2.5s ease-in-out infinite;}
@keyframes new-pulse{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.45);}50%{box-shadow:0 0 0 5px rgba(16,185,129,0);}}
.is-new{border-top:2px solid #10b981 !important;overflow:hidden;}
.c-bug.is-new{background:linear-gradient(145deg,var(--bg3) 60%,rgba(16,185,129,.08)) !important;}
.c-modan.is-new{background:linear-gradient(145deg,var(--bg3) 60%,rgba(16,185,129,.08)) !important;}
body.grid-compact .new-badge{display:none;}
/* confetti particles inside is-new grid cards */
.confetti-dot{position:absolute;top:0;pointer-events:none;opacity:0;animation:cdrop var(--dur,2.4s) ease-in var(--delay,0s) infinite;}
@keyframes cdrop{0%{transform:translateY(-10px) rotate(0deg) scale(1);opacity:.95;}60%{opacity:.6;}100%{transform:translateY(calc(var(--h,100px) + 12px)) rotate(400deg) scale(.7);opacity:0;}}
/* confetti inside store-view modal */
#sv-inner{position:relative;overflow:hidden;}
.sv-confetti-dot{position:absolute;top:0;pointer-events:none;opacity:0;animation:svcdrop var(--dur,2.2s) ease-in var(--delay,0s) 3;}
@keyframes svcdrop{0%{transform:translateY(-10px) rotate(0deg) scale(1);opacity:.9;}65%{opacity:.55;}100%{transform:translateY(var(--fall,200px)) rotate(380deg) scale(.65);opacity:0;}}
/* table row highlight for new stores */
#bug-tbody tr.s-row.is-new,#modan-tbody tr.s-row.is-new{background:linear-gradient(270deg,rgba(16,185,129,.07) 0%,transparent 55%) !important;}
#bug-tbody tr.s-row.is-new:hover,#modan-tbody tr.s-row.is-new:hover{background:linear-gradient(270deg,rgba(16,185,129,.13) 0%,var(--bg3) 55%) !important;}
#bug-tbody tr.s-row.is-new td:last-of-type,#modan-tbody tr.s-row.is-new td:last-of-type{border-right:3px solid #10b981;}
</style>

<script>
const DASH_BASE = typeof BASE!=='undefined'?BASE:'<?= $base ?>';
const CSRF      = '<?= View::e($csrf) ?>';
const CAN_EDIT  = <?= $canEdit?'true':'false' ?>;
// כל החנויות — ממופות לפי id
const ALL_BUG   = <?= json_encode(array_values($stores),      JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?: '[]' ?>;
const ALL_MODAN = <?= json_encode(array_values($modanStores), JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?: '[]' ?>;
const ALL_STORES_MAP = {};
[...ALL_BUG,...ALL_MODAN].forEach(s=>{ ALL_STORES_MAP[s.id]=s; });

/* ── prefs ── */
const PK='v2_dash';
function getPref(k,d){try{const p=JSON.parse(localStorage.getItem(PK)||'{}');return k in p?p[k]:d;}catch(e){return d;}}
function setPref(k,v){try{const p=JSON.parse(localStorage.getItem(PK)||'{}');p[k]=v;localStorage.setItem(PK,JSON.stringify(p));}catch(e){}}

/* ── copy helper ── */
let _toastTimer=null;
function copyText(val){
  navigator.clipboard.writeText(val).catch(()=>{
    const ta=document.createElement('textarea');ta.value=val;document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);
  });
  const toast=document.getElementById('copy-toast');
  document.getElementById('copy-toast-val').textContent=val;
  toast.style.display='block';toast.style.opacity='1';
  clearTimeout(_toastTimer);
  _toastTimer=setTimeout(()=>{toast.style.opacity='0';setTimeout(()=>{toast.style.display='none';},200);},2000);
}

/* ── section collapse ── */
function toggleSection(){const c=document.body.classList.toggle('section-closed');setPref('collapsed',c);}
if(getPref('collapsed',false))document.body.classList.add('section-closed');

/* ── edit mode ── */
const editCb=document.getElementById('edit-mode');
if(editCb){editCb.checked=getPref('editMode',false);if(editCb.checked)applyEM(true);}
function onEditModeChange(){applyEM(editCb.checked);setPref('editMode',editCb.checked);}
function applyEM(on){document.body.classList.toggle('edit-mode',on);const b=document.getElementById('add-btn');if(b)b.style.display=on?'flex':'none';const s=document.getElementById('sync-hours-btn');if(s)s.style.display=on?'flex':'none';}

/* ── modan ── */
const modanCb=document.getElementById('show-modan');
modanCb.checked=getPref('modan',false);
function onModanChange(){setPref('modan',modanCb.checked);renderFiltered();}

/* ── view ── */
let cv=getPref('view','grid');
function setView(v){
  cv=v;setPref('view',v);
  const isGrid=v==='grid'||v==='compact';
  document.getElementById('stores-grid').style.display=isGrid?'grid':'none';
  document.getElementById('stores-table').style.display=v==='table'?'block':'none';
  const ms=document.getElementById('modan-section');
  if(ms&&!isGrid)ms.style.display='none';
  document.getElementById('btn-grid').classList.toggle('active',v==='grid');
  document.getElementById('btn-compact').classList.toggle('active',v==='compact');
  document.getElementById('btn-table').classList.toggle('active',v==='table');
  document.body.classList.toggle('grid-compact',v==='compact');
}
setView(cv);

/* ── confetti in is-new grid cards ── */
(function(){
  const COLORS=['#10b981','#34d399','#6ee7b7','#f59e0b','#fbbf24','#60a5fa','#a78bfa'];
  document.querySelectorAll('.stores-grid .store-card.is-new').forEach(card=>{
    const h=card.offsetHeight||120;
    for(let i=0;i<9;i++){
      const dot=document.createElement('span');
      dot.className='confetti-dot';
      const x=8+Math.random()*84;
      dot.style.cssText=`left:${x}%;background:${COLORS[i%COLORS.length]};`+
        `--dur:${(2+Math.random()*2).toFixed(2)}s;`+
        `--delay:${(Math.random()*2.5).toFixed(2)}s;`+
        `--h:${h}px;`+
        `width:${3+Math.random()*4}px;height:${3+Math.random()*4}px;`+
        (Math.random()>.5?'border-radius:2px;':'');
      card.appendChild(dot);
    }
  });
})();

/* ── sort ── */
let sc=getPref('sort','num'),sd=getPref('sortDir','asc');
document.getElementById('sort-select').value=sc;
document.querySelectorAll('.th.srt[data-col]').forEach(th=>{
  th.addEventListener('click',()=>{
    const c=th.dataset.col;
    if(sc===c)sd=sd==='asc'?'desc':'asc';else{sc=c;sd='asc';}
    setPref('sort',sc);setPref('sortDir',sd);
    document.getElementById('sort-select').value=sc;
    updateSUI();renderFiltered();
  });
});
function sortStores(){sc=document.getElementById('sort-select').value;sd='asc';setPref('sort',sc);setPref('sortDir',sd);updateSUI();renderFiltered();}
function updateSUI(){
  document.querySelectorAll('.th.srt[data-col]').forEach(th=>{
    th.classList.remove('asc','desc');
    if(th.dataset.col===sc)th.classList.add(sd);
  });
}
updateSUI();

/* ── filter ── */
function filterStores(){
  const q=document.getElementById('store-search').value;
  document.getElementById('search-clear').style.display=q?'block':'none';
  setPref('city',document.getElementById('city-filter').value);
  renderFiltered();
}
function clearSearch(){document.getElementById('store-search').value='';filterStores();}

function renderFiltered(){
  const q=document.getElementById('store-search').value.toLowerCase().trim();
  const city=document.getElementById('city-filter').value;
  const modan=document.getElementById('show-modan').checked;

  function sortPool(pool){
    return [...pool].sort((a,b)=>{
      let va='',vb='';
      if(sc==='num'){va=a.store_num||'';vb=b.store_num||'';}
      else if(sc==='name'){va=a.name||'';vb=b.name||'';}
      else{va=a.city||'';vb=b.city||'';}
      return va.localeCompare(vb,'he',{numeric:true})*(sd==='asc'?1:-1);
    });
  }
  function matches(s){
    const mq=!q||s.name.toLowerCase().includes(q)||(s.store_num||'').includes(q)||(s.phone_main||'').includes(q)||(s.city||'').toLowerCase().includes(q);
    return mq&&(!city||s.city===city);
  }

  // Bug — filter/sort by data-id
  const sortedBug=sortPool(ALL_BUG);
  const visBug=new Set(sortedBug.filter(matches).map(s=>s.id));
  let ci=0,ri=0;
  sortedBug.forEach(s=>{
    const v=visBug.has(s.id);
    document.querySelectorAll(`#stores-grid .store-card[data-id="${s.id}"]`).forEach(el=>{el.style.display=v?'':'none';el.style.order=v?ci++:9999;});
    document.querySelectorAll(`#bug-tbody .s-row[data-id="${s.id}"]`).forEach(el=>{el.style.display=v?'':'none';el.style.order=v?ri++:9999;});
  });

  // Modan
  const modanSection=document.getElementById('modan-section');
  const modanTbody=document.getElementById('modan-tbody');
  if(modan){
    const sortedModan=sortPool(ALL_MODAN);
    const visModan=new Set(sortedModan.filter(matches).map(s=>s.id));
    let mi=0,mri=0;
    sortedModan.forEach(s=>{
      const v=visModan.has(s.id);
      document.querySelectorAll(`#stores-grid-modan .store-card[data-id="${s.id}"]`).forEach(el=>{el.style.display=v?'':'none';el.style.order=v?mi++:9999;});
      document.querySelectorAll(`#modan-tbody .s-row[data-id="${s.id}"]`).forEach(el=>{el.style.display=v?'':'none';el.style.order=v?mri++:9999;});
    });
    const mc=visModan.size;
    const isGridView=cv==='grid'||cv==='compact';
    if(modanSection)modanSection.style.display=(mc>0&&isGridView)?'block':'none';
    if(modanTbody)modanTbody.style.display=mc>0?'':'none';
    const mcEl=document.getElementById('modan-count');if(mcEl)mcEl.textContent=mc+' נקודות';
    const mtcEl=document.getElementById('modan-table-count');if(mtcEl)mtcEl.textContent='('+mc+')';
  } else {
    if(modanSection)modanSection.style.display='none';
    if(modanTbody)modanTbody.style.display='none';
  }

  const total=visBug.size+(modan?[...ALL_MODAN].filter(matches).length:0);
  const poolSize=ALL_BUG.length+(modan?ALL_MODAN.length:0);
  document.getElementById('result-count').textContent=total<poolSize?`מציג ${total} מתוך ${poolSize}`:poolSize+' סניפים';
  document.getElementById('section-count').textContent=total;
  document.getElementById('stat-count').textContent=visBug.size+' סניפי באג';
  const modanPill=document.getElementById('stat-modan-pill');
  if(modanPill)modanPill.style.display=modan?'inline-flex':'none';
  const modanCountEl=document.getElementById('stat-modan-count');
  if(modanCountEl&&modan)modanCountEl.textContent=(modan?[...ALL_MODAN].filter(matches).length:0)+' נקודות מודן';
  document.getElementById('no-results').style.display=total===0?'block':'none';
}

/* ── store view modal — לפי id ── */
let _svId=null;
async function openStoreView(id){
  if(typeof gsClose==='function') gsClose();
  if(document.body.classList.contains('edit-mode')&&CAN_EDIT){
    openStoreForm(ALL_STORES_MAP[id]||null);return;
  }
  _svId=id;
  const s=ALL_STORES_MAP[id];if(!s)return;
  const isBug=s.type==='סניף באג';
  const col=isBug?'var(--accent)':'#8b5cf6';
  const sz=getPref('svSize','m');
  const isL=sz==='l';
  const fs=sz==='s'?'12px':sz==='l'?'15px':'13px';

  const titleNum=(s.store_num&&s.store_num!=='0')?`<span style="font-size:22px;font-weight:800;color:${col};margin-left:6px">${esc(s.store_num)}</span>`:'';
  document.getElementById('sv-title').innerHTML=titleNum+esc(s.name);

  // card helper
  const card=(icon,color,title,body)=>`<div class="sv-cat">
    <div class="sv-cat-title" style="color:${color}">${icon} ${title}</div>
    ${body}
  </div>`;

  const ph=v=>v?`<span class="copy-val" onclick="copyText('${esc(v)}')" style="font-weight:600;">${esc(v)}</span>`:'';

  let cards=[];

  // מיקום
  if(s.city||s.address){
    let b=s.city?`<div style="font-weight:600">${esc(s.city)}</div>`:'';
    b+=s.address?`<div style="color:var(--text2);margin-top:2px">${esc(s.address)}</div>`:'';
    cards.push(card('<i class="bi bi-geo-alt-fill"></i>','#10b981','מיקום',b));
  }

  // פרטי חנות: טלפונים, מייל, שלוחה, קו
  {
    let rows=[];
    if(s.phone_main) rows.push(`<div><div style="font-size:11px;color:var(--text3)">ראשי</div>${ph(s.phone_main)}</div>`);
    if(s.phone_cell) rows.push(`<div><div style="font-size:11px;color:var(--text3)">נייד</div>${ph(s.phone_cell)}</div>`);
    if(s.mvoice_queue) rows.push(`<div><div style="font-size:11px;color:var(--text3)">שלוחה</div><span style="font-weight:600">${esc(s.mvoice_queue)}</span></div>`);
    if(s.telephone_line_num) rows.push(`<div><div style="font-size:11px;color:var(--text3)">קו</div><span style="font-weight:600">${esc(s.telephone_line_num)}</span></div>`);
    if(s.email) rows.push(`<div style="grid-column:1/-1"><div style="font-size:11px;color:var(--text3)">מייל</div><a href="mailto:${esc(s.email)}" style="color:var(--accent);text-decoration:none;font-weight:600;">${esc(s.email)}</a></div>`);
    if(rows.length){
      const grid=`<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">${rows.join('')}</div>`;
      cards.push(card('<i class="bi bi-telephone-fill"></i>','var(--accent)','פרטי חנות <span style="font-size:10px;color:var(--text3);font-weight:400;font-style:italic;">לחץ להעתקה</span>',grid));
    }
  }

  // שעות פעילות
  if(s.work_hours){
    const dayNames={'א':'א-ה','ו':'שישי','ש':'שבת'};
    let wh=s.work_hours;
    if(typeof wh==='string'){try{wh=JSON.parse(wh);}catch(e){wh=null;}}
    if(wh&&typeof wh==='object'){
      const rows=Object.entries(wh).filter(([,v])=>v).map(([k,v])=>`<div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--border);"><span style="color:var(--text2)">${esc(dayNames[k]||k)}</span><span style="font-weight:600">${esc(v)}</span></div>`).join('');
      if(rows) cards.push(card('<i class="bi bi-clock-fill"></i>','#06b6d4','שעות פעילות',rows));
    }
  }

  // מנהל חנות
  if(s.manager_name||s.manager_cell){
    const avatar=`<div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#f59e0b,#f97316);display:grid;place-items:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0">${esc((s.manager_name||'?').charAt(0))}</div>`;
    const info=`<div>${s.manager_name?`<div style="font-weight:600">${esc(s.manager_name)}</div>`:''} ${s.manager_cell?`<div>${ph(s.manager_cell)}</div>`:''}</div>`;
    cards.push(card('<i class="bi bi-person-fill"></i>','#f59e0b','מנהל חנות',`<div style="display:flex;align-items:center;gap:10px;">${avatar}${info}</div>`));
  }

  // תגיות
  if(s.tags){
    const tags=s.tags.split(',').map(t=>t.trim()).filter(Boolean);
    if(tags.length){
      const b=`<div style="display:flex;flex-wrap:wrap;gap:5px;">${tags.map(t=>`<span style="background:var(--bg4);border:1px solid var(--border);border-radius:12px;padding:2px 10px;font-size:11px;color:var(--text2);">${esc(t)}</span>`).join('')}</div>`;
      cards.push(card('<i class="bi bi-tags-fill"></i>','var(--text3)','תגיות',b));
    }
  }

  // הערה
  if(s.note){
    cards.push(card('<i class="bi bi-sticky-fill"></i>','#8b5cf6','הערה',`<div style="color:var(--text2)">${esc(s.note).replace(/\n/g,'<br>')}</div>`));
  }

  let html='';

  // alert — full width always
  if(s.alert_note){
    const ts=s.alert_updated_at?`<div style="font-size:11px;color:var(--text3);margin-top:3px;">${new Date(s.alert_updated_at).toLocaleString('he-IL',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'})}</div>`:'';
    html+=`<div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-right:3px solid var(--warning);border-radius:8px;padding:10px 14px;margin-bottom:10px;display:flex;gap:10px;align-items:flex-start;">
      <i class="bi bi-exclamation-triangle-fill" style="color:var(--warning);flex-shrink:0;margin-top:2px;"></i>
      <div><div style="font-weight:600;color:var(--warning);">${esc(s.alert_note)}</div>${ts}</div>
    </div>`;
  }

  // set font-size on body so all cards inherit
  document.getElementById('sv-body').style.fontSize=fs;

  // grid of cards — 1 col for S/M, 2 cols for L
  html+=`<div style="display:grid;gap:8px;${isL?'grid-template-columns:1fr 1fr;':''}">`;
  html+=cards.join('');
  // area managers placeholder (filled async)
  html+=`<div id="sv-am-slot"${isL?'':''} style="${isL?'grid-column:1/-1;':''}"></div>`;
  html+=`</div>`;

  // footer
  html+=`<div style="display:flex;gap:8px;margin-top:12px;">
    <a href="${DASH_BASE}/stores/id/${s.id}" class="btn btn-primary" style="flex:1;justify-content:center;">
      <i class="bi bi-arrow-left-circle"></i> עמוד מלא
    </a>
    ${s.phone_main?`<button class="btn btn-ghost" onclick="copyText('${esc(s.phone_main)}')" title="העתק טלפון ראשי"><i class="bi bi-telephone-fill"></i></button>`:''}
  </div>`;

  document.getElementById('sv-body').innerHTML=html;
  document.getElementById('sv-modal').style.display='flex';

  // Confetti burst for new stores
  const isNewStore=!!(s.created_at && (Date.now()-new Date(s.created_at).getTime()) < 7*24*3600*1000);
  const svInner=document.getElementById('sv-inner');
  svInner.querySelectorAll('.sv-confetti-dot').forEach(el=>el.remove());
  if(isNewStore){
    const COLS=['#10b981','#34d399','#6ee7b7','#f59e0b','#fbbf24','#60a5fa','#a78bfa','#f472b6'];
    for(let i=0;i<18;i++){
      const d=document.createElement('span');
      d.className='sv-confetti-dot';
      const x=4+Math.random()*92;
      const size=3+Math.random()*5;
      d.style.cssText=`left:${x}%;background:${COLS[i%COLS.length]};`+
        `width:${size}px;height:${size}px;`+
        `--dur:${(1.8+Math.random()*2.2).toFixed(2)}s;`+
        `--delay:${(Math.random()*1.5).toFixed(2)}s;`+
        `--fall:${220+Math.floor(Math.random()*120)}px;`+
        (Math.random()>.5?'border-radius:2px;':'border-radius:50%;');
      svInner.appendChild(d);
    }
  }

  // Area managers — async
  try{
    const ams=await fetch(`${DASH_BASE}/api/area-managers/for-store/${id}`).then(r=>r.json());
    const slot=document.getElementById('sv-am-slot');
    if(slot&&ams&&ams.length){
      slot.innerHTML=`<div class="sv-cat">
        <div class="sv-cat-title" style="color:var(--accent)"><i class="bi bi-person-badge"></i> מנהלי אזור</div>
        ${ams.map(am=>`<div style="display:flex;flex-direction:column;gap:3px;margin-bottom:8px;padding:7px 10px;background:var(--bg3);border-radius:8px;cursor:pointer;"
          onclick="showAmDetail(${JSON.stringify(am).replace(/</g,'\\u003c').replace(/>/g,'\\u003e').replace(/&/g,'\\u0026')})">
          <div style="font-weight:600;">${escHtml(am.name)}</div>
          ${am.phone?`<a href="tel:${escHtml(am.phone)}" onclick="event.stopPropagation()" style="color:var(--accent);text-decoration:none;display:flex;align-items:center;gap:5px;font-size:12px;"><i class="bi bi-telephone-fill"></i>${escHtml(am.phone)}</a>`:''}
          ${am.email?`<a href="mailto:${escHtml(am.email)}" onclick="event.stopPropagation()" style="color:var(--accent);text-decoration:none;display:flex;align-items:center;gap:5px;font-size:12px;"><i class="bi bi-envelope-fill"></i>${escHtml(am.email)}</a>`:''}
        </div>`).join('')}
      </div>`;
    } else if(slot){
      slot.remove();
    }
  }catch(e){}
}

function closeSV(){document.getElementById('sv-modal').style.display='none';}
function switchToEdit(){closeSV();openStoreForm(ALL_STORES_MAP[_svId]||null);}
document.getElementById('sv-modal').addEventListener('click',e=>{if(e.target===document.getElementById('sv-modal'))closeSV();});

const SV_SIZES={s:{maxWidth:'400px'},m:{maxWidth:'520px'},l:{maxWidth:'820px'}};
function svSetSize(sz, rerender){
  const inner=document.getElementById('sv-inner');
  const d=SV_SIZES[sz]||SV_SIZES.m;
  inner.style.maxWidth=d.maxWidth;
  ['s','m','l'].forEach(s=>{
    const b=document.getElementById('sv-size-'+s);
    if(b){b.style.background=s===sz?'var(--accent)':'';b.style.color=s===sz?'#fff':'var(--text2)';}
  });
  setPref('svSize',sz);
  // re-render if modal is open and id known
  if(rerender!==false && _svId!=null && document.getElementById('sv-modal').style.display==='flex'){
    openStoreView(_svId);
  }
}
// init from prefs — no rerender on load
svSetSize(getPref('svSize','m'), false);

async function doToggle(id){
  if(!confirm('לשנות סטטוס?'))return;
  const res=await fetch(DASH_BASE+'/stores/'+id+'/toggle',{method:'POST',body:new URLSearchParams({_csrf:CSRF})});
  const d=await res.json();if(d.ok)location.reload();
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function showAmDetail(am) {
  const sourceLabel = am.source_type === 'contact' ? 'איש קשר' : 'משתמש מערכת';
  document.getElementById('am-detail-body').innerHTML = `
    <div style="display:flex;flex-direction:column;gap:10px;">
      <div style="font-size:16px;font-weight:700;">${escHtml(am.name)}</div>
      ${am.phone ? `<a href="tel:${escHtml(am.phone)}" style="color:var(--accent);font-size:14px;text-decoration:none;"><i class="bi bi-telephone-fill"></i> ${escHtml(am.phone)}</a>` : ''}
      ${am.email ? `<a href="mailto:${escHtml(am.email)}" style="color:var(--accent);font-size:14px;text-decoration:none;"><i class="bi bi-envelope-fill"></i> ${escHtml(am.email)}</a>` : ''}
      <div style="font-size:12px;color:var(--text3);margin-top:4px;">${sourceLabel}</div>
    </div>`;
  document.getElementById('am-detail-modal').style.display = 'flex';
}

/* ── sync bug work hours ── */
async function syncBugWorkHours(){
  const btn=document.getElementById('sync-hours-btn');
  btn.disabled=true;
  btn.innerHTML='<i class="bi bi-arrow-repeat"></i> מסנכרן...';
  try{
    const res=await fetch(DASH_BASE+'/stores/sync-work-hours',{method:'POST',body:new URLSearchParams({_csrf:CSRF})});
    if(res.redirected||!res.ok){location.reload();return;}
    const d=await res.json();
    if(d.ok){
      alert('סנכרון הצליח — עודכנו '+d.updated+' חנויות');
    } else {
      alert('שגיאה: '+(d.error||'נכשל'));
    }
  } catch(e){
    alert('שגיאה: '+e.message);
  } finally {
    btn.disabled=false;
    btn.innerHTML='<i class="bi bi-clock-history"></i> סנכרון שעות';
  }
}

// ── Duty inline ───────────────────────────────────────────────────────
(async function () {
  try {
    const data = await fetch(DASH_BASE + '/api/duty/current').then(r => r.json());
    if (!data.schedule) return;
    const s  = data.schedule;
    const ws = new Date(data.week_start);
    const we = new Date(ws); we.setDate(ws.getDate() + 6);
    const f  = d => `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}`;
    document.getElementById('dw-name').textContent = s.rep_name || '';
    document.getElementById('dw-dept').textContent = s.department || '';
    document.getElementById('dw-week').textContent = `${f(ws)}–${f(we)}`;
    document.getElementById('dw-inline').style.display = '';
  } catch { }
})();

/* ── 3D parallax + glare on store cards — single delegated listener ── */
(function(){
  const TILT=12;
  let _hovered=null;
  let _pending=false;
  let _ex=0,_ey=0;

  function getGlare(card){
    let g=card.querySelector('.pk-glare');
    if(!g){g=document.createElement('div');g.className='pk-glare';card.appendChild(g);}
    return g;
  }

  function applyTilt(){
    _pending=false;
    if(!_hovered)return;
    const r=_hovered.getBoundingClientRect();
    const x=(_ex-r.left)/r.width-.5;   // -0.5..0.5
    const y=(_ey-r.top)/r.height-.5;

    // tilt
    _hovered.style.transform=`perspective(600px) rotateY(${x*TILT}deg) rotateX(${-y*TILT}deg) scale(1.03) translateY(-4px)`;

    // glare — highlight cone centered where the "light" hits
    const isBug=_hovered.classList.contains('c-bug');
    const col=isBug?'91,141,238':'139,92,246';
    // gx/gy in % relative to card (0-100)
    const gx=(_ex-r.left)/r.width*100;
    const gy=(_ey-r.top)/r.height*100;
    const glare=getGlare(_hovered);
    glare.style.background=`radial-gradient(ellipse 80% 60% at ${gx}% ${gy}%, rgba(255,255,255,.13) 0%, rgba(${col},.07) 40%, transparent 70%)`;
    glare.style.opacity='1';

    // dynamic shadow — deeper on the side away from cursor
    const sx=x*18, sy=y*18;
    _hovered.style.boxShadow=`${-sx}px ${-sy}px 32px rgba(0,0,0,.55), 0 8px 24px rgba(0,0,0,.35)`;
  }

  function resetCard(card){
    card.style.transition='transform .3s cubic-bezier(.34,1.3,.64,1),box-shadow .3s,border-color .15s';
    card.style.transform='';
    card.style.boxShadow='';
    const g=card.querySelector('.pk-glare');
    if(g)g.style.opacity='0';
  }

  ['stores-grid','stores-grid-modan'].forEach(function(id){
    const g=document.getElementById(id);
    if(!g)return;

    g.addEventListener('mousemove',function(e){
      const card=e.target.closest('.store-card');
      if(!card)return;
      if(_hovered&&_hovered!==card){
        // moved to a different card — reset previous immediately
        _hovered.style.transition='none';
        resetCard(_hovered);
      }
      _hovered=card;
      // remove transition while moving for crisp tracking
      card.style.transition='box-shadow .06s,border-color .15s';
      _ex=e.clientX;_ey=e.clientY;
      if(!_pending){_pending=true;requestAnimationFrame(applyTilt);}
    });

    g.addEventListener('mouseleave',function(e){
      const card=e.target.closest('.store-card');
      if(!card||!card.matches(':hover')===false){}
      // use relatedTarget to confirm truly leaving the card
      if(card&&!card.contains(e.relatedTarget)){
        resetCard(card);
        if(_hovered===card)_hovered=null;
      }
    },true);
  });
})();

// Restore prefs
const sc2=getPref('city','');if(sc2)document.getElementById('city-filter').value=sc2;
document.addEventListener('keydown',e=>{
  if(e.key==='Escape'){closeSV();if(typeof closeStoreForm==='function')closeStoreForm();document.getElementById('am-detail-modal').style.display='none';}
});
renderFiltered();
</script>
