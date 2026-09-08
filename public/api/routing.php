<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../includes/db.php';

$startLat = $_GET['start_lat'] ?? null;
$startLng = $_GET['start_lng'] ?? null;
$endLat = $_GET['end_lat'] ?? null;
$endLng = $_GET['end_lng'] ?? null;

if (!$startLat || !$startLng || !$endLat || !$endLng) {
    die(json_encode(['error' => 'Missing coordinates']));
}

function dist($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

$directWalkDist = dist($startLat, $startLng, $endLat, $endLng);

if ($directWalkDist < 1000) {
    $time = round($directWalkDist / 1.4 / 60);
    die(json_encode([
        'status' => 'success',
        'route' => [
            'total_time_mins' => $time,
            'segments' => [
                [
                    'type' => 'WALK',
                    'instruction' => 'Mergi pe jos spre destinație',
                    'distance' => round($directWalkDist) . 'm',
                    'time' => $time . ' min'
                ]
            ]
        ]
    ]));
}

$timeWalk1 = mt_rand(3, 8);
$timeWait = mt_rand(2, 7);
$timeRide = round($directWalkDist / 5.5 / 60);
$timeWalk2 = mt_rand(2, 5);

$total = $timeWalk1 + $timeWait + $timeRide + $timeWalk2;

$mockLines = ['335', '41', '1', '10', '32', '104', '381', '79'];
$line = $mockLines[array_rand($mockLines)];
$type = 'Autobuzul';
if (in_array($line, ['41', '1', '10', '32'])) $type = 'Tramvaiul';
if (in_array($line, ['79'])) $type = 'Troleibuzul';

die(json_encode([
    'status' => 'success',
    'route' => [
        'total_time_mins' => $total,
        'segments' => [
            [
                'type' => 'WALK',
                'instruction' => 'Mergi pe jos până la stația cea mai apropiată',
                'distance' => ($timeWalk1 * 80) . 'm',
                'time' => $timeWalk1 . ' min'
            ],
            [
                'type' => 'TRANSIT',
                'line' => $line,
                'vehicle_type' => $type,
                'instruction' => "Așteaptă $type $line (aprox. $timeWait min) și mergi " . mt_rand(3, 8) . " stații",
                'time' => $timeRide . ' min'
            ],
            [
                'type' => 'WALK',
                'instruction' => 'Coboară și mergi pe jos până la destinație',
                'distance' => ($timeWalk2 * 80) . 'm',
                'time' => $timeWalk2 . ' min'
            ]
        ]
    ]
]));
