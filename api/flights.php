<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Mock data pentru zboruri care pleaca din Bucuresti azi
$flights = [
    ['flight_number' => 'RO 381', 'destination' => 'Paris (CDG)', 'departure_time' => '08:30', 'status' => 'On Time'],
    ['flight_number' => 'W6 3071', 'destination' => 'London (LTN)', 'departure_time' => '09:15', 'status' => 'Delayed'],
    ['flight_number' => 'FR 1005', 'destination' => 'Rome (CIA)', 'departure_time' => '10:00', 'status' => 'Boarding'],
    ['flight_number' => 'RO 315', 'destination' => 'Munich (MUC)', 'departure_time' => '11:45', 'status' => 'On Time'],
    ['flight_number' => 'LH 1653', 'destination' => 'Frankfurt (FRA)', 'departure_time' => '12:30', 'status' => 'On Time'],
    ['flight_number' => 'TK 1044', 'destination' => 'Istanbul (IST)', 'departure_time' => '14:20', 'status' => 'On Time'],
    ['flight_number' => 'W6 3257', 'destination' => 'Madrid (MAD)', 'departure_time' => '16:00', 'status' => 'Scheduled'],
    ['flight_number' => 'RO 153', 'destination' => 'Tel Aviv (TLV)', 'departure_time' => '18:10', 'status' => 'Scheduled']
];

echo json_encode([
    'status' => 'success',
    'date' => date('Y-m-d'),
    'data' => $flights
]);
?>