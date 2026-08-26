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
    } elseif (strpos($shape_id, 'overpass_') === 0) {
        // Retrieve cached shape from session or temporary cache file
        $safe_shape_id = basename($shape_id);
        $cacheFile = '/tmp/' . $safe_shape_id . '.json';
        if (file_exists($cacheFile)) {
            $coords = json_decode(file_get_contents($cacheFile), true);
            echo json_encode(['status' => 'success', 'data' => $coords]);
            die();
        }
    }

    // Fallback if not found
    $coords = [];
    echo json_encode(['status' => 'success', 'data' => $coords]);
    die();
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

$type = 'BUS';
$route_type = 3;

if ($line == '32' || $line == '1' || $line == '41' || (intval($line) > 0 && intval($line) < 60)) {
    $type = 'TRAM';
    $route_type = 0;
} else if (intval($line) >= 60 && intval($line) <= 99) {
    $type = 'TROLLEYBUS';
    $route_type = 11;
}

// Fetch real route from Overpass API
$cacheFileMain = '/tmp/overpass_route_' . md5($line . $direction) . '.json';
$shapeId = 'overpass_' . md5($line . $direction);

if (file_exists($cacheFileMain) && filemtime($cacheFileMain) > time() - 86400) {
    // Return cached data
    $result = json_decode(file_get_contents($cacheFileMain), true);
    echo json_encode(['status' => 'success', 'data' => [$result]]);
    die();
}

$dirIdx = ($direction === 'intors') ? 1 : 0;
$query = '[out:json];
area["name"="București"]->.searchArea;
relation["type"="route"]["route"~"tram|bus|trolleybus"]["ref"="' . $line . '"](area.searchArea);
out geom;
(node(r););
out tags;
';

$url = 'https://overpass-api.de/api/interpreter';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "data=" . urlencode($query));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "BucurestiTransportLive/1.0");
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);

$nodeMap = [];
$relations = [];

if (isset($data['elements'])) {
    foreach($data['elements'] as $el) {
        if ($el['type'] == 'node') {
            $nodeMap[$el['id']] = $el['tags']['name'] ?? 'Stație';
        } elseif ($el['type'] == 'relation') {
            $relations[] = $el;
        }
    }
}

$stationsData = [];
$shapeData = [];
$longName = "Traseu " . $line;

if (!empty($relations)) {
    $rel = $relations[$dirIdx] ?? $relations[0];
    if (isset($rel['tags']['name'])) {
        $longName = $rel['tags']['name'];
    } elseif (isset($rel['tags']['from']) && isset($rel['tags']['to'])) {
        $longName = $rel['tags']['from'] . ' -> ' . $rel['tags']['to'];
    }

    foreach ($rel['members'] as $mem) {
        if ($mem['type'] == 'way' && isset($mem['geometry'])) {
            foreach ($mem['geometry'] as $pt) {
                $shapeData[] = ['lat' => $pt['lat'], 'lng' => $pt['lon']];
            }
        }

        if (($mem['role'] == 'stop' || $mem['role'] == 'platform' || $mem['role'] == 'stop_entry_only' || $mem['role'] == 'stop_exit_only') && isset($mem['lat'])) {
            $stName = $nodeMap[$mem['ref']] ?? 'Stație';
            // Mock some arrivals for presentation
            $stationsData[] = [
                'name' => $stName,
                'has_arrivals' => (mt_rand(0, 10) > 7),
                'next_arrival' => mt_rand(1, 15),
                'lat' => $mem['lat'],
                'lng' => $mem['lon']
            ];
        }
    }
}

if (empty($stationsData)) {
    // Overpass failed or line not found, fallback to basic mock
    $stationsData = [
        ['name' => 'Stație Start', 'has_arrivals' => true, 'next_arrival' => 2],
        ['name' => 'Stație Intermediară', 'has_arrivals' => false],
        ['name' => 'Stație Finală', 'has_arrivals' => false]
    ];
}

// Save shape for shape endpoint
file_put_contents('/tmp/' . $shapeId . '.json', json_encode($shapeData));

$resultData = [
    'route_short_name' => $line,
    'route_long_name' => $longName,
    'route_type' => $route_type,
    'shape_id' => $shapeId,
    'stations' => $stationsData
];

// Save to main cache
file_put_contents($cacheFileMain, json_encode($resultData));

echo json_encode([
    'status' => 'success',
    'data' => [$resultData]
]);
?>
