<?php
use Core\View;
$csrf       = $_SESSION['csrf_token'] ?? '';
$permGroup  = $permGroup  ?? 0;
$isAdmin    = $isAdmin    ?? false;
$isTech     = $isTech     ?? false;
$canReport  = $canReport  ?? false;
$technicians = $technicians ?? [];
$base       = rtrim(CFG['app']['url'], '/');
?>

<!-- ────── LAB STYLES ────── -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<style>
/* ═══ LAB DESIGN SYSTEM ═══════════════════════════════════════════════════ */
#content{background:var(--bg);}
*{box-sizing:border-box;}

#content,#content *:not(.number-font *){font-family:'Inter','Heebo',sans-serif;}

/* ── animations ── */
.view-section{display:none;}
.view-section.active-view{display:block!important;animation:labFadeIn .2s ease;}
@keyframes labFadeIn{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:translateY(0)}}

/* ── KPI cards ── */
.lab-kpi{
  background:var(--bg2);border:1px solid var(--border);border-radius:10px;
  padding:.875rem 1rem;display:flex;align-items:center;gap:.875rem;
  box-shadow:0 1px 4px rgba(0,0,0,.08),0 0 0 0 transparent;
  transition:box-shadow .2s,transform .18s;
}
.lab-kpi:hover{box-shadow:0 6px 20px rgba(0,0,0,.14);transform:translateY(-1px);}
.lab-kpi-icon{width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex-shrink:0;}
.lab-kpi .lbl{font-size:10.5px;font-weight:500;color:var(--text3);margin-bottom:1px;}
.lab-kpi .val{font-size:1.45rem;font-weight:700;color:var(--text);font-variant-numeric:tabular-nums;line-height:1;}

/* ── section cards ── */
.lab-card{
  background:var(--bg2);border:1px solid var(--border);border-radius:10px;overflow:hidden;
  box-shadow:0 1px 4px rgba(0,0,0,.07);
}
/* per-card accent colors via left border */
.lab-card.card-scan  {border-top:2px solid rgba(91,141,238,.6);}
.lab-card.card-form  {border-top:2px solid rgba(34,197,94,.55);}
.lab-card.card-hist  {border-top:2px solid rgba(245,158,11,.55);}
.lab-card.card-inv   {border-top:2px solid rgba(6,182,212,.55);}
.lab-card.card-ana   {border-top:2px solid rgba(168,85,247,.55);}
.lab-card.card-users {border-top:2px solid rgba(236,72,153,.55);}

.lab-card-header{
  display:flex;align-items:center;justify-content:space-between;
  padding:.65rem 1rem;border-bottom:1px solid var(--border);
  background:var(--bg3);
}
.lab-card-title{display:flex;align-items:center;gap:7px;font-size:12.5px;font-weight:600;color:var(--text2);margin:0;}

/* ── forms ── */
.form-control,.form-select{
  background:var(--bg3)!important;border:1px solid var(--border)!important;
  color:var(--text)!important;border-radius:6px!important;
  font-size:13.5px!important;font-family:inherit!important;
  padding:.42rem .7rem!important;height:auto!important;
  transition:border-color .15s,box-shadow .15s;
}
.form-control:focus,.form-select:focus{
  border-color:var(--accent)!important;
  box-shadow:0 0 0 3px rgba(91,141,238,.12)!important;
  background:var(--bg3)!important;color:var(--text)!important;outline:none!important;
}
.form-control::placeholder{color:var(--text3)!important;font-size:13px!important;}
.form-label{
  color:var(--text3)!important;font-size:11px!important;font-weight:600!important;
  text-transform:uppercase!important;letter-spacing:.04em!important;
  margin-bottom:5px!important;display:block;
}
.input-group-text{background:var(--bg3)!important;border:1px solid var(--border)!important;color:var(--text3)!important;border-radius:6px 0 0 6px!important;}
.input-group .form-control{border-radius:0 6px 6px 0!important;}
select option{background:var(--bg2)!important;color:var(--text)!important;}

/* ── filter bar + colored filter fields ── */
.lab-filter-bar{background:var(--bg3);border-bottom:1px solid var(--border);padding:.75rem 1rem;}
#global_search{border-color:rgba(91,141,238,.35)!important;}
#global_search:focus{border-color:var(--accent)!important;}
#filter_manufacturer{border-color:rgba(6,182,212,.35)!important;}
#filter_manufacturer:focus{border-color:#06b6d4!important;box-shadow:0 0 0 3px rgba(6,182,212,.12)!important;}
#filter_stock_status{border-color:rgba(245,158,11,.35)!important;}
#filter_stock_status:focus{border-color:var(--warning)!important;box-shadow:0 0 0 3px rgba(245,158,11,.12)!important;}
#filter_min_qty{border-color:rgba(168,85,247,.35)!important;}
#filter_min_qty:focus{border-color:#a855f7!important;box-shadow:0 0 0 3px rgba(168,85,247,.12)!important;}
/* clear-filters button — red only when active */
#btn-clear-filters{background:transparent;border:1px solid var(--border);color:var(--text3);border-radius:6px;transition:all .2s;width:100%;}
#btn-clear-filters:hover{background:var(--bg4);}
#btn-clear-filters.has-filter{
  border-color:var(--danger);color:var(--danger);
  animation:filterPulse 1.8s ease-in-out infinite;
}
@keyframes filterPulse{0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.0)}50%{box-shadow:0 0 0 4px rgba(239,68,68,.18)}}

/* ── DataTables — layout & toolbar ── */
.dataTables_wrapper{color:var(--text)!important;width:100%!important;box-sizing:border-box;}
.dataTables_wrapper table.dataTable{width:100%!important;min-width:100%!important;}
.dataTables_scrollBody,.dataTables_scrollHead{width:100%!important;}
/* fix: table inside lab-card must stretch full width */
.lab-card .dataTables_wrapper{width:100%!important;}
.lab-card table.dataTable{width:100%!important;}

