<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../includes/db.php';

$vehicles = [];
$status = 'success';

// 1. Incarcam cheia API din baza de date
try {
    $db = getDB();
    $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'tpbi_api_key'");
    $keyRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $apiKey = $keyRow ? trim($keyRow['setting_value']) : '';
} catch (Exception $e) {
    $apiKey = '';
}

// Daca avem cheie API setata, incercam sa preluam date reale de la mo-bi.ro
if (!empty($apiKey)) {
    // Endpoints obisnuite de la mo-bi.ro pentru preluarea vehiculelor (poate necesita ajustari in functie de documentatia lor exacta)
    $url = "https://mo-bi.ro/api/v1/vehicles";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout mic pt a nu bloca interfata daca serverul e picat
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $apiKey,
        "Accept: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && $response) {
        $realData = json_decode($response, true);
        if (isset($realData['data']) && is_array($realData['data'])) {
            // Mapeaza datele reale in structura ceruta de frontend
            foreach ($realData['data'] as $v) {
                // Stabilim tipul (poate veni ca RouteType din GTFS: 3=Bus, 0=Tram, 11=Trolley)
                $type = 'BUS';
                if (isset($v['route_type'])) {
                    if ($v['route_type'] == 0) $type = 'TRAM';
                    else if ($v['route_type'] == 11) $type = 'TROLLEYBUS';
                }

                $vehicles[] = [
                    'id' => $v['vehicle_id'] ?? uniqid(),
                    'line' => $v['route_short_name'] ?? '?',
                    'type' => $type,
                    'lat' => $v['latitude'] ?? 0,
                    'lng' => $v['longitude'] ?? 0,
                    'heading' => $v['bearing'] ?? 0,
                    'speed' => $v['speed'] ?? 0,
                    'occupancy' => $v['occupancy_status'] ?? 0
                ];
            }
        }
    }
}

// 2. Fallback (Date simulate) - daca API-ul da gres (Cloudflare, etc) sau cheia nu e pusa inca
if (empty($vehicles)) {
    $status = 'mock_data';
    $baseLat = 44.4323; // Centrul Bucurestiului
    $baseLng = 26.1063;

    $lines = ['32', '335', '79', '1', '131', '381', '41'];
    $types = ['TRAM', 'BUS', 'TROLLEYBUS', 'TRAM', 'BUS', 'BUS', 'TRAM'];

    for ($i = 1; $i <= 30; $i++) {
        $lineIdx = array_rand($lines);
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
            'occupancy' => mt_rand(1, 3)
        ];
    }
}

echo json_encode([
    'status' => 'success', // Ramane success pt ca UI-ul sa randeze punctele
    'data_source' => $status,
    'timestamp' => time(),
    'data' => $vehicles
]);
?>