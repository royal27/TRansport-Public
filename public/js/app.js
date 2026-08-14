// Setari initiale harta
const map = L.map('map').setView([44.4396, 26.0963], 13); // Centrat pe Bucuresti

// Adaugare tile layer (OpenStreetMap)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Grupuri pentru markere pentru a le putea sterge usor la update
const vehiclesLayer = L.layerGroup().addTo(map);
const stationsLayer = L.layerGroup().addTo(map);

// Stare filtre
const filters = {
    'BUS': true,
    'TRAM': true,
    'TROLLEYBUS': true
};

const filterBus = document.getElementById('filter-bus');
const filterTram = document.getElementById('filter-tram');
const filterTrolley = document.getElementById('filter-trolley');

if (filterBus) {
    filterBus.addEventListener('change', (e) => { filters['BUS'] = e.target.checked; loadVehicles(); });
    filterTram.addEventListener('change', (e) => { filters['TRAM'] = e.target.checked; loadVehicles(); });
    filterTrolley.addEventListener('change', (e) => { filters['TROLLEYBUS'] = e.target.checked; loadVehicles(); });
}

// Referinte UI
const welcomeInfo = document.getElementById('welcome-info');
const stationInfo = document.getElementById('station-info');
const stationNameEl = document.getElementById('station-name');
const arrivalsListEl = document.getElementById('arrivals-list');
const btnBack = document.getElementById('btn-back');

const lineInfo = document.getElementById('line-info');
const btnBackLine = document.getElementById('btn-back-line');
const lineSearchInput = document.getElementById('line-search');
const searchBtn = lineSearchInput ? lineSearchInput.nextElementSibling : null;

// Event listeners UI
btnBack.addEventListener('click', () => {
    stationInfo.classList.add('hidden');
    welcomeInfo.classList.remove('hidden');
});

if (btnBackLine) {
    btnBackLine.addEventListener('click', () => {
        lineInfo.classList.add('hidden');
        welcomeInfo.classList.remove('hidden');
        lineSearchInput.value = '';
    });
}

// Logică de căutare linie
async function searchLine(line) {
    if (!line) return;

    // Hide other panels
    welcomeInfo.classList.add('hidden');
    stationInfo.classList.add('hidden');
    lineInfo.classList.remove('hidden');

    const timelineList = document.getElementById('timeline-list');
    timelineList.innerHTML = `<div class="loading">${i18n.loading || 'Se încarcă...'}</div>`;

    try {
        const response = await fetch(`/api/lines.php?q=${line}`);
        const result = await response.json();

        if (result.status === 'success') {
            // Update Header
            document.getElementById('line-info-badge').innerHTML = `<i class="${result.icon}"></i> <span>${result.line}</span>`;

            // Culoare badge în funcție de tip
            const badge = document.getElementById('line-info-badge');
            if (result.type === 'TRAM') badge.style.borderColor = 'var(--tram)';
            else if (result.type === 'TROLLEYBUS') badge.style.borderColor = 'var(--trolley)';
            else badge.style.borderColor = 'var(--bus)';

            document.getElementById('line-direction-text').innerHTML = result.direction;

            // Build timeline
            let html = '';
            result.stations.forEach((station, index) => {
                let activeClass = station.has_arrivals ? 'active' : '';

                let arrivalsHtml = '';
                if (station.has_arrivals) {
                    arrivalsHtml = `
                        <div class="timeline-arrivals">
                            <div class="next-arrival-title">${i18n.next_arrivals || 'Următoarele sosiri'}</div>
                            <div class="next-arrival-time">${station.next_arrival} <span>${i18n.min || 'min'}</span></div>
                            <div class="other-arrivals">${i18n.other_arrivals || 'Alte sosiri programate: '}${station.other_arrivals}</div>
                        </div>
                    `;
                }

                html += `
                    <div class="timeline-item ${activeClass}">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="timeline-station">${station.name}</div>
                            ${arrivalsHtml}
                        </div>
                    </div>
                `;
            });

            timelineList.innerHTML = html;
        } else {
            timelineList.innerHTML = '<div class="loading" style="color:red">Nu s-a găsit linia.</div>';
        }
    } catch (error) {
        timelineList.innerHTML = '<div class="loading" style="color:red">Eroare la căutare.</div>';
    }
}

if (searchBtn && lineSearchInput) {
    searchBtn.addEventListener('click', () => {
        searchLine(lineSearchInput.value.trim());
    });

    lineSearchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchLine(lineSearchInput.value.trim());
        }
    });
}

// Functie pt culori pe baza de tip vehicul
function getColorByType(type) {
    if (type === 'TRAM') return 'var(--tram)';
    if (type === 'TROLLEYBUS') return 'var(--trolley)';
    return 'var(--bus)';
}

