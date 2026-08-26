<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/translations.php';

// Limba
$lang = $_GET['lang'] ?? 'ro';
if (!in_array($lang, ['ro', 'en', 'fr'])) $lang = 'ro';

// Logo
$db = getDB();
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'app_logo'");
$logo_row = $stmt->fetch(PDO::FETCH_ASSOC);
$logo_path = $logo_row ? $logo_row['setting_value'] : '';

// Get theme and announcement settings
$stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('theme_color', 'announcement_text', 'announcement_speed', 'app_name', 'app_version', 'app_author', 'weather_api_key')");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$theme_color = $settings['theme_color'] ?? 'green';
$announcement_text = $settings['announcement_text'] ?? '';
$announcement_speed = $settings['announcement_speed'] ?? '15';
$app_name = $settings['app_name'] ?? 'București Transport Live';
$app_version = $settings['app_version'] ?? '1.0.0';
$app_author = $settings['app_author'] ?? 'Admin';
$weather_api_key = $settings['weather_api_key'] ?? '';

// Vreme si Data pt header (PHP variables pt initializare)
$current_date = date('d.m.Y');
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" data-theme="<?= htmlspecialchars($theme_color) ?>">
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
    <title><?= htmlspecialchars($app_name) ?></title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">

</head>
<body class="<?= (isset($is_responsive) && $is_responsive) ? 'is-responsive' : '' ?>">

<!-- Theme toggle moved back to floating for cleaner UI -->
<div id="top-header" class="floating-header">
    <button id="theme-toggle" class="theme-toggle-btn" title="Toggle Light/Dark Mode">
        <i class="fas fa-lightbulb"></i>
    </button>
</div>

<nav class="left-nav">
                <div class="nav-top">
            <a href="index.php?lang=<?= $lang ?>" class="nav-item active" title="<?= getTranslation('btn_map', $lang) ?>"><i class="fas fa-map-marker-alt"></i></a>
            <a href="schedules.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_schedules', $lang) ?>"><i class="fas fa-clock"></i></a>
            <a href="lines.php?lang=<?= $lang ?>" class="nav-item" title="Orar și Linii Curente"><i class="fas fa-route"></i></a>
            <a href="flights.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_flights', $lang) ?>"><i class="fas fa-plane"></i></a>
            <a href="metro.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_metro', $lang) ?>"><i class="fas fa-subway"></i></a>
            <a href="route.php?lang=<?= $lang ?>" class="nav-item" title="Organizează rută"><i class="fas fa-directions"></i></a>
            <a href="tickets.php?lang=<?= $lang ?>" class="nav-item" title="Cumpără Ticket"><i class="fas fa-ticket-alt"></i></a>
        </div>
                <div class="nav-bottom">
            <div class="lang-selector-nav">
                <a href="?lang=ro" class="<?= $lang=='ro'?'active':'' ?>">🇷🇴</a>
                <a href="?lang=en" class="<?= $lang=='en'?'active':'' ?>">🇬🇧</a>
                <a href="?lang=fr" class="<?= $lang=='fr'?'active':'' ?>">🇫🇷</a>
                <a href="?lang=es" class="<?= $lang=='es'?'active':'' ?>">🇪🇸</a>
            </div>
            <a href="account.php?lang=<?= $lang ?>" class="nav-item" title="Contul meu"><i class="fas fa-user-circle"></i></a>
            <a href="/admin/index.php" class="nav-item" title="Admin"><i class="fas fa-cog"></i></a>
        </div>
    </nav>

