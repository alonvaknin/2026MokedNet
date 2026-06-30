(function () {
  'use strict';

  const BASE = window.__V2_BASE || '';
  const CSRF = window.__CSRF   || '';

  const canvas    = document.getElementById('game-canvas');
  const overlay   = document.getElementById('game-overlay');
  const startBtn  = document.getElementById('game-start-btn');
  const scoreEl   = document.getElementById('game-score-display');
  const bestEl    = document.getElementById('game-best-display');
  const lbList    = document.getElementById('game-lb-list');
  const lbMe      = document.getElementById('game-lb-me');
  const lbMyScore = document.getElementById('game-lb-my-score');
  const timerEl   = document.getElementById('game-timer-display');
  const levelEl   = document.getElementById('game-level-display');
  const closeBtn  = document.getElementById('game-close-btn');
  const widget    = document.getElementById('game-widget');

  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  // ── בועות — הטקסט הוא הבועה עצמה ─────────────────────────────────────
  // bad:true = לא לפוצץ (לקוח טוב / הפסקה) — מוריד ניקוד
  const BUBBLES = [
    // ── רגילות — מפוצצים = +ניקוד ──────────────────────────────────────
    { text: 'במעבדה אמרו\nשיגיע מחר',        color: '#5b8dee', points: 10, weight: 18, rare: false, bad: false },
    { text: 'נזק פיזי!',                      color: '#e17055', points: 10, weight: 16, rare: false, bad: false },
    { text: 'שואב משאיר\nסימני מים',          color: '#a29bfe', points: 10, weight: 16, rare: false, bad: false },
    { text: 'אני מכיר\nאת הבעלים',            color: '#fdcb6e', points: 12, weight: 14, rare: false, bad: false },
    { text: 'אתבע אתכם\nעל 60₪ בשביל\nהעיקרון', color: '#fd79a8', points: 12, weight: 14, rare: false, bad: false },
    { text: 'ראיתי\nשקראתם!!',                color: '#00b894', points: 8,  weight: 15, rare: false, bad: false },
    { text: 'זו הפעם\nה-5 שלי היום',           color: '#74b9ff', points: 8,  weight: 14, rare: false, bad: false },
    { text: 'הנהג אמר\nשהגיע...',              color: '#a29bfe', points: 10, weight: 13, rare: false, bad: false },
    { text: 'ה-SMS אמר\n"בדרך"...',            color: '#a29bfe', points: 10, weight: 12, rare: false, bad: false },
    { text: 'ניסיתי\nלכבות\nולהדליק',         color: '#e17055', points: 8,  weight: 13, rare: false, bad: false },
    { text: 'הרובוט\nנתקע\nבפינה',             color: '#e17055', points: 8,  weight: 12, rare: false, bad: false },
    { text: 'שלחתי\n12 הודעות!',               color: '#00b894', points: 10, weight: 12, rare: false, bad: false },
    { text: 'שלוש נקודות\nואז כלום',           color: '#00b894', points: 8,  weight: 10, rare: false, bad: false },
    { text: 'זה לא\nעובד כבר\nשנה!',           color: '#5b8dee', points: 10, weight: 11, rare: false, bad: false },
    { text: 'שלחתם אותי\nלנציג אחר',           color: '#74b9ff', points: 8,  weight: 10, rare: false, bad: false },
    { text: 'חיכיתי\nשעה בתור',                color: '#74b9ff', points: 10, weight: 10, rare: false, bad: false },
    { text: 'אני רושם\nהכל!',                  color: '#fdcb6e', points: 12, weight: 9,  rare: false, bad: false },
    { text: 'יש לי הכל\nבצילום מסך',           color: '#fd79a8', points: 12, weight: 9,  rare: false, bad: false },
    { text: 'אני הולכת\nלפייסבוק!',            color: '#fd79a8', points: 12, weight: 9,  rare: false, bad: false },
    { text: 'כן...',                            color: '#b2bec3', points: 5,  weight: 8,  rare: false, bad: false },
    { text: 'אוקיי...',                         color: '#b2bec3', points: 5,  weight: 8,  rare: false, bad: false },
    { text: 'מה שתגיד...',                      color: '#b2bec3', points: 5,  weight: 7,  rare: false, bad: false },
    { text: 'הסברתי\nכבר 3 פעמים!',            color: '#6c5ce7', points: 12, weight: 8,  rare: false, bad: false },
    { text: 'שמעת בכלל?',                      color: '#6c5ce7', points: 10, weight: 8,  rare: false, bad: false },
    { text: 'שבועיים?!\nשילמתי PRIME',         color: '#a29bfe', points: 12, weight: 7,  rare: false, bad: false },
    { text: 'תחזירו לי\nאת הכסף!',             color: '#5b8dee', points: 10, weight: 9,  rare: false, bad: false },
    { text: 'זו לא\nהדרך לדבר!',               color: '#fd79a8', points: 12, weight: 8,  rare: false, bad: false },
    { text: 'אני מגישה\nתלונה!',               color: '#fd79a8', points: 12, weight: 8,  rare: false, bad: false },
    { text: 'אל תשים\nאותי בהמתנה',            color: '#6c5ce7', points: 10, weight: 8,  rare: false, bad: false },
    // ── נדירות — +ניקוד גבוה ─────────────────────────────────────────
    { text: 'אני מכיר\nאת יוסי\nהמנכ"ל',      color: '#f9ca24', points: 30, weight: 2,  rare: true,  bad: false },
    { text: 'קניתי 12\nשואבים!',               color: '#f9ca24', points: 28, weight: 3,  rare: true,  bad: false },
    { text: 'אני עורך\nדין!',                  color: '#fdcb6e', points: 22, weight: 4,  rare: true,  bad: false },
    { text: 'השואב\nבלע את\nהשטיח',            color: '#d63031', points: 25, weight: 3,  rare: true,  bad: false },
    { text: 'הרובוט\nמסתובב\nבחושך',           color: '#d63031', points: 20, weight: 4,  rare: true,  bad: false },
    { text: 'אחת\nמהאוזניות\nשותקת',           color: '#e84393', points: 20, weight: 4,  rare: true,  bad: false },
    { text: 'חיברתי\nל-5 מכשירים\nבו-זמנית',  color: '#e84393', points: 22, weight: 3,  rare: true,  bad: false },
    { text: 'הצעדים\nשגויים ב-\n8000',         color: '#55efc4', points: 20, weight: 4,  rare: true,  bad: false },
    // ── בועות טובות — לא לפוצץ! (❌ מוצג עליהן) ─────────────────────
    { text: 'תודה\nרבה!',                      color: '#27ae60', points: -15, weight: 8, rare: false, bad: true },
    { text: 'שירות\nמעולה!',                   color: '#27ae60', points: -15, weight: 7, rare: false, bad: true },
    { text: 'הנציג היה\nמדהים!',               color: '#2ecc71', points: -20, weight: 5, rare: false, bad: true },
    { text: 'אני חוזר\nבטוח!',                 color: '#27ae60', points: -12, weight: 6, rare: false, bad: true },
    { text: 'נתתי\n5 כוכבים!',                 color: '#f39c12', points: -20, weight: 5, rare: false, bad: true },
    { text: 'רוצה\nלשדרג!',                    color: '#16a085', points: -10, weight: 6, rare: false, bad: true },
    { text: 'חבר הפנה\nאותי',                  color: '#16a085', points: -10, weight: 5, rare: false, bad: true },
    { text: 'הקפה\nמוכן! ☕',                  color: '#795548', points: -25, weight: 3, rare: true,  bad: true },
    { text: 'מגיע לך\nהפסקה!',                color: '#795548', points: -20, weight: 3, rare: true,  bad: true },
  ];

  const LEVELS = [
    { level: 1, duration: 30, label: 'בוקר רגוע',        maxB: 3, speedMult: 1.0 },
    { level: 2, duration: 30, label: 'אחרי צהריים',        maxB: 4, speedMult: 1.2 },
    { level: 3, duration: 30, label: 'שיא פנייות',        maxB: 5, speedMult: 1.5 },
    { level: 4, duration: 30, label: 'כולם כועסים',       maxB: 6, speedMult: 1.8 },
    { level: 5, duration: 30, label: 'יום שישי 15:00 😱', maxB: 7, speedMult: 2.2 },
    { level: 6, duration: 99, label: 'כאוס מוחלט 🔥',     maxB: 8, speedMult: 2.6 },
  ];

  const BAD_POOL  = BUBBLES.filter(t => t.bad);
  const GOOD_POOL = BUBBLES.filter(t => !t.bad);
  const GOOD_W = GOOD_POOL.reduce((s, t) => s + t.weight, 0);
  const BAD_W  = BAD_POOL.reduce((s, t) => s + t.weight, 0);

  function pickFrom(pool, total) {
    let r = Math.random() * total;
    for (const t of pool) { r -= t.weight; if (r <= 0) return t; }
    return pool[0];
  }

  function randomBubbleType() {
    return Math.random() < 0.2 ? pickFrom(BAD_POOL, BAD_W) : pickFrom(GOOD_POOL, GOOD_W);
  }

  // ── State ──────────────────────────────────────────────────────────────
  let running   = false;
  let score     = 0;
  let bestScore = 0;
  let bubbles   = [];
  let particles = [];
  let toasts    = [];
  let lastTime  = 0;
  let elapsed   = 0;
  let levelTime = 0;
  let levelIdx  = 0;
  let rafId     = null;
  let saveTimer = null;
  let W = 0, H = 0;
  let bannerText = '', bannerAlpha = 0;

  // ── Resize ─────────────────────────────────────────────────────────────
  function resize() {
    const wrap = canvas.parentElement;
    W = canvas.width  = wrap.clientWidth;
    H = canvas.height = wrap.clientHeight;
  }

  // ── Level ──────────────────────────────────────────────────────────────
  function currentLevel() { return LEVELS[Math.min(levelIdx, LEVELS.length - 1)]; }

  function updateLevel(dt) {
    levelTime += dt / 60;
    const lv = currentLevel();
    if (timerEl) timerEl.textContent = Math.max(0, Math.ceil(lv.duration - levelTime));
    if (levelTime >= lv.duration && levelIdx < LEVELS.length - 1) {
      levelIdx++;
      levelTime = 0;
      const next = currentLevel();
      if (levelEl) levelEl.textContent = `${next.level} — ${next.label}`;
      bannerText  = `רמה ${next.level}: ${next.label}`;
      bannerAlpha = 1.8;
    }
  }

  // ── Bubble sizing: מחשב רדיוס לפי שורות טקסט ─────────────────────────
  function bubbleRadius(type) {
    const lines = type.text.split('\n');
    const longest = Math.max(...lines.map(l => l.length));
    // rare = גדול, bad = בינוני, רגיל לפי אורך טקסט
    if (type.rare) return 44 + Math.random() * 8;
    const base = Math.max(34, Math.min(52, longest * 5.5 + lines.length * 7));
    return base + Math.random() * 6;
  }

  // ── Bubble factory ─────────────────────────────────────────────────────
  function makeBubble() {
    const type  = randomBubbleType();
    const lv    = currentLevel();
    const r     = bubbleRadius(type);
    const speed = (0.6 * lv.speedMult) * (0.85 + Math.random() * 0.3);
    return {
      x: r + Math.random() * Math.max(1, W - 2 * r),
      y: H + r + 10,
      r,
      speed: Math.min(speed, 4.2),
      type,
      alpha: 1,
      glowPhase: Math.random() * Math.PI * 2,
      popped: false,
    };
  }

  function maxBubbles() { return currentLevel().maxB; }

  // ── Particles ──────────────────────────────────────────────────────────
  function spawnParticles(x, y, color, count) {
    for (let i = 0; i < count; i++) {
      const angle = (Math.PI * 2 * i) / count + Math.random() * 0.5;
      const spd   = 1.8 + Math.random() * 2.8;
      particles.push({ x, y, vx: Math.cos(angle) * spd, vy: Math.sin(angle) * spd,
        r: 3 + Math.random() * 3, color, life: 1, decay: 0.035 + Math.random() * 0.04 });
    }
  }

  // ── Draw ───────────────────────────────────────────────────────────────
  function drawBubble(b) {
    const { x, y, r, type, alpha, glowPhase } = b;
    ctx.save();
    ctx.globalAlpha = alpha;

    // Glow
    if (type.rare) {
      const g = 0.5 + 0.5 * Math.sin(glowPhase);
      ctx.shadowColor = type.color; ctx.shadowBlur = 12 + g * 18;
    } else if (type.bad) {
      const g = 0.4 + 0.4 * Math.sin(glowPhase * 0.6);
      ctx.shadowColor = type.color; ctx.shadowBlur = 5 + g * 7;
    }

    // Circle
    ctx.beginPath();
    ctx.arc(x, y, r, 0, Math.PI * 2);
    ctx.fillStyle = type.color + (type.bad ? '1a' : '28');
    ctx.fill();
    ctx.strokeStyle = type.color;
    ctx.lineWidth   = type.rare ? 2.5 : type.bad ? 2 : 1.8;
    if (type.bad) ctx.setLineDash([5, 3]);
    ctx.stroke();
    ctx.setLineDash([]);
    ctx.shadowBlur = 0;

    // Text — multi-line, עברית RTL
    const lines     = type.text.split('\n');
    const lineCount = lines.length;
    const fontSize  = type.rare ? Math.round(r * 0.32) : Math.round(r * 0.29);
    const lineH     = fontSize * 1.28;
    const startY    = y - ((lineCount - 1) * lineH) / 2;

    ctx.font         = `bold ${fontSize}px Arial, sans-serif`;
    ctx.textAlign    = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle    = '#fff';
    ctx.shadowColor  = 'rgba(0,0,0,.55)';
    ctx.shadowBlur   = 3;
    lines.forEach((line, i) => ctx.fillText(line, x, startY + i * lineH));
    ctx.shadowBlur = 0;

    // ❌ badge on bad bubbles
    if (type.bad) {
      ctx.font      = `${Math.round(r * 0.38)}px serif`;
      ctx.textBaseline = 'middle';
      ctx.fillText('❌', x + r * 0.58, y - r * 0.58);
    }
    // ⭐ glow dot on rare
    if (type.rare) {
      ctx.font      = `${Math.round(r * 0.32)}px serif`;
      ctx.textBaseline = 'middle';
      ctx.fillText('⭐', x + r * 0.62, y - r * 0.62);
    }

    ctx.restore();
  }

  function drawParticles() {
    particles.forEach(p => {
      ctx.save();
      ctx.globalAlpha = Math.min(p.life, 1);
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r * Math.min(p.life, 1), 0, Math.PI * 2);
      ctx.fillStyle = p.color; ctx.fill();
      ctx.restore();
    });
  }

  function drawToasts() {
    toasts.forEach(t => {
      ctx.save();
      ctx.globalAlpha  = Math.min(t.life, 1);
      ctx.font         = `bold 14px Arial, sans-serif`;
      ctx.textAlign    = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillStyle    = t.color;
      ctx.shadowColor  = 'rgba(0,0,0,.8)';
      ctx.shadowBlur   = 5;
      ctx.fillText(t.text, t.x, t.y);
      ctx.restore();
    });
  }

  function drawBanner() {
    if (bannerAlpha <= 0) return;
    ctx.save();
    ctx.globalAlpha  = Math.min(bannerAlpha, 1);
    ctx.font         = 'bold 17px Arial, sans-serif';
    ctx.textAlign    = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle    = '#fdcb6e';
    ctx.shadowColor  = '#f9ca24'; ctx.shadowBlur = 16;
    ctx.fillText(bannerText, W / 2, H / 2);
    ctx.restore();
    bannerAlpha -= 0.012;
  }

  // ── Game loop ──────────────────────────────────────────────────────────
  function loop(ts) {
    if (!running) return;
    const dt = Math.min((ts - lastTime) / 16.67, 3);
    lastTime  = ts;
    elapsed  += dt / 60;

    updateLevel(dt);
    while (bubbles.length < maxBubbles()) bubbles.push(makeBubble());

    bubbles.forEach(b => {
      b.y -= b.speed * dt;
      b.glowPhase += 0.05 * dt;
      if (b.y + b.r < 0) {
        if (!b.type.bad) { score = Math.max(0, score - 5); updateScoreDisplay(); }
        b.popped = true;
      }
    });
    bubbles = bubbles.filter(b => !b.popped);

    particles.forEach(p => { p.x += p.vx * dt; p.y += p.vy * dt; p.vy += 0.15 * dt; p.life -= p.decay * dt; });
    particles = particles.filter(p => p.life > 0);

    toasts.forEach(t => { t.y += t.vy * dt; t.life -= 0.022 * dt; });
    toasts = toasts.filter(t => t.life > 0);

    ctx.clearRect(0, 0, W, H);
    bubbles.forEach(drawBubble);
    drawParticles();
    drawToasts();
    drawBanner();
    rafId = requestAnimationFrame(loop);
  }

  // ── Click ──────────────────────────────────────────────────────────────
  function onCanvasClick(e) {
    if (!running) return;
    const rect = canvas.getBoundingClientRect();
    const cx = (e.clientX - rect.left) * (W / rect.width);
    const cy = (e.clientY - rect.top)  * (H / rect.height);

    let pts = 0;
    for (let i = bubbles.length - 1; i >= 0; i--) {
      const b = bubbles[i];
      const dx = cx - b.x, dy = cy - b.y;
      if (dx * dx + dy * dy > b.r * b.r) continue;

      pts = b.type.points;
      const prev  = score;
      score = Math.max(0, score + pts);
      const delta = score - prev;

      spawnParticles(b.x, b.y, b.type.color, b.type.rare ? 14 : b.type.bad ? 5 : 8);
      toasts.push({
        x: b.x, y: b.y - b.r - 8,
        text:  (delta >= 0 ? '+' : '') + delta,
        color: b.type.bad ? '#e74c3c' : b.type.rare ? '#f9ca24' : b.type.color,
        life: 1.4, vy: -1.6,
      });

      bubbles.splice(i, 1);
      updateScoreDisplay();
      break;
    }

    if (pts > 0 && Math.floor(score / 50) > Math.floor((score - pts) / 50)) {
      scoreEl.classList.remove('game-score-flash');
      void scoreEl.offsetWidth;
      scoreEl.classList.add('game-score-flash');
    }
  }

  // ── Score / Save / Leaderboard ─────────────────────────────────────────
  function updateScoreDisplay() {
    scoreEl.textContent = score;
    if (score > bestScore) { bestScore = score; bestEl.textContent = bestScore; }
  }

  async function saveScore() {
    if (score <= 0) return;
    try {
      const res = await fetch(BASE + '/api/game-score.php', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({ score }),
      });
      const data = await res.json();
      if (data.saved) loadLeaderboard();
    } catch (e) { console.error('[game] saveScore failed', e); }
  }

  async function loadLeaderboard() {
    try {
      const data = await fetch(BASE + '/api/game-leaderboard.php').then(r => r.json());
      if (!data.ok) return;
      const medals = ['🥇','🥈','🥉','4️⃣','5️⃣'];
      lbList.innerHTML = data.top5.map((row, i) => `
        <div class="game-lb-row${row.is_me ? ' is-me' : ''}">
          <span class="game-lb-rank">${medals[i]}</span>
          <span class="game-lb-name">${escHtml(row.name)}</span>
          <span class="game-lb-score">${Number(row.score) || 0}</span>
        </div>`).join('');
      if (data.my_score > 0) {
        lbMe.style.display    = 'flex';
        lbMyScore.textContent = data.my_score;
        bestScore = Math.max(bestScore, data.my_score);
        bestEl.textContent    = bestScore;
      }
    } catch (_) {}
  }

  function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Start ──────────────────────────────────────────────────────────────
  function startGame() {
    if (running) return;
    resize();
    score = 0; bubbles = []; particles = []; toasts = [];
    elapsed = 0; levelTime = 0; levelIdx = 0; bannerAlpha = 0;
    running = true;
    overlay.style.display = 'none';
    updateScoreDisplay();
    const lv = currentLevel();
    if (levelEl) levelEl.textContent = `${lv.level} — ${lv.label}`;
    if (timerEl) timerEl.textContent = lv.duration;
    lastTime = performance.now();
    if (rafId) cancelAnimationFrame(rafId);
    rafId = requestAnimationFrame(loop);
    clearInterval(saveTimer);
    saveTimer = setInterval(saveScore, 10000);
  }

  // ── Close ──────────────────────────────────────────────────────────────
  if (closeBtn && widget) {
    closeBtn.addEventListener('click', () => {
      if (running) { saveScore(); running = false; }
      clearInterval(saveTimer);
      if (rafId) cancelAnimationFrame(rafId);
      widget.style.display = 'none';
    });
  }

  // ── Events ─────────────────────────────────────────────────────────────
  startBtn.addEventListener('click', startGame);
  canvas.addEventListener('click', onCanvasClick);
  document.addEventListener('visibilitychange', () => { if (document.hidden && running) saveScore(); });
  window.addEventListener('beforeunload', () => {
    if (!running || score <= 0) return;
    const pl = JSON.stringify({ score, _csrf: CSRF });
    navigator.sendBeacon
      ? navigator.sendBeacon(BASE + '/api/game-score.php', new Blob([pl], { type: 'application/json' }))
      : saveScore();
  });

  new ResizeObserver(resize).observe(canvas.parentElement);
  loadLeaderboard();
})();
