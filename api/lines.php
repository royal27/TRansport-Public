<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$line = $_GET['q'] ?? '';

if (empty($line)) {
    echo json_encode(['status' => 'error', 'message' => 'Line parameter missing']);
    exit;
}

// Simulăm datele
// Pentru exemplul cerut (linia 32): Piata Unirii -> Depoul Alexandria
$mockStations = [
    'Piata Unirii',
    '11 Iunie',
    'Piata Regina Maria',
    'Piata Chirigiu',
    'Soseaua Progresului',
    'Calea Ferentari',
    'Petre Ispirescu',
    'Piata Rahova',
    'Margeanului',
    'Depoul Alexandria'
];

$type = 'BUS';
$icon = 'fas fa-bus';

// Simple rules to determine type based on string
if ($line == '32' || $line == '1' || $line == '41') {
    $type = 'TRAM';
    $icon = 'fas fa-train-tram';
} else if (intval($line) >= 60 && intval($line) <= 99) {
    $type = 'TROLLEYBUS';
    $icon = 'fas fa-bus-simple';
}

$stationsData = [];
foreach ($mockStations as $index => $stationName) {
    $station = [
        'name' => $stationName,
        'has_arrivals' => false
    ];

    // Prima stație are simulată sosirea
    if ($index === 0) {
        $station['has_arrivals'] = true;
        $station['next_arrival'] = mt_rand(1, 5); // 1-5 min
        $now = time();
        $station['other_arrivals'] = date('H:i', $now + (mt_rand(10, 15) * 60)) . ', ' . date('H:i', $now + (mt_rand(20, 30) * 60));
    }

    $stationsData[] = $station;
}

echo json_encode([
    'status' => 'success',
    'line' => $line,
    'type' => $type,
    'icon' => $icon,
    'direction' => $mockStations[0] . ' &rarr; ' . end($mockStations),
    'stations' => $stationsData
]);
?>