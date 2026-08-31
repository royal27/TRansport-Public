<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    die();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['image_url'])) {
    echo json_encode(['success' => false, 'error' => 'URL-ul imaginii lipsește.']);
    die();
}

$image_url = $data['image_url'];

// Generate a simple hash from the image URL to deterministically pick a map
$hash = crc32($image_url);

// Map variants
$map_type = 'default';
if ($hash % 3 === 0) {
    $map_type = 'minimal';
} else if ($hash % 3 === 1) {
    $map_type = 'future';
}

$lines = [];

if ($map_type === 'minimal') {
    $lines[] = [
        "id" => 1, "name" => "M2", "color" => "#3b5998", "start_time" => "05:00", "end_time" => "23:30", "interval_minutes" => 6, "is_dashed" => 0,
        "stations" => [
            ["name" => "Pipera", "x" => 450, "y" => 150, "text_offset_x" => 10, "text_offset_y" => -5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Universitate", "x" => 400, "y" => 390, "text_offset_x" => 15, "text_offset_y" => 5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Piața Unirii", "x" => 400, "y" => 450, "text_offset_x" => 15, "text_offset_y" => 15, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Berceni", "x" => 600, "y" => 770, "text_offset_x" => 15, "text_offset_y" => 5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0]
        ]
    ];
} else if ($map_type === 'future') {
    $lines[] = [
        "id" => 2, "name" => "M1", "color" => "#ffe100", "start_time" => "05:00", "end_time" => "23:30", "interval_minutes" => 6, "is_dashed" => 0,
        "stations" => [
            ["name" => "Pantelimon Nou", "x" => 750, "y" => 430, "text_offset_x" => 15, "text_offset_y" => -5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Dristor 2 Nou", "x" => 550, "y" => 490, "text_offset_x" => -20, "text_offset_y" => 20, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Piața Unirii", "x" => 400, "y" => 450, "text_offset_x" => 15, "text_offset_y" => -5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Extensie Vest", "x" => 150, "y" => 400, "text_offset_x" => -20, "text_offset_y" => 15, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 1]
        ]
    ];
} else {
    // Default Map
    $lines[] = [
        "id" => 1, "name" => "M2", "color" => "#3b5998", "start_time" => "05:00", "end_time" => "23:30", "interval_minutes" => 6, "is_dashed" => 0,
        "stations" => [
            ["name" => "Pipera", "x" => 450, "y" => 150, "text_offset_x" => 10, "text_offset_y" => -5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "wp", "x" => 400, "y" => 150, "text_offset_x" => 12, "text_offset_y" => 4, "is_waypoint" => 1, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Aurel Vlaicu", "x" => 400, "y" => 180, "text_offset_x" => -80, "text_offset_y" => 0, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Aviatorilor", "x" => 400, "y" => 220, "text_offset_x" => -70, "text_offset_y" => 0, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Piața Victoriei", "x" => 400, "y" => 270, "text_offset_x" => -90, "text_offset_y" => 0, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Piața Romană", "x" => 400, "y" => 330, "text_offset_x" => -90, "text_offset_y" => 0, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Universitate", "x" => 400, "y" => 390, "text_offset_x" => 15, "text_offset_y" => 5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Piața Unirii", "x" => 400, "y" => 450, "text_offset_x" => 15, "text_offset_y" => 15, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Tineretului", "x" => 400, "y" => 510, "text_offset_x" => 15, "text_offset_y" => 5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Eroii Revoluției", "x" => 400, "y" => 570, "text_offset_x" => 15, "text_offset_y" => 5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Constantin Brâncoveanu", "x" => 440, "y" => 610, "text_offset_x" => 10, "text_offset_y" => -5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Piața Sudului", "x" => 480, "y" => 650, "text_offset_x" => 10, "text_offset_y" => -5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Apărătorii Patriei", "x" => 520, "y" => 690, "text_offset_x" => 10, "text_offset_y" => -5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Dimitrie Leonida", "x" => 560, "y" => 730, "text_offset_x" => 10, "text_offset_y" => -5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0],
            ["name" => "Berceni", "x" => 600, "y" => 770, "text_offset_x" => 15, "text_offset_y" => 5, "is_waypoint" => 0, "font_weight" => "bold", "is_under_construction" => 0]
        ]
    ];
}

$parsed_data = [
    "lines" => $lines,
    "decorations" => []
];

// Return simulated map data
echo json_encode(['success' => true, 'map_data' => $parsed_data]);
