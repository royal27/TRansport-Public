<?php
// Dictionary for translations
$translations = [
    'ro' => [
        'app_name' => 'București Transport Live',
        'subtitle' => 'Vezi instant ce vine la stația ta.',
        'btn_schedules' => 'Vezi orar și linii',
        'btn_flights' => 'Găsește zboruri',
        'btn_map' => 'Hartă Live',
        'weather' => 'Vremea',
        'footer_text' => 'CopyRight Transport 2026 By Stoian rudolf',
        'click_station' => 'Apasă pe o stație pe hartă pentru a vedea următoarele sosiri.',
        'loading' => 'Se încarcă...',
        'no_vehicles' => 'Nu există vehicule programate în curând.',
        'estimated_arrival' => 'Sosire estimată',
        'min' => 'min',
        'station_name' => 'Nume Stație',
        'flights_title' => 'Zboruri din București (Otopeni)',
        'flight_number' => 'Număr Zbor',
        'destination' => 'Destinație',
        'departure_time' => 'Ora Plecării',
        'status' => 'Status',
        'schedules_title' => 'Orar și Linii Curente',
        'line' => 'Linie',
        'schedule_details' => 'Detalii Orar',
        'filter_bus' => 'Autobuze',
        'filter_tram' => 'Tramvaie',
        'filter_trolley' => 'Troleibuze',
        'btn_metro' => 'Metrou'
    ],
    'en' => [
        'app_name' => 'Bucharest Live Transport',
        'subtitle' => 'See instantly what arrives at your station.',
        'btn_schedules' => 'View Schedules & Lines',
        'btn_flights' => 'Find Flights',
        'btn_map' => 'Live Map',
        'weather' => 'Weather',
        'footer_text' => 'CopyRight Transport 2026 By Stoian rudolf',
        'click_station' => 'Click on a station on the map to see next arrivals.',
        'loading' => 'Loading...',
        'no_vehicles' => 'No vehicles scheduled soon.',
        'estimated_arrival' => 'Estimated arrival',
        'min' => 'min',
        'station_name' => 'Station Name',
        'flights_title' => 'Flights from Bucharest (Otopeni)',
        'flight_number' => 'Flight Number',
        'destination' => 'Destination',
        'departure_time' => 'Departure Time',
        'status' => 'Status',
        'schedules_title' => 'Schedules & Current Lines',
        'line' => 'Line',
        'schedule_details' => 'Schedule Details',
        'filter_bus' => 'Buses',
        'filter_tram' => 'Trams',
        'filter_trolley' => 'Trolleybuses',
        'btn_metro' => 'Metro'
    ],
    'fr' => [
        'app_name' => 'Transport en direct de Bucarest',
        'subtitle' => 'Voyez instantanément ce qui arrive à votre station.',
        'btn_schedules' => 'Voir horaires et lignes',
        'btn_flights' => 'Trouver des vols',
        'btn_map' => 'Carte en direct',
        'weather' => 'Météo',
        'footer_text' => 'CopyRight Transport 2026 By Stoian rudolf',
        'click_station' => 'Cliquez sur une station sur la carte pour voir les prochaines arrivées.',
        'loading' => 'Chargement...',
        'no_vehicles' => 'Aucun véhicule prévu prochainement.',
        'estimated_arrival' => 'Arrivée estimée',
        'min' => 'min',
        'station_name' => 'Nom de la station',
        'flights_title' => 'Vols au départ de Bucarest (Otopeni)',
        'flight_number' => 'Numéro de vol',
        'destination' => 'Destination',
        'departure_time' => 'Heure de départ',
        'status' => 'Statut',
        'schedules_title' => 'Horaires et lignes actuelles',
        'line' => 'Ligne',
        'schedule_details' => 'Détails des horaires',
        'filter_bus' => 'Bus',
        'filter_tram' => 'Tramways',
        'filter_trolley' => 'Trolleybus',
        'btn_metro' => 'Métro'
    ]
];

function getTranslation($key, $lang = 'ro') {
    global $translations;
    if (!isset($translations[$lang])) {
        $lang = 'ro';
    }
    return $translations[$lang][$key] ?? $key;
}
?>