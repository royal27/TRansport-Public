<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Acesta este un Mock API care returneaza vehicule simulate în zona Bucureștiului
// Coordonatele de baza (Piata Victoriei aproximativ)
$baseLat = 44.4526;
$baseLng = 26.0873;

$vehicles = [];
$lines = ['335', '79', '1', '131', '381', '41'];
$types = ['BUS', 'TROLLEYBUS', 'TRAM', 'BUS', 'BUS', 'TRAM'];

// Generăm 30 de vehicule random care se misca putin in jurul centrului
for ($i = 1; $i <= 30; $i++) {
    $lineIdx = array_rand($lines);

    // Random position offset
    $latOffset = (mt_rand(-300, 300) / 10000);
    $lngOffset = (mt_rand(-300, 300) / 10000);

    $vehicles[] = [
        'id' => 'V' . str_pad($i, 4, '0', STR_PAD_LEFT),
        'line' => $lines[$lineIdx],
        'type' => $types[$lineIdx],
        'lat' => $baseLat + $latOffset,
        'lng' => $baseLng + $lngOffset,
        'heading' => mt_rand(0, 360),
        'speed' => mt_rand(10, 50),
        'occupancy' => mt_rand(1, 3), // 1 = Liber, 2 = Mediu, 3 = Aglomerat
        'last_updated' => time()
    ];
}

echo json_encode([
    'status' => 'success',
    'timestamp' => time(),
    'data' => $vehicles
]);
?>