<?php
$settings = file_exists('settings.json')
    ? array_merge(require_defaults(), json_decode(file_get_contents('settings.json'), true))
    : require_defaults();

function require_defaults() {
    return [
        'title'         => 'Timebox',
        'accent_color'  => '#185FA5',
        'warning_color' => '#BA7517',
        'done_color'    => '#A32D2D',
        'bg_color'      => '#ffffff',
        'text_color'    => '#1a1a1a',
        'logo_path'     => '',
        'default_sound' => 'gong',
        'show_presets'  => true,
        'preset_times'  => [5, 15, 25, 45, 60],
    ];
}
$s = $settings;
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($s['title']) ?></title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: <?= $s['bg_color'] ?>;
      color: <?= $s['text_color'] ?>;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    header {
      position: fixed;
      top: 0; left: 0; right: 0;
      padding: 14px 24px;
      display: flex;
      align-items: center;
      gap: 12px;
      border-bottom: 1px solid rgba(0,0,0,0.07);
      background: <?= $s['bg_color'] ?>;
      z-index: 10;
    }
    header img { height: 32px; object-fit: contain; }
    header .site-title { font-size: 16px; font-weight: 500; }
    .admin-link { margin-left: auto; font-size: 13px; color: rgba(0,0,0,0.3); text-decoration: none; }
    .admin-link:hover { color: <?= $s['accent_color'] ?>; }
    main {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 28px;
      padding: 100px 24px 40px;
      width: 100%;
      max-width: 520px;
    }
    <?php if ($s['show_presets']): ?>
    .presets { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }
    .preset-btn {
      padding: 8px 16px;
      border-radius: 8px;
      border: 1px solid rgba(0,0,0,0.12);
      background: transparent;
      color: <?= $s['text_color'] ?>;
      font-size: 14px;
      cursor: pointer;
      transition: background 0.15s, border-color 0.15s;
    }
    .preset-btn:hover { background: rgba(0,0,0,0.05); border-color: <?= $s['accent_color'] ?>; }
    <?php endif; ?>
    #display {
      font-size: 88px;
      font-weight: 300;
      letter-spacing: 6px;
      font-variant-numeric: tabular-nums;
      line-height: 1;
      transition: color 0.3s;
      color: <?= $s['text_color'] ?>;
    }
    #display.running { color: <?= $s['accent_color'] ?>; }
    #display.warning { color: <?= $s['warning_color'] ?>; }
    #display.done    { color: <?= $s['done_color'] ?>; }
    .progress-track {
      width: 100%; max-width: 320px; height: 3px;
      background: rgba(0,0,0,0.08); border-radius: 2px; overflow: hidden;
    }
    .progress-fill {
      height: 100%; background: <?= $s['accent_color'] ?>;
      border-radius: 2px; width: 0%; transition: width 0.5s linear;
    }
    .add-btns { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }
    .add-btn {
      padding: 10px 20px; border-radius: 8px;
      border: 1px solid rgba(0,0,0,0.12); background: transparent;
      color: <?= $s['text_color'] ?>; font-size: 14px; cursor: pointer;
      transition: background 0.15s;
    }
    .add-btn:hover { background: rgba(0,0,0,0.05); }
    .controls { display: flex; gap: 10px; align-items: center; }
    #startBtn {
      padding: 13px 40px; border-radius: 10px; border: none;
      background: <?= $s['accent_color'] ?>; color: #fff;
      font-size: 16px; font-weight: 500; cursor: pointer;
      transition: opacity 0.15s, transform 0.1s; min-width: 130px;
    }
    #startBtn:hover { opacity: 0.9; }
    #startBtn:active { transform: scale(0.98); }
    #resetBtn {
      padding: 13px 16px; border-radius: 10px;
      border: 1px solid rgba(0,0,0,0.12); background: transparent;
      color: <?= $s['text_color'] ?>; font-size: 18px; cursor: pointer;
      transition: background 0.15s;
    }
    #resetBtn:hover { background: rgba(0,0,0,0.05); }
    .sound-row { display: flex; align-items: center; gap: 10px; font-size: 13px; color: rgba(0,0,0,0.4); }
    select {
      padding: 6px 10px; border-radius: 7px;
      border: 1px solid rgba(0,0,0,0.12); background: transparent;
      color: <?= $s['text_color'] ?>; font-size: 13px; cursor: pointer;
    }
  </style>
</head>
<body>

<header>
  <?php if ($s['logo_path']): ?>
    <img src="<?= htmlspecialchars($s['logo_path']) ?>" alt="Logo">
  <?php endif; ?>
  <span class="site-title"><?= htmlspecialchars($s['title']) ?></span>
  <a class="admin-link" href="admin.php">⚙</a>
</header>

