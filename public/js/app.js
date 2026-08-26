
const map = L.map('map').setView([44.4268, 26.1025], 13);
L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap &copy; CARTO'
}).addTo(map);

const vehiclesLayer = L.layerGroup().addTo(map);
const stationsLayer = L.layerGroup().addTo(map);
let currentRoutePolyline = null;

// DOM Elements
const welcomeInfo = document.getElementById('welcome-info');
const stationInfo = document.getElementById('station-info');
const lineInfo = document.getElementById('line-info');
const btnBack = document.getElementById('btn-back');
const btnBackLine = document.getElementById('btn-back-line');
const stationNameEl = document.getElementById('station-name');
const arrivalsListEl = document.getElementById('arrivals-list');
const lineSearchInput = document.getElementById('line-search');
const timelineList = document.getElementById('timeline-list');
const bottomPanel = document.getElementById('bottom-panel');

// State
let filters = {
    'BUS': true,
    'TRAM': true,
    'TROLLEYBUS': true
};
let currentLineType = 'BUS'; // default category

// Events for back buttons
if (btnBack) btnBack.addEventListener('click', () => {
    stationInfo.classList.add('hidden');
    welcomeInfo.classList.remove('hidden');
});

if (btnBackLine) btnBackLine.addEventListener('click', () => {
    lineInfo.classList.add('hidden');
    bottomPanel.classList.add('hidden');
    welcomeInfo.classList.remove('hidden');
    if (currentRoutePolyline) map.removeLayer(currentRoutePolyline);
});

// Setup Category Tabs & Popup
const linesPopup = document.getElementById('lines-popup');
const linesPopupTitle = document.getElementById('lines-popup-title');
const linesPopupList = document.getElementById('lines-popup-list');
const closeLinesPopup = document.getElementById('close-lines-popup');

if (closeLinesPopup) {
    closeLinesPopup.addEventListener('click', () => {
        linesPopup.classList.add('hidden');
    });
}

document.querySelectorAll('.cat-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
        e.currentTarget.classList.add('active');

        const type = e.currentTarget.getAttribute('data-type');
        if (type === 'bus') currentLineType = 'BUS';
        else if (type === 'tram') currentLineType = 'TRAM';
        else if (type === 'trolley') currentLineType = 'TROLLEYBUS';

        // Optional: filter map markers immediately
        filters['BUS'] = type === 'bus';
        filters['TRAM'] = type === 'tram';
        filters['TROLLEYBUS'] = type === 'trolley';

        loadVehicles(); // refresh map

        // Open the popup to select a specific line
        openLinesPopup(currentLineType);
    });
});

function openLinesPopup(type) {
    if (!linesPopup) return;

    // Extract available lines of this type from allVehicles
    const linesOfType = [...new Set(allVehicles.filter(v => v.type === type).map(v => v.line))].sort((a,b) => parseInt(a) - parseInt(b));

    let title = 'Linii';
    if (type === 'BUS') title = 'Linii Autobuz';
    if (type === 'TRAM') title = 'Linii Tramvai';
    if (type === 'TROLLEYBUS') title = 'Linii Troleibuz';
    linesPopupTitle.innerText = title;

    linesPopupList.innerHTML = '';

    if (type === 'TRAM') {
        const allTramLines = ['1', '3', '5', '7', '10', '11', '14', '16', '19', '21', '23', '24', '25', '27', '32', '36', '40', '41', '44', '45', '47', '55'];
        allTramLines.forEach(l => {
            if (!linesOfType.includes(l)) linesOfType.push(l);
        });
        linesOfType.sort((a,b) => parseInt(a) - parseInt(b));
    }

    if (linesOfType.length === 0) {
        linesPopupList.innerHTML = '<div style="padding: 10px;">Nicio linie activă găsită momentan.</div>';
    } else {
        linesOfType.forEach(line => {
            const btn = document.createElement('button');
            btn.className = 'line-item-btn line-item';
            btn.setAttribute('data-line', line);
            btn.innerText = line;
            btn.addEventListener('click', () => {
                linesPopup.classList.add('hidden');
                document.getElementById('line-search').value = line;
                searchLine(line);
            });
            linesPopupList.appendChild(btn);
        });
    }

    linesPopup.classList.remove('hidden');
}

