/**
 * prefs-loader.js — v4
 * Zero-flash via CSS variables only. No inline styles on elements.
 */

(function() {

  const FONTS = {
    assistant: { name:'Assistant',     stack:"'Assistant',sans-serif"     },
    heebo:     { name:'Heebo',         stack:"'Heebo',sans-serif"          },
    rubik:     { name:'Rubik',         stack:"'Rubik',sans-serif"          },
    inter:     { name:'Inter',         stack:"'Inter',sans-serif"          },
    ibm:       { name:'IBM Plex Sans', stack:"'IBM Plex Sans',sans-serif"  },
    nunito:    { name:'Nunito',        stack:"'Nunito',sans-serif"         },
  };

  const PALETTES = {
    blue:    ['#0d1117','#5b8dee','#4a7cdd','#1a2240'],
    purple:  ['#0e0b18','#8b5cf6','#7c3aed','#1e1535'],
    cyan:    ['#091518','#06b6d4','#0891b2','#0f2530'],
    emerald: ['#091810','#10b981','#059669','#0d2518'],
    rose:    ['#130a0d','#f43f5e','#e11d48','#2a1018'],
    amber:   ['#130f07','#f59e0b','#d97706','#251a0a'],
    indigo:  ['#0a0c18','#6366f1','#4f46e5','#141830'],
    teal:    ['#091614','#14b8a6','#0d9488','#102822'],
    orange:  ['#120c07','#f97316','#ea6c0a','#22130a'],
    pink:    ['#130a15','#ec4899','#db2777','#240f28'],
    slate:   ['#0b0d12','#64748b','#475569','#151820'],
    lime:    ['#0b1208','#84cc16','#65a30d','#142009'],
    sky:     ['#081220','#38bdf8','#0ea5e9','#0c2035'],
    violet:  ['#0e0820','#a78bfa','#8b5cf6','#1a1038'],
    red:     ['#120808','#ef4444','#dc2626','#200e0e'],
    green:   ['#091510','#22c55e','#16a34a','#0e2215'],
    gold:    ['#120f05','#eab308','#ca8a04','#201a07'],
    navy:    ['#06091a','#3b82f6','#2563eb','#0c1230'],
    coral:   ['#130d08','#fb923c','#f97316','#221508'],
    mono:    ['#0d0d0d','#a3a3a3','#737373','#1a1a1a'],
  };

  const DEFAULT = {
    mode:'dark', palette:'blue', radius:'10',
    shadows:'medium', btn_style:'filled',
    glass:false, animations:true, density:'normal',
    font:'assistant'
  };

  function rgba(hex, a) {
    const r=parseInt(hex.slice(1,3),16), g=parseInt(hex.slice(3,5),16), b=parseInt(hex.slice(5,7),16);
    return `rgba(${r},${g},${b},${a})`;
  }
  function blend(c1, c2, t) {
    const p = h => [parseInt(h.slice(1,3),16), parseInt(h.slice(3,5),16), parseInt(h.slice(5,7),16)];
    const [r1,g1,b1]=p(c1), [r2,g2,b2]=p(c2);
    return '#'+[
      Math.round(r1+(r2-r1)*t),
      Math.round(g1+(g2-g1)*t),
      Math.round(b1+(b2-b1)*t)
    ].map(x=>x.toString(16).padStart(2,'0')).join('');
  }

  function apply(rawPrefs) {
    const prefs = {...DEFAULT, ...rawPrefs};
    const p     = PALETTES[prefs.palette] || PALETTES.blue;
    const root  = document.documentElement;
    const isDark = prefs.mode === 'dark' ||
      (prefs.mode === 'system' && window.matchMedia('(prefers-color-scheme:dark)').matches);

    /* ── Font: only via CSS variable ── */
    const fontDef = FONTS[prefs.font] || FONTS.assistant;
    root.style.setProperty('--font',     fontDef.stack);
    root.style.setProperty('--num-font', fontDef.stack);

    /* ── Palette colours ── */
    root.style.setProperty('--accent',       p[1]);
    root.style.setProperty('--accent-hover', p[2]);
    root.style.setProperty('--accent-dim',   rgba(p[1], .15));

    if (isDark) {
      root.style.setProperty('--bg',      p[0]);
      root.style.setProperty('--bg2',     blend(p[0], p[3], .45));
      root.style.setProperty('--bg3',     blend(p[0], p[3], .75));
      root.style.setProperty('--bg4',     p[3]);
      root.style.setProperty('--text',    '#e2e5f0');
      root.style.setProperty('--text2',   '#7c829c');
      root.style.setProperty('--text3',   '#4a5068');
      root.style.setProperty('--border',  'rgba(255,255,255,.07)');
      root.style.setProperty('--border2', 'rgba(255,255,255,.14)');
    } else {
      root.style.setProperty('--bg',      '#f0f2f8');
      root.style.setProperty('--bg2',     '#ffffff');
      root.style.setProperty('--bg3',     '#e8eaf2');
      root.style.setProperty('--bg4',     '#d8dce8');
      root.style.setProperty('--text',    '#1a1d2e');
      root.style.setProperty('--text2',   '#4a5068');
      root.style.setProperty('--text3',   '#8892a4');
      root.style.setProperty('--border',  'rgba(0,0,0,.08)');
      root.style.setProperty('--border2', 'rgba(0,0,0,.15)');
    }

    /* ── Radius ── */
    const rv = parseInt(prefs.radius) || 10;
    root.style.setProperty('--radius',    rv + 'px');
    root.style.setProperty('--radius-sm', Math.max(rv - 4, 2) + 'px');

    /* ── Shadow ── */
    const shadows = {
      none:   'none',
      soft:   '0 2px 8px rgba(0,0,0,.2)',
      medium: '0 4px 20px rgba(0,0,0,.35)',
      strong: '0 8px 40px rgba(0,0,0,.55)'
    };
    root.style.setProperty('--shadow', shadows[prefs.shadows] || shadows.medium);

    /* ── Nav density ── */
    const density = { compact:'6px 8px', normal:'9px 12px', spacious:'12px 12px' };
    root.style.setProperty('--nav-item-pad', density[prefs.density] || density.normal);

    /* ── Glass CSS variable (rgba based on current bg) ── */
    const glassBg = isDark
      ? `rgba(${parseInt(p[0].slice(1,3),16)},${parseInt(p[0].slice(3,5),16)},${parseInt(p[0].slice(5,7),16)},.85)`
      : 'rgba(255,255,255,.85)';
    root.style.setProperty('--glass-bg', glassBg);

    /* ── Body classes (use documentElement before body exists) ── */
    const target = document.body || document.documentElement;
    target.classList.toggle('glass-mode', !!prefs.glass);
    target.classList.toggle('no-anim',    prefs.animations === false);

    /* ── Button style override ── */
    let bso = document.getElementById('v2-bso');
    if (!bso) { bso = document.createElement('style'); bso.id = 'v2-bso'; document.head.appendChild(bso); }
    if (prefs.btn_style === 'outlined') {
      bso.textContent = `.btn-primary{background:transparent!important;border:2px solid var(--accent)!important;color:var(--accent)!important;box-shadow:none!important}.btn-primary:hover{background:var(--accent-dim)!important}`;
    } else if (prefs.btn_style === 'soft') {
      bso.textContent = `.btn-primary{background:var(--accent-dim)!important;color:var(--accent)!important;box-shadow:none!important}`;
    } else {
      bso.textContent = '';
    }

    window.V2_PREFS = {...DEFAULT, ...prefs};
  }

  /* ── Static styles ── */
  const st = document.createElement('style');
  st.textContent = `
/* Glass mode */
.glass-mode .card{background:var(--glass-bg)!important;backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.12)!important}
.glass-mode #sidebar{background:var(--glass-bg)!important;border-left:1px solid rgba(255,255,255,.12)!important;box-shadow:-4px 0 32px rgba(0,0,0,.4)}
.glass-mode #topbar{background:var(--glass-bg)!important;backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,.08)!important}
.no-anim *,.no-anim *::before,.no-anim *::after{transition:none!important;animation:none!important}
#sidebar .nav-item{padding:var(--nav-item-pad,9px 12px)}`;
  document.head.appendChild(st);

  /* ── Boot: apply from localStorage immediately ── */
  window.V2_PREFS = {...DEFAULT};
  try {
    const saved = JSON.parse(localStorage.getItem('v2_prefs') || '{}');
    apply(Object.keys(saved).length ? saved : DEFAULT);
  } catch(e) { apply(DEFAULT); }

  /* ── DB sync after page load ── */
  window.addEventListener('load', function() {
    const base = window.__V2_BASE || '';
    if (!base) return;
    fetch(base + '/preferences/get', { credentials: 'include' })
      .then(r => r.ok ? r.json() : null)
      .then(dbPrefs => {
        if (!dbPrefs || !Object.keys(dbPrefs).length) return;
        const local = (() => { try { return JSON.parse(localStorage.getItem('v2_prefs')||'{}'); } catch(e){return{};} })();
        if (!local._updated || (dbPrefs._updated && dbPrefs._updated > local._updated)) {
          localStorage.setItem('v2_prefs', JSON.stringify(dbPrefs));
          apply(dbPrefs);
        }
      })
      .catch(() => {});
  });

  window.applyV2Prefs = apply;
  window.V2_FONTS     = FONTS;

})();