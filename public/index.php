<?php
require_once '../includes/db.php';
require_once '../includes/translations.php';

// Limba
$lang = $_GET['lang'] ?? 'ro';
if (!in_array($lang, ['ro', 'en', 'fr'])) $lang = 'ro';

// Logo
$db = getDB();
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'app_logo'");
$logo_row = $stmt->fetch(PDO::FETCH_ASSOC);
$logo_path = $logo_row ? $logo_row['setting_value'] : '';

// Vreme si Data pt header (PHP variables pt initializare)
$current_date = date('d.m.Y');
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getTranslation('app_name', $lang) ?></title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">
    <style>
        .front-header {
            background-color: var(--primary-dark);
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 2000;
            position: relative;
        }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .header-logo { height: 40px; }
        .header-nav a {
            color: white;
            text-decoration: none;
            margin-right: 15px;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .header-nav a:hover, .header-nav a.active { background-color: rgba(255,255,255,0.2); }
        .header-right { display: flex; align-items: center; gap: 15px; font-size: 14px; }
        .weather-info, .time-info { display: flex; align-items: center; gap: 5px; }

        .lang-selector select {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 5px;
            border-radius: 4px;
            outline: none;
            cursor: pointer;
        }
        .lang-selector select option { color: black; }

        .front-footer {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 13px;
            z-index: 2000;
            position: relative;
        }

        #app-wrapper { display: flex; flex-direction: column; height: 100vh; }
        #app-container { flex: 1; height: calc(100vh - 100px); }

        @media (max-width: 768px) {
            .front-header { flex-direction: column; gap: 10px; }
            .header-nav { display: flex; flex-wrap: wrap; justify-content: center; gap: 5px; margin-top: 10px; }
            .header-nav a { margin: 0; }
        }
    </style>
</head>
<body>

<div id="app-wrapper">
    <header class="front-header">
        <div class="header-left">
            <?php if($logo_path): ?>
                <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" class="header-logo">
            <?php else: ?>
                <h2><i class="fas fa-bus-alt"></i> <?= getTranslation('app_name', $lang) ?></h2>
            <?php endif; ?>
        </div>

        <div class="header-nav">
            <a href="index.php?lang=<?= $lang ?>" class="active"><i class="fas fa-map"></i> <?= getTranslation('btn_map', $lang) ?></a>
            <a href="schedules.php?lang=<?= $lang ?>"><i class="fas fa-clock"></i> <?= getTranslation('btn_schedules', $lang) ?></a>
            <a href="flights.php?lang=<?= $lang ?>"><i class="fas fa-plane"></i> <?= getTranslation('btn_flights', $lang) ?></a>
            <a href="metro.php?lang=<?= $lang ?>"><i class="fas fa-subway"></i> <?= getTranslation('btn_metro', $lang) ?></a>
        </div>

        <div class="header-right">
            <div class="time-info">
                <i class="fas fa-calendar-alt"></i> <?= $current_date ?>
                <i class="fas fa-clock" style="margin-left: 5px;"></i> <span id="live-clock">--:--:--</span>
            </div>
            <div class="weather-info" id="weather-widget">
                <i class="fas fa-cloud-sun"></i> <span><?= getTranslation('loading', $lang) ?></span>
            </div>
            <div class="lang-selector">
                <select onchange="window.location.href=this.value">
                    <option value="?lang=ro" <?= $lang == 'ro' ? 'selected' : '' ?>>🇷🇴 RO</option>
                    <option value="?lang=en" <?= $lang == 'en' ? 'selected' : '' ?>>🇬🇧 EN</option>
                    <option value="?lang=fr" <?= $lang == 'fr' ? 'selected' : '' ?>>🇫🇷 FR</option>
                </select>
            </div>
        </div>
    </header>

    <div id="app-container">
        <!-- Sidebar / Panel principal -->
        <div id="sidebar">
            <div class="sidebar-header">
                <h2><?= getTranslation('app_name', $lang) ?></h2>
                <p><?= getTranslation('subtitle', $lang) ?></p>
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

            <div id="welcome-info">
                <div class="search-box">
                    <input type="text" id="line-search" placeholder="<?= getTranslation('search_line', $lang) ?>">
                    <button><i class="fas fa-search"></i></button>
                </div>
                <div class="instructions">
                    <i class="fas fa-hand-pointer fa-2x"></i>
                    <p><?= getTranslation('click_station', $lang) ?></p>
                </div>
            </div>

            <div id="line-info" class="hidden">
                <div class="line-header-top">
                    <button id="btn-back-line" class="btn-icon"><i class="fas fa-chevron-left"></i></button>
                    <div class="line-badge" id="line-info-badge"><i class="fas fa-train-tram"></i> <span>32</span></div>
                    <span class="agency-name">STB</span>
                    <button class="btn-icon right"><i class="fas fa-share-nodes"></i></button>
                </div>

                <div class="line-direction">
                    <div id="line-direction-text">Piata Unirii &rarr; Depoul Alexandria</div>
                    <a href="#" class="switch-dir"><?= getTranslation('switch_direction', $lang) ?> <i class="fas fa-chevron-down"></i></a>
                    <button class="btn-icon right-white"><i class="fas fa-external-link-alt"></i></button>
                </div>

                <div class="line-info-banner">
                    <i class="fas fa-info-circle"></i> <?= getTranslation('select_station_schedule', $lang) ?>
                </div>

                <div class="timeline-container" id="timeline-list">
                    <!-- Timeline items go here via JS -->
                </div>
            </div>
        </div>

        <!-- Harta -->
        <div id="map" style="position: relative;">
            <div class="map-filters">
                <label><input type="checkbox" id="filter-bus" checked> <i class="fas fa-bus" style="color: var(--bus)"></i> <?= getTranslation('filter_bus', $lang) ?></label>
                <label><input type="checkbox" id="filter-tram" checked> <i class="fas fa-train-tram" style="color: var(--tram)"></i> <?= getTranslation('filter_tram', $lang) ?></label>
                <label><input type="checkbox" id="filter-trolley" checked> <i class="fas fa-bus-simple" style="color: var(--trolley)"></i> <?= getTranslation('filter_trolley', $lang) ?></label>
            </div>
        </div>
    </div>

    <footer class="front-footer">
        <?= getTranslation('footer_text', $lang) ?>
    </footer>
</div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <!-- Pass translations to JS -->
    <script>
        const i18n = {
            loading: "<?= getTranslation('loading', $lang) ?>",
            no_vehicles: "<?= getTranslation('no_vehicles', $lang) ?>",
            estimated_arrival: "<?= getTranslation('estimated_arrival', $lang) ?>",
            min: "<?= getTranslation('min', $lang) ?>",
            next_arrivals: "<?= getTranslation('next_arrivals', $lang) ?>",
            other_arrivals: "<?= getTranslation('other_arrivals', $lang) ?>"
        };
    </script>
    <script src="js/app.js"></script>
    <script>
        // Ceas Live
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('ro-RO');
            document.getElementById('live-clock').textContent = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Vreme Open-Meteo Bucuresti
        async function fetchWeather() {
            try {
                // Lat/Lng Bucuresti: 44.4323, 26.1063
                const res = await fetch('https://api.open-meteo.com/v1/forecast?latitude=44.4323&longitude=26.1063&current_weather=true');
                const data = await res.json();
                if(data.current_weather) {
                    const temp = data.current_weather.temperature;
                    document.querySelector('#weather-widget span').textContent = temp + '°C';
                }
            } catch(e) {
                console.error("Weather error:", e);
                document.querySelector('#weather-widget span').textContent = "--°C";
            }
        }
        fetchWeather();
    </script>
</body>
</html>