(function () {
  'use strict';

  const BASE  = window.__V2_BASE || '';
  const CSRF  = window.__CSRF   || '';

  const canvas  = document.getElementById('game-canvas');
  const overlay = document.getElementById('game-overlay');
  const startBtn = document.getElementById('game-start-btn');
  const scoreEl = document.getElementById('game-score-display');
  const bestEl  = document.getElementById('game-best-display');
  const lbList  = document.getElementById('game-lb-list');
  const lbMe    = document.getElementById('game-lb-me');
  const lbMyScore = document.getElementById('game-lb-my-score');

  if (!canvas) return;

  const ctx = canvas.getContext('2d');

  // ── Bubble types ──────────────────────────────────────────────────────
  const TYPES = [
    { emoji: '📞', color: '#5b8dee', label: 'טלפון',         points: 10, weight: 30, rare: false },
    { emoji: '💬', color: '#00b894', label: 'וואטסאפ',        points: 10, weight: 28, rare: false },
    { emoji: '🔧', color: '#e17055', label: 'תמיכה',          points: 10, weight: 22, rare: false },
    { emoji: '📦', color: '#a29bfe', label: 'משלוח',          points: 10, weight: 20, rare: false },
    { emoji: '🔌', color: '#636e72', label: 'מטען',           points: 5,  weight: 18, rare: false },
    { emoji: '⌚', color: '#fdcb6e', label: 'שעון חכם',       points: 15, weight: 10, rare: false },
    { emoji: '🎧', color: '#fd79a8', label: 'אוזניות',        points: 20, weight: 5,  rare: true  },
    { emoji: '🤖', color: '#d63031', label: 'שואב רובוטי',   points: 25, weight: 3,  rare: true  },
  ];

  const TOTAL_WEIGHT = TYPES.reduce((s, t) => s + t.weight, 0);

  function randomType() {
    let r = Math.random() * TOTAL_WEIGHT;
    for (const t of TYPES) { r -= t.weight; if (r <= 0) return t; }
    return TYPES[0];
  }

  // ── State ─────────────────────────────────────────────────────────────
  let running   = false;
  let score     = 0;
  let bestScore = 0;
  let bubbles   = [];
  let particles = [];
  let lastTime  = 0;
  let elapsed   = 0; // seconds since game started
  let rafId     = null;
  let saveTimer = null;
  let W = 0, H = 0;

  // ── Resize ────────────────────────────────────────────────────────────
  function resize() {
    const wrap = canvas.parentElement;
    W = canvas.width  = wrap.clientWidth;
    H = canvas.height = wrap.clientHeight;
  }

  // ── Bubble factory ────────────────────────────────────────────────────
  function makeBubble() {
    const type  = randomType();
    const r     = type.rare ? 28 + Math.random() * 8 : 20 + Math.random() * 8;
    const speed = (0.6 + elapsed * 0.008) * (0.85 + Math.random() * 0.3);
    return {
      x:     r + Math.random() * (W - 2 * r),
      y:     H + r + 10,
      r,
      speed: Math.min(speed, 3.5),
      type,
      alpha: 1,
      glowPhase: Math.random() * Math.PI * 2,
      popped: false,
    };
  }

  function maxBubbles() {
    return Math.min(3 + Math.floor(elapsed / 15), 8);
  }

  // ── Particles ─────────────────────────────────────────────────────────
  function spawnParticles(x, y, color, count) {
    for (let i = 0; i < count; i++) {
      const angle = (Math.PI * 2 * i) / count + Math.random() * 0.5;
      const speed = 2 + Math.random() * 3;
      particles.push({
        x, y,
        vx: Math.cos(angle) * speed,
        vy: Math.sin(angle) * speed,
        r: 3 + Math.random() * 3,
        color,
        life: 1,
        decay: 0.04 + Math.random() * 0.04,
      });
    }
  }

  // ── Draw ──────────────────────────────────────────────────────────────
  function drawBubble(b) {
    ctx.save();
    ctx.globalAlpha = b.alpha;

    // Glow for rare bubbles
    if (b.type.rare) {
      const glow = 0.5 + 0.5 * Math.sin(b.glowPhase);
      ctx.shadowColor = b.type.color;
      ctx.shadowBlur  = 8 + glow * 12;
    }

    // Circle
    ctx.beginPath();
    ctx.arc(b.x, b.y, b.r, 0, Math.PI * 2);
    ctx.fillStyle = b.type.color + '33';
    ctx.fill();
    ctx.strokeStyle = b.type.color;
    ctx.lineWidth = b.type.rare ? 2.5 : 1.5;
    ctx.stroke();

    // Emoji
    ctx.shadowBlur = 0;
    ctx.font = `${Math.round(b.r * 1.05)}px serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#fff';
    ctx.fillText(b.type.emoji, b.x, b.y);

    ctx.restore();
  }

  function drawParticles() {
    particles.forEach(p => {
      ctx.save();
      ctx.globalAlpha = p.life;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r * p.life, 0, Math.PI * 2);
      ctx.fillStyle = p.color;
      ctx.fill();
      ctx.restore();
    });
  }

  // ── Game loop ─────────────────────────────────────────────────────────
  function loop(ts) {
    if (!running) return;
    const dt = Math.min((ts - lastTime) / 16.67, 3); // delta in frames
    lastTime  = ts;
    elapsed  += dt / 60;

    // Refill bubbles
    while (bubbles.length < maxBubbles()) {
      bubbles.push(makeBubble());
    }

    // Update bubbles
    bubbles.forEach(b => {
      b.y -= b.speed * dt;
      b.glowPhase += 0.05 * dt;
      if (b.y + b.r < 0) {
        // Escaped — penalty
        score = Math.max(0, score - 5);
        b.popped = true;
        updateScoreDisplay();
      }
    });
    bubbles = bubbles.filter(b => !b.popped);

    // Update particles
    particles.forEach(p => {
      p.x   += p.vx * dt;
      p.y   += p.vy * dt;
      p.vy  += 0.15 * dt; // gravity
      p.life -= p.decay * dt;
    });
    particles = particles.filter(p => p.life > 0);

    // Draw
    ctx.clearRect(0, 0, W, H);
    bubbles.forEach(drawBubble);
    drawParticles();

    rafId = requestAnimationFrame(loop);
  }

  // ── Click / tap ───────────────────────────────────────────────────────
  function onCanvasClick(e) {
    if (!running) return;
    const rect = canvas.getBoundingClientRect();
    const scaleX = W / rect.width;
    const scaleY = H / rect.height;
    const cx = (e.clientX - rect.left) * scaleX;
    const cy = (e.clientY - rect.top)  * scaleY;

    let hit = false;
    let pts = 0;
    for (let i = bubbles.length - 1; i >= 0; i--) {
      const b  = bubbles[i];
      const dx = cx - b.x;
      const dy = cy - b.y;
      if (dx * dx + dy * dy <= b.r * b.r) {
        pts = b.type.points;
        score += pts;
        spawnParticles(b.x, b.y, b.type.color, b.type.rare ? 10 : 6);
        bubbles.splice(i, 1);
        hit = true;
        updateScoreDisplay();
        break;
      }
    }
    if (!hit) return;

    // Level flash every 50 pts
    if (Math.floor(score / 50) > Math.floor((score - pts) / 50)) {
      scoreEl.classList.remove('game-score-flash');
      void scoreEl.offsetWidth;
      scoreEl.classList.add('game-score-flash');
    }
  }

  // ── Score display ─────────────────────────────────────────────────────
  function updateScoreDisplay() {
    scoreEl.textContent = score;
    if (score > bestScore) {
      bestScore = score;
      bestEl.textContent = bestScore;
    }
  }

  // ── Save score ────────────────────────────────────────────────────────
  async function saveScore() {
    if (score <= 0) return;
    try {
      await fetch(BASE + '/api/game/score', {
        method:  'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        body:    JSON.stringify({ score }),
      });
      loadLeaderboard();
    } catch (_) {}
  }

  // ── Leaderboard ───────────────────────────────────────────────────────
  async function loadLeaderboard() {
    try {
      const res  = await fetch(BASE + '/api/game/leaderboard');
      const data = await res.json();
      if (!data.ok) return;

      const medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
      lbList.innerHTML = data.top5.map((row, i) => `
        <div class="game-lb-row${row.is_me ? ' is-me' : ''}">
          <span class="game-lb-rank">${medals[i] || (i + 1)}</span>
          <span class="game-lb-name">${escHtml(row.name)}</span>
          <span class="game-lb-score">${Number(row.score) || 0}</span>
        </div>
      `).join('');

      if (data.my_score > 0) {
        lbMe.style.display = 'flex';
        lbMyScore.textContent = data.my_score;
        bestScore = Math.max(bestScore, data.my_score);
        bestEl.textContent = bestScore;
      }
    } catch (_) {}
  }

  function escHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // ── Start / stop ──────────────────────────────────────────────────────
  function startGame() {
    if (running) return;
    resize();
    score    = 0;
    bubbles  = [];
    particles = [];
    elapsed  = 0;
    running  = true;
    overlay.style.display = 'none';
    updateScoreDisplay();
    lastTime = performance.now();
    if (rafId) cancelAnimationFrame(rafId);
    rafId = requestAnimationFrame(loop);

    // Auto-save every 30s
    clearInterval(saveTimer);
    saveTimer = setInterval(saveScore, 30000);
  }

  // ── Events ────────────────────────────────────────────────────────────
  startBtn.addEventListener('click', startGame);
  canvas.addEventListener('click', onCanvasClick);

  document.addEventListener('visibilitychange', () => {
    if (document.hidden && running) saveScore();
  });
  window.addEventListener('beforeunload', () => {
    if (!running || score <= 0) return;
    // sendBeacon is more reliable than fetch on page unload
    const payload = JSON.stringify({ score, _csrf: CSRF });
    if (navigator.sendBeacon) {
      navigator.sendBeacon(BASE + '/api/game/score', new Blob([payload], { type: 'application/json' }));
    } else {
      saveScore();
    }
  });

  new ResizeObserver(resize).observe(canvas.parentElement);

  // Initial leaderboard load
  loadLeaderboard();
})();
