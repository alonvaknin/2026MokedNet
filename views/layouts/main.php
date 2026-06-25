<?php
use Core\Auth;
use Core\View;
$user      = Auth::user();
$appName   = CFG['app']['name'];
$base      = rtrim(CFG['app']['url'], '/');
$fullName  = $_SESSION['full_name'] ?? '';
$parts     = explode(' ', trim($fullName));
$content = $content ?? '';
$initials  = mb_substr($parts[0] ?? 'U', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '');
$deptName  = $user['dept_name']  ?? '';
$groupName = $user['group_name'] ?? '';
$canPbxSearch = \Core\Auth::can('pbxSearch');
$overdueCount = 0;
if (!empty($_SESSION['user_id'])) {
    $overdueCount = (int)\Core\DB::value(
        'SELECT COUNT(*) FROM tasks
         WHERE assigned_user_id=? AND is_active=1
           AND DATE_ADD(created_at, INTERVAL sla_days DAY) < NOW()',
        [$_SESSION['user_id']]
    );
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= View::e($appName) ?></title>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0' stop-color='%235b8dee'/%3E%3Cstop offset='1' stop-color='%237c5ce8'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='64' height='64' rx='14' fill='url(%23g)'/%3E%3Ctext x='32' y='46' font-family='Arial,sans-serif' font-size='36' font-weight='700' fill='white' text-anchor='middle'%3E%D7%9E%3C/text%3E%3C/svg%3E">
<script>window.__V2_BASE="<?= $base ?>";window.__OVERDUE_COUNT=<?= (int)$overdueCount ?>;</script>
<script src="<?= $base ?>/prefs-loader.js"></script>
<link rel="dns-prefetch" href="https://fonts.googleapis.com">
<link rel="dns-prefetch" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Assistant:wght@300;400;500;600;700&family=Heebo:wght@300;400;500;600;700&family=Rubik:wght@300;400;500;600;700&family=IBM+Plex+Sans:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<noscript><link href="https://fonts.googleapis.com/css2?family=Assistant:wght@300;400;500;600;700&family=Heebo:wght@300;400;500;600;700&family=Rubik:wght@300;400;500;600;700&family=IBM+Plex+Sans:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet"></noscript>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --sidebar-w:272px;--sidebar-mini:64px;--header-h:58px;
  --font:'Assistant',sans-serif;--num-font:'Assistant',sans-serif;
  --bg:#0d0f16;--bg2:#13161f;--bg3:#1a1e2b;--bg4:#212638;
  --border:rgba(255,255,255,.07);--border2:rgba(255,255,255,.14);
  --text:#e2e5f0;--text2:#7c829c;--text3:#4a5068;
  --accent:#5b8dee;--accent-dim:rgba(91,141,238,.15);--accent-hover:#4a7cdd;
  --success:#22c55e;--warning:#f59e0b;--danger:#ef4444;
  --radius:10px;--radius-sm:6px;--shadow:0 8px 32px rgba(0,0,0,.4);
}
html{font-size:15px}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden}
#sidebar{width:var(--sidebar-w);height:100vh;background:var(--bg2);border-left:1px solid var(--border);display:flex;flex-direction:column;position:fixed;right:0;top:0;z-index:200;transition:width .25s cubic-bezier(.4,0,.2,1);overflow:visible;font-family:var(--font)}
body.nav-collapsed #sidebar{width:var(--sidebar-mini)}
body.nav-collapsed #sidebar:hover{width:var(--sidebar-w);box-shadow:-10px 0 40px rgba(0,0,0,.6)}
body.nav-collapsed #sidebar:not(:hover) .nav-text,
body.nav-collapsed #sidebar:not(:hover) .nav-group-label,
body.nav-collapsed #sidebar:not(:hover) .nav-arrow,
body.nav-collapsed #sidebar:not(:hover) .sidebar-name,
body.nav-collapsed #sidebar:not(:hover) .sidebar-logo-text{opacity:0;width:0;pointer-events:none;overflow:hidden;white-space:nowrap}
body.nav-collapsed #sidebar:hover .nav-text,
body.nav-collapsed #sidebar:hover .nav-group-label,
body.nav-collapsed #sidebar:hover .nav-arrow,
body.nav-collapsed #sidebar:hover .sidebar-name,
body.nav-collapsed #sidebar:hover .sidebar-logo-text{opacity:1;width:auto;pointer-events:auto;white-space:nowrap}
.sidebar-brand{height:var(--header-h);display:flex;align-items:center;padding:0 14px 0 18px;gap:12px;border-bottom:1px solid var(--border);flex-shrink:0;text-decoration:none;overflow:visible;transition:background .15s;position:relative}
.sidebar-brand:hover{background:var(--bg3)}
.sidebar-logo{width:36px;height:36px;min-width:36px;background:linear-gradient(135deg,#5b8dee,#7c5ce8);border-radius:10px;display:grid;place-items:center;font-size:17px;font-weight:700;color:#fff;flex-shrink:0;box-shadow:0 4px 14px rgba(91,141,238,.35);transition:transform .2s,box-shadow .2s}
.sidebar-brand:hover .sidebar-logo{transform:scale(1.08);box-shadow:0 6px 20px rgba(91,141,238,.5)}
.sidebar-logo-text{font-size:16px;font-weight:700;color:var(--text);white-space:nowrap;letter-spacing:-.3px;transition:opacity .2s,width .25s}
#nav-toggle{position:absolute;left:-13px;top:50%;transform:translateY(-50%);width:26px;height:26px;background:var(--bg4);border:1px solid var(--border2);border-radius:50%;display:grid;place-items:center;cursor:pointer;color:var(--text2);font-size:13px;z-index:202;transition:background .15s,box-shadow .15s;flex-shrink:0}
#nav-toggle:hover{background:var(--accent-dim);color:var(--accent);box-shadow:0 0 0 4px rgba(91,141,238,.15)}
body.nav-collapsed #nav-toggle i{transform:rotate(180deg)}
.nav-scroll{flex:1;overflow-y:auto;overflow-x:hidden;padding:8px 8px 16px;scrollbar-width:thin;scrollbar-color:var(--border2) transparent}
.nav-scroll::-webkit-scrollbar{width:3px}
.nav-scroll::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}
.nav-group-label{font-size:10px;font-weight:700;letter-spacing:.14em;color:var(--text3);padding:18px 12px 6px;text-transform:uppercase;white-space:nowrap;transition:opacity .2s}
.nav-item{display:flex;align-items:center;gap:10px;padding:var(--nav-item-pad,9px 12px);border-radius:9px;color:var(--text2);text-decoration:none;font-size:15px;font-weight:700;cursor:pointer;white-space:nowrap;position:relative;min-height:42px;transition:background .15s,color .15s,transform .12s,box-shadow .15s}
.nav-item:hover{background:var(--bg3);color:var(--text);transform:translateX(-2px);box-shadow:2px 0 12px rgba(0,0,0,.15)}
.nav-item.active{background:var(--ni-bg,var(--accent-dim));color:var(--ni-color,var(--accent));box-shadow:inset 0 0 0 1px rgba(255,255,255,.06)}
.nav-item.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:65%;background:var(--ni-color,var(--accent));border-radius:0 3px 3px 0;box-shadow:0 0 8px var(--ni-color,var(--accent))}
.nav-icon{font-size:20px;min-width:26px;text-align:center;flex-shrink:0;color:var(--ni-color,var(--text3));transition:color .15s,transform .18s}
.nav-item:hover .nav-icon{transform:scale(1.15) rotate(-4deg)}
.nav-item.active .nav-icon{filter:drop-shadow(0 0 6px var(--ni-color,var(--accent)))}
.nav-text{transition:opacity .2s;flex:1;font-size:15px;font-weight:700}
.nav-arrow{font-size:11px;color:var(--text3);transition:transform .22s,opacity .2s;flex-shrink:0}
.nav-dropdown.open>.nav-item .nav-arrow{transform:rotate(90deg)}
.nav-sub{max-height:0;overflow:hidden;transition:max-height .28s cubic-bezier(.4,0,.2,1);padding-right:36px}
.nav-dropdown.open .nav-sub{max-height:600px}
.nav-sub .nav-item{font-size:14px;font-weight:500;padding:8px 10px;color:var(--text3);min-height:36px;border-radius:7px}
.nav-sub .nav-item::before{display:none}
.nav-sub .nav-item:hover{color:var(--text);background:var(--bg4);transform:translateX(-1px)}
.nav-sub .nav-item.active{color:var(--ni-color,var(--accent));background:var(--ni-bg,var(--accent-dim))}
.ni-home   {--ni-color:#5b8dee;--ni-bg:rgba(91,141,238,.12)}
.ni-store  {--ni-color:#10b981;--ni-bg:rgba(16,185,129,.12)}
.ni-task   {--ni-color:#f59e0b;--ni-bg:rgba(245,158,11,.12)}
.ni-lab    {--ni-color:#8b5cf6;--ni-bg:rgba(139,92,246,.12)}
.ni-support{--ni-color:#06b6d4;--ni-bg:rgba(6,182,212,.12)}
.ni-link   {--ni-color:#ec4899;--ni-bg:rgba(236,72,153,.12)}
.ni-admin  {--ni-color:#f97316;--ni-bg:rgba(249,115,22,.12)}
.nav-item.wip{opacity:.45;cursor:default}
.nav-item.wip:hover{background:none;color:var(--text2);transform:none;box-shadow:none}
body.nav-collapsed #sidebar:not(:hover) .nav-item[data-tip]{position:relative}
body.nav-collapsed #sidebar:not(:hover) .nav-item[data-tip]::after{content:attr(data-tip);position:absolute;left:calc(100% + 10px);top:50%;transform:translateY(-50%);background:var(--bg4);color:var(--text);font-size:13px;font-weight:600;padding:6px 12px;border-radius:8px;white-space:nowrap;opacity:0;pointer-events:none;border:1px solid var(--border2);box-shadow:var(--shadow);transition:opacity .15s .1s;z-index:300}
body.nav-collapsed #sidebar:not(:hover) .nav-item[data-tip]:hover::after{opacity:1}
.sidebar-footer{border-top:1px solid var(--border);padding:8px;flex-shrink:0}
.sidebar-user{display:flex;align-items:center;gap:10px;padding:9px 11px;border-radius:9px;cursor:pointer;transition:background .13s,transform .12s;text-decoration:none}
.sidebar-user:hover{background:var(--bg3);transform:translateY(-1px)}
.sidebar-avatar-sm{width:34px;height:34px;min-width:34px;border-radius:50%;display:grid;place-items:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;transition:transform .18s,box-shadow .18s}
.sidebar-user:hover .sidebar-avatar-sm{transform:scale(1.08);box-shadow:0 4px 12px rgba(0,0,0,.3)}
.sidebar-name{flex:1;overflow:hidden;transition:opacity .2s,width .25s}
.sidebar-name-text{font-size:14px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sidebar-name-sub{font-size:11px;color:var(--text3);white-space:nowrap}
#main{flex:1;margin-right:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column;transition:margin-right .25s cubic-bezier(.4,0,.2,1)}
body.nav-collapsed #main{margin-right:var(--sidebar-mini)}
#topbar{height:var(--header-h);background:var(--bg2);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 22px;gap:10px;position:sticky;top:0;z-index:100;font-family:var(--font)}
.topbar-search{display:flex;align-items:center;gap:8px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0 12px;max-width:300px;flex:1;transition:border-color .15s,box-shadow .15s}
.topbar-search:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px rgba(91,141,238,.12)}
.topbar-search i{color:var(--text3);font-size:14px;flex-shrink:0}
.topbar-search input{background:none;border:none;outline:none;color:var(--text);font-family:var(--font);font-size:13px;padding:8px 0;width:100%}
.topbar-search input::placeholder{color:var(--text3)}
.topbar-search kbd{font-size:10px;color:var(--text3);background:var(--bg4);border:1px solid var(--border2);border-radius:4px;padding:1px 5px;font-family:var(--font)}
.topbar-spacer{flex:1}
#page-title{font-size:13px;color:var(--text3);display:flex;align-items:center;gap:6px}
#page-title .crumb-current{color:var(--text2);font-weight:600}
.topbar-icon-btn{width:36px;height:36px;background:none;border:1px solid var(--border);border-radius:8px;display:grid;place-items:center;cursor:pointer;color:var(--text2);font-size:17px;transition:all .15s;flex-shrink:0}
.topbar-icon-btn:hover{background:var(--accent-dim);color:var(--accent);border-color:var(--accent)}
.topbar-avatar-btn{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;font-size:12px;font-weight:700;color:#fff;cursor:pointer;border:2px solid var(--border2);transition:border-color .15s,transform .15s,box-shadow .15s;position:relative;flex-shrink:0}
.topbar-avatar-btn:hover{border-color:var(--accent);transform:scale(1.08);box-shadow:0 0 0 4px rgba(91,141,238,.15)}
#user-dropdown{position:absolute;top:calc(100% + 10px);left:0;background:var(--bg2);border:1px solid var(--border2);border-radius:var(--radius);width:240px;box-shadow:var(--shadow);z-index:200;overflow:hidden;opacity:0;transform:translateY(-6px) scale(.97);pointer-events:none;transition:opacity .18s,transform .18s}
#user-dropdown.open{opacity:1;transform:translateY(0) scale(1);pointer-events:auto}
.ud-header{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.ud-avatar{width:40px;height:40px;border-radius:50%;display:grid;place-items:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0}
.ud-name{font-size:13px;font-weight:600;color:var(--text)}
.ud-role{font-size:11px;color:var(--text3);margin-top:2px}
.ud-item{display:flex;align-items:center;gap:10px;padding:10px 16px;font-size:13px;color:var(--text2);text-decoration:none;cursor:pointer;transition:background .13s,color .13s,padding-right .13s}
.ud-item:hover{background:var(--bg3);color:var(--text);padding-right:20px}
.ud-item i{font-size:16px;width:18px;text-align:center;transition:transform .15s}
.ud-item:hover i{transform:scale(1.15)}
.ud-sep{height:1px;background:var(--border);margin:4px 0}
#mobile-toggle{display:none;width:34px;height:34px;background:none;border:none;color:var(--text2);font-size:20px;cursor:pointer;border-radius:var(--radius-sm);align-items:center;justify-content:center;transition:background .13s,color .13s}
#mobile-toggle:hover{background:var(--bg3);color:var(--text)}
#nav-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:199}
@media(max-width:768px){
  #sidebar{right:calc(-1 * var(--sidebar-w));transition:right .25s cubic-bezier(.4,0,.2,1),width .22s;width:var(--sidebar-w)!important}
  body.nav-open #sidebar{right:0}
  body.nav-open #nav-overlay{display:block}
  #main{margin-right:0!important}
  #mobile-toggle{display:flex}
  #nav-toggle{display:none}
  #content{padding:16px}
}
#content{flex:1;padding:24px 26px}
.page-title{font-size:21px;font-weight:700;margin-bottom:20px;color:var(--text)}
.card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:20px;transition:border-color .15s,box-shadow .15s}
.card:hover{border-color:var(--border2)}
.card-header{font-size:12px;font-weight:700;color:var(--text2);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border);text-transform:uppercase;letter-spacing:.06em}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--radius-sm);font-size:14px;font-family:var(--font);font-weight:600;border:none;cursor:pointer;text-decoration:none;transition:background .13s,transform .1s,box-shadow .13s}
.btn:active{transform:scale(.96)}
.btn:hover{transform:translateY(-1px)}
.btn-primary{background:var(--accent);color:#fff;box-shadow:0 2px 8px rgba(91,141,238,.25)}
.btn-primary:hover{background:var(--accent-hover);box-shadow:0 4px 16px rgba(91,141,238,.4)}
.btn-ghost{background:var(--bg3);color:var(--text2);border:1px solid var(--border)}
.btn-ghost:hover{color:var(--text);border-color:var(--border2);background:var(--bg4)}
.btn-danger{background:var(--danger);color:#fff}
.badge{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600}
.badge-success{background:rgba(34,197,94,.13);color:#22c55e}
.badge-warning{background:rgba(245,158,11,.14);color:#f59e0b}
.badge-danger{background:rgba(239,68,68,.13);color:#ef4444}
.badge-info{background:var(--accent-dim);color:var(--accent)}
.badge-purple{background:rgba(139,92,246,.13);color:#8b5cf6}
.alert{padding:12px 16px;border-radius:var(--radius-sm);font-size:14px;margin-bottom:16px;display:flex;align-items:center;gap:10px}
.alert-info{background:var(--accent-dim);border:1px solid rgba(91,141,238,.25);color:#8bb0f5}
.alert-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#4ade80}
.alert-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171}
.no-anim *,.no-anim *::before,.no-anim *::after{transition:none!important;animation:none!important}
.glass-mode .card{background:rgba(255,255,255,.05)!important;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}
.glass-mode #sidebar,.glass-mode #topbar{background:var(--glass-bg,rgba(13,15,22,.85))!important;backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px)}
#sidebar .nav-item{padding:var(--nav-item-pad,9px 12px)}
#v2-toast{position:fixed;bottom:26px;left:50%;transform:translateX(-50%) translateY(10px);background:var(--bg4);border:1px solid var(--border2);color:var(--text);padding:10px 22px;border-radius:8px;font-size:14px;z-index:9999;box-shadow:var(--shadow);pointer-events:none;opacity:0;transition:opacity .2s,transform .2s}
#v2-toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
/* Phone & email links — plain copyable text, Alt+click to call/mail */
a[href^="tel:"],a[href^="mailto:"]{color:inherit;text-decoration:none;cursor:copy;border-bottom:1px dotted transparent;transition:border-color .15s,opacity .15s}
a[href^="tel:"]:hover,a[href^="mailto:"]:hover{border-bottom-color:currentColor;opacity:.8}
a[href^="tel:"][data-copy-hint]::after,a[href^="mailto:"][data-copy-hint]::after{content:' ⎘';font-size:.75em;opacity:.45}
.nav-sla-badge{display:inline-flex;align-items:center;justify-content:center;
  min-width:18px;height:18px;padding:0 5px;background:var(--danger);color:#fff;
  font-size:10px;font-weight:700;border-radius:9px;margin-right:auto;flex-shrink:0;
  animation:sla-pulse 2s ease-in-out infinite;}
@keyframes sla-pulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(239,68,68,.4)}
  50%{opacity:.85;box-shadow:0 0 0 5px rgba(239,68,68,0)}}
</style>
</head>
<body>

<div id="nav-overlay" onclick="closeMobileNav()"></div>

<aside id="sidebar">
  <a href="<?= $base ?>/dashboard" class="sidebar-brand">
    <div id="nav-toggle" onclick="event.preventDefault();event.stopPropagation();toggleCollapse()" title="כווץ/פתח">
      <i class="bi bi-chevron-right"></i>
    </div>
    <div class="sidebar-logo">מ</div>
    <span class="sidebar-logo-text"><?= View::e($appName) ?></span>
  </a>
  <nav class="nav-scroll" id="nav-menu">
    <div style="padding:14px 12px;color:var(--text3);font-size:13px;">טוען...</div>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= $base ?>/preferences" class="sidebar-user" data-tip="העדפות">
      <div class="sidebar-avatar-sm" style="background:linear-gradient(135deg,var(--accent),#c084fc)">
        <?= View::e($initials) ?>
      </div>
      <div class="sidebar-name">
        <div class="sidebar-name-text"><?= View::e($fullName) ?></div>
        <div class="sidebar-name-sub"><?= View::e($groupName) ?></div>
      </div>
      <!-- <i class="bi bi-sliders" style="color:var(--text3);font-size:14px;flex-shrink:0;"></i> -->
    </a>
  </div>
</aside>

<div id="main">
  <header id="topbar">
    <button id="mobile-toggle" onclick="openMobileNav()"><i class="bi bi-list"></i></button>
    <div class="topbar-search">
      <i class="bi bi-search"></i>
      <input type="search" id="global-search" placeholder="חיפוש גלובאלי..." autocomplete="off">
      <kbd>⌘K</kbd>
    </div>
    <div class="topbar-spacer"></div>
    <div id="page-title"><span class="crumb-current" id="crumb-text">דשבורד</span></div>
    <div style="position:relative;">
      <div class="topbar-avatar-btn"
           style="background:linear-gradient(135deg,var(--accent),#c084fc)"
           id="topbar-av"
           onclick="toggleUserMenu()"
           title="<?= View::e($fullName) ?>">
        <?= View::e($initials) ?>
      </div>
      <div id="user-dropdown">
        <div class="ud-header">
          <div class="ud-avatar" style="background:linear-gradient(135deg,var(--accent),#c084fc)"><?= View::e($initials) ?></div>
          <div>
            <div class="ud-name"><?= View::e($fullName) ?></div>
            <div class="ud-role"><?= View::e($deptName) ?><?= $deptName&&$groupName?' · ':'' ?><?= View::e($groupName) ?></div>
          </div>
        </div>
        <a href="<?= $base ?>/preferences" class="ud-item" onclick="closeUserMenu()">
          <i class="bi bi-palette-fill" style="color:var(--accent)"></i> העדפות תצוגה
        </a>
        <a href="<?= $base ?>/users/<?= (int)($_SESSION['user_id']??0) ?>" class="ud-item" onclick="closeUserMenu()">
          <i class="bi bi-person-fill" style="color:#10b981"></i> הפרופיל שלי
        </a>
        <div class="ud-sep"></div>
        <a href="<?= $base ?>/logout" class="ud-item" style="color:var(--danger)">
          <i class="bi bi-box-arrow-left"></i> התנתקות
        </a>
      </div>
    </div>
  </header>
  <main id="content"><?= $content ?></main>
</div>

<!-- Wizenet Search Modal -->
<div id="wiz-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:flex-start;justify-content:center;padding:40px 16px 16px;">
  <div id="wiz-modal-inner" style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:var(--wiz-w,860px);max-height:92vh;display:flex;flex-direction:column;transition:max-width .2s;">
    <!-- STICKY SEARCH AREA -->
    <div style="position:sticky;top:0;background:var(--bg2);z-index:2;border-radius:var(--radius) var(--radius) 0 0;border-bottom:1px solid var(--border);">
      <!-- title row -->
      <div style="display:flex;align-items:center;gap:12px;padding:14px 18px 10px;">
        <i class="bi bi-telephone-fill" style="color:var(--accent);font-size:17px;flex-shrink:0;"></i>
        <div style="flex:1;">
          <div style="font-size:14px;font-weight:700;">חיפוש קריאות וייזנט</div>
          <div style="font-size:10px;color:var(--text3);"># קריאה · 📱 טלפון · 📦 IMEI · 🏢 לקוח · מכמה במכה: 301111, 302222</div>
        </div>
        <!-- resize buttons -->
        <div style="display:flex;gap:4px;flex-shrink:0;">
          <button onclick="wizResize('sm')" id="wiz-sz-sm" title="רגיל" style="background:var(--bg4);border:1px solid var(--border);border-radius:4px;padding:2px 7px;cursor:pointer;color:var(--text3);font-size:10px;">S</button>
          <button onclick="wizResize('md')" id="wiz-sz-md" title="רחב" style="background:var(--bg4);border:1px solid var(--border);border-radius:4px;padding:2px 7px;cursor:pointer;color:var(--text3);font-size:10px;">M</button>
          <button onclick="wizResize('lg')" id="wiz-sz-lg" title="מלא" style="background:var(--bg4);border:1px solid var(--border);border-radius:4px;padding:2px 7px;cursor:pointer;color:var(--text3);font-size:10px;">L</button>
        </div>
        <button onclick="closeWizModal()" style="background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;line-height:1;flex-shrink:0;">✕</button>
      </div>
      <!-- search input + phone button on same row -->
      <div style="padding:0 18px 10px;display:flex;gap:7px;align-items:center;">
        <div style="flex:1;display:flex;align-items:center;gap:7px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0 11px;transition:border-color .15s;" id="wiz-input-wrap">
          <i class="bi bi-search" style="color:var(--text3);font-size:13px;flex-shrink:0;"></i>
          <input type="text" id="wiz-input" placeholder="301111 או 301111, 302222..."
                 style="background:none;border:none;outline:none;color:var(--text);font-family:var(--font);font-size:14px;padding:8px 0;width:100%;"
                 onkeydown="if(event.key==='Enter')doWizSearch()">
          <span id="wiz-type-hint" style="font-size:10px;color:var(--text3);white-space:nowrap;flex-shrink:0;padding:2px 6px;border-radius:8px;background:var(--bg4);"></span>
        </div>
        <button class="btn btn-primary" onclick="doWizSearch()" id="wiz-search-btn" style="padding:8px 14px;">
          <i class="bi bi-search"></i> חפש
        </button>
      </div>
      <!-- phone search + dates on same row -->
      <div style="padding:0 18px 10px;display:flex;gap:7px;align-items:center;flex-wrap:wrap;">
        <button id="wiz-phone-quick" style="display:none;align-items:center;gap:5px;font-size:12px;background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);border-radius:6px;padding:4px 10px;cursor:pointer;color:#10b981;font-family:var(--font);" onclick="wizPhoneSearch()">
          <i class="bi bi-telephone-fill"></i> <span id="wiz-phone-quick-num"></span>
        </button>
        <div style="display:flex;align-items:center;gap:5px;margin-right:auto;">
          <span style="font-size:11px;color:var(--text3);flex-shrink:0;"><i class="bi bi-calendar3"></i></span>
          <div style="display:flex;align-items:center;gap:4px;background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:2px 7px;">
            <label style="font-size:11px;color:var(--text3);">מ-</label>
            <input type="date" id="wiz-from" style="background:none;border:none;outline:none;color:var(--text);font-size:11px;font-family:var(--font);">
          </div>
          <div style="display:flex;align-items:center;gap:4px;background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:2px 7px;">
            <label style="font-size:11px;color:var(--text3);">עד-</label>
            <input type="date" id="wiz-to" style="background:none;border:none;outline:none;color:var(--text);font-size:11px;font-family:var(--font);">
          </div>
          <button class="btn btn-ghost" style="padding:3px 8px;font-size:11px;" onclick="resetWizDates()" title="ברירת מחדל">
            <i class="bi bi-arrow-counterclockwise"></i>
          </button>
          <span id="wiz-date-label" style="font-size:10px;color:var(--text3);"></span>
        </div>
      </div>
      <!-- history toggle button + collapse -->
      <div style="padding:0 18px 8px;display:flex;align-items:center;gap:6px;">
        <button onclick="toggleWizHistory()" id="wiz-hist-btn"
          style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text3);background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:3px 10px;cursor:pointer;font-family:var(--font);transition:all .13s;">
          <i class="bi bi-clock-history" style="font-size:12px;"></i>
          <span id="wiz-hist-label">היסטורית חיפושים</span>
          <span id="wiz-hist-count" style="background:var(--bg4);border-radius:10px;padding:0 5px;font-size:10px;"></span>
          <i class="bi bi-chevron-down" id="wiz-hist-chevron" style="font-size:10px;transition:transform .3s;"></i>
        </button>
      </div>
      <div id="wiz-history-wrap" style="overflow:hidden;max-height:0;transition:max-height .4s ease;">
        <div id="wiz-history" style="padding:10px 18px 12px;border-top:1px solid var(--border);"></div>
      </div>
    </div>
    <!-- SCROLLABLE RESULTS -->
    <div id="wiz-results" style="padding:14px 18px;overflow-y:auto;flex:1;min-height:60px;"></div>
  </div>
</div>

<!-- Global Contact View Modal -->
<div id="gcv-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:650;align-items:center;justify-content:center;padding:20px;">
  <div style="background:var(--bg2);border:1px solid var(--border2);border-radius:var(--radius);width:100%;max-width:440px;max-height:88vh;overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,.6);">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg2);z-index:1;">
      <div id="gcv-title" style="font-size:15px;font-weight:700;display:flex;align-items:center;gap:10px;"></div>
      <div style="display:flex;gap:8px;align-items:center;">
        <a id="gcv-page-link" href="#" style="font-size:11px;color:var(--text3);text-decoration:none;padding:4px 8px;background:var(--bg4);border:1px solid var(--border);border-radius:5px;">
          <i class="bi bi-box-arrow-up-left"></i> פתח בדף
        </a>
        <button onclick="closeGcv()" style="background:none;border:none;color:var(--text2);font-size:20px;cursor:pointer;">✕</button>
      </div>
    </div>
    <div id="gcv-body" style="padding:18px;"></div>
  </div>
</div>

<!-- Global Search Modal -->
<div id="gs-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.72);z-index:600;align-items:center;justify-content:center;padding:16px;">
  <div style="background:var(--bg2);border:1px solid var(--border2);border-radius:var(--radius);width:100%;max-width:620px;box-shadow:0 24px 80px rgba(0,0,0,.65);overflow:hidden;">
    <!-- Input row -->
    <div style="display:flex;align-items:center;gap:10px;padding:13px 16px;border-bottom:1px solid var(--border);">
      <i class="bi bi-search" style="color:var(--text3);font-size:15px;flex-shrink:0;"></i>
      <input type="search" id="gs-input" autocomplete="off" spellcheck="false"
             placeholder="חיפוש..."
             style="flex:1;background:none;border:none;outline:none;color:var(--text);font-family:var(--font);font-size:15px;direction:rtl;">
      <kbd style="font-size:10px;color:var(--text3);background:var(--bg4);border:1px solid var(--border2);border-radius:4px;padding:2px 7px;font-family:var(--font);flex-shrink:0;">Esc</kbd>
    </div>
    <!-- Scope pills -->
    <div style="display:flex;gap:6px;padding:9px 14px;border-bottom:1px solid var(--border);flex-wrap:wrap;background:var(--bg3);">
      <button class="gs-scope gs-scope-active" data-scope="stores"   onclick="gsSetScope('stores')"><i class="bi bi-shop"></i> חנויות</button>
      <button class="gs-scope"                 data-scope="calls"    onclick="gsSetScope('calls')"><i class="bi bi-headset"></i> קריאות שירות</button>
      <button class="gs-scope"                 data-scope="contacts" onclick="gsSetScope('contacts')"><i class="bi bi-people-fill"></i> אנשי קשר</button>
      <button class="gs-scope"                 data-scope="products" onclick="gsSetScope('products')"><i class="bi bi-box-seam"></i> מוצרים</button>
      <?php if (!empty($canPbxSearch)): ?><button class="gs-scope" data-scope="pbx" onclick="gsSetScope('pbx')"><i class="bi bi-telephone-outbound"></i> שיחות מרכזיה</button><?php else: ?><button class="gs-scope gs-scope-soon" data-scope="pbx" disabled title="ללא הרשאה"><i class="bi bi-telephone-outbound"></i> שיחות מרכזיה</button><?php endif; ?>
    </div>
    <!-- Results -->
    <div id="gs-results" style="max-height:58vh;overflow-y:auto;min-height:72px;"></div>
  </div>
</div>
<style>
.gs-scope{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:20px;font-size:12px;font-weight:600;font-family:var(--font);border:1px solid var(--border);background:var(--bg4);color:var(--text2);cursor:pointer;transition:all .13s}
.gs-scope:hover:not(:disabled){background:var(--accent-dim);color:var(--accent);border-color:rgba(91,141,238,.4)}
.gs-scope.gs-scope-active{background:var(--accent-dim);color:var(--accent);border-color:rgba(91,141,238,.4)}
.gs-scope.gs-scope-soon{opacity:.45;cursor:default}
.gs-row{display:flex;align-items:center;gap:12px;padding:10px 16px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .12s}
.gs-row:last-child{border-bottom:none}
.gs-row:hover{background:var(--bg3)}
.gs-empty{text-align:center;padding:36px 20px;color:var(--text3)}
.gs-empty i{font-size:30px;display:block;margin-bottom:10px;opacity:.35}
</style>

<div id="v2-toast"></div>

<script>
const BASE='<?= $base ?>';
function toggleCollapse(){const c=document.body.classList.toggle('nav-collapsed');localStorage.setItem('nav_collapsed',c?'1':'0');}
if(localStorage.getItem('nav_collapsed')==='1')document.body.classList.add('nav-collapsed');
function openMobileNav(){document.body.classList.add('nav-open');}
function closeMobileNav(){document.body.classList.remove('nav-open');}
function v2Toast(msg){const t=document.getElementById('v2-toast');t.textContent=msg;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2500);}
function toggleUserMenu(){document.getElementById('user-dropdown').classList.toggle('open');}
function closeUserMenu(){document.getElementById('user-dropdown').classList.remove('open');}
document.addEventListener('click',e=>{
  const dd=document.getElementById('user-dropdown');const btn=document.getElementById('topbar-av');
  if(dd&&!dd.contains(e.target)&&btn&&!btn.contains(e.target))closeUserMenu();
});

/* ── Wizenet History ──────────────────────────────────── */
const WIZ_KEY='v2_wiz_hist', WIZ_TTL=4*24*3600*1000;

function wizHistLoad(){
  try{
    const all=JSON.parse(localStorage.getItem(WIZ_KEY)||'[]');
    const now=Date.now();
    return all.filter(h=>(now-h.ts)<WIZ_TTL);
  }catch(e){return[];}
}
function wizHistSave(arr){try{localStorage.setItem(WIZ_KEY,JSON.stringify(arr));}catch(e){}}

function wizHistAdd(entry){
  let h=wizHistLoad().filter(x=>!(x.q===entry.q&&x.type===entry.type));
  h.unshift({...entry,ts:Date.now()});
  wizHistSave(h.slice(0,30));
  renderWizHistory();
}
function wizHistRemove(q,type){
  wizHistSave(wizHistLoad().filter(h=>!(h.q===q&&h.type===type)));
  renderWizHistory();
}
function buildHistSub(d){
  const parts=[];
  if(d.contactName&&d.contactName.trim())parts.push(d.contactName.trim());
  else if(d.branch&&d.branch.trim())parts.push(d.branch.trim());
  if(d.contactCell&&d.contactCell.trim())parts.push(d.contactCell.trim());
  else if(d.companyPhone&&d.companyPhone.trim())parts.push(d.companyPhone.trim());
  if(d.callId&&!parts.length)parts.push('#'+d.callId);
  return parts.join(' · ');
}
function detectSearchType(q){
  const n=q.replace(/\D/g,'');
  if(/^\d{3,6}$/.test(q))return'call';
  if(/^\d+$/.test(q)&&n.length>=9&&n.length<=11)return'phone';
  if(/^\d+$/.test(q)&&n.length>=14)return'imei';
  return'company';
}
function toggleWizHistory(){
  const wrap=document.getElementById('wiz-history-wrap');
  const chevron=document.getElementById('wiz-hist-chevron');
  const btn=document.getElementById('wiz-hist-btn');
  const isOpen=wrap.style.maxHeight&&wrap.style.maxHeight!=='0px'&&wrap.style.maxHeight!=='0';
  wrap.style.transition='max-height .4s ease';
  wrap.style.maxHeight=isOpen?'0':'300px';
  if(chevron)chevron.style.transform=isOpen?'':'rotate(180deg)';
  if(btn)btn.style.color=isOpen?'var(--text3)':'var(--accent)';
  if(btn)btn.style.borderColor=isOpen?'var(--border)':'rgba(91,141,238,.3)';
}
function renderWizHistory(){
  const el=document.getElementById('wiz-history');
  if(!el)return;
  const hist=wizHistLoad();
  if(!hist.length){
    const wrap=document.getElementById('wiz-history-wrap');
    if(wrap)wrap.style.maxHeight='0';
    return;
  }
  // Update count badge on button
  const countBadge=document.getElementById('wiz-hist-count');
  if(countBadge)countBadge.textContent=hist.length;
  const curQ=document.getElementById('wiz-input').value.trim();
  const typeIcon={call:'#',phone:'📱',imei:'📦',company:'🏢'};
  let html='<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:7px;">'
    +'<span style="font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.07em;">חיפושים אחרונים</span>'
    +'<button onclick="toggleWizHistory()" style="background:none;border:none;cursor:pointer;color:var(--text3);font-size:12px;padding:0;">✕</button>'
    +'</div>';
  html+='<div style="display:flex;flex-wrap:wrap;gap:6px;padding-bottom:10px;">';
  hist.forEach(h=>{
    const isCur=h.q===curQ;
    const age=Math.floor((Date.now()-h.ts)/3600000);
    const ageStr=age<1?'עכשיו':age<24?age+'ש':Math.floor(age/24)+'י';
    const bg=isCur?'var(--accent-dim)':'var(--bg3)';
    const border=isCur?'var(--accent)':'var(--border)';
    const qColor=isCur?'var(--accent)':'var(--text)';
    const qWeight=isCur?'700':'500';
    html+='<div style="display:flex;align-items:stretch;border:1px solid '+border+';border-radius:8px;background:'+bg+';overflow:hidden;max-width:220px;">';
    html+='<button onclick="wizRunSearch(\''+h.q.replace(/'/g,"\\'")+'\')" '
      +'style="background:none;border:none;padding:6px 9px;cursor:pointer;text-align:right;font-family:var(--font);flex:1;min-width:0;">';
    html+='<div style="display:flex;align-items:center;gap:5px;">';
    html+='<span style="font-size:11px;flex-shrink:0;">'+( typeIcon[h.type]||'')+'</span>';
    html+='<span style="font-size:12px;font-weight:'+qWeight+';color:'+qColor+';white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:120px;">'+esc(h.q)+'</span>';
    html+='<span style="font-size:10px;color:var(--text3);flex-shrink:0;margin-right:auto;">'+ageStr+'</span>';
    html+='</div>';
    if(h.sub)html+='<div style="font-size:11px;color:var(--text2);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'+esc(h.sub)+'</div>';
    html+='</button>';
    html+='<button onclick="wizHistRemove(\''+h.q.replace(/'/g,"\\'")+'\',\''+h.type+'\')" '
      +'style="background:none;border:none;border-right:1px solid var(--border);padding:0 8px;cursor:pointer;color:var(--text3);font-size:12px;flex-shrink:0;" title="הסר">✕</button>';
    html+='</div>';
  });
  html+='</div>';
  el.innerHTML=html;
}
function wizRunSearch(q){
  document.getElementById('wiz-input').value=q;
  // trigger hint
  const v=q.replace(/\D/g,'');
  const hint=document.getElementById('wiz-type-hint');
  const t=detectSearchType(q);
  hint.textContent={call:'# קריאה',phone:'📱 טלפון',imei:'📦 IMEI',company:'🏢 לקוח'}[t]||'';
  doWizSearch();
}

/* ── Wizenet Modal ─────────────────────────────────────── */
function _wizDefaultDates(){
  const now=new Date(),past=new Date();past.setFullYear(past.getFullYear()-2);
  const fmt=d=>d.toISOString().split('T')[0];
  document.getElementById('wiz-from').value=fmt(past);
  document.getElementById('wiz-to').value=fmt(now);
  updateWizDateLabel();
}
function resetWizDates(){_wizDefaultDates();}
function updateWizDateLabel(){
  const f=document.getElementById('wiz-from').value,t=document.getElementById('wiz-to').value;
  if(f&&t){const days=Math.round((new Date(t)-new Date(f))/86400000);document.getElementById('wiz-date-label').textContent='('+days+' ימים)';}
}
document.addEventListener('change',e=>{if(e.target.id==='wiz-from'||e.target.id==='wiz-to')updateWizDateLabel();});
function openWizModal(){
  document.getElementById('wiz-modal').style.display='flex';
  wizInitSize();
  document.getElementById('wiz-results').innerHTML='';
  if(!document.getElementById('wiz-from').value)_wizDefaultDates();
  renderWizHistory();
  // Auto-open history for 2s then collapse with animation
  const hist=wizHistLoad();
  const chevronEl=document.getElementById('wiz-hist-chevron');
  const btnEl=document.getElementById('wiz-hist-btn');
  if(hist.length){
    const wrap=document.getElementById('wiz-history-wrap');
    wrap.style.transition='max-height .4s ease';
    wrap.style.maxHeight='300px';
    if(chevronEl)chevronEl.style.transform='rotate(180deg)';
    if(btnEl){btnEl.style.color='var(--accent)';btnEl.style.borderColor='rgba(91,141,238,.3)';}
    setTimeout(()=>{
      wrap.style.transition='max-height .5s ease';
      wrap.style.maxHeight='0';
      if(chevronEl)chevronEl.style.transform='';
      if(btnEl){btnEl.style.color='var(--text3)';btnEl.style.borderColor='var(--border)';}
    },2000);
  }
  setTimeout(()=>document.getElementById('wiz-input').focus(),50);
}
function closeWizModal(){document.getElementById('wiz-modal').style.display='none';}
document.getElementById('wiz-modal').addEventListener('click',e=>{if(e.target===document.getElementById('wiz-modal'))closeWizModal();});
document.getElementById('wiz-input').addEventListener('input',function(){
  const v=this.value.replace(/\D/g,'');
  const hint=document.getElementById('wiz-type-hint');
  if(!this.value){hint.textContent='';renderWizHistory();return;}
  if(/^\d+$/.test(this.value)){
    if(v.length<=6)hint.textContent='# קריאה';
    else if(v.length>=9&&v.length<=11)hint.textContent='📱 טלפון';
    else if(v.length>=14)hint.textContent='📦 IMEI';
    else hint.textContent=v.length+' ספרות';
  }else{hint.textContent='🏢 לקוח';}
});

// Last found phone for quick phone-search button
let _lastFoundPhone='';
function wizPhoneSearch(){
  if(_lastFoundPhone){
    document.getElementById('wiz-input').value=_lastFoundPhone;
    doWizSearch();
  }
}
function showPhoneQuick(phone){
  _lastFoundPhone=phone;
  const btn=document.getElementById('wiz-phone-quick');
  const num=document.getElementById('wiz-phone-quick-num');
  if(btn&&num&&phone){
    num.textContent=phone;
    btn.style.display='flex';
    btn.style.opacity='0';btn.style.transform='translateY(-6px)';
    requestAnimationFrame(()=>{
      btn.style.transition='opacity .25s,transform .25s';
      btn.style.opacity='1';btn.style.transform='translateY(0)';
    });
  }
}
function hidePhoneQuick(){
  _lastFoundPhone='';
  const btn=document.getElementById('wiz-phone-quick');
  if(btn)btn.style.display='none';
}

async function doWizSearch(){
  const raw=document.getElementById('wiz-input').value.trim();
  if(!raw)return;
  const from=document.getElementById('wiz-from').value;
  const to=document.getElementById('wiz-to').value;
  const fmtDate=s=>s?s.split('-').reverse().join('/'):'';
  const resultsEl=document.getElementById('wiz-results');
  const btn=document.getElementById('wiz-search-btn');
  hidePhoneQuick();

  // Multi-query: split by comma, max 10
  const queries=[...new Set(raw.split(',').map(s=>s.trim()).filter(Boolean))].slice(0,10);
  const isMulti=queries.length>1;

  resultsEl.innerHTML='<div style="text-align:center;padding:28px;color:var(--text3);"><i class="bi bi-hourglass-split" style="font-size:28px;display:block;margin-bottom:10px;animation:wiz-spin 1s linear infinite;"></i>מחפש'+(isMulti?' '+queries.length+' קריאות...':'...')+'</div>';
  btn.disabled=true;

  try{
    if(isMulti){
      // Parallel fetch for all queries (callid only)
      resultsEl.innerHTML='';
      let found=0;
      const fetchOne=async(q)=>{
        const clean=q.replace(/\D/g,'');
        if(!clean)return;
        const url=BASE+'/api/wize/call?id='+encodeURIComponent(clean);
        try{
          const res=await fetch(url);const data=await res.json();
          if(data.ok){
            found++;
            renderWizCall(resultsEl,data,true,q);
            const phone=data.contactCell||data.companyPhone||'';
            if(phone)showPhoneQuick(phone);
            wizHistAdd({q,type:'call',sub:buildHistSub(data)});
          }else{
            resultsEl.insertAdjacentHTML('beforeend',
              '<div style="font-size:12px;color:var(--text3);padding:6px 0;border-bottom:1px solid var(--border);">קריאה #'+esc(q)+' — לא נמצאה</div>');
          }
        }catch(e){}
      };
      await Promise.all(queries.map(fetchOne));
      if(found===0)resultsEl.innerHTML='<div style="text-align:center;padding:28px;color:var(--text3);">לא נמצאו תוצאות עבור: '+esc(queries.join(', '))+'</div>';
      btn.disabled=false;
      return;
    }

    // Single query
    const q=queries[0];
    const sType=detectSearchType(q);
    const clean=q.replace(/\D/g,'');
    const isShortNum=/^\d{3,6}$/.test(q);
    let url=isShortNum
      ?BASE+'/api/wize/call?id='+encodeURIComponent(clean)
      :BASE+'/api/wize/search?'+new URLSearchParams({q,from:fmtDate(from),to:fmtDate(to)});
    const res=await fetch(url);const data=await res.json();
    if(data.error){
      resultsEl.innerHTML='<div class="alert alert-error"><i class="bi bi-exclamation-triangle-fill"></i> '+esc(data.error)+'</div>';
    }else if(data.ok){
      wizHistAdd({q,type:sType,sub:buildHistSub(data)});
      resultsEl.innerHTML='';
      const phone=data.contactCell||data.companyPhone||'';
      if(phone&&(sType==='call'||sType==='imei'))showPhoneQuick(phone);
      renderWizCall(resultsEl,data,true,q);
    }else if(data.results!==undefined){
      if(!data.results.length){
        wizHistAdd({q,type:sType,sub:'אין תוצאות'});
        resultsEl.innerHTML='<div style="text-align:center;padding:28px;color:var(--text3);"><i class="bi bi-search" style="font-size:32px;display:block;margin-bottom:10px;opacity:.4;"></i>לא נמצאו תוצאות<br><span style="font-size:12px;">'+esc(data.dateFrom||'')+' – '+esc(data.dateTo||'')+'</span></div>';
      }else{
        const sub=data.results.length>1?data.results.length+' תוצאות':buildHistSub(data.results[0]);
        wizHistAdd({q,type:sType,sub});
        const typeLabel={Cphone:'📱 טלפון',Ccell:'📱 טלפון',Pserial:'📦 IMEI',Ccompany:'🏢 לקוח',callid:'# קריאה'}[data.searchType]||'';
        resultsEl.innerHTML='<div style="font-size:12px;color:var(--text3);margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">'+data.count+' תוצאות '+typeLabel+' · '+esc(data.dateFrom||'')+' – '+esc(data.dateTo||'')+'</div>';
        // No phone-quick button when search is already by phone
        data.results.forEach(r=>renderWizCall(resultsEl,r,true,q));
      }
    }
  }catch(e){
    resultsEl.innerHTML='<div class="alert alert-error"><i class="bi bi-wifi-off"></i> שגיאת תקשורת</div>';
    console.error('wiz',e);
  }
  btn.disabled=false;
}

const WIZ_SIZES={sm:'640px',md:'860px',lg:'1100px'};
function wizResize(sz){
  const inner=document.getElementById('wiz-modal-inner');
  if(!inner)return;
  inner.style.maxWidth=WIZ_SIZES[sz]||'860px';
  localStorage.setItem('wiz_modal_size',sz);
  // update button states
  ['sm','md','lg'].forEach(s=>{
    const b=document.getElementById('wiz-sz-'+s);
    if(b){b.style.color=s===sz?'var(--accent)':'var(--text3)';b.style.borderColor=s===sz?'rgba(91,141,238,.4)':'var(--border)';b.style.background=s===sz?'var(--accent-dim)':'var(--bg4)';}
  });
}
function wizInitSize(){
  const sz=localStorage.getItem('wiz_modal_size')||'md';
  wizResize(sz);
}

function wizCardToggle(id){
  const body=document.getElementById('body-'+id);
  const btn=document.getElementById('toggle-'+id);
  if(!body)return;
  const isOpen=body.style.display!=='none';
  body.style.display=isOpen?'none':'block';
  if(btn)btn.innerHTML=isOpen?'▼':'▲';
}

function relTime(dateStr){
  if(!dateStr)return'';
  const m=dateStr.match(/(\d+)\/(\d+)\/(\d+)\s+(\d+):(\d+)/);
  if(!m)return'';
  const dt=new Date(m[3],m[2]-1,m[1],m[4],m[5]);
  const diff=Date.now()-dt.getTime();
  const totalMins=Math.floor(diff/60000);
  const totalHrs=Math.floor(totalMins/60);
  const totalDays=Math.floor(totalHrs/24);
  if(totalDays>=365){
    const yrs=Math.floor(totalDays/365);
    const remDays=totalDays-yrs*365;
    const mos=Math.floor(remDays/30);
    const d=remDays%30;
    let s=yrs+' שנ\u05d9\u05dd';
    if(mos>0)s+=' ו-'+mos+' ח\u05d5\u05d3\u05e9\u05d9\u05dd';
    else if(d>0)s+=' ו-'+d+' \u05d9\u05de\u05d9\u05dd';
    return s;
  }
  if(totalDays>=30){
    const mos=Math.floor(totalDays/30);
    const remD=totalDays-mos*30;
    let s=mos+(mos===1?' \u05d7\u05d5\u05d3\u05e9':' \u05d7\u05d5\u05d3\u05e9\u05d9\u05dd');
    if(remD>0)s+=' ו-'+remD+' \u05d9\u05de\u05d9\u05dd';
    return s;
  }
  if(totalDays>0){
    const remHrs=totalHrs-totalDays*24;
    let s=totalDays+(totalDays===1?' \u05d9\u05d5\u05dd':' \u05d9\u05de\u05d9\u05dd');
    if(remHrs>0)s+=' ו-'+remHrs+' \u05e9\u05e2\u05d5\u05ea';
    return s;
  }
  if(totalHrs>0){
    const remMins=totalMins-totalHrs*60;
    let s=totalHrs+(totalHrs===1?' \u05e9\u05e2\u05d4':' \u05e9\u05e2\u05d5\u05ea');
    if(remMins>0)s+=' ו-'+remMins+' \u05d3\u05e7\u05d5\u05ea';
    return s;
  }
  if(totalMins>0)return totalMins+' \u05d3\u05e7\u05d5\u05ea';
  return '\u05e2\u05db\u05e9\u05d9\u05d5';
}

function renderWizCall(container,d,append,searchQuery){
  /* ── helpers ── */
  const statusMap={
    'נמסר ללקוח':'#22c55e','סגור':'#22c55e',
    'פתוח':'var(--accent)','בטיפול':'#f59e0b',
    'ממתין':'#f59e0b','בוטל':'#ef4444'
  };
  const statusColor=Object.entries(statusMap).find(([k])=>d.statusName?.includes(k))?.[1]||'var(--text3)';
  const relAge=relTime(d.createDate);
  const ageNum=parseInt(relAge);
  const isUrgent=relAge.includes('ימים')&&ageNum>7||relAge.includes('חודשים')||relAge.includes('שנים');

  /* ── subjects ── */
  let subjectsData=d.subjects||[];
  if(!subjectsData.length&&d.sla&&d.sla.trim())
    subjectsData=[{name:d.sla,desc:''}];

  /* ── resolution cleanup ── */
  const cleanRes=(d.resolution||[]).filter(r=>r.replace(/[\s:;,]/g,'').length>5);

  /* ── build card ── */
  let h='';
  const cardId='wc-'+Math.random().toString(36).slice(2,8);
  h+='<div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:14px;font-size:13px;">';

  /* STATUS BAR — colored top strip */
  h+='<div style="height:4px;background:'+statusColor+';"></div>';

  /* HEADER — clicking anywhere collapses/expands the card */
  h+='<div onclick="wizCardToggle(\''+cardId+'\')" '
    +'style="padding:14px 16px 12px;background:var(--bg3);border-bottom:1px solid var(--border);cursor:pointer;user-select:none;transition:background .13s;" '
    +'onmouseenter="this.style.background=\'var(--bg4)\'" onmouseleave="this.style.background=\'var(--bg3)\'">';
  // hlQ — highlight matching text
  function hlQ(text,q){
    if(!q||!text)return esc(text||'');
    const t=String(text),sq=String(q);
    const idx=t.indexOf(sq);
    if(idx<0)return esc(t);
    return esc(t.slice(0,idx))+'<mark style="background:#f59e0b33;color:var(--warning);border-radius:2px;padding:0 1px;">'+esc(sq)+'</mark>'+esc(t.slice(idx+sq.length));
  }
  /* ROW 1: call number · status badge · spacer · toggle indicator · wizenet link */
  h+='<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap;">';
  h+='<span style="font-size:18px;font-weight:800;color:var(--accent);font-variant-numeric:tabular-nums;letter-spacing:-.5px;">#'+esc(d.callId||'')+'</span>';
  if(d.statusName){
    h+='<span style="font-size:12px;font-weight:700;color:'+statusColor+';padding:2px 10px;border:1px solid '+statusColor+';border-radius:12px;background:var(--bg4);">'+esc(d.statusName)+'</span>';
  }
  h+='<div style="flex:1;"></div>';
  h+='<span id="toggle-'+cardId+'" style="font-size:11px;color:var(--text3);background:var(--bg2);border:1px solid var(--border);border-radius:5px;padding:2px 7px;flex-shrink:0;line-height:1.4;">▲</span>';
  h+='<a href="https://bug.wizenet.co.il/serviceControl.aspx?control=modulesCustom/bug/CallDetailsTech&CallID='+esc(d.callId||'')+'" target="_blank" onclick="event.stopPropagation()"'
    +' style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:var(--accent);text-decoration:none;'
    +'padding:4px 9px;border:1px solid rgba(91,141,238,.3);border-radius:var(--radius-sm);white-space:nowrap;flex-shrink:0;background:var(--accent-dim);">'
    +'<i class="bi bi-box-arrow-up-right"></i> וייזנט</a>';
  h+='</div>';
  /* ROW 2: date cards + diff badge */
  {
    const raCreate=relTime(d.createDate);
    const raUpdate=relTime(d.updateDate);
    let diffNum='', diffLabel='';
    if(d.createDate&&d.updateDate){
      const _pd=s=>{const m=s.match(/(\d+)\/(\d+)\/(\d+)\s+(\d+):(\d+)/);return m?new Date(m[3],m[2]-1,m[1],m[4],m[5]):null;};
      const dtC=_pd(d.createDate),dtU=_pd(d.updateDate);
      if(dtC&&dtU){
        const _ms=Math.abs(dtU-dtC);
        const _mins=Math.floor(_ms/60000),_hrs=Math.floor(_mins/60),_days=Math.floor(_hrs/24);
        if(_days>=30){const _mos=Math.floor(_days/30);diffNum=_mos;diffLabel=_mos===1?'חודש':'חודשים';}
        else if(_days>0){diffNum=_days;diffLabel=_days===1?'יום':'ימים';}
        else if(_hrs>0){diffNum=_hrs;diffLabel=_hrs===1?'שעה':'שעות';}
        else{diffNum=_mins;diffLabel='דקות';}
      }
    }
    h+='<div style="display:flex;align-items:stretch;gap:8px;">';
    /* open date card */
    if(d.createDate){
      const [dPart, tPart] = d.createDate.split(' ');
      h+='<div style="flex:1;background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:8px 12px;min-width:0;">';
      h+='<div style="font-size:11px;font-weight:700;color:var(--text3);margin-bottom:3px;">📅 פתיחה</div>';
      h+='<div style="font-size:18px;font-weight:800;color:var(--text);font-variant-numeric:tabular-nums;line-height:1;">'+esc(dPart)+'</div>';
      if(tPart) h+='<div style="font-size:12px;font-weight:600;color:var(--text2);margin-top:2px;">'+esc(tPart)+'</div>';
      if(raCreate)h+='<div style="font-size:16px;color:var(--text3);margin-top:2px;">לפני '+esc(raCreate)+'</div>';
      h+='</div>';
    }
    /* diff badge in the middle */
    if(diffNum!==''){
      h+='<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;min-width:44px;padding:0 4px;">';
      h+='<div style="font-size:17px;font-weight:800;color:var(--text2);font-variant-numeric:tabular-nums;line-height:1.1;">'+esc(String(diffNum))+'</div>';
      h+='<div style="font-size:10px;color:var(--text3);">'+esc(diffLabel)+' הפרש</div>';
      h+='</div>';
    }
    /* update date card */
    if(d.updateDate){
      const [udPart, utPart] = d.updateDate.split(' ');
      h+='<div style="flex:1;background:var(--bg2);border:1px solid var(--border);border-right:3px solid '+statusColor+';border-radius:8px;padding:8px 12px;min-width:0;">';
      h+='<div style="font-size:11px;font-weight:700;color:var(--text3);margin-bottom:3px;">🔄 '+esc(d.statusName)+'</div>';
      h+='<div style="font-size:18px;font-weight:800;color:'+statusColor+';font-variant-numeric:tabular-nums;line-height:1;">'+esc(udPart)+'</div>';
      if(utPart) h+='<div style="font-size:12px;font-weight:600;color:var(--text2);margin-top:2px;">'+esc(utPart)+'</div>';
      if(raUpdate)h+='<div style="font-size:16px;color:var(--text3);margin-top:2px;">לפני '+esc(raUpdate)+'</div>';
      h+='</div>';
    }
    h+='</div>';
  }
  /* urgent indicator */
  if(relAge&&isUrgent){
    h+='<div style="margin-top:8px;display:inline-flex;align-items:center;gap:5px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:var(--radius-sm);padding:3px 10px;font-size:12px;font-weight:700;color:var(--danger);">⚠️ ישנה! '+esc(relAge)+'</div>';
  }
  h+='</div>';

  h+='<div id="body-'+cardId+'">';

  /* PRODUCT STRIP */
  if(d.product||d.makat||d.serial){
    h+='<div style="padding:10px 16px;background:rgba(91,141,238,.06);border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-wrap:wrap;">';
    h+='<i class="bi bi-box-seam" style="color:var(--accent);font-size:18px;flex-shrink:0;"></i>';
    h+='<div style="flex:1;min-width:0;">';
    if(d.product)h+='<div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:2px;">'+esc(d.product)+'</div>';
    h+='<div style="display:flex;gap:14px;flex-wrap:wrap;">';
    if(d.makat)h+='<span style="font-size:11px;color:var(--text3);">מקט <code style="font-size:11px;color:var(--text2);background:var(--bg4);padding:1px 6px;border-radius:3px;font-family:monospace;">'+esc(d.makat)+'</code></span>';
    if(d.serial)h+='<span style="font-size:11px;color:var(--text3);">S/N <code style="font-size:11px;color:var(--text2);background:var(--bg4);padding:1px 6px;border-radius:3px;font-family:monospace;">'+hlQ(d.serial,searchQuery)+'</code></span>';
    h+='</div>';
    h+='</div>';
    h+='</div>';
  }

  /* MAIN CONTENT — contact RIGHT (prominent), branch LEFT */
  h+='<div style="display:grid;grid-template-columns:1fr 1fr;gap:0;">';

  /* col LEFT: branch / location */
  h+='<div style="border-left:1px solid var(--border);">';
  if(d.branch||d.address||d.companyPhone){
    h+='<div style="padding:12px 16px;border-bottom:1px solid var(--border);">';
    h+='<div style="font-size:10px;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;display:flex;align-items:center;gap:4px;">'
      +'<i class="bi bi-shop" style="font-size:12px;"></i> סניף</div>';
    if(d.branch)h+='<div style="font-weight:600;color:var(--text);margin-bottom:2px;">'+esc(d.branch)+'</div>';
    if(d.address)h+='<div style="font-size:12px;color:var(--text2);margin-bottom:5px;line-height:1.4;">'+esc(d.address)+'</div>';
    if(d.companyPhone)h+='<a href="tel:'+esc(d.companyPhone)+'" style="font-size:13px;color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:5px;font-weight:600;">'
      +'<i class="bi bi-telephone-fill" style="font-size:12px;"></i>'+esc(d.companyPhone)+'</a>';
    h+='</div>';
  }
  h+='</div>';

  /* col RIGHT: contact — more prominent */
  h+='<div style="background:rgba(16,185,129,.04);">';
  if(d.contactName||d.contactCell){
    h+='<div style="padding:14px 16px;border-bottom:1px solid var(--border);">';
    h+='<div style="font-size:10px;font-weight:700;color:#10b981;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;display:flex;align-items:center;gap:4px;">'
      +'<i class="bi bi-person-fill" style="font-size:12px;"></i> איש קשר</div>';
    // Large avatar + name
    h+='<div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">';
    h+='<div style="width:38px;height:38px;border-radius:50%;background:rgba(16,185,129,.2);border:2px solid rgba(16,185,129,.4);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#10b981;flex-shrink:0;">'+esc((d.contactName||'?').charAt(0))+'</div>';
    h+='<div>';
    if(d.contactName)h+='<div style="font-size:15px;font-weight:700;color:var(--text);">'+esc(d.contactName)+'</div>';
    h+='</div></div>';
    if(d.contactCell)h+='<a href="tel:'+esc(d.contactCell)+'" style="display:inline-flex;align-items:center;gap:6px;font-size:15px;font-weight:700;color:#10b981;text-decoration:none;background:rgba(16,185,129,.12);padding:5px 12px;border-radius:6px;border:1px solid rgba(16,185,129,.25);">'
      +'<i class="bi bi-telephone-fill" style="font-size:13px;"></i>'+esc(d.contactCell)+'</a>';
    if(d.contactEmail&&d.contactEmail.trim()&&!d.contactEmail.includes('@486142'))
      h+='<div style="font-size:11px;color:var(--text3);margin-top:4px;">'+esc(d.contactEmail)+'</div>';
    h+='</div>';
  }
  h+='</div>';

  h+='</div>'; /* end 2col grid */

  /* SUBJECTS TABLE — full width */
  if(subjectsData.length){
    // subjects — prominent section
    h+='<div style="border-top:2px solid rgba(245,158,11,.4);">';
    // section title
    h+='<div style="padding:10px 16px 8px;background:rgba(245,158,11,.07);border-bottom:1px solid rgba(245,158,11,.2);display:flex;align-items:center;justify-content:space-between;">';
    h+='<span style="font-size:12px;font-weight:700;color:#f59e0b;display:flex;align-items:center;gap:6px;">'
      +'<i class="bi bi-list-check" style="font-size:14px;"></i>טענות לקוח וטיפול מעבדה</span>';
    h+='<span style="font-size:10px;color:var(--text3);">'+subjectsData.length+' רשומות</span>';
    h+='</div>';
    // col headers
    h+='<div style="display:grid;grid-template-columns:1fr 1fr;background:rgba(245,158,11,.05);">';
    h+='<div style="padding:6px 14px;font-size:11px;font-weight:700;color:#f59e0b;border-left:1px solid rgba(245,158,11,.2);display:flex;align-items:center;gap:4px;">'
      +'<i class="bi bi-person-exclamation" style="font-size:12px;"></i> טענת לקוח</div>';
    h+='<div style="padding:6px 14px;font-size:11px;font-weight:700;color:#10b981;display:flex;align-items:center;gap:4px;">'
      +'<i class="bi bi-wrench-adjustable" style="font-size:12px;"></i> טיפול מעבדה</div>';
    h+='</div>';
    subjectsData.forEach((s,i)=>{
      const hasDesc=s.desc&&s.desc.trim()&&s.desc.trim()!=='-'&&s.desc.trim().length>1;
      const rowBg=i%2?'rgba(245,158,11,.03)':'var(--bg2)';
      h+='<div style="display:grid;grid-template-columns:1fr 1fr;background:'+rowBg+';border-top:1px solid var(--border);">';
      // complaint cell
      h+='<div style="padding:10px 14px;border-left:1px solid var(--border);">';
      h+='<div style="font-size:13px;color:var(--text);line-height:1.5;font-weight:500;">'+esc(s.name)+'</div>';
      h+='</div>';
      // treatment cell
      h+='<div style="padding:10px 14px;background:'+(hasDesc?'rgba(16,185,129,.04)':'transparent')+';">';
      if(hasDesc){
        h+='<div style="font-size:13px;color:var(--text);line-height:1.5;">'+esc(s.desc)+'</div>';
      }else{
        h+='<div style="display:flex;align-items:center;gap:5px;"><span style="width:6px;height:6px;border-radius:50%;background:#f59e0b;flex-shrink:0;"></span><span style="font-size:12px;color:#f59e0b;font-style:italic;">ממתין לטיפול</span></div>';
      }
      h+='</div>';
      h+='</div>';
    });
    h+='</div>';
  }

  /* COMMENTS */
  if(d.comments&&d.comments.trim()){
    h+='<div style="padding:10px 16px;border-top:1px solid var(--border);background:rgba(245,158,11,.04);">';
    h+='<div style="font-size:10px;font-weight:700;color:#f59e0b;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;">'
      +'<i class="bi bi-chat-square-text" style="font-size:12px;margin-left:4px;"></i>הערות</div>';
    h+='<p style="font-size:13px;color:var(--text2);margin:0;line-height:1.6;">'+esc(d.comments)+'</p>';
    h+='</div>';
  }

  /* RESOLUTION */
  if(cleanRes.length){
    h+='<div style="border-top:1px solid var(--border);">';
    h+='<button onclick="var nx=this.nextElementSibling;nx.style.display=nx.style.display===\'none\'?\'block\':\'none\';" '
      +'style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:10px 16px;background:var(--bg3);border:none;cursor:pointer;color:var(--text2);font-size:12px;font-family:var(--font);">'
      +'<span style="display:flex;align-items:center;gap:6px;"><i class="bi bi-clock-history"></i> היסטוריית עדכונים ('+cleanRes.length+')</span>'
      +'<i class="bi bi-chevron-down" style="font-size:12px;"></i></button>';
    h+='<div style="display:none;padding:10px 16px;">';
    cleanRes.forEach(r=>{
      h+='<div style="border-right:3px solid var(--border2);padding:5px 10px;margin-bottom:6px;font-size:12px;color:var(--text2);line-height:1.5;">'+esc(r)+'</div>';
    });
    h+='</div>';
    h+='</div>';
  }

  h+='</div>'; // end collapsible body
  h+='</div>'; /* end card */

  if(append)container.insertAdjacentHTML('beforeend',h);
  else container.innerHTML=h;
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

/* ── Nav ── */
const NI_CLASS={'bi-house-fill':'ni-home','bi-home-fill':'ni-home','bi-shop':'ni-store','bi-building-fill':'ni-store','bi-people-fill':'ni-store','bi-check2-square':'ni-task','bi-eyedropper':'ni-lab','bi-box-seam':'ni-lab','bi-headset':'ni-support','bi-book':'ni-support','bi-link-45deg':'ni-link','bi-chat-dots-fill':'ni-link','bi-bag-fill':'ni-link','bi-telephone-fill':'ni-link','bi-telephone-outbound':'ni-link','bi-gear-fill':'ni-admin','bi-menu-button-wide':'ni-admin','bi-palette-fill':'ni-admin','bi-person-fill':'ni-admin'};
const ICON_MAP={'bx-home':'bi-house-fill','bx-store':'bi-shop','bx-phone':'bi-telephone-fill','bx-task':'bi-check2-square','bx-flask':'bi-eyedropper','bx-chart':'bi-bar-chart-fill','bx-cog':'bi-gear-fill','bx-user':'bi-person-fill','bxs-report':'bi-file-earmark-text-fill','bx-link':'bi-link-45deg','bx-wrench':'bi-wrench-adjustable','bx-buildings':'bi-building-fill','bxs-dashboard':'bi-grid-fill','bx-package':'bi-box-seam'};
function mapIcon(i){if(!i)return'bi-circle';if(i.startsWith('bi'))return i;for(const[k,v]of Object.entries(ICON_MAP)){if(i.includes(k))return v;}return'bi-circle-fill';}
function niClass(i){return NI_CLASS[i]||'';}
function resolveHref(item){
  const t=(item.navLinkType||'url').toLowerCase();
  if(t==='jsfunction') return '#';
  const l=item.navLink||'';
  if(!l) return '#';
  if(l.startsWith('http')) return l;
  return BASE+l;
}
function navOnClick(item){
  const t=(item.navLinkType||'url').toLowerCase();
  if(t!=='jsfunction') return '';
  const fn=(item.navLink||'').trim();
  if(!fn) return '';
  const call=fn.includes('(')?fn:fn+'()';
  return 'onclick="event.preventDefault();'+call+'"';
}
async function loadNav(){
  try{const res=await fetch(BASE+'/api/nav',{method:'POST',credentials:'include'});if(!res.ok)return;const items=await res.json();if(Array.isArray(items)&&items.length)renderNav(items);}catch(e){console.error('nav',e);}
}
function renderNav(items){
  const menu=document.getElementById('nav-menu');
  const bp=BASE.startsWith('http')?new URL(BASE).pathname.replace(/\/$/,''):BASE;
  const cur=window.location.pathname.replace(bp,'')||'/';
  const parents=items.filter(i=>i.mainNavContainer&&i.isParent&&!i.isSubMenu);
  const singles=items.filter(i=>i.mainNavContainer&&!i.isParent&&i.isSubMenu);
  const kids=items.filter(i=>i.isSubMenu&&!i.mainNavContainer);
  let html='';
  if(singles.length){
    html+='<div class="nav-group-label">ראשי</div>';
    for(const item of singles){
      const isJs=(item.navLinkType||'').toLowerCase()==='jsfunction';
      const href=resolveHref(item);const icon=mapIcon(item.icon);const ni=niClass(icon);
      const active=!isJs&&cur===item.navLink?'active':'';
      const isWip=!isJs&&href==='#';
      const oc=isJs?navOnClick(item):isWip?'onclick="v2Toast(\''+item.navNameHEB.replace(/'/g,"\\'")+'  — בפיתוח\');return false;"':'';
      const tb=!isJs&&item.toBlank&&item.toBlank!=0?' target="_blank"':'';
      html+='<a href="'+href+'"'+tb+' '+oc+' class="nav-item '+ni+' '+active+(isWip?' wip':'')+'\" data-tip="'+item.navNameHEB+'">'
        +'<i class="bi '+icon+' nav-icon"></i><span class="nav-text">'+item.navNameHEB+'</span></a>';
    }
  }
  for(const parent of parents){
    const myKids=kids.filter(k=>String(k.parentID)===String(parent.id));
    if(!myKids.length)continue;
    const pIcon=mapIcon(parent.icon);const pNi=niClass(pIcon);
    const anyActive=myKids.some(k=>cur===k.navLink&&(k.navLinkType||'url').toLowerCase()!=='jsfunction');
    html+='<div class="nav-group-label">'+parent.navNameHEB+'</div>'
      +'<div class="nav-dropdown'+(anyActive?' open':'')+'" id="dd-'+parent.id+'">'
      +'<div class="nav-item '+pNi+'" data-tip="'+parent.navNameHEB+'" onclick="toggleDrop(\'dd-'+parent.id+'\')">'
      +'<i class="bi '+pIcon+' nav-icon"></i><span class="nav-text">'+parent.navNameHEB+'</span>'
      +'<i class="bi bi-chevron-left nav-arrow"></i></div><div class="nav-sub">';
    for(const kid of myKids){
      const isJs=(kid.navLinkType||'').toLowerCase()==='jsfunction';
      const href=resolveHref(kid);const kIcon=mapIcon(kid.icon);const kNi=niClass(kIcon);
      const active=!isJs&&cur===kid.navLink?'active':'';
      const isWip=!isJs&&href==='#';
      const oc=isJs?navOnClick(kid):isWip?'onclick="v2Toast(\''+kid.navNameHEB.replace(/'/g,"\\'")+'  — בפיתוח\');return false;"':'';
      const tb=!isJs&&kid.toBlank&&kid.toBlank!=0?' target="_blank"':'';
      html+='<a href="'+href+'"'+tb+' '+oc+' class="nav-item '+kNi+' '+active+(isWip?' wip':'')+'\" data-tip="'+kid.navNameHEB+'">'
        +'<i class="bi '+kIcon+' nav-icon" style="font-size:17px"></i><span class="nav-text">'+kid.navNameHEB+'</span></a>';
    }
    html+='</div></div>';
  }
  menu.innerHTML=html||'<div style="padding:10px;color:var(--text3);font-size:13px;">אין פריטים</div>';
  const match=items.find(i=>i.navLink&&(i.navLinkType||'url').toLowerCase()!=='jsfunction'&&cur.startsWith(i.navLink)&&i.navLink!=='/');
  if(match)document.getElementById('crumb-text').textContent=match.navNameHEB;
  injectSlaBadge();
}
function injectSlaBadge(){
  if(!window.__OVERDUE_COUNT)return;
  const navLinks=document.querySelectorAll('#nav-menu .nav-item');
  navLinks.forEach(el=>{
    const href=el.getAttribute('href')||'';
    if(href===window.__V2_BASE+'/tasks'||href==='/tasks'){
      if(!el.querySelector('.nav-sla-badge')){
        const badge=document.createElement('span');
        badge.className='nav-sla-badge';
        badge.textContent=window.__OVERDUE_COUNT;
        badge.title=window.__OVERDUE_COUNT+' משימות שעברו SLA';
        badge.onclick=function(e){e.stopPropagation();window.location.href=window.__V2_BASE+'/tasks?filter=overdue';};
        el.appendChild(badge);
      }
    }
  });
}
function toggleDrop(id){const el=document.getElementById(id);if(!el)return;document.querySelectorAll('.nav-dropdown.open').forEach(d=>{if(d.id!==id)d.classList.remove('open');});el.classList.toggle('open');}
loadNav();

/* ── Global Search ─────────────────────────────────── */
const si=document.getElementById('global-search');
const _gsModal=document.getElementById('gs-modal');
const _gsInput=document.getElementById('gs-input');
let _gsScope='stores';
let _gsHighlightIdx=-1;

function gsOpen(q,fromTopbar){
  _gsModal.style.alignItems=fromTopbar?'flex-start':'center';
  _gsModal.style.paddingTop=fromTopbar?'56px':'16px';
  _gsModal.style.display='flex';
  if(q!=null)_gsInput.value=q;
  setTimeout(()=>{_gsInput.focus();},30);
  const qv=(_gsInput.value||'').trim();
  if(qv.length>2) gsAutoSearch(qv); else gsEmpty();
}
function gsClose(){
  _gsModal.style.display='none';
  _gsInput.value='';
  si.value='';
  document.getElementById('gs-results').innerHTML='';
}
function gsEmpty(){
  const hints={stores:'שם, מספר, עיר או טלפון',calls:'מספר קריאה, טלפון, IMEI או שם לקוח',contacts:'שם, טלפון או חברה',products:'שם מוצר, מקט או ברקוד',pbx:'מספר טלפון לחיפוש שיחות מרכזיה'};
  document.getElementById('gs-results').innerHTML=
    '<div class="gs-empty"><i class="bi bi-search"></i>'+
    '<div>הקלד ולחץ <kbd style="background:var(--bg4);border:1px solid var(--border2);border-radius:4px;padding:1px 6px;font-size:11px;font-family:var(--font);">Enter</kbd> לחיפוש</div>'+
    (hints[_gsScope]?'<div style="font-size:11px;margin-top:5px;opacity:.55;">'+hints[_gsScope]+'</div>':'')+
    '</div>';
}
const _gsScopes=['stores','calls','contacts','products'<?= !empty($canPbxSearch)?",'pbx'":'' ?>];
function gsSetScope(scope){
  _gsScope=scope;
  document.querySelectorAll('.gs-scope').forEach(b=>b.classList.toggle('gs-scope-active',b.dataset.scope===scope));
  const q=(_gsInput.value||'').trim();
  if(q) gsSearch();
  else gsEmpty();
}
function gsNavScope(dir){
  // RTL: ArrowRight → previous, ArrowLeft → next
  const idx=_gsScopes.indexOf(_gsScope);
  gsSetScope(_gsScopes[(idx+dir+_gsScopes.length)%_gsScopes.length]);
}
function gsHl(text,q){
  if(!text||!q)return(typeof esc==='function'?esc:s=>String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'))(text||'');
  const E=s=>String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const idx=String(text).toLowerCase().indexOf(String(q).toLowerCase());
  if(idx<0)return E(text);
  return E(text.slice(0,idx))+'<mark style="background:rgba(91,141,238,.28);color:var(--accent);border-radius:2px;padding:0 1px;">'+E(text.slice(idx,idx+q.length))+'</mark>'+E(text.slice(idx+q.length));
}
function gsRenderStores(stores,q){
  const res=document.getElementById('gs-results');
  const E=s=>String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  if(!stores.length){res.innerHTML='<div class="gs-empty"><i class="bi bi-shop"></i>לא נמצאו חנויות עבור "'+E(q)+'"</div>';return;}
  let h='<div>';
  stores.forEach(s=>{
    const col=(s.type==='סניף באג')?'var(--accent)':'#8b5cf6';
    const num=E(s.id||'');
    const onclick=typeof openStoreView!=='undefined'
      ?'openStoreView(\''+num+'\')'
      :'window.location.href=BASE+\'/stores/\'+encodeURIComponent(\''+num+'\')';
    h+='<div class="gs-row" onclick="'+onclick+'">';
    h+='<span style="font-size:19px;font-weight:800;color:'+col+';min-width:50px;flex-shrink:0;">'+gsHl(s.store_num,q)+'</span>';
    h+='<div style="flex:1;min-width:0;"><div style="font-weight:600;font-size:14px;">'+gsHl(s.name,q)+'</div>';
    if(s.city)h+='<div style="font-size:11px;color:var(--text3);"><i class="bi bi-geo-alt-fill" style="font-size:10px;"></i> '+gsHl(s.city,q)+'</div>';
    h+='</div>';
    if(s.phone_main)h+='<a href="tel:'+E(s.phone_main)+'" onclick="event.stopPropagation()" style="font-size:12px;color:var(--accent);text-decoration:none;white-space:nowrap;"><i class="bi bi-telephone-fill"></i> '+gsHl(s.phone_main,q)+'</a>';
    if(s.alert_note)h+='<i class="bi bi-exclamation-triangle-fill" style="color:var(--warning);font-size:13px;flex-shrink:0;" title="'+E(s.alert_note)+'"></i>';
    h+='</div>';
  });
  h+='</div>';
  res.innerHTML=h;
}
let _gsContacts=[];
function gsRenderContacts(contacts,q){
  _gsContacts=contacts;
  const res=document.getElementById('gs-results');
  const E=s=>String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  if(!contacts.length){res.innerHTML='<div class="gs-empty"><i class="bi bi-people-fill"></i>לא נמצאו אנשי קשר עבור "'+E(q)+'"</div>';return;}
  const typeCol={'נותן שירות':'#10b981','פנים ארגוני':'#5b8dee','ספק':'#f59e0b','תמיכה טכנית':'#06b6d4','איש קשר':'#8b5cf6','אחר':'#7c829c'};
  const avatarColors=['#5b8dee','#8b5cf6','#10b981','#f59e0b','#ec4899','#06b6d4','#f97316'];
  let h='<div>';
  contacts.forEach((c,i)=>{
    const fullName=((c.first_name||'')+' '+(c.last_name||'')).trim();
    const col=typeCol[c.contact_type||'איש קשר']||'#8b5cf6';
    const hash=fullName.split('').reduce((a,ch)=>Math.imul(31,a)+ch.charCodeAt(0)|0,0);
    const acolor=avatarColors[Math.abs(hash)%avatarColors.length];
    h+='<div class="gs-row" tabindex="-1" onclick="gsOpenContactView(_gsContacts['+i+'])">';
    h+='<div style="width:36px;height:36px;border-radius:50%;background:'+acolor+';display:grid;place-items:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;">'+E((c.first_name||'?').charAt(0))+'</div>';
    h+='<div style="flex:1;min-width:0;">';
    h+='<div style="font-weight:600;">'+gsHl(fullName,q)+'</div>';
    if(c.phone)h+='<div style="font-size:12px;color:var(--text3);"><i class="bi bi-telephone-fill" style="font-size:10px;margin-left:3px;"></i>'+gsHl(c.phone,q)+'</div>';
    if(c.email&&c.email.trim())h+='<div style="font-size:12px;color:var(--text3);"><i class="bi bi-envelope-fill" style="font-size:10px;margin-left:3px;"></i>'+gsHl(c.email,q)+'</div>';
    h+='</div>';
    h+='<span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;color:'+col+';border:1px solid '+col+'44;background:'+col+'15;white-space:nowrap;">'+E(c.contact_type||'איש קשר')+'</span>';
    h+='</div>';
  });
  h+='</div>';
  res.innerHTML=h;
}
function gsRenderProducts(products,q){
  const res=document.getElementById('gs-results');
  const E=s=>String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  if(!products.length){res.innerHTML='<div class="gs-empty"><i class="bi bi-box-seam"></i>לא נמצאו מוצרים עבור "'+E(q)+'"</div>';return;}
  let h='<div>';
  products.forEach(p=>{
    const desc=p.description||'';
    const barcode=p.barcode||'';
    const mfr=p.manufacturer||'';
    const warr=(p.warranty&&p.warranty!=='אין טקסט אחריות')?p.warranty.replace(/\+ אחריות: /g,''):'';
    const bugid=p.bugid||0;
    h+='<div class="gs-row" style="align-items:flex-start;gap:10px;">';
    h+='<div style="width:38px;height:38px;border-radius:8px;background:var(--bg3);border:1px solid var(--border);display:grid;place-items:center;font-size:17px;flex-shrink:0;margin-top:1px;"><i class="bi bi-box-seam" style="color:var(--accent);"></i></div>';
    h+='<div style="flex:1;min-width:0;">';
    h+='<div style="font-weight:600;font-size:14px;">'+gsHl(desc,q)+'</div>';
    h+='<div style="font-size:11px;color:var(--text3);display:flex;gap:8px;flex-wrap:wrap;margin-top:2px;">';
    h+='<span><i class="bi bi-upc" style="font-size:9px;"></i> '+gsHl(barcode,q)+'</span>';
    if(mfr) h+='<span>'+E(mfr)+'</span>';
    if(warr) h+='<span><i class="bi bi-shield-check" style="color:#22c55e;font-size:9px;"></i> '+E(warr)+'</span>';
    h+='</div></div>';
    if(p.user) h+='<span style="font-size:11px;color:var(--text3);white-space:nowrap;padding-top:3px;">'+E(p.user)+'</span>';
    if(bugid) h+='<button onclick="event.stopPropagation();_gsProdInventory('+bugid+',this)" style="font-size:11px;background:rgba(91,141,238,.1);border:1px solid rgba(91,141,238,.3);border-radius:5px;color:var(--accent);cursor:pointer;padding:3px 8px;white-space:nowrap;font-family:var(--font);transition:all .13s;" onmouseover="this.style.background=\'rgba(91,141,238,.2)\'" onmouseout="this.style.background=\'rgba(91,141,238,.1)\'"><i class="bi bi-binoculars"></i> מלאי</button>';
    h+='</div>';
  });
  h+='</div>';
  res.innerHTML=h;
}
async function _gsProdInventory(bugid,btn){
  btn.disabled=true;
  btn.innerHTML='<i class="bi bi-hourglass-split"></i> טוען...';
  try{
    const r=await fetch(BASE+'/api/inventory?itemid='+bugid);
    const data=await r.json();
    document.querySelectorAll('.gs-inv-popup').forEach(el=>el.remove());
    const popup=document.createElement('div');
    popup.className='gs-inv-popup';
    popup.style.cssText='position:fixed;z-index:9999;background:var(--bg2);border:1px solid var(--border2);border-radius:10px;box-shadow:0 16px 48px rgba(0,0,0,.6);padding:14px 16px;max-width:500px;width:90vw;max-height:70vh;overflow-y:auto;direction:rtl;font-family:var(--font);';
    popup.innerHTML='<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;"><strong style="font-size:13px;display:flex;align-items:center;gap:6px;"><i class="bi bi-box-seam" style="color:var(--accent);"></i> מלאי</strong><button onclick="this.closest(\'.gs-inv-popup\').remove()" style="background:none;border:none;color:var(--text2);font-size:18px;cursor:pointer;line-height:1;">✕</button></div>'+(data.table||data.error||'אין נתוני מלאי');
    document.body.appendChild(popup);
    const rect=btn.getBoundingClientRect(),pw=Math.min(500,window.innerWidth*0.9);
    popup.style.left=Math.max(8,Math.min(rect.left,window.innerWidth-pw-8))+'px';
    popup.style.top=(rect.bottom+8)+'px';
    setTimeout(()=>document.addEventListener('click',function h(e){if(!popup.contains(e.target)&&e.target!==btn){popup.remove();document.removeEventListener('click',h);}},true),50);
  }catch(e){if(typeof v2Toast!=='undefined')v2Toast('שגיאה בטעינת מלאי');}
  btn.disabled=false;
  btn.innerHTML='<i class="bi bi-binoculars"></i> מלאי';
}
function gsNavResult(dir){
  const rows=[...document.querySelectorAll('#gs-results .gs-row')];
  if(!rows.length)return;
  if(_gsHighlightIdx>=0&&rows[_gsHighlightIdx])rows[_gsHighlightIdx].style.background='';
  _gsHighlightIdx=Math.max(0,Math.min(rows.length-1,_gsHighlightIdx+dir));
  const target=rows[_gsHighlightIdx];
  if(target){target.style.background='var(--bg3)';target.scrollIntoView({block:'nearest'});}
}
async function gsAutoSearch(q){
  const res=document.getElementById('gs-results');
  const E=s=>String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  res.innerHTML='<div class="gs-empty"><i class="bi bi-hourglass-split"></i>מחפש...</div>';
  const[contacts,stores]=await Promise.all([
    fetch(BASE+'/api/contacts?q='+encodeURIComponent(q)).then(r=>r.json()).catch(()=>[]),
    (()=>{
      const pool=[...(window.ALL_BUG||[]),...(window.ALL_MODAN||[])];
      if(pool.length){
        const ql=q.toLowerCase();
        return Promise.resolve(pool.filter(s=>
          (s.name||'').toLowerCase().includes(ql)||
          (s.store_num||'').includes(ql)||
          (s.phone_main||'').includes(ql)||
          (s.city||'').toLowerCase().includes(ql)
        ).slice(0,10));
      }
      return fetch(BASE+'/api/stores?q='+encodeURIComponent(q)).then(r=>r.json()).catch(()=>[]);
    })()
  ]);
  const cArr=Array.isArray(contacts)?contacts:[];
  const sArr=Array.isArray(stores)?stores:[];
  if(!cArr.length&&!sArr.length){
    res.innerHTML='<div class="gs-empty"><i class="bi bi-search"></i>לא נמצאו תוצאות עבור "'+E(q)+'"</div>';
    return;
  }
  const typeCol={'נותן שירות':'#10b981','פנים ארגוני':'#5b8dee','ספק':'#f59e0b','תמיכה טכנית':'#06b6d4','איש קשר':'#8b5cf6','אחר':'#7c829c'};
  const avatarColors=['#5b8dee','#8b5cf6','#10b981','#f59e0b','#ec4899','#06b6d4','#f97316'];
  let h='<div>';
  if(cArr.length){
    h+='<div style="padding:6px 14px 4px;font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid var(--border);background:var(--bg3);"><i class="bi bi-people-fill" style="margin-left:4px;"></i>אנשי קשר</div>';
    _gsContacts=cArr;
    cArr.forEach((c,i)=>{
      const fullName=((c.first_name||'')+' '+(c.last_name||'')).trim();
      const col=typeCol[c.contact_type||'איש קשר']||'#8b5cf6';
      const hash=fullName.split('').reduce((a,ch)=>Math.imul(31,a)+ch.charCodeAt(0)|0,0);
      const acolor=avatarColors[Math.abs(hash)%avatarColors.length];
      h+='<div class="gs-row" tabindex="-1" onclick="gsOpenContactView(_gsContacts['+i+'])">';
      h+='<div style="width:36px;height:36px;border-radius:50%;background:'+acolor+';display:grid;place-items:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;">'+E((c.first_name||'?').charAt(0))+'</div>';
      h+='<div style="flex:1;min-width:0;">';
      h+='<div style="font-weight:600;">'+gsHl(fullName,q)+'</div>';
      if(c.phone)h+='<div style="font-size:12px;color:var(--text3);"><i class="bi bi-telephone-fill" style="font-size:10px;margin-left:3px;"></i>'+gsHl(c.phone,q)+'</div>';
      if(c.email&&c.email.trim())h+='<div style="font-size:12px;color:var(--text3);"><i class="bi bi-envelope-fill" style="font-size:10px;margin-left:3px;"></i>'+gsHl(c.email,q)+'</div>';
      h+='</div>';
      h+='<span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;color:'+col+';border:1px solid '+col+'44;background:'+col+'15;white-space:nowrap;">'+E(c.contact_type||'איש קשר')+'</span>';
      h+='</div>';
    });
  }
  if(sArr.length){
    h+='<div style="padding:6px 14px 4px;font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid var(--border);background:var(--bg3);'+(cArr.length?'border-top:1px solid var(--border);':'')+'"><i class="bi bi-shop" style="margin-left:4px;"></i>חנויות</div>';
    sArr.forEach(s=>{
      const col=(s.type==='סניף באג')?'var(--accent)':'#8b5cf6';
      const num=E(s.id||'');
      const onclick=typeof openStoreView!=='undefined'
        ?'openStoreView(\''+num+'\')'
        :'window.location.href=BASE+\'/stores/\'+encodeURIComponent(\''+num+'\')';
      h+='<div class="gs-row" tabindex="-1" onclick="'+onclick+'">';
      h+='<span style="font-size:19px;font-weight:800;color:'+col+';min-width:50px;flex-shrink:0;">'+gsHl(s.store_num,q)+'</span>';
      h+='<div style="flex:1;min-width:0;"><div style="font-weight:600;font-size:14px;">'+gsHl(s.name,q)+'</div>';
      if(s.city)h+='<div style="font-size:11px;color:var(--text3);"><i class="bi bi-geo-alt-fill" style="font-size:10px;"></i> '+gsHl(s.city,q)+'</div>';
      h+='</div>';
      if(s.phone_main)h+='<a href="tel:'+E(s.phone_main)+'" onclick="event.stopPropagation()" style="font-size:12px;color:var(--accent);text-decoration:none;white-space:nowrap;"><i class="bi bi-telephone-fill"></i> '+gsHl(s.phone_main,q)+'</a>';
      if(s.alert_note)h+='<i class="bi bi-exclamation-triangle-fill" style="color:var(--warning);font-size:13px;flex-shrink:0;" title="'+E(s.alert_note)+'"></i>';
      h+='</div>';
    });
  }
  h+='</div>';
  res.innerHTML=h;
}
async function gsSearch(){
  const q=_gsInput.value.trim();
  if(!q){gsEmpty();return;}
  const res=document.getElementById('gs-results');
  const E=s=>String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  if(_gsScope==='stores'){
    const pool=[...(window.ALL_BUG||[]),...(window.ALL_MODAN||[])];
    if(pool.length){
      const ql=q.toLowerCase();
      const matches=pool.filter(s=>(s.name||'').toLowerCase().includes(ql)||(s.store_num||'').includes(ql)||(s.phone_main||'').includes(ql)||(s.city||'').toLowerCase().includes(ql)).slice(0,15);
      gsRenderStores(matches,q);
    }else{
      res.innerHTML='<div class="gs-empty"><i class="bi bi-hourglass-split"></i>טוען...</div>';
      try{const r=await fetch(BASE+'/api/stores?q='+encodeURIComponent(q));const data=await r.json();gsRenderStores(Array.isArray(data)?data:[],q);}
      catch(e){res.innerHTML='<div style="padding:14px;"><div class="alert alert-error"><i class="bi bi-wifi-off"></i> שגיאת תקשורת</div></div>';}
    }
  }else if(_gsScope==='calls'){
    gsClose();
    openWizModal();
    document.getElementById('wiz-input').value=q;
    doWizSearch();
  }else if(_gsScope==='contacts'){
    res.innerHTML='<div class="gs-empty"><i class="bi bi-hourglass-split"></i>טוען...</div>';
    try{const r=await fetch(BASE+'/api/contacts?q='+encodeURIComponent(q));const data=await r.json();gsRenderContacts(Array.isArray(data)?data:[],q);}
    catch(e){res.innerHTML='<div style="padding:14px;"><div class="alert alert-error"><i class="bi bi-wifi-off"></i> שגיאת תקשורת</div></div>';}
  }else if(_gsScope==='products'){
    res.innerHTML='<div class="gs-empty"><i class="bi bi-hourglass-split"></i>מחפש מוצרים...</div>';
    try{const r=await fetch(BASE+'/api/products?query='+encodeURIComponent(q));const data=await r.json();gsRenderProducts(Array.isArray(data)?data:[],q);}
    catch(e){res.innerHTML='<div style="padding:14px;"><div class="alert alert-error"><i class="bi bi-wifi-off"></i> שגיאת תקשורת</div></div>';}
  } else if(_gsScope==='pbx'){
    gsClose();
    if(typeof openPbxModal==='function'){openPbxModal();const pi=document.getElementById('pbx-phone-input');if(pi&&q){pi.value=q;pbxSearch();}}
  }
}

// Topbar input → just open modal, sync text, no auto-search
si.addEventListener('focus',e=>{gsOpen(e.target.value||undefined,true);});
si.addEventListener('input',e=>{
  const q=e.target.value;
  if(_gsModal.style.display==='none')gsOpen(q||undefined,true);
  else _gsInput.value=q;
});

// Modal input → sync topbar, auto-search contacts+stores on >2 chars
_gsInput.addEventListener('input',e=>{
  si.value=e.target.value;
  _gsHighlightIdx=-1;
  const q=e.target.value.trim();
  if(q.length>2) gsAutoSearch(q);
  else gsEmpty();
});

// Keyboard inside modal input
_gsInput.addEventListener('keydown',e=>{
  if(e.key==='ArrowDown'){
    e.preventDefault();gsNavResult(1);
  }else if(e.key==='ArrowUp'){
    e.preventDefault();gsNavResult(-1);
  }else if(e.key==='Enter'){
    e.preventDefault();
    if(_gsHighlightIdx>=0){
      const rows=[...document.querySelectorAll('#gs-results .gs-row')];
      if(rows[_gsHighlightIdx]){rows[_gsHighlightIdx].click();return;}
    }
    gsSearch();
  }
  // Arrow keys navigate scopes (RTL: Right=prev, Left=next)
  else if(e.key==='ArrowRight'){e.preventDefault();gsNavScope(-1);}
  else if(e.key==='ArrowLeft') {e.preventDefault();gsNavScope(1);}
});

_gsModal.addEventListener('click',e=>{if(e.target===_gsModal)gsClose();});

// Ctrl+K — e.code is layout-independent (works with Hebrew keyboard)
document.addEventListener('keydown',e=>{
  if((e.metaKey||e.ctrlKey)&&e.code==='KeyK'){e.preventDefault();si.focus();gsOpen(si.value||undefined);}
  if(e.key==='Escape'){gsClose();closeGcv();si.blur();closeMobileNav();closeUserMenu();if(typeof closeWizModal==='function')closeWizModal();}
});

// Wire wiz button after DOM ready

/* ── Global tel / mailto click handler ────────────────
   Normal click  → copy to clipboard + toast
   Alt + click   → follow the link (call / open mail)   */
document.addEventListener('click', e => {
  const a = e.target.closest('a[href^="tel:"],a[href^="mailto:"]');
  if (!a) return;
  e.preventDefault();
  e.stopPropagation();
  const href  = a.getAttribute('href') || '';
  const value = href.startsWith('tel:')    ? href.slice(4)
              : href.startsWith('mailto:') ? href.slice(7)
              : a.textContent.trim();
  if (!value) return;
  if (e.altKey) {
    // Alt+click → follow the link explicitly (bypasses browser "save as" default)
    window.location.href = href;
    return;
  }
  navigator.clipboard.writeText(value)
    .then(()  => v2Toast('📋 הועתק: ' + value))
    .catch(()  => {
      const ta = document.createElement('textarea');
      ta.value = value; ta.style.position='fixed'; ta.style.opacity='0';
      document.body.appendChild(ta); ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      v2Toast('📋 הועתק: ' + value);
    });
}, true);
function gsOpenContactView(c){
  const E=s=>String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const typeCol={'נותן שירות':'#10b981','פנים ארגוני':'#5b8dee','ספק':'#f59e0b','תמיכה טכנית':'#06b6d4','איש קשר':'#8b5cf6','אחר':'#7c829c'};
  const avatarColors=['#5b8dee','#8b5cf6','#10b981','#f59e0b','#ec4899','#06b6d4','#f97316'];
  const fullName=((c.first_name||'')+' '+(c.last_name||'')).trim();
  const col=typeCol[c.contact_type||'איש קשר']||'#8b5cf6';
  const hash=fullName.split('').reduce((a,ch)=>Math.imul(31,a)+ch.charCodeAt(0)|0,0);
  const acolor=avatarColors[Math.abs(hash)%avatarColors.length];
  const initials=(c.first_name||'?').charAt(0)+(c.last_name||'').charAt(0);
  const tags=(c.tags||'').split(',').map(t=>t.trim()).filter(Boolean);

  document.getElementById('gcv-title').innerHTML=
    '<div style="width:34px;height:34px;border-radius:50%;background:'+acolor+';display:grid;place-items:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;">'+E(initials)+'</div>'+
    '<div><div>'+E(fullName)+'</div>'+
    '<span style="font-size:11px;font-weight:400;color:'+col+';">'+E(c.contact_type||'איש קשר')+'</span></div>';

  document.getElementById('gcv-page-link').href=BASE+'/contacts?q='+encodeURIComponent(fullName);

  let html='';

  // Phone / contact
  if(c.phone||c.phone2||c.email||c.website){
    html+='<div style="background:var(--bg3);border:1px solid var(--border);border-right:3px solid #10b981;border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:10px;">';
    html+='<div style="font-size:10px;font-weight:700;color:#10b981;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;"><i class="bi bi-telephone-fill"></i> יצירת קשר</div>';
    if(c.phone)html+='<a href="tel:'+E(c.phone)+'" style="display:flex;align-items:center;gap:8px;font-size:15px;font-weight:700;color:var(--accent);text-decoration:none;background:var(--accent-dim);padding:7px 12px;border-radius:7px;border:1px solid rgba(91,141,238,.2);margin-bottom:6px;"><i class="bi bi-telephone-fill"></i>'+E(c.phone)+'</a>';
    if(c.phone2)html+='<a href="tel:'+E(c.phone2)+'" style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--accent);text-decoration:none;margin-bottom:4px;"><i class="bi bi-telephone"></i>'+E(c.phone2)+'</a>';
    if(c.email)html+='<a href="mailto:'+E(c.email)+'" style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text2);text-decoration:none;"><i class="bi bi-envelope-fill"></i>'+E(c.email)+'</a>';
    if(c.website)html+='<a href="'+E(c.website)+'" target="_blank" style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text3);text-decoration:none;margin-top:4px;"><i class="bi bi-globe"></i>'+E(c.website)+'</a>';
    html+='</div>';
  }

  // Details
  if(c.role||c.department||c.address){
    html+='<div style="background:var(--bg3);border:1px solid var(--border);border-right:3px solid #f59e0b;border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:10px;">';
    html+='<div style="font-size:10px;font-weight:700;color:#f59e0b;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;"><i class="bi bi-building"></i> פרטים</div>';
    html+='<div style="display:grid;gap:6px;font-size:13px;">';
    if(c.role)html+='<div><span style="font-size:10px;color:var(--text3);">תפקיד</span><div style="font-weight:600;">'+E(c.role)+'</div></div>';
    if(c.department)html+='<div><span style="font-size:10px;color:var(--text3);">מחלקה / חברה</span><div>'+E(c.department)+'</div></div>';
    if(c.address)html+='<div><span style="font-size:10px;color:var(--text3);">כתובת</span><div>'+E(c.address)+'</div></div>';
    html+='</div></div>';
  }

  // Tags
  if(tags.length){
    html+='<div style="background:var(--bg3);border:1px solid var(--border);border-right:3px solid #06b6d4;border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:10px;">';
    html+='<div style="font-size:10px;font-weight:700;color:#06b6d4;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;"><i class="bi bi-tags-fill"></i> תגיות</div>';
    html+='<div style="display:flex;flex-wrap:wrap;gap:5px;">';
    tags.forEach(t=>{html+='<span style="font-size:12px;background:var(--bg4);border:1px solid var(--border2);border-radius:12px;padding:2px 9px;color:var(--text2);">'+E(t)+'</span>';});
    html+='</div></div>';
  }

  // Note
  if(c.note){
    html+='<div style="background:var(--bg3);border:1px solid var(--border);border-right:3px solid #8b5cf6;border-radius:var(--radius-sm);padding:12px 14px;">';
    html+='<div style="font-size:10px;font-weight:700;color:#8b5cf6;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;"><i class="bi bi-sticky-fill"></i> הערה</div>';
    html+='<p style="font-size:13px;color:var(--text2);margin:0;line-height:1.6;">'+E(c.note).replace(/\n/g,'<br>')+'</p></div>';
  }

  document.getElementById('gcv-body').innerHTML=html||'<div style="color:var(--text3);text-align:center;padding:20px;">אין פרטים נוספים</div>';
  document.getElementById('gcv-modal').style.display='flex';
}
function closeGcv(){document.getElementById('gcv-modal').style.display='none';}
document.getElementById('gcv-modal')?.addEventListener('click',e=>{if(e.target===document.getElementById('gcv-modal'))closeGcv();});
</script>
<style>@keyframes wiz-spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>
<?php
$fmtComponent = __DIR__ . '/../components/formatter-modal.php';
if (file_exists($fmtComponent)) include $fmtComponent;

$crmPopup = __DIR__ . '/../components/crm-popup.php';
if (file_exists($crmPopup)) include $crmPopup;

$calWidget = __DIR__ . '/../components/calendar-widget.php';
if (file_exists($calWidget)) include $calWidget;

$pbxSearch = __DIR__ . '/../components/pbx-search.php';
if (file_exists($pbxSearch)) include $pbxSearch;

$autoModal = __DIR__ . '/../components/automation-modal.php';
if (file_exists($autoModal)) include $autoModal;
?>
<script>
/* expose CSRF to automation module from server-side session token */
window._autoCsrf = '<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>';
</script>

<!-- ══ PRANK ENGINE ══ -->
<script>
(function(){
'use strict';

/* ── cleanup registry ── */
const _cleaners=[];
let _active=false;
let _xCount=0;
let _xTimer=null;

/* ── cancel on triple-X ── */
document.addEventListener('keydown',function(e){
  if(!_active)return;
  if(e.key==='x'||e.key==='X'){
    clearTimeout(_xTimer);
    _xCount++;
    _xTimer=setTimeout(()=>{_xCount=0;},800);
    if(_xCount>=3){_xCount=0;prankStop();}
  }
});

function prankStop(){
  _cleaners.forEach(fn=>{try{fn();}catch(e){}});
  _cleaners.length=0;
  _active=false;
  const overlay=document.getElementById('_pk_overlay');
  if(overlay)overlay.remove();
  showToast('😌 בסדר בסדר... נגמר');
}

function showToast(msg){
  const t=document.createElement('div');
  t.style.cssText='position:fixed;bottom:30px;left:50%;transform:translateX(-50%);background:#1a1e2b;border:1px solid rgba(255,255,255,.15);border-radius:10px;padding:10px 22px;font-size:14px;color:#e2e5f0;z-index:99999;box-shadow:0 8px 30px rgba(0,0,0,.6);pointer-events:none;font-family:Assistant,sans-serif;';
  t.textContent=msg;
  document.body.appendChild(t);
  setTimeout(()=>t.remove(),3000);
}

/* ══════════════ PRANKS ══════════════ */

/* 1. ג'וקים רצים על המסך */
function prankCockroaches(){
  const COUNT=18;
  const bugs=[];
  for(let i=0;i<COUNT;i++){
    const el=document.createElement('div');
    el.textContent='🪳';
    el.style.cssText='position:fixed;font-size:28px;z-index:99998;pointer-events:none;transition:none;user-select:none;';
    el.style.left=Math.random()*window.innerWidth+'px';
    el.style.top=Math.random()*window.innerHeight+'px';
    document.body.appendChild(el);
    bugs.push({el,vx:(Math.random()-.5)*5,vy:(Math.random()-.5)*5,rot:Math.random()*360});
  }
  const raf=requestAnimationFrame(function tick(){
    bugs.forEach(b=>{
      let x=parseFloat(b.el.style.left);
      let y=parseFloat(b.el.style.top);
      x+=b.vx; y+=b.vy;
      if(x<-30||x>window.innerWidth+30){b.vx*=-1;b.el.style.transform=`scaleX(${b.vx>0?1:-1})`;}
      if(y<-30||y>window.innerHeight+30){b.vy*=-1;}
      b.rot+=b.vx*3;
      b.el.style.left=x+'px';
      b.el.style.top=y+'px';
    });
    if(_active)requestAnimationFrame(tick);
  });
  _cleaners.push(()=>{bugs.forEach(b=>b.el.remove());});
}

/* 2. אלמנטים נופלים בגרביטציה */
function prankGravity(){
  const elements=[...document.querySelectorAll('.store-card,.card,.stat-pill,.nav-item,.btn')].slice(0,30);
  const saved=[];
  elements.forEach(el=>{
    const rect=el.getBoundingClientRect();
    const clone=el.cloneNode(true);
    clone.style.cssText=`position:fixed;left:${rect.left}px;top:${rect.top}px;width:${rect.width}px;height:${rect.height}px;margin:0;z-index:9997;pointer-events:none;transition:none;`;
    document.body.appendChild(clone);
    el.style.visibility='hidden';
    saved.push({orig:el,clone,vy:0,vx:(Math.random()-.5)*3,y:rect.top,x:rect.left,rot:0,vrot:(Math.random()-.5)*8,onFloor:false});
  });
  const floor=window.innerHeight-60;
  const raf=requestAnimationFrame(function tick(){
    saved.forEach(o=>{
      if(o.onFloor)return;
      o.vy+=0.6;
      o.y+=o.vy;
      o.x+=o.vx;
      o.rot+=o.vrot;
      if(o.y>=floor){o.y=floor;o.vy*=-.35;o.vrot*=.5;if(Math.abs(o.vy)<1)o.onFloor=true;}
      o.clone.style.top=o.y+'px';
      o.clone.style.left=o.x+'px';
      o.clone.style.transform=`rotate(${o.rot}deg)`;
    });
    if(_active)requestAnimationFrame(tick);
  });
  _cleaners.push(()=>{
    saved.forEach(o=>{o.clone.remove();o.orig.style.visibility='';});
  });
}

/* 3. הבהלה — צלמית זז עם צרחה */
function prankJumpscare(){
  const face=document.createElement('div');
  face.style.cssText='position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.01);pointer-events:none;';
  face.innerHTML='<div style="font-size:0px;transition:font-size .05s;animation:_pk_grow .4s ease-out forwards;">😱</div>';
  const style=document.createElement('style');
  style.textContent='@keyframes _pk_grow{0%{font-size:0px;opacity:0}30%{font-size:260px;opacity:1}70%{font-size:220px}100%{font-size:200px;opacity:.9}}';
  document.head.appendChild(style);

  // Shake screen
  document.body.style.animation='_pk_shake .4s ease';
  const shakeStyle=document.createElement('style');
  shakeStyle.textContent='@keyframes _pk_shake{0%{transform:translate(0)}20%{transform:translate(-12px,8px)}40%{transform:translate(12px,-8px)}60%{transform:translate(-8px,5px)}80%{transform:translate(8px,-5px)}100%{transform:translate(0)}}';
  document.head.appendChild(shakeStyle);

  document.body.appendChild(face);

  // Play beep via Web Audio
  try{
    const ctx=new(window.AudioContext||window.webkitAudioContext)();
    const osc=ctx.createOscillator();
    const gain=ctx.createGain();
    osc.connect(gain);gain.connect(ctx.destination);
    osc.frequency.value=880;
    gain.gain.setValueAtTime(0.4,ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001,ctx.currentTime+0.6);
    osc.start();osc.stop(ctx.currentTime+0.6);
  }catch(e){}

  const tid=setTimeout(()=>{
    face.style.opacity='0';face.style.transition='opacity .5s';
    setTimeout(()=>face.remove(),500);
  },1800);

  _cleaners.push(()=>{
    face.remove();style.remove();shakeStyle.remove();
    document.body.style.animation='';
    clearTimeout(tid);
  });
}

/* 4. הטקסט מתהפך / משתגע */
function prankChaosText(){
  const targets=[...document.querySelectorAll('.sc-name,.sc-num,.nav-text,.page-title,.sidebar-logo-text')];
  const originals=targets.map(el=>({el,text:el.textContent}));
  const chars='!@#$%^&*<>?/|\\~`אבגדהוזחטיכלמנסעפצקרשת';
  function glitch(){
    targets.forEach((el,i)=>{
      const orig=originals[i].text;
      if(Math.random()<.3){
        el.textContent=orig.split('').map(c=>Math.random()<.4?chars[Math.floor(Math.random()*chars.length)]:c).join('');
        setTimeout(()=>{el.textContent=orig;},120+Math.random()*200);
      }
    });
  }
  const iv=setInterval(glitch,150);
  _cleaners.push(()=>{
    clearInterval(iv);
    originals.forEach(o=>{o.el.textContent=o.text;});
  });
}

/* 5. עיניים עוקבות אחרי העכבר */
function prankEyes(){
  const container=document.createElement('div');
  container.style.cssText='position:fixed;inset:0;z-index:99997;pointer-events:none;overflow:hidden;';
  const PAIRS=6;
  const eyeEls=[];
  for(let i=0;i<PAIRS;i++){
    const pair=document.createElement('div');
    pair.style.cssText=`position:absolute;display:flex;gap:6px;left:${10+Math.random()*80}%;top:${10+Math.random()*80}%;transform:translate(-50%,-50%);`;
    for(let j=0;j<2;j++){
      const eye=document.createElement('div');
      eye.style.cssText='width:38px;height:38px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 0 8px rgba(0,0,0,.6);';
      const pupil=document.createElement('div');
      pupil.style.cssText='width:16px;height:16px;border-radius:50%;background:#111;position:relative;transition:transform .08s;';
      eye.appendChild(pupil);
      pair.appendChild(eye);
      eyeEls.push({eye,pupil});
    }
    container.appendChild(pair);
  }
  document.body.appendChild(container);

  function onMove(e){
    eyeEls.forEach(({eye,pupil})=>{
      const r=eye.getBoundingClientRect();
      const cx=r.left+r.width/2,cy=r.top+r.height/2;
      const angle=Math.atan2(e.clientY-cy,e.clientX-cx);
      const dist=Math.min(8,Math.hypot(e.clientX-cx,e.clientY-cy)/8);
      pupil.style.transform=`translate(${Math.cos(angle)*dist}px,${Math.sin(angle)*dist}px)`;
    });
  }
  document.addEventListener('mousemove',onMove);
  _cleaners.push(()=>{container.remove();document.removeEventListener('mousemove',onMove);});
}

/* ══ SELECTOR ══ */
const pranks=[
  {name:'🪳 פלישת ג\'וקים',fn:prankCockroaches},
  {name:'🌍 גרביטציה',fn:prankGravity},
  {name:'😱 הבהלה',fn:prankJumpscare},
  {name:'🔡 כאוס טקסט',fn:prankChaosText},
  {name:'👀 עיניים צופיות',fn:prankEyes},
];

window.prank=function(index){
  if(_active)prankStop();
  _active=true;
  _xCount=0;

  // pick random or specific
  const p=typeof index==='number'?pranks[index%pranks.length]:pranks[Math.floor(Math.random()*pranks.length)];

  // hint overlay
  const hint=document.createElement('div');
  hint.id='_pk_overlay';
  hint.style.cssText='position:fixed;top:14px;right:50%;transform:translateX(50%);z-index:99999;background:rgba(13,15,22,.9);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:7px 18px;font-size:12px;color:#7c829c;font-family:Assistant,sans-serif;pointer-events:none;backdrop-filter:blur(6px);';
  hint.textContent=p.name+' · לחץ X שלוש פעמים לביטול';
  document.body.appendChild(hint);

  p.fn();
};

})();
</script>
</body>
</html>