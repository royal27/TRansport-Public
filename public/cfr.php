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
    $is_responsive = true;
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
    <title><?= getTranslation('cfr_title', $lang) ?> - <?= getTranslation('app_name', $lang) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <style>
        body { display: flex; flex-direction: row; height: 100vh; overflow-y: hidden; background-color: #f4f7f6; }

        #app-wrapper { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }

        .front-footer { background-color: #2c3e50; color: white; text-align: center; padding: 10px; margin-top: auto; font-size: 13px; }

        .page-content { max-width: 1000px; margin: 30px auto; padding: 0 20px; flex: 1; width: 100%; box-sizing: border-box; display: flex; flex-direction: column; }
        .page-title { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid var(--primary); padding-bottom: 10px; }

        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; background: #ddd; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; color: #333; }
        .tab-btn.active { background: var(--primary); color: white; }

        table.trains-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .trains-table th { background-color: #1a252f; color: #f1c40f; padding: 18px 15px; text-align: left; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        .trains-table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .trains-table tr:last-child td { border-bottom: none; }
        .trains-table tr:hover { background-color: #f4f6f9; }

        .train-cell { display: flex; align-items: center; gap: 10px; font-weight: bold; font-size: 16px; color: #2c3e50; }
        .train-type { font-size: 12px; color: white; background: #e74c3c; padding: 2px 6px; border-radius: 4px; display: inline-block; }

        .destination-cell { font-weight: 600; font-size: 15px; color: #34495e; }
        .time-cell { font-weight: bold; font-size: 18px; color: #2c3e50; }

        .status-cell { font-weight: bold; }
        .status-ontime { color: #27ae60; }
        .status-delayed { color: #e74c3c; }

        .platform-cell { font-weight: bold; font-size: 18px; text-align: center; color: #d35400; background: #fdf2e9; border-radius: 4px; padding: 5px; }

        #departures-section, #arrivals-section { display: none; }
        #departures-section.active, #arrivals-section.active { display: block; }

        .loading { text-align: center; padding: 50px; font-size: 18px; color: #7f8c8d; }

        @media (max-width: 768px) {
            .trains-table th, .trains-table td { padding: 5px; font-size: 12px; }
            .train-cell { font-size: 14px; }
            .time-cell, .platform-cell { font-size: 15px; }
            .tab-btn { flex: 1; text-align: center; }
            .page-content { padding: 0 5px; margin: 10px auto; overflow-x: hidden; width: 100%; box-sizing: border-box; }
            .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .trains-table { width: 100%; min-width: 500px; table-layout: fixed; }
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
            <a href="cfr.php?lang=<?= $lang ?>" class="nav-item active" title="<?= getTranslation('cfr_title', $lang) ?>"><i class="fas fa-train"></i></a>
            <a href="alerts.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('stb_alerts', $lang) ?>"><i class="fas fa-bell"></i></a>
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
    <div class="page-content">
        <h2 class="page-title"><i class="fa-solid fa-train"></i> <?= getTranslation('cfr_title', $lang) ?> - Gara de Nord</h2>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('departures')"><?= getTranslation('departures', $lang) ?></button>
            <button class="tab-btn" onclick="switchTab('arrivals')"><?= getTranslation('arrivals', $lang) ?></button>
        </div>

        <div id="departures-section" class="active">
            <div id="departures-container" class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> <?= getTranslation('loading', $lang) ?>...</div>
        </div>

        <div id="arrivals-section">
            <div id="arrivals-container" class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> <?= getTranslation('loading', $lang) ?>...</div>
        </div>
    </div>

    <footer class="front-footer">
        <?= getTranslation('footer_text', $lang) ?>
    </footer>
</div>

<script>
    const lang = '<?= $lang ?>';

    // Tabs
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('#departures-section, #arrivals-section').forEach(sec => sec.classList.remove('active'));

        event.target.classList.add('active');
        document.getElementById(tab + '-section').classList.add('active');
    }

    // Fetch and render data
    function loadCFRData() {
        fetch('api/cfr.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    renderTable('departures-container', data.data.departures, '<?= getTranslation('destination', $lang) ?>');
                    renderTable('arrivals-container', data.data.arrivals, '<?= getTranslation('origin', $lang) ?>');
                } else {
                    document.getElementById('departures-container').innerHTML = `<div class="error">${data.message}</div>`;
                    document.getElementById('arrivals-container').innerHTML = `<div class="error">${data.message}</div>`;
                }
            })
            .catch(error => {
                document.getElementById('departures-container').innerHTML = `<div class="error">Eroare la încărcarea datelor.</div>`;
                document.getElementById('arrivals-container').innerHTML = `<div class="error">Eroare la încărcarea datelor.</div>`;
            });
    }

    function renderTable(containerId, trains, routeHeader) {
        if (!trains || trains.length === 0) {
            document.getElementById(containerId).innerHTML = `<div style="text-align:center; padding: 20px;">Nu există trenuri programate.</div>`;
            return;
        }

        let html = `
            <div class="table-responsive">
            <table class="trains-table">
                <thead>
                    <tr>
                        <th>Tren</th>
                        <th>${routeHeader}</th>
                        <th>Ora</th>
                        <th>Status</th>
                        <th>Linia</th>
                    </tr>
                </thead>
                <tbody>
        `;

        trains.forEach(train => {
            let statusClass = 'status-ontime';
            if (train.status.toLowerCase().includes('întârziat') || train.status.toLowerCase().includes('delay')) {
                statusClass = 'status-delayed';
            }

            html += `
                <tr>
                    <td>
                        <div class="train-cell">
                            <span class="train-type">${train.type}</span>
                            ${train.number}
                        </div>
                    </td>
                    <td class="destination-cell">${train.route}</td>
                    <td class="time-cell">${train.time}</td>
                    <td class="status-cell ${statusClass}">${train.status}</td>
                    <td class="platform-cell">${train.platform !== '' ? train.platform : '-'}</td>
                </tr>
            `;
        });

        html += `</tbody></table></div>`;
        document.getElementById(containerId).innerHTML = html;
    }

    // Load data on page load
    loadCFRData();
    // Refresh data every 60 seconds
    setInterval(loadCFRData, 60000);
</script>
</body>
</html>