let globalCurrentLine = null;
let globalCurrentAdminId = null;
let currentDirection = 'dus';

const switchDirBtn = document.getElementById('bp-switch-dir');
if (switchDirBtn) {
    switchDirBtn.addEventListener('click', () => {
        currentDirection = currentDirection === 'dus' ? 'intors' : 'dus';
        if (globalCurrentLine) {
            searchLine(globalCurrentLine, globalCurrentAdminId);
        }
    });
}

async function searchLine(line, adminId = null) {
    if (!line) return;

    globalCurrentLine = line;
    globalCurrentAdminId = adminId;

    welcomeInfo.classList.add('hidden');
    stationInfo.classList.add('hidden');
    lineInfo.classList.remove('hidden');
    bottomPanel.classList.remove('hidden'); // Show floating panel

    timelineList.innerHTML = `<div class="loading">${i18n.loading}</div>`;
    try {
        let url = `api/lines.php?search=${encodeURIComponent(line)}&direction=${currentDirection}`;
        if (adminId) url += `&admin_id=${adminId}`;
        const response = await fetch(url);

        const result = await response.json();

        if (result.status !== 'success' || !result.data || result.data.length === 0) {
            timelineList.innerHTML = `<div class="loading">${i18n.no_vehicles}</div>`;
            bottomPanel.classList.add('hidden');
            return;
        }

        const routeData = result.data[0];

        let type = routeData.route_type == 0 ? 'TRAM' : (routeData.route_type == 11 ? 'TROLLEYBUS' : 'BUS');
        let icon = type === 'TRAM' ? 'fas fa-train-tram' : (type === 'TROLLEYBUS' ? 'fas fa-bus-simple' : 'fas fa-bus');
        let color = type === 'TRAM' ? 'var(--tram-red)' : (type === 'TROLLEYBUS' ? 'var(--trolley-green)' : 'var(--bus-blue)');

        // Fetch shape
        let shapeCoords = [];
        if (routeData.shape_id) {
            const shapeRes = await fetch(`api/lines.php?shape=${routeData.shape_id}`);
            const shapeResult = await shapeRes.json();
            if (shapeResult.status === 'success') shapeCoords = shapeResult.data;
        }

        // Mock stations (or fetch real if API provides)
        const realStations = [];
        if (routeData.stations && routeData.stations.length > 0) {
            routeData.stations.forEach(s => realStations.push(s));
        } else {
            // Mock fallback
            for (let i = 1; i <= 5; i++) {
                realStations.push({
                    name: `Stația ${i}`,
                    has_arrivals: i === 2 || i === 4,
                    next_arrival: i * 3,
                    other_arrivals: `${i*3 + 10} min, ${i*3 + 25} min`
                });
            }
        }

        const formattedResult = {
            line: routeData.route_short_name,
            type: type,
            icon: icon,
            color: color,
            direction: routeData.route_long_name || routeData.route_short_name,
            stations: realStations
        };

        renderTimelineUI(formattedResult, shapeCoords);

    } catch (error) {
        timelineList.innerHTML = '<div class="loading" style="color:red">Eroare conexiune server API.</div>';
    }
}

