<?php
use Core\View;
$base     = rtrim(CFG['app']['url'], '/');
$showCron = $showCron ?? false;
$filters  = $filters ?? [];

function actionColor(string $action): array {
    if (str_contains($action, 'login_ok') || str_contains($action, 'create'))  return ['#10b981','rgba(16,185,129,.12)'];
    if (str_contains($action, 'login_fail') || str_contains($action, 'delete')) return ['#ef4444','rgba(239,68,68,.1)'];
    if (str_contains($action, 'update'))   return ['#5b8dee','rgba(91,141,238,.12)'];
    if (str_contains($action, 'toggle'))   return ['#f59e0b','rgba(245,158,11,.1)'];
    if (str_contains($action, 'logout'))   return ['#7c829c','rgba(124,130,156,.1)'];
    if (str_contains($action, 'cancel'))   return ['#f97316','rgba(249,115,22,.1)'];
    return ['#7c829c','rgba(124,130,156,.1)'];
}
function cronStatusColor(string $status): array {
    return match($status) {
        'ok'      => ['#10b981','rgba(16,185,129,.12)'],
        'warning' => ['#f59e0b','rgba(245,158,11,.1)'],
        'error'   => ['#ef4444','rgba(239,68,68,.1)'],
        default   => ['#7c829c','rgba(124,130,156,.1)'],
    };
}
function actionIcon(string $action): string {
    if (str_contains($action, 'login_ok'))   return 'bi-box-arrow-in-right';
    if (str_contains($action, 'login_fail')) return 'bi-exclamation-triangle-fill';
    if (str_contains($action, 'logout'))     return 'bi-box-arrow-right';
    if (str_contains($action, 'create'))     return 'bi-plus-circle-fill';
    if (str_contains($action, 'update'))     return 'bi-pencil-fill';
    if (str_contains($action, 'delete'))     return 'bi-trash-fill';
    if (str_contains($action, 'toggle'))     return 'bi-toggle-on';
    if (str_contains($action, 'cancel'))     return 'bi-x-circle-fill';
    return 'bi-activity';
}
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <div class="page-title" style="margin-bottom:4px;">
      <i class="bi <?= $showCron ? 'bi-clock-fill' : 'bi-clock-history' ?>" style="color:var(--accent);"></i>
      <?= $showCron ? 'לוג CRON' : 'לוג פעולות' ?>
    </div>
    <div style="font-size:13px;color:var(--text3);"><?= number_format($total ?? 0) ?> רשומות</div>
  </div>
  <div style="display:flex;gap:8px;">
    <a href="<?= $base ?>/activity-log<?= $showCron ? '' : '?cron=1' ?>"
       style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;
              border:1px solid <?= $showCron ? 'var(--accent)' : 'var(--border)' ?>;
              background:<?= $showCron ? 'var(--accent)' : 'var(--bg3)' ?>;
              color:<?= $showCron ? '#fff' : 'var(--text2)' ?>;">
      <i class="bi bi-clock-fill"></i> CRON
    </a>
  </div>
</div>

