<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    die();
}

$id = (int)($_GET['id'] ?? 0);
$db = getDB();
$stmt = $db->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->execute([$id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    die("Linie inexistenta.");
}

// Logo pt Header
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'app_logo'");
$logo_row = $stmt->fetch(PDO::FETCH_ASSOC);
$logo_path = $logo_row ? $logo_row['setting_value'] : '';

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestiune Stații: <?= htmlspecialchars($schedule['line_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/admin_style.css?v=<?= time() ?>">
    <style>
        #map { height: 500px; width: 100%; border-radius: 8px; margin-bottom: 20px; cursor: crosshair; }
        .flex-container { display: flex; gap: 20px; flex-wrap: wrap; }
        .map-section { flex: 2; min-width: 300px; }
        .stations-section { flex: 1; min-width: 300px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .direction-toggle { display: flex; margin-bottom: 15px; }
        .direction-btn { flex: 1; padding: 10px; border: 1px solid #ccc; background: #eee; cursor: pointer; text-align: center; font-weight: bold; }
        .direction-btn.active { background: #3498db; color: white; border-color: #3498db; }
        .station-item { display: flex; justify-content: space-between; padding: 8px; border-bottom: 1px solid #eee; align-items: center; }
        .station-item button { background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
        .instructions { background: #e8f4fd; border-left: 4px solid #3498db; padding: 10px; margin-bottom: 15px; }
    </style>
</head>
<body>

<header class="admin-header">
    <div class="header-left">
        <a href="schedules.php" style="color:white; margin-right: 15px;"><i class="fas fa-arrow-left"></i> Înapoi</a>
        <span><i class="fas fa-route"></i> <?= htmlspecialchars($schedule['line_name']) ?> (<?= htmlspecialchars($schedule['category']) ?>)</span>
    </div>
</header>

<div style="padding: 20px;">
    <div class="instructions">
        <strong>Instrucțiuni:</strong>
        1. Selectează sensul (Dus / Întors) din panoul din dreapta.<br>
        2. Dă click pe hartă pentru a plasa stațiile în ordinea corectă a traseului.<br>
        3. Traseul se va desena automat între stații.<br>
        4. Nu uita să apeși pe <strong>"Salvează Toate Stațiile"</strong> la final!
    </div>

    <div class="flex-container">
        <div class="map-section">
            <div id="map"></div>
        </div>

        <div class="stations-section">
            <div class="direction-toggle">
                <div class="direction-btn active" id="btn-dus" onclick="setDirection('dus')">Dus</div>
                <div class="direction-btn" id="btn-intors" onclick="setDirection('intors')">Întors</div>
            </div>

            <ul id="stations-list" style="list-style:none; padding:0; margin-bottom: 20px; max-height: 400px; overflow-y:auto;">
                <!-- Stations will be listed here -->
            </ul>

            <button onclick="saveStations()" class="btn-edit" style="width:100%; padding: 12px; font-size:16px;"><i class="fas fa-save"></i> Salvează Toate Stațiile</button>
            <p id="status" style="margin-top: 10px; color: green; font-weight: bold;"></p>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const scheduleId = <?= $id ?>;
    let currentDirection = 'dus';
    let stations = [];

    const map = L.map('map').setView([44.4268, 26.1025], 13);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO'
    }).addTo(map);

    let dusPolyline = L.polyline([], {color: '#3498db', weight: 4}).addTo(map);
    let intorsPolyline = L.polyline([], {color: '#e74c3c', weight: 4, dashArray: '5, 10'}).addTo(map);
    let markersLayer = L.layerGroup().addTo(map);

    function setDirection(dir) {
        currentDirection = dir;
        document.getElementById('btn-dus').classList.toggle('active', dir === 'dus');
        document.getElementById('btn-intors').classList.toggle('active', dir === 'intors');
        renderList();
    }

    map.on('click', function(e) {
        const name = prompt("Introdu numele stației (ex: Piața Victoriei):");
        if (name) {
            stations.push({
                name: name,
                direction: currentDirection,
                lat: e.latlng.lat,
                lng: e.latlng.lng
            });
            renderList();
        }
    });

    function deleteStation(index) {
        stations.splice(index, 1);
        renderList();
    }

    function renderList() {
        const list = document.getElementById('stations-list');
        list.innerHTML = '';
        markersLayer.clearLayers();

        let dusCoords = [];
        let intorsCoords = [];

        stations.forEach((st, idx) => {
            if (st.direction === currentDirection) {
                const li = document.createElement('li');
                li.className = 'station-item';
                li.innerHTML = `<span><i class="fas fa-map-marker-alt" style="color:${currentDirection==='dus'?'#3498db':'#e74c3c'}"></i> ${st.name}</span>
                                <button onclick="deleteStation(${idx})"><i class="fas fa-times"></i></button>`;
                list.appendChild(li);
            }

            const color = st.direction === 'dus' ? '#3498db' : '#e74c3c';
            const marker = L.circleMarker([st.lat, st.lng], {
                radius: 6,
                fillColor: color,
                color: "#fff",
                weight: 1,
                opacity: 1,
                fillOpacity: 0.8
            }).bindTooltip(st.name, {permanent: false, direction: 'top'});

            markersLayer.addLayer(marker);

            if (st.direction === 'dus') dusCoords.push([st.lat, st.lng]);
            else intorsCoords.push([st.lat, st.lng]);
        });

        dusPolyline.setLatLngs(dusCoords);
        intorsPolyline.setLatLngs(intorsCoords);
    }

    // Load existing
    fetch(`api_schedules.php?action=get_stations&schedule_id=${scheduleId}`)
        .then(res => res.json())
        .then(data => {
            if(data.success && data.stations) {
                stations = data.stations.map(s => ({
                    name: s.name,
                    direction: s.direction,
                    lat: parseFloat(s.latitude),
                    lng: parseFloat(s.longitude)
                }));
                renderList();

                if(stations.length > 0) {
                    const bounds = L.latLngBounds(stations.map(s => [s.lat, s.lng]));
                    map.fitBounds(bounds);
                }
            }
        });

    function saveStations() {
        document.getElementById('status').innerText = 'Se salvează...';
        fetch('api_schedules.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'save_stations',
                schedule_id: scheduleId,
                stations: stations
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('status').innerText = 'Stațiile și traseul au fost salvate cu succes!';
                setTimeout(() => document.getElementById('status').innerText = '', 3000);
            } else {
                alert('Eroare: ' + data.error);
            }
        });
    }
</script>
</body>
</html>
