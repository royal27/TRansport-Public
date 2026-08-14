<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$stationId = $_GET['id'] ?? null;

// Mock date statii
$stations = [
    'S001' => ['name' => 'Piața Victoriei', 'lat' => 44.4526, 'lng' => 26.0873],
    'S002' => ['name' => 'Piața Romană', 'lat' => 44.4442, 'lng' => 26.0975],
    'S003' => ['name' => 'Universitate', 'lat' => 44.4355, 'lng' => 26.1025],
    'S004' => ['name' => 'Piața Unirii', 'lat' => 44.4268, 'lng' => 26.1025],
    'S005' => ['name' => 'Gara de Nord', 'lat' => 44.4463, 'lng' => 26.0745],
];

if ($stationId) {
    if (!isset($stations[$stationId])) {
        echo json_encode(['status' => 'error', 'message' => 'Statia nu a fost gasita']);
        exit;
    }

    // Generăm sosiri random (ce vine la statia mea)
    $arrivals = [];
    $lines = ['335', '79', '1', '131', '381', '41'];
    $types = ['BUS', 'TROLLEYBUS', 'TRAM', 'BUS', 'BUS', 'TRAM'];

    // Genereaza intre 3 si 6 sosiri
    $numArrivals = mt_rand(3, 6);
    for ($i = 0; $i < $numArrivals; $i++) {
        $lineIdx = array_rand($lines);
        $minutes = mt_rand(1, 20); // sosire in 1-20 min

        $arrivals[] = [
            'line' => $lines[$lineIdx],
            'type' => $types[$lineIdx],
            'minutes' => $minutes
        ];
    }

    // Sortam dupa minute
    usort($arrivals, function($a, $b) {
        return $a['minutes'] <=> $b['minutes'];
    });

    echo json_encode([
        'status' => 'success',
        'station' => $stations[$stationId],
        'arrivals' => $arrivals
    ]);
    exit;
}

// Daca nu e cerut un ID anume, returnam toate statiile
$stationsList = [];
foreach ($stations as $id => $data) {
    $data['id'] = $id;
    $stationsList[] = $data;
}

echo json_encode([
    'status' => 'success',
    'data' => $stationsList
]);
?>