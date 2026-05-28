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
      font-family: 'Inter', sans-serif;
    }
    body {
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      min-height: 100vh;
      background: <?= htmlspecialchars($s['color_idle']) ?>;
      transition: background 0.6s ease;
    }
    .tb-top {
      position: fixed; top: 0; left: 0; right: 0;
      padding: 16px 22px; display: flex; align-items: center;
      justify-content: space-between; z-index: 10;
    }
    .pill-select { position: relative; display: inline-block; }
    .pill-select select {
      appearance: none; -webkit-appearance: none;
      padding: 9px 36px 9px 16px; border-radius: 30px;
      border: none; background: rgba(0,0,0,0.15); color: #fff;
      font-size: 14px; font-weight: 600; cursor: pointer; outline: none;
    }
    .pill-select select option { color: #333; background: #fff; }
    .pill-select::after {
      content: '▾'; position: absolute; right: 13px; top: 50%;
      transform: translateY(-50%); color: rgba(255,255,255,0.7);
      font-size: 11px; pointer-events: none;
    }
    .tb-admin {
      font-size: 18px; color: rgba(255,255,255,0.3);
      text-decoration: none; transition: color 0.15s;
    }
    .tb-admin:hover { color: rgba(255,255,255,0.9); }
    .logo-wrap { display: flex; align-items: center; gap: 10px; }
    .logo-wrap img { height: 28px; object-fit: contain; }
    .logo-title { font-size: 14px; font-weight: 700; color: rgba(255,255,255,0.5); letter-spacing: 0.08em; }

    main {
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      gap: 24px; padding: 100px 20px 60px; width: 100%;
    }
    .presets { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }
    .preset-btn {
      padding: 11px 22px; border-radius: 30px; border: none;
      background: rgba(0,0,0,0.15); color: #fff;
      font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.15s;
    }
    .preset-btn:hover { background: rgba(0,0,0,0.25); }

    #display {
      font-size: clamp(80px, 18vw, 160px);
      font-weight: 800; color: #fff; line-height: 1;
      text-align: center; font-variant-numeric: tabular-nums;
      transition: font-family 0.15s;
      font-family: '<?= htmlspecialchars($s['font']) ?>', sans-serif;
    }
    #display.warning { animation: pulse 0.8s ease-in-out infinite alternate; }
    @keyframes pulse { from { opacity: 1; } to { opacity: 0.45; } }

    .add-btns { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }
    .add-btn {
      padding: 12px 24px; border-radius: 30px; border: none;
      background: rgba(0,0,0,0.15); color: #fff;
      font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.15s;
    }
    .add-btn:hover { background: rgba(0,0,0,0.25); }

    .controls { display: flex; align-items: center; gap: 14px; }
    .skip-btn {
      display: flex; flex-direction: column; align-items: center;
      justify-content: center; gap: 4px; width: 60px; height: 60px;
      border-radius: 50%; background: rgba(0,0,0,0.15); border: none;
      color: #fff; cursor: pointer; transition: all 0.15s;
    }
    .skip-btn:hover { background: rgba(0,0,0,0.25); }
    .skip-btn svg { width: 22px; height: 22px; }
    .skip-btn span { font-size: 11px; font-weight: 700; }
    #resetBtn {
      width: 48px; height: 48px; border-radius: 50%;
      background: rgba(0,0,0,0.1); border: none;
      color: rgba(255,255,255,0.5); cursor: pointer; transition: all 0.15s;
      display: flex; align-items: center; justify-content: center;
    }
    #resetBtn:hover { color: #fff; background: rgba(0,0,0,0.2); }
    #resetBtn svg { width: 20px; height: 20px; }
    #startBtn {
      width: 88px; height: 88px; border-radius: 50%;
      background: #fff; border: none; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.15s; flex-shrink: 0;
    }
    #startBtn:hover { transform: scale(1.06); }
    #startBtn:active { transform: scale(0.95); }
    #startBtn svg { width: 32px; height: 32px; }

    .progress-bar {
      position: fixed; bottom: 0; left: 0; height: 4px;
      background: rgba(0,0,0,0.2); width: 0%;
      transition: width 0.5s linear;
    }
  </style>
</head>
<body id="body">

<div class="tb-top">
  <div class="logo-wrap">
    <?php if ($s['logo_path']): ?>
      <img src="<?= htmlspecialchars($s['logo_path']) ?>" alt="Logo">
    <?php endif; ?>
    <?php if ($s['title']): ?>
      <span class="logo-title"><?= htmlspecialchars($s['title']) ?></span>
    <?php endif; ?>
  </div>
  <div style="display:flex;gap:10px;align-items:center;">
    <div class="pill-select">
      <select id="soundSelect">
        <option value="gong"  <?= $s['default_sound']==='gong'  ? 'selected':'' ?>>Gong</option>
        <option value="bell"  <?= $s['default_sound']==='bell'  ? 'selected':'' ?>>Indian Bell</option>
        <option value="bowl"  <?= $s['default_sound']==='bowl'  ? 'selected':'' ?>>Singing Bowl</option>
        <option value="off"   <?= $s['default_sound']==='off'   ? 'selected':'' ?>>Kein Ton</option>
      </select>
    </div>
    <div class="pill-select">
      <select id="fontSelect" onchange="setFont(this.value)">
        <option value="Inter"      <?= $s['font']==='Inter'       ? 'selected':'' ?>>Modern</option>
        <option value="Bebas Neue" <?= $s['font']==='Bebas Neue'  ? 'selected':'' ?>>Block</option>
        <option value="Space Mono" <?= $s['font']==='Space Mono'  ? 'selected':'' ?>>Mono</option>
        <option value="Syne"       <?= $s['font']==='Syne'        ? 'selected':'' ?>>Syne</option>
      </select>
    </div>
  </div>
  <a class="tb-admin" href="admin.php" title="Einstellungen">⚙</a>
