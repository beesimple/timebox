<?php
$defaults = [
    'title'         => 'Timebox',
    'font'          => 'Inter',
    'color_idle'    => '#1ac8a0',
    'color_warn'    => '#e8833a',
    'color_done'    => '#7c3aed',
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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@700&family=Bebas+Neue&family=Syne:wght@800&family=Inter:wght@800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
      height: 100%; width: 100%;
      font-family: '<?= htmlspecialchars($s['font']) ?>', sans-serif;
      overflow: hidden;
    }
    body {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100vh;
      background: <?= htmlspecialchars($s['color_idle']) ?>;
      transition: background 0.6s ease;
    }

    /* HEADER */
    .tb-header {
      position: fixed; top: 0; left: 0; right: 0;
      padding: 0 28px;
      height: 64px;
      display: flex; align-items: center;
      z-index: 10;
    }
    .logo-wrap { display: flex; align-items: center; gap: 12px; }
    .logo-wrap img { height: 60px; object-fit: contain; }
    .logo-title {
      font-size: 40px; font-weight: 800;
      color: rgba(255,255,255,0.85);
      letter-spacing: -1px; line-height: 1;
    }
    .tb-admin {
      margin-left: auto;
      font-size: 20px; color: rgba(255,255,255,0.3);
      text-decoration: none; transition: color 0.15s;
    }
    .tb-admin:hover { color: rgba(255,255,255,0.85); }

    /* MAIN LAYOUT */
    main {
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      gap: clamp(12px, 2vh, 24px);
      width: 100%; padding: 80px 20px 20px;
    }

    /* PRESETS */
    .presets { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
    .preset-btn {
      padding: 12px 26px; border-radius: 30px; border: none;
      background: rgba(0,0,0,0.15); color: #fff;
      font-size: clamp(14px, 1.6vw, 18px); font-weight: 600;
      cursor: pointer; transition: all 0.15s;
    }
    .preset-btn:hover { background: rgba(0,0,0,0.25); }

    /* TIMER */
    #display {
      font-size: clamp(120px, 22vw, 260px);
      font-weight: 800; color: #fff; line-height: 0.9;
      text-align: center; font-variant-numeric: tabular-nums;
      font-family: '<?= htmlspecialchars($s['font']) ?>', sans-serif;
      letter-spacing: -4px;
      transition: color 0.3s;
    }
    #display.warning { animation: pulse 0.8s ease-in-out infinite alternate; }
    @keyframes pulse { from { opacity: 1; } to { opacity: 0.4; } }

    /* ADD BUTTONS */
    .add-btns { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
    .add-btn {
      padding: 13px 28px; border-radius: 30px; border: none;
      background: rgba(0,0,0,0.15); color: #fff;
      font-size: clamp(14px, 1.6vw, 18px); font-weight: 600;
      cursor: pointer; transition: all 0.15s;
    }
    .add-btn:hover { background: rgba(0,0,0,0.25); }

    /* CONTROLS */
    .controls {
      display: flex; flex-direction: column;
      align-items: center; gap: 14px;
    }
    #startBtn {
      width: clamp(80px, 9vw, 110px);
      height: clamp(80px, 9vw, 110px);
      border-radius: 50%; background: #fff; border: none;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: all 0.15s;
    }
    #startBtn:hover { transform: scale(1.06); }
    #startBtn:active { transform: scale(0.95); }
    #startBtn svg { width: 38%; height: 38%; }

    .sub-controls {
      display: flex; align-items: center; justify-content: center; gap: 20px;
    }
    .skip-btn {
      display: flex; flex-direction: column; align-items: center;
      justify-content: center; gap: 4px;
      width: clamp(52px, 5.5vw, 68px);
      height: clamp(52px, 5.5vw, 68px);
      border-radius: 50%; background: rgba(0,0,0,0.15); border: none;
      color: #fff; cursor: pointer; transition: all 0.15s;
    }
    .skip-btn:hover { background: rgba(0,0,0,0.25); }
    .skip-btn svg { width: clamp(18px, 2vw, 24px); height: clamp(18px, 2vw, 24px); }
    .skip-btn span { font-size: clamp(10px, 1vw, 13px); font-weight: 700; }

    #resetBtn {
      width: clamp(44px, 4.5vw, 58px);
      height: clamp(44px, 4.5vw, 58px);
      border-radius: 50%; background: rgba(0,0,0,0.1); border: none;
      color: rgba(255,255,255,0.5); cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.15s;
    }
    #resetBtn:hover { color: #fff; background: rgba(0,0,0,0.2); }
    #resetBtn svg { width: clamp(16px, 1.8vw, 22px); height: clamp(16px, 1.8vw, 22px); }

    /* PROGRESS */
    .progress-bar {
      position: fixed; bottom: 0; left: 0; height: 5px;
      background: rgba(0,0,0,0.2); width: 0%;
      transition: width 0.5s linear;
    }

    /* RESPONSIVE TABLET */
    @media (max-width: 1024px) {
      .logo-title { font-size: 32px; }
      #display { font-size: clamp(100px, 20vw, 200px); }
      .preset-btn, .add-btn { padding: 11px 20px; font-size: 15px; }
    }

    /* RESPONSIVE MOBILE */
    @media (max-width: 600px) {
      .tb-header { height: 56px; padding: 0 16px; }
      .logo-wrap img { height: 40px; }
      .logo-title { font-size: 26px; }
      main { padding: 70px 12px 16px; gap: 14px; }
      #display { font-size: clamp(80px, 22vw, 140px); letter-spacing: -2px; }
      .preset-btn, .add-btn { padding: 10px 16px; font-size: 13px; }
      .presets, .add-btns { gap: 7px; }
      #startBtn { width: 80px; height: 80px; }
      .skip-btn { width: 52px; height: 52px; }
      #resetBtn { width: 44px; height: 44px; }
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

<main>
  <?php if ($s['show_presets']): ?>
  <div class="presets">
    <?php foreach ($s['preset_times'] as $t): ?>
      <button class="preset-btn" onclick="setTime(<?= intval($t)*60 ?>)"><?= intval($t) ?> min</button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div id="display">00:00</div>

  <div class="add-btns">
    <button class="add-btn" onclick="addTime(1800)">+30 min</button>
    <button class="add-btn" onclick="addTime(300)">+5 min</button>
    <button class="add-btn" onclick="addTime(60)">+1 min</button>
  </div>

  <div class="controls">
    <button id="startBtn" onclick="toggleTimer()">
      <svg id="btnIcon" viewBox="0 0 24 24" fill="currentColor">
        <polygon points="6,3 20,12 6,21" style="fill:<?= htmlspecialchars($s['color_idle']) ?>"/>
      </svg>
    </button>
    <div class="sub-controls">
      <button class="skip-btn" onclick="addTime(-15)" title="-15s">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
          <path d="M3 3v5h5"/>
        </svg>
        <span>15s</span>
      </button>
      <button id="resetBtn" onclick="resetTimer()" title="Reset">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
          <path d="M21 3v5h-5"/>
        </svg>
      </button>
      <button class="skip-btn" onclick="addTime(15)" title="+15s">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
          <path d="M21 3v5h-5"/>
        </svg>
        <span>15s</span>
      </button>
    </div>
  </div>
</main>

<div class="progress-bar" id="progressBar"></div>

<script>
const C_IDLE = '<?= htmlspecialchars($s['color_idle']) ?>';
const C_WARN = '<?= htmlspecialchars($s['color_warn']) ?>';
const C_DONE = '<?= htmlspecialchars($s['color_done']) ?>';

let remaining=0, initialTotal=0, running=false, iv=null;
const body    = document.getElementById('body');
const disp    = document.getElementById('display');
const bar     = document.getElementById('progressBar');
const startBtn= document.getElementById('startBtn');
const actx    = window.AudioContext ? new AudioContext() : (window.webkitAudioContext ? new webkitAudioContext() : null);

function fmt(s){ return String(Math.floor(Math.max(0,s)/60)).padStart(2,'0')+':'+String(Math.max(0,s)%60).padStart(2,'0'); }

function currentColor(){
  if(running && remaining<=60) return C_WARN;
  if(!running && remaining===0 && initialTotal>0) return C_DONE;
  return C_IDLE;
}

function setPlayIcon(playing){
  const col = body.style.background || C_IDLE;
  const icon = document.getElementById('btnIcon');
  if(playing){
    icon.innerHTML = '<rect x="5" y="3" width="4" height="18" rx="1" style="fill:'+col+'"/><rect x="15" y="3" width="4" height="18" rx="1" style="fill:'+col+'"/>';
  } else {
    icon.innerHTML = '<polygon points="6,3 20,12 6,21" style="fill:'+col+'"/>';
  }
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
  if(!remaining && !running) return;
  if(actx && actx.state==='suspended') actx.resume();
  running = !running;
  if(running){
    setPlayIcon(true);
    iv = setInterval(()=>{
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
    clearInterval(iv);
    setPlayIcon(false);
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
