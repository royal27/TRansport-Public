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
    <script src="js/panzoom.min.js"></script>
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
        .page-content { max-width: 1400px; margin: 30px auto; padding: 0 20px; flex: 1; width: 100%; box-sizing: border-box; display: flex; flex-direction: column; }
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

        .draw-animation {
            stroke-dasharray: 2000;
            stroke-dashoffset: 2000;
            animation: drawPath 3s ease forwards;
        }
        @keyframes drawPath {
            to { stroke-dashoffset: 0; }
        }

        @media (max-width: 768px) {
            .front-header { flex-direction: column; gap: 10px; }
        }

        .timetable-sidebar {
            position: absolute;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100%;
            background: #111;
            color: #fff;
            z-index: 2000;
            transition: right 0.3s ease;
            box-shadow: -5px 0 15px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .timetable-sidebar.open {
            right: 0;
        }
        .timetable-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #222;
            border-bottom: 2px solid #444;
        }
        .timetable-header h2 {
            margin: 0;
            color: #f1c40f;
            font-size: 1.5rem;
            text-transform: uppercase;
        }
        .timetable-close {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .timetable-board {
            padding: 0;
            font-family: 'Courier New', Courier, monospace;
        }
        .timetable-row {
            border-bottom: 1px solid #333;
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .timetable-row.highlight {
            border-left: 5px solid #f1c40f;
        }
        .timetable-direction {
            color: #aaa;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .timetable-main {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }
        .timetable-destination {
            color: #f1c40f;
            font-size: 2rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 70%;
        }
        .timetable-time {
            color: #fff;
            font-size: 2rem;
            font-weight: bold;
        }
        .timetable-footer {
            background: #008080;
            color: #fff;
            padding: 10px;
            text-align: center;
            font-size: 1.1rem;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .timetable-sidebar {
                width: 100%;
                right: -100%;
            }
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
        <?php if (empty(trim($metro_map_html))): ?>

        <div id="timetableSidebar" class="timetable-sidebar">
            <div class="timetable-header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <span id="ttCurrentTime" style="font-size: 2rem; color: #fff;">--:--</span>
                    <h2 id="ttStationName">STAȚIE</h2>
                </div>
                <button class="timetable-close" onclick="closeTimetable()"><i class="fas fa-times"></i></button>
            </div>
            <div class="timetable-board" id="ttBoard">
                <!-- Rows injected here -->
            </div>
            <div class="timetable-footer">
                Stimați călători, atenție la închiderea ușilor.
            </div>
        </div>

        <div class="metro-svg-container" style="width: 100%; flex: 1; min-height: 70vh; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; position: relative;">
            <!-- Added viewBox calculation logic in JS to handle responsiveness -->
            <div id="panzoom-wrapper" style="width: 100%; height: 100%;">
                <svg id="metroSvg" style="width:100%; height:100%; cursor: grab;"></svg>
            </div>

            <div style="position: absolute; bottom: 20px; right: 20px; display: flex; flex-direction: column; gap: 10px; z-index: 1000;">
                <button id="zoomIn" style="width:40px; height:40px; border-radius:50%; border:none; background:var(--primary); color:white; font-size:18px; cursor:pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);"><i class="fas fa-plus"></i></button>
                <button id="zoomOut" style="width:40px; height:40px; border-radius:50%; border:none; background:var(--primary); color:white; font-size:18px; cursor:pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);"><i class="fas fa-minus"></i></button>
                <button id="zoomReset" style="width:40px; height:40px; border-radius:50%; border:none; background:#7f8c8d; color:white; font-size:18px; cursor:pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);"><i class="fas fa-compress"></i></button>
            </div>
        </div>

        <div class="legend-container" id="metroLegend" style="margin-top: 20px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; background: white; padding: 15px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <!-- Legenda generată live -->
        </div>
        <?php else: ?>
            <div class="metro-map-html-container">
                <?= $metro_map_html ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Live Station Modal -->


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
                renderMetroMap(data.lines, data.decorations || []);
                renderLegend(data.lines);
                startSimulation(data.lines);
                if (data.zoom && pz) {
                    setTimeout(() => {
                        pz.zoom(parseFloat(data.zoom), { animate: true });
                    }, 100);
                }
            }
        } catch (e) {
            console.error("Failed to load metro data", e);
        }
    }

    function renderMetroMap(lines, decorations) {
        const svg = document.getElementById('metroSvg');
        if (!svg) return;
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

        // Consider decorations in viewBox calculation
        decorations.forEach(dec => {
            if (dec.x < minX) minX = dec.x;
            if (dec.y < minY) minY = dec.y;
            if (dec.x > maxX) maxX = dec.x;
            if (dec.y > maxY) maxY = dec.y;
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

        // Render Decorations
        decorations.forEach(dec => {
            const group = document.createElementNS("http://www.w3.org/2000/svg", "g");
            let el;

            if (dec.type === 'text') {
                el = document.createElementNS("http://www.w3.org/2000/svg", "text");
                el.textContent = dec.content;
                el.setAttribute("fill", dec.color);
                el.setAttribute("font-size", "14px");
                el.setAttribute("font-weight", dec.font_weight);
                el.setAttribute("x", dec.x);
                el.setAttribute("y", dec.y);
                if(document.documentElement.classList.contains('dark-mode')) {
                     el.setAttribute("fill", "#eee");
                }
            } else if (dec.type === 'image') {
                el = document.createElementNS("http://www.w3.org/2000/svg", "image");
                el.setAttribute("href", dec.content);
                el.setAttribute("x", dec.x);
                el.setAttribute("y", dec.y);
                el.setAttribute("width", dec.width || 100);
                el.setAttribute("height", dec.height || 100);
            } else if (dec.type === 'rect') {
                el = document.createElementNS("http://www.w3.org/2000/svg", "rect");
                el.setAttribute("x", dec.x);
                el.setAttribute("y", dec.y);
                el.setAttribute("width", dec.width);
                el.setAttribute("height", dec.height);
                el.setAttribute("fill", dec.color);
                el.setAttribute("opacity", "0.5");
            } else if (dec.type === 'circle') {
                el = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                el.setAttribute("cx", dec.x);
                el.setAttribute("cy", dec.y);
                el.setAttribute("r", dec.width);
                el.setAttribute("fill", dec.color);
                el.setAttribute("opacity", "0.5");
            } else if (dec.type.startsWith('icon_')) {
                el = document.createElementNS("http://www.w3.org/2000/svg", "text");
                el.setAttribute("class", "fa");
                el.textContent = dec.content;
                el.setAttribute("fill", dec.color);
                el.setAttribute("font-size", "24px");
                el.setAttribute("x", dec.x);
                el.setAttribute("y", dec.y);
                el.style.fontFamily = '"Font Awesome 6 Free"';
                el.style.fontWeight = '900';
            }

            if (el) {
                group.appendChild(el);
                svg.appendChild(group);
            }
        });

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
            if (line.is_dashed == 1) {
                path.setAttribute("stroke-dasharray", "10,10");
            } else {
                path.classList.add("draw-animation");
            }
            svg.appendChild(path);
        });

        // Render Stations
        lines.forEach(line => {
            if (!line.stations) return;
            line.stations.forEach((st, idx) => {
                const group = document.createElementNS("http://www.w3.org/2000/svg", "g");
                group.setAttribute("class", "station-group");
                group.setAttribute("data-line", line.id);
                group.setAttribute("data-idx", idx);
                group.style.cursor = "pointer";

                // Interactivity for "Live Station" logic
                group.onclick = () => showLiveStation(line, idx);

                const circle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                circle.setAttribute("cx", st.x);
                circle.setAttribute("cy", st.y);
                circle.setAttribute("r", 5);
                circle.setAttribute("fill", "#fff");
                circle.setAttribute("stroke", line.color);
                circle.setAttribute("stroke-width", "3");

                if (st.is_waypoint == 1) {
                    circle.style.display = 'none';
                }

                const text = document.createElementNS("http://www.w3.org/2000/svg", "text");
                const ox = st.text_offset_x !== null ? parseInt(st.text_offset_x) : 12;
                const oy = st.text_offset_y !== null ? parseInt(st.text_offset_y) : 4;

                text.setAttribute("x", parseInt(st.x) + ox);
                text.setAttribute("y", parseInt(st.y) + oy);
                text.textContent = st.name;
                text.setAttribute("font-family", "sans-serif");
                text.setAttribute("font-size", "12px");
                text.setAttribute("font-weight", st.font_weight || "bold");
                text.setAttribute("fill", "#333");

                if (st.is_waypoint == 1) {
                    text.style.display = 'none';
                }

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
        if (!legend) return;
        legend.innerHTML = '';
        lines.forEach(line => {
            if(!line.stations || line.stations.length === 0) return;
            // Get actual stations, skipping waypoints for legend
            const realStations = line.stations.filter(s => s.is_waypoint == 0);
            if (realStations.length === 0) return;

            const first = realStations[0].name;
            const last = realStations[realStations.length - 1].name;

            const item = document.createElement('div');
            item.style.display = 'flex';
            item.style.alignItems = 'center';
            item.style.gap = '8px';
            item.style.fontWeight = 'bold';

            const safeName = document.createTextNode(line.name).textContent;
            const safeFirst = document.createTextNode(first).textContent;
            const safeLast = document.createTextNode(last).textContent;

            let html = `<span style="display:inline-block; width:15px; height:15px; border-radius:50%; background:${line.color}"></span>
                        ${safeName}: ${safeFirst} — ${safeLast}`;

            if (line.is_dashed == 1) {
                html += ` <span style="font-size:0.8rem; color:#f39c12;">(în dezvoltare)</span>`;
            }

            item.innerHTML = html;
            legend.appendChild(item);
        });
    }

    // Live Real-Time Engine based on Clock & Intervals
    const TRAIN_TRAVEL_SEC = 120; // 2 minutes between stations
    const TRAIN_STOP_SEC = 30;    // 30 seconds wait at station
    let activeTrains = [];

    function startSimulation(lines) {
        const svg = document.getElementById('metroSvg');
        if (!svg) return;

        lines.forEach(line => {
            if (!line.stations || line.stations.length < 2) return;
            if (line.is_dashed == 1) return; // Do not simulate trains on lines under construction

            const startH = parseInt(line.start_time.split(':')[0]);
            const startM = parseInt(line.start_time.split(':')[1]);
            const endH = parseInt(line.end_time.split(':')[0]);
            const endM = parseInt(line.end_time.split(':')[1]);
            const intervalSec = (parseInt(line.interval_minutes) || 6) * 60;

            const now = new Date();
            const startToday = new Date(now.getFullYear(), now.getMonth(), now.getDate(), startH, startM, 0);
            const endToday = new Date(now.getFullYear(), now.getMonth(), now.getDate(), endH, endM, 0);

            // If outside operating hours, skip generating trains
            if (now < startToday || now > endToday) return;

            const secondsSinceStart = Math.floor((now - startToday) / 1000);

            // Generate active trains for both directions based on interval
            // Total round trip time
            const oneWayTime = (line.stations.length - 1) * (TRAIN_TRAVEL_SEC + TRAIN_STOP_SEC);

            // Generate trains in direction 1 (Tur)
            for(let s = 0; s < secondsSinceStart; s += intervalSec) {
                const trainAge = secondsSinceStart - s;
                if (trainAge < oneWayTime) {
                     spawnTrain(svg, line, 1, trainAge);
                }
            }

            // Generate trains in direction -1 (Retur)
            for(let s = 0; s < secondsSinceStart; s += intervalSec) {
                const trainAge = secondsSinceStart - s;
                if (trainAge < oneWayTime) {
                     spawnTrain(svg, line, -1, trainAge);
                }
            }
        });

        // Start render loop
        requestAnimationFrame(renderEngine);
    }

    function spawnTrain(svg, line, direction, ageSeconds) {
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

        activeTrains.push({
            el: trainGroup,
            line: line,
            direction: direction, // 1 or -1
            age: ageSeconds // how many seconds this train has been running
        });
    }

    let lastTick = performance.now();
    function renderEngine(now) {
        const dt = (now - lastTick) / 1000; // delta time in seconds
        lastTick = now;

        activeTrains.forEach(t => {
            t.age += dt;
            const cycleTime = TRAIN_TRAVEL_SEC + TRAIN_STOP_SEC;

            let stIdx = Math.floor(t.age / cycleTime);
            let timeInCycle = t.age % cycleTime;

            // Handle direction
            let actualStIdx = t.direction === 1 ? stIdx : (t.line.stations.length - 1 - stIdx);
            let nextStIdx = actualStIdx + t.direction;

            // If train reached the end, hide it (or we could loop it, but we let spawnTrain handle frequency)
            if (stIdx >= t.line.stations.length - 1) {
                t.el.style.display = 'none';
                return;
            } else {
                t.el.style.display = 'block';
            }

            const st1 = t.line.stations[actualStIdx];
            const st2 = t.line.stations[nextStIdx];

            if (timeInCycle < TRAIN_TRAVEL_SEC) {
                // Moving
                let progress = timeInCycle / TRAIN_TRAVEL_SEC;
                const x = parseFloat(st1.x) + (parseFloat(st2.x) - parseFloat(st1.x)) * progress;
                const y = parseFloat(st1.y) + (parseFloat(st2.y) - parseFloat(st1.y)) * progress;
                t.el.setAttribute("transform", `translate(${x}, ${y})`);
            } else {
                // Stopped at next station
                t.el.setAttribute("transform", `translate(${st2.x}, ${st2.y})`);
            }
        });

        requestAnimationFrame(renderEngine);
    }

    function showLiveStation(line, stIdx) {
        if (line.is_dashed == 1) return; // No live info for under construction lines
        const st = line.stations[stIdx];
        if (st.is_waypoint == 1) return; // Don't show live info for invisible waypoints

        const modal = document.getElementById('liveStationModal');
        const stNameEl = document.getElementById('lsName');
        const contentEl = document.getElementById('lsContent');

        const stName = st.name;
        stNameEl.innerHTML = `<span style="display:inline-block; width:15px; height:15px; border-radius:50%; background:${line.color}; margin-right:8px;"></span> ${document.createTextNode(stName).textContent}`;

        let html = '';
        const now = new Date();
        const startH = parseInt(line.start_time.split(':')[0]);
        const startM = parseInt(line.start_time.split(':')[1]);
        const intervalSec = (parseInt(line.interval_minutes) || 6) * 60;

        const startToday = new Date(now.getFullYear(), now.getMonth(), now.getDate(), startH, startM, 0);
        const secondsSinceStart = Math.floor((now - startToday) / 1000);

        const cycleTime = TRAIN_TRAVEL_SEC + TRAIN_STOP_SEC;

        function getNextArrivals(direction, stationIndex, count = 3) {
            const timeToReachStation = stationIndex * cycleTime;
            let arrivals = [];

            // Find upcoming departures that will reach this station
            for (let s = 0; s < secondsSinceStart + (24 * 3600); s += intervalSec) {
                const arrival = s + timeToReachStation;
                if (arrival > secondsSinceStart) {
                    const waitSeconds = arrival - secondsSinceStart;
                    arrivals.push(Math.ceil(waitSeconds / 60));
                    if (arrivals.length >= count) break;
                }
            }

            return arrivals;
        }

        // Tur (Direction 1) -> To last station
        if (stIdx < line.stations.length - 1) {
            const dirName = line.stations[line.stations.length - 1].name;
            const etas = getNextArrivals(1, stIdx);
            const etaHtml = etas.length > 0
                ? etas.map((e, i) => `<span style="${i===0 ? 'font-size:1.5rem; color:#27ae60;' : 'font-size:1.1rem; color:#7f8c8d; margin-left:10px;'}"><i class="fas fa-clock"></i> ${e} min</span>`).join('')
                : '<span style="font-size:1.5rem; color:#777;">--</span>';

            html += `<div style="margin-bottom:15px; padding:10px; background:#f8f9fa; border-radius:8px; border-left:4px solid ${line.color};">
                <div style="font-size:0.85rem; color:#777; text-transform:uppercase; font-weight:bold;">Direcția</div>
                <div style="font-size:1.1rem; font-weight:bold; margin-bottom:5px;">${document.createTextNode(dirName).textContent}</div>
                <div style="font-weight:bold; display:flex; align-items:baseline;">${etaHtml}</div>
            </div>`;
        }

        // Retur (Direction -1) -> To first station
        if (stIdx > 0) {
            const dirName = line.stations[0].name;
            // For retur, distance is from end of line backwards
            const stIdxFromEnd = line.stations.length - 1 - stIdx;
            const etas = getNextArrivals(-1, stIdxFromEnd);
            const etaHtml = etas.length > 0
                ? etas.map((e, i) => `<span style="${i===0 ? 'font-size:1.5rem; color:#27ae60;' : 'font-size:1.1rem; color:#7f8c8d; margin-left:10px;'}"><i class="fas fa-clock"></i> ${e} min</span>`).join('')
                : '<span style="font-size:1.5rem; color:#777;">--</span>';

            html += `<div style="padding:10px; background:#f8f9fa; border-radius:8px; border-left:4px solid ${line.color};">
                <div style="font-size:0.85rem; color:#777; text-transform:uppercase; font-weight:bold;">Direcția</div>
                <div style="font-size:1.1rem; font-weight:bold; margin-bottom:5px;">${document.createTextNode(dirName).textContent}</div>
                <div style="font-weight:bold; display:flex; align-items:baseline;">${etaHtml}</div>
            </div>`;
        }

        contentEl.innerHTML = html;
        modal.style.display = 'flex';
    }

    let pz;
    document.addEventListener("DOMContentLoaded", () => {
        loadMetroData();

        const svgEl = document.getElementById('metroSvg');
        const wrapper = document.getElementById('panzoom-wrapper');
        if (svgEl && wrapper && typeof Panzoom !== 'undefined') {
            pz = Panzoom(svgEl, {
                maxScale: 10,
                minScale: 0.1,
                canvas: true,
                cursor: 'grab'
            });
            wrapper.addEventListener('wheel', pz.zoomWithWheel);

            document.getElementById('zoomIn')?.addEventListener('click', pz.zoomIn);
            document.getElementById('zoomOut')?.addEventListener('click', pz.zoomOut);
            document.getElementById('zoomReset')?.addEventListener('click', () => pz.reset());

            svgEl.addEventListener('panzoomstart', () => { svgEl.style.cursor = 'grabbing'; });
            svgEl.addEventListener('panzoomend', () => { svgEl.style.cursor = 'grab'; });
        }
    });
</script>
</body>
</html>