function renderTimelineUI(result, shapeCoordinates) {
    const badgeHtml = `<i class="${result.icon}"></i> <span style="margin-left:5px;">${result.line}</span>`;

    // Update sidebar header badge
    const headerBadge = document.getElementById('line-info-badge');
    headerBadge.innerHTML = badgeHtml;
    headerBadge.style.backgroundColor = result.color;

    // Update bottom panel
    const bpBadge = document.getElementById('bp-line-badge');
    bpBadge.innerHTML = badgeHtml;
    bpBadge.style.backgroundColor = result.color;
    document.getElementById('bp-direction-text').innerHTML = result.direction;

    // Build timeline - now we calculate progression based on live vehicles on this line
    let activeVehicles = allVehicles.filter(v => v.line === result.line);

    // Sort stations by order (they are already sorted from the API/Overpass)
    // Find the furthest vehicle on the path to mark stations as "passed"
    // For simplicity, we just use the first active vehicle for demonstration of progression
    let vehicleToTrack = activeVehicles.length > 0 ? activeVehicles[0] : null;

    // In a real app we'd use turf.js to find nearest point on line.
    // Here we'll do a simple distance check to find closest station to the vehicle.
    let closestStationIdx = -1;
    let minDistance = 999999;

    if (vehicleToTrack) {
        result.stations.forEach((station, idx) => {
            if (station.lat && station.lng) {
                let d = Math.sqrt(Math.pow(station.lat - vehicleToTrack.lat, 2) + Math.pow(station.lng - vehicleToTrack.lng, 2));
                if (d < minDistance) {
                    minDistance = d;
                    closestStationIdx = idx;
                }
            }
        });
    }

    let html = '';
    result.stations.forEach((station, idx) => {
        let stateClass = '';
        if (closestStationIdx !== -1) {
            if (idx < closestStationIdx) stateClass = 'passed';
            else if (idx === closestStationIdx) stateClass = 'active'; // Currently at
            else stateClass = 'upcoming';
        } else {
            stateClass = station.has_arrivals ? 'active' : 'upcoming';
        }

        let arrivalsHtml = '';
        if (station.has_arrivals) {
            arrivalsHtml = `
                <div style="text-align:right; font-size:12px;">
                    <div style="color:var(--stb-green); font-weight:bold; font-size:15px;">${station.next_arrival} min</div>
                </div>
            `;
        }

        html += `
            <div class="timeline-item ${stateClass}" data-idx="${idx}">
                <div class="timeline-marker"></div>
                <div class="timeline-content" style="margin-left:30px; width:100%; display:flex; justify-content:space-between;">
                    <div class="timeline-station">${station.name}</div>
                    ${arrivalsHtml}
                </div>
            </div>
        `;
    });
    timelineList.innerHTML = html;

    // Attach click event to timeline items to snap map to station
    document.querySelectorAll('.timeline-item').forEach(item => {
        item.addEventListener('click', (e) => {
            let idx = item.getAttribute('data-idx');
            let st = result.stations[idx];
            if (st && st.lat) {
                map.setView([st.lat, st.lng], 16);
            }
        });
    });

    if (currentRoutePolyline) map.removeLayer(currentRoutePolyline);

    // Draw route
    if (shapeCoordinates && shapeCoordinates.length > 0) {
        const latlngs = shapeCoordinates.map(p => [p.lat, p.lng]);
        currentRoutePolyline = L.polyline(latlngs, {color: result.color, weight: 6, opacity: 0.8}).addTo(map);
        map.fitBounds(currentRoutePolyline.getBounds());
    }
}

if (lineSearchInput) {
    lineSearchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchLine(lineSearchInput.value.trim());
        }
    });
}

function getColorByType(type) {
    if (type === 'TRAM') return 'var(--tram-red)';
    if (type === 'TROLLEYBUS') return 'var(--trolley-green)';
    return 'var(--bus-blue)';
}

function getIconByType(type) {
    if (type === 'TRAM') return 'fas fa-train-tram'; // Tramvai
    if (type === 'TROLLEYBUS') return 'fas fa-bus-simple'; // Troleibuz (same as the tabs)
    return 'fas fa-bus'; // Autobuz
}

