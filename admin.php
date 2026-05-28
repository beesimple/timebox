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

function save_profiles($profiles) {
    file_put_contents('profiles.json', json_encode($profiles, JSON_PRETTY_PRINT));
}

function save_settings($s) {
    file_put_contents('settings.json', json_encode($s, JSON_PRETTY_PRINT));
}

$s        = load_settings();
$profiles = load_profiles();
$error    = '';
$success  = '';

// Password check helper
function check_password($s) {
    if (!empty($s['password']) && ($_POST['admin_password'] ?? '') !== $s['password']) {
        return false;
    }
    return true;
}

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!check_password($s)) {
        $error = 'Falsches Passwort.';
        goto render;
    }

    // ── SAVE ACTIVE SETTINGS ──────────────────────────────────────
    if ($action === 'save_settings') {
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
            'password'      => $_POST['password'] ?? $s['password'],
        ];
        save_settings($new);
        $s = $new;
        $success = 'Einstellungen gespeichert.';
    }

    // ── SAVE AS NEW PROFILE ───────────────────────────────────────
    if ($action === 'save_profile') {
        $name = trim($_POST['profile_name'] ?? '');
        if ($name === '') { $error = 'Profilname darf nicht leer sein.'; goto render; }
        $id = 'p_' . time() . '_' . rand(100,999);
        $profile = $s;
        $profile['name'] = $name;
        unset($profile['password']);
        $profiles[$id] = $profile;
        save_profiles($profiles);
        $success = 'Profil ' . $name . ' gespeichert.';
    }

    // ── ACTIVATE PROFILE ─────────────────────────────────────────
    if ($action === 'activate_profile') {
        $id = $_POST['profile_id'] ?? '';
        if (isset($profiles[$id])) {
            $new = array_merge(defaults(), $profiles[$id]);
            $new['password'] = $s['password']; // keep password
            save_settings($new);
            $s = $new;
            $success = 'Profil ' . $profiles[$id]['name'] . ' aktiviert.';
        }
    }

    // ── DELETE PROFILE ────────────────────────────────────────────
    if ($action === 'delete_profile') {
        $id = $_POST['profile_id'] ?? '';
        if (isset($profiles[$id])) {
            $name = $profiles[$id]['name'];
            unset($profiles[$id]);
            save_profiles($profiles);
            $success = 'Profil ' . $name . ' geloescht.';
        }
    }

    // ── UPDATE PROFILE ────────────────────────────────────────────
    if ($action === 'update_profile') {
        $id = $_POST['profile_id'] ?? '';
        if (isset($profiles[$id])) {
            $logo_path = $profiles[$id]['logo_path'] ?? '';
            if (!empty($_FILES['profile_logo']['name'])) {
                $ext = strtolower(pathinfo($_FILES['profile_logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','svg','webp'])) {
                    if (!is_dir('uploads')) mkdir('uploads', 0755, true);
                    $fn = 'uploads/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['profile_logo']['name']));
                    if (move_uploaded_file($_FILES['profile_logo']['tmp_name'], $fn)) $logo_path = $fn;
                }
            }
            if (!empty($_POST['remove_profile_logo'])) $logo_path = '';
            $raw = $_POST['profile_preset_times'] ?? '5,15,25,45,60';
            $pts = array_values(array_filter(array_map('intval', explode(',', $raw))));
            if (empty($pts)) $pts = [5,15,25,45,60];
            $profiles[$id] = array_merge($profiles[$id], [
                'name'          => trim($_POST['profile_name_edit'] ?? $profiles[$id]['name']),
                'title'         => trim($_POST['profile_title'] ?? 'Timebox'),
                'font'          => $_POST['profile_font'] ?? 'Inter',
                'color_idle'    => $_POST['profile_color_idle'] ?? '#1ac8a0',
                'color_warn'    => $_POST['profile_color_warn'] ?? '#e8833a',
                'color_done'    => $_POST['profile_color_done'] ?? '#7c3aed',
                'default_sound' => $_POST['profile_default_sound'] ?? 'gong',
                'show_presets'  => isset($_POST['profile_show_presets']),
                'preset_times'  => $pts,
                'logo_path'     => $logo_path,
            ]);
            save_profiles($profiles);
            $success = 'Profil ' . $profiles[$id]['name'] . ' aktualisiert.';
        }
    }

    $profiles = load_profiles();
}

