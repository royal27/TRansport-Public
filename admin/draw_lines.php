<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.php");
    exit;
}

require_once '../includes/db.php';
$db = getDB();

$stmt = $db->query("SELECT * FROM custom_lines ORDER BY name ASC");
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);
$linesJson = json_encode($lines);
?>
<!DOCTYPE html>
<html lang="ro">
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
    <title>Desenează Linii - București Transport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>

    <link rel="stylesheet" href="css/admin_style.css?v=<?= time() ?>">
</head>
<body class="<?= (isset($is_responsive) && $is_responsive) ? 'is-responsive' : '' ?>">

<header class="admin-header">
    <div class="header-left"><button class="menu-toggle" id="menuToggle"><i class="fas fa-bars" style="color:white;"></i></button> <span><i class="fas fa-bus"></i> Admin Panel - Editor Hartă</span></div>
    <div><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['admin_user']) ?></div>
</header>

<div class="wrapper">
    <div class="sidebar" id="sidebar">
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard & Setări</a>
        <a href="schedules.php"><i class="fas fa-clock"></i> Gestiune Orar & Linii</a>
        <a href="create_lines.php"><i class="fas fa-route"></i> Creează Linii</a>
        <a href="draw_lines.php" class="active"><i class="fas fa-draw-polygon"></i> Desenează Linii</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Administrează Utilizatori</a>
        <a href="manage_tickets.php"><i class="fas fa-ticket-alt"></i> Plăți prin SMS</a>
        <a href="backup.php"><i class="fas fa-save"></i> Backup / Restore</a>
        <a href="../public/index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Vezi site-ul</a>
        <a href="index.php?action=logout" style="color: #e74c3c; margin-top: 50px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="toolbar">
            <label><strong>Selectează Linia:</strong></label>
            <select id="lineSelect">
                <option value="">-- Alege o linie --</option>
                <?php foreach($lines as $line): ?>
                    <option value="<?= $line['id'] ?>" data-color="<?= htmlspecialchars($line['color']) ?>">
                        <?= htmlspecialchars($line['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button id="btnSaveRoute" class="btn-save" style="display: none;"><i class="fas fa-save"></i> Salvează Traseul desenat</button>
            <button id="btnLiveRecord" class="btn-save" style="display: none; background-color: #e74c3c; margin-left:10px;"><i class="fas fa-circle"></i> Începe înregistrarea traseului</button>
            <span id="statusMsg" style="color: #27ae60; font-weight: bold; margin-left: 10px;"></span>
        </div>

        <div class="marker-controls" id="markerControls" style="display: none;">
            <span><strong>Adaugă pe hartă (Click pe mod, apoi click pe hartă):</strong></span>
            <button class="marker-btn" data-type="station"><i class="fas fa-map-marker-alt" style="color:#3498db;"></i> Stație</button>
            <button class="marker-btn" data-type="work"><i class="fas fa-hard-hat" style="color:#f39c12;"></i> Drum în lucru</button>
            <button class="marker-btn" data-type="accident"><i class="fas fa-car-crash" style="color:#e74c3c;"></i> Accident</button>
            <button class="marker-btn" data-type="detour"><i class="fas fa-directions" style="color:#9b59b6;"></i> Rută ocolitoare</button>
            <button class="marker-btn" data-type="traffic"><i class="fas fa-traffic-light" style="color:#e67e22;"></i> Aglomerație</button>
            <button class="marker-btn" data-type="police"><i class="fas fa-user-shield" style="color:#2980b9;"></i> Poliție</button>
            <button class="marker-btn" data-type="interventie"><i class="fas fa-ambulance" style="color:#c0392b;"></i> Intervenție STB</button>
            <button class="marker-btn" data-type="suspended"><i class="fas fa-ban" style="color:#000;"></i> Linie suspendată</button>
        </div>

        <div id="map" style="height: 600px; min-height: 600px; width: 100%; display: block; position: relative; z-index: 1;"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script>
    const map = L.map('map').setView([44.4268, 26.1025], 13); // Bucharest center
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap & CARTO',
        maxZoom: 19
    }).addTo(map);

    let currentLineId = null;
    let currentLineColor = '#3388ff';
    let routePolyline = null; // Displayed route from DB
    let drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    let markersLayer = new L.FeatureGroup();
    map.addLayer(markersLayer);

    const drawControl = new L.Control.Draw({
        edit: {
            featureGroup: drawnItems
        },
        draw: {
            polygon: false,
            circle: false,
            rectangle: false,
            marker: false,
            circlemarker: false,
            polyline: {
                shapeOptions: {
                    color: currentLineColor,
                    weight: 4
                }
            }
        }
    });

    const markerIcons = {
        'station': L.divIcon({ html: '<i class="fas fa-map-marker-alt fa-2x" style="color:#3498db"></i>', className: 'custom-icon', iconSize: [30, 30], iconAnchor: [15, 30] }),
        'work': L.divIcon({ html: '<i class="fas fa-hard-hat fa-2x" style="color:#f39c12"></i>', className: 'custom-icon', iconSize: [30, 30], iconAnchor: [15, 30] }),
        'accident': L.divIcon({ html: '<i class="fas fa-car-crash fa-2x" style="color:#e74c3c"></i>', className: 'custom-icon', iconSize: [30, 30], iconAnchor: [15, 30] }),
        'detour': L.divIcon({ html: '<i class="fas fa-directions fa-2x" style="color:#9b59b6"></i>', className: 'custom-icon', iconSize: [30, 30], iconAnchor: [15, 30] }),
        'traffic': L.divIcon({ html: '<i class="fas fa-traffic-light fa-2x" style="color:#e67e22"></i>', className: 'custom-icon', iconSize: [30, 30], iconAnchor: [15, 30] }),
        'police': L.divIcon({ html: '<i class="fas fa-user-shield fa-2x" style="color:#2980b9"></i>', className: 'custom-icon', iconSize: [30, 30], iconAnchor: [15, 30] }),
        'interventie': L.divIcon({ html: '<i class="fas fa-ambulance fa-2x" style="color:#c0392b"></i>', className: 'custom-icon', iconSize: [30, 30], iconAnchor: [15, 30] }),
        'suspended': L.divIcon({ html: '<i class="fas fa-ban fa-2x" style="color:#000"></i>', className: 'custom-icon', iconSize: [30, 30], iconAnchor: [15, 30] })
    };

    let activeMarkerType = null;

    document.getElementById('lineSelect').addEventListener('change', function() {
        currentLineId = this.value;
        if (currentLineId) {
            currentLineColor = this.options[this.selectedIndex].getAttribute('data-color');
            document.getElementById('btnSaveRoute').style.display = 'inline-block';
            document.getElementById('btnLiveRecord').style.display = 'inline-block';
            document.getElementById('markerControls').style.display = 'flex';
            map.addControl(drawControl);
            drawControl.setDrawingOptions({
                polyline: { shapeOptions: { color: currentLineColor, weight: 5, opacity: 0.8 } }
            });
            loadLineData(currentLineId);
        } else {
            document.getElementById('btnSaveRoute').style.display = 'none';
            document.getElementById('btnLiveRecord').style.display = 'none';
            document.getElementById('markerControls').style.display = 'none';
            map.removeControl(drawControl);
            drawnItems.clearLayers();
            markersLayer.clearLayers();
            if(routePolyline) map.removeLayer(routePolyline);

            // Stop recording if active
            if (isRecording) {
                document.getElementById('btnLiveRecord').click();
            }
        }
    });

    function loadLineData(lineId) {
        drawnItems.clearLayers();
        markersLayer.clearLayers();
        if(routePolyline) map.removeLayer(routePolyline);

        // Load Routes
        fetch(`api_draw.php?action=get_routes&line_id=${lineId}`)
            .then(res => res.json())
            .then(data => {
                if (data.length > 0) {
                    const latlngs = data.map(pt => [pt.latitude, pt.longitude]);
                    routePolyline = L.polyline(latlngs, {color: currentLineColor, weight: 5}).addTo(drawnItems);
                    map.fitBounds(routePolyline.getBounds());
                }
            });

        // Load Markers
        fetch(`api_draw.php?action=get_markers&line_id=${lineId}`)
            .then(res => res.json())
            .then(data => {
                data.forEach(m => {
                    addMarkerToMap(m.latitude, m.longitude, m.type, m.description, m.id);
                });
            });
    }

    // Drawing Polyline
    map.on(L.Draw.Event.CREATED, function (e) {
        const type = e.layerType;
        const layer = e.layer;

        if (type === 'polyline') {
            drawnItems.clearLayers(); // Only allow one route per line to be drawn at a time
            layer.options.color = currentLineColor;
            drawnItems.addLayer(layer);
        }
    });

    document.getElementById('btnSaveRoute').addEventListener('click', function() {
        if (!currentLineId) return;

        const layers = drawnItems.getLayers();
        let routes = [];

        // Find polyline
        layers.forEach(l => {
            if (l instanceof L.Polyline) {
                const latlngs = l.getLatLngs();
                routes = latlngs.map(ll => ({lat: ll.lat, lng: ll.lng}));
            }
        });

        if (routes.length === 0) {
            alert('Desenează mai întâi un traseu folosind uneltele din stânga hărții (Polyline).');
            return;
        }

        fetch('api_draw.php?action=save_routes', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ line_id: currentLineId, routes: routes })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) showStatus('Traseu salvat cu succes!');
            else alert('Eroare: ' + data.error);
        });
    });

    // Live GPS Recording
    let isRecording = false;
    let watchId = null;
    let livePolyline = null;
    let liveCoordinates = [];

    const btnLiveRecord = document.getElementById('btnLiveRecord');
    btnLiveRecord.addEventListener('click', function() {
        if (!currentLineId) return;

        if (!isRecording) {
            // Start recording
            if (!navigator.geolocation) {
                alert('Geolocalizarea nu este suportată de browser-ul tău.');
                return;
            }

            isRecording = true;
            this.innerHTML = '<i class="fas fa-square"></i> Oprește înregistrarea traseului';
            this.style.backgroundColor = '#7f8c8d'; // Grey

            // Clear existing drawn polyline to start fresh
            drawnItems.clearLayers();
            if(routePolyline) map.removeLayer(routePolyline);

            liveCoordinates = [];
            livePolyline = L.polyline([], {color: currentLineColor, weight: 5}).addTo(drawnItems);

            showStatus('Înregistrare live pornită...');

            watchId = navigator.geolocation.watchPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const newLatLng = L.latLng(lat, lng);

                    liveCoordinates.push(newLatLng);
                    livePolyline.setLatLngs(liveCoordinates);
                    map.panTo(newLatLng);
                },
                function(error) {
                    console.error('Error getting GPS:', error);
                    showStatus('Eroare semnal GPS: ' + error.message);
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 0,
                    timeout: 5000
                }
            );

        } else {
            // Stop recording
            isRecording = false;
            this.innerHTML = '<i class="fas fa-circle"></i> Începe înregistrarea traseului';
            this.style.backgroundColor = '#e74c3c'; // Red

            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            showStatus('Înregistrare oprită. Nu uita să salvezi traseul!');
        }
    });

    // Custom Marker Placement
    document.querySelectorAll('.marker-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.marker-btn').forEach(b => b.classList.remove('active'));
            if (activeMarkerType === this.dataset.type) {
                activeMarkerType = null; // Toggle off
                map.getContainer().style.cursor = '';
            } else {
                activeMarkerType = this.dataset.type;
                this.classList.add('active');
                map.getContainer().style.cursor = 'crosshair';
            }
        });
    });

    map.on('click', function(e) {
        if (!activeMarkerType || !currentLineId) return;

        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        const desc = prompt("Introdu detalii pentru această locație (ex: 'Stația Victoriei', 'Groapă pe banda 1'):");

        if (desc !== null) {
            fetch('api_draw.php?action=add_marker', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ line_id: currentLineId, lat: lat, lng: lng, type: activeMarkerType, description: desc })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    addMarkerToMap(lat, lng, activeMarkerType, desc, data.id);
                    showStatus('Marker adăugat!');
                }
            });
        }

        // reset tool
        activeMarkerType = null;
        document.querySelectorAll('.marker-btn').forEach(b => b.classList.remove('active'));
        map.getContainer().style.cursor = '';
    });

    function addMarkerToMap(lat, lng, type, desc, id) {
        const marker = L.marker([lat, lng], {icon: markerIcons[type] || markerIcons['station']});

        const popupContent = `
            <div class="custom-popup">
                <h4>Detaliu (ID: ${id})</h4>
                <p>${desc}</p>
                <button onclick="deleteMarker(${id})" style="background:#e74c3c;color:white;border:none;padding:5px;cursor:pointer;border-radius:3px;">Șterge</button>
            </div>
        `;
        marker.bindPopup(popupContent);
        marker.markerId = id;
        markersLayer.addLayer(marker);
    }

    window.deleteMarker = function(id) {
        if(confirm('Ștergi acest marker?')) {
            fetch('api_draw.php?action=delete_marker', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ marker_id: id })
            }).then(() => {
                markersLayer.eachLayer(layer => {
                    if (layer.markerId === id) markersLayer.removeLayer(layer);
                });
                showStatus('Marker șters!');
            });
        }
    };

    function showStatus(msg) {
        const el = document.getElementById('statusMsg');
        el.innerText = msg;
        setTimeout(() => el.innerText = '', 3000);
    }
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var menuToggle = document.getElementById("menuToggle");
    var sidebar = document.getElementById("sidebar");
    if(menuToggle && sidebar) {
        menuToggle.addEventListener("click", function() {
            sidebar.classList.toggle("open");
        });
    }
});
</script>
</body>
</html>
