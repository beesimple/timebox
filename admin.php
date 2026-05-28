<?php
session_start();

function defaults() {
    return [
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
        'active_id'     => '',
    ];
}

function load_settings() {
    return file_exists('settings.json')
        ? array_merge(defaults(), json_decode(file_get_contents('settings.json'), true))
        : defaults();
}

function load_profiles() {
    return file_exists('profiles.json')
        ? json_decode(file_get_contents('profiles.json'), true)
        : [];
}

function save_profiles($p) {
    file_put_contents('profiles.json', json_encode($p, JSON_PRETTY_PRINT));
}

function save_settings($d) {
    file_put_contents('settings.json', json_encode($d, JSON_PRETTY_PRINT));
}

function upload_logo($key) {
    if (empty($_FILES[$key]['name']) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) return false;
    $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','svg','webp'])) return false;
    if (!is_dir('uploads')) mkdir('uploads', 0755, true);
    $fn = 'uploads/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES[$key]['name']));
    return move_uploaded_file($_FILES[$key]['tmp_name'], $fn) ? $fn : false;
}

function profile_from_post() {
    $raw = $_POST['preset_times'] ?? '5,15,25,45,60';
    $pts = array_values(array_filter(array_map('intval', explode(',', $raw))));
    if (empty($pts)) $pts = [5,15,25,45,60];
    return [
        'name'          => trim($_POST['name'] ?? 'Profil'),
        'title'         => trim($_POST['title'] ?? 'Timebox'),
        'font'          => $_POST['font'] ?? 'Inter',
        'color_idle'    => $_POST['color_idle'] ?? '#1ac8a0',
        'color_warn'    => $_POST['color_warn'] ?? '#e8833a',
        'color_done'    => $_POST['color_done'] ?? '#7c3aed',
        'text_color'    => $_POST['text_color'] ?? '#ffffff',
        'default_sound' => $_POST['default_sound'] ?? 'gong',
        'show_presets'  => isset($_POST['show_presets']),
        'preset_times'  => $pts,
    ];
}

$s        = load_settings();
$profiles = load_profiles();
$msg      = '';
$err      = '';
$action   = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($s['password']) && ($_POST['admin_password'] ?? '') !== $s['password']) {
        $err = 'Falsches Passwort.';
        goto render;
    }

    // Neues leeres Profil
    if ($action === 'new_profile') {
        $id = 'p_' . time() . '_' . rand(100,999);
        $profiles[$id] = array_merge(defaults(), ['name' => 'Neues Profil']);
        unset($profiles[$id]['password'], $profiles[$id]['active_id']);
        save_profiles($profiles);
        header('Location: admin.php?edit=' . $id);
        exit;
    }

    // Profil speichern
    if ($action === 'save_profile') {
        $id = $_POST['profile_id'] ?? '';
        if (!isset($profiles[$id])) { $err = 'Profil nicht gefunden.'; goto render; }
        $data = profile_from_post();
        $logo = upload_logo('logo');
        if ($logo !== false) {
            $data['logo_path'] = $logo;
        } else {
            $data['logo_path'] = empty($_POST['remove_logo'])
                ? ($profiles[$id]['logo_path'] ?? '')
                : '';
        }
        $profiles[$id] = $data;
        save_profiles($profiles);
        // Falls dieses Profil aktiv ist, settings auch updaten
        if ($s['active_id'] === $id) {
            $new = array_merge($data, ['password' => $s['password'], 'active_id' => $id]);
            save_settings($new);
        }
        header('Location: admin.php?saved=1');
        exit;
    }

    // Profil aktivieren
    if ($action === 'activate') {
        $id = $_POST['profile_id'] ?? '';
        if (isset($profiles[$id])) {
            $new = array_merge(defaults(), $profiles[$id], [
                'password'  => $s['password'],
                'active_id' => $id,
            ]);
            save_settings($new);
        }
        header('Location: admin.php?activated=1');
        exit;
    }

    // Profil löschen
    if ($action === 'delete_profile') {
        $id = $_POST['profile_id'] ?? '';
        if (isset($profiles[$id])) {
            unset($profiles[$id]);
            save_profiles($profiles);
            if ($s['active_id'] === $id) {
                $s['active_id'] = '';
                save_settings($s);
            }
        }
        header('Location: admin.php');
        exit;
    }

    // Passwort ändern
    if ($action === 'save_password') {
        $s['password'] = $_POST['new_password'] ?? '';
        save_settings($s);
        header('Location: admin.php?saved=1');
        exit;
    }
}

