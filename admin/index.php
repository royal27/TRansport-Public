<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/translations.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$success = '';

// Preia setarile curente
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$logo_path = $settings['app_logo'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_settings') {
        $api_key = $_POST['tpbi_api_key'] ?? '';
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('tpbi_api_key', ?)");
        $stmt->execute([$api_key]);

        $theme_color = $_POST['theme_color'] ?? 'blue';
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('theme_color', ?)");
        $stmt->execute([$theme_color]);

        $announcement_text = $_POST['announcement_text'] ?? '';
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('announcement_text', ?)");
        $stmt->execute([$announcement_text]);

        $announcement_speed = $_POST['announcement_speed'] ?? '15';
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('announcement_speed', ?)");
        $stmt->execute([$announcement_speed]);

        $app_name = $_POST['app_name'] ?? '';
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('app_name', ?)");
        $stmt->execute([$app_name]);

        $app_version = $_POST['app_version'] ?? '';
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('app_version', ?)");
        $stmt->execute([$app_version]);

        $app_author = $_POST['app_author'] ?? '';
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('app_author', ?)");
        $stmt->execute([$app_author]);

        $weather_api_key = $_POST['weather_api_key'] ?? '';
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('weather_api_key', ?)");
        $stmt->execute([$weather_api_key]);

        $metro_map_html = $_POST['metro_map_html'] ?? '';
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('metro_map_html', ?)");
        $stmt->execute([$metro_map_html]);

        $responsive_mode = isset($_POST['responsive_mode']) ? '1' : '0';
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('responsive_mode', ?)");
        $stmt->execute([$responsive_mode]);

        // Refresh local settings array
        $settings['tpbi_api_key'] = $api_key;
        $settings['theme_color'] = $theme_color;
        $settings['announcement_text'] = $announcement_text;
        $settings['announcement_speed'] = $announcement_speed;
        $settings['app_name'] = $app_name;
        $settings['app_version'] = $app_version;
        $settings['app_author'] = $app_author;
        $settings['weather_api_key'] = $weather_api_key;
        $settings['metro_map_html'] = $metro_map_html;
        $settings['responsive_mode'] = $responsive_mode;




        $success = "Setările generale au fost salvate.";
    } elseif ($_POST['action'] == 'upload_splash') {
        if (isset($_FILES['splash_file']) && $_FILES['splash_file']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm'];
            $filename = $_FILES['splash_file']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $new_filename = 'splash_' . time() . '.' . $ext;
                $upload_path = __DIR__ . '/../public/uploads/' . $new_filename;

                if (!is_dir(__DIR__ . '/../public/uploads')) {
                    mkdir(__DIR__ . '/../public/uploads', 0777, true);
                }

                if (move_uploaded_file($_FILES['splash_file']['tmp_name'], $upload_path)) {
                    $splash_url = 'uploads/' . $new_filename;
                    $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('splash_screen_media', ?)");
                    $stmt->execute([$splash_url]);
                    $settings['splash_screen_media'] = $splash_url;
                    $success = "Splash screen a fost încărcat cu succes.";
                } else {
                    $error = "Eroare la mutarea fișierului de splash.";
                }
            } else {
                $error = "Extensie nepermisă pentru splash screen.";
            }
        }
    } elseif ($_POST['action'] == 'remove_splash') {
        $stmt = $db->prepare("DELETE FROM settings WHERE setting_key = 'splash_screen_media'");
        $stmt->execute();
        $settings['splash_screen_media'] = '';
        $success = "Splash screen a fost șters.";
    } elseif ($_POST['action'] == 'upload_logo') {
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == 0) {

            // Verificare tip MIME suportata pe orice server (inclusiv shared hosting)
            $image_info = getimagesize($_FILES['logo_file']['tmp_name']);
            $mime = $image_info !== false ? $image_info['mime'] : '';

            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
            $file_ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

            if ($image_info !== false && in_array($mime, $allowed_mimes) && in_array($file_ext, $allowed_exts)) {
                $new_filename = 'logo_' . time() . '.' . $file_ext;

                $upload_dir = '../public/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_path)) {
                    $logo_url = 'uploads/' . $new_filename;
                    $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('app_logo', ?)");
                    $stmt->execute([$logo_url]);
                    $logo_path = $logo_url;
                    $success = "Logo a fost încărcat cu succes.";
                } else {
                    $success = "Eroare la mutarea fișierului.";
                }
            } else {
                $success = "Format invalid. Doar JPG, PNG, GIF.";
            }
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <?php
    // Get responsive mode setting
    $is_responsive = true; // Default
    if (isset($db)) {
        try {
            $resp_stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'responsive_mode'");
            $resp_row = $resp_stmt->fetch(PDO::FETCH_ASSOC);
            if ($resp_row && $resp_row['setting_value'] === '0') {
                $is_responsive = false;
            }
        } catch(Exception $e) { }
    }
    if ($is_responsive): ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php endif; ?>
    <title>Dashboard - București Transport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/admin_style.css?v=<?= time() ?>">
