<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/translations.php';

// Limba
$lang = $_GET['lang'] ?? 'ro';
if (!in_array($lang, ['ro', 'en', 'fr'])) $lang = 'ro';

// Fetch schedules
$db = getDB();
$stmt = $db->query("SELECT * FROM schedules ORDER BY line_name ASC");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Logo pt Header
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
    <title><?= getTranslation('schedules_title', $lang) ?> - <?= getTranslation('app_name', $lang) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
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

        .page-content { max-width: 1000px; margin: 30px auto; padding: 0 20px; flex: 1; width: 100%; box-sizing: border-box; display: flex; flex-direction: column; }
        .page-title { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid var(--primary); padding-bottom: 10px; }

        .schedule-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .schedule-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 4px solid var(--primary); }
        .schedule-card h3 { margin-top: 0; color: var(--primary-dark); font-size: 20px; display: flex; align-items: center; gap: 10px; }
        .schedule-card p { color: #555; line-height: 1.6; white-space: pre-line; margin-bottom: 0; }

        @media (max-width: 768px) {
            .front-header { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body style="display: flex; flex-direction: row; height: 100vh; margin: 0; overflow: hidden; background-color: #f4f7f6;">

    <nav class="left-nav">
        <div class="nav-top">
            <a href="index.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_map', $lang) ?>"><i class="fas fa-map-marker-alt"></i></a>
            <a href="schedules.php?lang=<?= $lang ?>" class="nav-item active" title="<?= getTranslation('btn_schedules', $lang) ?>"><i class="fas fa-clock"></i></a>
            <a href="lines.php?lang=<?= $lang ?>" class="nav-item" title="Orar și Linii Curente"><i class="fas fa-route"></i></a>
            <a href="flights.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_flights', $lang) ?>"><i class="fas fa-plane"></i></a>
            <a href="metro.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_metro', $lang) ?>"><i class="fas fa-subway"></i></a>
            <a href="route.php?lang=<?= $lang ?>" class="nav-item" title="Organizează rută"><i class="fas fa-directions"></i></a>
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
        <h1 class="page-title"><i class="fas fa-list"></i> <?= getTranslation('schedules_title', $lang) ?> (Live STB)</h1>

        <div id="dynamic-schedules" style="text-align:center; padding: 40px;">
            <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--primary)"></i>
            <p style="margin-top:15px; color:#555;"><?= getTranslation('loading', $lang) ?></p>
        </div>

        <div id="schedules-content" style="display:none;">
            <!-- Categoria Tramvaie -->
            <div class="category-header"><i class="fas fa-train-tram" style="color:var(--tram)"></i> TRAMVAIE</div>
            <div class="line-pill-grid" id="grid-tram"></div>

            <!-- Categoria Troleibuze -->
            <div class="category-header"><i class="fas fa-bus-simple" style="color:var(--trolley)"></i> TROLEIBUZE</div>
            <div class="line-pill-grid" id="grid-trolley"></div>

            <!-- Categoria Autobuze -->
            <div class="category-header"><i class="fas fa-bus" style="color:var(--bus)"></i> AUTOBUZE & LINII DE NOAPTE</div>
            <div class="line-pill-grid" id="grid-bus"></div>
        </div>

        <?php if(count($schedules) > 0): ?>
            <h2 style="margin-top: 50px; color:#333; font-size:18px;">Notificări / Linii personalizate adăugate în Admin</h2>
            <div class="schedule-grid" style="margin-top: 15px;">
                <?php foreach($schedules as $s): ?>
                    <div class="schedule-card">
                        <h3><i class="fas fa-bullhorn"></i> <?= htmlspecialchars($s['line_name']) ?></h3>
                        <p><?= htmlspecialchars($s['schedule_details']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="front-footer">
        <?= getTranslation('footer_text', $lang) ?>
    </footer>

    <script>
    document.addEventListener("DOMContentLoaded", async function() {
        try {
            const res = await fetch('api/proxy_routes.php');
            const result = await res.json();

            if (result && result.data) {
                const gridTram = document.getElementById('grid-tram');
                const gridTrolley = document.getElementById('grid-trolley');
                const gridBus = document.getElementById('grid-bus');

                // Sortam alfabetic/numeric
                result.data.sort((a, b) => a.route_short_name.localeCompare(b.route_short_name, undefined, {numeric: true}));

                result.data.forEach(r => {
                    const pill = `<a href="index.php?lang=<?= $lang ?>&search=${r.route_short_name}" class="line-pill" title="${r.route_long_name}">${r.route_short_name}</a>`;

                    if (r.route_type == 0) {
                        gridTram.innerHTML += pill;
                    } else if (r.route_type == 11) {
                        gridTrolley.innerHTML += pill;
                    } else {
                        gridBus.innerHTML += pill;
                    }
                });

                document.getElementById('dynamic-schedules').style.display = 'none';
                document.getElementById('schedules-content').style.display = 'block';
            }
        } catch (e) {
            document.getElementById('dynamic-schedules').innerHTML = '<p style="color:red;">Eroare la încărcarea liniilor STB.</p>';
        }
    });
    </script>
</div>
</body>
</html>