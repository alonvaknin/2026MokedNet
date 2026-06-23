<?php
// Splash — shown once per browser (localStorage key: mn_splash_v2)
// 5-second countdown, then close button activates
?>
<link href="https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<div id="mn-splash">
<style>
#mn-splash{
  position:fixed;inset:0;z-index:99999;
  display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,#0a0f1e 0%,#0d1528 40%,#111827 100%);
  font-family:'Heebo',sans-serif;
  direction:rtl;
}

/* animated background particles */
#mn-splash-bg{
  position:absolute;inset:0;overflow:hidden;pointer-events:none;
}
.sp-orb{
  position:absolute;border-radius:50%;filter:blur(80px);opacity:.18;
  animation:orbFloat linear infinite;
}
@keyframes orbFloat{
  0%{transform:translateY(0) scale(1)}
  50%{transform:translateY(-40px) scale(1.08)}
  100%{transform:translateY(0) scale(1)}
}

#mn-splash-inner{
  position:relative;z-index:2;
  display:flex;flex-direction:column;align-items:center;
  max-width:700px;width:calc(100% - 40px);
  animation:splashIn .6s cubic-bezier(.4,0,.2,1) both;
}
@keyframes splashIn{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}

/* badge */
.sp-badge{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(91,141,238,.15);
  border:1px solid rgba(91,141,238,.35);
  border-radius:100px;
  padding:8px 20px;
  font-size:13px;font-weight:600;color:#7aabff;
  letter-spacing:.04em;
  margin-bottom:28px;
  animation:badgeIn .5s .15s both;
}
@keyframes badgeIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
.sp-badge-dot{
  width:8px;height:8px;border-radius:50%;
  background:#5b8dee;
  box-shadow:0 0 8px #5b8dee;
  animation:pulse 1.5s ease-in-out infinite;
}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.7)}}

/* headline */
.sp-headline{
  font-size:clamp(40px,8vw,72px);
  font-weight:900;
  color:#fff;
  text-align:center;
  line-height:1.1;
  margin-bottom:14px;
  animation:headIn .55s .25s both;
}
@keyframes headIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.sp-headline span{
  background:linear-gradient(90deg,#5b8dee,#a78bfa,#5b8dee);
  background-size:200% auto;
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  animation:shimmer 3s linear infinite;
}
@keyframes shimmer{to{background-position:200% center}}

.sp-sub{
  font-size:clamp(16px,2.5vw,20px);
  color:rgba(255,255,255,.55);
  font-weight:400;
  text-align:center;
  margin-bottom:52px;
  animation:headIn .55s .35s both;
}

/* features grid */
.sp-grid{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:14px;
  width:100%;
  margin-bottom:48px;
}
.sp-card{
  background:rgba(255,255,255,.05);
  border:1px solid rgba(255,255,255,.09);
  border-radius:16px;
  padding:20px 22px;
  display:flex;align-items:flex-start;gap:14px;
  animation:cardIn .5s both;
  transition:background .2s,border-color .2s;
}
.sp-card:hover{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.16)}
.sp-card:nth-child(1){animation-delay:.4s}
.sp-card:nth-child(2){animation-delay:.5s}
.sp-card:nth-child(3){animation-delay:.6s}
.sp-card:nth-child(4){animation-delay:.7s}
@keyframes cardIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

.sp-card-icon{
  width:44px;height:44px;min-width:44px;border-radius:12px;
  display:grid;place-items:center;font-size:22px;flex-shrink:0;
}
.sp-card-title{font-size:15px;font-weight:700;color:#fff;margin-bottom:4px;}
.sp-card-desc{font-size:13px;color:rgba(255,255,255,.5);font-weight:400;line-height:1.5;}

/* close button */
.sp-close-wrap{
  display:flex;flex-direction:column;align-items:center;gap:14px;
  animation:headIn .5s .8s both;
}
#mn-splash-close{
  display:inline-flex;align-items:center;gap:10px;
  padding:16px 44px;border-radius:14px;border:none;cursor:pointer;
  font-family:'Heebo',sans-serif;font-size:17px;font-weight:700;
  background:linear-gradient(135deg,#5b8dee,#7c5ce8);color:#fff;
  box-shadow:0 8px 32px rgba(91,141,238,.45);
  transition:opacity .2s,transform .15s,box-shadow .2s,filter .2s;
  letter-spacing:.01em;
  min-width:220px;justify-content:center;
}
#mn-splash-close:disabled{
  background:rgba(255,255,255,.07);
  border:1px solid rgba(255,255,255,.1);
  color:rgba(255,255,255,.3);
  cursor:not-allowed;box-shadow:none;
  filter:none;
}
#mn-splash-close:not(:disabled):hover{
  transform:translateY(-3px);
  box-shadow:0 14px 40px rgba(91,141,238,.6);
  filter:brightness(1.1);
}
#mn-splash-close:not(:disabled):active{transform:scale(.97)}

/* countdown bar */
#sp-bar-wrap{
  width:220px;height:3px;background:rgba(255,255,255,.1);border-radius:10px;overflow:hidden;
}
#sp-bar{
  height:100%;background:linear-gradient(90deg,#5b8dee,#a78bfa);border-radius:10px;
  transition:width 1s linear;
  width:100%;
}
#sp-timer-txt{
  font-size:12px;color:rgba(255,255,255,.35);font-weight:500;
}

