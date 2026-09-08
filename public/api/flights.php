<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Set timezone to Bucharest
date_default_timezone_set('Europe/Bucharest');
$now = time();

$airlines = [
    'RO' => ['name' => 'TAROM', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/cd/Tarom_Logo.svg/512px-Tarom_Logo.svg.png'],
    'W6' => ['name' => 'Wizz Air', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/05/Wizz_Air_logo.svg/512px-Wizz_Air_logo.svg.png'],
    'FR' => ['name' => 'Ryanair', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e0/Ryanair_logo.svg/512px-Ryanair_logo.svg.png'],
    'LH' => ['name' => 'Lufthansa', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b8/Lufthansa_Logo_2018.svg/512px-Lufthansa_Logo_2018.svg.png'],
    'TK' => ['name' => 'Turkish Airlines', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8e/Turkish_Airlines_logo_2019_compact.svg/512px-Turkish_Airlines_logo_2019_compact.svg.png'],
    'AF' => ['name' => 'Air France', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/44/Air_France_Logo.svg/512px-Air_France_Logo.svg.png'],
    'BA' => ['name' => 'British Airways', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/4/42/British_Airways_Logo.svg/512px-British_Airways_Logo.svg.png']
];

$destinations = [
    'London (LTN)', 'Paris (CDG)', 'Rome (CIA)', 'Madrid (MAD)', 'Munich (MUC)',
    'Frankfurt (FRA)', 'Istanbul (IST)', 'Tel Aviv (TLV)', 'Dubai (DXB)', 'Vienna (VIE)',
    'Amsterdam (AMS)', 'Milan (BGY)'
];

$statuses = [
    'On Time' => 60,
    'Delayed' => 15,
    'Boarding' => 10,
    'Final Call' => 5,
    'Departed' => 10
];

function getRandomStatus() {
    global $statuses;
    $rand = mt_rand(1, 100);
    $sum = 0;
    foreach ($statuses as $status => $prob) {
        $sum += $prob;
        if ($rand <= $sum) return $status;
    }
    return 'Scheduled';
}

$flights = [];
// Generate flights starting from 2 hours ago up to 6 hours from now
for ($i = -2; $i <= 6; $i++) {
    for ($j = 0; $j < mt_rand(2, 4); $j++) { // 2 to 4 flights per hour
        $airlineCode = array_rand($airlines);
        $airline = $airlines[$airlineCode];
        $dest = $destinations[array_rand($destinations)];

        $flightTime = $now + ($i * 3600) + (mt_rand(0, 59) * 60);
        $status = 'Scheduled';

        $timeDiff = $flightTime - $now;

        if ($timeDiff < -1800) {
            $status = 'Departed';
        } elseif ($timeDiff >= -1800 && $timeDiff <= 0) {
            $status = (mt_rand(0,10) > 2) ? 'Departed' : 'Delayed';
        } elseif ($timeDiff > 0 && $timeDiff <= 1800) { // next 30 mins
            $status = (mt_rand(0,10) > 7) ? 'Final Call' : 'Boarding';
        } elseif ($timeDiff > 1800 && $timeDiff <= 5400) { // 30m to 1.5h
            $status = getRandomStatus();
            if ($status == 'Departed') $status = 'On Time';
            if ($status == 'Final Call') $status = 'On Time';
        } else {
            $status = (mt_rand(0,10) > 8) ? 'Delayed' : 'Scheduled';
        }

        $flights[] = [
            'flight_number' => $airlineCode . ' ' . mt_rand(100, 9999),
            'airline_name' => $airline['name'],
            'airline_logo' => $airline['logo'],
            'destination' => $dest,
            'timestamp' => $flightTime,
            'departure_time' => date('H:i', $flightTime),
            'status' => $status
        ];
    }
}

// Sort by departure time
usort($flights, function($a, $b) {
    return $a['timestamp'] <=> $b['timestamp'];
});

echo json_encode([
    'status' => 'success',
    'date' => date('Y-m-d'),
    'data' => $flights
]);
?>