<?php if ($showCron): ?>
<!-- ── CRON Filters ── -->
<div class="card" style="padding:14px;margin-bottom:16px;">
  <form method="GET" action="<?= $base ?>/activity-log" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
    <input type="hidden" name="cron" value="1">
    <select name="cron_name" class="log-sel">
      <option value="">כל הכרונים</option>
      <?php foreach ($cronNames ?? [] as $cn): ?>
        <option value="<?= View::e($cn['cron_name']) ?>" <?= ($filters['cron_name']??'')===$cn['cron_name']?'selected':'' ?>>
          <?= View::e($cn['cron_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="action" class="log-sel">
      <option value="">כל הפעולות</option>
      <?php foreach ($cronActions ?? [] as $a): ?>
        <option value="<?= View::e($a['action']) ?>" <?= ($filters['action']??'')===$a['action']?'selected':'' ?>>
          <?= View::e($a['action']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="log-sel">
      <option value="">כל הסטטוסים</option>
      <option value="ok"      <?= ($filters['status']??'')==='ok'?'selected':''      ?>>ok</option>
      <option value="warning" <?= ($filters['status']??'')==='warning'?'selected':'' ?>>warning</option>
      <option value="error"   <?= ($filters['status']??'')==='error'?'selected':''   ?>>error</option>
    </select>
    <input type="datetime-local" name="from" class="log-sel"
           value="<?= View::e($filters['from'] ?? '') ?>" title="מתאריך">
    <input type="datetime-local" name="to" class="log-sel"
           value="<?= View::e($filters['to'] ?? '') ?>" title="עד תאריך">
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> סנן</button>
    <?php if (array_filter($filters)): ?>
      <a href="<?= $base ?>/activity-log?cron=1" class="btn btn-ghost"><i class="bi bi-x"></i> נקה</a>
    <?php endif; ?>
  </form>
</div>

<!-- ── CRON table ── -->
<div class="card" style="padding:0;overflow:hidden;">
  <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
      <thead>
        <tr style="background:var(--bg3);">
          <th class="lth">זמן</th>
          <th class="lth">כרון</th>
          <th class="lth">פעולה</th>
          <th class="lth">סטטוס</th>
          <th class="lth">פרטים</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($logs)): ?>
        <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text3);">
          <i class="bi bi-inbox" style="font-size:28px;display:block;margin-bottom:8px;opacity:.3;"></i>
          אין רשומות
        </td></tr>
      <?php else: ?>
      <?php foreach ($logs as $log):
        [$sColor, $sBg] = cronStatusColor($log['status']);
      ?>
      <tr style="border-bottom:1px solid var(--border);">
        <td class="ltd" style="white-space:nowrap;font-size:12px;color:var(--text3);">
          <?= date('d/m/Y', strtotime($log['created_at'])) ?><br>
          <span style="font-size:11px;"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
        </td>
        <td class="ltd">
          <span style="font-family:monospace;font-size:12px;color:var(--accent);"><?= View::e($log['cron_name']) ?></span>
        </td>
        <td class="ltd" style="font-size:12px;color:var(--text2);"><?= View::e($log['action']) ?></td>
        <td class="ltd">
          <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:10px;
                       background:<?= $sBg ?>;color:<?= $sColor ?>;font-size:11px;font-weight:700;">
            <i class="bi <?= $log['status']==='ok' ? 'bi-check-circle-fill' : ($log['status']==='error' ? 'bi-x-circle-fill' : 'bi-exclamation-triangle-fill') ?>"></i>
            <?= View::e($log['status']) ?>
          </span>
        </td>
        <td class="ltd" style="font-size:12px;color:var(--text2);"><?= View::e($log['details'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination CRON -->
  <?php if (isset($pages) && $pages > 1): ?>
  <div style="display:flex;align-items:center;gap:6px;padding:12px 16px;border-top:1px solid var(--border);flex-wrap:wrap;">
    <?php
    $currentPage = $page ?? 1;
    $qs = http_build_query(array_merge($filters ?? [], ['cron' => '1', 'page' => 0]));
    for ($p = 1; $p <= min($pages, 20); $p++):
      $qp = str_replace('page=0', 'page='.$p, $qs);
    ?>
      <a href="<?= $base ?>/activity-log?<?= $qp ?>"
         style="display:inline-block;padding:4px 10px;border-radius:6px;font-size:13px;text-decoration:none;
                background:<?= $p===$currentPage?'var(--accent)':'var(--bg3)' ?>;
                color:<?= $p===$currentPage?'#fff':'var(--text2)' ?>;
                border:1px solid <?= $p===$currentPage?'var(--accent)':'var(--border)' ?>;">
        <?= $p ?>
      </a>
    <?php endfor; ?>
    <span style="font-size:12px;color:var(--text3);margin-right:auto;">
      עמוד <?= $currentPage ?> מתוך <?= $pages ?> (<?= number_format($total ?? 0) ?> רשומות)
    </span>
  </div>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- ── Filters ── -->
<div class="card" style="padding:14px;margin-bottom:16px;">
  <form method="GET" action="<?= $base ?>/activity-log" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
    <!-- search -->
    <div class="log-srch">
      <i class="bi bi-search"></i>
      <input type="text" name="q" value="<?= View::e($filters['q'] ?? '') ?>"
             placeholder="חיפוש רשומה, IP, ערך...">
    </div>
    <!-- action -->
    <select name="action" class="log-sel">
      <option value="">כל הפעולות</option>
      <?php foreach ($actions ?? [] as $a): ?>
        <option value="<?= View::e($a['action']) ?>" <?= ($filters['action']??'')===$a['action']?'selected':'' ?>>
          <?= View::e($a['action']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <!-- entity -->
    <select name="entity" class="log-sel">
      <option value="">כל הישויות</option>
      <?php foreach ($entities ?? [] as $e): ?>
        <option value="<?= View::e($e['entity_type']) ?>" <?= ($filters['entity_type']??'')===$e['entity_type']?'selected':'' ?>>
          <?= View::e($e['entity_type']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <!-- user -->
    <select name="user" class="log-sel">
      <option value="">כל המשתמשים</option>
      <?php foreach ($users ?? [] as $u): ?>
        <option value="<?= (int)$u['user_id'] ?>" <?= ($filters['user_id']??0)==(int)$u['user_id']?'selected':'' ?>>
          <?= View::e($u['user_name'] ?? 'ID '.$u['user_id']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <!-- dates -->
    <input type="datetime-local" name="from" class="log-sel"
           value="<?= View::e($filters['from'] ?? '') ?>" title="מתאריך">
    <input type="datetime-local" name="to" class="log-sel"
           value="<?= View::e($filters['to'] ?? '') ?>" title="עד תאריך">
    <!-- ip -->
    <input type="text" name="ip" class="log-sel" placeholder="IP"
           value="<?= View::e($filters['ip'] ?? '') ?>" style="max-width:140px;direction:ltr;">

    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> סנן</button>
    <?php if ($filters): ?>
      <a href="<?= $base ?>/activity-log" class="btn btn-ghost"><i class="bi bi-x"></i> נקה</a>
    <?php endif; ?>
  </form>
</div>

<!-- ── Log table ── -->
<div class="card" style="padding:0;overflow:hidden;">
  <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
      <thead>
        <tr style="background:var(--bg3);">
          <th class="lth">זמן</th>
          <th class="lth">משתמש</th>
          <th class="lth">פעולה</th>
          <th class="lth">ישות</th>
          <th class="lth">שינוי</th>
          <th class="lth">IP</th>
          <th class="lth" style="min-width:36px;"></th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($logs)): ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text3);">
          <i class="bi bi-inbox" style="font-size:28px;display:block;margin-bottom:8px;opacity:.3;"></i>
          אין רשומות
        </td></tr>
      <?php else: ?>
      <?php foreach ($logs as $log):
        [$aColor, $aBg] = actionColor($log['action']);
        $icon            = actionIcon($log['action']);
        $hasDiff         = !empty($log['diff_json']);
        $hasField        = !empty($log['field_name']);
        $rowId           = 'lr-' . $log['id'];
      ?>
      <tr class="log-row" onclick="toggleLogDetail('<?= $rowId ?>')"
          style="border-bottom:1px solid var(--border);cursor:pointer;">
        <td class="ltd" style="white-space:nowrap;font-size:12px;color:var(--text3);">
          <?= date('d/m/Y', strtotime($log['created_at'])) ?><br>
          <span style="font-size:11px;"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
        </td>
        <td class="ltd">
          <div style="font-weight:600;font-size:13px;"><?= View::e($log['user_name'] ?? '—') ?></div>
          <?php if ($log['user_id']): ?>
            <div style="font-size:11px;color:var(--text3);">ID <?= (int)$log['user_id'] ?></div>
          <?php endif; ?>
        </td>
        <td class="ltd">
          <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:12px;
                       background:<?= $aBg ?>;color:<?= $aColor ?>;font-size:11px;font-weight:700;white-space:nowrap;">
            <i class="bi <?= $icon ?>"></i>
            <?= View::e($log['action']) ?>
          </span>
        </td>
        <td class="ltd">
          <?php if ($log['entity_type']): ?>
            <div style="font-size:12px;color:var(--text3);"><?= View::e($log['entity_type']) ?></div>
          <?php endif; ?>
          <?php if ($log['entity_label']): ?>
            <div style="font-weight:500;"><?= View::e($log['entity_label']) ?></div>
          <?php endif; ?>
        </td>
        <td class="ltd">
          <?php if ($hasField): ?>
            <div style="font-size:12px;color:var(--text3);margin-bottom:3px;"><?= View::e($log['field_name']) ?></div>
            <div style="display:flex;align-items:center;gap:5px;font-size:12px;">
              <?php if ($log['old_value'] !== null): ?>
                <span style="background:rgba(239,68,68,.1);color:#f87171;padding:2px 7px;border-radius:4px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                      title="<?= View::e($log['old_value']) ?>"><?= View::e(mb_substr($log['old_value'],0,30)) ?></span>
                <i class="bi bi-arrow-left" style="color:var(--text3);flex-shrink:0;"></i>
              <?php endif; ?>
              <?php if ($log['new_value'] !== null): ?>
                <span style="background:rgba(16,185,129,.1);color:#4ade80;padding:2px 7px;border-radius:4px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                      title="<?= View::e($log['new_value']) ?>"><?= View::e(mb_substr($log['new_value'],0,30)) ?></span>
              <?php endif; ?>
            </div>
          <?php elseif ($hasDiff): ?>
            <?php $diff = json_decode($log['diff_json'], true) ?? []; ?>
            <span style="font-size:12px;color:var(--accent);">
              <?= count($diff) ?> שדות השתנו
            </span>
          <?php elseif ($log['detail']): ?>
            <span style="font-size:12px;color:var(--text2);"><?= View::e(mb_substr($log['detail'],0,50)) ?></span>
          <?php endif; ?>
        </td>
        <td class="ltd" style="direction:ltr;font-family:monospace;font-size:12px;color:var(--text3);">
          <?= View::e($log['ip']) ?>
        </td>
        <td class="ltd" style="text-align:center;">
          <?php if ($hasDiff || $hasField || $log['detail']): ?>
            <i class="bi bi-chevron-down log-chev" id="chev-<?= $rowId ?>"
               style="color:var(--text3);font-size:11px;transition:transform .2s;"></i>
          <?php endif; ?>
        </td>
      </tr>
      <!-- Detail row -->
      <?php if ($hasDiff || $hasField || $log['detail']): ?>
      <tr id="<?= $rowId ?>" style="display:none;background:var(--bg3);">
        <td colspan="7" style="padding:14px 20px;">
          <?php if ($log['detail']): ?>
            <div style="font-size:13px;color:var(--text2);margin-bottom:8px;">
              <i class="bi bi-info-circle" style="color:var(--accent);margin-left:5px;"></i>
              <?= View::e($log['detail']) ?>
            </div>
          <?php endif; ?>
          <?php if ($hasDiff): ?>
            <?php $diff = json_decode($log['diff_json'], true) ?? []; ?>
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
              <thead>
                <tr>
                  <th style="text-align:right;padding:4px 10px;color:var(--text3);font-weight:600;border-bottom:1px solid var(--border);">שדה</th>
                  <th style="text-align:right;padding:4px 10px;color:#f87171;font-weight:600;border-bottom:1px solid var(--border);">לפני</th>
                  <th style="text-align:right;padding:4px 10px;color:#4ade80;font-weight:600;border-bottom:1px solid var(--border);">אחרי</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($diff as $field => $vals): ?>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:5px 10px;font-weight:600;color:var(--text2);"><?= View::e($field) ?></td>
                <td style="padding:5px 10px;color:#f87171;word-break:break-word;max-width:220px;"><?= View::e($vals['old'] ?? '') ?></td>
                <td style="padding:5px 10px;color:#4ade80;word-break:break-word;max-width:220px;"><?= View::e($vals['new'] ?? '') ?></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
          <?php if ($log['user_agent']): ?>
            <div style="margin-top:8px;font-size:11px;color:var(--text3);direction:ltr;text-align:left;">
              <?= View::e(mb_substr($log['user_agent'], 0, 100)) ?>
            </div>
          <?php endif; ?>
          <?php if ($log['request_url']): ?>
            <div style="margin-top:4px;font-size:11px;color:var(--text3);direction:ltr;text-align:left;">
              <?= View::e($log['request_method'] ?? '') ?> <?= View::e($log['request_url']) ?>
            </div>
          <?php endif; ?>
        </td>
      </tr>
      <?php endif; ?>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if (isset($pages) && $pages > 1): ?>
  <div style="display:flex;align-items:center;gap:6px;padding:12px 16px;border-top:1px solid var(--border);flex-wrap:wrap;">
    <?php
    $currentPage = $page ?? 1;
    $baseFilters = array_merge($filters ?? [], $showCron ? ['cron' => '1'] : []);
    $qs = http_build_query(array_merge($baseFilters, ['page' => 0]));
    for ($p = 1; $p <= min($pages, 20); $p++):
      $qp = str_replace('page=0', 'page='.$p, $qs);
    ?>
      <a href="<?= $base ?>/activity-log?<?= $qp ?>"
         style="display:inline-block;padding:4px 10px;border-radius:6px;font-size:13px;text-decoration:none;
                background:<?= $p===$currentPage?'var(--accent)':'var(--bg3)' ?>;
                color:<?= $p===$currentPage?'#fff':'var(--text2)' ?>;
                border:1px solid <?= $p===$currentPage?'var(--accent)':'var(--border)' ?>;">
        <?= $p ?>
      </a>
    <?php endfor; ?>
    <span style="font-size:12px;color:var(--text3);margin-right:auto;">
      עמוד <?= $currentPage ?> מתוך <?= $pages ?> (<?= number_format($total ?? 0) ?> רשומות)
    </span>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<style>
.log-srch{display:flex;align-items:center;gap:7px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0 10px;flex:1;min-width:200px;max-width:300px;}
.log-srch:focus-within{border-color:var(--accent);}
.log-srch i{color:var(--text3);font-size:13px;}
.log-srch input{background:none;border:none;outline:none;color:var(--text);font-family:var(--font);font-size:13px;padding:7px 0;width:100%;}
.log-sel{background:var(--bg3);border:1px solid var(--border);border-radius:7px;padding:7px 10px;color:var(--text);font-size:12px;font-family:var(--font);outline:none;}
.lth{padding:9px 12px;text-align:right;font-weight:600;font-size:11px;color:var(--text3);border-bottom:1px solid var(--border);white-space:nowrap;text-transform:uppercase;letter-spacing:.05em;}
.ltd{padding:10px 12px;vertical-align:top;}
.log-row:hover td{background:rgba(255,255,255,.025);}
</style>

<script>
function toggleLogDetail(id){
  const row=document.getElementById(id);
  const chev=document.getElementById('chev-'+id);
  if(!row)return;
  const open=row.style.display==='none'||row.style.display==='';
  row.style.display=open?'table-row':'none';
  if(chev)chev.style.transform=open?'rotate(180deg)':'rotate(0deg)';
}
</script>