/* top toolbar: export btn + search on same row */
.dataTables_wrapper>.row:first-child{
  display:flex!important;align-items:center!important;justify-content:space-between!important;
  padding:.55rem .875rem!important;margin:0!important;flex-wrap:nowrap!important;gap:8px!important;
  border-bottom:1px solid var(--border);background:var(--bg3);flex-direction: row-reverse;
}
/* bottom toolbar: info + pagination */
.dataTables_wrapper>.row:last-child{
  display:flex!important;align-items:center!important;justify-content:space-between!important;
  padding:.45rem .875rem!important;margin:0!important;flex-wrap:wrap!important;gap:6px!important;
  border-top:1px solid var(--border);
}
.dataTables_wrapper>.row>.col-sm-12,.dataTables_wrapper>.row>[class*=col-]{
  padding:0!important;flex:0 0 auto!important;width:auto!important;
}
/* השורה האמצעית (הטבלה עצמה) — מתוחה לכל הרוחב */
.dataTables_wrapper>.row:not(:first-child):not(:last-child)>.col-sm-12{
  flex:1 1 100%!important;width:100%!important;max-width:100%!important;
}
div.dataTables_wrapper div.dataTables_filter,
div.dataTables_wrapper div.dataTables_length{text-align:start;}
.dataTables_filter label{
  color:var(--text3)!important;font-size:12px;white-space:nowrap;
  display:flex;align-items:center;gap:6px;margin:0;
}
.dataTables_length label{
  color:var(--text3)!important;font-size:12px;white-space:nowrap;
  display:flex;align-items:center;gap:5px;margin:0;
}
.dataTables_filter input{
  background:var(--bg2)!important;border:1px solid var(--border)!important;color:var(--text)!important;
  border-radius:6px!important;padding:5px 10px!important;font-size:12.5px!important;
  font-family:inherit!important;height:30px!important;width:200px!important;
}
.dataTables_filter input:focus{border-color:var(--accent)!important;box-shadow:0 0 0 2px rgba(91,141,238,.12)!important;outline:none!important;}
.dataTables_length select{
  background:var(--bg2)!important;border:1px solid var(--border)!important;color:var(--text)!important;
  border-radius:6px!important;padding:4px 6px!important;font-size:12px!important;height:30px!important;
}
.dataTables_info{color:var(--text3)!important;font-size:11.5px;padding:0!important;}
.dataTables_paginate{padding:0!important;}
.dataTables_paginate .paginate_button{
  color:var(--text2)!important;border-radius:5px!important;border:none!important;
  padding:4px 9px!important;margin:0 1px!important;font-size:12px!important;transition:background .1s!important;
}
.dataTables_paginate .paginate_button:hover{background:var(--bg3)!important;color:var(--text)!important;}
.dataTables_paginate .paginate_button.current,.dataTables_paginate .paginate_button.current:hover{background:var(--accent)!important;color:#fff!important;font-weight:600!important;}
.dataTables_paginate .paginate_button.disabled{opacity:.25!important;}

/* DT Buttons (export) — compact icon-like button */
.dt-buttons{display:inline-flex;gap:4px;}
.dt-button,.buttons-excel{
  background:transparent!important;border:1px solid var(--border)!important;color:var(--text2)!important;
  border-radius:6px!important;padding:4px 10px!important;font-size:11.5px!important;height:30px!important;
  font-family:inherit!important;cursor:pointer!important;transition:all .15s!important;
  display:inline-flex!important;align-items:center!important;gap:5px!important;
}
.dt-button:hover,.buttons-excel:hover{background:var(--bg4)!important;color:var(--text)!important;border-color:var(--accent)!important;}

/* table rows */
table.dataTable{border-collapse:collapse!important;width:100%!important;table-layout:auto;}
table.dataTable thead th{
  background:var(--bg3)!important;color:var(--text2)!important;border:none!important;
  border-bottom:2px solid var(--border2,var(--border))!important;
  font-size:10.5px!important;font-weight:700!important;text-transform:uppercase!important;
  letter-spacing:.07em!important;padding:11px 14px!important;white-space:nowrap;user-select:none;
  font-family:'Inter','Heebo',sans-serif!important;
  position:relative;
}
/* sort indicators — AFTER text (left side in RTL) */
table.dataTable thead th.sorting::before,
table.dataTable thead th.sorting_asc::before,
table.dataTable thead th.sorting_desc::before,
table.dataTable thead th.sorting::after,
table.dataTable thead th.sorting_asc::after,
table.dataTable thead th.sorting_desc::after{
  display:none!important;
}
table.dataTable thead th.sorting::after,
table.dataTable thead th.sorting_asc::after,
table.dataTable thead th.sorting_desc::after{
  display:inline-block!important;
  position:static!important;
  margin-right:6px!important;
  margin-left:0!important;
  opacity:1!important;
  vertical-align:middle;
}
table.dataTable thead th.sorting::after{content:'⇅';color:rgba(150,150,180,.45);font-size:12px!important;}
table.dataTable thead th.sorting_asc::after{content:'↑';color:var(--accent)!important;font-size:13px!important;font-weight:900;}
table.dataTable thead th.sorting_desc::after{content:'↓';color:var(--accent)!important;font-size:13px!important;font-weight:900;}

table.dataTable tbody tr{background:transparent!important;}
table.dataTable tbody tr td{
  background:transparent!important;color:var(--text)!important;border:none!important;
  border-bottom:1px solid var(--border)!important;
  padding:11px 14px!important;vertical-align:middle!important;
  font-size:13px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
  font-family:'Inter','Heebo',sans-serif!important;
}
table.dataTable tbody tr:last-child td{border-bottom:none!important;}
table.dataTable tbody tr:hover td{background:rgba(255,255,255,.02)!important;}
table.dataTable tbody tr.row-out td{background:rgba(245,158,11,.03)!important;}
table.dataTable tbody tr.row-in  td{background:rgba(91,141,238,.03)!important;}

/* ── POS search / autocomplete ── */
.pos-search-wrap{position:relative;}
.pos-scan-icon{position:absolute;right:11px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:.95rem;pointer-events:none;}
#pos_item_input{padding-right:36px!important;height:42px;font-size:13.5px!important;}
.awesomplete{display:block!important;position:relative;}
.awesomplete>ul{
  position:absolute!important;top:calc(100% + 3px)!important;
  right:0!important;left:0!important;width:100%!important;
  background:var(--bg2)!important;border:1px solid var(--border)!important;
  border-radius:6px!important;box-shadow:0 8px 24px rgba(0,0,0,.35)!important;
  padding:4px!important;z-index:9999!important;max-height:260px;overflow-y:auto;
}
.awesomplete>ul>li{border-radius:4px!important;margin:1px 0!important;padding:7px 10px!important;color:var(--text)!important;cursor:pointer!important;list-style:none;}
.awesomplete>ul>li:hover,[aria-selected=true]{background:var(--bg3)!important;}
.awesomplete mark{background:transparent!important;color:var(--accent)!important;font-weight:700!important;}

/* ── cart ── */
.pos-cart-wrap{background:var(--bg3);border:1px solid var(--border);border-radius:6px;overflow:hidden;}
.pos-cart-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1.75rem;color:var(--text3);gap:5px;}
.pos-cart-item{display:flex;align-items:center;gap:10px;padding:8px 12px;border-bottom:1px solid var(--border);transition:background .1s;}
.pos-cart-item:last-child{border-bottom:none;}
.pos-cart-item:hover{background:rgba(255,255,255,.02);}
.pos-cart-item-info{flex:1;min-width:0;}
.pos-cart-item-part{font-size:12.5px;font-weight:600;color:var(--text);font-family:'IBM Plex Mono',monospace;}
.pos-cart-item-name{font-size:11.5px;color:var(--text3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:1px;}
.pos-cart-item-stock{font-size:11px;margin-top:2px;}
.pos-qty-input{width:60px!important;text-align:center!important;font-weight:600!important;font-size:14px!important;padding:4px 2px!important;border-color:rgba(91,141,238,.5)!important;}
.pos-remove-btn{width:27px;height:27px;border-radius:5px;border:1px solid var(--border);background:transparent;color:var(--text3);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s;font-size:12px;flex-shrink:0;}
.pos-remove-btn:hover{background:rgba(239,68,68,.1);color:var(--danger);border-color:rgba(239,68,68,.3);}

/* ── movement form ── */
.mov-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
@media(max-width:600px){.mov-form-grid{grid-template-columns:1fr;}}
.mov-submit-btn{
  width:100%;padding:.65rem 1.5rem;font-size:13.5px;font-weight:600;border-radius:6px;
  background:var(--accent);border:none;color:#fff;cursor:pointer;
  transition:opacity .15s,box-shadow .15s;display:flex;align-items:center;justify-content:center;gap:7px;
  box-shadow:0 2px 8px rgba(91,141,238,.3);
  font-family:inherit;
}
.mov-submit-btn:hover{opacity:.88;box-shadow:0 4px 16px rgba(91,141,238,.42);}
.mov-submit-btn:active{opacity:.95;transform:scale(.99);}

/* ── badges ── */
.lbadge{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:600;line-height:1.4;font-family:inherit;}
.lbadge-green{background:rgba(34,197,94,.12);color:var(--success);}
.lbadge-red{background:rgba(239,68,68,.12);color:var(--danger);}
.lbadge-yellow{background:rgba(245,158,11,.12);color:var(--warning);}
.lbadge-blue{background:rgba(91,141,238,.12);color:var(--accent);}
.lbadge-cyan{background:rgba(6,182,212,.12);color:#06b6d4;}
.lbadge-gray{background:var(--bg4);color:var(--text2);border:1px solid var(--border);}

/* ── offcanvas ── */
.offcanvas{background:var(--bg2)!important;border-left:1px solid var(--border)!important;}
.offcanvas-header{background:var(--bg3)!important;border-bottom:1px solid var(--border)!important;padding:.875rem 1rem!important;}
.offcanvas-title{color:var(--text)!important;font-weight:600!important;font-size:14px!important;}
.drawer-save-btn{width:100%;padding:.65rem;border-radius:6px;font-weight:600;font-size:14px;background:var(--accent);border:none;color:#fff;cursor:pointer;transition:opacity .15s;box-shadow:0 2px 8px rgba(91,141,238,.3);font-family:inherit;}
.drawer-save-btn:hover{opacity:.88;}

/* ── modals ── */
.modal-content{background:var(--bg2)!important;border:1px solid var(--border)!important;border-radius:10px!important;color:var(--text)!important;box-shadow:0 20px 60px rgba(0,0,0,.35)!important;}
.modal-header{border-bottom:1px solid var(--border)!important;padding:.875rem 1rem!important;background:var(--bg3)!important;border-radius:10px 10px 0 0!important;}
.modal-footer{border-top:1px solid var(--border)!important;background:transparent!important;padding:.75rem 1rem!important;}
.modal-header.mh-accent{background:rgba(91,141,238,.06)!important;border-bottom-color:rgba(91,141,238,.15)!important;}
.modal-header.mh-danger{background:rgba(239,68,68,.06)!important;border-bottom-color:rgba(239,68,68,.15)!important;}
.modal-header.mh-success{background:rgba(34,197,94,.06)!important;border-bottom-color:rgba(34,197,94,.15)!important;}

/* ── pivot ── */
.pivot-container{min-height:400px;overflow-x:auto;}
.pvtUi{color:var(--text)!important;}
.pvtAxisContainer,.pvtVals{background:var(--bg3)!important;border:1px solid var(--border)!important;border-radius:0!important;padding:8px!important;}
.pvtAttr{background:rgba(91,141,238,.1)!important;border:1px solid rgba(91,141,238,.25)!important;border-radius:4px!important;padding:2px 8px!important;color:var(--accent)!important;font-weight:600!important;font-size:12px!important;}
.pvtAxisContainer select,.pvtVals select{background:var(--bg2)!important;border:1px solid var(--border)!important;color:var(--text)!important;border-radius:4px!important;padding:3px 6px!important;font-size:12px!important;}
table.pvtTable{border-collapse:collapse;}
table.pvtTable thead tr th,table.pvtTable tbody tr th{background:var(--bg3)!important;border:1px solid var(--border)!important;color:var(--text2)!important;font-size:12px;padding:5px 8px;}
table.pvtTable tbody tr td{background:var(--bg2)!important;border:1px solid var(--border)!important;color:var(--text)!important;font-size:12px;padding:5px 8px;}
.pvtFilterBox{background:var(--bg2)!important;border:1px solid var(--border)!important;border-radius:6px!important;color:var(--text)!important;box-shadow:0 8px 28px rgba(0,0,0,.4)!important;position:absolute!important;z-index:99999!important;}
.pvtFilterBox input[type=text]{background:var(--bg3)!important;border:1px solid var(--border)!important;color:var(--text)!important;border-radius:4px!important;padding:4px 8px!important;}
.pvtCheckContainer p{color:var(--text2)!important;font-size:12px;}

/* ── misc ── */
.number-font{font-family:'IBM Plex Mono','Courier New',monospace!important;font-variant-numeric:tabular-nums;}
.nowrap{white-space:nowrap;}
.btn-icon{width:27px;height:27px;border-radius:5px;border:1px solid var(--border);background:transparent;color:var(--text3);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s;font-size:12px;flex-shrink:0;}
.btn-icon:hover{background:var(--bg3);color:var(--text);}
.btn-icon.danger:hover{background:rgba(239,68,68,.1);color:var(--danger);border-color:rgba(239,68,68,.25);}
.section-sep{height:1px;background:var(--border);margin:1rem 0;}

/* ── btn overrides ── */
.btn{font-size:13px;font-family:inherit;}
.btn-sm{font-size:12px!important;padding:.3rem .65rem!important;}
.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--text);border-radius:6px;}
.btn-ghost:hover{background:var(--bg3);color:var(--text);}

/* ── users table: clamp width ── */
#dtLabUsers_wrapper{max-width:100%;overflow:hidden;width:100%;}
#dtLabUsers{table-layout:fixed!important;width:100%!important;}
#dtLabUsers td,#dtLabUsers th{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
/* users table — top toolbar compact */
#dtLabUsers_wrapper>.row:first-child{background:var(--bg3);}
</style>

<!-- external libs needed by lab (not in v2 layout) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-1.11.5/b-2.2.2/b-html5-2.2.2/datatables.min.css" />
<script type="text/javascript" src="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-1.11.5/b-2.2.2/b-html5-2.2.2/datatables.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/c3/0.4.11/c3.min.css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.5/d3.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/c3/0.4.11/c3.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/pivot.min.css" />
<script src="https://cdn.jsdelivr.net/npm/pivottable@2.23.0/dist/pivot.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pivottable@2.23.0/dist/export_renderers.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/c3_renderers.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js"></script>

<!-- ────── TAB BAR ────── -->
<style>
.lab-tabbar{display:flex;align-items:center;gap:2px;background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:4px 5px;margin-bottom:1rem;flex-wrap:wrap;}
.lab-tab{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:5px;border:none;background:transparent;color:var(--text3);font-size:13px;font-weight:500;cursor:pointer;font-family:var(--font);transition:background .12s,color .12s;white-space:nowrap;}
.lab-tab:hover{background:var(--bg3);color:var(--text2);}
.lab-tab.active{background:var(--accent);color:#fff;font-weight:600;}
.lab-tab-sep{width:1px;height:18px;background:var(--border);margin:0 3px;flex-shrink:0;}
</style>
<div class="lab-tabbar">
    <?php if ($isAdmin): ?>
    <button class="lab-tab" data-target="#view-movements"><i class="bi bi-qr-code-scan"></i> תנועות</button>
    <?php endif; ?>
    <button class="lab-tab" data-target="#view-inventory"><i class="bi bi-box-seam"></i> ניהול מלאי</button>
    <?php if ($isAdmin): ?>
    <button class="lab-tab" data-target="#view-analytics"><i class="bi bi-bar-chart-line"></i> ניתוח</button>
    <button class="lab-tab" data-target="#view-users"><i class="bi bi-people-fill"></i> צוות</button>
    <?php endif; ?>
    <!-- actions right side -->
    <div style="margin-right:auto;display:flex;gap:8px;align-items:center;">
        <div id="inventory-actions" style="display:none;gap:8px;align-items:center;">
            <?php if ($isAdmin): ?>
            <button class="btn btn-ghost btn-sm" onclick="Inventory.openExcelModal()"><i class="bi bi-file-earmark-excel me-1"></i>ייבוא</button>
            <button class="btn btn-primary btn-sm" onclick="App.openAddDrawer()"><i class="bi bi-plus-lg me-1"></i>פריט חדש</button>
            <?php endif; ?>
        </div>
        <div id="analytics-actions" style="display:none;gap:8px;">
            <button class="btn btn-sm" style="background:rgba(239,68,68,.12);color:var(--danger);border:1px solid rgba(239,68,68,.2);border-radius:7px;" onclick="Analytics.exportToPDF()"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</button>
        </div>
    </div>
</div>

<!-- ────── MAIN CONTENT ────── -->

<!-- KPI cards for movements -->
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mb-4" id="mov-insights" style="display:none">
    <div class="col">
        <div class="lab-kpi kpi-blue">
            <div class="lab-kpi-icon" style="background:rgba(91,141,238,.13);color:var(--accent)"><i class="bi bi-lightning-charge"></i></div>
            <div><div class="lbl">תנועות היום</div><div class="val" id="mov-kpi-today">0</div></div>
        </div>
    </div>
    <div class="col">
        <div class="lab-kpi kpi-red">
            <div class="lab-kpi-icon" style="background:rgba(239,68,68,.13);color:var(--danger)"><i class="bi bi-box-arrow-up-right"></i></div>
            <div><div class="lbl">נופק (החודש)</div><div class="val" id="mov-kpi-out">0</div></div>
        </div>
    </div>
    <div class="col">
        <div class="lab-kpi" style="border-left:3px solid var(--warning)">
            <div class="lab-kpi-icon" style="background:rgba(245,158,11,.13);color:var(--warning)"><i class="bi bi-fire"></i></div>
            <div><div class="lbl">הפריט הלוהט (החודש)</div><div class="val" id="mov-kpi-item" style="font-size:1rem">---</div></div>
        </div>
    </div>
    <div class="col">
        <div class="lab-kpi" style="border-left:3px solid #06b6d4">
            <div class="lab-kpi-icon" style="background:rgba(6,182,212,.13);color:#06b6d4"><i class="bi bi-trophy"></i></div>
            <div><div class="lbl">שיאן הטכנאים</div><div class="val" id="mov-kpi-tech" style="font-size:1.1rem">---</div></div>
        </div>
    </div>
    <div class="col">
        <div class="lab-kpi" style="border-left:3px solid var(--success)">
            <div class="lab-kpi-icon" style="background:rgba(34,197,94,.13);color:var(--success)"><i class="bi bi-tools"></i></div>
            <div><div class="lbl">קריאות שירות (החודש)</div><div class="val" id="mov-kpi-calls">0</div></div>
        </div>
    </div>
</div>

<!-- ── VIEW: MOVEMENTS ── -->
<div id="view-movements" class="view-section">
    <?php if ($isAdmin): ?>
    <div class="row g-3 mb-3">
        <!-- LEFT: search + cart -->
        <div class="col-lg-5">
            <div class="lab-card card-scan h-100">
                <div class="lab-card-header">
                    <span class="lab-card-title"><i class="bi bi-upc-scan" style="color:var(--accent)"></i> חיפוש וסריקה</span>
                </div>
                <div style="padding:.875rem;">
                    <div class="pos-search-wrap mb-3">
                        <i class="bi bi-upc-scan pos-scan-icon"></i>
                        <input type="text" id="pos_item_input" class="form-control"
                               placeholder="סרוק ברקוד / מק״ט / שם פריט..."
                               autocomplete="off" data-lpignore="true" spellcheck="false"
                               readonly onfocus="this.removeAttribute('readonly');">
                    </div>
                    <label class="form-label" style="margin-bottom:6px;">פריטים לתנועה</label>
                    <div class="pos-cart-wrap" id="posSelectedItems" style="max-height:320px;overflow-y:auto;">
                        <div class="pos-cart-empty">
                            <i class="bi bi-inbox" style="font-size:1.6rem;opacity:.25"></i>
                            <span style="font-size:12px">חפש או סרוק פריט להוסיף</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: form details -->
        <div class="col-lg-7">
            <div class="lab-card card-form h-100">
                <div class="lab-card-header">
                    <span class="lab-card-title"><i class="bi bi-pencil-square" style="color:var(--success)"></i> פרטי תנועה</span>
                </div>
                <div style="padding:.875rem;">
                    <form id="formMovement">
                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                        <div class="mov-form-grid mb-3">
                            <div>
                                <label class="form-label">קריאת שירות</label>
                                <input type="text" name="service_call_id" class="form-control" placeholder="מספר קריאה">
                            </div>
                            <div>
                                <label class="form-label">טכנאי <span style="color:var(--danger)">*</span></label>
                                <select name="technician_id" class="form-select" required>
                                    <option value="">בחר טכנאי...</option>
                                    <?php foreach ($technicians as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>"><?= View::e($t['first_name'] . ' ' . $t['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">הערות <span style="color:var(--danger)">*</span></label>
                                <input type="text" name="notes" id="movNotes" class="form-control" placeholder="תיאור קצר...">
                                <div class="mt-1 d-flex gap-1 flex-wrap" id="notes-presets">
                                    <button type="button" class="btn btn-sm btn-outline-warning fw-bold" id="btn-preset-tikun"
                                            style="font-size:11px;padding:2px 10px;border-radius:20px;">
                                        <i class="bi bi-tools me-1"></i>תיקון
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-notes-preset="החלפת חלף"
                                            style="font-size:11px;padding:2px 10px;border-radius:20px;">החלפת חלף</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-notes-preset="אחריות"
                                            style="font-size:11px;padding:2px 10px;border-radius:20px;">אחריות</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-notes-preset="בדיקה"
                                            style="font-size:11px;padding:2px 10px;border-radius:20px;">בדיקה</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-notes-preset="איפוס מלאי"
                                            style="font-size:11px;padding:2px 10px;border-radius:20px;">איפוס מלאי</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-notes-preset="אינטרנט"
                                            style="font-size:11px;padding:2px 10px;border-radius:20px;">אינטרנט</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-notes-preset="בית לקוח"
                                            style="font-size:11px;padding:2px 10px;border-radius:20px;">בית לקוח</button>

                                </div>
                                <input type="hidden" name="is_report_inventory" id="is_report_inventory" value="0">
                            </div>
                            <div>
                                <label class="form-label">מספר סידורי (SN)</label>
                                <input type="text" name="sNum" class="form-control" placeholder="אופציונלי">
                            </div>
                        </div>
                        <button type="submit" class="mov-submit-btn">
                            <i class="bi bi-check2-circle"></i> בצע תנועת מלאי
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div style="background:rgba(91,141,238,.05);border:1px solid rgba(91,141,238,.15);border-radius:6px;padding:12px 16px;margin-bottom:.875rem;display:flex;align-items:center;gap:10px;">
        <i class="bi bi-info-circle" style="color:var(--accent);flex-shrink:0"></i>
        <span style="color:var(--text2);font-size:13px;">אין הרשאה לביצוע הוצאת פריטים</span>
    </div>
    <?php endif; ?>

    <!-- movements table -->
    <div class="lab-card card-hist">
        <div class="lab-card-header">
            <span class="lab-card-title"><i class="bi bi-clock-history" style="color:var(--warning)"></i> היסטוריית תנועות</span>
            <div class="d-flex gap-2 align-items-center">
                <?php if ($isAdmin || $canReport): ?>
                <div class="form-check form-switch mb-0 d-flex align-items-center gap-1" id="filter-reported-wrap">
                    <input class="form-check-input" type="checkbox" id="filterReportedToggle" style="cursor:pointer">
                    <label class="form-check-label" for="filterReportedToggle" style="font-size:12px;color:var(--text3)">דווח בלבד</label>
                </div>
                <?php endif; ?>
                <?php if ($canReport): ?>
                <button class="btn btn-sm btn-outline-primary fw-bold" id="btnReportInventory" style="border-radius:20px;font-size:11.5px;">
                    <i class="bi bi-send-check me-1"></i>דיווח מלאי
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div style="overflow-x:auto;max-width:100%;">
            <table id="dtMovements" class="table" style="min-width:860px;">
                <thead><tr>
                    <th>תאריך</th><th>משתמש</th><th>מק"ט</th><th>פריט</th><th>כיוון</th>
                    <th>כמות</th><th>קריאה</th><th>טכנאי</th><th>הערה</th><th>SN</th><th>סטטוס</th><th>זמן דיווח</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── VIEW: INVENTORY ── -->
<div id="view-inventory" class="view-section">
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="lab-kpi" style="border-right:3px solid var(--accent)">
                <div class="lab-kpi-icon" style="background:rgba(91,141,238,.12);color:var(--accent)"><i class="bi bi-boxes"></i></div>
                <div><div class="lbl">סה"כ חלפים</div><div class="val" id="kpi-total-items">—</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="lab-kpi" style="border-right:3px solid var(--danger)">
                <div class="lab-kpi-icon" style="background:rgba(239,68,68,.12);color:var(--danger)"><i class="bi bi-exclamation-triangle"></i></div>
                <div><div class="lbl">מתחת למינימום</div><div class="val" id="kpi-low-stock">—</div></div>
            </div>
        </div>
    </div>

    <div class="lab-card card-inv">
        <div class="lab-card-header">
            <span class="lab-card-title"><i class="bi bi-box-seam" style="color:#06b6d4"></i> מצב מלאי</span>
        </div>
        <!-- filter bar -->
        <div class="lab-filter-bar">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">חיפוש</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="global_search" class="form-control" placeholder="מק״ט, שם, תאימות...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">יצרן</label>
                    <select id="filter_manufacturer" class="form-select"><option value="">הכל</option></select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">סטטוס מלאי</label>
                    <select id="filter_stock_status" class="form-select">
                        <option value="">הכל</option>
                        <option value="in_stock">במלאי</option>
                        <option value="low_stock">מתחת למינימום</option>
                        <option value="out_of_stock">חסר</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">מכמות</label>
                    <input type="number" id="filter_min_qty" class="form-control" placeholder="0">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="btn-clear-filters" class="btn w-100" onclick="resetFilters()">
                        <i class="bi bi-x-circle me-1"></i> נקה פילטרים
                    </button>
                </div>
            </div>
        </div>
        <div style="overflow-x:auto;max-width:100%;">
            <table id="dtInventory" class="table" style="min-width:900px;">
                <thead><tr>
                    <th>מק"ט</th><th>ברקוד</th><th>שם מוצר</th><th>יצרן</th><th>תאימות</th>
                    <th>כמות</th><th>בדרך</th><th>מינ׳</th><th>סטטוס</th><th>מיקום</th>
                    <th>עדכון</th><?php if($isAdmin): ?><th style="width:40px"></th><?php endif; ?>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── VIEW: ANALYTICS ── -->
<div id="view-analytics" class="view-section">
    <div class="lab-card card-ana">
        <div class="lab-card-header">
            <span class="lab-card-title"><i class="bi bi-bar-chart-line" style="color:#a855f7"></i> ניתוח נתוני מלאי</span>
        </div>
        <div style="padding:.875rem;overflow-x:auto;">
            <div class="pivot-container" id="pivot_capture_area">
                <div id="pivot_output" style="color:var(--text3);text-align:center;padding:2.5rem 0;">
                    <i class="bi bi-hourglass-split" style="font-size:1.5rem;opacity:.25;display:block;margin-bottom:8px;"></i>
                    מעבד נתונים...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── VIEW: USERS ── -->
<div id="view-users" class="view-section">
    <div class="lab-card card-users">
        <div class="lab-card-header">
            <span class="lab-card-title"><i class="bi bi-people-fill" style="color:#ec4899"></i> ניהול צוות מעבדה</span>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus me-1"></i>הוסף טכנאי
            </button>
        </div>
        <div style="width:100%;overflow:hidden;max-width:100%;">
            <table id="dtLabUsers" class="table" style="table-layout:fixed;width:100%!important;">
                <thead><tr>
                    <th style="width:22%">שם מלא</th>
                    <th style="width:30%">מייל</th>
                    <th style="width:22%">כניסה אחרונה</th>
                    <th style="width:14%;text-align:center">סטטוס</th>
                    <th style="width:12%;text-align:center">פעולות</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════
     OFFCANVAS — ADD / EDIT ITEM
═══════════════════════════════════════════════ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="drawerItem" aria-labelledby="drawerTitle" style="width:480px">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="drawerTitle"><i class="bi bi-box-seam me-2" style="color:var(--accent)"></i>הוספת פריט חדש</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" style="padding:1.25rem;overflow-y:auto;">
        <form id="formItem">
            <input type="hidden" name="_csrf"   value="<?= View::e($csrf) ?>">
            <input type="hidden" id="frm_id"     name="id">
            <input type="hidden" id="frm_action" name="action" value="create">

            <div id="qty_help" class="d-none mb-3" style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:var(--success);border-radius:8px;padding:10px 14px;font-size:13px;font-weight:600;"></div>

            <!-- section: identity -->
            <div class="form-section mb-3">
                <div class="form-section-title"><i class="bi bi-fingerprint"></i> זיהוי פריט</div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">מק"ט <span style="color:var(--danger)">*</span></label>
                        <input type="text" class="form-control" id="frm_part" name="part_number" required placeholder="ABC-123">
                    </div>
                    <div class="col-6">
                        <label class="form-label">ברקוד</label>
                        <input type="text" class="form-control" id="frm_barcode" name="barcode" placeholder="אופציונלי">
                    </div>
                    <div class="col-12">
                        <label class="form-label">שם פריט <span style="color:var(--danger)">*</span></label>
                        <input type="text" class="form-control" id="frm_name" name="product_name_en" required placeholder="תיאור הפריט...">
                    </div>
                    <div class="col-6">
                        <label class="form-label">יצרן</label>
                        <input type="text" class="form-control" id="frm_manuf" name="manufacturer" placeholder="Samsung, Apple...">
                    </div>
                    <div class="col-6">
                        <label class="form-label">דגם</label>
                        <input type="text" class="form-control" id="frm_model" name="model">
                    </div>
                    <div class="col-12">
                        <label class="form-label">תאימות</label>
                        <input type="text" class="form-control" id="frm_compat" name="compatibility" placeholder="iPhone 13, Galaxy S22...">
                    </div>
                </div>
            </div>

            <!-- section: stock -->
            <div class="form-section mb-3">
                <div class="form-section-title"><i class="bi bi-boxes"></i> מלאי ומיקום</div>
                <div class="row g-2">
                    <div class="col-4">
                        <label class="form-label">כמות <span style="color:var(--danger)">*</span></label>
                        <input type="number" class="form-control text-center fw-bold" id="frm_qty" name="qty" required style="border-color:var(--accent)!important;font-size:1.1rem;">
                        <div class="d-none mt-1" id="qty_reduce_warn" style="color:var(--warning);font-size:11px;"><i class="bi bi-info-circle"></i> הוצאה — דרך תנועות בלבד</div>
                    </div>
                    <div class="col-4">
                        <label class="form-label">מינימום <i class="bi bi-bell" style="color:var(--warning)"></i></label>
                        <input type="number" class="form-control text-center" id="frm_min" name="min_qty" placeholder="0" style="border-color:rgba(245,158,11,.4)!important;">
                    </div>
                    <div class="col-4">
                        <label class="form-label">מחיר ₪</label>
                        <input type="number" step="0.01" class="form-control" id="frm_price" name="price_store">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="bi bi-geo-alt me-1"></i> מיקום</label>
                        <input type="text" class="form-control" id="frm_loc" name="location" placeholder="ארון ב׳ מדף 3...">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="bi bi-tags me-1"></i> תגיות</label>
                        <input type="text" class="form-control" id="frm_tags" name="tags" placeholder="כבל, USB, תיקון...">
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:10px;padding-top:4px;">
                <button type="button" class="btn btn-ghost flex-shrink-0" data-bs-dismiss="offcanvas">ביטול</button>
                <button type="submit" class="drawer-save-btn"><i class="bi bi-check2-circle me-2"></i>שמור פריט</button>
            </div>
        </form>

        <div class="section-sep"></div>
        <div style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.07em;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-clock-history"></i> היסטוריית פריט
        </div>
        <table class="table table-sm" id="dtItemHistory" style="font-size:12px;">
            <thead><tr><th>תאריך</th><th>סוג</th><th>פרטים</th><th>משתמש</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- ════════════════════════════════════════════
     MODAL — Update cart item qty
═══════════════════════════════════════════════ -->
<div class="modal fade" id="updateCartItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg">
            <div class="modal-header mh-accent">
                <h6 class="modal-title fw-bold" style="color:var(--accent)"><i class="bi bi-cart-plus me-2"></i>עדכון כמות</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div style="background:var(--bg3);border-radius:8px;padding:12px;margin-bottom:16px;">
                    <div style="font-size:13px;font-weight:700;color:var(--accent)" id="upd_name"></div>
                    <div style="font-size:11px;color:var(--text3);margin-top:4px;">
                        מק"ט: <span id="upd_part" class="number-font"></span> &nbsp;·&nbsp;
                        מלאי: <span id="upd_stock" style="color:var(--success);font-weight:700"></span> &nbsp;·&nbsp;
                        בעגלה: <span id="upd_current_cart" style="color:var(--accent);font-weight:700"></span>
                        <span id="upd_barcode" style="display:none"></span>
                    </div>
                </div>
                <label class="form-label text-center d-block">כמות להוספה</label>
                <input type="number" id="upd_add_qty" class="form-control form-control-lg text-center fw-bold" value="1" min="1" style="font-size:1.5rem;border-color:var(--accent)!important;">
                <div style="font-size:11px;color:var(--text3);text-align:center;margin-top:6px;">Enter לאישור מהיר</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">ביטול</button>
                <button type="button" class="btn btn-primary fw-bold px-4" id="btnConfirmCartUpdate">הוסף לעגלה</button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════
     MODAL — Excel import
═══════════════════════════════════════════════ -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header mh-success">
                <h5 class="modal-title fw-bold" style="color:var(--success)">
                    <i class="bi bi-file-earmark-excel me-2"></i>ייבוא מאקסל
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <label class="form-label">בחר קובץ (.xlsx / .csv)</label>
                <input type="file" id="excelFile" class="form-control" accept=".xlsx,.xls,.csv" style="padding:.6rem;">
                <div id="mappingContainer" class="mt-3" style="display:none;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:14px;"></div>
            </div>
            <div class="modal-footer">
                <button id="uploadMappedData" class="btn btn-primary w-100 fw-bold" style="border-radius:50px;display:none;padding:.75rem;">
                    <i class="bi bi-cloud-arrow-up me-2"></i>התחל ייבוא
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════
     MODAL — Manager override
═══════════════════════════════════════════════ -->
<div class="modal fade" id="overrideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg">
            <div class="modal-header mh-danger" style="border-bottom:1px solid rgba(239,68,68,.2)!important">
                <h6 class="modal-title fw-bold" style="color:var(--danger)"><i class="bi bi-shield-lock me-2"></i>אישור חריגת מלאי</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div style="width:56px;height:56px;background:rgba(245,158,11,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                    <i class="bi bi-exclamation-triangle" style="font-size:1.6rem;color:var(--warning)"></i>
                </div>
                <p style="color:var(--text2);font-size:13px;margin-bottom:16px;">הכמות גדולה מהמלאי הקיים.<br>הזן סיסמת מנהל להמשך:</p>
                <input type="password" id="overridePassword" class="form-control text-center fw-bold mb-3" placeholder="סיסמה" style="font-size:1.1rem;letter-spacing:.1em;">
                <button type="button" class="btn btn-danger w-100 fw-bold" id="btnConfirmOverride" style="border-radius:50px;padding:.7rem;">אשר חריגה</button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════
     MODAL — Add Lab User
═══════════════════════════════════════════════ -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formAddLabUser" class="modal-content shadow-lg">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2" style="color:var(--accent)"></i>הוספת טכנאי</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">שם פרטי</label>
                        <input type="text" name="fName" class="form-control" required placeholder="ישראל">
                    </div>
                    <div class="col-6">
                        <label class="form-label">שם משפחה</label>
                        <input type="text" name="lName" class="form-control" required placeholder="ישראלי">
                    </div>
                    <div class="col-12">
                        <label class="form-label">אימייל</label>
                        <input type="email" name="email" class="form-control" required placeholder="israel@company.com">
                    </div>
                    <div class="col-12">
                        <label class="form-label">סיסמה</label>
                        <input type="password" name="password" class="form-control" required placeholder="לפחות 6 תווים">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">ביטול</button>
                <button type="submit" class="btn btn-primary px-4 fw-bold">הוסף טכנאי</button>
            </div>
        </form>
    </div>
</div>

<!-- ════════════════════════════════════════════
     MODAL — דיווח מלאי
═══════════════════════════════════════════════ -->
<div class="modal fade" id="reportInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg">
            <div class="modal-header" style="border-bottom:1px solid var(--border)">
                <h5 class="modal-title fw-bold" style="color:var(--accent)">
                    <i class="bi bi-send-check me-2"></i>דיווח מלאי — פריטים לדיווח
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="reportPreviewLoading" class="text-center p-4" style="color:var(--text3)">
                    <div class="spinner-border spinner-border-sm me-2"></div>טוען נתונים...
                </div>
                <div id="reportPreviewContent" style="display:none">
                    <div style="padding:.75rem 1rem;background:var(--bg3);border-bottom:1px solid var(--border);font-size:12px;color:var(--text3)">
                        סמן/בטל סימון שורות לכלול בדיווח. שורות מסומנות יכללו בקובץ האקסל ויסומנו כדווחו.
                    </div>
                    <div style="overflow-x:auto">
                        <table class="table" id="tblReportPreview" style="margin-bottom:0">
                            <thead><tr>
                                <th style="width:38px;text-align:center">
                                    <input type="checkbox" id="chkAllReport" checked title="בחר הכל">
                                </th>
                                <th>מקט</th>
                                <th>תיאור</th>
                                <th>תאימות</th>
                                <th class="text-center">כמות</th>
                                <th class="text-center">קריאה</th>
                            </tr></thead>
                            <tbody id="reportPreviewBody"></tbody>
                        </table>
                    </div>
                </div>
                <div id="reportPreviewEmpty" style="display:none;padding:2rem;text-align:center;color:var(--text3)">
                    <i class="bi bi-inbox" style="font-size:2rem;opacity:.3;display:block;margin-bottom:8px"></i>
                    אין פריטים לדיווח. לחץ "תיקון" בטופס תנועה כדי לסמן פריטים.
                </div>
            </div>
            <div class="modal-footer" id="reportPreviewFooter" style="display:none">
                <span style="font-size:12px;color:var(--text3);flex:1" id="reportPreviewCount"></span>
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">ביטול</button>
                <button type="button" class="btn btn-primary fw-bold px-4" id="btnDownloadReport">
                    <i class="bi bi-file-earmark-excel me-2"></i>הורד אקסל
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════ -->
<script>
const LAB_CSRF       = '<?= $csrf ?>';
const LAB_BASE       = '<?= $base ?>';
const LAB_CAN_REPORT = <?= $canReport ? 'true' : 'false' ?>;
const LAB_GROUP   = <?= $permGroup ?>;
const LAB_ADMIN   = <?= $isAdmin ? 'true' : 'false' ?>;

/* ── helpers ─────────────────────────────────────────────────────────────── */
function labToast(msg, type = 'success') {
    if (typeof v2Toast === 'function') { v2Toast(msg); return; }
    // fallback
    if ($('.lab-toast-container').length === 0)
        $('body').append('<div class="lab-toast-container position-fixed bottom-0 start-0 p-3" style="z-index:9999;"></div>');
    const colors = { success:'#22c55e', danger:'#ef4444', warning:'#f59e0b', info:'#06b6d4' };
    const c = colors[type] || colors.success;
    const $t = $(`<div style="background:var(--bg4,#1a1e2b);border:1px solid ${c};color:#e2e5f0;border-radius:8px;padding:10px 18px;margin-bottom:8px;box-shadow:0 4px 24px rgba(0,0,0,.4);font-size:14px;font-weight:600">${msg}</div>`);
    $('.lab-toast-container').append($t);
    setTimeout(() => $t.fadeOut(300, function(){ $(this).remove(); }), 4000);
}

/* csrf header for fetch */
function labFetch(url, opts = {}) {
    opts.headers = { ...(opts.headers || {}), 'X-CSRF-Token': LAB_CSRF };
    return fetch(url, opts);
}

/* ── App ─────────────────────────────────────────────────────────────────── */
const App = {
    state: { inventoryData: [], posCart: [], dtInv: null, dtMov: null, pivotLoaded: false },

    switchTab(target, save = true) {
        if (!$(target).length) return;
        $('.lab-tab').removeClass('active');
        $(`.lab-tab[data-target="${target}"]`).addClass('active');
        $('.view-section').removeClass('active-view');
        $(target).addClass('active-view');

        const isInv   = target === '#view-inventory';
        const isAna   = target === '#view-analytics';
        const isUsers = target === '#view-users';
        const isMov   = target === '#view-movements';

        $('#inventory-actions').css('display', isInv  ? 'flex' : 'none');
        $('#analytics-actions').css('display', isAna  ? 'flex' : 'none');

        if (isUsers) loadLabUsersTable();
        if (isInv  && App.state.dtInv)  App.state.dtInv.columns.adjust();
        if (isMov  && App.state.dtMov)  App.state.dtMov.columns.adjust();
        if (isAna && !App.state.pivotLoaded) { Analytics.load(); App.state.pivotLoaded = true; }

        if (save) { try { localStorage.setItem('lab_active_tab', target); } catch(e){} }
    },

    init() {
        // tab switching
        $(document).on('click', '.lab-tab', function() {
            App.switchTab($(this).data('target'));
        });

        // restore last tab from localStorage
        const LAB_TABS_ALLOWED = ['#view-movements','#view-inventory','#view-analytics','#view-users'];
        let savedTab = null;
        try { savedTab = localStorage.getItem('lab_active_tab'); } catch(e){}
        const defaultTab = <?= $isAdmin ? "'#view-movements'" : "'#view-inventory'" ?>;
        const tabToShow = (savedTab && LAB_TABS_ALLOWED.includes(savedTab) && $(savedTab).length) ? savedTab : defaultTab;
        App.switchTab(tabToShow, false);

        Inventory.init();
        Movements.init();
    },

    openAddDrawer() {
        $('#formItem')[0].reset();
        $('#frm_id').val('');
        $('#frm_action').val('create');
        $('#frm_name, #frm_manuf, #frm_model').prop('readonly', false);
        $('#qty_help').addClass('d-none');
        $('#drawerTitle').html('<i class="bi bi-box-seam me-2"></i>הוספת פריט חדש');
        $('#frm_qty').attr('min', '0');
        $('#qty_reduce_warn').addClass('d-none');
        bootstrap.Offcanvas.getOrCreateInstance('#drawerItem').show();
    }
};

/* ── Inventory ───────────────────────────────────────────────────────────── */
const Inventory = {
    init() {
        App.state.dtInv = $('#dtInventory').DataTable({
            dom: "<'row'<'col-sm-12 col-md-6 d-flex align-items-center gap-2'B><'col-sm-12 col-md-6 d-flex justify-content-end'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
            buttons: [{ extend:'excelHtml5', text:'<i class="bi bi-file-earmark-excel"></i>',
                titleAttr:'ייצוא לאקסל',
                className:'dt-button',
                title:'מלאי מעבדה - '+new Date().toLocaleDateString('he-IL'), exportOptions:{columns:':visible'} }],
            ajax: {
                url: LAB_BASE + '/api/lab/inventory',
                dataSrc(json) {
                    App.state.inventoryData = json;
                    $('#kpi-total-items').text(json.length);
                    $('#kpi-low-stock').text(json.filter(r => parseInt(r.qty) <= parseInt(r.min_qty)).length);
                    return json;
                }
            },
            deferRender: true, pageLength: 25, order: [[10,'desc']], autoWidth: false,
            language: { url:'//cdn.datatables.net/plug-ins/2.3.2/i18n/he.json' },
            columns: [
                { data:'part_number', className:'fw-bold number-font', render: d => `<span style="color:var(--accent)">${d}</span>` },
                { data:'barcode' },
                { data:'product_name_en' },
                { data:'manufacturer' },
                { data:'compatibility', defaultContent:'' },
                { data:'qty', className:'text-center', render(d,t,r) {
                    const n = parseInt(d), low = n <= (parseInt(r.min_qty)||0);
                    const cls = n <= 0 ? 'lbadge-red' : low ? 'lbadge-yellow' : 'lbadge-green';
                    return `<span class="lbadge ${cls} number-font">${d}</span>`;
                }},
                { data:'incoming_qty', className:'text-center', render(d) {
                    return parseInt(d)>0 ? `<span class="lbadge lbadge-cyan"><i class="bi bi-truck"></i> ${d}</span>` : `<span style="color:var(--text3)">—</span>`;
                }},
                { data:'min_qty', className:'text-center number-font' },
                { data:null, render(data,type,row) {
                    const cur = parseInt(row.qty)||0, inc = parseInt(row.incoming_qty)||0, min = parseInt(row.min_qty)||0;
                    const net = cur+inc, toOrder = min>0 ? min-net : 0;
                    if (toOrder>0) return `<span class="lbadge lbadge-red"><i class="bi bi-cart-x"></i> להזמין ${toOrder}</span>`;
                    if (cur<=0 && net>0) return `<span class="lbadge lbadge-yellow"><i class="bi bi-hourglass-split"></i> ממתין למשלוח</span>`;
                    return `<span class="lbadge lbadge-green"><i class="bi bi-check2"></i> תקין</span>`;
                }},
                { data:'location', render:d=>d?`<span style="color:var(--text2)">${d}</span>`:'<span style="color:var(--text3)">—</span>' },
                { data:'updated_at', className:'nowrap', render:(d,t)=>t==='display'&&d?new Date(d).toLocaleDateString('he-IL'):d },
                { data:null, orderable:false, className:'text-center', render(data,type,row) {
                    if (!LAB_ADMIN) return '';
                    return `<button class="btn-icon" onclick="Inventory.edit(${row.id})" title="עריכה" style="color:#06b6d4;border-color:rgba(6,182,212,.25)"><i class="bi bi-pencil"></i></button>`;
                }}
            ]
        });

        // advanced filters
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'dtInventory') return true;
            const status = $('#filter_stock_status').val();
            const minIn  = parseInt($('#filter_min_qty').val());
            const qty    = parseInt(data[5]);
            const minReq = parseInt(data[7]);
            if (status === 'in_stock'    && qty <= 0)     return false;
            if (status === 'out_of_stock'&& qty > 0)      return false;
            if (status === 'low_stock'   && qty > minReq) return false;
            if (!isNaN(minIn)            && qty < minIn)  return false;
            return true;
        });

        $('#global_search').on('keyup', function(){ App.state.dtInv.search(this.value).draw(); });
        $('#filter_manufacturer').on('change', function(){ App.state.dtInv.column(3).search(this.value).draw(); });
        $('#filter_stock_status, #filter_min_qty').on('change keyup', function(){ App.state.dtInv.draw(); });
        App.state.dtInv.on('draw', function(){
            const mfrs = App.state.dtInv.column(3).data().unique().sort();
            const cur  = $('#filter_manufacturer').val();
            $('#filter_manufacturer').empty().append('<option value="">כל היצרנים</option>');
            mfrs.each(d => { if (d) $('#filter_manufacturer').append(`<option value="${d}">${d}</option>`); });
            $('#filter_manufacturer').val(cur);
        });

        const updateClearBtn = () => {
            const active = $('#global_search').val() || $('#filter_min_qty').val() ||
                           $('#filter_manufacturer').val() || $('#filter_stock_status').val();
            $('#btn-clear-filters').toggleClass('has-filter', !!active);
        };
        $('#global_search, #filter_min_qty, #filter_manufacturer, #filter_stock_status').on('input change', updateClearBtn);

        window.resetFilters = function() {
            $('#global_search, #filter_min_qty').val('');
            $('#filter_manufacturer, #filter_stock_status').val('');
            App.state.dtInv.search('').column(3).search('').draw();
            updateClearBtn();
        };

        this.initAutocomplete();

        $('#formItem').submit(function(e) {
            e.preventDefault();
            const action = $('#frm_action').val();
            const url    = action === 'create' ? LAB_BASE+'/api/lab/item/add' : LAB_BASE+'/api/lab/item/update';
            let data = $(this).serializeArray();
            if (action === 'create') data.push({ name:'qty_to_add', value:$('#frm_qty').val() });
            $.post(url, $.param(data), res => {
                if (res.success) {
                    labToast('נשמר בהצלחה');
                    bootstrap.Offcanvas.getOrCreateInstance('#drawerItem').hide();
                    App.state.dtInv.ajax.reload(null, false);
                    if (App.state.dtMov) App.state.dtMov.ajax.reload(null, false);
                } else { labToast(res.message||'שגיאה', 'danger'); }
            }, 'json');
        });
    },

    edit(id) {
        let item = App.state.inventoryData.find(i => Number(i.id) === Number(id));
        if (!item) return;
        const cur = item.qty || 0;
        $('#frm_qty').val(cur).attr('min', cur);
        $('#qty_reduce_warn').removeClass('d-none');
        $('#frm_action').val('update');
        $('#frm_id').val(item.id);
        $('#frm_part').val(item.part_number||'');
        $('#frm_barcode').val(item.barcode||'');
        $('#frm_name').val(item.product_name_en||'');
        $('#frm_manuf').val(item.manufacturer||'');
        $('#frm_model').val(item.model||'');
        $('#frm_compat').val(item.compatibility||'');
        $('#frm_loc').val(item.location||'');
        $('#frm_price').val(item.price_store||'');
        $('#frm_min').val(item.min_qty||0);
        $('#frm_tags').val(item.tags||'');
        $('#drawerTitle').html('<i class="bi bi-pencil-square me-2"></i>עריכת פריט');
        $('#qty_help').addClass('d-none');

        const $hBody = $('#dtItemHistory tbody');
        $hBody.html('<tr><td colspan="4" class="text-center" style="color:var(--text3)">טוען...</td></tr>');

        $.when(
            $.get(LAB_BASE+'/api/lab/history'),
            $.get(LAB_BASE+'/api/lab/item/logs?item_id='+item.id)
        ).done(function(movRes, logRes) {
            const movements = (movRes[0]||[]).filter(l => l.part_number === item.part_number);
            const fieldLogs = logRes[0]||[];

            const rows = [];
            movements.forEach(m => rows.push({ ts: new Date(m.date).getTime(), type: 'mov', data: m }));
            fieldLogs.forEach(l => rows.push({ ts: new Date(l.changed_at).getTime(), type: 'log', data: l }));
            rows.sort((a,b) => b.ts - a.ts);

            $hBody.empty();
            if (!rows.length) {
                $hBody.html('<tr><td colspan="4" class="text-center" style="color:var(--text3)">אין היסטוריה</td></tr>');
                return;
            }
            rows.forEach(r => {
                if (r.type === 'mov') {
                    const m = r.data;
                    const isIn = (m.direction||'').toUpperCase() === 'IN';
                    const badge = isIn
                        ? '<span class="badge bg-success-subtle" style="font-size:10px">הכנסה</span>'
                        : '<span class="badge bg-danger-subtle"  style="font-size:10px">הוצאה</span>';
                    $hBody.append(`<tr>
                        <td style="white-space:nowrap;color:var(--text3)">${new Date(m.date).toLocaleDateString('he-IL')}</td>
                        <td>${badge}</td>
                        <td><strong>${m.qty}</strong> יח'</td>
                        <td>${m.username||''}</td>
                    </tr>`);
                } else {
                    const l = r.data;
                    const before = l.old_value||'—';
                    const after  = l.new_value||'—';
                    $hBody.append(`<tr>
                        <td style="white-space:nowrap;color:var(--text3)">${new Date(l.changed_at).toLocaleDateString('he-IL')}</td>
                        <td><span class="badge" style="background:var(--bg4);color:var(--text2);font-size:10px">✏️ ${l.field_name}</span></td>
                        <td style="direction:ltr"><span style="color:var(--danger);text-decoration:line-through">${before}</span> → <span style="color:var(--success)">${after}</span></td>
                        <td>${l.username||''}</td>
                    </tr>`);
                }
            });
        });

        bootstrap.Offcanvas.getOrCreateInstance('#drawerItem').show();
    },

    openExcelModal() {
        const m = bootstrap.Modal.getOrCreateInstance('#uploadModal');
        m.show();
    },

    initAutocomplete() {
        const sourceLogic = (req, res) => {
            const term = req.term.toLowerCase();
            const matches = App.state.inventoryData.filter(i =>
                (i.part_number||'').toLowerCase().includes(term)||
                (i.barcode||'').toLowerCase().includes(term)||
                (i.product_name_en||'').toLowerCase().includes(term)
            ).slice(0, 10);
            res(matches.map(i => ({ value:i.part_number, item:i })));
        };
        const onSelect = (event, ui) => {
            const item = ui.item.item;
            $('#frm_id').val(item.id);
            $('#frm_part').val(item.part_number);
            $('#frm_barcode').val(item.barcode);
            $('#frm_name').val(item.product_name_en).prop('readonly', true);
            $('#frm_manuf').val(item.manufacturer).prop('readonly', true);
            $('#frm_model').val(item.model).prop('readonly', true);
            $('#frm_compat').val(item.compatibility||'');
            $('#frm_loc').val(item.location);
            $('#frm_min').val(item.min_qty);
            $('#frm_price').val(item.price_store);
            $('#frm_qty').val(1);
            $('#frm_action').val('create');
            $('#qty_help').removeClass('d-none').html('<span style="color:var(--success);font-weight:700"><i class="bi bi-info-circle me-1"></i>פריט קיים! הכמות תתווסף למלאי.</span>');
            $('#drawerTitle').text('הוספת מלאי לפריט קיים');
            return false;
        };
        const renderItem = (ul, item) => {
            const d = item.item;
            const stockCls = d.qty > 0 ? 'color:var(--success)' : 'color:var(--danger)';
            return $('<li>').append(
                $('<div class="ui-menu-item-wrapper p-2 border-bottom"></div>').append(`
                    <div style="display:flex;justify-content:space-between;align-items:center;width:100%">
                        <div><span style="font-weight:700;color:var(--text)">${d.part_number}</span>
                        <div style="font-size:.8rem;color:var(--text3)">${d.product_name_en}</div></div>
                        <span style="${stockCls};font-weight:700">${d.qty > 0 ? 'מלאי: '+d.qty : 'חסר!'}</span>
                    </div>`)
            ).appendTo(ul);
        };
        ['#frm_part','#frm_barcode'].forEach(sel => {
            const $ac = $(sel).autocomplete({ source:sourceLogic, minLength:2, select:onSelect });
            if ($ac.data('ui-autocomplete')) $ac.data('ui-autocomplete')._renderItem = renderItem;
        });
        $('#frm_part, #frm_barcode').on('input', function() {
            if (!this.value) {
                $('#frm_id').val('');
                $('#frm_name, #frm_manuf, #frm_model').prop('readonly', false).val('');
                $('#qty_help').addClass('d-none');
                $('#drawerTitle').text('הוספת פריט חדש');
            }
        });
    }
};