function renderVehiclesOnMap(dataList) {
    vehiclesLayer.clearLayers();
    dataList.forEach(v => {
        if (!filters[v.type]) return;

        const color = getColorByType(v.type);
        const faIcon = getIconByType(v.type);

        const icon = L.divIcon({
            className: 'custom-div-icon',
            html: `<div class="vehicle-marker" style="background-color: ${color}; display:flex; flex-direction:column; align-items:center; justify-content:center; font-size:12px;">
                        <i class="${faIcon}" style="font-size:10px; margin-bottom:1px;"></i>
                        <span style="line-height:1;">${v.line}</span>
                   </div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 18]
        });

        const marker = L.marker([v.lat, v.lng], { icon: icon });

        // La click pe vehicul, simulăm căutarea/selecția liniei respective
        marker.on('click', () => {
            searchLine(v.line);
        });

        vehiclesLayer.addLayer(marker);
    });
}

let allVehicles = []; // Store globally for the popup menu

async function loadVehicles() {
    try {
        const response = await fetch('api/vehicles.php');
        const result = await response.json();

        if (result.status === 'success') {
            allVehicles = result.data;
            renderVehiclesOnMap(result.data);

            // If popup is open, refresh its content to show updated active lines
            const popupEl = document.getElementById('lines-popup');
            if (popupEl && !popupEl.classList.contains('hidden') && currentLineType) {
                openLinesPopup(currentLineType);
            }
        }
    } catch (e) {}
}

async function loadStations() {
    try {
        const response = await fetch('api/stations.php');
        const result = await response.json();

        if (result.status === 'success') {
            stationsLayer.clearLayers();
            result.data.forEach(s => {
                const icon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div class="station-marker"></div>`,
                    iconSize: [14, 14],
                    iconAnchor: [7, 7]
                });

                const marker = L.marker([s.lat, s.lng], { icon: icon });
                marker.on('click', () => { fetchStationArrivals(s.id, s.name); });
                stationsLayer.addLayer(marker);
            });
        }
    } catch (e) {}
}

async function fetchStationArrivals(stationId, stationName) {
    welcomeInfo.classList.add('hidden');
    lineInfo.classList.add('hidden');
    bottomPanel.classList.add('hidden');
    stationInfo.classList.remove('hidden');
    stationNameEl.textContent = stationName;
    arrivalsListEl.innerHTML = `<div class="loading">${i18n.loading}</div>`;

    try {
        const response = await fetch(`api/stations.php?id=${stationId}`);
        const result = await response.json();

        if (result.status === 'success') {
            if (result.arrivals.length === 0) {
                arrivalsListEl.innerHTML = `<div class="loading">${i18n.no_vehicles}</div>`;
                return;
            }

            let html = '';
            result.arrivals.forEach(a => {
                let color = a.type === 'TRAM' ? 'var(--tram-red)' : (a.type === 'TROLLEYBUS' ? 'var(--trolley-green)' : 'var(--bus-blue)');
                html += `
                    <div style="display:flex; align-items:center; padding:15px; border-bottom:1px solid #eee;">
                        <div style="background:${color}; color:white; width:45px; height:30px; display:flex; align-items:center; justify-content:center; border-radius:4px; font-weight:bold; margin-right:15px;">
                            ${a.line}
                        </div>
                        <div style="flex:1;">Sosire</div>
                        <div style="font-size:18px; font-weight:bold; color:var(--stb-green);">${a.minutes} <span style="font-size:14px; font-weight:normal; color:#777;">min</span></div>
                    </div>
                `;
            });
            arrivalsListEl.innerHTML = html;
        }
    } catch (e) {}
}

loadStations();
loadVehicles();
setInterval(loadVehicles, 10000);

// Dark Mode Toggle Logic
document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;

    // Load user preference from localStorage
    const currentMode = localStorage.getItem('theme-mode');
    if (currentMode === 'dark') {
        htmlElement.classList.add('dark-mode');
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            htmlElement.classList.toggle('dark-mode');
            if (htmlElement.classList.contains('dark-mode')) {
                localStorage.setItem('theme-mode', 'dark');
            } else {
                localStorage.setItem('theme-mode', 'light');
            }
        });
    }
});

let userLocationMarker = null;
let liveTrackingWatchId = null;
let isLiveTracking = false;

