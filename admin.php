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

function save_settings($data) {
    file_put_contents('settings.json', json_encode($data, JSON_PRETTY_PRINT));
}

function upload_logo($file_key) {
    if (empty($_FILES[$file_key]['name']) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','svg','webp'])) return null;
    if (!is_dir('uploads')) mkdir('uploads', 0755, true);
    $fn = 'uploads/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES[$file_key]['name']));
    return move_uploaded_file($_FILES[$file_key]['tmp_name'], $fn) ? $fn : null;
}

function fields_from_post($prefix = '') {
    $p = $prefix;
    $raw = $_POST[$p.'preset_times'] ?? '5,15,25,45,60';
    $pts = array_values(array_filter(array_map('intval', explode(',', $raw))));
    if (empty($pts)) $pts = [5,15,25,45,60];
    return [
        'title'         => trim($_POST[$p.'title'] ?? 'Timebox'),
        'font'          => $_POST[$p.'font'] ?? 'Inter',
        'color_idle'    => $_POST[$p.'color_idle'] ?? '#1ac8a0',
        'color_warn'    => $_POST[$p.'color_warn'] ?? '#e8833a',
        'color_done'    => $_POST[$p.'color_done'] ?? '#7c3aed',
        'text_color'    => $_POST[$p.'text_color'] ?? '#ffffff',
        'default_sound' => $_POST[$p.'default_sound'] ?? 'gong',
        'show_presets'  => isset($_POST[$p.'show_presets']),
        'preset_times'  => $pts,
    ];
}

$s        = load_settings();
$profiles = load_profiles();
$error    = '';
$success  = '';
$action   = $_POST['action'] ?? $_GET['action'] ?? '';

// Password check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($s['password']) && ($_POST['admin_password'] ?? '') !== $s['password']) {
        $error = 'Falsches Passwort.';
        goto render;
    }

    // ── SAVE ACTIVE SETTINGS ─────────────────────────────────────
    if ($action === 'save_settings') {
        $logo = upload_logo('logo');
        $data = fields_from_post();
        $data['logo_path'] = $logo ?? (empty($_POST['remove_logo']) ? $s['logo_path'] : '');
        $data['password']  = $_POST['password'] ?? $s['password'];
        save_settings($data);
        header('Location: admin.php?tab=settings&saved=1');
        exit;
    }

    // ── NEW EMPTY PROFILE ─────────────────────────────────────────
    if ($action === 'new_profile') {
        $id = 'p_' . time() . '_' . rand(100,999);
        $profile = defaults();
        $profile['name'] = 'Neues Profil';
        unset($profile['password']);
        $profiles[$id] = $profile;
        save_profiles($profiles);
        header('Location: admin.php?tab=profiles&edit=' . $id);
        exit;
    }

    // ── SAVE PROFILE ─────────────────────────────────────────────
    if ($action === 'save_profile') {
        $id = $_POST['profile_id'] ?? '';
        if (!isset($profiles[$id])) { $error = 'Profil nicht gefunden.'; goto render; }
        $logo = upload_logo('profile_logo');
        $data = fields_from_post('p_');
        $data['name']      = trim($_POST['p_name'] ?? 'Profil');
        $data['logo_path'] = $logo ?? (empty($_POST['remove_profile_logo']) ? ($profiles[$id]['logo_path'] ?? '') : '');
        $profiles[$id] = $data;
        save_profiles($profiles);
        $success = 'Profil gespeichert.';
        header('Location: admin.php?tab=profiles&saved=1');
        exit;
    }

    // ── ACTIVATE PROFILE ─────────────────────────────────────────
    if ($action === 'activate_profile') {
        $id = $_POST['profile_id'] ?? '';
        if (isset($profiles[$id])) {
            $data = array_merge(defaults(), $profiles[$id]);
            $data['password'] = $s['password'];
            save_settings($data);
        }
        header('Location: admin.php?tab=profiles&saved=1');
        exit;
    }

    // ── DELETE PROFILE ────────────────────────────────────────────
    if ($action === 'delete_profile') {
        $id = $_POST['profile_id'] ?? '';
        if (isset($profiles[$id])) {
            unset($profiles[$id]);
            save_profiles($profiles);
        }
        header('Location: admin.php?tab=profiles');
        exit;
    }

    $profiles = load_profiles();
}

