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
        <h1 class="page-title"><i class="fas fa-subway"></i> <?= getTranslation('btn_metro', $lang) ?></h1>
        <p style="color: #555; margin-bottom: 20px; text-align: center;">
            Harta generală a rețelei de metrou din București (Metrorex).
        </p>
        <div class="metro-svg-container" style="width: 100%; height: 60vh; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; position: relative;">
            <!-- Added viewBox calculation logic in JS to handle responsiveness -->
            <svg id="metroSvg" style="width:100%; height:100%;"></svg>
        </div>

        <div class="legend-container" id="metroLegend" style="margin-top: 20px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; background: white; padding: 15px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <!-- Legenda generată live -->
        </div>
    </div>

    <footer class="front-footer">
        <?= getTranslation('footer_text', $lang) ?>
    </footer>

</div>

<script>
    // Live Metro Simulation Logic
    async function loadMetroData() {
        try {
            const res = await fetch('api/metro.php');
            const data = await res.json();
            if (data.success) {
                renderMetroMap(data.lines);
                renderLegend(data.lines);
                startSimulation(data.lines);
            }
        } catch (e) {
            console.error("Failed to load metro data", e);
        }
    }

    function renderMetroMap(lines) {
        const svg = document.getElementById('metroSvg');
        svg.innerHTML = '';

        // Calculate bounds for viewBox to make it responsive
        let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
        lines.forEach(line => {
            if (!line.stations) return;
            line.stations.forEach(st => {
                if (st.x < minX) minX = st.x;
                if (st.y < minY) minY = st.y;
                if (st.x > maxX) maxX = st.x;
                if (st.y > maxY) maxY = st.y;
            });
        });

        if (minX !== Infinity) {
            // Add padding
            const padding = 50;
            const width = Math.max(100, maxX - minX + padding * 2);
            const height = Math.max(100, maxY - minY + padding * 2);
            svg.setAttribute("viewBox", `${minX - padding} ${minY - padding} ${width} ${height}`);
        } else {
             svg.setAttribute("viewBox", `0 0 800 600`); // fallback
        }

        // Render Paths
        lines.forEach(line => {
            if (!line.stations || line.stations.length < 2) return;
            const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
            let d = `M ${line.stations[0].x} ${line.stations[0].y} `;
            for (let i = 1; i < line.stations.length; i++) {
                d += `L ${line.stations[i].x} ${line.stations[i].y} `;
            }
            path.setAttribute("d", d);
            path.setAttribute("stroke", line.color);
            path.setAttribute("stroke-width", "6");
            path.setAttribute("fill", "none");
            path.setAttribute("stroke-linejoin", "round");
            svg.appendChild(path);
        });

        // Render Stations
        lines.forEach(line => {
            if (!line.stations) return;
            line.stations.forEach(st => {
                const group = document.createElementNS("http://www.w3.org/2000/svg", "g");
                const circle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                circle.setAttribute("cx", st.x);
                circle.setAttribute("cy", st.y);
                circle.setAttribute("r", 5);
                circle.setAttribute("fill", "#fff");
                circle.setAttribute("stroke", line.color);
                circle.setAttribute("stroke-width", "3");

                const text = document.createElementNS("http://www.w3.org/2000/svg", "text");
                text.setAttribute("x", parseInt(st.x) + 10);
                text.setAttribute("y", parseInt(st.y) + 4);
                text.textContent = st.name;
                text.setAttribute("font-family", "sans-serif");
                text.setAttribute("font-size", "12px");
                text.setAttribute("font-weight", "bold");
                text.setAttribute("fill", "#333");

                // Dark mode text color adjustment inline hack
                if(document.documentElement.classList.contains('dark-mode')) {
                     text.setAttribute("fill", "#eee");
                }

                group.appendChild(circle);
                group.appendChild(text);
                svg.appendChild(group);
            });
        });
    }

    function renderLegend(lines) {
        const legend = document.getElementById('metroLegend');
        legend.innerHTML = '';
        lines.forEach(line => {
            if(!line.stations || line.stations.length === 0) return;
            const first = line.stations[0].name;
            const last = line.stations[line.stations.length - 1].name;

            const item = document.createElement('div');
            item.style.display = 'flex';
            item.style.alignItems = 'center';
            item.style.gap = '8px';
            item.style.fontWeight = 'bold';

            const safeName = document.createTextNode(line.name).textContent;
            const safeFirst = document.createTextNode(first).textContent;
            const safeLast = document.createTextNode(last).textContent;

            item.innerHTML = `
                <span style="display:inline-block; width:15px; height:15px; border-radius:50%; background:${line.color}"></span>
                ${safeName}: ${safeFirst} — ${safeLast}
            `;
            legend.appendChild(item);
        });
    }

    // Very basic simulation to satisfy "trains moving on lines"
    function startSimulation(lines) {
        const svg = document.getElementById('metroSvg');

        lines.forEach(line => {
            if (!line.stations || line.stations.length < 2) return;

            // Create a train element
            const trainGroup = document.createElementNS("http://www.w3.org/2000/svg", "g");
            const trainBg = document.createElementNS("http://www.w3.org/2000/svg", "rect");
            trainBg.setAttribute("width", "30");
            trainBg.setAttribute("height", "16");
            trainBg.setAttribute("rx", "4");
            trainBg.setAttribute("fill", "#333");
            trainBg.setAttribute("x", "-15");
            trainBg.setAttribute("y", "-8");

            const trainIcon = document.createElementNS("http://www.w3.org/2000/svg", "text");
            trainIcon.textContent = "🚇";
            trainIcon.setAttribute("font-size", "10px");
            trainIcon.setAttribute("x", "-6");
            trainIcon.setAttribute("y", "3");

            trainGroup.appendChild(trainBg);
            trainGroup.appendChild(trainIcon);
            svg.appendChild(trainGroup);

            // Animation logic
            let currentStIdx = 0;
            let direction = 1;
            let progress = 0; // 0 to 1 between stations
            const speed = 0.02;

            function animate() {
                if (!line.stations[currentStIdx + direction]) {
                    direction *= -1; // reverse
                }

                const st1 = line.stations[currentStIdx];
                const st2 = line.stations[currentStIdx + direction];

                progress += speed;
                if (progress >= 1) {
                    progress = 0;
                    currentStIdx += direction;
                } else {
                    const x = parseFloat(st1.x) + (parseFloat(st2.x) - parseFloat(st1.x)) * progress;
                    const y = parseFloat(st1.y) + (parseFloat(st2.y) - parseFloat(st1.y)) * progress;
                    trainGroup.setAttribute("transform", `translate(${x}, ${y})`);
                }

                requestAnimationFrame(animate);
            }

            // Stagger start times
            setTimeout(() => requestAnimationFrame(animate), Math.random() * 2000);
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        loadMetroData();
    });
</script>
</body>
</html>