(function () {
  'use strict';

  const BASE = window.__V2_BASE || '';
  const CSRF = window.__CSRF   || '';

  const canvas     = document.getElementById('game-canvas');
  const overlay    = document.getElementById('game-overlay');
  const startBtn   = document.getElementById('game-start-btn');
  const scoreEl    = document.getElementById('game-score-display');
  const bestEl     = document.getElementById('game-best-display');
  const lbList     = document.getElementById('game-lb-list');
  const lbMe       = document.getElementById('game-lb-me');
  const lbMyScore  = document.getElementById('game-lb-my-score');
  const timerEl    = document.getElementById('game-timer-display');
  const levelEl    = document.getElementById('game-level-display');

  if (!canvas) return;

  const ctx = canvas.getContext('2d');

  // ── Bubble types ───────────────────────────────────────────────────────
  // כל bubble יכול לשאת label מצחיק שמוצג בתוך הבועה במקום emoji בלבד
  const TYPES = [
    // נפוצים
    { emoji: '😤', color: '#5b8dee', label: 'לקוח מקלל',        points: 10, weight: 22, rare: false, funny: ['לא מקבל אינטרנט!!', 'תחזירו לי את הכסף!', 'זה לא עובד כבר שנה!', 'אני מדבר עם הבוס שלכם!'] },
    { emoji: '📦', color: '#a29bfe', label: 'איפה המשלוח',       points: 10, weight: 20, rare: false, funny: ['זה אמור להגיע אתמול', 'ה-SMS אמר "בדרך"...', 'שבועיים?! שילמתי PRIME', 'הנהג אמר שהגיע...'] },
    { emoji: '🔧', color: '#e17055', label: 'תמיכה טכנית',       points: 10, weight: 18, rare: false, funny: ['ניסיתי לכבות ולהדליק', 'השואב לא שואב', 'האוזניות אחת שותקת', 'הרובוט נתקע בפינה'] },
    { emoji: '💬', color: '#00b894', label: 'וואטסאפ',           points: 10, weight: 18, rare: false, funny: ['למה לא עוניתם?!', 'שלחתי 12 הודעות', 'ראיתי שקראתם!!', 'שלוש נקודות ואז כלום'] },
    { emoji: '📞', color: '#74b9ff', label: 'שיחה חוזרת',        points: 8,  weight: 16, rare: false, funny: ['זו הפעם ה-5 שלי היום', 'נותקתי שוב', 'חיכיתי שעה בתור', 'שלחתם אותי לנציג אחר'] },
    { emoji: '😶', color: '#b2bec3', label: 'אייל הפך לאדיש',    points: 5,  weight: 12, rare: false, funny: ['כן...', 'אוקיי...', 'בסדר...', 'מה שתגיד...'] },
    // בינוניים
    { emoji: '👔', color: '#fdcb6e', label: 'שיחת מנהל',         points: 15, weight: 10, rare: false, funny: ['תעביר לי מנהל עכשיו', 'אני מכיר את הCEO', 'אני עורך דין!', 'אני רושם הכל!'] },
    { emoji: '😡', color: '#fd79a8', label: 'בת-אל כועסת',       points: 15, weight: 8,  rare: false, funny: ['זו לא הדרך לדבר!', 'אני מגישה תלונה!', 'יש לי הכל בצילום מסך', 'אני הולכת לפייסבוק!'] },
    { emoji: '🤦', color: '#6c5ce7', label: 'מנהל תסכול',        points: 15, weight: 8,  rare: false, funny: ['מה עשיתם לי?!', 'הסברתי כבר 3 פעמים', 'אל תשים אותי בהמתנה', 'שמעת בכלל?'] },
    // נדירים
    { emoji: '🤖', color: '#d63031', label: 'שואב נפשע',         points: 25, weight: 4,  rare: true,  funny: ['בלע את השטיח', 'תקוע מתחת לספה', 'מסתובב בחושך', 'שואב את עצמו'] },
    { emoji: '🎧', color: '#fd79a8', label: 'אוזניות רפאים',      points: 20, weight: 5,  rare: true,  funny: ['אחת שותקת!', 'בס יותר מדי', 'זה מציץ?', 'חיברתי ל-5 מכשירים'] },
    { emoji: '⌚', color: '#55efc4', label: 'שעון פילוסוף',       points: 20, weight: 4,  rare: true,  funny: ['לא מודד דופק', 'מחובר לאייפון של אמא', 'הצעדים שגויים', 'ישן עם השעון?'] },
    { emoji: '👑', color: '#f9ca24', label: 'לקוח VIP',           points: 30, weight: 2,  rare: true,  funny: ['אני לקוח 20 שנה!', 'קניתי 12 שואבים', 'מכיר את יוסי המנכל', 'רשמו את שמי!'] },
  ];

  // רמות — כל רמה מוסיפה מהירות + בועות
  const LEVELS = [
    { level: 1, duration: 30, label: 'בוקר רגוע',      maxB: 3, speedMult: 1.0 },
    { level: 2, duration: 30, label: 'אחרי ארוחת צהריים', maxB: 4, speedMult: 1.2 },
    { level: 3, duration: 30, label: 'שיא פנייות',      maxB: 5, speedMult: 1.5 },
    { level: 4, duration: 30, label: 'כולם כועסים',     maxB: 6, speedMult: 1.8 },
    { level: 5, duration: 30, label: 'יום שישי ב-15:00', maxB: 7, speedMult: 2.2 },
    { level: 6, duration: 30, label: 'כאוס מוחלט',      maxB: 8, speedMult: 2.6 },
  ];

  const TOTAL_WEIGHT = TYPES.reduce((s, t) => s + t.weight, 0);

  function randomType() {
    let r = Math.random() * TOTAL_WEIGHT;
    for (const t of TYPES) { r -= t.weight; if (r <= 0) return t; }
    return TYPES[0];
  }

  function randomFunny(type) {
    const arr = type.funny;
    return arr[Math.floor(Math.random() * arr.length)];
  }

  // ── State ──────────────────────────────────────────────────────────────
  let running    = false;
  let score      = 0;
  let bestScore  = 0;
  let bubbles    = [];
  let particles  = [];
  let toasts     = []; // floating text popups on pop
  let lastTime   = 0;
  let elapsed    = 0;  // total seconds played
  let levelTime  = 0;  // seconds in current level
  let levelIdx   = 0;
  let rafId      = null;
  let saveTimer  = null;
  let W = 0, H = 0;

  // ── Resize ─────────────────────────────────────────────────────────────
  function resize() {
    const wrap = canvas.parentElement;
    W = canvas.width  = wrap.clientWidth;
    H = canvas.height = wrap.clientHeight;
  }

  // ── Level logic ────────────────────────────────────────────────────────
  function currentLevel() {
    return LEVELS[Math.min(levelIdx, LEVELS.length - 1)];
  }

  function updateLevel(dt) {
    levelTime += dt / 60;
    const lv = currentLevel();
    const timeLeft = lv.duration - levelTime;

    // Update timer display
    if (timerEl) timerEl.textContent = Math.max(0, Math.ceil(timeLeft));

    if (levelTime >= lv.duration && levelIdx < LEVELS.length - 1) {
      levelIdx++;
      levelTime = 0;
      const next = currentLevel();
      if (levelEl) levelEl.textContent = `${next.level} — ${next.label}`;
      showLevelBanner(next);
    }
  }

  // ── Level banner ───────────────────────────────────────────────────────
  let bannerText = '';
  let bannerAlpha = 0;

  function showLevelBanner(lv) {
    bannerText  = `רמה ${lv.level}: ${lv.label} 🔥`;
    bannerAlpha = 1;
  }

  function drawBanner() {
    if (bannerAlpha <= 0) return;
    ctx.save();
    ctx.globalAlpha = bannerAlpha;
    ctx.font = 'bold 15px Arial, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#fdcb6e';
    ctx.shadowColor = '#f9ca24';
    ctx.shadowBlur = 10;
    ctx.fillText(bannerText, W / 2, H / 2);
    ctx.restore();
    bannerAlpha -= 0.012;
  }

  // ── Bubble factory ─────────────────────────────────────────────────────
  function makeBubble() {
    const type  = randomType();
    const lv    = currentLevel();
    const r     = type.rare ? 30 + Math.random() * 8 : 22 + Math.random() * 8;
    const speed = (0.7 * lv.speedMult) * (0.85 + Math.random() * 0.3);
    return {
      x:         r + Math.random() * (W - 2 * r),
      y:         H + r + 10,
      r,
      speed:     Math.min(speed, 4.5),
      type,
      funny:     randomFunny(type),
      alpha:     1,
      glowPhase: Math.random() * Math.PI * 2,
      popped:    false,
    };
  }

  function maxBubbles() {
    return currentLevel().maxB;
  }

  // ── Particles ──────────────────────────────────────────────────────────
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

  // ── Floating toast text ────────────────────────────────────────────────
  function spawnToast(x, y, text, color) {
    toasts.push({ x, y, text, color, life: 1, vy: -1.2 });
  }

  function drawToasts() {
    toasts.forEach(t => {
      ctx.save();
      ctx.globalAlpha = t.life;
      ctx.font = 'bold 11px Arial, sans-serif';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillStyle = t.color;
      ctx.shadowColor = 'rgba(0,0,0,.5)';
      ctx.shadowBlur = 4;
      ctx.fillText(t.text, t.x, t.y);
      ctx.restore();
    });
  }

  // ── Draw ───────────────────────────────────────────────────────────────
  function drawBubble(b) {
    ctx.save();
    ctx.globalAlpha = b.alpha;

    if (b.type.rare) {
      const glow = 0.5 + 0.5 * Math.sin(b.glowPhase);
      ctx.shadowColor = b.type.color;
      ctx.shadowBlur  = 10 + glow * 14;
    }

    // Circle fill + stroke
    ctx.beginPath();
    ctx.arc(b.x, b.y, b.r, 0, Math.PI * 2);
    ctx.fillStyle = b.type.color + '2a';
    ctx.fill();
    ctx.strokeStyle = b.type.color;
    ctx.lineWidth = b.type.rare ? 2.5 : 1.5;
    ctx.stroke();

    // Emoji (top half)
    ctx.shadowBlur = 0;
    const emojiSize = Math.round(b.r * 0.85);
    ctx.font = `${emojiSize}px serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#fff';
    ctx.fillText(b.type.emoji, b.x, b.y - b.r * 0.22);

    // Funny text (bottom of bubble)
    const maxW = b.r * 1.7;
    const fontSize = Math.max(7, Math.round(b.r * 0.36));
    ctx.font = `bold ${fontSize}px Arial, sans-serif`;
    ctx.fillStyle = '#fff';
    ctx.globalAlpha = b.alpha * 0.85;
    // clip text to fit
    let txt = b.funny;
    while (ctx.measureText(txt).width > maxW && txt.length > 3) {
      txt = txt.slice(0, -1);
    }
    if (txt !== b.funny) txt += '..';
    ctx.fillText(txt, b.x, b.y + b.r * 0.45);

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

  // ── Game loop ──────────────────────────────────────────────────────────
  function loop(ts) {
    if (!running) return;
    const dt = Math.min((ts - lastTime) / 16.67, 3);
    lastTime  = ts;
    elapsed  += dt / 60;

    updateLevel(dt);

    while (bubbles.length < maxBubbles()) {
      bubbles.push(makeBubble());
    }

    bubbles.forEach(b => {
      b.y -= b.speed * dt;
      b.glowPhase += 0.05 * dt;
      if (b.y + b.r < 0) {
        score = Math.max(0, score - 5);
        b.popped = true;
        updateScoreDisplay();
      }
    });
    bubbles = bubbles.filter(b => !b.popped);

    particles.forEach(p => {
      p.x  += p.vx * dt;
      p.y  += p.vy * dt;
      p.vy += 0.15 * dt;
      p.life -= p.decay * dt;
    });
    particles = particles.filter(p => p.life > 0);

    toasts.forEach(t => {
      t.y  += t.vy * dt;
      t.life -= 0.025 * dt;
    });
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
        spawnParticles(b.x, b.y, b.type.color, b.type.rare ? 12 : 7);
        spawnToast(b.x, b.y - b.r - 8, `+${pts}`, b.type.color);
        bubbles.splice(i, 1);
        hit = true;
        updateScoreDisplay();
        break;
      }
    }
    if (!hit) return;

    if (Math.floor(score / 50) > Math.floor((score - pts) / 50)) {
      scoreEl.classList.remove('game-score-flash');
      void scoreEl.offsetWidth;
      scoreEl.classList.add('game-score-flash');
    }
  }

  // ── Score ──────────────────────────────────────────────────────────────
  function updateScoreDisplay() {
    scoreEl.textContent = score;
    if (score > bestScore) {
      bestScore = score;
      bestEl.textContent = bestScore;
    }
  }

  // ── Save ───────────────────────────────────────────────────────────────
  async function saveScore() {
    if (score <= 0) return;
    try {
      await fetch(BASE + '/api/game-score.php', {
        method:  'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        body:    JSON.stringify({ score }),
      });
      loadLeaderboard();
    } catch (_) {}
  }

  // ── Leaderboard ────────────────────────────────────────────────────────
  async function loadLeaderboard() {
    try {
      const res  = await fetch(BASE + '/api/game-leaderboard.php');
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

  // ── Start ──────────────────────────────────────────────────────────────
  function startGame() {
    if (running) return;
    resize();
    score     = 0;
    bubbles   = [];
    particles = [];
    toasts    = [];
    elapsed   = 0;
    levelTime = 0;
    levelIdx  = 0;
    running   = true;
    bannerAlpha = 0;
    overlay.style.display = 'none';
    updateScoreDisplay();

    const lv = currentLevel();
    if (levelEl) levelEl.textContent = `${lv.level} — ${lv.label}`;
    if (timerEl) timerEl.textContent = lv.duration;

    lastTime = performance.now();
    if (rafId) cancelAnimationFrame(rafId);
    rafId = requestAnimationFrame(loop);

    clearInterval(saveTimer);
    saveTimer = setInterval(saveScore, 30000);
  }

  // ── Events ─────────────────────────────────────────────────────────────
  startBtn.addEventListener('click', startGame);
  canvas.addEventListener('click', onCanvasClick);

  document.addEventListener('visibilitychange', () => {
    if (document.hidden && running) saveScore();
  });

  window.addEventListener('beforeunload', () => {
    if (!running || score <= 0) return;
    const payload = JSON.stringify({ score, _csrf: CSRF });
    if (navigator.sendBeacon) {
      navigator.sendBeacon(BASE + '/api/game-score.php', new Blob([payload], { type: 'application/json' }));
    } else {
      saveScore();
    }
  });

  new ResizeObserver(resize).observe(canvas.parentElement);
  loadLeaderboard();
})();
