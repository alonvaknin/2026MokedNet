<?php
use Core\View;
$base = rtrim(CFG['app']['url'], '/');
$csrf = $_SESSION['csrf_token'] ?? '';

$groupMap   = [];
$categories = $categories ?? [];
$groups     = $groups ?? [];
foreach ($groups as $g) { $groupMap[$g['id']] = $g; }
?>

<style>
#pg-wrap{display:grid;grid-template-columns:240px 1fr;gap:18px;align-items:start;}
#pg-sidebar{position:sticky;top:72px;display:flex;flex-direction:column;gap:8px;}
.pg-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;}
.pg-card-hd{padding:10px 14px;font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid var(--border);background:var(--bg3);}
.grp-item{display:flex;align-items:center;gap:8px;padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .12s,color .12s;color:var(--text2);font-size:14px;font-weight:500;}
.grp-item:last-child{border-bottom:none;}
.grp-item:hover{background:var(--bg3);color:var(--text);}
.grp-item.active{background:rgba(91,141,238,.1);color:var(--accent);}
.grp-item.active .grp-dot{background:var(--accent);}
.grp-dot{width:8px;height:8px;border-radius:50%;background:var(--border2);flex-shrink:0;transition:background .12s;}
.grp-badge{margin-right:auto;font-size:11px;color:var(--text3);background:var(--bg4);border:1px solid var(--border);border-radius:10px;padding:1px 7px;}
#pg-main{display:flex;flex-direction:column;gap:14px;}
#pg-header{display:flex;align-items:center;gap:12px;padding:14px 18px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);}
#pg-title{font-size:18px;font-weight:700;color:var(--text);flex:1;}
#pg-subtitle{font-size:12px;color:var(--text3);}
#pg-dirty{display:none;align-items:center;gap:6px;font-size:12px;color:var(--warning);background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);border-radius:6px;padding:4px 12px;}
#pg-save{display:none;}
.cat-section{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;}
.cat-header{display:flex;align-items:center;gap:10px;padding:11px 16px;background:var(--bg3);border-bottom:1px solid var(--border);cursor:pointer;user-select:none;transition:background .12s;}
.cat-header:hover{background:var(--bg4);}
.cat-icon{font-size:16px;width:20px;text-align:center;flex-shrink:0;}
.cat-name{font-size:13px;font-weight:700;color:var(--text);flex:1;}
.cat-count{font-size:11px;color:var(--text3);background:var(--bg4);border:1px solid var(--border);border-radius:10px;padding:1px 8px;}
.cat-on-count{font-size:11px;font-weight:700;color:var(--accent);background:rgba(91,141,238,.1);border:1px solid rgba(91,141,238,.2);border-radius:10px;padding:1px 8px;display:none;}
.cat-toggle-all{font-size:11px;color:var(--text3);background:none;border:1px solid var(--border);border-radius:5px;padding:2px 9px;cursor:pointer;transition:all .12s;font-family:var(--font);}
.cat-toggle-all:hover{background:var(--accent-dim);color:var(--accent);border-color:rgba(91,141,238,.4);}
.cat-chevron{font-size:11px;color:var(--text3);transition:transform .2s;flex-shrink:0;}
.cat-body{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:8px;padding:12px 14px;}
.cat-section.collapsed .cat-body{display:none;}
.cat-section.collapsed .cat-chevron{transform:rotate(-90deg);}
/* perm-card — div (לא label!) כדי למנוע double-toggle */
.perm-card{display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:border-color .13s,background .13s;user-select:none;}
.perm-card:hover{border-color:var(--border2);}
.perm-card.on{border-color:var(--accent);background:rgba(91,141,238,.08);}
.perm-card input[type=checkbox]{accent-color:var(--accent);width:16px;height:16px;cursor:pointer;flex-shrink:0;pointer-events:none;}
.perm-label{font-size:13px;color:var(--text2);line-height:1.3;flex:1;}
.perm-card.on .perm-label{color:var(--text);}
#pg-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;gap:12px;color:var(--text3);background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);}
#pg-empty i{font-size:40px;opacity:.3;}
</style>

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
  <a href="<?= $base ?>/users" class="btn btn-ghost" style="padding:6px 10px;">← משתמשים</a>
  <div class="page-title" style="margin-bottom:0;">ניהול הרשאות קבוצות</div>
</div>