<main>
  <?php if ($s['show_presets']): ?>
  <div class="presets">
    <?php foreach ($s['preset_times'] as $t): ?>
      <button class="preset-btn" onclick="setTime(<?= $t * 60 ?>)"><?= $t ?> min</button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div id="display">00:00</div>

  <div class="progress-track">
    <div class="progress-fill" id="progressFill"></div>
  </div>

  <div class="add-btns">
    <button class="add-btn" onclick="addTime(1800)">+ 30 min</button>
    <button class="add-btn" onclick="addTime(300)">+ 5 min</button>
    <button class="add-btn" onclick="addTime(60)">+ 1 min</button>
  </div>

  <div class="controls">
    <button id="startBtn" onclick="toggleTimer()">▶ Start</button>
    <button id="resetBtn" onclick="resetTimer()" title="Zurücksetzen">↺</button>
  </div>

  <div class="sound-row">
    <span>🔔</span>
    <select id="soundSelect">
      <option value="gong"  <?= $s['default_sound']==='gong'  ? 'selected' : '' ?>>Gong</option>
      <option value="bell"  <?= $s['default_sound']==='bell'  ? 'selected' : '' ?>>Indian Bell</option>
      <option value="bowl"  <?= $s['default_sound']==='bowl'  ? 'selected' : '' ?>>Singing Bowl</option>
      <option value="off"   <?= $s['default_sound']==='off'   ? 'selected' : '' ?>>Kein Ton</option>
    </select>
  </div>
</main>

<script>
let remaining = 0, initialTotal = 0, running = false, iv = null;
const disp = document.getElementById('display');
const fill = document.getElementById('progressFill');
const btn  = document.getElementById('startBtn');
const ctx  = window.AudioContext ? new AudioContext() : (window.webkitAudioContext ? new webkitAudioContext() : null);

function fmt(s){ return String(Math.floor(s/60)).padStart(2,'0')+':'+String(s%60).padStart(2,'0'); }

function updateDisplay(){
  disp.textContent = fmt(remaining);
  fill.style.width = initialTotal > 0 ? ((initialTotal - remaining) / initialTotal * 100) + '%' : '0%';
  disp.className = '';
  if (running) disp.classList.add(remaining <= 60 ? 'warning' : 'running');
}

function setTime(s)  { resetTimer(); remaining = s; initialTotal = s; updateDisplay(); }
function addTime(s)  { remaining += s; if (!initialTotal) initialTotal = remaining; updateDisplay(); }

function toggleTimer(){
  if (!remaining && !running) return;
  if (ctx && ctx.state === 'suspended') ctx.resume();
  running = !running;
  if (running){
    btn.textContent = '⏸ Pause';
    iv = setInterval(() => {
      remaining--;
      updateDisplay();
      if (remaining <= 0){
        remaining = 0; running = false; clearInterval(iv);
        btn.textContent = '▶ Start';
        disp.className = 'done';
        fill.style.width = '100%';
        playSound(document.getElementById('soundSelect').value);
      }
    }, 1000);
  } else {
    clearInterval(iv);
    btn.textContent = '▶ Start';
  }
}

function resetTimer(){
  clearInterval(iv); running = false; remaining = 0; initialTotal = 0;
  btn.textContent = '▶ Start';
  disp.textContent = '00:00'; disp.className = '';
  fill.style.width = '0%';
}

function playSound(type){
  if (!ctx || type === 'off') return;
  if (ctx.state === 'suspended') ctx.resume();
  const t = ctx.currentTime;
  if (type === 'gong'){
    [[110,4],[220,3]].forEach(([f,d],i) => {
      const o=ctx.createOscillator(), g=ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type='sine'; o.frequency.setValueAtTime(f,t); o.frequency.exponentialRampToValueAtTime(f*0.85,t+d);
      g.gain.setValueAtTime(0.7/(i+1),t); g.gain.exponentialRampToValueAtTime(0.001,t+d);
      o.start(t); o.stop(t+d);
    });
  } else if (type === 'bell'){
    [440,880,1320].forEach((f,i) => {
      const o=ctx.createOscillator(), g=ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type='sine'; o.frequency.setValueAtTime(f,t);
      g.gain.setValueAtTime(0.5/(i+1),t); g.gain.exponentialRampToValueAtTime(0.001,t+2.5);
      o.start(t); o.stop(t+2.5);
    });
  } else if (type === 'bowl'){
    [[315,5],[630,4]].forEach(([f,d],i) => {
      const o=ctx.createOscillator(), g=ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type='sine'; o.frequency.setValueAtTime(f,t);
      g.gain.setValueAtTime(0.5/(i+1),t); g.gain.exponentialRampToValueAtTime(0.001,t+d);
      o.start(t); o.stop(t+d);
    });
  }
}
</script>
</body>
</html>