render:
$s        = load_settings();
$profiles = load_profiles();
$edit_id  = $_GET['edit'] ?? null;
$edit_p   = ($edit_id && isset($profiles[$edit_id])) ? $profiles[$edit_id] : null;
if ($_GET['saved'] ?? false)     $msg = 'Gespeichert.';
if ($_GET['activated'] ?? false) $msg = 'Profil aktiviert – Timer läuft jetzt damit.';
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin – <?= htmlspecialchars($s['title']) ?></title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f0efeb; color: #1a1a1a; min-height: 100vh; padding: 40px 20px; }
    .wrap { max-width: 660px; margin: 0 auto; }
    header { display: flex; align-items: center; margin-bottom: 28px; }
    header h1 { font-size: 20px; font-weight: 600; }
    header a { margin-left: auto; font-size: 13px; color: #888; text-decoration: none; }
    header a:hover { color: #333; }
    .alert-ok  { background: #e8f5e9; color: #2e7d32; padding: 12px 16px; border-radius: 9px; font-size: 14px; margin-bottom: 20px; }
    .alert-err { background: #fdecea; color: #c62828; padding: 12px 16px; border-radius: 9px; font-size: 14px; margin-bottom: 20px; }
    .card { background: #fff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.07); padding: 22px; margin-bottom: 14px; }
    .card-title { font-size: 11px; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 16px; }
    .field { margin-bottom: 14px; }
    .field:last-child { margin-bottom: 0; }
    label { display: block; font-size: 13px; color: #555; margin-bottom: 5px; }
    input[type=text], input[type=password], select {
      width: 100%; padding: 9px 12px; border-radius: 9px;
      border: 1px solid rgba(0,0,0,0.13); font-size: 14px; color: #1a1a1a;
      background: #fff; outline: none; transition: border-color 0.15s;
    }
    input:focus, select:focus { border-color: #888; }
    .three-colors { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
    .color-block label { font-size: 12px; color: #777; margin-bottom: 5px; }
    .color-swatch { width: 100%; height: 44px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.1); cursor: pointer; margin-bottom: 5px; display: block; }
    .color-hex { width: 100%; padding: 6px 10px; border-radius: 7px; border: 1px solid rgba(0,0,0,0.13); font-size: 13px; text-align: center; }
    .check-row { display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer; }
    .check-row input { width: 16px; height: 16px; }
    .hint { font-size: 11px; color: #bbb; margin-top: 4px; }
    .logo-preview { display: flex; align-items: center; gap: 10px; margin-top: 8px; }
    .logo-preview img { height: 36px; border-radius: 4px; border: 1px solid rgba(0,0,0,0.09); }
    .rm-logo { font-size: 12px; color: #c0392b; background: none; border: none; cursor: pointer; }
    input[type=file] { font-size: 13px; color: #555; }
    .text-mode-row { display: flex; gap: 10px; }
    .text-mode-label { display: flex; align-items: center; gap: 8px; padding: 9px 16px; border-radius: 9px; border: 1.5px solid rgba(0,0,0,0.1); cursor: pointer; font-size: 14px; font-weight: 500; }
    .text-mode-label input { width: 15px; height: 15px; }
    .color-dot { width: 18px; height: 18px; border-radius: 50%; border: 2px solid #ddd; flex-shrink: 0; }
    .actions { display: flex; gap: 10px; align-items: center; margin-top: 4px; flex-wrap: wrap; }
    .btn { padding: 10px 22px; border-radius: 9px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: opacity 0.15s; }
    .btn:hover { opacity: 0.85; }
    .btn-dark   { background: #1a1a1a; color: #fff; }
    .btn-green  { background: #1ac8a0; color: #fff; }
    .btn-outline { background: transparent; border: 1px solid rgba(0,0,0,0.2); color: #333; }
    .btn-red    { background: #e74c3c; color: #fff; }
    .btn-sm { padding: 7px 14px; font-size: 13px; border-radius: 8px; }
    .back-link { font-size: 13px; color: #888; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 18px; }
    .back-link:hover { color: #333; }
    .preview-link { font-size: 13px; color: #999; text-decoration: none; }
    .preview-link:hover { color: #333; }
    /* Profile list */
    .profile-list { display: flex; flex-direction: column; gap: 10px; }
    .profile-item { background: #fff; border-radius: 12px; border: 2px solid transparent; padding: 16px 18px; display: flex; align-items: center; gap: 14px; }
    .profile-item.active { border-color: #1ac8a0; background: #f0fdf9; }
    .p-dots { display: flex; gap: 5px; flex-shrink: 0; }
    .p-dot { width: 13px; height: 13px; border-radius: 50%; }
    .p-info { flex: 1; }
    .p-name { font-size: 15px; font-weight: 600; }
    .p-meta { font-size: 12px; color: #aaa; margin-top: 2px; }
    .p-badge { font-size: 11px; font-weight: 600; color: #1ac8a0; background: #e0faf3; padding: 3px 8px; border-radius: 20px; }
    .p-actions { display: flex; gap: 7px; flex-shrink: 0; }
  </style>
</head>
<body>
<div class="wrap">
  <header>
    <h1>⚙ Admin</h1>
    <a href="index.php">← Zum Timer</a>
  </header>

  <?php if ($msg): ?><div class="alert-ok">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert-err">✗ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <?php if ($edit_id && $edit_p): ?>
  <!-- ══════════════ PROFIL BEARBEITEN ══════════════ -->
  <a href="admin.php" class="back-link">← Zurück</a>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save_profile">
    <input type="hidden" name="profile_id" value="<?= $edit_id ?>">
    <?php if (!empty($s['password'])): ?>
      <input type="hidden" name="admin_password" value="">
    <?php endif; ?>

    <div class="card">
      <div class="card-title">Profilname</div>
      <input type="text" name="name" value="<?= htmlspecialchars($edit_p['name'] ?? '') ?>" placeholder="z.B. Edulab Workshop" autofocus>
    </div>

    <div class="card">
      <div class="card-title">Branding</div>
      <div class="field">
        <label>App-Titel (erscheint oben links)</label>
        <input type="text" name="title" value="<?= htmlspecialchars($edit_p['title'] ?? 'Timebox') ?>">
      </div>
      <div class="field">
        <label>Logo</label>
        <input type="file" name="logo" accept=".png,.jpg,.jpeg,.gif,.svg,.webp">
        <?php if (!empty($edit_p['logo_path'])): ?>
        <div class="logo-preview" id="lprev">
          <img src="<?= htmlspecialchars($edit_p['logo_path']) ?>" alt="Logo">
          <button type="button" class="rm-logo" onclick="document.getElementById('rl').value='1';document.getElementById('lprev').remove();">✕ entfernen</button>
        </div>
        <?php endif; ?>
        <input type="hidden" name="remove_logo" id="rl" value="">
      </div>
    </div>

    <div class="card">
      <div class="card-title">Schrift & Ton</div>
      <div class="field">
        <label>Schriftart</label>
        <select name="font">
          <?php foreach (['Inter'=>'Modern (Inter)','Bebas Neue'=>'Block','Space Mono'=>'Mono','Syne'=>'Syne'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= ($edit_p['font']??'Inter')===$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Ton beim Ablauf</label>
        <select name="default_sound">
          <?php foreach (['gong'=>'Gong','bell'=>'Indian Bell','bowl'=>'Singing Bowl','off'=>'Kein Ton'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= ($edit_p['default_sound']??'gong')===$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Farben</div>
      <div class="field">
        <label>Schriftfarbe</label>
        <div class="text-mode-row">
          <label class="text-mode-label">
            <input type="radio" name="text_color" value="#ffffff" <?= (($edit_p['text_color']??'#ffffff')==='#ffffff')?'checked':'' ?>>
            <span class="color-dot" style="background:#fff;"></span> Hell
          </label>
          <label class="text-mode-label">
            <input type="radio" name="text_color" value="#444444" <?= (($edit_p['text_color']??'')==='#444444')?'checked':'' ?>>
            <span class="color-dot" style="background:#444;"></span> Dunkel
          </label>
        </div>
      </div>
      <div class="three-colors">
        <?php
        $cols = [
          ['color_idle','ci','Grundfarbe',$edit_p['color_idle']??'#1ac8a0'],
          ['color_warn','cw','Letzte Minute',$edit_p['color_warn']??'#e8833a'],
          ['color_done','cd','Zeit abgelaufen',$edit_p['color_done']??'#7c3aed'],
        ];
        foreach ($cols as [$name,$id,$lbl,$val]):
        ?>
        <div class="color-block">
          <label><?= $lbl ?></label>
          <input type="color" class="color-swatch" id="<?= $id ?>_p" value="<?= $val ?>"
            oninput="document.getElementById('<?= $id ?>').value=this.value">
          <input type="text" class="color-hex" name="<?= $name ?>" id="<?= $id ?>" value="<?= $val ?>"
            oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value))document.getElementById('<?= $id ?>_p').value=this.value">
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Timer-Optionen</div>
      <div class="field">
        <label class="check-row">
          <input type="checkbox" name="show_presets" <?= !empty($edit_p['show_presets'])?'checked':'' ?>>
          Preset-Buttons anzeigen
        </label>
      </div>
      <div class="field">
        <label>Preset-Zeiten (Minuten, kommagetrennt)</label>
        <input type="text" name="preset_times" value="<?= implode(',', $edit_p['preset_times']??[5,15,25,45,60]) ?>">
        <p class="hint">z.B. 5,15,25,45,60</p>
      </div>
    </div>

    <div class="actions">
      <button type="submit" class="btn btn-dark">Profil speichern</button>
      <a href="admin.php" class="preview-link">Abbrechen</a>
    </div>
  </form>

  <?php else: ?>
  <!-- ══════════════ PROFILLISTE ══════════════ -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <div style="font-size:15px;font-weight:600;">Profile</div>
    <form method="POST">
      <input type="hidden" name="action" value="new_profile">
      <?php if (!empty($s['password'])): ?>
        <input type="hidden" name="admin_password" value="">
      <?php endif; ?>
      <button type="submit" class="btn btn-green btn-sm">+ Neues Profil</button>
    </form>
  </div>

  <?php if (empty($profiles)): ?>
    <div class="card" style="text-align:center;color:#aaa;padding:40px;">
      Noch keine Profile. Klicke auf "+ Neues Profil".
    </div>
  <?php else: ?>
    <div class="profile-list">
    <?php foreach ($profiles as $pid => $p):
      $isActive = ($s['active_id'] === $pid);
    ?>
      <div class="profile-item <?= $isActive ? 'active' : '' ?>">
        <div class="p-dots">
          <div class="p-dot" style="background:<?= htmlspecialchars($p['color_idle']??'#ccc') ?>"></div>
          <div class="p-dot" style="background:<?= htmlspecialchars($p['color_warn']??'#ccc') ?>"></div>
          <div class="p-dot" style="background:<?= htmlspecialchars($p['color_done']??'#ccc') ?>"></div>
        </div>
        <div class="p-info">
          <div class="p-name"><?= htmlspecialchars($p['name'] ?? 'Profil') ?></div>
          <div class="p-meta"><?= htmlspecialchars($p['title']??'') ?> · <?= htmlspecialchars($p['font']??'Inter') ?></div>
        </div>
        <?php if ($isActive): ?><span class="p-badge">✓ Aktiv</span><?php endif; ?>
        <div class="p-actions">
          <?php if (!$isActive): ?>
          <form method="POST">
            <input type="hidden" name="action" value="activate">
            <input type="hidden" name="profile_id" value="<?= $pid ?>">
            <?php if (!empty($s['password'])): ?><input type="hidden" name="admin_password" value=""><?php endif; ?>
            <button type="submit" class="btn btn-green btn-sm">Aktivieren</button>
          </form>
          <?php endif; ?>
          <a href="admin.php?edit=<?= $pid ?>" class="btn btn-outline btn-sm">Bearbeiten</a>
          <form method="POST" onsubmit="return confirm('Profil wirklich loschen?')">
            <input type="hidden" name="action" value="delete_profile">
            <input type="hidden" name="profile_id" value="<?= $pid ?>">
            <?php if (!empty($s['password'])): ?><input type="hidden" name="admin_password" value=""><?php endif; ?>
            <button type="submit" class="btn btn-red btn-sm">×</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Passwort -->
  <div class="card" style="margin-top:24px;">
    <div class="card-title">Sicherheit</div>
    <form method="POST">
      <input type="hidden" name="action" value="save_password">
      <div class="field">
        <label>Admin-Passwort (leer = kein Schutz)</label>
        <input type="password" name="new_password" value="<?= htmlspecialchars($s['password']) ?>" placeholder="Passwort setzen...">
      </div>
      <div class="actions"><button type="submit" class="btn btn-dark btn-sm">Passwort speichern</button></div>
    </form>
  </div>

  <?php endif; ?>
</div>
</body>
</html>
