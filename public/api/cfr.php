<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/db.php';
$db = getDB();

try {
    $stmt = $db->query("SELECT * FROM cfr_trains ORDER BY time ASC");
    $trains = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $departures = [];
    $arrivals = [];

    // Simulate current time for realistic platform/delay logic
    $current_time = time();

    foreach ($trains as $train) {
        $train_time_parts = explode(':', $train['time']);
        $train_timestamp = mktime((int)$train_time_parts[0], (int)$train_time_parts[1], 0);

        $diff_minutes = ($train_timestamp - $current_time) / 60;

        // Generate realistic delays and platforms based on proximity to departure/arrival
        $status = $train['default_status'];
        $platform = $train['default_platform'];

        if ($platform === '') {
            if ($diff_minutes > -30 && $diff_minutes < 120) {
                // Seed random with train id and date so it's consistent for the same train today
                srand($train['id'] + crc32(date('Y-m-d')));
                $platform = (string)rand(1, 14);

                // 20% chance of delay
                if (rand(1, 100) <= 20) {
                    $delay = rand(5, 45);
                    $status = "Întârziat $delay min";
                }
            } else {
                $platform = '-';
            }
        }

        $train_data = [
            'id' => $train['id'],
            'type' => $train['type'],
            'number' => $train['train_number'],
            'route' => $train['route'],
            'time' => $train['time'],
            'status' => $status,
            'platform' => $platform
        ];

        if ($train['direction'] === 'plecare') {
            $departures[] = $train_data;
        } else {
            $arrivals[] = $train_data;
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'departures' => $departures,
            'arrivals' => $arrivals
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Eroare la preluarea datelor.']);
}
