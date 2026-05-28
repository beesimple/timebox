<?php
session_start();

function defaults() {
    return [
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
}

function load() {
    return file_exists('settings.json')
        ? array_merge(defaults(), json_decode(file_get_contents('settings.json'), true))
        : defaults();
}

$s = load();
$error = '';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($s['password']) && ($_POST['admin_password'] ?? '') !== $s['password']) {
        $error = 'Falsches Passwort.';
        goto render;
    }

    $logo_path = $s['logo_path'];
    if (!empty($_FILES['logo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','svg','webp'])) {
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            $fn = 'uploads/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['logo']['name']));
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $fn)) $logo_path = $fn;
        }
    }
    if (!empty($_POST['remove_logo'])) $logo_path = '';

    $raw = $_POST['preset_times'] ?? '5,15,25,45,60';
    $pts = array_values(array_filter(array_map('intval', explode(',', $raw))));
    if (empty($pts)) $pts = [5,15,25,45,60];

    $new = [
        'title'         => trim($_POST['title'] ?? 'Timebox'),
        'font'          => $_POST['font'] ?? 'Inter',
        'color_idle'    => $_POST['color_idle'] ?? '#1ac8a0',
        'color_warn'    => $_POST['color_warn'] ?? '#e8833a',
        'color_done'    => $_POST['color_done'] ?? '#7c3aed',
        'default_sound' => $_POST['default_sound'] ?? 'gong',
        'show_presets'  => isset($_POST['show_presets']),
        'preset_times'  => $pts,
        'logo_path'     => $logo_path,
        'password'      => $_POST['password'] ?? '',
    ];

    file_put_contents('settings.json', json_encode($new, JSON_PRETTY_PRINT));
    $s = $new;
    $saved = true;
}