</div>

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
    <button class="skip-btn" onclick="addTime(-15)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
      <span>15s</span>
    </button>
    <button id="resetBtn" onclick="resetTimer()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
    </button>
    <button id="startBtn" onclick="toggleTimer()">
      <svg id="btnIcon" viewBox="0 0 24 24" fill="currentColor"><polygon points="6,3 20,12 6,21" style="fill:<?= htmlspecialchars($s['color_idle']) ?>"/></svg>
    </button>
    <button class="skip-btn" onclick="addTime(15)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
      <span>15s</span>
    </button>
  </div>
</main>

<div class="progress-bar" id="progressBar"></div>

<script>
const C_IDLE = '<?= htmlspecialchars($s['color_idle']) ?>';
const C_WARN = '<?= htmlspecialchars($s['color_warn']) ?>';
const C_DONE = '<?= htmlspecialchars($s['color_done']) ?>';

let remaining=0,initialTotal=0,running=false,iv=null;
const body=document.getElementById('body');
const disp=document.getElementById('display');
const bar=document.getElementById('progressBar');
const btn=document.getElementById('startBtn');
const actx=window.AudioContext?new AudioContext():(window.webkitAudioContext?new webkitAudioContext():null);

function fmt(s){return String(Math.floor(Math.max(0,s)/60)).padStart(2,'0')+':'+String(Math.max(0,s)%60).padStart(2,'0');}
function currentBg(){return body.style.background||C_IDLE;}

function setPlayIcon(playing){
  const el=document.getElementById('btnIcon');
  const bg=currentBg();
  if(playing){
    el.innerHTML='<rect x="5" y="3" width="4" height="18" rx="1" style="fill:'+bg+'"/><rect x="15" y="3" width="4" height="18" rx="1" style="fill:'+bg+'"/>';
  } else {
    el.innerHTML='<polygon points="6,3 20,12 6,21" style="fill:'+bg+'"/>';
  }
}

function updateDisplay(){
  disp.textContent=fmt(remaining);
  bar.style.width=initialTotal>0?((initialTotal-remaining)/initialTotal*100)+'%':'0%';
  disp.className='';
  if(running){
    if(remaining<=60){disp.classList.add('warning');body.style.background=C_WARN;setPlayIcon(true);}
    else{body.style.background=C_IDLE;setPlayIcon(true);}
  }
}

function setTime(s){resetTimer();remaining=s;initialTotal=s;updateDisplay();}
function addTime(s){remaining=Math.max(0,remaining+s);if(!initialTotal||remaining>initialTotal)initialTotal=remaining;updateDisplay();}

function toggleTimer(){
  if(!remaining&&!running)return;
  if(actx&&actx.state==='suspended')actx.resume();
  running=!running;
  if(running){
    setPlayIcon(true);
    iv=setInterval(()=>{
      remaining--;updateDisplay();
      if(remaining<=0){
        remaining=0;running=false;clearInterval(iv);
        body.style.background=C_DONE;
        setPlayIcon(false);
        disp.className='';bar.style.width='100%';
        playSound(document.getElementById('soundSelect').value);
      }
    },1000);
  } else {
    clearInterval(iv);setPlayIcon(false);
  }
}

function resetTimer(){
  clearInterval(iv);running=false;remaining=0;initialTotal=0;
  body.style.background=C_IDLE;
  setPlayIcon(false);
  disp.textContent='00:00';disp.className='';bar.style.width='0%';
}

function setFont(f){disp.style.fontFamily="'"+f+"',sans-serif";}

function playSound(type){
  if(!actx||type==='off')return;
  if(actx.state==='suspended')actx.resume();
  const t=actx.currentTime;
  if(type==='gong'){[[110,4],[220,3]].forEach(([f,d],i)=>{const o=actx.createOscillator(),g=actx.createGain();o.connect(g);g.connect(actx.destination);o.type='sine';o.frequency.setValueAtTime(f,t);o.frequency.exponentialRampToValueAtTime(f*.85,t+d);g.gain.setValueAtTime(.7/(i+1),t);g.gain.exponentialRampToValueAtTime(.001,t+d);o.start(t);o.stop(t+d);});}
  else if(type==='bell'){[440,880,1320].forEach((f,i)=>{const o=actx.createOscillator(),g=actx.createGain();o.connect(g);g.connect(actx.destination);o.type='sine';o.frequency.setValueAtTime(f,t);g.gain.setValueAtTime(.5/(i+1),t);g.gain.exponentialRampToValueAtTime(.001,t+2.5);o.start(t);o.stop(t+2.5);});}
  else if(type==='bowl'){[[315,5],[630,4]].forEach(([f,d],i)=>{const o=actx.createOscillator(),g=actx.createGain();o.connect(g);g.connect(actx.destination);o.type='sine';o.frequency.setValueAtTime(f,t);g.gain.setValueAtTime(.5/(i+1),t);g.gain.exponentialRampToValueAtTime(.001,t+d);o.start(t);o.stop(t+d);});}
}
</script>
</body>
</html>