/* confetti */
.sp-dot{
  position:absolute;border-radius:50%;pointer-events:none;
  animation:dotFall linear forwards;
}
@keyframes dotFall{
  0%{transform:translateY(-20px) rotate(0);opacity:1}
  100%{transform:translateY(110vh) rotate(600deg);opacity:0}
}
</style>

<!-- background orbs -->
<div id="mn-splash-bg">
  <div class="sp-orb" style="width:500px;height:500px;background:#1e3a8a;top:-100px;right:-100px;animation-duration:8s"></div>
  <div class="sp-orb" style="width:400px;height:400px;background:#4c1d95;bottom:-80px;left:-80px;animation-duration:11s;animation-delay:-3s"></div>
  <div class="sp-orb" style="width:300px;height:300px;background:#1e40af;top:40%;left:30%;animation-duration:9s;animation-delay:-5s"></div>
</div>

<div id="mn-splash-inner">

  <div class="sp-badge">
    <div class="sp-badge-dot"></div>
    עדכון חדש זמין
  </div>

  <div class="sp-headline">מוקדנט<br><span>קיבלה שדרוג</span></div>
  <div class="sp-sub">כל מה שאהבתם — עכשיו מהיר, חכם ויפה יותר</div>

  <div class="sp-grid">
    <div class="sp-card">
      <div class="sp-card-icon" style="background:rgba(91,141,238,.18)">🎨</div>
      <div>
        <div class="sp-card-title">העדפות תצוגה אישיות</div>
        <div class="sp-card-desc">פונט, רקע, וסגנון — כל אחד בוחר לעצמו</div>
      </div>
    </div>
    <div class="sp-card">
      <div class="sp-card-icon" style="background:rgba(16,185,129,.18)">🔍</div>
      <div>
        <div class="sp-card-title">חיפוש גלובאלי</div>
        <div class="sp-card-desc">חנויות, קריאות, אנשי קשר ומוצרים — מקום אחד</div>
      </div>
    </div>
    <div class="sp-card">
      <div class="sp-card-icon" style="background:rgba(245,158,11,.18)">✨</div>
      <div>
        <div class="sp-card-title">עיצוב חדש לחלוטין</div>
        <div class="sp-card-desc">ממשק מודרני, נקי ומהיר לשימוש</div>
      </div>
    </div>
    <div class="sp-card">
      <div class="sp-card-icon" style="background:rgba(139,92,246,.18)">⚡</div>
      <div>
        <div class="sp-card-title">ועוד בדרך</div>
        <div class="sp-card-desc">שיפורים נוספים מגיעים בקרוב</div>
      </div>
    </div>
  </div>

  <div class="sp-close-wrap">
    <button id="mn-splash-close" disabled onclick="mnSplashClose()">
      <span id="sp-btn-label">המשך בעוד <strong id="sp-num">5</strong></span>
    </button>
    <div id="sp-bar-wrap"><div id="sp-bar"></div></div>
    <div id="sp-timer-txt">הכפתור יתאפשר בעוד <span id="sp-txt-num">5</span> שניות</div>
  </div>

</div><!-- /inner -->

<script>
(function(){
  var KEY = 'mn_splash_v2';
  var el  = document.getElementById('mn-splash');

  if (localStorage.getItem(KEY)) { el.style.display = 'none'; return; }

  // confetti
  var colors = ['#5b8dee','#a78bfa','#f59e0b','#10b981','#ef4444','#fff','#c084fc'];
  for (var i = 0; i < 70; i++) {
    var d = document.createElement('div');
    d.className = 'sp-dot';
    var size = 5 + Math.random() * 7;
    d.style.cssText = [
      'left:'   + Math.random()*100 + '%',
      'top:0',
      'width:'  + size + 'px',
      'height:' + size + 'px',
      'background:' + colors[Math.floor(Math.random()*colors.length)],
      'animation-duration:' + (2.5 + Math.random()*3) + 's',
      'animation-delay:'    + (Math.random()*1.5) + 's',
      'border-radius:' + (Math.random()>.5 ? '50%' : '2px')
    ].join(';');
    el.appendChild(d);
  }

  var btn      = document.getElementById('mn-splash-close');
  var label    = document.getElementById('sp-btn-label');
  var numEl    = document.getElementById('sp-num');
  var txtNum   = document.getElementById('sp-txt-num');
  var bar      = document.getElementById('sp-bar');
  var timerTxt = document.getElementById('sp-timer-txt');
  var remain   = 5;

  // kick off bar shrink after paint
  requestAnimationFrame(function(){ bar.style.width = '0%'; });

  function tick() {
    remain--;
    if (numEl)  numEl.textContent  = remain;
    if (txtNum) txtNum.textContent = remain;
    if (remain <= 0) {
      btn.disabled = false;
      label.innerHTML = 'כניסה לאתר &nbsp;→';
      if (timerTxt) timerTxt.style.display = 'none';
      btn.style.animation = 'pulse .6s ease-in-out 2';
    } else {
      setTimeout(tick, 1000);
    }
  }
  setTimeout(tick, 1000);

  window.mnSplashClose = function() {
    localStorage.setItem(KEY, '1');
    el.style.transition = 'opacity .4s';
    el.style.opacity    = '0';
    setTimeout(function(){ el.style.display = 'none'; }, 420);
  };

  // click outside inner closes (only when button is active)
  el.addEventListener('click', function(e) {
    if (e.target === el && !btn.disabled) mnSplashClose();
  });
})();
</script>
</div>