/* ── Movements ───────────────────────────────────────────────────────────── */
const Movements = {
    init() {
        App.state.dtMov = $('#dtMovements').DataTable({
            dom: "<'row'<'col-sm-12 col-md-6 d-flex align-items-center gap-2'B><'col-sm-12 col-md-6 d-flex justify-content-end'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
            buttons: [{ extend:'excelHtml5', text:'<i class="bi bi-file-earmark-excel"></i>',
                titleAttr:'ייצוא לאקסל',
                className:'dt-button',
                title:'תנועות מלאי - '+new Date().toLocaleDateString('he-IL'), exportOptions:{columns:':visible'} }],
            ajax: {
                url: LAB_BASE+'/api/lab/history',
                dataSrc(json) { Movements.calculateInsights(json); return json; }
            },
            deferRender:true, order:[[0,'desc']], pageLength:50,
            lengthMenu:[[25,50,100,-1],['25','50','100','הכל']],
            language:{ url:'//cdn.datatables.net/plug-ins/2.3.2/i18n/he.json' },
            autoWidth:false,
            columns:[
                { data:'date',          className:'text-center nowrap', width:'128px', render:(d,t)=>t==='display'?new Date(d).toLocaleString('he-IL'):d },
                { data:'username',      className:'text-center', width:'88px' },
                { data:'part_number',   className:'text-center number-font fw-bold', width:'92px' },
                { data:'product_name_en', width:'170px' },
                { data:'direction', className:'text-center', width:'76px', render(d) {
                    const isIn = (d||'').toUpperCase() === 'IN';
                    return isIn ? `<span class="lbadge lbadge-blue"><i class="bi bi-arrow-down-left"></i> הכנסה</span>`
                                : `<span class="lbadge lbadge-yellow"><i class="bi bi-arrow-up-right"></i> הוצאה</span>`;
                }},
                { data:'qty',           className:'text-center number-font', width:'54px' },
                { data:'service_call_id', className:'text-center number-font', width:'72px' },
                { data:'technician',    className:'text-center', width:'88px', defaultContent:'---' },
                { data:'notes',         width:'140px' },
                { data:'serial_number', className:'text-center number-font', width:'108px', defaultContent:'' },
                { data:'status', className:'text-center', width:'108px', render(d,t,row) {
                    if (d === 'pending') {
                        if (LAB_ADMIN) return `<div style="display:flex;align-items:center;gap:6px;justify-content:center">
                            <span class="lbadge lbadge-yellow">ממתין</span>
                            <button class="btn-icon" onclick="approveMove(${row.id})" title="אשר" style="color:var(--success);border-color:rgba(34,197,94,.3)"><i class="bi bi-check-lg"></i></button>
                        </div>`;
                        return '<span class="lbadge lbadge-yellow">ממתין לאישור</span>';
                    }
                    return d === 'approved' ? '<span class="lbadge lbadge-green"><i class="bi bi-check2"></i> מאושר</span>' : (d||'');
                }},
                { data:'reported_at', className:'text-center nowrap', width:'110px', defaultContent:'',
                  render:(d,t)=> {
                    if (!d) return '';
                    if (t !== 'display') return d;
                    return `<span class="lbadge lbadge-green" style="font-size:10px"><i class="bi bi-check2-circle me-1"></i>${new Date(d).toLocaleString('he-IL')}</span>`;
                  }
                }
            ],
            createdRow(row, data) {
                const dir = (data.direction||'').toUpperCase();
                if (dir === 'OUT') $(row).addClass('row-out');
                else if (dir === 'IN') $(row).addClass('row-in');
            }
        });

        // POS autocomplete — supports barcode scan, part number, name
        const input = document.getElementById('pos_item_input');
        if (input) {
            const awe = new Awesomplete(input, {
                minChars: 1,
                autoFirst: true,
                list: [],
                filter: () => true,
                replace(s) { this.input.value = ''; },
                item(text) {
                    const li = document.createElement('li');
                    li.innerHTML = text.label;
                    return li;
                }
            });

            const updateList = (term) => {
                const t = term.trim().toLowerCase();
                if (!t) { awe.close(); return; }
                const results = App.state.inventoryData.filter(i =>
                    (i.part_number||'').toLowerCase().includes(t) ||
                    (i.barcode||'').toLowerCase().includes(t) ||
                    (i.product_name_en||'').toLowerCase().includes(t)
                ).slice(0, 12);
                awe.list = results.map(i => {
                    const qBadge = i.qty > 0
                        ? `<span class="lbadge lbadge-green" style="flex-shrink:0">${i.qty}</span>`
                        : `<span class="lbadge lbadge-red" style="flex-shrink:0">חסר</span>`;
                    return {
                        label: `<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;width:100%">
                            <div style="min-width:0">
                              <div style="font-size:12.5px;font-weight:600;color:var(--text);font-family:'IBM Plex Mono',monospace;white-space:nowrap">${i.part_number||'—'}</div>
                              <div style="font-size:11.5px;color:var(--text3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:260px">${i.product_name_en}</div>
                            </div>${qBadge}</div>`,
                        value: String(i.id)
                    };
                });
                if (results.length) awe.open(); else awe.close();
            };

            input.addEventListener('input', function() { updateList(this.value); });

            // barcode scan: scanner types fast and hits Enter — catch it before form submit
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && awe.ul && awe.ul.childElementCount === 0) {
                    // direct barcode match
                    const term = this.value.trim();
                    const exact = App.state.inventoryData.find(i =>
                        (i.barcode||'').toLowerCase() === term.toLowerCase() ||
                        (i.part_number||'').toLowerCase() === term.toLowerCase()
                    );
                    if (exact) { Movements.addToCart(exact); this.value = ''; e.preventDefault(); }
                }
            });

            input.addEventListener('awesomplete-selectcomplete', function(e) {
                const item = App.state.inventoryData.find(i => String(i.id) === String(e.text.value));
                if (item) Movements.addToCart(item);
                this.value = '';
                awe.close();
            });
        }

        // form submit
        $('#formMovement').submit(function(e) {
            e.preventDefault();
            const techId = $(this).find('[name="technician_id"]').val();
            if (!techId) { labToast('חובה לבחור טכנאי!', 'danger'); return; }
            const notes = $(this).find('[name="notes"]').val().trim();
            if (notes.length < 5) { labToast('חובה להזין הערה (5 תווים לפחות)', 'danger'); return; }
            if (!App.state.posCart.length) { labToast('העגלה ריקה', 'warning'); return; }

            const doMove = () => {
                let data = $(this).serializeArray();
                App.state.posCart.forEach(i => {
                    data.push({name:'item_id[]', value:i.id});
                    data.push({name:'item_qty[]', value:i.pull_qty});
                });
                const $btn = $(this).find('[type="submit"]');
                const orig = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>עובד...');
                $.post(LAB_BASE+'/api/lab/movement', $.param(data), res => {
                    $btn.prop('disabled', false).html(orig);
                    if (res.success) {
                        labToast(res.message||'בוצע!');
                        App.state.posCart = [];
                        Movements.renderCart();
                        $('#formMovement')[0].reset();
                        $('#is_report_inventory').val('0');
                        $('#btn-preset-tikun').removeClass('active btn-danger').addClass('btn-outline-danger');
                        $('[data-notes-preset]').removeClass('active btn-secondary').addClass('btn-outline-secondary');
                        if (App.state.dtMov) App.state.dtMov.ajax.reload(null, false);
                        if (App.state.dtInv) App.state.dtInv.ajax.reload(null, false);
                    } else { labToast(res.message||'שגיאה', 'danger'); }
                }, 'json').fail(() => { $btn.prop('disabled',false).html(orig); labToast('שגיאת תקשורת','danger'); });
            };

            if (App.state.posCart.some(i => Number(i.pull_qty) > Number(i.stock))) {
                $('#overridePassword').val('');
                let m = bootstrap.Modal.getInstance(document.getElementById('overrideModal'));
                if (m) m.dispose();
                m = new bootstrap.Modal('#overrideModal', {backdrop:'static', keyboard:false});
                m.show();
                $('#btnConfirmOverride').off('click').on('click', () => {
                    const d = new Date();
                    const pass = String(d.getDate()).padStart(2,'0')+String(d.getMonth()+1).padStart(2,'0')+d.getFullYear();
                    if ($('#overridePassword').val() === pass) { m.hide(); setTimeout(doMove.bind(this), 500); }
                    else labToast('סיסמה שגויה!','danger');
                });
            } else { doMove.call(this); }
        });
    },

    calculateInsights(data) {
        if (!data?.length) return;
        const now = new Date(), todayStr = now.toISOString().split('T')[0];
        const cm = now.getMonth(), cy = now.getFullYear();
        let todayCount=0, outQty=0, itemsMap={}, techMap={}, callsSet=new Set();
        data.forEach(r => {
            if (!r.date) return;
            const d = new Date(r.date);
            if (r.date.split(' ')[0] === todayStr) todayCount++;
            if (d.getMonth()===cm && d.getFullYear()===cy) {
                if ((r.direction||'').toUpperCase() === 'OUT') {
                    outQty += parseInt(r.qty)||0;
                    const tech = r.technician||'לא שויך';
                    techMap[tech] = (techMap[tech]||0)+1;
                    if (r.service_call_id?.trim()) callsSet.add(r.service_call_id);
                }
                const item = r.product_name_en||'לא ידוע';
                itemsMap[item] = (itemsMap[item]||0)+(parseInt(r.qty)||1);
            }
        });
        const topItem = Object.keys(itemsMap).length ? Object.keys(itemsMap).reduce((a,b)=>itemsMap[a]>itemsMap[b]?a:b) : '---';
        const topTech = Object.keys(techMap).length  ? Object.keys(techMap).reduce((a,b)=>techMap[a]>techMap[b]?a:b)   : '---';
        $('#mov-kpi-today').text(todayCount);
        $('#mov-kpi-out').text(outQty);
        $('#mov-kpi-item').text(topItem).attr('title', topItem);
        $('#mov-kpi-tech').text(topTech);
        $('#mov-kpi-calls').text(callsSet.size);
        $('#mov-insights').fadeIn();
    },

    addToCart(item) {
        const existing = App.state.posCart.find(x => x.id === item.id);
        if (existing) {
            $('#upd_part').text(existing.part);
            $('#upd_barcode').text(existing.barcode||'---');
            $('#upd_name').text(existing.name);
            $('#upd_stock').text(existing.stock);
            $('#upd_current_cart').text(existing.pull_qty);
            $('#upd_add_qty').val(1);
            const m = new bootstrap.Modal('#updateCartItemModal');
            m.show();
            $('#updateCartItemModal').on('shown.bs.modal', () => $('#upd_add_qty').trigger('focus').select());
            const confirm = () => {
                const add = parseInt($('#upd_add_qty').val())||0;
                if (add > 0) {
                    existing.pull_qty += add;
                    App.state.posCart = App.state.posCart.filter(x => x.id !== item.id);
                    App.state.posCart.unshift(existing);
                    Movements.renderCart(); m.hide();
                    labToast(`עודכנה הכמות ל-${existing.part}`);
                }
            };
            $('#btnConfirmCartUpdate').off('click').on('click', confirm);
            $('#upd_add_qty').off('keypress').on('keypress', e => { if (e.which===13) confirm(); });
            return;
        }
        if (item.qty <= 0) labToast('שים לב: הפריט חסר במלאי! תצטרך סיסמת מנהל.', 'warning');
        App.state.posCart.push({ id:item.id, part:item.part_number, barcode:item.barcode, name:item.product_name_en, stock:item.qty, pull_qty:1 });
        Movements.renderCart();
    },

    updateCartQty(id, qty, el) {
        const i = App.state.posCart.find(x => x.id === id);
        if (i) { let v=parseInt(qty)||1; if(v<1)v=1; if(el)el.value=v; i.pull_qty=v; }
    },

    removeFromCart(id) { App.state.posCart = App.state.posCart.filter(i => i.id !== id); Movements.renderCart(); },

    renderCart() {
        const $c = $('#posSelectedItems');
        $c.empty();
        if (!App.state.posCart.length) {
            $c.html('<div class="pos-cart-empty"><i class="bi bi-inbox" style="font-size:1.5rem;opacity:.25;display:block"></i><span style="font-size:12px">חפש או סרוק פריט להוסיף</span></div>');
            return;
        }
        App.state.posCart.forEach(i => {
            const stockCls = i.stock > 0 ? 'lbadge-green' : 'lbadge-red';
            $c.append(`<div class="pos-cart-item">
                <div class="pos-cart-item-info">
                    <div class="pos-cart-item-part number-font">${i.part}${i.barcode ? ` <span style="font-size:10px;color:var(--text3)">[${i.barcode}]</span>` : ''}</div>
                    <div class="pos-cart-item-name">${i.name}</div>
                    <div class="pos-cart-item-stock"><span class="lbadge ${stockCls}"><i class="bi bi-box-seam"></i> ${i.stock}</span></div>
                </div>
                <input type="number" class="pos-qty-input form-control" value="${i.pull_qty}" min="1"
                       onchange="Movements.updateCartQty(${i.id},this.value,this)"
                       onkeyup="Movements.updateCartQty(${i.id},this.value,this)">
                <button type="button" class="pos-remove-btn" onclick="Movements.removeFromCart(${i.id})">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>`);
        });
    }
};