<div id="pg-wrap">
  <!-- Sidebar -->
  <div id="pg-sidebar">
    <div class="pg-card">
      <div class="pg-card-hd">קבוצות (<?= count($groups) ?>)</div>
      <?php foreach ($groups as $g): ?>
        <div class="grp-item" id="grp-<?= (int)$g['id'] ?>"
             data-id="<?= (int)$g['id'] ?>"
             onclick="pgSelectGroup(<?= (int)$g['id'] ?>)">
          <div class="grp-dot"></div>
          <span><?= View::e($g['name_heb']) ?></span>
          <?php if ((int)($g['user_count'] ?? 0) > 0): ?>
            <span class="grp-badge"><?= (int)$g['user_count'] ?></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Main panel -->
  <div id="pg-main">
    <div id="pg-header">
      <div>
        <div id="pg-title" style="color:var(--text3);">בחר קבוצה</div>
        <div id="pg-subtitle"></div>
      </div>
      <div id="pg-dirty"><i class="bi bi-circle-fill" style="font-size:7px;"></i> יש שינויים לא שמורים</div>
      <button class="btn btn-primary" id="pg-save" onclick="pgSave()">
        <i class="bi bi-floppy-fill"></i> שמור
      </button>
    </div>

    <div id="pg-cats" style="display:none;flex-direction:column;gap:14px;">
      <?php
      $catIcons = [
        'מרכזיה וטלפוניה' => 'bi-telephone-fill',
        'מעבדה'            => 'bi-eyedropper',
        'חנויות ולקוחות'   => 'bi-shop',
        'מוקד ושירות'      => 'bi-headset',
        'כספים'            => 'bi-cash-coin',
        'ניהול מערכת'      => 'bi-gear-fill',
      ];
      foreach ($categories as $catName => $perms):
        $catId = 'cat-' . preg_replace('/[^a-z0-9]/i', '-', $catName);
        $icon  = $catIcons[$catName] ?? 'bi-circle';
      ?>
      <div class="cat-section" id="<?= $catId ?>">
        <div class="cat-header" onclick="pgToggleCat('<?= $catId ?>')">
          <i class="bi <?= $icon ?> cat-icon" style="color:var(--accent);"></i>
          <span class="cat-name"><?= View::e($catName) ?></span>
          <span class="cat-on-count" id="<?= $catId ?>-on"></span>
          <span class="cat-count"><?= count($perms) ?> הרשאות</span>
          <button class="cat-toggle-all" id="<?= $catId ?>-btn"
                  onclick="event.stopPropagation();pgToggleCatAll('<?= $catId ?>')">הפעל הכל</button>
          <i class="bi bi-chevron-down cat-chevron"></i>
        </div>
        <div class="cat-body">
          <?php foreach ($perms as $key => $label): ?>
          <div class="perm-card" onclick="pgCardClick(this)">
            <input type="checkbox"
                   name="<?= View::e($key) ?>"
                   id="perm-<?= View::e($key) ?>">
            <span class="perm-label"><?= View::e($label) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div id="pg-empty">
      <i class="bi bi-shield-lock"></i>
      <div style="font-size:15px;font-weight:600;">בחר קבוצה לעריכת הרשאות</div>
      <div style="font-size:13px;">כל שינוי יחול על כל המשתמשים בקבוצה</div>
    </div>
  </div>
</div>

<script>
const PG_BASE   = '<?= $base ?>';
const PG_CSRF   = '<?= View::e($csrf) ?>';
const PG_GROUPS = <?= json_encode(array_values($groups), JSON_UNESCAPED_UNICODE) ?>;

let pgCurrentId = null;
let pgDirty     = false;

/* ── בחירת קבוצה ── */
function pgSelectGroup(id) {
  pgCurrentId = id;

  document.querySelectorAll('.grp-item').forEach(el =>
    el.classList.toggle('active', parseInt(el.dataset.id) === id)
  );

  const group = PG_GROUPS.find(g => g.id == id);
  document.getElementById('pg-title').textContent    = group?.name_heb || 'קבוצה ' + id;
  document.getElementById('pg-title').style.color    = 'var(--text)';
  document.getElementById('pg-subtitle').textContent =
    (group?.user_count > 0) ? group.user_count + ' משתמשים פעילים' : '';

  // מלא checkboxes לפי ערכי הקבוצה
  document.querySelectorAll('.perm-card input[type=checkbox]').forEach(cb => {
    const on = parseInt(group?.[cb.name] ?? 0) === 1;
    cb.checked = on;
    cb.closest('.perm-card').classList.toggle('on', on);
  });

  document.querySelectorAll('.cat-section').forEach(s => {
    pgUpdateCatCount(s.id);
    pgUpdateCatBtn(s.id);
  });

  document.getElementById('pg-cats').style.display  = 'flex';
  document.getElementById('pg-empty').style.display = 'none';
  document.getElementById('pg-save').style.display  = 'inline-flex';
  pgSetDirty(false);
}

