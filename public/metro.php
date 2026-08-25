<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/translations.php';

// Limba
$lang = $_GET['lang'] ?? 'ro';
if (!in_array($lang, ['ro', 'en', 'fr'])) $lang = 'ro';

// Logo pt Header
$db = getDB();
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'app_logo'");
$logo_row = $stmt->fetch(PDO::FETCH_ASSOC);
$logo_path = $logo_row ? $logo_row['setting_value'] : '';

// Metro Map HTML Setting
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'metro_map_html'");
$metro_map_html_row = $stmt->fetch(PDO::FETCH_ASSOC);
$metro_map_html = $metro_map_html_row ? $metro_map_html_row['setting_value'] : '';

$current_date = date('d.m.Y');
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
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
    <title><?= getTranslation('btn_metro', $lang) ?> - <?= getTranslation('app_name', $lang) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <style>
        body { display: flex; flex-direction: row; height: 100vh; overflow-y: hidden; background-color: #f4f7f6; }
        .front-header {
            background-color: var(--primary-dark);
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .header-logo { height: 40px; }
        .header-nav a {
            color: white; text-decoration: none; margin-right: 15px; font-weight: 500; padding: 5px 10px; border-radius: 4px; transition: background 0.2s;
        }
        .header-nav a:hover, .header-nav a.active { background-color: rgba(255,255,255,0.2); }
        .header-right { display: flex; align-items: center; gap: 15px; font-size: 14px; }

        .front-footer { background-color: #2c3e50; color: white; text-align: center; padding: 10px; margin-top: auto; font-size: 13px; }

        #app-wrapper { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .page-content { max-width: 1000px; margin: 30px auto; padding: 0 20px; flex: 1; width: 100%; box-sizing: border-box; display: flex; flex-direction: column; }
        .page-title { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid var(--primary); padding-bottom: 10px; text-align: center; width: 100%; }

        .metro-map-img { margin: 0 auto; display: block;
            max-width: 90%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border: 4px solid white;
        }

        .metro-map-html-container {
            width: 100%;
            text-align: center;
        }
        .metro-map-html-container iframe {
            max-width: 100%;
        }

        @media (max-width: 768px) {
            .front-header { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body class="<?= (isset($is_responsive) && $is_responsive) ? 'is-responsive' : '' ?>">

    <nav class="left-nav">
        <div class="nav-top">
            <a href="index.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_map', $lang) ?>"><i class="fas fa-map-marker-alt"></i></a>
            <a href="schedules.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_schedules', $lang) ?>"><i class="fas fa-clock"></i></a>
            <a href="lines.php?lang=<?= $lang ?>" class="nav-item" title="Orar și Linii Curente"><i class="fas fa-route"></i></a>
            <a href="flights.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_flights', $lang) ?>"><i class="fas fa-plane"></i></a>
            <a href="metro.php?lang=<?= $lang ?>" class="nav-item active" title="<?= getTranslation('btn_metro', $lang) ?>"><i class="fas fa-subway"></i></a>
            <a href="route.php?lang=<?= $lang ?>" class="nav-item" title="Organizează rută"><i class="fas fa-directions"></i></a>
            <a href="tickets.php?lang=<?= $lang ?>" class="nav-item" title="Cumpără Ticket"><i class="fas fa-ticket-alt"></i></a>
        </div>
        <div class="nav-bottom">
            <div class="lang-selector-nav">
                <a href="?lang=ro" class="<?= $lang=='ro'?'active':'' ?>">RO</a>
                <a href="?lang=en" class="<?= $lang=='en'?'active':'' ?>">EN</a>
                <a href="?lang=fr" class="<?= $lang=='fr'?'active':'' ?>">FR</a>
            </div>
            <a href="account.php?lang=<?= $lang ?>" class="nav-item" title="Contul meu"><i class="fas fa-user-circle"></i></a>
            <a href="/admin/index.php" class="nav-item" title="Admin"><i class="fas fa-cog"></i></a>
        </div>
    </nav>

    <div id="app-wrapper">
    <div class="page-content">
        <h1 class="page-title"><i class="fas fa-subway"></i> <?= getTranslation('btn_metro', $lang) ?></h1>
        <p style="color: #555; margin-bottom: 20px; text-align: center;">
            Harta generală a rețelei de metrou din București (Metrorex).
        </p>
        <?php if (!empty(trim($metro_map_html))): ?>
            <div class="metro-map-html-container">
                <?= $metro_map_html ?>
            </div>
        <?php else: ?>
            <!-- Harta oficială Metrorex / Harta generică actualizată -->
            <img src="img/metro_map.png" alt="Metrorex Map" class="metro-map-img">
        <?php endif; ?>
    </div>

    <footer class="front-footer">
        <?= getTranslation('footer_text', $lang) ?>
    </footer>

</div>
</body>
</html>