/* ── Notes preset buttons ────────────────────────────────────────────────── */
function resetNotesPresets() {
    $('#btn-preset-tikun').removeClass('active btn-danger').addClass('btn-outline-danger');
    $('[data-notes-preset]').removeClass('active btn-secondary').addClass('btn-outline-secondary');
    $('#is_report_inventory').val('0');
}

$('#btn-preset-tikun').on('click', function() {
    $('#movNotes').val('תיקון');
    $('#is_report_inventory').val('1');
    $(this).addClass('active btn-danger').removeClass('btn-outline-danger');
    $('[data-notes-preset]').removeClass('active btn-secondary').addClass('btn-outline-secondary');
});

$(document).on('click', '[data-notes-preset]', function() {
    $('#movNotes').val($(this).data('notes-preset'));
    $('#is_report_inventory').val('0');
    $('#btn-preset-tikun').removeClass('active btn-danger').addClass('btn-outline-danger');
    $(this).addClass('active btn-secondary').removeClass('btn-outline-secondary');
});

$('#movNotes').on('input', function() {
    if ($(this).val() !== 'תיקון') {
        $('#is_report_inventory').val('0');
        $('#btn-preset-tikun').removeClass('active btn-danger').addClass('btn-outline-danger');
    }
});

/* ── Reported filter ─────────────────────────────────────────────────────── */
$('#filterReportedToggle').on('change', function() {
    if (App.state.dtMov) {
        App.state.dtMov.ajax.url(LAB_BASE + '/api/lab/history' + (this.checked ? '?reported=1' : '')).load();
    }
});

