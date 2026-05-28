<?php
session_start();

function load_settings() {
    $defaults = [
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
        'password'      => '',
    ];
    if (file_exists('settings.json')) {
        $saved = json_decode(file_get_contents('settings.json'), true);
        return array_merge($defaults, $saved);
    }
    return $defaults;
}

$s = load_settings();
$error = '';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Passwortschutz prüfen
    if (!empty($s['password'])) {
        $entered = $_POST['admin_password'] ?? '';
        if ($entered !== $s['password']) {
            $error = 'Falsches Passwort.';
            goto render;
        }
    }

    // Logo hochladen
    $logo_path = $s['logo_path'];
    if (!empty($_FILES['logo']['name'])) {
        $allowed = ['jpg','jpeg','png','gif','svg','webp'];
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = 'uploads/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['logo']['name']));
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $filename)) {
                $logo_path = $filename;
            }
        }
    }
    if (!empty($_POST['remove_logo'])) $logo_path = '';

    // Preset-Zeiten parsen
    $raw = $_POST['preset_times'] ?? '5,15,25,45,60';
    $preset_times = array_filter(array_map('intval', explode(',', $raw)));
    if (empty($preset_times)) $preset_times = [5, 15, 25, 45, 60];

    $new = [
        'title'         => trim($_POST['title'] ?? 'Timebox'),
        'accent_color'  => $_POST['accent_color']  ?? '#185FA5',
        'warning_color' => $_POST['warning_color'] ?? '#BA7517',
        'done_color'    => $_POST['done_color']    ?? '#A32D2D',
        'bg_color'      => $_POST['bg_color']      ?? '#ffffff',
        'text_color'    => $_POST['text_color']    ?? '#1a1a1a',
        'logo_path'     => $logo_path,
        'default_sound' => $_POST['default_sound'] ?? 'gong',
        'show_presets'  => isset($_POST['show_presets']),
        'preset_times'  => array_values($preset_times),
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
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f5f5f3; color: #1a1a1a; min-height: 100vh; padding: 40px 20px; }
    .container { max-width: 560px; margin: 0 auto; }
    header { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; }
    header a { font-size: 13px; color: #888; text-decoration: none; margin-left: auto; }
    header a:hover { color: #333; }
    h1 { font-size: 20px; font-weight: 500; }
    .card { background: #fff; border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); padding: 24px; margin-bottom: 16px; }
    .card h2 { font-size: 12px; font-weight: 500; color: #888; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 18px; }
    .field { margin-bottom: 16px; }
    .field:last-child { margin-bottom: 0; }
    label { display: block; font-size: 13px; color: #555; margin-bottom: 6px; }
    input[type=text], input[type=password], select {
      width: 100%; padding: 9px 12px; border-radius: 8px;
      border: 1px solid rgba(0,0,0,0.14); font-size: 14px; color: #1a1a1a;
      background: #fff; outline: none; transition: border-color 0.15s;
    }
    input[type=text]:focus, input[type=password]:focus, select:focus { border-color: #555; }
    .color-row { display: flex; align-items: center; gap: 10px; }
    .color-row input[type=color] {
      width: 40px; height: 36px; border-radius: 6px;
      border: 1px solid rgba(0,0,0,0.14); padding: 2px;
      cursor: pointer; background: #fff; flex-shrink: 0;
    }
    .color-row input[type=text] { flex: 1; }
    .checkbox-row { display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer; }
    .checkbox-row input[type=checkbox] { width: 16px; height: 16px; cursor: pointer; }
    .logo-preview { display: flex; align-items: center; gap: 12px; margin-top: 10px; }
    .logo-preview img { height: 40px; border-radius: 4px; border: 1px solid rgba(0,0,0,0.1); }
    .remove-logo { font-size: 12px; color: #c0392b; cursor: pointer; background: none; border: none; padding: 0; }
    .alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; }
    .alert-success { background: #e8f5e9; color: #2e7d32; }
    .alert-error   { background: #fdecea; color: #c62828; }
    .submit-row { display: flex; gap: 12px; align-items: center; margin-top: 8px; }
    button[type=submit] {
      padding: 11px 28px; border-radius: 9px; border: none;
      background: #1a1a1a; color: #fff; font-size: 15px; font-weight: 500;
      cursor: pointer; transition: opacity 0.15s;
    }
    button[type=submit]:hover { opacity: 0.82; }
    .preview-link { font-size: 13px; color: #888; text-decoration: none; }
    .preview-link:hover { color: #333; }
    .hint { font-size: 11px; color: #aaa; margin-top: 4px; }
    input[type=file] { font-size: 13px; color: #555; }
  </style>
</head>
<body>
<div class="container">

  <header>
    <h1>⚙ Einstellungen</h1>
    <a href="index.php">← Zum Timer</a>
  </header>

  <?php if ($saved): ?>
    <div class="alert alert-success">✓ Einstellungen gespeichert.</div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">

    <?php if (!empty($s['password'])): ?>
    <div class="card">
      <h2>Passwort</h2>
      <div class="field">
        <label>Admin-Passwort</label>
        <input type="password" name="admin_password" placeholder="Passwort eingeben" autocomplete="current-password">
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <h2>Branding</h2>
      <div class="field">
        <label>Titel / App-Name</label>
        <input type="text" name="title" value="<?= htmlspecialchars($s['title']) ?>">
      </div>
      <div class="field">
        <label>Logo</label>
        <input type="file" name="logo" accept=".png,.jpg,.jpeg,.gif,.svg,.webp">
        <?php if (!empty($s['logo_path'])): ?>
        <div class="logo-preview">
          <img src="<?= htmlspecialchars($s['logo_path']) ?>" alt="Logo">
          <button type="button" class="remove-logo" onclick="document.getElementById('removeLogo').value='1'; this.closest('.logo-preview').remove();">✕ entfernen</button>
        </div>
        <?php endif; ?>
        <input type="hidden" name="remove_logo" id="removeLogo" value="">
      </div>
    </div>

    <div class="card">
      <h2>Farben</h2>
      <?php
      $colors = [
          ['bg_color',      'Hintergrundfarbe'],
          ['text_color',    'Textfarbe'],
          ['accent_color',  'Akzentfarbe (Timer läuft)'],
          ['warning_color', 'Warnfarbe (letzte Minute)'],
          ['done_color',    'Fertig-Farbe (Zeit abgelaufen)'],
      ];
      foreach ($colors as [$key, $label]):
      ?>
      <div class="field">
        <label><?= $label ?></label>
        <div class="color-row">
          <input type="color" value="<?= $s[$key] ?>" oninput="document.getElementById('<?= $key ?>').value=this.value">
          <input type="text" name="<?= $key ?>" id="<?= $key ?>" value="<?= $s[$key] ?>">
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <h2>Timer-Optionen</h2>
      <div class="field">
        <label class="checkbox-row">
          <input type="checkbox" name="show_presets" <?= $s['show_presets'] ? 'checked' : '' ?>>
          Schnell-Preset-Buttons anzeigen
        </label>
      </div>
      <div class="field">
        <label>Preset-Zeiten (Minuten, kommagetrennt)</label>
        <input type="text" name="preset_times" value="<?= implode(',', $s['preset_times']) ?>">
        <p class="hint">Beispiel: 5,15,25,45,60</p>
      </div>
      <div class="field">
        <label>Standard-Ton</label>
        <select name="default_sound">
          <?php foreach (['gong'=>'Gong','bell'=>'Indian Bell','bowl'=>'Singing Bowl','off'=>'Kein Ton'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $s['default_sound']===$v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="card">
      <h2>Sicherheit</h2>
      <div class="field">
        <label>Admin-Passwort (leer = kein Schutz)</label>
        <input type="password" name="password" value="<?= htmlspecialchars($s['password']) ?>" autocomplete="new-password" placeholder="Passwort setzen…">
        <p class="hint">Schützt das Einstellungsmenü vor unbefugtem Zugriff.</p>
      </div>
    </div>

    <div class="submit-row">
      <button type="submit">Speichern</button>
      <a class="preview-link" href="index.php" target="_blank">Timer öffnen ↗</a>
    </div>

  </form>
</div>
</body>
</html>
