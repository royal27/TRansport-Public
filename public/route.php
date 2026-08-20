<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/translations.php';

$lang = $_GET['lang'] ?? 'ro';
if (!in_array($lang, ['ro', 'en', 'fr'])) $lang = 'ro';

// Fetch settings
$db = getDB();
$stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('theme_color', 'app_name')");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$theme_color = $settings['theme_color'] ?? 'green';
$app_name = $settings['app_name'] ?? 'București Transport Live';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" data-theme="<?= htmlspecialchars($theme_color) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizează rută - <?= htmlspecialchars($app_name) ?></title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">
    <style>
        body { display: flex; flex-direction: row; height: 100vh; margin: 0; overflow: hidden; background-color: #f4f7f6; }
        #map-container { flex: 1; height: 100vh; position: relative; }
        #map { width: 100%; height: 100%; z-index: 1; }

        /* Sidebar styling for route page */
        #route-sidebar {
            width: 350px;
            background: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .route-header {
            background-color: var(--primary);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .route-header h2 { margin: 0; font-size: 20px; }

        /* Adjust Leaflet Routing Machine UI */
        .leaflet-routing-container {
            width: 100% !important;
            max-width: 100% !important;
            background-color: transparent !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
            color: #333 !important;
        }
        .leaflet-routing-alternatives-container {
            padding: 10px;
        }
        .leaflet-routing-geocoders {
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }
        .leaflet-routing-geocoder {
            margin-bottom: 10px;
        }
        .leaflet-routing-geocoder input {
            width: calc(100% - 30px);
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        /* We will move the routing container into the sidebar */
    </style>
</head>
<body>

    <nav class="left-nav">
        <div class="nav-top">
            <a href="index.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_map', $lang) ?>"><i class="fas fa-map-marker-alt"></i></a>
            <a href="schedules.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_schedules', $lang) ?>"><i class="fas fa-clock"></i></a>
            <a href="lines.php?lang=<?= $lang ?>" class="nav-item" title="Orar și Linii Curente"><i class="fas fa-route"></i></a>
            <a href="flights.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_flights', $lang) ?>"><i class="fas fa-plane"></i></a>
            <a href="metro.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_metro', $lang) ?>"><i class="fas fa-subway"></i></a>
            <a href="route.php?lang=<?= $lang ?>" class="nav-item active" title="Organizează rută"><i class="fas fa-directions"></i></a>
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

    <div id="app-wrapper" style="display: flex; flex: 1; flex-direction: row; height: 100%;">

        <div id="route-sidebar">
            <div class="route-header">
                <h2><i class="fas fa-directions"></i> Organizează Rută</h2>
                <p style="margin: 5px 0 0; font-size: 13px; opacity: 0.9;">Alege punctul de plecare și destinația</p>
            </div>
            <div id="routing-ui-container"></div>
        </div>

        <div id="map-container">
            <div id="map"></div>
        </div>

    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Inițializare hartă pe București
            var map = L.map('map', {
                zoomControl: false
            }).setView([44.4268, 26.1025], 13);

            L.control.zoom({ position: 'topright' }).addTo(map);

            // Layer harta
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(map);

            // Routing control
            var routingControl = L.Routing.control({
                waypoints: [
                    null,
                    null
                ],
                routeWhileDragging: true,
                geocoder: L.Control.Geocoder.nominatim(),
                language: 'ro', // Română
                showAlternatives: true,
                fitSelectedRoutes: true,
                lineOptions: {
                    styles: [{color: 'var(--primary, #27ae60)', opacity: 0.8, weight: 6}]
                }
            }).addTo(map);

            // Muta UI-ul de routing în sidebar-ul nostru pt un design mai curat
            var container = routingControl.getContainer();
            // Ascunde containerul initial pana se incarca, pt o tranzitie fluida
            container.style.display = 'none';
            document.getElementById('routing-ui-container').appendChild(container);

            setTimeout(() => {
                container.style.display = 'block';
            }, 500);
        });
    </script>
</body>
</html>