render:
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Einstellungen – <?= htmlspecialchars($s['title']) ?></title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f0efeb; color: #1a1a1a; min-height: 100vh; padding: 40px 20px; }
    .wrap { max-width: 600px; margin: 0 auto; }
    header { display: flex; align-items: center; margin-bottom: 32px; }
    header h1 { font-size: 20px; font-weight: 600; }
    header a { margin-left: auto; font-size: 13px; color: #888; text-decoration: none; }
    header a:hover { color: #333; }
    .card { background: #fff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.07); padding: 24px; margin-bottom: 14px; }
    .card-title { font-size: 11px; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 18px; }
    .field { margin-bottom: 16px; }
    .field:last-child { margin-bottom: 0; }
    label { display: block; font-size: 13px; color: #555; margin-bottom: 6px; }
    input[type=text], input[type=password], select {
      width: 100%; padding: 10px 12px; border-radius: 9px;
      border: 1px solid rgba(0,0,0,0.13); font-size: 14px;
      color: #1a1a1a; background: #fff; outline: none; transition: border-color 0.15s;
    }
    input:focus, select:focus { border-color: #888; }
    .three-colors { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
    .color-block label { font-size: 12px; color: #777; margin-bottom: 6px; }
    .color-swatch { width: 100%; height: 48px; border-radius: 9px; border: 1px solid rgba(0,0,0,0.1); cursor: pointer; margin-bottom: 6px; }
    .color-hex { width: 100%; padding: 7px 10px; border-radius: 7px; border: 1px solid rgba(0,0,0,0.13); font-size: 13px; text-align: center; }
    .check-row { display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer; }
    .check-row input { width: 16px; height: 16px; cursor: pointer; }
    .hint { font-size: 11px; color: #bbb; margin-top: 4px; }
    .logo-preview { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
    .logo-preview img { height: 40px; border-radius: 4px; border: 1px solid rgba(0,0,0,0.09); }
    .rm-logo { font-size: 12px; color: #c0392b; background: none; border: none; cursor: pointer; }
    .alert { padding: 12px 16px; border-radius: 9px; font-size: 14px; margin-bottom: 18px; }
    .ok { background: #e8f5e9; color: #2e7d32; }
    .err { background: #fdecea; color: #c62828; }
    .actions { display: flex; align-items: center; gap: 14px; margin-top: 6px; }
    .btn-save { padding: 12px 30px; border-radius: 10px; border: none; background: #1a1a1a; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; transition: opacity 0.15s; }
    .btn-save:hover { opacity: 0.8; }
    .btn-preview { font-size: 13px; color: #999; text-decoration: none; }
    .btn-preview:hover { color: #333; }
    input[type=file] { font-size: 13px; color: #555; }
  </style>
</head>
<body>
<div class="wrap">
  <header>
    <h1>⚙ Einstellungen</h1>
    <a href="index.php">← Zum Timer</a>
  </header>

  <?php if ($saved): ?><div class="alert ok">✓ Gespeichert.</div><?php endif; ?>
  <?php if ($error): ?><div class="alert err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data">

    <?php if (!empty($s['password'])): ?>
    <div class="card">
      <div class="card-title">Passwort</div>
      <div class="field">
        <label>Admin-Passwort</label>
        <input type="password" name="admin_password" placeholder="Passwort eingeben">
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-title">Branding</div>
      <div class="field">
        <label>App-Titel</label>
        <input type="text" name="title" value="<?= htmlspecialchars($s['title']) ?>">
      </div>
      <div class="field">
        <label>Logo</label>
        <input type="file" name="logo" accept=".png,.jpg,.jpeg,.gif,.svg,.webp">
        <?php if (!empty($s['logo_path'])): ?>
        <div class="logo-preview">
          <img src="<?= htmlspecialchars($s['logo_path']) ?>" alt="Logo">
          <button type="button" class="rm-logo" onclick="document.getElementById('rmLogo').value='1';this.closest('.logo-preview').remove();">✕ entfernen</button>
        </div>
        <?php endif; ?>
        <input type="hidden" name="remove_logo" id="rmLogo" value="">
      </div>
    </div>

    <div class="card">
      <div class="card-title">Schrift & Ton</div>
      <div class="field">
        <label>Schriftart</label>
        <select name="font">
          <?php foreach (['Inter'=>'Modern (Inter)','Bebas Neue'=>'Block (Bebas Neue)','Space Mono'=>'Mono (Space Mono)','Syne'=>'Syne'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $s['font']===$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Ton beim Ablauf</label>
        <select name="default_sound">
          <?php foreach (['gong'=>'Gong','bell'=>'Indian Bell','bowl'=>'Singing Bowl','off'=>'Kein Ton'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $s['default_sound']===$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Farben</div>
      <div class="three-colors">
        <div class="color-block">
          <label>Grundfarbe</label>
          <input type="color" class="color-swatch" value="<?= $s['color_idle'] ?>" oninput="document.getElementById('ci').value=this.value">
          <input type="text" class="color-hex" name="color_idle" id="ci" value="<?= $s['color_idle'] ?>">
        </div>
        <div class="color-block">
          <label>Letzte Minute</label>
          <input type="color" class="color-swatch" value="<?= $s['color_warn'] ?>" oninput="document.getElementById('cw').value=this.value">
          <input type="text" class="color-hex" name="color_warn" id="cw" value="<?= $s['color_warn'] ?>">
        </div>
        <div class="color-block">
          <label>Zeit abgelaufen</label>
          <input type="color" class="color-swatch" value="<?= $s['color_done'] ?>" oninput="document.getElementById('cd').value=this.value">
          <input type="text" class="color-hex" name="color_done" id="cd" value="<?= $s['color_done'] ?>">
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Timer-Optionen</div>
      <div class="field">
        <label class="check-row">
          <input type="checkbox" name="show_presets" <?= $s['show_presets']?'checked':'' ?>>
          Preset-Buttons anzeigen
        </label>
      </div>
      <div class="field">
        <label>Preset-Zeiten (Minuten, kommagetrennt)</label>
        <input type="text" name="preset_times" value="<?= implode(',', $s['preset_times']) ?>">
        <p class="hint">z.B. 5,15,25,45,60</p>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Sicherheit</div>
      <div class="field">
        <label>Admin-Passwort (leer = kein Schutz)</label>
        <input type="password" name="password" value="<?= htmlspecialchars($s['password']) ?>" placeholder="Passwort setzen…">
        <p class="hint">Schützt diese Einstellungsseite.</p>
      </div>
    </div>

    <div class="actions">
      <button type="submit" class="btn-save">Speichern</button>
      <a href="index.php" target="_blank" class="btn-preview">Timer öffnen ↗</a>
    </div>
  </form>
</div>
</body>
</html>
