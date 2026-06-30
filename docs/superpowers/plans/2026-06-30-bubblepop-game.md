# BubblePop Game Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a BubblePop mini-game widget to the dashboard — bubbles rise, click to pop, score saved all-time per user with a top-5 leaderboard.

**Architecture:** A 400×300px widget block appended to the bottom of `views/pages/dashboard.php`, split 60/40 between a Canvas game and a leaderboard panel. Game logic lives entirely in `public/game.js` (Canvas API, vanilla JS). Two standalone PHP API files under `public/api/game/` handle score persistence and leaderboard reads.

**Tech Stack:** PHP 8.1, vanilla JS, HTML5 Canvas API, CSS custom properties (existing dark theme vars).

## Global Constraints

- PHP 8.1+, `declare(strict_types=1)` in every PHP file
- No external frameworks or Composer packages
- CSRF required on every POST: header `X-CSRF-TOKEN` or `_csrf` POST field
- Auth required on every API endpoint — use `Auth::user()` returning `null` if not logged in
- DB is `DB::*` (alon_db2 V2); table `game_scores` already exists with columns: `id`, `user_id`, `score`, `played_at`
- JS: use `window.__V2_BASE` for base URL, `window.__CSRF` for CSRF token
- RTL Hebrew UI — labels in Hebrew, `dir="rtl"` inherited from layout
- No new routes needed — APIs live in `public/api/` (standalone bootstrap pattern)
- CSS uses existing vars: `--bg`, `--bg2`, `--bg3`, `--bg4`, `--border`, `--border2`, `--accent`, `--accent-dim`, `--text`, `--text2`, `--text3`, `--radius`, `--success`, `--danger`, `--warning`, `--font`

---

## File Map

| File | Action | Responsibility |
|------|--------|----------------|
| `public/api/game/score.php` | Create | POST — save score if higher than current best |
| `public/api/game/leaderboard.php` | Create | GET — return top-5 + caller's own score |
| `public/game.css` | Create | Widget layout + canvas styles |
| `public/game.js` | Create | Full Canvas game loop, bubble logic, particles, leaderboard refresh |
| `views/pages/dashboard.php` | Modify | Append widget HTML block + load game.css/game.js |

---

### Task 1: API — Save Score

**Files:**
- Create: `public/api/game/score.php`

**Interfaces:**
- Consumes: `POST /api/game/score` with JSON body `{"score": 350}` and header `X-CSRF-TOKEN`
- Produces: `{"ok": true, "saved": true}` if new score is higher, `{"ok": true, "saved": false}` if not

- [ ] **Step 1: Create `public/api/game/score.php`**

```php
<?php
declare(strict_types=1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';
use Core\Auth;
use Core\DB;

header('Content-Type: application/json; charset=utf-8');

if (!Auth::user()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method not allowed']);
    exit;
}

$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$csrfSession = $_SESSION['csrf_token'] ?? '';
if (!$csrfHeader || !hash_equals($csrfSession, $csrfHeader)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true);
$score = (int)($body['score'] ?? 0);

if ($score <= 0 || $score > 99999) {
    echo json_encode(['ok' => false, 'error' => 'invalid score']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'no user']);
    exit;
}

try {
    $current = (int)DB::value(
        'SELECT score FROM game_scores WHERE user_id = ?',
        [$userId]
    );

    if ($score <= $current) {
        echo json_encode(['ok' => true, 'saved' => false]);
        exit;
    }

    DB::execute(
        'INSERT INTO game_scores (user_id, score, played_at)
         VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE score = VALUES(score), played_at = NOW()',
        [$userId, $score]
    );

    echo json_encode(['ok' => true, 'saved' => true]);
} catch (Throwable $ex) {
    error_log('[game/score] ' . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server error']);
}
```

- [ ] **Step 2: Verify the file exists and is readable**

```
ls public/api/game/score.php
```
Expected: file listed.

- [ ] **Step 3: Commit**

```bash
git add public/api/game/score.php
git commit -m "feat: add game score API endpoint"
```

---

### Task 2: API — Leaderboard

**Files:**
- Create: `public/api/game/leaderboard.php`

**Interfaces:**
- Consumes: `GET /api/game/leaderboard` (no body, session auth)
- Produces:
```json
{
  "top5": [
    {"name": "דני כהן", "score": 890, "is_me": false},
    {"name": "אלון ו׳", "score": 580, "is_me": true}
  ],
  "my_score": 580
}
```

- [ ] **Step 1: Create `public/api/game/leaderboard.php`**

