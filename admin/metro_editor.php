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
    <script src="js/panzoom.min.js"></script>
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
        .draggable { pointer-events: all !important; cursor: move; }

        /* Modal */
        .modal-overlay { position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 2000; }
        .modal { background: white; padding: 20px; border-radius: 8px; width: 300px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .modal h3 { margin-top: 0; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }

        .toast { position: fixed; bottom: 20px; right: 20px; background: #2ecc71; color: white; padding: 10px 20px; border-radius: 4px; display: none; z-index: 3000; }

        @keyframes customPulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        .blinking-icon {
            animation: customPulse 1.5s infinite ease-in-out;
            transform-origin: center;
        }
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

            <div style="border-top: 1px solid var(--border); padding-top: 10px; margin-top: 5px;">
                <p style="font-size:0.8rem; font-weight:bold; margin:0 0 5px 0;">Adaugă Elemente (Decoruri):</p>
                <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                    <button class="btn btn-outline" style="padding:4px; font-size:0.8rem;" onclick="addDecoration('text')"><i class="fas fa-font"></i> Text Legenda</button>
                    <button class="btn btn-outline" style="padding:4px; font-size:0.8rem;" onclick="document.getElementById('imgUploadInput').click()"><i class="fas fa-image"></i> Poză (Upload)</button>
                    <button class="btn btn-outline" style="padding:4px; font-size:0.8rem;" onclick="addDecoration('rect')"><i class="far fa-square"></i> Pătrat</button>
                    <button class="btn btn-outline" style="padding:4px; font-size:0.8rem;" onclick="addDecoration('circle')"><i class="far fa-circle"></i> Cerc</button>
                </div>
            </div>
            <input type="file" id="imgUploadInput" style="display:none;" accept="image/*" onchange="handleImageUpload(event)">

            <div style="border-top: 1px solid var(--border); padding-top: 10px; margin-top: 5px;">
                <label style="font-size: 0.8rem; font-weight: bold; display: block; margin-bottom: 5px;">Zoom Inițial Frontend:</label>
                <div style="display: flex; gap: 5px; align-items: center; margin-bottom: 10px;">
                    <input type="number" id="initialZoomInput" value="1" step="0.1" min="0.1" max="10" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                    <button class="btn btn-outline" title="Folosește zoom-ul curent" onclick="setZoomFromCurrent()"><i class="fas fa-eye"></i></button>
                </div>

                <label style="font-size: 0.8rem; font-weight: bold; display: block; margin-bottom: 5px;">Mesaj Subsol Orar:</label>
                <div style="margin-bottom: 10px;">
                    <input type="text" id="footerMessageInput" value="Stimați călători, atenție la închiderea ușilor." style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                </div>

                <button class="btn btn-outline" style="width:100%; padding:8px; border:2px dashed var(--primary); color:var(--primary);" onclick="exportMap()"><i class="fas fa-file-export"></i> Export Hartă</button>
                <button class="btn btn-outline" style="width:100%; padding:8px; margin-top:5px; border:2px dashed #2ecc71; color:#2ecc71;" onclick="document.getElementById('importMapInput').click()"><i class="fas fa-file-import"></i> Import Hartă</button>
                <input type="file" id="importMapInput" style="display:none;" accept=".json" onchange="importMap(event)">
            </div>

            <button class="btn btn-success" onclick="saveAll()"><i class="fas fa-save"></i> Salvează Modificări</button>
            <p style="font-size: 0.8rem; color: #777; margin:0; text-align: center;">Click pe hartă în modul "Desenează" pentru a adăuga o stație la linia activă.</p>
        </div>

        <div style="padding: 10px; border-bottom: 1px solid var(--border); background: #f9f9f9;">
            <button class="btn btn-outline" style="width: 100%; margin-bottom: 5px; font-size: 0.85rem;" onclick="openVariantsModal()"><i class="fas fa-layer-group"></i> Variante Hărți</button>
            <button class="btn btn-outline" style="width: 100%; font-size: 0.85rem; color: #d35400; border-color: #d35400;" onclick="activate2011Map()"><i class="fas fa-map-marked-alt"></i> Activează Harta M1-M7 (2011)</button>
        </div>

        <div class="lines-list" id="linesList">
            <!-- Lines will be injected here -->
        </div>
    </div>

    <div class="map-container mode-select" id="mapContainer" style="display: flex; flex-direction: column;">
        <div style="padding: 10px; background: white; border-bottom: 1px solid var(--border); display: flex; gap: 10px; z-index: 10;">
            <button class="btn btn-outline" id="zoomInBtn" title="Zoom In"><i class="fas fa-search-plus"></i></button>
            <button class="btn btn-outline" id="zoomOutBtn" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
            <button class="btn btn-outline" id="zoomResetBtn" title="Reset Zoom"><i class="fas fa-compress"></i></button>
            <input type="file" id="bgGuideUpload" style="display:none;" accept="image/*" onchange="handleBgGuideUpload(event)">
            <button class="btn btn-outline" title="Ghidaj din Poză (Fundal)" onclick="document.getElementById('bgGuideUpload').click()"><i class="fas fa-image"></i> Ghidaj</button>
            <button class="btn btn-outline" style="color: #8e44ad; border-color: #8e44ad;" title="Detectare AI din Ghidaj" onclick="autoDrawFromGuide()"><i class="fas fa-magic"></i> Desenează Automat</button>
            <span style="display:flex; align-items:center; font-size:12px; color:#555;">Folosiți Scroll pentru zoom. Click pe rotiță (sau țineți spațiu) pt Pan.</span>
        </div>

        <!-- Loading Overlay for Auto Draw -->
        <div id="aiLoadingOverlay" style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.8); z-index:2000; align-items:center; justify-content:center; flex-direction:column;">
            <i class="fas fa-robot fa-3x fa-spin" style="color:#8e44ad; margin-bottom:15px;"></i>
            <h3 style="color:#333; margin:0;">AI analizează imaginea...</h3>
            <p style="color:#666;">Te rugăm să aștepți.</p>
        </div>
        <div id="panzoom-wrapper" style="flex:1; overflow:hidden; position:relative;">
            <div id="guideOverlay" style="position: absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:0; background-size: contain; background-repeat: no-repeat; opacity:0.3; transform-origin: 0 0;"></div>
            <svg id="metroSvg" style="width: 100%; height: 100%; position:relative; z-index:1; overflow:visible;">
                <!-- Paths and circles will be drawn here -->
            </svg>
        </div>
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

    <!-- Station Edit Modal -->
    <div class="modal-overlay" id="stationEditModal">
        <div class="modal">
            <h3>Editare Stație</h3>
            <input type="hidden" id="stationEditLineId">
            <input type="hidden" id="stationEditIdx">
            <div class="form-group">
                <label>Nume Stație</label>
                <input type="text" id="stationEditName">
            </div>
            <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                <input type="checkbox" id="stationEditWaypoint" style="width:auto;">
                <label style="margin:0;">Punct de control (ascunde cerc/text)</label>
            </div>
            <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                <input type="checkbox" id="stationEditUnderConstruction" style="width:auto;">
                <label style="margin:0;">Stație în construcție</label>
            </div>
            <div class="form-group">
                <label>Grosime Font</label>
                <select id="stationEditFontWeight" style="width:100%; padding:6px; border:1px solid #ccc; border-radius:4px;">
                    <option value="normal">Normal</option>
                    <option value="bold">Bold (Îngroșat)</option>
                </select>
            </div>
            <div class="modal-footer" style="justify-content: space-between;">
                <button class="btn btn-danger" onclick="deleteStation()">Șterge Stația</button>
                <div style="display:flex; gap:10px;">
                    <button class="btn btn-outline" onclick="closeModal('stationEditModal')">Anulare</button>
                    <button class="btn btn-primary" onclick="saveStationEdit()">Salvează</button>
                </div>
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
            <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                <input type="checkbox" id="lineDashedInput" style="width:auto;">
                <label style="margin:0;">Linie în construcție (Întreruptă)</label>
            </div>
            <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
            <p style="font-size:0.8rem; margin:0 0 10px 0; font-weight:bold;">Orar Trenuri (Generare Automată)</p>
            <div style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label>Ora Început</label>
                    <input type="time" id="lineStartInput" value="05:00">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Ora Final</label>
                    <input type="time" id="lineEndInput" value="23:30">
                </div>
            </div>
            <div class="form-group">
                <label>Interval (minute)</label>
                <input type="number" id="lineIntervalInput" value="6" min="1">
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('lineModal')">Anulare</button>
                <button class="btn btn-primary" onclick="saveLine()">Salvează</button>
            </div>
        </div>
    </div>

    <!-- Variants Modal -->
    <div class="modal-overlay" id="variantsModal">
        <div class="modal" style="width: 500px;">
            <h3>Variante Hărți</h3>
            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                <input type="text" id="newVariantName" placeholder="Nume variantă nouă..." style="flex:1; padding:6px; border:1px solid #ccc; border-radius:4px;">
                <button class="btn btn-success" onclick="saveAsNewVariant()"><i class="fas fa-plus"></i> Salvează Curenta</button>
            </div>

            <div id="variantsList" style="max-height: 300px; overflow-y: auto; border: 1px solid var(--border); border-radius: 4px; padding: 10px; background: var(--bg-light);">
                <!-- Variants injected here -->
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('variantsModal')">Închide</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast">Modificări salvate!</div>

    <script>
        let linesData = [];
        let decorationsData = [];
        let variantsData = [];
        let activeLineId = null;
        let mode = 'select'; // 'draw' or 'select'
        let draggedStation = null;
        let draggedDecoration = null;
        let resizingDecoration = null; // Store { idx, startX, startY, startW, startH }

        let pendingStationPos = null;

        let pz;

        // Initialize
        async function loadData() {
            const res = await fetch('api_metro.php?action=load');
            const data = await res.json();
            if (data.success) {
                linesData = data.lines;
                decorationsData = data.decorations || [];
                variantsData = data.variants || [];

                if (data.zoom) {
                    document.getElementById('initialZoomInput').value = data.zoom;
                }

                if (data.footer_message) {
                    document.getElementById('footerMessageInput').value = data.footer_message;
                }

                renderLinesList();
                renderMap();
                renderVariantsList();

                if (!pz) {
                    const svgEl = document.getElementById('metroSvg');
                    const wrapper = document.getElementById('panzoom-wrapper');
                    pz = Panzoom(svgEl, {
                        maxScale: 10,
                        minScale: 0.1,
                        canvas: true,
                        excludeClass: 'draggable'
                    });
                    wrapper.addEventListener('wheel', pz.zoomWithWheel);

                    // Sync guide overlay with panzoom transform
                    svgEl.addEventListener('panzoomchange', (e) => {
                        const guide = document.getElementById('guideOverlay');
                        if (guide) {
                            guide.style.transform = `matrix(${e.detail.scale}, 0, 0, ${e.detail.scale}, ${e.detail.x}, ${e.detail.y})`;
                        }
                    });

                    document.getElementById('zoomInBtn').addEventListener('click', pz.zoomIn);
                    document.getElementById('zoomOutBtn').addEventListener('click', pz.zoomOut);
                    document.getElementById('zoomResetBtn').addEventListener('click', pz.reset);
                }
            }
        }

        function setMode(newMode) {
            mode = newMode;
            document.getElementById('modeDraw').style.background = mode === 'draw' ? '#e0e0e0' : 'white';
            document.getElementById('modeSelect').style.background = mode === 'select' ? '#e0e0e0' : 'white';
            document.getElementById('mapContainer').className = 'map-container mode-' + mode;
            if (pz) {
                if (mode === 'draw') {
                    pz.setOptions({ cursor: 'crosshair', disablePan: true });
                } else {
                    pz.setOptions({ cursor: 'default', disablePan: false });
                }
            }
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
                                <button class="btn btn-outline" style="padding: 2px 6px; font-size:0.8rem; border-color:${line.is_hidden == 1 ? 'gray' : 'var(--primary)'}; color:${line.is_hidden == 1 ? 'gray' : 'var(--primary)'};" onclick="toggleLineVisibility(${line.id}, event)" title="${line.is_hidden == 1 ? 'Arată Linie' : 'Ascunde Linie'}"><i class="fas ${line.is_hidden == 1 ? 'fa-eye-slash' : 'fa-eye'}"></i></button>
                                <button class="btn btn-outline" style="padding: 2px 6px; font-size:0.8rem;" onclick="activateLineMove(${line.id}, event)" title="Mută Toată Linia"><i class="fas fa-arrows-alt"></i></button>
                                <button class="btn btn-outline" style="padding: 2px 6px; font-size:0.8rem;" onclick="openLineIcons(${line.id}, event)" title="Adaugă Iconițe"><i class="fas fa-icons"></i></button>
                                <button class="btn btn-outline" style="padding: 2px 6px; font-size:0.8rem; border-color:var(--danger); color:var(--danger);" onclick="deleteLine(event, ${line.id})" title="Șterge Linie"><i class="fas fa-trash"></i></button>
                                <button class="btn btn-outline" style="padding: 2px 6px; font-size:0.8rem;" onclick="editLine(event, ${line.id})" title="Editează Linie"><i class="fas fa-edit"></i></button>
                            </div>
                    </div>
                    <div style="font-size:0.8rem; color:#777;">
                        ${line.stations ? line.stations.length : 0} stații
                    </div>
                `;
                list.appendChild(div);
            });
        }

        function addDecoration(type) {
            let content = '';
            let color = '#333333';
            let width = 50;
            let height = 50;

            if (type === 'text') {
                content = prompt("Introduceți textul pentru legendă:", "Text Legenda");
                if (!content) return;
                color = prompt("Culoare Hex (opțional):", "#000000") || '#000000';
            } else if (type === 'image') {
                // Open file input instead of adding directly
                document.getElementById('imgUploadInput').click();
                return;
            } else if (type === 'rect' || type === 'circle') {
                color = prompt("Culoare Hex (opțional):", "#3498db") || '#3498db';
                width = parseInt(prompt("Lățime/Rază:", "50")) || 50;
                if(type === 'rect') height = parseInt(prompt("Înălțime:", "50")) || 50;
            } else if (type.startsWith('icon_')) {
                // Determine icon content
                const icons = {
                    'icon_plane': '\uf072', 'icon_train': '\uf238', 'icon_road': '\uf018',
                    'icon_soldier': '\ufe4b', 'icon_water': '\uf773', 'icon_cone': '\uf243', 'icon_tree': '\uf1bb'
                };
                content = icons[type];
            }

            decorationsData.push({
                id: 'new_' + Date.now(),
                type: type,
                x: 100,
                y: 100,
                width: width,
                height: height,
                content: content,
                color: color,
                font_weight: 'normal'
            });
            renderMap();
        }

        async function handleImageUpload(e) {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);
            formData.append('action', 'upload_image');

            try {
                const res = await fetch('api_metro.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    decorationsData.push({
                        id: 'new_' + Date.now(),
                        type: 'image',
                        x: 100,
                        y: 100,
                        width: 100, // Default display width
                        height: 100,
                        content: data.url, // Contains uploaded path
                        color: '',
                        font_weight: 'normal'
                    });
                    renderMap();
                } else {
                    alert('Eroare la încărcare imagine: ' + (data.error || 'Unknown'));
                }
            } catch (err) {
                alert('Eroare rețea.');
            }
            e.target.value = ''; // Reset
        }

        function renderMap() {
            const svg = document.getElementById('metroSvg');
            svg.innerHTML = ''; // Clear

            // Draw decorations first so they are behind paths
            decorationsData.forEach((dec, idx) => {
                const group = document.createElementNS("http://www.w3.org/2000/svg", "g");
                let el;

                if (dec.type === 'text') {
                    el = document.createElementNS("http://www.w3.org/2000/svg", "text");
                    el.setAttribute("class", "draggable");
                    el.textContent = dec.content;
                    el.setAttribute("fill", dec.color);
                    el.setAttribute("font-size", "14px");
                    el.setAttribute("font-weight", dec.font_weight);
                    el.setAttribute("x", dec.x);
                    el.setAttribute("y", dec.y);
                } else if (dec.type === 'image') {
                    el = document.createElementNS("http://www.w3.org/2000/svg", "image");
                    el.setAttribute("href", dec.content);
                    el.setAttribute("x", dec.x - dec.width/2);
                    el.setAttribute("y", dec.y - dec.height/2);
                    el.setAttribute("width", dec.width);
                    el.setAttribute("height", dec.height);
                    el.setAttribute("preserveAspectRatio", "xMidYMid meet");
                    el.setAttribute("class", "draggable blinking-icon");
                    el.style.transformOrigin = `${dec.x}px ${dec.y}px`;
                    el.setAttribute("width", dec.width || 100);
                    el.setAttribute("height", dec.height || 100);
                } else if (dec.type === 'rect') {
                    el = document.createElementNS("http://www.w3.org/2000/svg", "rect");
                    el.setAttribute("class", "draggable");
                    el.setAttribute("x", dec.x);
                    el.setAttribute("y", dec.y);
                    el.setAttribute("width", dec.width);
                    el.setAttribute("height", dec.height);
                    el.setAttribute("fill", dec.color);
                    el.setAttribute("opacity", "0.5");
                } else if (dec.type === 'circle') {
                    el = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                    el.setAttribute("class", "draggable");
                    el.setAttribute("cx", dec.x);
                    el.setAttribute("cy", dec.y);
                    el.setAttribute("r", dec.width);
                    el.setAttribute("fill", dec.color);
                    el.setAttribute("opacity", "0.5");
                } else if (dec.type.startsWith('icon_')) {
                    el = document.createElementNS("http://www.w3.org/2000/svg", "text");
                    el.setAttribute("class", "fa draggable");
                    el.textContent = dec.content;
                    el.setAttribute("fill", dec.color);
                    el.setAttribute("font-size", "24px");
                    el.setAttribute("x", dec.x);
                    el.setAttribute("y", dec.y);
                    el.style.fontFamily = '"Font Awesome 6 Free"';
                    el.style.fontWeight = '900';
                }

                if (el) {
                    el.style.cursor = 'move';
                    el.style.pointerEvents = 'all'; // Required to capture mouse events on SVGs

                    // Tooltip text explaining double-click
                    const title = document.createElementNS("http://www.w3.org/2000/svg", "title");
                    title.textContent = "Dublu-click pentru a șterge acest element.";
                    el.appendChild(title);

                    el.onmousedown = (e) => {
                        if (mode === 'select') {
                            e.stopPropagation();
                            if (pz) pz.setOptions({ disablePan: true });
                            draggedDecoration = idx;

                            // To prevent text selection during drag
                            e.preventDefault();
                        }
                    };

                    // Double click to delete
                    el.ondblclick = (e) => {
                        if (mode === 'select') {
                            e.stopPropagation();
                            if(confirm('Ștergi acest element?')) {
                                decorationsData.splice(idx, 1);
                                renderMap();
                            }
                        }
                    };

                    group.appendChild(el);

                    // Add Resize Handle if it is an image
                    if (dec.type === 'image' || dec.type === 'rect') {
                        const handle = document.createElementNS("http://www.w3.org/2000/svg", "rect");
                        handle.setAttribute("x", dec.x + (dec.width || 100) - 5);
                        handle.setAttribute("y", dec.y + (dec.height || 100) - 5);
                        handle.setAttribute("width", 10);
                        handle.setAttribute("height", 10);
                        handle.setAttribute("fill", "white");
                        handle.setAttribute("stroke", "#3498db");
                        handle.setAttribute("stroke-width", "2");
                        handle.style.cursor = "se-resize";
                        handle.style.pointerEvents = "all";

                        handle.onmousedown = (e) => {
                            if (mode === 'select') {
                                e.stopPropagation();
                                if (pz) pz.setOptions({ disablePan: true });
                                resizingDecoration = {
                                    idx: idx,
                                    startX: e.clientX,
                                    startY: e.clientY,
                                    startW: dec.width || 100,
                                    startH: dec.height || 100
                                };
                                e.preventDefault();
                            }
                        };
                        group.appendChild(handle);
                    }

                    svg.appendChild(group);
                }
            });


            // Draw lines (paths)
            linesData.forEach(line => {
                if (line.is_hidden == 1) return;
                if (!line.stations || line.stations.length < 2) return;

                // If the entire line is dashed
                if (line.is_dashed == 1) {
                    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
                    let d = `M ${line.stations[0].x} ${line.stations[0].y} `;
                    for (let i = 1; i < line.stations.length; i++) {
                        d += `L ${line.stations[i].x} ${line.stations[i].y} `;
                    }
                    path.setAttribute("d", d);
                    path.setAttribute("stroke", line.color);
                    path.setAttribute("fill", "none");
                    path.setAttribute("stroke-width", "4"); // mai subțiri
                    path.setAttribute("stroke-linejoin", "round");
                    path.setAttribute("stroke-dasharray", "8,16"); // mai îndepărtate
                    svg.appendChild(path);
                } else {
                    // Draw segment by segment to handle individual under construction stations
                    for (let i = 1; i < line.stations.length; i++) {
                        const prevSt = line.stations[i-1];
                        const currSt = line.stations[i];

                        const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
                        path.setAttribute("d", `M ${prevSt.x} ${prevSt.y} L ${currSt.x} ${currSt.y}`);
                        path.setAttribute("stroke", line.color);
                        path.setAttribute("fill", "none");
                        path.setAttribute("stroke-linejoin", "round");

                        if (prevSt.is_under_construction == 1 || currSt.is_under_construction == 1) {
                            path.setAttribute("stroke-width", "4");
                            path.setAttribute("stroke-dasharray", "8,16");
                        } else {
                            path.setAttribute("stroke-width", "6");
                        }

                        svg.appendChild(path);
                    }
                }
            });

            // Draw stations (circles & text)
            linesData.forEach(line => {
                if (!line.stations) return;

                line.stations.forEach((st, idx) => {
                    const group = document.createElementNS("http://www.w3.org/2000/svg", "g");

                    const circle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                    circle.setAttribute("class", "draggable");
                    circle.setAttribute("cx", st.x);
                    circle.setAttribute("cy", st.y);
                    circle.setAttribute("r", 6);
                    circle.setAttribute("fill", "#fff");
                    circle.setAttribute("stroke", line.color);
                    circle.setAttribute("stroke-width", "3");

                    if (st.is_waypoint == 1) {
                        circle.style.display = 'none'; // Hide visual circle for waypoints
                        // Add an invisible slightly larger circle to allow dragging the waypoint
                        const hiddenHitbox = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                        hiddenHitbox.setAttribute("cx", st.x);
                        hiddenHitbox.setAttribute("cy", st.y);
                        hiddenHitbox.setAttribute("r", 10);
                        hiddenHitbox.setAttribute("fill", "transparent");
                        hiddenHitbox.onmousedown = (e) => {
                            if (mode === 'select') {
                                e.stopPropagation();
                                if (pz) pz.setOptions({ disablePan: true });
                                draggedStation = { type: 'station', lineId: line.id, stationIdx: idx };
                            }
                        };
                        hiddenHitbox.ondblclick = (e) => openStationEdit(line.id, idx);
                        group.appendChild(hiddenHitbox);
                    }

                    // Drag functionality for Station
                    circle.onmousedown = (e) => {
                        if (mode === 'select') {
                            e.stopPropagation();
                            if (pz) pz.setOptions({ disablePan: true });
                            draggedStation = { type: 'station', lineId: line.id, stationIdx: idx };
                        }
                    };
                    circle.ondblclick = (e) => {
                        if (mode === 'select') openStationEdit(line.id, idx);
                    };

                    const text = document.createElementNS("http://www.w3.org/2000/svg", "text");
                    text.setAttribute("class", "draggable");
                    const ox = st.text_offset_x !== undefined ? parseInt(st.text_offset_x) : 12;
                    const oy = st.text_offset_y !== undefined ? parseInt(st.text_offset_y) : 4;

                    text.setAttribute("x", parseInt(st.x) + ox);
                    text.setAttribute("y", parseInt(st.y) + oy);
                    text.textContent = st.name;
                    text.setAttribute("font-weight", st.font_weight || 'bold');
                    text.style.pointerEvents = 'all'; // Allow clicking text
                    text.style.cursor = 'move';

                    if (st.is_waypoint == 1) {
                        text.style.display = 'none'; // Hide text for waypoints
                    }

                    // Drag functionality for Text
                    text.onmousedown = (e) => {
                        if (mode === 'select') {
                            e.stopPropagation(); // prevent triggering svg drag
                            if (pz) pz.setOptions({ disablePan: true });
                            draggedStation = { type: 'text', lineId: line.id, stationIdx: idx, startX: e.clientX, startY: e.clientY, startOx: ox, startOy: oy };
                        }
                    };
                    text.ondblclick = (e) => {
                        if (mode === 'select') openStationEdit(line.id, idx);
                    };

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
            if (mode === 'select') {
                const rect = svgElement.getBoundingClientRect();
                let scale = 1;
                if (pz) scale = pz.getScale();
                const mouseX = (e.clientX - rect.left) / scale;
                const mouseY = (e.clientY - rect.top) / scale;

                if (draggedStation) {
                    const line = linesData.find(l => l.id === draggedStation.lineId);
                    const st = line.stations[draggedStation.stationIdx];

                    if (draggedStation.type === 'station') {
                        st.x = mouseX;
                        st.y = mouseY;
                    } else if (draggedStation.type === 'text') {
                        const dx = (e.clientX - draggedStation.startX) / scale;
                        const dy = (e.clientY - draggedStation.startY) / scale;
                        st.text_offset_x = draggedStation.startOx + dx;
                        st.text_offset_y = draggedStation.startOy + dy;
                    }
                    renderMap();
                } else if (resizingDecoration !== null) {
                    const dec = decorationsData[resizingDecoration.idx];
                    const dx = (e.clientX - resizingDecoration.startX) / scale;
                    const dy = (e.clientY - resizingDecoration.startY) / scale;
                    // Maintain aspect ratio logic loosely or just free resize
                    dec.width = Math.max(20, resizingDecoration.startW + dx);
                    dec.height = Math.max(20, resizingDecoration.startH + dy);
                    renderMap();

                } else if (draggedDecoration !== null) {
                    const dec = decorationsData[draggedDecoration];
                    dec.x = mouseX;
                    dec.y = mouseY;
                    renderMap();
                } else if (movingLineId && isDraggingLine) {
                    const line = linesData.find(l => l.id === movingLineId);
                    if (line) {
                        const dx = mouseX - dragLineStartX;
                        const dy = mouseY - dragLineStartY;
                        line.stations.forEach(st => {
                            st.x = parseFloat(st.x) + dx;
                            st.y = parseFloat(st.y) + dy;
                        });
                        dragLineStartX = mouseX;
                        dragLineStartY = mouseY;
                        renderMap();
                    }
                }
            }
        });


        window.addEventListener('mouseup', () => {
            draggedStation = null;
            draggedDecoration = null;
            resizingDecoration = null;
            isDraggingLine = false;
            if (pz && mode === 'select') {
                pz.setOptions({ disablePan: false });
            }
        });


        svgElement.addEventListener('mousedown', (e) => {
            if (movingLineId && mode === 'select') {
                const rect = svgElement.getBoundingClientRect();
                let scale = 1;
                if (pz) scale = pz.getScale();
                dragLineStartX = (e.clientX - rect.left) / scale;
                dragLineStartY = (e.clientY - rect.top) / scale;
                isDraggingLine = true;
                if (pz) pz.setOptions({ disablePan: true });
                return;
            }
        });

        svgElement.addEventListener('click', (e) => {
            if (mode === 'draw' && activeLineId) {
                const rect = svgElement.getBoundingClientRect();
                let scale = 1;
                if (pz) scale = pz.getScale();
                let x = Math.round((e.clientX - rect.left) / scale);
                let y = Math.round((e.clientY - rect.top) / scale);

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
            document.getElementById('lineDashedInput').checked = false;
            document.getElementById('lineStartInput').value = '05:00';
            document.getElementById('lineEndInput').value = '23:30';
            document.getElementById('lineIntervalInput').value = '6';
            document.getElementById('lineModalTitle').innerText = 'Linie Nouă';
            document.getElementById('lineModal').style.display = 'flex';
        }

        function editLine(e, id) {
            e.stopPropagation();
            const line = linesData.find(l => l.id === id);
            document.getElementById('lineIdInput').value = line.id;
            document.getElementById('lineNameInput').value = line.name;
            document.getElementById('lineColorInput').value = line.color;
            document.getElementById('lineDashedInput').checked = (line.is_dashed == 1);
            document.getElementById('lineStartInput').value = line.start_time || '05:00';
            document.getElementById('lineEndInput').value = line.end_time || '23:30';
            document.getElementById('lineIntervalInput').value = line.interval_minutes || 6;
            document.getElementById('lineModalTitle').innerText = 'Editare Linie';
            document.getElementById('lineModal').style.display = 'flex';
        }

        async function saveLine() {
            const id = document.getElementById('lineIdInput').value;
            const name = document.getElementById('lineNameInput').value;
            const color = document.getElementById('lineColorInput').value;
            const is_dashed = document.getElementById('lineDashedInput').checked ? 1 : 0;
            const start_time = document.getElementById('lineStartInput').value;
            const end_time = document.getElementById('lineEndInput').value;
            const interval_minutes = document.getElementById('lineIntervalInput').value;

            if (!name) return alert('Nume invalid');

            const payload = { name, color, is_dashed, start_time, end_time, interval_minutes };
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

        async function toggleLineVisibility(id, e) {
            e.stopPropagation();
            const line = linesData.find(l => l.id === id);
            if (!line) return;
            line.is_hidden = line.is_hidden == 1 ? 0 : 1;
            renderLinesList();
            renderMap();
        }

        let movingLineId = null;
        let isDraggingLine = false;
        let dragLineStartX = 0;
        let dragLineStartY = 0;

        function activateLineMove(id, e) {
            e.stopPropagation();
            if (movingLineId === id) {
                movingLineId = null; // deactivate
                document.body.style.cursor = 'default';
            } else {
                movingLineId = id;
                document.body.style.cursor = 'move';
            }
        }

        function openLineIcons(id, e) {
             e.stopPropagation();
             document.getElementById('lineIconModal').style.display = 'flex';
             document.getElementById('lineIconModal').dataset.lineId = id;
        }

        function addIconToLine(type) {
            const lineId = parseInt(document.getElementById('lineIconModal').dataset.lineId);
            const line = linesData.find(l => l.id === lineId);
            if (!line || !line.stations || line.stations.length === 0) return closeModal('lineIconModal');

            const st = line.stations[0];
            const icons = {
                'icon_plane': '\uf072', 'icon_train': '\uf238', 'icon_road': '\uf018',
                'icon_soldier': '\ufe4b', 'icon_water': '\uf773', 'icon_cone': '\uf243', 'icon_tree': '\uf1bb'
            };
            let content = icons[type];
            let color = line.color;

            decorationsData.push({
                id: 'new_' + Date.now(),
                type: type,
                x: st.x,
                y: st.y - 20,
                width: 50,
                height: 50,
                content: content,
                color: color,
                font_weight: 'normal'
            });
            renderMap();
            closeModal('lineIconModal');
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

        // Station Edit Functions
        function openStationEdit(lineId, idx) {
            const line = linesData.find(l => l.id === lineId);
            const st = line.stations[idx];
            document.getElementById('stationEditLineId').value = lineId;
            document.getElementById('stationEditIdx').value = idx;
            document.getElementById('stationEditName').value = st.name;
            document.getElementById('stationEditWaypoint').checked = (st.is_waypoint == 1);
            document.getElementById('stationEditFontWeight').value = st.font_weight || 'bold';
            document.getElementById('stationEditUnderConstruction').checked = (st.is_under_construction == 1);
            document.getElementById('stationEditModal').style.display = 'flex';
        }

        function saveStationEdit() {
            const lineId = parseInt(document.getElementById('stationEditLineId').value);
            const idx = parseInt(document.getElementById('stationEditIdx').value);
            const line = linesData.find(l => l.id === lineId);
            const st = line.stations[idx];

            st.name = document.getElementById('stationEditName').value;
            st.is_waypoint = document.getElementById('stationEditWaypoint').checked ? 1 : 0;
            st.font_weight = document.getElementById('stationEditFontWeight').value;
            st.is_under_construction = document.getElementById('stationEditUnderConstruction').checked ? 1 : 0;

            closeModal('stationEditModal');
            renderMap();
        }

        function deleteStation() {
            if(confirm("Ești sigur că vrei să ștergi acest punct/stație?")) {
                const lineId = parseInt(document.getElementById('stationEditLineId').value);
                const idx = parseInt(document.getElementById('stationEditIdx').value);
                const line = linesData.find(l => l.id === lineId);
                line.stations.splice(idx, 1);
                closeModal('stationEditModal');
                renderMap();
            }
        }

        function handleBgGuideUpload(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (evt) => {
                const guide = document.getElementById('guideOverlay');
                guide.style.backgroundImage = `url('${evt.target.result}')`;
            };
            reader.readAsDataURL(file);
        }

        async function autoDrawFromGuide() {
            const guide = document.getElementById('guideOverlay');
            if (!guide.style.backgroundImage || guide.style.backgroundImage === 'none') {
                alert('Vă rugăm să încărcați mai întâi o imagine de ghidaj!');
                return;
            }
            if (!confirm('Atenție: Această acțiune va analiza imaginea încărcată și va genera automat harta, înlocuind structura curentă nesalvată. Continui?')) return;

            // Show loading animation
            const loader = document.getElementById('aiLoadingOverlay');
            loader.style.display = 'flex';

            try {
                const res = await fetch('ai_draw.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image_url: guide.style.backgroundImage })
                });

                const data = await res.json();

                if (data.success && data.map_data) {
                    const importRes = await fetch('api_metro.php?action=import_map', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(data.map_data)
                    });

                    const importData = await importRes.json();
                    if (importData.success) {
                        alert("Harta a fost detectată și importată cu succes de AI!");
                        await loadData();
                    } else {
                        alert("Eroare la importul hărții detectate: " + (importData.error || 'Unknown'));
                    }
                } else {
                    alert('Eroare AI: ' + (data.error || 'Unknown'));
                    if (data.raw_response) console.error("Raw AI Response:", data.raw_response);
                }
            } catch (err) {
                alert('Eroare de conexiune la serverul AI.');
                console.error(err);
            }

            loader.style.display = 'none';
        }

        function exportMap() {
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify({
                lines: linesData,
                decorations: decorationsData
            }));
            const downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", dataStr);
            downloadAnchorNode.setAttribute("download", "metro_map_backup_" + Date.now() + ".json");
            document.body.appendChild(downloadAnchorNode); // required for firefox
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        }

        async function importMap(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = async function(evt) {
                try {
                    const importedData = JSON.parse(evt.target.result);
                    if (importedData.lines) {
                        if (!confirm("Atenție: Importul va șterge harta curentă și o va înlocui complet cu cea importată. Continui?")) {
                            e.target.value = '';
                            return;
                        }
                        const res = await fetch('api_metro.php?action=import_map', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(importedData)
                        });
                        const data = await res.json();
                        if (data.success) {
                            alert("Harta a fost importată cu succes!");
                            loadData();
                        } else {
                            alert("Eroare la import: " + (data.error || 'Unknown'));
                        }
                    }
                } catch(err) {
                    alert("Fișier invalid!");
                }
            };
            reader.readAsText(file);
            e.target.value = ''; // Reset input
        }

        // Save All Stations & Decorations
        function setZoomFromCurrent() {
            if (pz) {
                const currentZoom = pz.getScale().toFixed(2);
                document.getElementById('initialZoomInput').value = currentZoom;
            }
        }

        function openVariantsModal() {
            document.getElementById('variantsModal').style.display = 'flex';
        }

        async function activate2011Map() {
            if (!confirm("Ești sigur? Această acțiune va șterge complet harta curentă (dacă nu ai salvat-o ca variantă) și o va înlocui cu harta completă M1-M7!")) {
                return;
            }
            try {
                const res = await fetch('api_metro.php?action=activate_2011_map', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    alert("Harta M1-M7 a fost activată cu succes!");
                    location.reload();
                } else {
                    alert("Eroare la activarea hărții: " + (data.error || 'Necunoscut'));
                }
            } catch (e) {
                console.error(e);
                alert("Eroare de conexiune!");
            }
        }

        function renderVariantsList() {
            const list = document.getElementById('variantsList');
            if (!list) return;
            list.innerHTML = '';

            if (variantsData.length === 0) {
                list.innerHTML = '<p style="color:#777; text-align:center; font-size:0.9rem;">Nicio variantă salvată.</p>';
                return;
            }

            variantsData.forEach(v => {
                const div = document.createElement('div');
                div.style = "display:flex; justify-content:space-between; align-items:center; padding:10px; background:white; margin-bottom:10px; border-radius:4px; border:1px solid #ccc;";

                const title = document.createElement('span');
                title.style = "font-weight:bold;";
                title.textContent = v.name;

                const actions = document.createElement('div');
                actions.style = "display:flex; gap:5px;";

                const activateBtn = document.createElement('button');
                activateBtn.className = "btn btn-outline";
                activateBtn.style = "font-size:0.8rem; padding:4px 8px; color:var(--primary);";
                activateBtn.innerHTML = "<i class='fas fa-check'></i> Activează";
                activateBtn.onclick = () => activateVariant(v.id);

                const deleteBtn = document.createElement('button');
                deleteBtn.className = "btn btn-outline";
                deleteBtn.style = "font-size:0.8rem; padding:4px 8px; color:#e74c3c;";
                deleteBtn.innerHTML = "<i class='fas fa-trash'></i>";
                deleteBtn.onclick = () => deleteVariant(v.id);

                actions.appendChild(activateBtn);
                actions.appendChild(deleteBtn);

                div.appendChild(title);
                div.appendChild(actions);
                list.appendChild(div);
            });
        }

        async function saveAsNewVariant() {
            const name = document.getElementById('newVariantName').value.trim();
            if (!name) return alert('Introdu un nume pentru variantă.');

            const mapData = {
                lines: linesData,
                decorations: decorationsData
            };

            const res = await fetch('api_metro.php?action=save_variant', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ variant_name: name, map_data: mapData })
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('newVariantName').value = '';
                loadData();
            } else {
                alert('Eroare la salvare.');
            }
        }

        async function activateVariant(id) {
            if (!confirm('Ești sigur? Harta curentă (dacă nu e salvată ca variantă) va fi pierdută și suprascrisă de varianta selectată.')) return;

            const res = await fetch(`api_metro.php?action=load_variant&id=${id}`);
            const data = await res.json();

            if (data.lines) {
                const importRes = await fetch('api_metro.php?action=import_map', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const importData = await importRes.json();
                if (importData.success) {
                    alert('Varianta a fost activată cu succes!');
                    closeModal('variantsModal');
                    loadData();
                }
            }
        }

        async function deleteVariant(id) {
            if (!confirm('Ștergi această variantă?')) return;
            const res = await fetch(`api_metro.php?action=delete_variant&id=${id}`);
            const data = await res.json();
            if (data.success) loadData();
        }

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

            if (typeof decorationsData !== 'undefined') {
                await fetch('api_metro.php?action=save_decorations', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ decorations: decorationsData })
                });
            }

            const zoomVal = document.getElementById('initialZoomInput').value;
            await fetch('api_metro.php?action=save_zoom', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ zoom: zoomVal })
            });

            const footerMsg = document.getElementById('footerMessageInput').value;
            await fetch('api_metro.php?action=save_footer_message', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ message: footerMsg })
            });

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

    <!-- Modal Line Icons -->
    <div id="lineIconModal" class="modal" style="display:none;" data-line-id="">
        <div class="modal-content" style="max-width: 400px;">
            <h3>Adaugă Iconiță pe Linie</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin: 20px 0;">
                <button class="btn btn-outline" style="padding:10px; font-size:1.5rem; color:#3498db;" onclick="addIconToLine('icon_plane')"><i class="fas fa-plane"></i></button>
                <button class="btn btn-outline" style="padding:10px; font-size:1.5rem; color:#e74c3c;" onclick="addIconToLine('icon_train')"><i class="fas fa-train"></i></button>
                <button class="btn btn-outline" style="padding:10px; font-size:1.5rem; color:#f1c40f;" onclick="addIconToLine('icon_road')"><i class="fas fa-road"></i></button>
                <button class="btn btn-outline" style="padding:10px; font-size:1.5rem; color:#2ecc71;" onclick="addIconToLine('icon_soldier')"><i class="fas fa-person-military-rifle"></i></button>
                <button class="btn btn-outline" style="padding:10px; font-size:1.5rem; color:#2980b9;" onclick="addIconToLine('icon_water')"><i class="fas fa-water"></i></button>
                <button class="btn btn-outline" style="padding:10px; font-size:1.5rem; color:#e67e22;" onclick="addIconToLine('icon_cone')"><i class="fas fa-traffic-cone"></i></button>
                <button class="btn btn-outline" style="padding:10px; font-size:1.5rem; color:#27ae60;" onclick="addIconToLine('icon_tree')"><i class="fas fa-tree"></i></button>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button class="btn btn-outline" onclick="closeModal('lineIconModal')">Închide</button>
            </div>
        </div>
    </div>
</body>
</html>
