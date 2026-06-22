<?php
/** @var array[] $stores   @var array[] $types  @var array[] $cities
 *  @var string  $q        @var string  $type   @var string  $city  */
use Core\View;
use Core\Auth;
$base    = rtrim(CFG['app']['url'], '/');
$canEdit = Auth::can('canEditStore');
$csrf    = $_SESSION['csrf_token'] ?? '';
$storeTypes = ['סניף באג','נקודת מודן','מחסן','אחר'];
?>

<div class="page-title">חיפוש סניפים</div>

<div class="card" style="margin-bottom:18px;">
  <form method="GET" action="<?= $base ?>/stores/search"
        style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <div style="flex:2;min-width:200px;">
      <label class="flabel">חיפוש חופשי</label>
      <div class="search-box" onfocusin="this.style.borderColor='var(--accent)'" onfocusout="this.style.borderColor=''">
        <i class="bi bi-search" style="color:var(--text3);"></i>
        <input type="text" name="q" value="<?= View::e($q ?? '') ?>"
               placeholder="שם, מספר, טלפון, עיר, כתובת, מנהל..."
               style="background:none;border:none;outline:none;color:var(--text);font-family:var(--font);font-size:14px;padding:9px 0;width:100%;"
               autofocus>
      </div>
    </div>
    <div style="min-width:140px;">
      <label class="flabel">סוג</label>
      <select name="type" class="finput">
        <option value="">כל הסוגים</option>
        <?php foreach ($types as $t): ?>
          <option value="<?= View::e($t['type']) ?>" <?= ($type??'')===$t['type']?'selected':'' ?>><?= View::e($t['type']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="min-width:140px;">
      <label class="flabel">עיר</label>
      <select name="city" class="finput">
        <option value="">כל הערים</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= View::e($c['city']) ?>" <?= ($city??'')===$c['city']?'selected':'' ?>><?= View::e($c['city']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> חפש</button>
    <?php if (!empty($q)||!empty($type)||!empty($city)): ?>
      <a href="<?= $base ?>/stores" class="btn btn-ghost"><i class="bi bi-x"></i> נקה</a>
    <?php endif; ?>
  </form>
</div>

<?php if (!empty($stores)): ?>
<div class="card" style="padding:0;overflow:hidden;">
  <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border);">
    <div style="font-size:13px;color:var(--text2);">נמצאו <strong style="color:var(--text)"><?= count($stores) ?></strong> תוצאות</div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary" style="padding:5px 12px;font-size:12px;" onclick="openStoreForm(null)">
      <i class="bi bi-plus-lg"></i> הוסף סניף
    </button>
    <?php endif; ?>
  </div>
  <div style="overflow-x:auto;">
    <table id="stores-tbl" style="width:100%;border-collapse:collapse;font-size:13px;min-width:700px;">
      <thead>
        <tr style="background:var(--bg3);color:var(--text2);">
          <th class="th-sort" data-col="0">מספר</th>
          <th class="th-sort" data-col="1">שם</th>
          <th class="th-sort" data-col="2">סוג</th>
          <th class="th-sort" data-col="3">עיר</th>
          <th class="th-sort" data-col="4">טלפון</th>
          <th class="th-sort" data-col="5">שלוחה</th>
          <th class="th-sort" data-col="6">מנהל</th>
          <th style="padding:10px 12px;text-align:right;"></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($stores as $s):
        $isBug   = ($s['type']??'') === 'סניף באג';
        $isModan = ($s['type']??'') === 'נקודת מודן';
        $hasAlert= !empty($s['alert_note']);
      ?>
      <tr style="border-bottom:1px solid var(--border);<?= $hasAlert?'background:rgba(245,158,11,.04);':'' ?>cursor:pointer;"
          onclick="window.location='<?= $base ?>/stores/id/<?= (int)$s['id'] ?>'">
        <td style="padding:10px 14px;">
          <span style="font-weight:800;font-size:17px;color:<?= $isBug?'var(--accent)':($isModan?'#8b5cf6':'var(--text2)') ?>;">
            <?= $s['store_num'] ? View::e($s['store_num']) : '—' ?>
          </span>
        </td>
        <td style="padding:10px 12px;">
          <div style="font-weight:600;display:flex;align-items:center;gap:6px;">
            <span style="width:5px;height:5px;border-radius:50%;background:<?= $isBug?'var(--accent)':($isModan?'#8b5cf6':'var(--text3)') ?>;display:inline-block;flex-shrink:0;"></span>
            <?= View::e($s['name']) ?>
            <?php if ($hasAlert): ?>
              <i class="bi bi-exclamation-triangle-fill" style="color:var(--warning);font-size:12px;" title="<?= View::e($s['alert_note']) ?>"></i>
            <?php endif; ?>
          </div>
          <?php if ($hasAlert): ?>
          <div style="font-size:11px;color:var(--warning);margin-top:2px;display:flex;align-items:center;gap:4px;">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?= View::e(mb_substr($s['alert_note'],0,55)) ?><?= mb_strlen($s['alert_note'])>55?'…':'' ?>
            <?php if (!empty($s['alert_updated_at'])): ?>
              <span style="color:var(--text3);font-size:10px;">· <?= date('d/m H:i', strtotime($s['alert_updated_at'])) ?></span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </td>
        <td style="padding:10px 12px;">
          <?php if ($isBug): ?><span class="badge badge-info" style="font-size:11px;">באג</span>
          <?php elseif ($isModan): ?><span class="badge badge-purple" style="font-size:11px;">מודן</span>
          <?php else: ?><span class="badge" style="background:var(--bg4);color:var(--text2);font-size:11px;"><?= View::e($s['type']) ?></span><?php endif; ?>
        </td>
        <td style="padding:10px 12px;color:var(--text2);"><?= View::e($s['city']??'') ?></td>
        <td style="padding:10px 12px;direction:ltr;text-align:right;">
          <?php if ($s['phone_main']): ?>
            <a href="tel:<?= View::e($s['phone_main']) ?>" onclick="event.stopPropagation()"
               style="color:var(--accent);text-decoration:none;font-family:monospace;"><?= View::e($s['phone_main']) ?></a>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td style="padding:10px 12px;color:var(--text2);"><?= View::e($s['mvoice_queue']??'—') ?></td>
        <td style="padding:10px 12px;color:var(--text2);"><?= View::e($s['manager_name']??'') ?></td>
        <td style="padding:10px 12px;" onclick="event.stopPropagation()">
          <div style="display:flex;gap:4px;">
            <a href="<?= $base ?>/stores/id/<?= (int)$s['id'] ?>" class="btn btn-ghost" style="padding:4px 8px;font-size:12px;">פרטים</a>
            <?php if ($canEdit): ?>
            <button class="btn btn-ghost" style="padding:4px 8px;font-size:12px;"
                    onclick="openStoreForm(<?= htmlspecialchars(json_encode($s, JSON_UNESCAPED_UNICODE)) ?>)">
              <i class="bi bi-pencil-fill"></i>
            </button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif (isset($q) || !empty($type) || !empty($city)): ?>
  <div class="alert alert-info"><i class="bi bi-search"></i> לא נמצאו תוצאות</div>