// Randeaza vehicule pe harta
function renderVehiclesOnMap(dataList) {
    vehiclesLayer.clearLayers();

    dataList.forEach(v => {
        // Aplică filtrele
        if (!filters[v.type]) return;

        const color = getColorByType(v.type);

        // Creare iconita custom cu HTML
        const icon = L.divIcon({
            className: 'custom-div-icon',
            html: `<div class="vehicle-marker" style="background-color: ${color}">${v.line}</div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        const marker = L.marker([v.lat, v.lng], { icon: icon });

        // Popup simplu
        marker.bindPopup(`<b>Linia ${v.line}</b><br>Tip: ${v.type}<br>Viteza: ${v.speed} km/h`);

        vehiclesLayer.addLayer(marker);
    });
}

// Incarcare vehicule
async function loadVehicles() {
    try {
        // Incepem prin a cere backend-ului nostru (pentru cheie si fallback)
        const response = await fetch('/api/vehicles.php');
        const result = await response.json();

        // Daca backend-ul returneaza date REALE, le folosim
        if (result.status === 'success' && result.data_source !== 'mock_data') {
            renderVehiclesOnMap(result.data);
            return;
        }

        // Incercam preluarea din Frontend direct
        // Chiar daca nu avem cheie (API open conform TPBI), trimitem requestul
        if (result.try_frontend_fetch) {
            try {
                let headers = {
                    'Accept': 'application/json'
                };
                if (result.tpbi_api_key) {
                    headers['Authorization'] = 'Bearer ' + result.tpbi_api_key;
                }

                const mobiResponse = await fetch('https://mo-bi.ro/api/v1/vehicles', {
                    headers: headers
                });

                if (mobiResponse.ok) {
                    const realData = await mobiResponse.json();
                    if (realData.data) {
                        const parsedVehicles = realData.data.map(v => {
                            let type = 'BUS';
                            if (v.route_type == 0) type = 'TRAM';
                            else if (v.route_type == 11) type = 'TROLLEYBUS';

                            return {
                                id: v.vehicle_id,
                                line: v.route_short_name || '?',
                                type: type,
                                lat: v.latitude,
                                lng: v.longitude,
                                heading: v.bearing,
                                speed: v.speed
                            };
                        });
                        renderVehiclesOnMap(parsedVehicles);
                        return; // Oprim aici daca a mers frontend fetch
                    }
                }
            } catch (frontendErr) {
                console.warn('Frontend direct fetch failed (CORS/Cloudflare):', frontendErr);
            }
        }

        // Fallback: folosim datele trimise de backend (mock_data) daca tot restul a picat
        if (result.status === 'success') {
            renderVehiclesOnMap(result.data);
        }

    } catch (error) {
        console.error('Eroare la incarcare vehicule (Complet):', error);
    }
}

// Incarcare statii
async function loadStations() {
    try {
        const response = await fetch('/api/stations.php');
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

                // La click pe statie cerem detaliile
                marker.on('click', () => {
                    fetchStationArrivals(s.id, s.name);
                });

                stationsLayer.addLayer(marker);
            });
        }
    } catch (error) {
        console.error('Eroare la incarcare statii:', error);
    }
}

// Fetch sosiri pentru o statie (Ce vine la statia mea)
async function fetchStationArrivals(stationId, stationName) {
    // UI Update
    welcomeInfo.classList.add('hidden');
    stationInfo.classList.remove('hidden');
    stationNameEl.textContent = stationName;
    arrivalsListEl.innerHTML = `<div class="loading">${i18n.loading}</div>`;

    try {
        const response = await fetch(`/api/stations.php?id=${stationId}`);
        const result = await response.json();

        if (result.status === 'success') {
            if (result.arrivals.length === 0) {
                arrivalsListEl.innerHTML = `<div class="loading">${i18n.no_vehicles}</div>`;
                return;
            }

            let html = '';
            const iconMap = {
                'BUS': '<i class="fas fa-bus"></i>',
                'TRAM': '<i class="fas fa-train-tram"></i>',
                'TROLLEYBUS': '<i class="fas fa-bus-simple"></i>' // placeholder if no trolley icon
            };

            result.arrivals.forEach(a => {
                html += `
                    <div class="arrival-item">
                        <div class="route-badge type-${a.type}">
                            ${a.line}
                        </div>
                        <div class="arrival-details">
                            <div class="route-type">${iconMap[a.type] || ''} ${i18n.estimated_arrival}</div>
                        </div>
                        <div class="arrival-time">
                            ${a.minutes} <span>${i18n.min}</span>
                        </div>
                    </div>
                `;
            });

            arrivalsListEl.innerHTML = html;
        }
    } catch (error) {
        console.error('Eroare la preluare sosiri:', error);
        arrivalsListEl.innerHTML = `<div class="loading" style="color:red">${i18n.loading} (Error)</div>`;
    }
}

// Init
loadStations();
loadVehicles();

// Polling pt vehicule la fiecare 5 secunde
setInterval(loadVehicles, 5000);