<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../includes/db.php';
$db = getDB();

$admin_id = $_GET['admin_id'] ?? 0;
$line = $_GET['search'] ?? '';
$direction = $_GET['direction'] ?? 'dus';

// Handle shape request
$shape_id = $_GET['shape'] ?? '';
if (!empty($shape_id)) {
    if (strpos($shape_id, 'admin_') === 0) {
        $id = str_replace('admin_', '', $shape_id);
        $parts = explode('_', $id);
        $s_id = $parts[0] ?? 0;
        $dir = $parts[1] ?? 'dus';

        $stmt = $db->prepare("SELECT latitude as lat, longitude as lng FROM schedule_stations WHERE schedule_id = ? AND direction = ? ORDER BY order_idx ASC");
        $stmt->execute([$s_id, $dir]);
        $coords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $coords]);
        die();
    } else {
        // Generate some mock polyline coordinates for Bucharest center
        $coords = [
            ['lat' => 44.4396, 'lng' => 26.0963],
            ['lat' => 44.4377, 'lng' => 26.0986],
            ['lat' => 44.4343, 'lng' => 26.1030],
            ['lat' => 44.4300, 'lng' => 26.1054],
            ['lat' => 44.4268, 'lng' => 26.1025]
        ];
        echo json_encode(['status' => 'success', 'data' => $coords]);
        die();
    }
}

if (!empty($admin_id)) {
    $stmt = $db->prepare("SELECT * FROM schedules WHERE id = ?");
    $stmt->execute([$admin_id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($schedule) {
        $stmt_st = $db->prepare("SELECT * FROM schedule_stations WHERE schedule_id = ? AND direction = ? ORDER BY order_idx ASC");
        $stmt_st->execute([$admin_id, $direction]);
        $db_stations = $stmt_st->fetchAll(PDO::FETCH_ASSOC);

        $stationsData = [];
        foreach ($db_stations as $st) {
            $stationsData[] = [
                'name' => $st['name'],
                'has_arrivals' => false,
                'lat' => $st['latitude'],
                'lng' => $st['longitude']
            ];
        }

        $route_type = 3;
        if ($schedule['category'] === 'TRAM') $route_type = 0;
        if ($schedule['category'] === 'TROLLEYBUS') $route_type = 11;

        echo json_encode([
            'status' => 'success',
            'data' => [
                [
                    'route_short_name' => $schedule['line_name'],
                    'route_long_name' => count($stationsData) > 0 ? ($stationsData[0]['name'] . ' -> ' . end($stationsData)['name']) : $schedule['line_name'],
                    'route_type' => $route_type,
                    'shape_id' => 'admin_' . $admin_id . '_' . $direction,
                    'stations' => $stationsData
                ]
            ]
        ]);
        die();
    }
}

if (empty($line)) {
    echo json_encode(['status' => 'error', 'message' => 'Line parameter missing']);
    die();
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
