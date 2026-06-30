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

  // ── סוגי בועות ────────────────────────────────────────────────────────
  // bad:true = מפסידים נקודות בלחיצה (בועות "טובות" שלא צריך לפוצץ)
  const TYPES = [
    // ── רעים — מפוצצים = +ניקוד ──────────────────────────────────────
    {
      emoji: '😤', color: '#5b8dee', label: 'לקוח מקלל', points: 10, weight: 22, rare: false, bad: false,
      funny: ['לא מקבל אינטרנט!!', 'תחזירו לי את הכסף!', 'זה לא עובד כבר שנה!', 'אני מדבר עם הבוס!'],
    },
    {
      emoji: '📦', color: '#a29bfe', label: 'איפה המשלוח', points: 10, weight: 20, rare: false, bad: false,
      funny: ['זה אמור להגיע אתמול', 'ה-SMS אמר "בדרך"...', 'שבועיים?! שילמתי PRIME', 'הנהג אמר שהגיע...'],
    },
    {
      emoji: '🔧', color: '#e17055', label: 'תמיכה טכנית', points: 10, weight: 18, rare: false, bad: false,
      funny: ['ניסיתי לכבות ולהדליק', 'השואב לא שואב', 'האוזניות אחת שותקת', 'הרובוט נתקע בפינה'],
    },
    {
      emoji: '💬', color: '#00b894', label: 'וואטסאפ', points: 10, weight: 18, rare: false, bad: false,
      funny: ['למה לא עוניתם?!', 'שלחתי 12 הודעות', 'ראיתי שקראתם!!', 'שלוש נקודות ואז כלום'],
    },
    {
      emoji: '📞', color: '#74b9ff', label: 'שיחה חוזרת', points: 8, weight: 16, rare: false, bad: false,
      funny: ['זו הפעם ה-5 שלי היום', 'נותקתי שוב', 'חיכיתי שעה בתור', 'שלחתם אותי לנציג אחר'],
    },
    {
      emoji: '😶', color: '#b2bec3', label: 'אייל האדיש', points: 5, weight: 12, rare: false, bad: false,
      funny: ['כן...', 'אוקיי...', 'בסדר...', 'מה שתגיד...'],
    },
    {
      emoji: '👔', color: '#fdcb6e', label: 'שיחת מנהל', points: 15, weight: 10, rare: false, bad: false,
      funny: ['תעביר לי מנהל עכשיו', 'אני מכיר את הCEO', 'אני עורך דין!', 'אני רושם הכל!'],
    },
    {
      emoji: '😡', color: '#fd79a8', label: 'בת-אל כועסת', points: 15, weight: 8, rare: false, bad: false,
      funny: ['זו לא הדרך לדבר!', 'אני מגישה תלונה!', 'יש לי הכל בצילום מסך', 'אני הולכת לפייסבוק!'],
    },
    {
      emoji: '🤦', color: '#6c5ce7', label: 'מנהל תסכול', points: 15, weight: 8, rare: false, bad: false,
      funny: ['מה עשיתם לי?!', 'הסברתי כבר 3 פעמים', 'אל תשים אותי בהמתנה', 'שמעת בכלל?'],
    },
    // ── נדירים חיוביים — מפוצצים = +ניקוד גבוה ─────────────────────
    {
      emoji: '🤖', color: '#d63031', label: 'שואב נפשע', points: 25, weight: 4, rare: true, bad: false,
      funny: ['בלע את השטיח', 'תקוע מתחת לספה', 'מסתובב בחושך', 'שואב את עצמו'],
    },
    {
      emoji: '🎧', color: '#e84393', label: 'אוזניות רפאים', points: 20, weight: 5, rare: true, bad: false,
      funny: ['אחת שותקת!', 'בס יותר מדי', 'זה מציץ?', 'חיברתי ל-5 מכשירים'],
    },
    {
      emoji: '⌚', color: '#55efc4', label: 'שעון פילוסוף', points: 20, weight: 4, rare: true, bad: false,
      funny: ['לא מודד דופק', 'מחובר לאייפון של אמא', 'הצעדים שגויים', 'ישן עם השעון?'],
    },
    {
      emoji: '👑', color: '#f9ca24', label: 'לקוח VIP', points: 30, weight: 2, rare: true, bad: false,
      funny: ['אני לקוח 20 שנה!', 'קניתי 12 שואבים', 'מכיר את יוסי המנכל', 'רשמו את שמי!'],
    },
    // ── בועות "טובות" — לא לפוצץ! מורידות ניקוד ────────────────────
    {
      emoji: '😊', color: '#27ae60', label: 'לקוח מרוצה', points: -15, weight: 8, rare: false, bad: true,
      funny: ['תודה רבה!', 'שירות מעולה!', 'אתם הכי טובים', 'המלצתי לכולם!'],
    },
    {
      emoji: '🌟', color: '#f39c12', label: 'ביקורת 5 כוכבים', points: -20, weight: 5, rare: false, bad: true,
      funny: ['נתתי 5 כוכבים!', 'מה שירות!', 'הנציג היה מדהים', 'אני חוזר בטוח!'],
    },
    {
      emoji: '🎁', color: '#16a085', label: 'מבצע מיוחד', points: -10, weight: 6, rare: false, bad: true,
      funny: ['רצה לשדרג', 'מעוניין בחבילה', 'האם יש מבצע?', 'חבר הפנה אותי'],
    },
    {
      emoji: '☕', color: '#795548', label: 'הפסקת קפה', points: -25, weight: 3, rare: true, bad: true,
      funny: ['הקפה מוכן!', 'מגיע לך הפסקה', 'נשנוש בהמתנה', 'אספרסו או קפה שחור?'],
    },
  ];

  const LEVELS = [
    { level: 1, duration: 30, label: 'בוקר רגוע',        maxB: 3, speedMult: 1.0 },
    { level: 2, duration: 30, label: 'אחרי צהריים',        maxB: 4, speedMult: 1.2 },
    { level: 3, duration: 30, label: 'שיא פנייות',        maxB: 5, speedMult: 1.5 },
    { level: 4, duration: 30, label: 'כולם כועסים',       maxB: 6, speedMult: 1.8 },
    { level: 5, duration: 30, label: 'יום שישי 15:00 😱', maxB: 7, speedMult: 2.2 },
    { level: 6, duration: 99, label: 'כאוס מוחלט 🔥',     maxB: 8, speedMult: 2.6 },
  ];

  const BAD_TYPES  = TYPES.filter(t => t.bad);
  const GOOD_TYPES = TYPES.filter(t => !t.bad);
  const GOOD_WEIGHT = GOOD_TYPES.reduce((s, t) => s + t.weight, 0);
  const BAD_WEIGHT  = BAD_TYPES.reduce((s, t) => s + t.weight, 0);

  function randomType() {
    // ~20% סיכוי לבועה "טובה"
    if (Math.random() < 0.2) {
      let r = Math.random() * BAD_WEIGHT;
      for (const t of BAD_TYPES) { r -= t.weight; if (r <= 0) return t; }
      return BAD_TYPES[0];
    }
    let r = Math.random() * GOOD_WEIGHT;
    for (const t of GOOD_TYPES) { r -= t.weight; if (r <= 0) return t; }
    return GOOD_TYPES[0];
  }

  function randomFunny(type) {
    const arr = type.funny;
    return arr[Math.floor(Math.random() * arr.length)];
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

  // ── Level logic ────────────────────────────────────────────────────────
  function currentLevel() { return LEVELS[Math.min(levelIdx, LEVELS.length - 1)]; }

  function updateLevel(dt) {
    levelTime += dt / 60;
    const lv = currentLevel();
    const timeLeft = lv.duration - levelTime;
    if (timerEl) timerEl.textContent = Math.max(0, Math.ceil(timeLeft));

    if (levelTime >= lv.duration && levelIdx < LEVELS.length - 1) {
      levelIdx++;
      levelTime = 0;
      const next = currentLevel();
      if (levelEl) levelEl.textContent = `${next.level} — ${next.label}`;
      bannerText  = `רמה ${next.level}: ${next.label}`;
      bannerAlpha = 1.5;
    }
  }

  // ── Bubble factory ─────────────────────────────────────────────────────
  function makeBubble() {
    const type  = randomType();
    const lv    = currentLevel();
    const r     = type.rare ? 32 + Math.random() * 8
                : type.bad  ? 26 + Math.random() * 6
                :             24 + Math.random() * 8;
    const speed = (0.65 * lv.speedMult) * (0.85 + Math.random() * 0.3);
    return {
      x: r + Math.random() * (W - 2 * r),
      y: H + r + 10,
      r,
      speed: Math.min(speed, 4.5),
      type,
      funny: randomFunny(type),
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
      const spd   = 2 + Math.random() * 3;
      particles.push({
        x, y,
        vx: Math.cos(angle) * spd,
        vy: Math.sin(angle) * spd,
        r: 3 + Math.random() * 3,
        color,
        life: 1,
        decay: 0.035 + Math.random() * 0.04,
      });
    }
  }

  // ── Floating toast ─────────────────────────────────────────────────────
  function spawnToast(x, y, text, color) {
    toasts.push({ x, y, text, color, life: 1.2, vy: -1.5 });
  }

  // ── Draw ───────────────────────────────────────────────────────────────
  function drawBubble(b) {
    ctx.save();
    ctx.globalAlpha = b.alpha;

    // Glow — rare=חזק, bad=ירוק עדין
    if (b.type.rare) {
      const g = 0.5 + 0.5 * Math.sin(b.glowPhase);
      ctx.shadowColor = b.type.color;
      ctx.shadowBlur  = 12 + g * 16;
    } else if (b.type.bad) {
      const g = 0.4 + 0.4 * Math.sin(b.glowPhase * 0.7);
      ctx.shadowColor = b.type.color;
      ctx.shadowBlur  = 6 + g * 8;
    }

    // Circle
    ctx.beginPath();
    ctx.arc(b.x, b.y, b.r, 0, Math.PI * 2);
    ctx.fillStyle = b.type.color + (b.type.bad ? '22' : '2a');
    ctx.fill();
    ctx.strokeStyle = b.type.color;
    ctx.lineWidth   = b.type.rare ? 2.5 : b.type.bad ? 2 : 1.5;
    // bad bubbles — קו מקווקו
    if (b.type.bad) ctx.setLineDash([4, 3]);
    ctx.stroke();
    ctx.setLineDash([]);

    // Emoji — centered
    ctx.shadowBlur = 0;
    ctx.font = `${Math.round(b.r * 0.9)}px serif`;
    ctx.textAlign    = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle    = '#fff';
    ctx.fillText(b.type.emoji, b.x, b.y);

    // Warning badge on bad bubbles — ❌ top-right
    if (b.type.bad) {
      ctx.font = `${Math.round(b.r * 0.45)}px serif`;
      ctx.fillText('❌', b.x + b.r * 0.6, b.y - b.r * 0.6);
    }

    ctx.restore();
  }

  function drawParticles() {
    particles.forEach(p => {
      ctx.save();
      ctx.globalAlpha = Math.min(p.life, 1);
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r * Math.min(p.life, 1), 0, Math.PI * 2);
      ctx.fillStyle = p.color;
      ctx.fill();
      ctx.restore();
    });
  }

  function drawToasts() {
    toasts.forEach(t => {
      ctx.save();
      ctx.globalAlpha = Math.min(t.life, 1);
      ctx.font = 'bold 13px Arial, sans-serif';
      ctx.textAlign    = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillStyle    = t.color;
      ctx.shadowColor  = 'rgba(0,0,0,.7)';
      ctx.shadowBlur   = 5;
      ctx.fillText(t.text, t.x, t.y);
      // funny line below score
      if (t.label) {
        ctx.globalAlpha *= 0.85;
        ctx.font = '10px Arial, sans-serif';
        ctx.fillText(t.label, t.x, t.y + 14);
      }
      ctx.restore();
    });
  }

  function drawBanner() {
    if (bannerAlpha <= 0) return;
    const a = Math.min(bannerAlpha, 1);
    ctx.save();
    ctx.globalAlpha    = a;
    ctx.font           = 'bold 16px Arial, sans-serif';
    ctx.textAlign      = 'center';
    ctx.textBaseline   = 'middle';
    ctx.fillStyle      = '#fdcb6e';
    ctx.shadowColor    = '#f9ca24';
    ctx.shadowBlur     = 14;
    ctx.fillText(bannerText, W / 2, H / 2);
    ctx.restore();
    bannerAlpha -= 0.01;
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
        // bad bubble escaped = no penalty (good!)
        if (!b.type.bad) {
          score = Math.max(0, score - 5);
          updateScoreDisplay();
        }
        b.popped = true;
      }
    });
    bubbles = bubbles.filter(b => !b.popped);

    particles.forEach(p => {
      p.x  += p.vx * dt; p.y += p.vy * dt;
      p.vy += 0.15 * dt;
      p.life -= p.decay * dt;
    });
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
    const rect   = canvas.getBoundingClientRect();
    const scaleX = W / rect.width;
    const scaleY = H / rect.height;
    const cx = (e.clientX - rect.left) * scaleX;
    const cy = (e.clientY - rect.top)  * scaleY;

    let hit = false, pts = 0;
    for (let i = bubbles.length - 1; i >= 0; i--) {
      const b  = bubbles[i];
      const dx = cx - b.x, dy = cy - b.y;
      if (dx * dx + dy * dy <= b.r * b.r) {
        pts = b.type.points; // negative for bad bubbles
        const prevScore = score;
        score = Math.max(0, score + pts);
        const delta = score - prevScore;

        spawnParticles(b.x, b.y, b.type.color, b.type.rare ? 12 : b.type.bad ? 5 : 7);

        // toast: score delta + funny text
        const toastColor = b.type.bad ? '#e74c3c' : b.type.color;
        const deltaStr   = delta >= 0 ? `+${delta}` : `${delta}`;
        toasts.push({
          x: b.x, y: b.y - b.r - 6,
          text:  deltaStr,
          label: b.funny,
          color: toastColor,
          life: 1.4, vy: -1.4,
        });

        bubbles.splice(i, 1);
        hit = true;
        updateScoreDisplay();
        break;
      }
    }
    if (!hit) return;

    // level flash every 50 pts (only on gain)
    if (pts > 0 && Math.floor(score / 50) > Math.floor((score - pts) / 50)) {
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

  // ── Save — שולח לשרת; השרת שומר רק אם הניקוד גבוה מהשיא הקיים ────────
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
        lbMe.style.display  = 'flex';
        lbMyScore.textContent = data.my_score;
        bestScore = Math.max(bestScore, data.my_score);
        bestEl.textContent  = bestScore;
      }
    } catch (_) {}
  }

  function escHtml(s) {
    return String(s || '')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Start ──────────────────────────────────────────────────────────────
  function startGame() {
    if (running) return;
    resize();
    score = 0; bubbles = []; particles = []; toasts = [];
    elapsed = 0; levelTime = 0; levelIdx = 0;
    bannerAlpha = 0;
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
    saveTimer = setInterval(saveScore, 30000);
  }

  // ── Close widget ───────────────────────────────────────────────────────
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