/* ── Collapse category ── */
function pgToggleCat(catId) {
  document.getElementById(catId)?.classList.toggle('collapsed');
}

/* ── Toggle all in category ── */
function pgToggleCatAll(catId) {
  const section = document.getElementById(catId);
  const cbs     = [...section.querySelectorAll('input[type=checkbox]')];
  const allOn   = cbs.every(c => c.checked);
  cbs.forEach(cb => {
    cb.checked = !allOn;
    cb.closest('.perm-card').classList.toggle('on', !allOn);
  });
  pgUpdateCatCount(catId);
  pgUpdateCatBtn(catId);
  pgSetDirty(true);
}

/* ── Click on card — div, לא label, אז אנחנו מנהלים את ה-toggle ── */
function pgCardClick(card) {
  const cb = card.querySelector('input[type=checkbox]');
  cb.checked = !cb.checked;                          // ← toggle ידני (div, לא label!)
  card.classList.toggle('on', cb.checked);
  const section = card.closest('.cat-section');
  if (section) { pgUpdateCatCount(section.id); pgUpdateCatBtn(section.id); }
  pgSetDirty(true);
}

/* ── עדכן count badge ── */
function pgUpdateCatCount(catId) {
  const section = document.getElementById(catId);
  if (!section) return;
  const cbs  = section.querySelectorAll('input[type=checkbox]');
  const onN  = [...cbs].filter(c => c.checked).length;
  const badge = document.getElementById(catId + '-on');
  if (badge) {
    badge.textContent   = onN ? onN + '/' + cbs.length : '';
    badge.style.display = onN ? 'inline-block' : 'none';
  }
}

function pgUpdateCatBtn(catId) {
  const section = document.getElementById(catId);
  if (!section) return;
  const allOn = [...section.querySelectorAll('input[type=checkbox]')].every(c => c.checked);
  const btn   = document.getElementById(catId + '-btn');
  if (btn) btn.textContent = allOn ? 'כבה הכל' : 'הפעל הכל';
}

/* ── Dirty state ── */
function pgSetDirty(val) {
  pgDirty = val;
  document.getElementById('pg-dirty').style.display = val ? 'flex' : 'none';
  const btn = document.getElementById('pg-save');
  btn.style.background = val ? '' : 'var(--bg4)';
  btn.style.color      = val ? '' : 'var(--text2)';
}

/* ── Save ── */
async function pgSave() {
  if (!pgCurrentId) return;
  const btn = document.getElementById('pg-save');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> שומר...';

  // שולחים JSON כדי למנוע את בעיית PHP עם נקודות בשמות שדות
  // (PHP מחליף automation.viewAll → automation_viewAll ב-$_POST)
  const perms = {};
  document.querySelectorAll('.perm-card input[type=checkbox]').forEach(cb => {
    perms[cb.name] = cb.checked ? 1 : 0;
  });

  try {
    const res  = await fetch(PG_BASE + '/users/perm-groups/save', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': PG_CSRF,
      },
      body: JSON.stringify({ _csrf: PG_CSRF, permmisionsGroupID: pgCurrentId, perms }),
    });
    const data = await res.json();
    if (data.ok) {
      pgSetDirty(false);
      if (typeof v2Toast === 'function') v2Toast('✓ הרשאות נשמרו בהצלחה');
      const group = PG_GROUPS.find(g => g.id == pgCurrentId);
      if (group) {
        document.querySelectorAll('.perm-card input[type=checkbox]').forEach(cb => {
          group[cb.name] = cb.checked ? 1 : 0;
        });
      }
    } else {
      alert(data.error || 'שגיאה בשמירה');
    }
  } catch(e) {
    alert('שגיאת תקשורת');
  }

  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-floppy-fill"></i> שמור';
}

window.addEventListener('beforeunload', e => {
  if (pgDirty) { e.preventDefault(); e.returnValue = ''; }
});

document.addEventListener('keydown', e => {
  if ((e.ctrlKey || e.metaKey) && e.code === 'KeyS') {
    e.preventDefault();
    if (pgDirty) pgSave();
  }
});
</script>