/* ── ReportInventory ─────────────────────────────────────────────────────── */
const ReportInventory = {
    _rows: [],

    open() {
        const m = bootstrap.Modal.getOrCreateInstance('#reportInventoryModal');
        m.show();
        $('#reportPreviewLoading').show();
        $('#reportPreviewContent,#reportPreviewEmpty,#reportPreviewFooter').hide();
        $('#reportPreviewBody').empty();

        fetch(LAB_BASE + '/api/lab/report-preview')
            .then(r => r.json())
            .then(res => {
                $('#reportPreviewLoading').hide();
                if (!res.success || !res.rows.length) {
                    $('#reportPreviewEmpty').show();
                    return;
                }
                ReportInventory._rows = res.rows;
                res.rows.forEach(row => {
                    $('#reportPreviewBody').append(`
                        <tr data-id="${row.id}">
                            <td class="text-center">
                                <input type="checkbox" class="chk-report-row" data-id="${row.id}" checked>
                            </td>
                            <td class="number-font fw-bold">${row.part_number || '—'}</td>
                            <td>${row.product_name_en || '—'}</td>
                            <td>${row.compatibility || '—'}</td>
                            <td class="text-center number-font">${row.qty}</td>
                            <td class="text-center number-font">${row.service_call_id || '—'}</td>
                        </tr>
                    `);
                });
                ReportInventory.updateCount();
                $('#reportPreviewContent').show();
                $('#reportPreviewFooter').css('display', 'flex');
            })
            .catch(() => {
                $('#reportPreviewLoading').hide();
                labToast('שגיאה בטעינת נתוני דיווח', 'danger');
            });
    },

    updateCount() {
        const total   = $('.chk-report-row').length;
        const checked = $('.chk-report-row:checked').length;
        $('#reportPreviewCount').text(`${checked} מתוך ${total} פריטים נבחרו`);
    },

    download() {
        const checkedIds  = [];
        const excludedIds = [];
        $('.chk-report-row').each(function() {
            const id = parseInt($(this).data('id'));
            if ($(this).is(':checked')) checkedIds.push(id);
            else excludedIds.push(id);
        });

        if (!checkedIds.length) { labToast('לא נבחרו פריטים לדיווח', 'warning'); return; }

        const $btn = $('#btnDownloadReport');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>שולח...');

        fetch(LAB_BASE + '/api/lab/report-mark', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': LAB_CSRF },
            body: JSON.stringify({ ids: checkedIds, excluded: excludedIds })
        })
        .then(r => r.json())
        .then(res => {
            $btn.prop('disabled', false).html('<i class="bi bi-file-earmark-excel me-2"></i>הורד אקסל');
            if (!res.success) { labToast(res.message || 'שגיאה בשמירת הדיווח', 'danger'); return; }

            const reportedRows = ReportInventory._rows.filter(r => checkedIds.includes(parseInt(r.id)));
            const wsData = [
                ['מקט', 'כמות'],
                ...reportedRows.map(r => [
                    r.part_number || '',
                    r.qty
                ])
            ];
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            ws['!cols'] = [{ wch: 20 }, { wch: 10 }];
            XLSX.utils.book_append_sheet(wb, ws, 'דיווח מלאי');
            const today = new Date().toISOString().split('T')[0];
            XLSX.writeFile(wb, `דיווח_מלאי_${today}.xlsx`);

            bootstrap.Modal.getOrCreateInstance('#reportInventoryModal').hide();
            labToast('הדיווח הושלם והקובץ הורד');
            if (App.state.dtMov) App.state.dtMov.ajax.reload(null, false);
        })
        .catch(() => {
            $btn.prop('disabled', false).html('<i class="bi bi-file-earmark-excel me-2"></i>הורד אקסל');
            labToast('שגיאת תקשורת', 'danger');
        });
    }
};