<?php else: ?>
  <div style="text-align:center;padding:60px 20px;color:var(--text3);">
    <i class="bi bi-search" style="font-size:40px;display:block;margin-bottom:12px;opacity:.4;"></i>
    <div style="font-size:15px;">הזן מונח חיפוש כדי למצוא סניפים</div>
    <div style="font-size:13px;margin-top:6px;">ניתן לחפש לפי שם, מספר, טלפון, עיר, כתובת או מנהל</div>
  </div>
<?php endif; ?>

<?php if ($canEdit): ?>
<div id="store-edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:center;justify-content:center;padding:20px;">
  <?php include __DIR__ . '/_store_form.php'; ?>
</div>
<?php endif; ?>

<style>
.flabel{display:block;font-size:12px;color:var(--text2);margin-bottom:5px;font-weight:500}
.finput{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--text);font-size:14px;font-family:var(--font);outline:none}
.finput:focus{border-color:var(--accent)}
.search-box{display:flex;align-items:center;gap:8px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0 12px;transition:border-color .15s}
.th-sort{padding:10px 12px;text-align:right;font-weight:600;font-size:12px;cursor:pointer;user-select:none;white-space:nowrap;border-bottom:1px solid var(--border)}
.th-sort:hover{color:var(--text);background:var(--bg4)}
.th-sort.asc::after{content:' ↑';font-size:10px;color:var(--accent)}
.th-sort.desc::after{content:' ↓';font-size:10px;color:var(--accent)}
tbody tr:hover{background:var(--bg3)!important}
</style>

<script>
let _sc=-1,_sd=1;
document.querySelectorAll('.th-sort').forEach(th=>{
  th.addEventListener('click',()=>{
    const col=parseInt(th.dataset.col);
    if(_sc===col)_sd*=-1;else{_sc=col;_sd=1;}
    document.querySelectorAll('.th-sort').forEach(t=>t.classList.remove('asc','desc'));
    th.classList.add(_sd===1?'asc':'desc');
    const tb=document.querySelector('#stores-tbl tbody');
    [...tb.querySelectorAll('tr')].sort((a,b)=>
      (a.cells[col]?.textContent.trim()||'').localeCompare(b.cells[col]?.textContent.trim()||'','he',{numeric:true})*_sd
    ).forEach(r=>tb.appendChild(r));
  });
});
</script>
