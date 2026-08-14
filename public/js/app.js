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

// Referinte UI
const welcomeInfo = document.getElementById('welcome-info');
const stationInfo = document.getElementById('station-info');
const stationNameEl = document.getElementById('station-name');
const arrivalsListEl = document.getElementById('arrivals-list');
const btnBack = document.getElementById('btn-back');

// Event listeners UI
btnBack.addEventListener('click', () => {
    stationInfo.classList.add('hidden');
    welcomeInfo.classList.remove('hidden');
});

// Functie pt culori pe baza de tip vehicul
function getColorByType(type) {
    if (type === 'TRAM') return 'var(--tram)';
    if (type === 'TROLLEYBUS') return 'var(--trolley)';
    return 'var(--bus)';
}

// Incarcare vehicule
async function loadVehicles() {
    try {
        const response = await fetch('/api/vehicles.php');
        const result = await response.json();

        if (result.status === 'success') {
            vehiclesLayer.clearLayers();

            result.data.forEach(v => {
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
    } catch (error) {
        console.error('Eroare la incarcare vehicule:', error);
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