render:
$active_tab = $_GET['tab'] ?? 'settings';
$edit_id    = $_GET['edit'] ?? null;
$edit_p     = ($edit_id && isset($profiles[$edit_id])) ? $profiles[$edit_id] : null;
if ($_GET['saved'] ?? false) {
    $success = $active_tab === 'profiles' ? 'Profil gespeichert.' : 'Einstellungen gespeichert.';
}
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
    header { display: flex; align-items: center; margin-bottom: 28px; }
    header h1 { font-size: 20px; font-weight: 600; }
    header a { margin-left: auto; font-size: 13px; color: #888; text-decoration: none; }
    header a:hover { color: #333; }
    .tabs { display: flex; gap: 4px; margin-bottom: 24px; background: rgba(0,0,0,0.06); padding: 4px; border-radius: 12px; }
    .tab { flex: 1; padding: 10px; border-radius: 9px; border: none; background: transparent; font-size: 14px; font-weight: 500; color: #666; cursor: pointer; transition: all 0.15s; text-align: center; text-decoration: none; display: block; }
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
    .color-swatch { width: 100%; height: 48px; border-radius: 9px; border: 1px solid rgba(0,0,0,0.1); cursor: pointer; margin-bottom: 6px; display: block; }
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
    .btn { padding: 11px 24px; border-radius: 10px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; transition: opacity 0.15s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .btn-dark   { background: #1a1a1a; color: #fff; }
    .btn-dark:hover { opacity: 0.8; }
    .btn-green  { background: #1ac8a0; color: #fff; }
    .btn-green:hover { opacity: 0.85; }
    .btn-outline { background: transparent; border: 1px solid rgba(0,0,0,0.2); color: #333; }
    .btn-outline:hover { background: rgba(0,0,0,0.04); }
    .btn-red    { background: #c0392b; color: #fff; }
    .btn-red:hover { opacity: 0.85; }
    .btn-sm { padding: 7px 14px; font-size: 13px; border-radius: 8px; }
    .preview-link { font-size: 13px; color: #999; text-decoration: none; }
    .preview-link:hover { color: #333; }
    input[type=file] { font-size: 13px; color: #555; }
    .profile-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px; }
    .profile-item { background: #fff; border-radius: 12px; border: 1px solid rgba(0,0,0,0.07); padding: 16px 20px; display: flex; align-items: center; gap: 14px; }
    .profile-item.is-active { border-color: #1ac8a0; background: #f0fdf9; }
    .profile-dots { display: flex; gap: 5px; flex-shrink: 0; }
    .profile-dot { width: 14px; height: 14px; border-radius: 50%; }
    .profile-info { flex: 1; min-width: 0; }
    .profile-name { font-size: 15px; font-weight: 600; }
    .profile-meta { font-size: 12px; color: #aaa; margin-top: 2px; }
    .active-badge { font-size: 11px; font-weight: 600; color: #1ac8a0; background: #e0faf3; padding: 3px 8px; border-radius: 20px; flex-shrink: 0; }
    .profile-actions { display: flex; gap: 8px; flex-shrink: 0; }
    .back-link { font-size: 13px; color: #888; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 20px; }
    .back-link:hover { color: #333; }
    .text-mode-row { display: flex; gap: 10px; }
    .text-mode-label { display: flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; border: 1.5px solid rgba(0,0,0,0.1); cursor: pointer; font-size: 14px; font-weight: 500; transition: border-color 0.15s; }
    .text-mode-label input { width: 16px; height: 16px; cursor: pointer; }
    .color-dot { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #ddd; flex-shrink: 0; }
  </style>
</head>
<body>
<div class="wrap">
  <header>
    <h1>⚙ Admin</h1>
    <a href="index.php">← Zum Timer</a>
  </header>

  <?php if ($success): ?><div class="alert ok">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert err">✗ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <?php if (!empty($s['password'])): ?>
  <form method="POST" style="margin-bottom:20px;">
    <input type="hidden" name="action" value="check_pw">
    <div style="display:flex;gap:10px;">
      <input type="password" name="admin_password" placeholder="Admin-Passwort" style="max-width:280px;">
      <button type="submit" class="btn btn-dark btn-sm">Anmelden</button>
    </div>
  </form>
  <?php endif; ?>

  <!-- TABS -->
  <div class="tabs">
    <a href="admin.php?tab=settings" class="tab <?= $active_tab==='settings'&&!$edit_id ? 'active' : '' ?>">Einstellungen</a>
    <a href="admin.php?tab=profiles" class="tab <?= $active_tab==='profiles'||$edit_id ? 'active' : '' ?>">Profile (<?= count($profiles) ?>)</a>
  </div>

  <!-- ═══════════════ TAB: EINSTELLUNGEN ═══════════════ -->
  <div class="tab-content <?= $active_tab==='settings'&&!$edit_id ? 'active' : '' ?>">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save_settings">
      <?php if (!empty($s['password'])): ?>
        <input type="hidden" name="admin_password" value="<?= htmlspecialchars($_POST['admin_password'] ?? '') ?>">
      <?php endif; ?>

      <?php echo settings_form($s, ''); ?>

      <div class="actions">
        <button type="submit" class="btn btn-dark">Speichern</button>
        <a href="index.php" target="_blank" class="preview-link">Timer öffnen ↗</a>
      </div>
    </form>
  </div>

  <!-- ═══════════════ TAB: PROFILE ═══════════════ -->
  <div class="tab-content <?= $active_tab==='profiles'||$edit_id ? 'active' : '' ?>">

    <?php if ($edit_id && $edit_p): ?>
      <!-- EDIT SINGLE PROFILE -->
      <a href="admin.php?tab=profiles" class="back-link">← Zurück zu Profilen</a>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_profile">
        <input type="hidden" name="profile_id" value="<?= $edit_id ?>">
        <?php if (!empty($s['password'])): ?>
          <input type="hidden" name="admin_password" value="<?= htmlspecialchars($_POST['admin_password'] ?? '') ?>">
        <?php endif; ?>

        <div class="card">
          <div class="card-title">Profilname</div>
          <div class="field">
            <input type="text" name="p_name" value="<?= htmlspecialchars($edit_p['name'] ?? '') ?>" placeholder="z.B. Edulab Workshop" autofocus>
          </div>
        </div>

        <?php echo settings_form($edit_p, 'p_'); ?>

        <div class="actions">
          <button type="submit" class="btn btn-dark">Profil speichern</button>
          <a href="admin.php?tab=profiles" class="preview-link">Abbrechen</a>
        </div>
      </form>

    <?php else: ?>
      <!-- PROFILE LIST -->
      <div style="margin-bottom:16px;">
        <form method="POST">
          <input type="hidden" name="action" value="new_profile">
          <?php if (!empty($s['password'])): ?>
            <input type="hidden" name="admin_password" value="<?= htmlspecialchars($_POST['admin_password'] ?? '') ?>">
          <?php endif; ?>
          <button type="submit" class="btn btn-green">+ Neues Profil</button>
        </form>
      </div>

      <?php if (empty($profiles)): ?>
        <div class="card" style="text-align:center;color:#aaa;padding:40px;">
          Noch keine Profile. Klicke auf "Neues Profil" um eines zu erstellen.
        </div>
      <?php else: ?>
        <div class="profile-list">
          <?php foreach ($profiles as $pid => $p):
            $isActive = (($p['color_idle']??'') === $s['color_idle']
                      && ($p['title']??'') === $s['title']
                      && ($p['color_warn']??'') === $s['color_warn']);
          ?>
          <div class="profile-item <?= $isActive ? 'is-active' : '' ?>">
            <div class="profile-dots">
              <div class="profile-dot" style="background:<?= htmlspecialchars($p['color_idle']??'#ccc') ?>"></div>
              <div class="profile-dot" style="background:<?= htmlspecialchars($p['color_warn']??'#ccc') ?>"></div>
              <div class="profile-dot" style="background:<?= htmlspecialchars($p['color_done']??'#ccc') ?>"></div>
            </div>
            <div class="profile-info">
              <div class="profile-name"><?= htmlspecialchars($p['name'] ?? 'Profil') ?></div>
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
              <a href="admin.php?tab=profiles&edit=<?= $pid ?>" class="btn btn-outline btn-sm">Bearbeiten</a>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Profil wirklich loschen?')">
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
    <?php endif; ?>
  </div>

</div>
<script>
function syncPicker(val, pickerId) {
  if (/^#[0-9a-fA-F]{6}$/.test(val)) {
    document.getElementById(pickerId).value = val;
  }
}
</script>
</body>
</html>
<?php

function settings_form($d, $p) {
    $d = array_merge([
        'title'=>'Timebox','font'=>'Inter','color_idle'=>'#1ac8a0',
        'color_warn'=>'#e8833a','color_done'=>'#7c3aed','text_color'=>'#ffffff',
        'default_sound'=>'gong','show_presets'=>true,'preset_times'=>[5,15,25,45,60],
        'logo_path'=>''
    ], $d);
    $uid = str_replace('_','',uniqid());
    ob_start(); ?>

  <div class="card">
    <div class="card-title">Branding</div>
    <div class="field">
      <label>App-Titel</label>
      <input type="text" name="<?= $p ?>title" value="<?= htmlspecialchars($d['title']) ?>">
    </div>
    <div class="field">
      <label>Logo</label>
      <input type="file" name="<?= $p ?>logo" accept=".png,.jpg,.jpeg,.gif,.svg,.webp">
      <?php if (!empty($d['logo_path'])): ?>
      <div class="logo-preview" id="lp<?= $uid ?>">
        <img src="<?= htmlspecialchars($d['logo_path']) ?>" alt="Logo">
        <button type="button" class="rm-logo" onclick="document.getElementById('rl<?= $uid ?>').value='1';document.getElementById('lp<?= $uid ?>').remove();">✕ entfernen</button>
      </div>
      <?php endif; ?>
      <input type="hidden" name="<?= $p === 'p_' ? 'remove_profile_logo' : 'remove_logo' ?>" id="rl<?= $uid ?>" value="">
    </div>
  </div>

  <div class="card">
    <div class="card-title">Schrift & Ton</div>
    <div class="field">
      <label>Schriftart</label>
      <select name="<?= $p ?>font">
        <?php foreach (['Inter'=>'Modern (Inter)','Bebas Neue'=>'Block (Bebas Neue)','Space Mono'=>'Mono (Space Mono)','Syne'=>'Syne'] as $v=>$l): ?>
        <option value="<?= $v ?>" <?= $d['font']===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Ton beim Ablauf</label>
      <select name="<?= $p ?>default_sound">
        <?php foreach (['gong'=>'Gong','bell'=>'Indian Bell','bowl'=>'Singing Bowl','off'=>'Kein Ton'] as $v=>$l): ?>
        <option value="<?= $v ?>" <?= $d['default_sound']===$v?'selected':'' ?>><?= $l ?></option>
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
          <input type="radio" name="<?= $p ?>text_color" value="#ffffff" <?= ($d['text_color']==='#ffffff')?'checked':'' ?>>
          <span class="color-dot" style="background:#fff;"></span> Hell (weiss)
        </label>
        <label class="text-mode-label">
          <input type="radio" name="<?= $p ?>text_color" value="#444444" <?= ($d['text_color']==='#444444')?'checked':'' ?>>
          <span class="color-dot" style="background:#444;"></span> Dunkel (grau)
        </label>
      </div>
    </div>
    <div class="three-colors">
      <?php foreach ([
        [$p.'color_idle','ci'.$uid,'Grundfarbe',$d['color_idle']],
        [$p.'color_warn','cw'.$uid,'Letzte Minute',$d['color_warn']],
        [$p.'color_done','cd'.$uid,'Zeit abgelaufen',$d['color_done']],
      ] as [$name,$id,$label,$val]): ?>
      <div class="color-block">
        <label><?= $label ?></label>
        <input type="color" class="color-swatch" id="<?= $id ?>_p" value="<?= $val ?>"
          oninput="document.getElementById('<?= $id ?>').value=this.value">
        <input type="text" class="color-hex" name="<?= $name ?>" id="<?= $id ?>" value="<?= $val ?>"
          oninput="syncPicker(this.value,'<?= $id ?>_p')">
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-title">Timer-Optionen</div>
    <div class="field">
      <label class="check-row">
        <input type="checkbox" name="<?= $p ?>show_presets" <?= $d['show_presets']?'checked':'' ?>>
        Preset-Buttons anzeigen
      </label>
    </div>
    <div class="field">
      <label>Preset-Zeiten (Minuten, kommagetrennt)</label>
      <input type="text" name="<?= $p ?>preset_times" value="<?= implode(',', $d['preset_times']) ?>">
      <p class="hint">z.B. 5,15,25,45,60</p>
    </div>
  </div>

  <?php if ($p === ''): ?>
  <div class="card">
    <div class="card-title">Sicherheit</div>
    <div class="field">
      <label>Admin-Passwort (leer = kein Schutz)</label>
      <input type="password" name="password" value="<?= htmlspecialchars($d['password'] ?? '') ?>" placeholder="Passwort setzen...">
      <p class="hint">Schutzt dieses Admin-Panel.</p>
    </div>
  </div>
  <?php endif; ?>

    <?php return ob_get_clean();
}