$(document).on('click', '#btnReportInventory', () => ReportInventory.open());
$(document).on('click', '#btnDownloadReport',  () => ReportInventory.download());
$(document).on('change', '#chkAllReport', function() {
    $('.chk-report-row').prop('checked', this.checked);
    ReportInventory.updateCount();
});
$(document).on('change', '.chk-report-row', function() {
    ReportInventory.updateCount();
    const total   = $('.chk-report-row').length;
    const checked = $('.chk-report-row:checked').length;
    $('#chkAllReport').prop('indeterminate', checked > 0 && checked < total);
    $('#chkAllReport').prop('checked', checked === total);
});

/* ── Analytics ───────────────────────────────────────────────────────────── */
const Analytics = {
    load() {
        const renderers = $.extend({}, $.pivotUtilities.renderers, $.pivotUtilities.c3_renderers, $.pivotUtilities.export_renderers);
        const tr = { "טבלה":renderers["Table"],"מפת חום":renderers["Heatmap"],"תרשים עוגה":renderers["Pie Chart"],"תרשים עמודות":renderers["Bar Chart"],"תרשים קו":renderers["Line Chart"],"ייצוא TSV":renderers["TSV Export"] };
        const ta = { "ספירת תנועות":$.pivotUtilities.aggregators["Count"],"סכום":$.pivotUtilities.aggregators["Integer Sum"],"ממוצע":$.pivotUtilities.aggregators["Average"] };
        $.getJSON(LAB_BASE+'/api/lab/history-chart', data => {
            if (!data?.length) return;
            $('#pivot_output').empty().pivotUI(data, {
                renderers:tr, aggregators:ta,
                derivedAttributes: {
                    "שנה":  r=>{ const d=new Date(r["תאריך"]||''); return isNaN(d)?'?':d.getFullYear().toString(); },
                    "חודש": r=>{ const d=new Date(r["תאריך"]||''); if(isNaN(d))return'?'; const m=["ינואר","פברואר","מרץ","אפריל","מאי","יוני","יולי","אוגוסט","ספטמבר","אוקטובר","נובמבר","דצמבר"]; return String(d.getMonth()+1).padStart(2,'0')+' - '+m[d.getMonth()]; }
                },
                rendererName:"טבלה", aggregatorName:"ספירת תנועות", rows:["יצרן"], cols:["שנה","חודש"],
                localeStrings:{totals:"סך הכל",vs:"מול",by:"לפי",filterResults:"חפש"}
            });

            // fix pvtFilterBox position — reposition it next to the clicked triangle button
            $(document).off('click.pvtfix').on('click.pvtfix', '.pvtTriangle', function() {
                const $btn = $(this);
                setTimeout(() => {
                    const $box = $('.pvtFilterBox:visible');
                    if (!$box.length) return;
                    const btnRect = $btn[0].getBoundingClientRect();
                    const scrollTop  = window.pageYOffset || document.documentElement.scrollTop;
                    const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
                    const boxW = $box.outerWidth();
                    const boxH = $box.outerHeight();
                    const winW = window.innerWidth;
                    // position below the button, flip left if too close to edge
                    let top  = btnRect.bottom + scrollTop + 4;
                    let left = btnRect.left  + scrollLeft;
                    if (left + boxW > winW - 10) left = winW - boxW - 10 + scrollLeft;
                    $box.css({ position:'absolute', top, left, zIndex:99999 });
                }, 0);
            });
        });
    },
    exportToPDF() {
        const btn = event.target.closest('button');
        const orig = btn.innerHTML; btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>מייצר...';
        const el = document.querySelector('.pvtRendererArea');
        const oldBg = el.style.backgroundColor;
        el.style.backgroundColor='#fff'; el.style.padding='20px';
        html2pdf().set({ margin:10, filename:'דוח_מלאי_'+new Date().toLocaleDateString('he-IL')+'.pdf',
            image:{type:'jpeg',quality:.98}, html2canvas:{scale:2,useCORS:true}, jsPDF:{unit:'mm',format:'a4',orientation:'landscape'} })
        .from(el).save().then(() => { btn.disabled=false; btn.innerHTML=orig; el.style.backgroundColor=oldBg; el.style.padding=''; });
    }
};

