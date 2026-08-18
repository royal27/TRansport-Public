
const map = L.map('map').setView([44.4268, 26.1025], 13);
L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
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

// Setup Category Tabs
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
    });
});

async function searchLine(line) {
    if (!line) return;

    welcomeInfo.classList.add('hidden');
    stationInfo.classList.add('hidden');
    lineInfo.classList.remove('hidden');
    bottomPanel.classList.remove('hidden'); // Show floating panel

    timelineList.innerHTML = `<div class="loading">${i18n.loading}</div>`;

    try {
        const response = await fetch(`api/lines.php?search=${encodeURIComponent(line)}`);
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

    // Build timeline
    let html = '';
    result.stations.forEach((station) => {
        let activeClass = station.has_arrivals ? 'active' : '';
        let arrivalsHtml = '';
        if (station.has_arrivals) {
            arrivalsHtml = `
                <div style="text-align:right; font-size:12px;">
                    <div style="color:var(--stb-green); font-weight:bold; font-size:15px;">${station.next_arrival} min</div>
                </div>
            `;
        }

        html += `
            <div class="timeline-item ${activeClass}">
                <div class="timeline-marker"></div>
                <div class="timeline-content" style="margin-left:30px; width:100%; display:flex; justify-content:space-between;">
                    <div class="timeline-station">${station.name}</div>
                    ${arrivalsHtml}
                </div>
            </div>
        `;
    });
    timelineList.innerHTML = html;

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

function renderVehiclesOnMap(dataList) {
    vehiclesLayer.clearLayers();
    dataList.forEach(v => {
        if (!filters[v.type]) return;

        const color = getColorByType(v.type);
        const icon = L.divIcon({
            className: 'custom-div-icon',
            html: `<div class="vehicle-marker" style="background-color: ${color}">${v.line}</div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        const marker = L.marker([v.lat, v.lng], { icon: icon });
        marker.bindPopup(`<b>Linia ${v.line}</b>`);
        vehiclesLayer.addLayer(marker);
    });
}

async function loadVehicles() {
    try {
        const response = await fetch('api/vehicles.php');
        const result = await response.json();

        if (result.status === 'success' && result.data_source !== 'mock_data') {
            renderVehiclesOnMap(result.data);
            return;
        }

        if (result.try_frontend_fetch) {
            try {
                let headers = { 'Accept': 'application/json' };
                if (result.tpbi_api_key) headers['Authorization'] = 'Bearer ' + result.tpbi_api_key;

                const mobiResponse = await fetch('https://mo-bi.ro/api/v1/vehicles', { headers: headers });

                if (mobiResponse.ok) {
                    const realData = await mobiResponse.json();
                    if (realData.data) {
                        const parsedVehicles = realData.data.map(v => {
                            let type = 'BUS';
                            if (v.route_type == 0) type = 'TRAM';
                            else if (v.route_type == 11) type = 'TROLLEYBUS';
                            return { id: v.vehicle_id, line: v.route_short_name || '?', type: type, lat: v.latitude, lng: v.longitude };
                        });
                        renderVehiclesOnMap(parsedVehicles);
                        return;
                    }
                }
            } catch (err) {}
        }

        if (result.status === 'success') renderVehiclesOnMap(result.data);
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

// Check for search parameter in URL
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const lineToSearch = urlParams.get('search');
    if (lineToSearch) {
        if (lineSearchInput) {
            lineSearchInput.value = lineToSearch;
        }
        setTimeout(() => {
            searchLine(lineToSearch);
        }, 500); // Give map time to init
    }
});

// OpenWeatherMap Integration
document.addEventListener('DOMContentLoaded', () => {
    if (typeof weatherApiKey !== 'undefined' && weatherApiKey.trim() !== '') {
        const city = 'Bucharest';
        const url = `https://api.openweathermap.org/data/2.5/weather?q=${city}&units=metric&appid=${weatherApiKey}&lang=ro`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data && data.main && data.main.temp) {
                    const temp = Math.round(data.main.temp);
                    const desc = data.weather[0].description;
                    document.getElementById('tb-weather').innerHTML = `${temp}°C, ${desc}`;
                }
            })
            .catch(error => {
                console.error("Error fetching weather:", error);
            });
    }
});