<div id="app-wrapper">

    <!-- Adjacent white panel -->
    <div id="sidebar">
        <!-- 3-column grid for line categories -->
        <div class="transport-categories">
            <button class="cat-btn bus-btn active" data-type="bus"><i class="fas fa-bus"></i></button>
            <button class="cat-btn tram-btn active" data-type="tram"><i class="fas fa-train-tram"></i></button>
            <button class="cat-btn trolley-btn active" data-type="trolley"><i class="fas fa-bus-simple"></i></button>
        </div>

        <div class="search-box-container">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="line-search" placeholder="<?= getTranslation('search_line', $lang) ?>">
                <button id="search-btn" class="hidden">Search</button>
            </div>
        </div>

        <div id="welcome-info">
            <div class="instructions">
                <i class="fas fa-mouse-pointer fa-2x"></i>
                <p><?= getTranslation('click_station', $lang) ?></p>
            </div>
        </div>

        <div id="station-info" class="hidden">
            <div class="station-header">
                <button id="btn-back" class="btn-icon"><i class="fas fa-arrow-left"></i></button>
                <h3 id="station-name"><?= getTranslation('station_name', $lang) ?></h3>
            </div>
            <div id="arrivals-list">
                <div class="loading"><?= getTranslation('loading', $lang) ?></div>
            </div>
        </div>

        <div id="line-info" class="hidden">
            <div class="line-header-top">
                <button id="btn-back-line" class="btn-icon"><i class="fas fa-arrow-left"></i></button>
                <div class="line-badge" id="line-info-badge"></div>
                <span class="agency-name">STB</span>
            </div>

            <div class="timeline-container" id="timeline-list">
                <!-- Timeline items go here via JS -->
            </div>
        </div>

        <!-- Sidebar Footer App Info -->
        <div id="sidebar-footer" class="app-info-footer">
            <strong><?= htmlspecialchars($app_name) ?></strong> v<?= htmlspecialchars($app_version) ?><br>
            <small>by <?= htmlspecialchars($app_author) ?></small>
        </div>
    </div>

    <!-- Harta -->
    <div id="map-container">
        <div id="map"></div>

        <!-- Floating bottom panel showing route directions -->
        <div id="bottom-panel" class="hidden">
            <div class="bottom-panel-content">
                <div class="bp-left">
                    <span id="bp-line-badge" class="line-badge"></span>
                    <div class="bp-direction">
                        <span id="bp-direction-text">Direction</span>
                    </div>
                </div>
                <div>
                    <button id="bp-live-track" class="btn-icon-circular" style="background:#e74c3c; color:white; margin-right: 10px;" title="Live Route Tracking"><i class="fas fa-crosshairs"></i></button>
                    <button id="bp-switch-dir" class="btn-icon-circular"><i class="fas fa-exchange-alt"></i></button>
                </div>
            </div>
        </div>

        <!-- Modern Bottom Bar -->
        <div id="modern-bottom-bar">
            <div class="mbb-widgets">
                <div class="mbb-badge"><i class="fas fa-map-marker-alt"></i> București</div>
                <div class="mbb-badge"><a href="https://www.google.com/search?q=vremea+bucuresti" target="_blank" style="color: inherit; text-decoration: none;"><i class="fas fa-cloud-sun"></i> Vremea București</a></div>
                <div class="mbb-badge"><i class="far fa-calendar-alt"></i> <?= $current_date ?></div>
            </div>
            <?php if(!empty($announcement_text)): ?>
            <div class="mbb-ticker-container">
                <div class="mbb-ticker-label"><i class="fas fa-bolt"></i> INFO</div>
                <div class="mbb-ticker-wrap">
                    <div class="mbb-ticker-text" style="animation-duration: <?= (int)$announcement_speed ?>s;"><?= htmlspecialchars($announcement_text) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <!-- Pass translations to JS -->
    <script>
        const weatherApiKey = "<?= htmlspecialchars($weather_api_key) ?>";
        const i18n = {
            loading: "<?= getTranslation('loading', $lang) ?>",
            no_vehicles: "<?= getTranslation('no_vehicles', $lang) ?>",
            estimated_arrival: "<?= getTranslation('estimated_arrival', $lang) ?>",
            min: "<?= getTranslation('min', $lang) ?>",
            next_arrivals: "<?= getTranslation('next_arrivals', $lang) ?>",
            other_arrivals: "<?= getTranslation('other_arrivals', $lang) ?>"
        };
    </script>
    <script src="js/app.js?v=<?= time() ?>"></script>
</body>
</html>