/* ── Users table ─────────────────────────────────────────────────────────── */
function loadLabUsersTable() {
    if ($.fn.DataTable.isDataTable('#dtLabUsers')) { $('#dtLabUsers').DataTable().ajax.reload(null,false); return; }
    const tbl = $('#dtLabUsers').DataTable({
        language:{ url:'//cdn.datatables.net/plug-ins/2.3.2/i18n/he.json' },
        ajax: LAB_BASE+'/api/lab/users',
        pageLength:50, scrollX:false, autoWidth:false,
        columnDefs:[
            {targets:0, width:'22%'},
            {targets:1, width:'30%'},
            {targets:2, width:'23%'},
            {targets:3, width:'13%'},
            {targets:4, width:'12%'},
        ],
        columns:[
            { data:null, render:r=>`<strong>${r.first_name} ${r.last_name}</strong>` },
            { data:'email', render:d=>`<span class="number-font" style="color:var(--text2)">${d}</span>` },
            { data:'last_login', render:d=>d?`<span class="nowrap">${new Date(d).toLocaleString('he-IL')}</span>`:'<span class="lbadge lbadge-gray">טרם התחבר</span>' },
            { data:'is_active', className:'text-center', render:a=>a==1?'<span class="lbadge lbadge-green"><i class="bi bi-check-circle"></i> פעיל</span>':'<span class="lbadge lbadge-red"><i class="bi bi-x-circle"></i> חסום</span>' },
            { data:null, orderable:false, className:'text-center', render:r=>`<button class="btn-icon" onclick="toggleLabUser(${r.id})" title="שנה סטטוס"><i class="bi bi-arrow-left-right"></i></button>` }
        ]
    });
    $('#formAddLabUser').off('submit').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('[type="submit"]');
        $btn.prop('disabled',true).text('שומר...');
        $.post(LAB_BASE+'/api/lab/user/add', $(this).serialize(), res => {
            $btn.prop('disabled',false).text('שמור משתמש');
            if (res.success) { bootstrap.Modal.getInstance('#addUserModal').hide(); $('#formAddLabUser')[0].reset(); tbl.ajax.reload(); labToast('הטכנאי נוסף בהצלחה'); }
            else labToast(res.message||'שגיאה', 'danger');
        }, 'json');
    });
}

