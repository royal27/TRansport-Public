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

$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'theme_color'");
$theme_res = $stmt->fetch(PDO::FETCH_ASSOC);
$theme_color = $theme_res ? $theme_res['setting_value'] : 'green';

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
    <title><?= getTranslation('flights_title', $lang) ?> - <?= getTranslation('app_name', $lang) ?></title>
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

        #app-wrapper { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }

        .front-footer { background-color: #2c3e50; color: white; text-align: center; padding: 10px; margin-top: auto; font-size: 13px; }

        .page-content { max-width: 1000px; margin: 30px auto; padding: 0 20px; flex: 1; width: 100%; box-sizing: border-box; display: flex; flex-direction: column; }
        .page-title { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid var(--primary); padding-bottom: 10px; }

        table.flights-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .flights-table th { background-color: #1a252f; color: #f1c40f; padding: 18px 15px; text-align: left; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        .flights-table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .flights-table tr:last-child td { border-bottom: none; }
        .flights-table tr:hover { background-color: #f4f6f9; }

        .airline-cell { display: flex; align-items: center; gap: 15px; }
        .airline-logo { width: 60px; height: 30px; object-fit: contain; }
        .flight-number { font-weight: 700; font-size: 16px; color: #2c3e50; }
        .airline-name { font-size: 12px; color: #7f8c8d; display: block; }

        .destination-cell { font-weight: 600; font-size: 15px; color: #34495e; }
        .time-cell { font-weight: bold; font-size: 18px; color: #2c3e50; }

        .status-badge { padding: 6px 12px; border-radius: 4px; font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; min-width: 90px; text-align: center; }
        .status-OnTime { background-color: #2ecc71; color: white; }
        .status-Delayed { background-color: #e74c3c; color: white; animation: blink 2s infinite; }
        .status-Boarding { background-color: #f39c12; color: white; }
        .status-FinalCall { background-color: #d35400; color: white; animation: blink 1s infinite; }
        .status-Scheduled { background-color: #95a5a6; color: white; }
        .status-Departed { background-color: #34495e; color: white; }

        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }

        .loading-div { text-align: center; padding: 50px; color: #7f8c8d; }

        @media (max-width: 768px) {
            .front-header { flex-direction: column; gap: 10px; }
            .flights-table th, .flights-table td { padding: 5px; font-size: 12px; }
            .page-content { padding: 0 5px; margin: 10px auto; overflow-x: hidden; width: 100%; box-sizing: border-box; }
            .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .flights-table { width: 100%; min-width: 500px; table-layout: fixed; }
        }
    </style>
</head>
<body class="<?= (isset($is_responsive) && $is_responsive) ? 'is-responsive' : '' ?>">

    <nav class="left-nav">
        <?php if (!empty($logo_path)): ?>
            <div class="sidebar-logo-container" style="text-align: center; padding: 10px 0;">
                <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" style="max-height: 50px; max-width: 100%;">
            </div>
        <?php endif; ?>
        <div class="nav-top">
            <a href="index.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_map', $lang) ?>"><i class="fas fa-map-marker-alt"></i></a>
            <a href="schedules.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_schedules', $lang) ?>"><i class="fas fa-clock"></i></a>
            <a href="lines.php?lang=<?= $lang ?>" class="nav-item" title="Orar și Linii Curente"><i class="fas fa-route"></i></a>
            <a href="flights.php?lang=<?= $lang ?>" class="nav-item active" title="<?= getTranslation('btn_flights', $lang) ?>"><i class="fas fa-plane"></i></a>
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
    <div class="page-content">
        <h1 class="page-title"><i class="fas fa-plane-departure"></i> <?= getTranslation('flights_title', $lang) ?></h1>

        <div id="flights-container">
            <div class="loading-div"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><?= getTranslation('loading', $lang) ?></div>
        </div>
    </div>

    <footer class="front-footer">
        <?= getTranslation('footer_text', $lang) ?>
    </footer>

    <script>
        async function loadFlights() {
            try {
                const response = await fetch('api/flights.php');
                const result = await response.json();

                const container = document.getElementById('flights-container');

                if (result.status === 'success') {
                    let html = `
                        <div class="table-responsive">
                        <table class="flights-table">
                            <thead>
                                <tr>
                                    <th><?= getTranslation('flight_number', $lang) ?></th>
                                    <th><?= getTranslation('destination', $lang) ?></th>
                                    <th><i class="far fa-clock"></i> <?= getTranslation('departure_time', $lang) ?></th>
                                    <th><?= getTranslation('status', $lang) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    result.data.forEach(flight => {
                        const statusClass = 'status-' + flight.status.replace(/ /g, '');
                        html += `
                            <tr>
                                <td>
                                    <div class="airline-cell">
                                        <img src="${flight.airline_logo}" class="airline-logo" alt="${flight.airline_name}">
                                        <div>
                                            <span class="flight-number">${flight.flight_number}</span>
                                            <span class="airline-name">${flight.airline_name}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="destination-cell">${flight.destination}</td>
                                <td class="time-cell">${flight.departure_time}</td>
                                <td><span class="status-badge ${statusClass}">${flight.status}</span></td>
                            </tr>
                        `;
                    });

                    html += `</tbody></table></div>`;
                    container.innerHTML = html;
                }
            } catch (error) {
                document.getElementById('flights-container').innerHTML = '<p style="color:red; text-align:center;">Eroare la încărcarea zborurilor.</p>';
            }
        }

        loadFlights();
        // Reload la 60 secunde
        setInterval(loadFlights, 60000);
    </script>
</div>
</body>
</html>