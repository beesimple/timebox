<?php
$defaults = [
    'title'         => 'Timebox',
    'font'          => 'Inter',
    'color_idle'    => '#1ac8a0',
    'color_warn'    => '#e8833a',
    'color_done'    => '#7c3aed',
    'text_color'    => '#ffffff',
    'default_sound' => 'gong',
    'show_presets'  => true,
    'preset_times'  => [5, 15, 25, 45, 60],
    'logo_path'     => '',
    'password'      => '',
];
$s = file_exists('settings.json')
    ? array_merge($defaults, json_decode(file_get_contents('settings.json'), true))
    : $defaults;
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($s['title']) ?></title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><circle cx='32' cy='32' r='30' fill='<?= urlencode($s['color_idle']) ?>'/><circle cx='32' cy='32' r='24' fill='none' stroke='white' stroke-width='3'/><line x1='32' y1='32' x2='32' y2='14' stroke='white' stroke-width='3' stroke-linecap='round'/><line x1='32' y1='32' x2='44' y2='38' stroke='white' stroke-width='3' stroke-linecap='round'/></svg>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@700&family=Bebas+Neue&family=Syne:wght@800&family=Inter:wght@500;600;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; overflow: hidden; }
    body {
      font-family: '<?= htmlspecialchars($s['font']) ?>', sans-serif;
      background: <?= htmlspecialchars($s['color_idle']) ?>;
      display: flex; flex-direction: column;
      height: 100vh;
      transition: background 0.6s ease;
    }

    /* HEADER */
    .tb-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 18px 28px; flex-shrink: 0;
    }
    .logo-wrap { display: flex; align-items: center; gap: 12px; }
    .logo-wrap img { height: 60px; object-fit: contain; }
    .logo-title { font-size: 22px; font-weight: 800; color: <?= htmlspecialchars($s['text_color']) ?>; letter-spacing: -0.5px; }
    .tb-admin { font-size: 20px; color: <?= $s['text_color'] === '#ffffff' ? 'rgba(255,255,255,0.3)' : 'rgba(0,0,0,0.3)' ?>; text-decoration: none; transition: color 0.15s; }
    .tb-admin:hover { color: <?= htmlspecialchars($s['text_color']) ?>; }

    /* TIMER AREA */
    .timer-area {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      gap: 28px; min-height: 0;
    }
    #display {
      font-size: clamp(160px, 26vw, 340px);
      font-weight: 800; color: <?= htmlspecialchars($s['text_color']) ?>; line-height: 1;
      font-variant-numeric: tabular-nums; letter-spacing: -10px;
      font-family: '<?= htmlspecialchars($s['font']) ?>', sans-serif;
    }
    #display.warning { animation: pulse 0.75s ease-in-out infinite alternate; }
    @keyframes pulse { from { opacity: 1; } to { opacity: 0.35; } }

    #startBtn {
      width: 96px; height: 96px; border-radius: 50%;
      background: <?= htmlspecialchars($s['text_color']) ?>; border: none; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 8px 32px rgba(0,0,0,0.15);
      transition: all 0.15s; flex-shrink: 0;
    }
    #startBtn:hover { transform: scale(1.07); box-shadow: 0 12px 40px rgba(0,0,0,0.2); }
    #startBtn:active { transform: scale(0.95); }
    #startBtn svg { width: 36px; height: 36px; }

    /* FOOTER PANEL */
    .footer-panel {
      margin: 0 16px 16px;
      background: rgba(0,0,0,0.12);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border-radius: 18px;
      padding: 16px 24px;
      display: flex; align-items: center; justify-content: space-between; gap: 12px;
      flex-shrink: 0;
    }
    .fp-group { display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; }
    .fp-label { font-size: 11px; font-weight: 600; color: <?= $s['text_color'] === '#ffffff' ? 'rgba(255,255,255,0.4)' : 'rgba(0,0,0,0.35)' ?>; text-transform: uppercase; letter-spacing: 0.08em; margin-right: 4px; white-space: nowrap; }
    .fp-btn {
      padding: 14px 18px; border-radius: 24px; border: none;
      background: <?= $s['text_color'] === '#ffffff' ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.1)' ?>; color: <?= htmlspecialchars($s['text_color']) ?>;
      font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.15s; white-space: nowrap;
    }
    .fp-btn:hover { background: <?= $s['text_color'] === '#ffffff' ? 'rgba(255,255,255,0.28)' : 'rgba(0,0,0,0.2)' ?>; }
    .fp-divider { width: 1px; height: 36px; background: <?= $s['text_color'] === '#ffffff' ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.15)' ?>; flex-shrink: 0; }
    .fp-icon-wrap { display: flex; flex-direction: column; align-items: center; gap: 3px; }
    .fp-icon {
      width: 52px; height: 52px; border-radius: 14px; border: none;
      background: <?= $s['text_color'] === '#ffffff' ? 'rgba(255,255,255,0.12)' : 'rgba(0,0,0,0.1)' ?>; color: <?= htmlspecialchars($s['text_color']) ?>;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all 0.15s;
    }
    .fp-icon:hover { background: <?= $s['text_color'] === '#ffffff' ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.2)' ?>; }
    .fp-icon svg { width: 22px; height: 22px; }
    .fp-sub { font-size: 11px; color: <?= $s['text_color'] === '#ffffff' ? 'rgba(255,255,255,0.4)' : 'rgba(0,0,0,0.35)' ?>; font-weight: 600; }

    /* PROGRESS */
    .progress-bar {
      position: fixed; bottom: 0; left: 0; height: 4px;
      background: rgba(0,0,0,0.2); width: 0%;
      transition: width 0.5s linear;
    }

    /* RESPONSIVE TABLET */
    @media (max-width: 1024px) {
      #display { font-size: clamp(120px, 20vw, 240px); letter-spacing: -6px; }
      .footer-panel { flex-wrap: wrap; justify-content: center; gap: 10px; }
    }

    /* RESPONSIVE MOBILE */
    @media (max-width: 600px) {
      .tb-header { padding: 14px 16px; }
      .logo-title { font-size: 18px; }
      .logo-wrap img { height: 40px; }
      #display { font-size: clamp(90px, 22vw, 160px); letter-spacing: -4px; }
      #startBtn { width: 76px; height: 76px; }
      .footer-panel { padding: 10px 12px; gap: 6px; flex-wrap: wrap; justify-content: center; }
      .fp-btn { padding: 6px 10px; font-size: 12px; }
      .fp-label { display: none; }
      .fp-divider { display: none; }
    }
  </style>
