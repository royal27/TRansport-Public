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

$current_date = date('d.m.Y');
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getTranslation('flights_title', $lang) ?> - <?= getTranslation('app_name', $lang) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { display: flex; flex-direction: column; min-height: 100vh; overflow-y: auto; background-color: #f4f7f6; }
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

        .page-content { max-width: 1000px; margin: 30px auto; padding: 0 20px; flex: 1; width: 100%; box-sizing: border-box; }
        .page-title { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid var(--primary); padding-bottom: 10px; }

        table.flights-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .flights-table th { background-color: #34495e; color: white; padding: 15px; text-align: left; }
        .flights-table td { padding: 15px; border-bottom: 1px solid #eee; }
        .flights-table tr:last-child td { border-bottom: none; }
        .flights-table tr:hover { background-color: #f9f9f9; }

        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-OnTime { background-color: #d4edda; color: #155724; }
        .status-Delayed { background-color: #f8d7da; color: #721c24; }
        .status-Boarding { background-color: #cce5ff; color: #004085; }
        .status-Scheduled { background-color: #e2e3e5; color: #383d41; }

        .loading-div { text-align: center; padding: 50px; color: #7f8c8d; }

        @media (max-width: 768px) {
            .front-header { flex-direction: column; gap: 10px; }
            .flights-table th, .flights-table td { padding: 10px; font-size: 14px; }
        }
    </style>
</head>
<body>

    <header class="front-header">
        <div class="header-left">
            <?php if($logo_path): ?>
                <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" class="header-logo">
            <?php else: ?>
                <h2><i class="fas fa-bus-alt"></i> <?= getTranslation('app_name', $lang) ?></h2>
            <?php endif; ?>
        </div>

        <div class="header-nav">
            <a href="index.php?lang=<?= $lang ?>"><i class="fas fa-map"></i> <?= getTranslation('btn_map', $lang) ?></a>
            <a href="schedules.php?lang=<?= $lang ?>"><i class="fas fa-clock"></i> <?= getTranslation('btn_schedules', $lang) ?></a>
            <a href="flights.php?lang=<?= $lang ?>" class="active"><i class="fas fa-plane"></i> <?= getTranslation('btn_flights', $lang) ?></a>
            <a href="metro.php?lang=<?= $lang ?>"><i class="fas fa-subway"></i> <?= getTranslation('btn_metro', $lang) ?></a>
        </div>

        <div class="header-right">
            <div class="time-info"><i class="fas fa-calendar-alt"></i> <?= $current_date ?></div>
            <div class="lang-selector">
                <a href="?lang=ro" style="color: white; text-decoration: <?= $lang=='ro'?'underline':'none' ?>">RO</a> |
                <a href="?lang=en" style="color: white; text-decoration: <?= $lang=='en'?'underline':'none' ?>">EN</a> |
                <a href="?lang=fr" style="color: white; text-decoration: <?= $lang=='fr'?'underline':'none' ?>">FR</a>
            </div>
        </div>
    </header>

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
                        const statusClass = 'status-' + flight.status.replace(' ', '');
                        html += `
                            <tr>
                                <td><strong>${flight.flight_number}</strong></td>
                                <td>${flight.destination}</td>
                                <td>${flight.departure_time}</td>
                                <td><span class="status-badge ${statusClass}">${flight.status}</span></td>
                            </tr>
                        `;
                    });

                    html += `</tbody></table>`;
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
</body>
</html>