render:
$edit_id = $_GET['edit'] ?? null;
$edit_profile = ($edit_id && isset($profiles[$edit_id])) ? $profiles[$edit_id] : null;
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
    .wrap { max-width: 680px; margin: 0 auto; }

    header { display: flex; align-items: center; margin-bottom: 32px; }
    header h1 { font-size: 20px; font-weight: 600; }
    header a { margin-left: auto; font-size: 13px; color: #888; text-decoration: none; }
    header a:hover { color: #333; }

    .tabs { display: flex; gap: 4px; margin-bottom: 24px; background: rgba(0,0,0,0.06); padding: 4px; border-radius: 12px; }
    .tab { flex: 1; padding: 10px; border-radius: 9px; border: none; background: transparent; font-size: 14px; font-weight: 500; color: #666; cursor: pointer; transition: all 0.15s; text-align: center; }
    .tab.active { background: #fff; color: #1a1a1a; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }

    .tab-content { display: none; }
    .tab-content.active { display: block; }

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
    .ok  { background: #e8f5e9; color: #2e7d32; }
    .err { background: #fdecea; color: #c62828; }
    .actions { display: flex; align-items: center; gap: 12px; margin-top: 6px; flex-wrap: wrap; }
    .btn { padding: 11px 24px; border-radius: 10px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; transition: opacity 0.15s; }
    .btn-dark { background: #1a1a1a; color: #fff; }
    .btn-dark:hover { opacity: 0.8; }
    .btn-outline { background: transparent; border: 1px solid rgba(0,0,0,0.2); color: #333; }
    .btn-outline:hover { background: rgba(0,0,0,0.04); }
    .btn-green { background: #1ac8a0; color: #fff; }
    .btn-green:hover { opacity: 0.85; }
    .btn-red { background: #c0392b; color: #fff; }
    .btn-red:hover { opacity: 0.85; }
    .btn-sm { padding: 7px 14px; font-size: 13px; border-radius: 8px; }
    a.btn { text-decoration: none; display: inline-flex; align-items: center; }
    .preview-link { font-size: 13px; color: #999; text-decoration: none; }
    .preview-link:hover { color: #333; }
    input[type=file] { font-size: 13px; color: #555; }

    /* PROFILES LIST */
    .profile-list { display: flex; flex-direction: column; gap: 10px; }
    .profile-item {
      background: #fff; border-radius: 12px; border: 1px solid rgba(0,0,0,0.07);
      padding: 16px 20px; display: flex; align-items: center; gap: 14px;
    }
    .profile-item.is-active { border-color: #1ac8a0; background: #f0fdf9; }
    .profile-color-dots { display: flex; gap: 5px; flex-shrink: 0; }
    .profile-dot { width: 14px; height: 14px; border-radius: 50%; }
    .profile-info { flex: 1; min-width: 0; }
    .profile-name { font-size: 15px; font-weight: 600; color: #1a1a1a; }
    .profile-meta { font-size: 12px; color: #aaa; margin-top: 2px; }
    .active-badge { font-size: 11px; font-weight: 600; color: #1ac8a0; background: #e0faf3; padding: 3px 8px; border-radius: 20px; flex-shrink: 0; }
    .profile-actions { display: flex; gap: 8px; flex-shrink: 0; }

    /* SAVE AS PROFILE */
    .save-profile-row { display: flex; gap: 10px; align-items: flex-end; }
    .save-profile-row input { flex: 1; }
    .save-profile-row button { flex-shrink: 0; }

    /* EDIT FORM */
    .edit-back { font-size: 13px; color: #888; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 16px; }
    .edit-back:hover { color: #333; }
  </style>
</head>
<body>
<div class="wrap">
  <header>
    <h1>⚙ Admin</h1>
    <a href="index.php">← Zum Timer</a>
  </header>

  <?php if ($success): ?><div class="alert ok">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert err">✗ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <?php if (!empty($s['password']) && empty($_SESSION['admin_ok'])): ?>
  <!-- Password gate -->
  <div class="card">
    <div class="card-title">Passwort</div>
    <form method="POST">
      <input type="hidden" name="action" value="save_settings">
      <div class="field">
        <label>Admin-Passwort</label>
        <input type="password" name="admin_password" placeholder="Passwort eingeben" autofocus>
      </div>
      <div class="actions"><button type="submit" class="btn btn-dark">Anmelden</button></div>
    </form>
  </div>
  <?php else: ?>

  <!-- TABS -->
  <div class="tabs">
    <button class="tab <?= !$edit_id ? 'active' : '' ?>" onclick="showTab('settings')">Aktive Einstellungen</button>
    <button class="tab" onclick="showTab('profiles')">Profile (<?= count($profiles) ?>)</button>
  </div>

  <!-- TAB: SETTINGS -->
  <div id="tab-settings" class="tab-content <?= !$edit_id ? 'active' : '' ?>">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save_settings">
      <?php if (!empty($s['password'])): ?>
        <input type="hidden" name="admin_password" value="<?= htmlspecialchars($_POST['admin_password'] ?? '') ?>">
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
          <p class="hint">Schützt dieses Admin-Panel.</p>
        </div>
      </div>

      <div class="actions">
        <button type="submit" class="btn btn-dark">Speichern</button>
        <a href="index.php" target="_blank" class="preview-link">Timer öffnen ↗</a>
      </div>
    </form>

    <!-- Save current as profile -->
    <div class="card" style="margin-top:14px;">
      <div class="card-title">Aktuelle Einstellungen als Profil speichern</div>
      <form method="POST">
        <input type="hidden" name="action" value="save_profile">
        <?php if (!empty($s['password'])): ?>
          <input type="hidden" name="admin_password" value="<?= htmlspecialchars($_POST['admin_password'] ?? '') ?>">
        <?php endif; ?>
        <div class="save-profile-row">
          <input type="text" name="profile_name" placeholder="Profilname z.B. «Edulab Workshop»" required>
          <button type="submit" class="btn btn-green">Als Profil speichern</button>
        </div>
      </form>
    </div>
  </div>

  <!-- TAB: PROFILES -->
  <div id="tab-profiles" class="tab-content">
    <?php if (empty($profiles)): ?>
      <div class="card" style="text-align:center;color:#aaa;padding:40px;">
        Noch keine Profile. Einstellungen konfigurieren und oben als Profil speichern.
      </div>
    <?php else: ?>
      <div class="profile-list">
        <?php foreach ($profiles as $pid => $p):
          $isActive = ($p['color_idle']===$s['color_idle'] && $p['title']===$s['title'] && ($p['color_warn']??'') === $s['color_warn']);
        ?>
        <div class="profile-item <?= $isActive ? 'is-active' : '' ?>">
          <div class="profile-color-dots">
            <div class="profile-dot" style="background:<?= htmlspecialchars($p['color_idle']??'#ccc') ?>"></div>
            <div class="profile-dot" style="background:<?= htmlspecialchars($p['color_warn']??'#ccc') ?>"></div>
            <div class="profile-dot" style="background:<?= htmlspecialchars($p['color_done']??'#ccc') ?>"></div>
          </div>
          <div class="profile-info">
            <div class="profile-name"><?= htmlspecialchars($p['name']) ?></div>
            <div class="profile-meta"><?= htmlspecialchars($p['title']??'') ?> · <?= htmlspecialchars($p['font']??'Inter') ?></div>
          </div>
          <?php if ($isActive): ?><span class="active-badge">✓ Aktiv</span><?php endif; ?>
          <div class="profile-actions">
            <?php if (!$isActive): ?>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="activate_profile">
              <input type="hidden" name="profile_id" value="<?= $pid ?>">
              <?php if (!empty($s['password'])): ?><input type="hidden" name="admin_password" value="<?= htmlspecialchars($_POST['admin_password'] ?? '') ?>"><?php endif; ?>
              <button type="submit" class="btn btn-green btn-sm">Aktivieren</button>
            </form>
            <?php endif; ?>
            <a href="?edit=<?= $pid ?>" class="btn btn-outline btn-sm">Bearbeiten</a>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Profil wirklich löschen?')">
              <input type="hidden" name="action" value="delete_profile">
              <input type="hidden" name="profile_id" value="<?= $pid ?>">
              <?php if (!empty($s['password'])): ?><input type="hidden" name="admin_password" value="<?= htmlspecialchars($_POST['admin_password'] ?? '') ?>"><?php endif; ?>
              <button type="submit" class="btn btn-red btn-sm">Löschen</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($edit_profile): ?>
    <!-- EDIT PROFILE FORM -->
    <div style="margin-top:24px;">
      <a href="admin.php" class="edit-back">← Zurück zu Profilen</a>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_profile">
        <input type="hidden" name="profile_id" value="<?= $edit_id ?>">
        <?php if (!empty($s['password'])): ?><input type="hidden" name="admin_password" value="<?= htmlspecialchars($_POST['admin_password'] ?? '') ?>"><?php endif; ?>

        <div class="card">
          <div class="card-title">Profil bearbeiten</div>
          <div class="field">
            <label>Profilname</label>
            <input type="text" name="profile_name_edit" value="<?= htmlspecialchars($edit_profile['name']) ?>">
          </div>
          <div class="field">
            <label>App-Titel</label>
            <input type="text" name="profile_title" value="<?= htmlspecialchars($edit_profile['title']??'') ?>">
          </div>
          <div class="field">
            <label>Logo</label>
            <input type="file" name="profile_logo" accept=".png,.jpg,.jpeg,.gif,.svg,.webp">
            <?php if (!empty($edit_profile['logo_path'])): ?>
            <div class="logo-preview">
              <img src="<?= htmlspecialchars($edit_profile['logo_path']) ?>" alt="Logo">
              <button type="button" class="rm-logo" onclick="document.getElementById('rmPLogo').value='1';this.closest('.logo-preview').remove();">✕</button>
            </div>
            <?php endif; ?>
            <input type="hidden" name="remove_profile_logo" id="rmPLogo" value="">
          </div>
        </div>

        <div class="card">
          <div class="card-title">Schrift & Ton</div>
          <div class="field">
            <label>Schriftart</label>
            <select name="profile_font">
              <?php foreach (['Inter'=>'Modern (Inter)','Bebas Neue'=>'Block (Bebas Neue)','Space Mono'=>'Mono (Space Mono)','Syne'=>'Syne'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($edit_profile['font']??'Inter')===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Ton beim Ablauf</label>
            <select name="profile_default_sound">
              <?php foreach (['gong'=>'Gong','bell'=>'Indian Bell','bowl'=>'Singing Bowl','off'=>'Kein Ton'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($edit_profile['default_sound']??'gong')===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="card">
          <div class="card-title">Farben</div>
          <div class="three-colors">
            <div class="color-block">
              <label>Grundfarbe</label>
              <input type="color" class="color-swatch" value="<?= $edit_profile['color_idle']??'#1ac8a0' ?>" oninput="document.getElementById('pci').value=this.value">
              <input type="text" class="color-hex" name="profile_color_idle" id="pci" value="<?= $edit_profile['color_idle']??'#1ac8a0' ?>">
            </div>
            <div class="color-block">
              <label>Letzte Minute</label>
              <input type="color" class="color-swatch" value="<?= $edit_profile['color_warn']??'#e8833a' ?>" oninput="document.getElementById('pcw').value=this.value">
              <input type="text" class="color-hex" name="profile_color_warn" id="pcw" value="<?= $edit_profile['color_warn']??'#e8833a' ?>">
            </div>
            <div class="color-block">
              <label>Zeit abgelaufen</label>
              <input type="color" class="color-swatch" value="<?= $edit_profile['color_done']??'#7c3aed' ?>" oninput="document.getElementById('pcd').value=this.value">
              <input type="text" class="color-hex" name="profile_color_done" id="pcd" value="<?= $edit_profile['color_done']??'#7c3aed' ?>">
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-title">Timer-Optionen</div>
          <div class="field">
            <label class="check-row">
              <input type="checkbox" name="profile_show_presets" <?= !empty($edit_profile['show_presets'])?'checked':'' ?>>
              Preset-Buttons anzeigen
            </label>
          </div>
          <div class="field">
            <label>Preset-Zeiten (Minuten)</label>
            <input type="text" name="profile_preset_times" value="<?= implode(',', $edit_profile['preset_times']??[5,15,25,45,60]) ?>">
          </div>
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-dark">Profil speichern</button>
          <a href="admin.php" class="preview-link">Abbrechen</a>
        </div>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</div>

<script>
function showTab(name) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  event.target.classList.add('active');
}
<?php if ($edit_id): ?>
document.addEventListener('DOMContentLoaded', () => showTab('profiles'));
<?php endif; ?>
</script>
</body>
</html>