</head>
<body id="body">

  <header class="tb-header">
    <div class="logo-wrap">
      <?php if (!empty($s['logo_path'])): ?>
        <img src="<?= htmlspecialchars($s['logo_path']) ?>" alt="Logo">
      <?php endif; ?>
      <?php if (!empty($s['title'])): ?>
        <span class="logo-title"><?= htmlspecialchars($s['title']) ?></span>
      <?php endif; ?>
    </div>
    <a class="tb-admin" href="admin.php" title="Einstellungen">⚙</a>
  </header>

  <div class="timer-area">
    <div id="display">00:00</div>
    <button id="startBtn" onclick="toggleTimer()">
      <svg id="btnIcon" viewBox="0 0 24 24" fill="<?= htmlspecialchars($s['color_idle']) ?>">
        <polygon points="6,3 20,12 6,21"/>
      </svg>
    </button>
  </div>

  <div class="footer-panel">
    <?php if ($s['show_presets']): ?>
    <div class="fp-group">
      <span class="fp-label">Dauer</span>
      <?php foreach ($s['preset_times'] as $t): ?>
        <button class="fp-btn" onclick="setTime(<?= intval($t)*60 ?>)"><?= intval($t) ?>'</button>
      <?php endforeach; ?>
    </div>
    <div class="fp-divider"></div>
    <?php endif; ?>

    <div class="fp-group">
      <span class="fp-label">+Zeit</span>
      <button class="fp-btn" onclick="addTime(1800)">+30'</button>
      <button class="fp-btn" onclick="addTime(300)">+5'</button>
      <button class="fp-btn" onclick="addTime(60)">+1'</button>
      <button class="fp-btn" onclick="addTime(30)">+30''</button>
    </div>

    <div class="fp-divider"></div>

    <div class="fp-group" style="gap:10px;">
      <div class="fp-icon-wrap">
        <button class="fp-icon" onclick="addTime(-15)" title="−15s">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
          </svg>
        </button>
        <span class="fp-sub">−15s</span>
      </div>
      <div class="fp-icon-wrap">
        <button class="fp-icon" onclick="resetTimer()" title="Reset">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/>
          </svg>
        </button>
        <span class="fp-sub">Reset</span>
      </div>
      <div class="fp-icon-wrap">
        <button class="fp-icon" onclick="addTime(15)" title="+15s">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/>
          </svg>
        </button>
        <span class="fp-sub">+15s</span>
      </div>
    </div>
  </div>

  <div class="progress-bar" id="progressBar"></div>

