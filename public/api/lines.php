<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$shape_id = $_GET['shape'] ?? '';
if (!empty($shape_id)) {
    // Generate some mock polyline coordinates for Bucharest center
    $coords = [
        ['lat' => 44.4396, 'lng' => 26.0963],
        ['lat' => 44.4377, 'lng' => 26.0986],
        ['lat' => 44.4343, 'lng' => 26.1030],
        ['lat' => 44.4300, 'lng' => 26.1054],
        ['lat' => 44.4268, 'lng' => 26.1025]
    ];
    echo json_encode(['status' => 'success', 'data' => $coords]);
    exit;
}

$line = $_GET['search'] ?? '';

if (empty($line)) {
    echo json_encode(['status' => 'error', 'message' => 'Line parameter missing']);
    exit;
}

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
$route_type = 3;

if ($line == '32' || $line == '1' || $line == '41') {
    $type = 'TRAM';
    $route_type = 0;
} else if (intval($line) >= 60 && intval($line) <= 99) {
    $type = 'TROLLEYBUS';
    $route_type = 11;
}

$stationsData = [];
foreach ($mockStations as $index => $stationName) {
    $station = [
        'name' => $stationName,
        'has_arrivals' => false
    ];

    if ($index === 0) {
        $station['has_arrivals'] = true;
        $station['next_arrival'] = mt_rand(1, 5);
        $now = time();
        $station['other_arrivals'] = date('H:i', $now + (mt_rand(10, 15) * 60)) . ', ' . date('H:i', $now + (mt_rand(20, 30) * 60));
    }

    $stationsData[] = $station;
}

echo json_encode([
    'status' => 'success',
    'data' => [
        [
            'route_short_name' => $line,
            'route_long_name' => $mockStations[0] . ' -> ' . end($mockStations),
            'route_type' => $route_type,
            'shape_id' => 'mock_shape_' . $line,
            'stations' => $stationsData
        ]
    ]
]);
?>