```php
<?php
declare(strict_types=1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';
use Core\Auth;
use Core\DB;

header('Content-Type: application/json; charset=utf-8');

if (!Auth::user()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);

try {
    $top5 = DB::query(
        'SELECT u.first_name, u.last_name, gs.score, gs.user_id
         FROM game_scores gs
         JOIN users u ON u.id = gs.user_id
         ORDER BY gs.score DESC
         LIMIT 5',
        []
    );

    $myScore = (int)DB::value(
        'SELECT score FROM game_scores WHERE user_id = ?',
        [$userId]
    );

    $result = array_map(function ($row) use ($userId) {
        $first = $row['first_name'] ?? '';
        $last  = $row['last_name']  ?? '';
        // Show first name + first letter of last name for privacy
        $name  = $first . (strlen($last) ? ' ' . mb_substr($last, 0, 1) . '\'' : '');
        return [
            'name'  => $name,
            'score' => (int)$row['score'],
            'is_me' => (int)$row['user_id'] === $userId,
        ];
    }, $top5 ?: []);

    echo json_encode(['ok' => true, 'top5' => $result, 'my_score' => $myScore]);
} catch (Throwable $ex) {
    error_log('[game/leaderboard] ' . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server error']);
}
```

- [ ] **Step 2: Commit**

```bash
git add public/api/game/leaderboard.php
git commit -m "feat: add game leaderboard API endpoint"
```

---

### Task 3: Widget CSS

**Files:**
- Create: `public/game.css`

**Interfaces:**
- Produces: CSS classes `.game-widget`, `.game-canvas-wrap`, `.game-leaderboard`, `.game-header`, `.game-btn` used by Task 4 (dashboard HTML) and Task 5 (game.js)

- [ ] **Step 1: Create `public/game.css`**

```css
/* ── BubblePop widget ── */
.game-widget {
  display: flex;
  flex-direction: column;
  gap: 0;
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  margin-top: 20px;
  user-select: none;
}

.game-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  border-bottom: 1px solid var(--border);
  background: var(--bg3);
}

.game-header-title {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
}

.game-header-scores {
  display: flex;
  gap: 14px;
  font-size: 12px;
  color: var(--text2);
}

.game-header-scores span b {
  color: var(--accent);
  font-size: 14px;
}

.game-body {
  display: flex;
  height: 260px;
}

.game-canvas-wrap {
  position: relative;
  flex: 1;
  background: var(--bg);
  border-left: 1px solid var(--border);
}

.game-canvas-wrap canvas {
  display: block;
  width: 100%;
  height: 100%;
}

.game-start-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  background: rgba(0,0,0,.55);
  backdrop-filter: blur(2px);
}

.game-start-overlay p {
  font-size: 12px;
  color: var(--text2);
  text-align: center;
  padding: 0 20px;
}

.game-btn {
  padding: 7px 20px;
  border-radius: 8px;
  border: none;
  background: var(--accent);
  color: #fff;
  font-family: var(--font);
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: opacity .15s, transform .1s;
}

.game-btn:hover { opacity: .85; transform: scale(1.03); }
.game-btn:active { transform: scale(.97); }

.game-leaderboard {
  width: 148px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  padding: 10px 10px 6px;
  gap: 5px;
  background: var(--bg2);
}

.game-lb-title {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--text3);
  margin-bottom: 3px;
}

.game-lb-row {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: var(--text2);
  padding: 3px 4px;
  border-radius: 5px;
  transition: background .1s;
}

.game-lb-row.is-me {
  background: var(--accent-dim);
  color: var(--accent);
  font-weight: 700;
}

.game-lb-rank {
  font-size: 11px;
  width: 18px;
  text-align: center;
  flex-shrink: 0;
  color: var(--text3);
}

.game-lb-row.is-me .game-lb-rank { color: var(--accent); }

.game-lb-name {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.game-lb-score {
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  font-size: 12px;
}

.game-lb-divider {
  border: none;
  border-top: 1px solid var(--border);
  margin: 4px 0;
}

.game-lb-me-row {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--accent);
  font-weight: 600;
  padding: 2px 4px;
}

.game-score-flash {
  animation: gsflash .6s ease-out;
}

@keyframes gsflash {
  0%   { color: #fff; transform: scale(1.25); }
  100% { color: var(--accent); transform: scale(1); }
}
```

- [ ] **Step 2: Commit**

```bash
git add public/game.css
git commit -m "feat: add game widget CSS"
```

---

### Task 4: Dashboard Widget HTML

**Files:**
- Modify: `views/pages/dashboard.php` — append at end of file (before closing `</script>` tag there is none; append after the last `</div>` and last `</script>`)

**Interfaces:**
- Consumes: `public/game.css`, `public/game.js` (loaded here)
- Produces: DOM elements with IDs `game-widget`, `game-canvas`, `game-overlay`, `game-score-display`, `game-best-display`, `game-lb-list`, `game-lb-me`

- [ ] **Step 1: Append widget HTML to end of `views/pages/dashboard.php`**

Add the following block at the very end of the file (after line 1086 `renderFiltered();` and the closing `</script>`):