<script>
const C_IDLE = '<?= htmlspecialchars($s['color_idle']) ?>';
const C_WARN = '<?= htmlspecialchars($s['color_warn']) ?>';
const C_DONE = '<?= htmlspecialchars($s['color_done']) ?>';

let remaining=0, initialTotal=0, running=false, iv=null;
const body=document.getElementById('body');
const disp=document.getElementById('display');
const bar=document.getElementById('progressBar');
const actx=window.AudioContext?new AudioContext():(window.webkitAudioContext?new webkitAudioContext():null);

function fmt(s){return String(Math.floor(Math.max(0,s)/60)).padStart(2,'0')+':'+String(Math.max(0,s)%60).padStart(2,'0');}

function setPlayIcon(playing){
  const col = body.style.background || C_IDLE;
  const icon = document.getElementById('btnIcon');
  icon.innerHTML = playing
    ? `<rect x="5" y="3" width="4" height="18" rx="1" fill="${col}"/><rect x="15" y="3" width="4" height="18" rx="1" fill="${col}"/>`
    : `<polygon points="6,3 20,12 6,21" fill="${col}"/>`;
}

function updateDisplay(){
  disp.textContent = fmt(remaining);
  bar.style.width = initialTotal>0 ? ((initialTotal-remaining)/initialTotal*100)+'%' : '0%';
  disp.className = '';
  if(running){
    if(remaining<=60){ disp.classList.add('warning'); body.style.background=C_WARN; }
    else { body.style.background=C_IDLE; }
    setPlayIcon(true);
  }
}

function setTime(s){ resetTimer(); remaining=s; initialTotal=s; updateDisplay(); }
function addTime(s){ remaining=Math.max(0,remaining+s); if(!initialTotal||remaining>initialTotal) initialTotal=remaining; updateDisplay(); }

function toggleTimer(){
  if(!remaining&&!running) return;
  if(actx&&actx.state==='suspended') actx.resume();
  running=!running;
  if(running){
    setPlayIcon(true);
    iv=setInterval(()=>{
      remaining--;
      updateDisplay();
      if(remaining<=0){
        remaining=0; running=false; clearInterval(iv);
        body.style.background=C_DONE;
        setPlayIcon(false);
        disp.className='';
        bar.style.width='100%';
        playSound('<?= htmlspecialchars($s['default_sound']) ?>');
      }
    },1000);
  } else {
    clearInterval(iv); setPlayIcon(false);
  }
}

function resetTimer(){
  clearInterval(iv); running=false; remaining=0; initialTotal=0;
  body.style.background=C_IDLE;
  setPlayIcon(false);
  disp.textContent='00:00'; disp.className='';
  bar.style.width='0%';
}

function playSound(type){
  if(!actx||type==='off') return;
  if(actx.state==='suspended') actx.resume();
  const t=actx.currentTime;
  if(type==='gong'){[[110,4],[220,3]].forEach(([f,d],i)=>{const o=actx.createOscillator(),g=actx.createGain();o.connect(g);g.connect(actx.destination);o.type='sine';o.frequency.setValueAtTime(f,t);o.frequency.exponentialRampToValueAtTime(f*.85,t+d);g.gain.setValueAtTime(.7/(i+1),t);g.gain.exponentialRampToValueAtTime(.001,t+d);o.start(t);o.stop(t+d);});}
  else if(type==='bell'){[440,880,1320].forEach((f,i)=>{const o=actx.createOscillator(),g=actx.createGain();o.connect(g);g.connect(actx.destination);o.type='sine';o.frequency.setValueAtTime(f,t);g.gain.setValueAtTime(.5/(i+1),t);g.gain.exponentialRampToValueAtTime(.001,t+2.5);o.start(t);o.stop(t+2.5);});}
  else if(type==='bowl'){[[315,5],[630,4]].forEach(([f,d],i)=>{const o=actx.createOscillator(),g=actx.createGain();o.connect(g);g.connect(actx.destination);o.type='sine';o.frequency.setValueAtTime(f,t);g.gain.setValueAtTime(.5/(i+1),t);g.gain.exponentialRampToValueAtTime(.001,t+d);o.start(t);o.stop(t+d);});}
}
</script>
</body>
</html>
