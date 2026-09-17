<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/translations.php';

$lang = $_GET['lang'] ?? 'ro';
if (!in_array($lang, ['ro', 'en', 'fr', 'es'])) $lang = 'ro';

$db = getDB();
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'app_logo'");
$logo_row = $stmt->fetch(PDO::FETCH_ASSOC);
$logo_path = $logo_row ? $logo_row['setting_value'] : '';

$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'theme_color'");
$theme_res = $stmt->fetch(PDO::FETCH_ASSOC);
$theme_color = $theme_res ? $theme_res['setting_value'] : 'green';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" data-theme="<?= htmlspecialchars($theme_color) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getTranslation('stb_alerts', $lang) ?> - <?= getTranslation('app_name', $lang) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <style>
        body { display: flex; flex-direction: row; height: 100vh; overflow-y: hidden; background-color: #f4f7f6; }
        #app-wrapper { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .page-content { max-width: 1000px; margin: 30px auto; padding: 0 20px; flex: 1; width: 100%; box-sizing: border-box; }
        .page-title { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid var(--primary); padding-bottom: 10px; }

        .alert-card { background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; border-left: 5px solid #e74c3c; }
        .alert-date { color: #7f8c8d; font-size: 13px; margin-bottom: 5px; }
        .alert-title { font-weight: bold; font-size: 18px; color: #2c3e50; margin-bottom: 10px; }
        .alert-message { font-size: 15px; color: #34495e; line-height: 1.5; }
        .alert-lines { margin-top: 10px; }
        .badge-line { display: inline-block; padding: 4px 8px; border-radius: 4px; color: white; font-weight: bold; margin-right: 5px; font-size: 12px; }

        .loading { text-align: center; padding: 50px; font-size: 18px; color: #7f8c8d; }

        @media (max-width: 768px) {
            .page-content { padding: 0 10px; margin: 15px auto; }
        }
    </style>
</head>
<body class="is-responsive">

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
        <a href="cfr.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('cfr_title', $lang) ?>"><i class="fas fa-train"></i></a>
        <a href="alerts.php?lang=<?= $lang ?>" class="nav-item active" title="<?= getTranslation('stb_alerts', $lang) ?>"><i class="fas fa-bell"></i></a>
        <a href="flights.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_flights', $lang) ?>"><i class="fas fa-plane"></i></a>
        <a href="metro.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_metro', $lang) ?>"><i class="fas fa-subway"></i></a>
        <a href="route.php?lang=<?= $lang ?>" class="nav-item" title="Organizează rută"><i class="fas fa-directions"></i></a>
        <a href="tickets.php?lang=<?= $lang ?>" class="nav-item" title="Cumpără Ticket"><i class="fas fa-ticket-alt"></i></a>
    </div>
</nav>

<div id="app-wrapper">
    <div class="page-content">
        <h1 class="page-title"><i class="fas fa-bell"></i> <?= getTranslation('stb_alerts', $lang) ?></h1>
        <div id="alerts-container">
            <div class="loading"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><?= getTranslation('loading', $lang) ?></div>
        </div>
    </div>
</div>

<script>
    function formatDate(ms) {
        const date = new Date(ms);
        return date.toLocaleString('ro-RO', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }

    async function loadAlerts() {
        try {
            const response = await fetch('api/alerts.php');
            const data = await response.json();

            const container = document.getElementById('alerts-container');

            if (data.error) {
                container.innerHTML = `<div class="alert-card"><p style="color:red; text-align:center;">${data.error}</p></div>`;
                return;
            }

            if (data.notifications && data.notifications.length > 0) {
                let html = '';
                data.notifications.forEach(alert => {
                    let linesHtml = '';
                    if (alert.lines && alert.lines.length > 0) {
                        linesHtml = '<div class="alert-lines">';
                        alert.lines.forEach(line => {
                            linesHtml += `<span class="badge-line" style="background-color: ${line.color || '#333'}">${line.name}</span>`;
                        });
                        linesHtml += '</div>';
                    }

                    html += `
                        <div class="alert-card">
                            <div class="alert-date"><i class="far fa-clock"></i> ${formatDate(alert.created_at)}</div>
                            <div class="alert-title">${alert.title}</div>
                            <div class="alert-message">${alert.message}</div>
                            ${linesHtml}
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div style="text-align:center; padding: 20px;">Nu există alerte active momentan.</div>';
            }
        } catch (error) {
            document.getElementById('alerts-container').innerHTML = '<div class="alert-card"><p style="color:red; text-align:center;">Eroare la preluarea alertelor.</p></div>';
        }
    }

    loadAlerts();
    setInterval(loadAlerts, 60000);
</script>
</body>
</html>