```php
<!-- ── BubblePop Game Widget ── -->
<link rel="stylesheet" href="<?= $base ?>/game.css">

<div class="game-widget" id="game-widget">
  <div class="game-header">
    <div class="game-header-title">
      <i class="bi bi-controller" style="color:var(--accent)"></i>
      BubblePop
    </div>
    <div class="game-header-scores">
      <span>ניקוד: <b id="game-score-display">0</b></span>
      <span>שיא: <b id="game-best-display">0</b></span>
    </div>
  </div>
  <div class="game-body">
    <!-- leaderboard — RIGHT side (RTL) -->
    <div class="game-leaderboard">
      <div class="game-lb-title">🏆 טבלת שיאים</div>
      <div id="game-lb-list"></div>
      <hr class="game-lb-divider">
      <div id="game-lb-me" class="game-lb-me-row" style="display:none">
        <span>➤ אתה:</span><span id="game-lb-my-score">0</span>
      </div>
    </div>
    <!-- canvas — LEFT side -->
    <div class="game-canvas-wrap">
      <canvas id="game-canvas"></canvas>
      <div class="game-start-overlay" id="game-overlay">
        <p>פוצץ את הפניות לפני שיגיעו לראש!<br>
           🤖 שואב רובוטי = +25 &nbsp;|&nbsp; 🎧 אוזניות = +20</p>
        <button class="game-btn" id="game-start-btn">▶ התחל</button>
      </div>
    </div>
  </div>
</div>

<script src="<?= $base ?>/game.js"></script>
```

- [ ] **Step 2: Verify dashboard.php renders without PHP errors**

Open the dashboard in a browser (or run `php -l views/pages/dashboard.php`) — no parse errors.

- [ ] **Step 3: Commit**

```bash
git add views/pages/dashboard.php
git commit -m "feat: add BubblePop widget HTML to dashboard"
```

---

### Task 5: Game JavaScript

**Files:**
- Create: `public/game.js`

**Interfaces:**
- Consumes: DOM IDs from Task 4: `game-canvas`, `game-overlay`, `game-start-btn`, `game-score-display`, `game-best-display`, `game-lb-list`, `game-lb-me`, `game-lb-my-score`
- Consumes: `window.__V2_BASE`, `window.__CSRF`
- Consumes: `GET ${BASE}/api/game/leaderboard`, `POST ${BASE}/api/game/score`
- Produces: running game with bubbles, particles, score tracking, auto-save

- [ ] **Step 1: Create `public/game.js`**

```js
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
    for (let i = bubbles.length - 1; i >= 0; i--) {
      const b  = bubbles[i];
      const dx = cx - b.x;
      const dy = cy - b.y;
      if (dx * dx + dy * dy <= b.r * b.r) {
        score += b.type.points;
        spawnParticles(b.x, b.y, b.type.color, b.type.rare ? 10 : 6);
        bubbles.splice(i, 1);
        hit = true;
        updateScoreDisplay();
        break;
      }
    }
    if (!hit) return;

    // Level flash every 50 pts
    if (Math.floor(score / 50) > Math.floor((score - (bubbles[0]?.type?.points || 10)) / 50)) {
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
          <span class="game-lb-score">${row.score}</span>
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
    resize();
    score    = 0;
    bubbles  = [];
    particles = [];
    elapsed  = 0;
    running  = true;
    overlay.style.display = 'none';
    updateScoreDisplay();
    lastTime = performance.now();
    rafId = requestAnimationFrame(loop);

    // Auto-save every 30s
    saveTimer = setInterval(saveScore, 30000);
  }

  // ── Events ────────────────────────────────────────────────────────────
  startBtn.addEventListener('click', startGame);
  canvas.addEventListener('click', onCanvasClick);

  document.addEventListener('visibilitychange', () => {
    if (document.hidden && running) saveScore();
  });
  window.addEventListener('beforeunload', () => {
    if (running) saveScore();
  });

  new ResizeObserver(resize).observe(canvas.parentElement);

  // Initial leaderboard load
  loadLeaderboard();
})();
```

- [ ] **Step 2: Open dashboard in browser and verify:**
  - Widget appears below the stores section
  - "התחל" button starts the game
  - Bubbles with emoji rise from bottom
  - Clicking a bubble pops it with particles and increments score
  - Rare bubbles (🤖 🎧) glow
  - Leaderboard panel shows on the right

- [ ] **Step 3: Commit**

```bash
git add public/game.js
git commit -m "feat: add BubblePop game engine"
```

---

### Task 6: Smoke Test & Final Commit

**Files:** none new

- [ ] **Step 1: Test score saving**

Open browser DevTools → Network. Play game, wait 30s or trigger `beforeunload`. Confirm `POST /api/game/score` returns `{"ok":true,"saved":true}`.

- [ ] **Step 2: Test leaderboard**

Confirm `GET /api/game/leaderboard` returns valid JSON with `top5` array. Verify your name appears highlighted (`.is-me`) if you have a score.

- [ ] **Step 3: Test penalty**

Let a bubble escape the top — confirm score decreases by 5 (min 0).

- [ ] **Step 4: Test rare bubbles**

Wait ~1 minute for a 🤖 or 🎧 bubble to appear — confirm glow effect and +20/+25 points on click.

- [ ] **Step 5: Final commit**

```bash
git add -A
git commit -m "feat: BubblePop game widget complete"
```