// Live Route Tracking logic
document.addEventListener('DOMContentLoaded', () => {
    const btnLiveTrack = document.getElementById('bp-live-track');
    if(btnLiveTrack) {
        btnLiveTrack.addEventListener('click', () => {
            if(!isLiveTracking) {
                // Start tracking
                if(!navigator.geolocation) {
                    alert('Geolocalizarea nu este suportată de browser.');
                    return;
                }

                isLiveTracking = true;
                btnLiveTrack.style.backgroundColor = '#2ecc71'; // Green active

                liveTrackingWatchId = navigator.geolocation.watchPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const latlng = L.latLng(lat, lng);

                        if(!userLocationMarker) {
                            const icon = L.divIcon({
                                className: 'custom-div-icon',
                                html: `<div style="background:#2980b9; width:16px; height:16px; border-radius:50%; border:3px solid white; box-shadow:0 0 5px rgba(0,0,0,0.5);"></div>`,
                                iconSize: [22, 22],
                                iconAnchor: [11, 11]
                            });
                            userLocationMarker = L.marker(latlng, {icon: icon, zIndexOffset: 1000}).addTo(map);
                            userLocationMarker.bindPopup("Poziția ta curentă");
                        } else {
                            userLocationMarker.setLatLng(latlng);
                        }

                        map.panTo(latlng);
                    },
                    (error) => {
                        console.error("GPS Error:", error);
                        alert("Eroare la preluarea locației GPS: " + error.message);
                    },
                    { enableHighAccuracy: true, maximumAge: 0, timeout: 10000 }
                );
            } else {
                // Stop tracking
                isLiveTracking = false;
                btnLiveTrack.style.backgroundColor = '#e74c3c'; // Back to red
                if(liveTrackingWatchId) {
                    navigator.geolocation.clearWatch(liveTrackingWatchId);
                    liveTrackingWatchId = null;
                }
                if(userLocationMarker) {
                    map.removeLayer(userLocationMarker);
                    userLocationMarker = null;
                }
            }
        });
    }
});

// Check for search parameter in URL or Custom Line
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);

    // Default TPBI line search
    const lineToSearch = urlParams.get('search');
    const adminId = urlParams.get('admin_id');

    if (lineToSearch) {
        if (lineSearchInput) {
            lineSearchInput.value = lineToSearch;
        }
        setTimeout(() => {
            searchLine(lineToSearch, adminId);
        }, 500); // Give map time to init
    }


    // Custom line from DB
    const customLineId = urlParams.get('custom_line_id');
    if (customLineId) {
        setTimeout(() => {
            loadCustomLine(customLineId);
        }, 500);
    }
});