</head>
<body class="<?= (isset($is_responsive) && $is_responsive) ? 'is-responsive' : '' ?>">

<header class="admin-header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars" style="color:white;"></i></button>
        <?php if($logo_path): ?>
            <img src="../public/<?= htmlspecialchars($logo_path) ?>" alt="Logo" style="height: 40px; vertical-align: middle;">
        <?php else: ?>
            <i class="fas fa-bus"></i> Admin Panel
        <?php endif; ?>
    </div>
    <div>
        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['admin_user']) ?>
    </div>
</header>

<div class="wrapper">
    <div class="sidebar" id="sidebar">
        <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard & Setări</a>
        <a href="schedules.php"><i class="fas fa-clock"></i> Gestiune Orar & Linii</a>
        <a href="create_lines.php"><i class="fas fa-route"></i> Creează Linii</a>
        <a href="draw_lines.php"><i class="fas fa-draw-polygon"></i> Desenează Linii</a>
        <a href="metro_editor.php"><i class="fas fa-subway"></i> Desenează Harta Metrou</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Administrează Utilizatori</a>
        <a href="manage_tickets.php"><i class="fas fa-ticket-alt"></i> Plăți prin SMS</a>
        <a href="backup.php"><i class="fas fa-save"></i> Backup / Restore</a>
        <a href="../public/index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Vezi site-ul</a>
        <a href="?action=logout" style="color: #e74c3c; margin-top: 50px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h1>Dashboard</h1>
        </div>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="card">
            <hr style="margin: 20px 0; border: 1px solid #ddd;">

            <h2>Intro / Splash Screen</h2>
            <p style="color: #7f8c8d; font-size: 14px;">Încarcă un video scurt (mp4) sau o poză (gif/png) ce va fi afișat ca Intro timp de 5 secunde la prima vizitare a site-ului.</p>
            <?php if (!empty($settings['splash_screen_media'])): ?>
                <div style="margin-top: 10px; margin-bottom: 20px;">
                    <p><strong>Splash curent:</strong></p>
                    <?php if(in_array(strtolower(pathinfo($settings['splash_screen_media'], PATHINFO_EXTENSION)), ['mp4','webm'])): ?>
                        <video src="../public/<?= htmlspecialchars($settings['splash_screen_media']) ?>" style="max-height: 100px;" autoplay muted loop></video>
                    <?php else: ?>
                        <img src="../public/<?= htmlspecialchars($settings['splash_screen_media']) ?>" style="max-height: 100px;">
                    <?php endif; ?>
                    <form method="POST" action="" style="margin-top:10px;">
                        <input type="hidden" name="action" value="remove_splash">
                        <button type="submit" class="btn" style="background:#e74c3c;">Șterge Splash Screen</button>
                    </form>
                </div>
            <?php endif; ?>
            <form method="POST" action="" enctype="multipart/form-data" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="upload_splash">
                <div class="form-group">
                    <label>Alege fișier Splash (MP4, GIF, PNG, JPG)</label>
                    <input type="file" name="splash_file" accept=".png, .jpg, .jpeg, .gif, .mp4, .webm" required>
                </div>
                <button type="submit" class="btn">Încarcă Splash</button>
            </form>

            <hr style="margin: 20px 0; border: 1px solid #ddd;">

            <h2>Logo Site</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_logo">
                <div class="form-group">
                    <label>Încarcă imagine logo (PNG/JPG):</label>
                    <input type="file" name="logo_file" required>
                </div>
                <?php if ($logo_path): ?>
                    <p>Logo curent:</p>
                    <img src="../public/<?= htmlspecialchars($logo_path) ?>" class="logo-preview">
                <?php endif; ?>
                <br><br>
                <button type="submit">Salvează Logo</button>
            </form>
        </div>

        <div class="card">
            <h2>Setări Generale Site</h2>
            <p style="color: #7f8c8d; font-size: 14px;">Configurează API-ul, tema și anunțurile publice.</p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="save_settings">
                <div class="form-group">
                    <label>Cheie API mo-bi.ro (Opțional pt MVP local)</label>
                    <input type="text" name="tpbi_api_key" value="<?= htmlspecialchars($settings['tpbi_api_key'] ?? '') ?>" placeholder="ex: 12345-abcde...">
                </div>

                <div class="form-group">
                    <label>Culoare Temă</label>
                    <select name="theme_color" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; max-width: 500px;">
                        <option value="blue" <?= (isset($settings['theme_color']) && $settings['theme_color'] === 'blue') ? 'selected' : '' ?>>Albastru (Blue)</option>
                        <option value="green" <?= (!isset($settings['theme_color']) || $settings['theme_color'] === 'green') ? 'selected' : '' ?>>Verde (Green) - Implicit</option>
                        <option value="red" <?= (isset($settings['theme_color']) && $settings['theme_color'] === 'red') ? 'selected' : '' ?>>Roșu (Red)</option>
                        <option value="live-color" <?= (isset($settings['theme_color']) && $settings['theme_color'] === 'live-color') ? 'selected' : '' ?>>Live Color (Din Logo)</option>
                        <option value="romania" <?= (isset($settings['theme_color']) && $settings['theme_color'] === 'romania') ? 'selected' : '' ?>>ROMANIA (Animat)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Text Anunț (Afișat sus ca Live Text)</label>
                    <textarea name="announcement_text" rows="3" placeholder="Ex: Întârzieri pe linia 41 astăzi."><?= htmlspecialchars($settings['announcement_text'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Viteză Anunț (în secunde - ex: 15 pentru normal, 30 pentru lent)</label>
                    <input type="number" name="announcement_speed" value="<?= htmlspecialchars($settings['announcement_speed'] ?? '15') ?>" min="1" max="100">
                </div>

                <div class="form-group">
                    <label>Numele Aplicației</label>
                    <input type="text" name="app_name" value="<?= htmlspecialchars($settings['app_name'] ?? 'București Transport Live') ?>" placeholder="Ex: Transport Live">
                </div>

                <div class="form-group">
                    <label>Versiunea Aplicației</label>
                    <input type="text" name="app_version" value="<?= htmlspecialchars($settings['app_version'] ?? '1.0.0') ?>" placeholder="Ex: 1.0.0">
                </div>

                <div class="form-group">
                    <label>Autorul Aplicației</label>
                    <input type="text" name="app_author" value="<?= htmlspecialchars($settings['app_author'] ?? 'Admin') ?>" placeholder="Ex: John Doe">
                </div>

                <div class="form-group">
                    <label>Cheie API OpenWeatherMap (Pentru Vremea Live)</label>
                    <input type="text" name="weather_api_key" value="<?= htmlspecialchars($settings['weather_api_key'] ?? '') ?>" placeholder="ex: 12345abcde...">
                </div>





                <div class="form-group">
                    <label>Cod HTML pentru Harta Metrou (Opțional - înlocuiește imaginea standard dacă este setat)</label>
                    <textarea name="metro_map_html" rows="5" placeholder="Exemplu: <iframe src='...'></iframe>"><?= htmlspecialchars($settings['metro_map_html'] ?? '') ?></textarea>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                    <label style="margin:0;">Mod Responsiv Mobil & Tabletă (Opțional)</label>
                    <label class="switch">
                        <input type="checkbox" name="responsive_mode" value="1" <?= (isset($settings['responsive_mode']) && $settings['responsive_mode'] == '1') ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                    <small style="color: #666; font-size: 0.85rem;">Dacă este activat, site-ul se va restrânge pe mobil. Dacă este debifat, se va vedea ca pe desktop, doar mai mic.</small>
                </div>

                <button type="submit" style="margin-top:20px;">Salvează Setările</button>
            </form>
        </div>
    </div>
</div>

<footer class="admin-footer">
    CopyRight Transport 2026 By Stoian rudolf
</footer>


<script>
document.addEventListener("DOMContentLoaded", function() {
    var menuToggle = document.getElementById("menuToggle");
    var sidebar = document.getElementById("sidebar");
    if(menuToggle && sidebar) {
        menuToggle.addEventListener("click", function() {
            sidebar.classList.toggle("open");
        });
    }
});
</script>
</body>
</html>