function toggleLabUser(id) {
    if (!confirm('לשנות סטטוס משתמש?')) return;
    $.post(LAB_BASE+'/api/lab/user/toggle', { id, _csrf:LAB_CSRF }, res => {
        if (res.success) $('#dtLabUsers').DataTable().ajax.reload(null, false);
    }, 'json');
}

function approveMove(id) {
    $.post(LAB_BASE+'/api/lab/movement/approve', { move_id:id, _csrf:LAB_CSRF }, res => {
        if (res.success) { labToast('התנועה אושרה'); App.state.dtMov.ajax.reload(null,false); App.state.dtInv?.ajax.reload(null,false); }
        else labToast(res.message||'שגיאה','danger');
    }, 'json');
}

/* ── Excel import ────────────────────────────────────────────────────────── */
$('#excelFile').on('change', function(e) {
    const file = e.target.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const wb = XLSX.read(new Uint8Array(e.target.result), {type:'array'});
        const raw = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], {raw:false,defval:''});
        const data = raw.map(row => { const c={}; Object.keys(row).forEach(k=>{ c[k.trim()]=typeof row[k]==='string'?row[k].trim():row[k]; }); return c; });
        if (data.length) Inventory.showMappingUI(data);
        else labToast('לא נמצאו שורות בקובץ','warning');
    };
    reader.readAsArrayBuffer(file);
});

Inventory.showMappingUI = function(data) {
    const keys = Object.keys(data[0]);
    window._excelKeyMap = keys;
    const fields = [
        {id:'part_number',label:'מק"ט (חובה)',required:true},{id:'barcode',label:'ברקוד'},
        {id:'name',label:'שם המוצר'},{id:'compatibility',label:'תאימות'},
        {id:'incoming_qty',label:'מלאי בדרך'},{id:'qty',label:'כמות להוספה'},{id:'manufacturer',label:'יצרן'}
    ];
    let html = `<h6 style="color:var(--text2)">נמצאו ${data.length} שורות. שייך עמודות:</h6><div class="row g-3 mt-2">`;
    fields.forEach(f => {
        html += `<div class="col-md-6"><label class="form-label">${f.label}${f.required?'<span style="color:var(--danger)"> *</span>':''}</label>
            <select class="form-select map-field" data-field="${f.id}">
                <option value="">-- אל תייבא --</option>
                ${keys.map((k,i)=>`<option value="${i}">${k}</option>`).join('')}
            </select></div>`;
    });
    html += '</div>';
    $('#mappingContainer').html(html).fadeIn();
    $('#uploadMappedData').fadeIn().off('click').on('click', () => Inventory.processExcelUpload(data));
};

Inventory.processExcelUpload = function(allData) {
    const keyMap = window._excelKeyMap||[];
    let map = {};
    $('.map-field').each(function(){ const f=$(this).data('field'),i=$(this).val(); if(i!=='')map[f]=keyMap[parseInt(i)]; });
    if (!map.part_number) return labToast('חובה לשייך עמודת מק"ט','danger');

    const payload = allData.map(row => ({
        part_number:  map.part_number  ? String(row[map.part_number]??'').trim()  : '',
        barcode:      map.barcode      ? String(row[map.barcode]??'').trim()      : '',
        name:         map.name         ? String(row[map.name]??'').trim()         : '',
        manufacturer: map.manufacturer ? String(row[map.manufacturer]??'').trim() : '',
        compatibility:map.compatibility? String(row[map.compatibility]??'').trim(): '',
        qty:          map.qty          ? parseInt(row[map.qty])||0                : 0,
        incoming_qty: map.incoming_qty ? parseInt(row[map.incoming_qty])||0       : 0,
    })).filter(r => r.part_number || r.barcode);

    if (!payload.length) return labToast('לא נמצאו שורות תקינות','warning');

    $('#uploadMappedData').prop('disabled',true).text('מייבא...');
    fetch(LAB_BASE+'/api/lab/import', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-Token':LAB_CSRF},
        body: JSON.stringify({items:payload})
    }).then(r=>r.json()).then(res => {
        $('#uploadMappedData').prop('disabled',false).text('התחל ייבוא');
        if (res.success) {
            labToast(`יובאו ${res.imported} פריטים (חדשים: ${res.inserted}, עודכנו: ${res.updated})`);
            bootstrap.Modal.getInstance('#uploadModal').hide();
            App.state.dtInv.ajax.reload(null, false);
        } else labToast(res.message||'שגיאה','danger');
    }).catch(()=>labToast('שגיאת תקשורת','danger'));
};

/* ── init ────────────────────────────────────────────────────────────────── */
$(function() { App.init(); });
</script>