async function loadCustomLine(id) {
    welcomeInfo.classList.add('hidden');
    stationInfo.classList.add('hidden');
    lineInfo.classList.remove('hidden');
    bottomPanel.classList.remove('hidden');

    timelineList.innerHTML = '<div class="loading">Se încarcă linia...</div>';

    if (currentRoutePolyline) map.removeLayer(currentRoutePolyline);

    try {
        // Fetch info
        const infoRes = await fetch(`api/custom_lines.php?action=get_info&line_id=${id}`);
        const infoResult = await infoRes.json();
        if (infoResult.error) throw new Error(infoResult.error);

        // Fetch route
        const routeRes = await fetch(`api/custom_lines.php?action=get_routes&line_id=${id}`);
        const routeResult = await routeRes.json();

        // Fetch markers
        const markersRes = await fetch(`api/custom_lines.php?action=get_markers&line_id=${id}`);
        const markersResult = await markersRes.json();

        const badgeHtml = `<i class="fas fa-bus-alt"></i> <span style="margin-left:5px;">${infoResult.name}</span>`;

        const headerBadge = document.getElementById('line-info-badge');
        if(headerBadge) {
            headerBadge.innerHTML = badgeHtml;
            headerBadge.style.backgroundColor = infoResult.color;
        }

        const bpBadge = document.getElementById('bp-line-badge');
        if(bpBadge) {
            bpBadge.innerHTML = badgeHtml;
            bpBadge.style.backgroundColor = infoResult.color;
        }

        const bpDirText = document.getElementById('bp-direction-text');
        if(bpDirText) {
            bpDirText.innerHTML = infoResult.description || 'Traseu Customizat';
        }

        // Draw route
        if (routeResult && routeResult.length > 0) {
            const latlngs = routeResult.map(p => [p.latitude, p.longitude]);
            currentRoutePolyline = L.polyline(latlngs, {color: infoResult.color, weight: 6, opacity: 0.8}).addTo(map);
            map.fitBounds(currentRoutePolyline.getBounds());
        }

        // Draw custom markers onto map and build timeline
        let html = '';

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

        if (markersResult && markersResult.length > 0) {
            markersResult.forEach(m => {
                // Add marker to map
                const marker = L.marker([m.latitude, m.longitude], {icon: markerIcons[m.type] || markerIcons['station']});
                marker.bindPopup(`<b>${infoResult.name}</b><br>${m.description}`);

                // Add to our route polyline group conceptually, but actually just add to map.
                // To keep it simple and clean up properly later, we should ideally put it in a LayerGroup.
                // We'll reuse currentRoutePolyline by making it a FeatureGroup if we have markers, or just add them to the map directly.
                // Let's add them to the map directly but we would need to track them to remove them.
                // For simplicity here, we create a temporary array attached to currentRoutePolyline
                if (!currentRoutePolyline.markers) currentRoutePolyline.markers = [];
                currentRoutePolyline.markers.push(marker);
                marker.addTo(map);

                // Add to timeline
                let markerIconTimeline = '';
                if(m.type === 'work') markerIconTimeline = '<i class="fas fa-hard-hat" style="color:#f39c12;"></i> ';
                else if(m.type === 'accident') markerIconTimeline = '<i class="fas fa-car-crash" style="color:#e74c3c;"></i> ';
                else if(m.type === 'detour') markerIconTimeline = '<i class="fas fa-directions" style="color:#9b59b6;"></i> ';
                else if(m.type === 'traffic') markerIconTimeline = '<i class="fas fa-traffic-light" style="color:#e67e22;"></i> ';
                else if(m.type === 'police') markerIconTimeline = '<i class="fas fa-user-shield" style="color:#2980b9;"></i> ';
                else if(m.type === 'interventie') markerIconTimeline = '<i class="fas fa-ambulance" style="color:#c0392b;"></i> ';
                else if(m.type === 'suspended') markerIconTimeline = '<i class="fas fa-ban" style="color:#000;"></i> ';
                else markerIconTimeline = '<i class="fas fa-map-marker-alt" style="color:#3498db;"></i> ';

                html += `
                    <div class="timeline-item">
                        <div class="timeline-marker" style="border-color:${infoResult.color};"></div>
                        <div class="timeline-content" style="margin-left:30px; width:100%;">
                            <div class="timeline-station">${markerIconTimeline} ${m.description || 'Punct pe traseu'}</div>
                        </div>
                    </div>
                `;
            });
        } else {
            html = '<div style="padding:20px; color:#666;">Acest traseu nu are marcaje sau stații definite.</div>';
        }

        timelineList.innerHTML = html;

        // Cleanup old markers when new route is loaded
        const originalRemove = map.removeLayer.bind(map);
        map.removeLayer = function(layer) {
            if (layer === currentRoutePolyline && layer.markers) {
                layer.markers.forEach(m => originalRemove(m));
            }
            originalRemove(layer);
        };

    } catch (e) {
        timelineList.innerHTML = `<div class="loading" style="color:red">Eroare: ${e.message}</div>`;
    }
}


// GPS Live User Location
let userMarker = null;
let userAccuracyCircle = null;

if ("geolocation" in navigator) {
    navigator.geolocation.watchPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;

            if (!userMarker) {
                // Initialize user marker
                const userIcon = L.divIcon({
                    className: 'user-gps-marker',
                    html: '<div style="background-color: #3498db; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5);"></div>',
                    iconSize: [22, 22],
                    iconAnchor: [11, 11]
                });
                userMarker = L.marker([lat, lng], {icon: userIcon, zIndexOffset: 1000}).addTo(map);
                userAccuracyCircle = L.circle([lat, lng], {radius: accuracy, color: '#3498db', fillColor: '#3498db', fillOpacity: 0.15, weight: 1}).addTo(map);
            } else {
                // Update position
                userMarker.setLatLng([lat, lng]);
                userAccuracyCircle.setLatLng([lat, lng]);
                userAccuracyCircle.setRadius(accuracy);
            }
        },
        function(error) {
            console.log("Geolocation error: ", error.message);
        },
        {
            enableHighAccuracy: true,
            maximumAge: 10000,
            timeout: 5000
        }
    );
}
