<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.php");
    die();
}

require_once '../includes/db.php';
$db = getDB();

$is_responsive = true;
try {
    $resp_stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'responsive_mode'");
    $resp_row = $resp_stmt->fetch(PDO::FETCH_ASSOC);
    if ($resp_row && $resp_row['setting_value'] === '0') {
        $is_responsive = false;
    }
} catch(Exception $e) { }

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <?php if ($is_responsive): ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php endif; ?>
    <title>Editor Harta Metrou</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --bg-light: #f4f6f8;
            --border: #e0e0e0;
            --text: #333;
        }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin:0; padding:0; display:flex; height:100vh; overflow:hidden; background: var(--bg-light); color: var(--text); }
        .sidebar { width: 300px; background: white; border-right: 1px solid var(--border); display: flex; flex-direction: column; z-index: 100; box-shadow: 2px 0 10px rgba(0,0,0,0.05); }
        .sidebar-header { padding: 15px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; font-weight: bold; }
        .sidebar-header a { text-decoration: none; color: #7f8c8d; }

        .tools-panel { padding: 15px; border-bottom: 1px solid var(--border); display: flex; flex-direction: column; gap: 10px; }
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; color: white; display: flex; align-items: center; gap: 8px; justify-content: center; }
        .btn-primary { background: var(--primary); }
        .btn-primary:hover { background: #2980b9; }
        .btn-success { background: #2ecc71; }
        .btn-danger { background: #e74c3c; }
        .btn-outline { background: white; color: var(--text); border: 1px solid #ccc; }
        .btn-outline:hover { background: #f9f9f9; }

        .lines-list { flex: 1; overflow-y: auto; padding: 10px; }
        .line-item { background: white; border: 1px solid var(--border); border-radius: 4px; padding: 10px; margin-bottom: 10px; cursor: pointer; }
        .line-item.active { border-color: var(--primary); box-shadow: 0 0 0 2px rgba(52,152,219,0.2); }
        .line-header { display: flex; justify-content: space-between; align-items: center; font-weight: bold; margin-bottom: 10px; }
        .color-dot { width: 15px; height: 15px; border-radius: 50%; display: inline-block; }
        .form-group { margin-bottom: 10px; }
        .form-group label { display: block; font-size: 0.8rem; color: #7f8c8d; margin-bottom: 3px; }
        .form-group input { width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }

        .map-container { flex: 1; position: relative; background: #fff; overflow: hidden; cursor: crosshair; }
        .map-container.mode-select { cursor: default; }

        svg { width: 100%; height: 100%; }
        circle { cursor: pointer; }
        circle:hover { stroke-width: 3; stroke: #333; }
        path { fill: none; stroke-width: 6; stroke-linejoin: round; stroke-linecap: round; cursor: pointer; }
        path:hover { stroke-width: 8; opacity: 0.8; }
        text { font-family: sans-serif; font-size: 12px; font-weight: bold; fill: #333; pointer-events: none; }

        /* Modal */
        .modal-overlay { position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 2000; }
        .modal { background: white; padding: 20px; border-radius: 8px; width: 300px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .modal h3 { margin-top: 0; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }

        .toast { position: fixed; bottom: 20px; right: 20px; background: #2ecc71; color: white; padding: 10px 20px; border-radius: 4px; display: none; z-index: 3000; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <span><i class="fas fa-subway"></i> Editor Metrou</span>
            <a href="index.php"><i class="fas fa-times"></i></a>
        </div>

        <div class="tools-panel">
            <button class="btn btn-primary" onclick="showLineModal()"><i class="fas fa-plus"></i> Linie Nouă</button>
            <div style="display: flex; gap: 5px;">
                <button class="btn btn-outline" id="modeDraw" onclick="setMode('draw')" style="flex:1" title="Adaugă Stații"><i class="fas fa-pen"></i> Desenează</button>
                <button class="btn btn-outline" id="modeSelect" onclick="setMode('select')" style="flex:1" title="Selectează/Mută"><i class="fas fa-mouse-pointer"></i> Selectează</button>
            </div>
            <button class="btn btn-success" onclick="saveAll()"><i class="fas fa-save"></i> Salvează Modificări</button>
            <p style="font-size: 0.8rem; color: #777; margin:0; text-align: center;">Click pe hartă în modul "Desenează" pentru a adăuga o stație la linia activă.</p>
        </div>

        <div class="lines-list" id="linesList">
            <!-- Lines will be injected here -->
        </div>
    </div>

    <div class="map-container mode-select" id="mapContainer">
        <svg id="metroSvg">
            <!-- Paths and circles will be drawn here -->
        </svg>
    </div>

    <!-- Station Name Modal -->
    <div class="modal-overlay" id="stationModal">
        <div class="modal">
            <h3>Nume Stație</h3>
            <div class="form-group">
                <input type="text" id="stationNameInput" placeholder="Ex: Piața Unirii">
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('stationModal')">Anulare</button>
                <button class="btn btn-primary" onclick="confirmStation()">Adaugă</button>
            </div>
        </div>
    </div>

    <!-- Line Modal -->
    <div class="modal-overlay" id="lineModal">
        <div class="modal">
            <h3 id="lineModalTitle">Linie Nouă</h3>
            <input type="hidden" id="lineIdInput">
            <div class="form-group">
                <label>Nume (ex: M1)</label>
                <input type="text" id="lineNameInput">
            </div>
            <div class="form-group">
                <label>Culoare (Hex)</label>
                <input type="color" id="lineColorInput" value="#e74c3c" style="height:40px; padding:0;">
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('lineModal')">Anulare</button>
                <button class="btn btn-primary" onclick="saveLine()">Salvează</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast">Modificări salvate!</div>

    <script>
        let linesData = [];
        let activeLineId = null;
        let mode = 'select'; // 'draw' or 'select'
        let draggedStation = null;

        let pendingStationPos = null;

        // Initialize
        async function loadData() {
            const res = await fetch('api_metro.php?action=load');
            const data = await res.json();
            if (data.success) {
                linesData = data.lines;
                renderLinesList();
                renderMap();
            }
        }

        function setMode(newMode) {
            mode = newMode;
            document.getElementById('modeDraw').style.background = mode === 'draw' ? '#e0e0e0' : 'white';
            document.getElementById('modeSelect').style.background = mode === 'select' ? '#e0e0e0' : 'white';
            document.getElementById('mapContainer').className = 'map-container mode-' + mode;
        }

        function renderLinesList() {
            const list = document.getElementById('linesList');
            list.innerHTML = '';

            linesData.forEach(line => {
                const div = document.createElement('div');
                div.className = `line-item ${line.id === activeLineId ? 'active' : ''}`;
                div.onclick = () => { activeLineId = line.id; renderLinesList(); };

                const safeName = document.createTextNode(line.name).textContent;

                div.innerHTML = `
                    <div class="line-header">
                        <div>
                            <span class="color-dot" style="background: ${line.color}"></span>
                            ${safeName}
                        </div>
                        <div>
                            <i class="fas fa-edit" style="color:#7f8c8d; margin-right:5px;" onclick="editLine(event, ${line.id})"></i>
                            <i class="fas fa-trash" style="color:#e74c3c;" onclick="deleteLine(event, ${line.id})"></i>
                        </div>
                    </div>
                    <div style="font-size:0.8rem; color:#777;">
                        ${line.stations ? line.stations.length : 0} stații
                    </div>
                `;
                list.appendChild(div);
            });
        }

        function renderMap() {
            const svg = document.getElementById('metroSvg');
            svg.innerHTML = ''; // Clear

            // Draw lines first (paths)
            linesData.forEach(line => {
                if (!line.stations || line.stations.length < 2) return;

                const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
                let d = `M ${line.stations[0].x} ${line.stations[0].y} `;
                for (let i = 1; i < line.stations.length; i++) {
                    d += `L ${line.stations[i].x} ${line.stations[i].y} `;
                }
                path.setAttribute("d", d);
                path.setAttribute("stroke", line.color);

                svg.appendChild(path);
            });

            // Draw stations (circles & text)
            linesData.forEach(line => {
                if (!line.stations) return;

                line.stations.forEach((st, idx) => {
                    const group = document.createElementNS("http://www.w3.org/2000/svg", "g");

                    const circle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                    circle.setAttribute("cx", st.x);
                    circle.setAttribute("cy", st.y);
                    circle.setAttribute("r", 6);
                    circle.setAttribute("fill", "#fff");
                    circle.setAttribute("stroke", line.color);
                    circle.setAttribute("stroke-width", "3");

                    // Drag functionality
                    circle.onmousedown = (e) => {
                        if (mode === 'select') {
                            draggedStation = { lineId: line.id, stationIdx: idx };
                        }
                    };

                    const text = document.createElementNS("http://www.w3.org/2000/svg", "text");
                    text.setAttribute("x", parseInt(st.x) + 12);
                    text.setAttribute("y", parseInt(st.y) + 4);
                    text.textContent = st.name;

                    group.appendChild(circle);
                    group.appendChild(text);
                    svg.appendChild(group);
                });
            });
        }

        // SVG Interaction
        const svgElement = document.getElementById('metroSvg');

        // Add snapping functionality logic
        function snapToExistingStation(x, y, radius = 15) {
            for (const line of linesData) {
                if (!line.stations) continue;
                for (const st of line.stations) {
                    const dx = st.x - x;
                    const dy = st.y - y;
                    if (Math.sqrt(dx*dx + dy*dy) < radius) {
                        return { x: st.x, y: st.y, name: st.name };
                    }
                }
            }
            return { x, y, name: '' };
        }

        svgElement.addEventListener('mousemove', (e) => {
            if (draggedStation && mode === 'select') {
                const rect = svgElement.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const line = linesData.find(l => l.id === draggedStation.lineId);
                line.stations[draggedStation.stationIdx].x = x;
                line.stations[draggedStation.stationIdx].y = y;
                renderMap();
            }
        });

        svgElement.addEventListener('mouseup', () => {
            draggedStation = null;
        });

        svgElement.addEventListener('click', (e) => {
            if (mode === 'draw' && activeLineId) {
                const rect = svgElement.getBoundingClientRect();
                let x = Math.round(e.clientX - rect.left);
                let y = Math.round(e.clientY - rect.top);

                // If clicked on circle directly or very close, snap to it
                const snapped = snapToExistingStation(x, y);

                pendingStationPos = { x: snapped.x, y: snapped.y };
                document.getElementById('stationNameInput').value = snapped.name;

                document.getElementById('stationModal').style.display = 'flex';
                document.getElementById('stationNameInput').focus();
            } else if (mode === 'draw' && !activeLineId) {
                alert("Te rugăm să selectezi o linie din stânga mai întâi!");
            }
        });

        function confirmStation() {
            const name = document.getElementById('stationNameInput').value.trim();
            if (!name) return alert('Introdu un nume!');

            const line = linesData.find(l => l.id === activeLineId);
            if (!line.stations) line.stations = [];

            line.stations.push({
                name: name,
                x: pendingStationPos.x,
                y: pendingStationPos.y
            });

            closeModal('stationModal');
            renderLinesList();
            renderMap();
        }

        // Line Management
        function showLineModal() {
            document.getElementById('lineIdInput').value = '';
            document.getElementById('lineNameInput').value = '';
            document.getElementById('lineColorInput').value = '#e74c3c';
            document.getElementById('lineModalTitle').innerText = 'Linie Nouă';
            document.getElementById('lineModal').style.display = 'flex';
        }

        function editLine(e, id) {
            e.stopPropagation();
            const line = linesData.find(l => l.id === id);
            document.getElementById('lineIdInput').value = line.id;
            document.getElementById('lineNameInput').value = line.name;
            document.getElementById('lineColorInput').value = line.color;
            document.getElementById('lineModalTitle').innerText = 'Editare Linie';
            document.getElementById('lineModal').style.display = 'flex';
        }

        async function saveLine() {
            const id = document.getElementById('lineIdInput').value;
            const name = document.getElementById('lineNameInput').value;
            const color = document.getElementById('lineColorInput').value;

            if (!name) return alert('Nume invalid');

            const payload = { name, color };
            if (id) payload.id = id;

            const res = await fetch('api_metro.php?action=save_line', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                closeModal('lineModal');
                loadData();
                activeLineId = data.id;
            }
        }

        async function deleteLine(e, id) {
            e.stopPropagation();
            if(confirm("Sigur ștergi această linie și toate stațiile ei?")) {
                await fetch('api_metro.php?action=delete_line', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id})
                });
                if(activeLineId === id) activeLineId = null;
                loadData();
            }
        }

        // Save All Stations
        async function saveAll() {
            for (const line of linesData) {
                if (line.stations) {
                    await fetch('api_metro.php?action=save_stations', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ line_id: line.id, stations: line.stations })
                    });
                }
            }

            const toast = document.getElementById('toast');
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 3000);
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Initial setup
        setMode('select');
        loadData();
    </script>
</body>
</html>
