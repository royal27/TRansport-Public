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
    <title>Organizează rută - <?= htmlspecialchars($app_name) ?></title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Mobile overrides handled in style.css for body and wrapper */
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
            padding-bottom: 20px;
        }

        .route-header {
            background-color: var(--primary-color, #2ecc71);
            color: white;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        .route-header h2 { margin: 0; font-size: 20px; }

        /* Custom UI for Routing similar to the provided image */
        .custom-routing-ui {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .input-group {
            display: flex;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 10px;
            background: white;
        }

        .input-group i {
            margin-right: 10px;
            color: #4285F4; /* Blue for start */
        }

        .input-group.end i {
            color: #EA4335; /* Red for end */
        }

        .input-group label {
            font-size: 14px;
            color: #666;
            margin-right: 10px;
            min-width: 45px;
        }

        .input-group input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 15px;
        }

        .input-group .icon-action {
            color: #555;
            cursor: pointer;
            padding: 5px;
        }
        .input-group .icon-action:hover {
            color: #000;
        }

        .advanced-options {
            color: #1a73e8;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            margin-top: 10px;
        }
        .advanced-options:hover {
            text-decoration: underline;
        }

        .btn-search-route {
            background-color: #e0e0e0;
            color: #888;
            border: none;
            padding: 12px;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 15px;
            cursor: not-allowed;
            text-align: center;
            transition: 0.3s;
        }

        .btn-search-route.active {
            background-color: var(--primary-color, #2ecc71);
            color: white;
            cursor: pointer;
        }
        .btn-search-route.active:hover {
            opacity: 0.9;
        }

        /* Hide Leaflet Routing Machine default UI */
        .leaflet-routing-container {
            display: none !important;
        }

        .footer-branding {
            margin-top: auto;
            text-align: center;
            padding: 15px;
            font-size: 13px;
            color: #777;
            border-top: 1px solid #eee;
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
            <a href="metro.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_metro', $lang) ?>"><i class="fas fa-subway"></i></a>
            <a href="route.php?lang=<?= $lang ?>" class="nav-item active" title="Organizează rută"><i class="fas fa-directions"></i></a>
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

        <div id="route-sidebar">
            <div class="route-header">
                <h2><i class="fas fa-directions"></i> Organizează Rută</h2>
                <p style="margin: 5px 0 0; font-size: 13px; opacity: 0.9;">Alege punctul de plecare și destinația</p>
            </div>

            <div class="custom-routing-ui">
                <div class="input-group">
                    <i class="fas fa-map-marker-alt"></i>
                    <label>De la</label>
                    <input type="text" id="start-input" placeholder="Punctul de plecare...">
                    <i class="fas fa-search icon-action" id="start-search"></i>
                </div>

                <div class="input-group end">
                    <i class="fas fa-flag"></i>
                    <label>Până la</label>
                    <input type="text" id="end-input" placeholder="Punctul de sosire...">
                    <i class="fas fa-arrows-alt-v icon-action" id="swap-points"></i>
                </div>

                <a href="#" class="advanced-options">Afișează opțiunile avansate</a>

                <button type="button" class="btn-search-route" id="btn-search">CAUTĂ CELE MAI BUNE RUTE</button>
            </div>

            <div id="routing-ui-container"></div>

            <div class="footer-branding">
                București Transport Live v1.0.0<br>
                by Admin
            </div>
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

            // Bind our custom UI to the Leaflet Routing geocoder and waypoints

            const startInput = document.getElementById('start-input');
            const endInput = document.getElementById('end-input');
            const btnSearch = document.getElementById('btn-search');
            const geocoder = L.Control.Geocoder.nominatim();

            function checkInputs() {
                if (startInput.value.trim() !== '' && endInput.value.trim() !== '') {
                    btnSearch.classList.add('active');
                } else {
                    btnSearch.classList.remove('active');
                }
            }

            startInput.addEventListener('input', checkInputs);
            endInput.addEventListener('input', checkInputs);

            document.getElementById('swap-points').addEventListener('click', function() {
                let temp = startInput.value;
                startInput.value = endInput.value;
                endInput.value = temp;
                checkInputs();
            });

            btnSearch.addEventListener('click', function() {
                if (!btnSearch.classList.contains('active')) return;

                let startStr = startInput.value;
                let endStr = endInput.value;

                // Geocode start
                geocoder.geocode(startStr, function(resultsStart) {
                    if (resultsStart.length > 0) {
                        let wpStart = resultsStart[0].center;

                        // Geocode end
                        geocoder.geocode(endStr, function(resultsEnd) {
                            if (resultsEnd.length > 0) {
                                let wpEnd = resultsEnd[0].center;
                                routingControl.setWaypoints([
                                    L.latLng(wpStart.lat, wpStart.lng),
                                    L.latLng(wpEnd.lat, wpEnd.lng)
                                ]);

                                // After searching, show the actual routing instructions inside our container
                                container.style.display = 'block';
                            }
                        });
                    }
                });
            });

            // Muta UI-ul de routing în sidebar-ul nostru pt un design mai curat
            var container = routingControl.getContainer();
            // Ascunde containerul initial, il vom afisa dupa ce userul apasa butonul nostru de cautare
            container.style.display = 'none';
            document.getElementById('routing-ui-container').appendChild(container);

        });
    </script>
</body>
</html>
