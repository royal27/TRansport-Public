<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/GtfsRtParser.php';
$GLOBALS['db'] = getDB();

$vehicles = [];

// Fetch all AC votes from the last 4 hours grouped by vehicle to avoid N+1 queries
$allAcVotes = [];
try {
    $timeExpr = (strpos($GLOBALS['db']->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false) ? "datetime('now', '-4 hours')" : "DATE_SUB(NOW(), INTERVAL 4 HOUR)";
    $stmtVotesAll = $GLOBALS['db']->query("SELECT vehicle_id, has_ac, COUNT(*) as count FROM ac_votes WHERE created_at >= $timeExpr GROUP BY vehicle_id, has_ac");
    $voteResults = $stmtVotesAll->fetchAll(PDO::FETCH_ASSOC);
    foreach ($voteResults as $r) {
        if (!isset($allAcVotes[$r['vehicle_id']])) {
            $allAcVotes[$r['vehicle_id']] = ['yes' => 0, 'no' => 0];
        }
        if ($r['has_ac'] == 1) {
            $allAcVotes[$r['vehicle_id']]['yes'] = $r['count'];
        } else {
            $allAcVotes[$r['vehicle_id']]['no'] = $r['count'];
        }
    }
} catch (Exception $e) {}

// Pre-load all fleet JSON files
$fleetDataCache = [];

function loadFleetJson($filename, $modelPrefix) {
    global $fleetDataCache;
    $path = __DIR__ . '/../../includes/data/' . $filename;
    if (file_exists($path)) {
        $data = json_decode(file_get_contents($path), true);
        if ($data) {
            foreach ($data as $veh) {
                if (isset($veh['plate']) && isset($veh['inventory'])) {
                    $plate = str_replace('-', '', $veh['plate']);
                    $modelName = $modelPrefix;
                    if (isset($veh['modelType'])) {
                        $modelName = $veh['modelType'];
                    } else if ($filename === 'citaro.json') {
                         $modelName = "Mercedes Citaro Euro 3/4";
                    } else if ($filename === 'otokar.json') {
                         $modelName = "Otokar Kent";
                    } else if ($filename === 'city-tour.json') {
                         $modelName = "BCT Bus";
                    }

                    $fleetDataCache[$plate] = [
                        'inventory' => $veh['inventory'],
                        'model' => $modelName
                    ];
                }
            }
        }
    }
}

// Ensure trolleybus.json, citaro.json, otokar.json, all-buses.json etc. are loaded
loadFleetJson('citaro.json', 'Mercedes Citaro');
loadFleetJson('otokar.json', 'Otokar Kent');
loadFleetJson('all-buses.json', 'STB Bus');
loadFleetJson('trolleybus.json', 'STB Trolleybus');
loadFleetJson('city-tour.json', 'City Tour Bus');

function guessVehicleModel($id, $plate, $type) {
    global $fleetDataCache;
    $strippedPlate = str_replace(['-', ' '], '', $plate);

    // Exact match from JSON
    if (isset($fleetDataCache[$strippedPlate])) {
        return $fleetDataCache[$strippedPlate]['model'];
    }

    // Fallback heuristic if not in JSON
    $numId = (int)preg_replace('/[^0-9]/', '', $id);

    if ($type === 'BUS') {
        if ($numId >= 3200 && $numId <= 3499) return "Karsan e-ATA 12m";
        if ($numId >= 4000 && $numId <= 4999) return "Mercedes-Benz Citaro Euro 3/4";
        if ($numId >= 5300 && $numId <= 5399) return "Mercedes-Benz Citaro Euro 4";
        if ($numId >= 6200 && $numId <= 6299) return "Mercedes-Benz Citaro Euro 4";
        if ($numId >= 6400 && $numId <= 6499) return "Otokar Kent C 10m";
        if ($numId >= 6500 && $numId <= 6699) return "Otokar Kent C 12m";
        if ($numId >= 6800 && $numId <= 6999) return "Otokar Kent C 18m";
        if ($numId >= 7000 && $numId <= 7199) return "Mercedes-Benz Citaro Hybrid";
        if ($numId >= 7200 && $numId <= 7299) return "ZTE Granton 12m";
    } elseif ($type === 'TROLLEYBUS') {
        if ($numId >= 5100 && $numId <= 5299) return "Astra Irisbus Citelis";
        if ($numId >= 5300 && $numId <= 5399) return "Ikarus 415T";
        if ($numId >= 5400 && $numId <= 5499) return "Solaris Trollino 12";
        if ($numId >= 7300 && $numId <= 7399) return "Solaris Trollino 12";
    }
    return '';
}
$tramData = [];
if (file_exists(__DIR__ . '/../../includes/data/tramvaie.csv')) {
    $csv = array_map('str_getcsv', file(__DIR__ . '/../../includes/data/tramvaie.csv'));
    array_shift($csv);
    foreach ($csv as $row) {
        if (isset($row[1]) && isset($row[6])) {
            $tramData[trim($row[1])] = trim($row[6]);
        }
    }
}

$status = 'success';
$dataSource = 'tpbi_gtfs_rt';

// Fetch GTFS-RT directly from TPBI
$url = "https://gtfs.tpbi.ro/api/gtfs-rt/vehiclePositions";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// Verificare stricta SSL pentru securitate in productie
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_USERAGENT, "BucurestiTransportLive/1.0");

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200 && $response) {
    try {
        $parsedVehicles = GtfsRtParser::parseVehiclePositions($response);

        foreach ($parsedVehicles as $v) {
            // routeId from GTFS looks like PV1_335, PV9_41, etc.
            // Let's extract the actual line number
            $line = $v['routeId'];
            if (preg_match('/_([0-9]+)$/', $line, $matches)) {
                $line = $matches[1];
            } else if (preg_match('/_([0-9A-Za-z\-]+)$/', $line, $matches)) {
                 $line = $matches[1];
            }

            // Determine type by line number logic (as a fallback since GTFS-RT VP doesn't send route_type)
            $type = 'BUS';
            $num = (int)$line;
            if ($num > 0 && $num < 60) {
                $type = 'TRAM';
            } elseif ($num >= 60 && $num < 100) {
                $type = 'TROLLEYBUS';
            }

            // Overrides based on routeId prefixes if available
            if (strpos($v['routeId'], 'PV9_') === 0 && $num < 60) {
                $type = 'TRAM';
            }

            $model = '';
            if ($type === 'TRAM' && isset($v['plate']) && isset($tramData[$v['plate']])) {
                $model = $tramData[$v['plate']];
            } else {
                $model = guessVehicleModel($v['id'], $v['plate'] ?? '', $type);
            }
            $occ = isset($v['occupancyStatus']) && $v['occupancyStatus'] > 0 ? $v['occupancyStatus'] : mt_rand(1, 3);

            // Comfort logic: 1 is empty, 2 is moderate, 3 is crowded.
            // AC adds comfort. For this demo, let's assume buses >= year 2018 have AC (or default true for new models)
            $hasAc = strpos($model, 'Otokar') !== false || strpos($model, 'Hybrid') !== false || strpos($model, 'ZTE') !== false || strpos($model, 'Solaris') !== false || strpos($model, 'Imperio') !== false || strpos($model, 'Karsan') !== false;

            $acPoints = $hasAc ? 1.0 : 0.0;
            $crowdPoints = 1.0;
            if ($occ == 2) $crowdPoints = 0.6;
            if ($occ == 3) $crowdPoints = 0.2;

            // comfort = AC points × 0.6 + crowd points × 0.4
            $comfortScore = ($acPoints * 0.6) + ($crowdPoints * 0.4);

            $comfortTier = 'slab'; // poor
            if ($comfortScore >= 0.45) $comfortTier = 'ok';
            if ($comfortScore >= 0.75) $comfortTier = 'excelent'; // great

            $vid = $v['id'] ?: uniqid();
            $yes = 0; $no = 0;
            if (isset($allAcVotes[$vid])) {
                $yes = $allAcVotes[$vid]['yes'];
                $no = $allAcVotes[$vid]['no'];
                // Override AC status if strong user consensus
                if ($yes > $no && $yes > 2) $hasAc = true;
                if ($no > $yes && $no > 2) $hasAc = false;
            }

            $vehicles[] = [
                'id' => $vid,
                'line' => $line,
                'type' => $type,
                'lat' => $v['lat'],
                'lng' => $v['lon'],
                'heading' => $v['bearing'],
                'speed' => round($v['speed']),
                'plate' => $v['plate'],
                'model' => $model,
                'occupancy' => $occ,
                'comfortTier' => $comfortTier,
                'hasAc' => $hasAc,
                'votes_yes' => $yes ?? 0,
                'votes_no' => $no ?? 0
            ];
        }
    } catch (Exception $e) {
        $status = 'error_parsing';
    }
} else {
    $status = 'error_fetching';
}

// Ensure some essential lines are always present for the MVP demo
$essentialLines = ['1', '3', '5', '7', '10', '11', '14', '16', '19', '21', '23', '24', '25', '27', '32', '36', '40', '41', '44', '45', '47', '55', '335', '79', '131', '381'];
$presentLines = array_unique(array_column($vehicles, 'line'));
$missingLines = array_diff($essentialLines, $presentLines);

if (!empty($missingLines)) {
    $baseLat = 44.4323; // Centrul Bucurestiului
    $baseLng = 26.1063;
    foreach ($missingLines as $mLine) {
        $type = 'BUS';
        if (in_array($mLine, ['1', '10', '41', '32'])) $type = 'TRAM';
        elseif (in_array($mLine, ['79'])) $type = 'TROLLEYBUS';

        // Mock vehicles strictly on the exact polyline for the line
        $timeOffset = time() % 3600;

        // Let's try to get the cached shape if it exists
        $cacheFileMain = sys_get_temp_dir() . '/overpass_route_' . md5($mLine . 'dus') . '.json';
        $shapeCoords = [];
        if (file_exists($cacheFileMain)) {
            $r = json_decode(file_get_contents($cacheFileMain), true);
            $shapeId = $r['shape_id'] ?? '';
            $shapeFile = sys_get_temp_dir() . '/' . $shapeId . '.json';
            if (file_exists($shapeFile)) {
                $shapeCoords = json_decode(file_get_contents($shapeFile), true);
            }
        }

        for ($i = 0; $i < 3; $i++) {
            if (!empty($shapeCoords) && count($shapeCoords) > 10) {
                // Snap to route
                $numCoords = count($shapeCoords);

                // Vehicle 0 is at 10% of route, Vehicle 1 is at 40%, etc.
                // We make them move by advancing their index based on time.
                $baseIdx = (int)($numCoords * ($i * 0.3));

                // Move 1 coord index every 3 seconds
                $progression = (int)($timeOffset / 3);

                $currentIdx = ($baseIdx + $progression) % $numCoords;

                $lat = $shapeCoords[$currentIdx]['lat'];
                $lng = $shapeCoords[$currentIdx]['lng'];

                // approximate heading
                $nextIdx = ($currentIdx + 1) % $numCoords;
                $heading = 0;
                if (isset($shapeCoords[$nextIdx])) {
                    $dLon = ($shapeCoords[$nextIdx]['lng'] - $lng);
                    $y = sin($dLon) * cos($shapeCoords[$nextIdx]['lat']);
                    $x = cos($lat) * sin($shapeCoords[$nextIdx]['lat']) - sin($lat) * cos($shapeCoords[$nextIdx]['lat']) * cos($dLon);
                    $heading = (rad2deg(atan2($y, $x)) + 360) % 360;
                }
            } else {
                // Fallback loop
                $radius = 0.02 + ($i * 0.01);
                $speedFactor = 0.005;
                $angle = ($timeOffset * $speedFactor) + ($i * 2);
                $lat = $baseLat + ($radius * sin($angle));
                $lng = $baseLng + ($radius * cos($angle));
                $heading = (rad2deg($angle) + 90) % 360;
            }

            $vehicles[] = [
                'id' => 'MOCK_' . $mLine . '_' . $i,
                'line' => $mLine,
                'type' => $type,
                'lat' => $lat,
                'lng' => $lng,
                'heading' => $heading,
                'speed' => 20 + mt_rand(0, 10),
                'occupancy' => mt_rand(1, 3)
            ];
        }
    }
}

// Fallback (Date simulate) in caz ca TPBI GTFS cade cu totul
if (empty($vehicles)) {
    $dataSource = 'mock_data';
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
    'status' => $status,
    'data_source' => $dataSource,
    'timestamp' => time(),
    'vehicle_count' => count($vehicles),
    'data' => $vehicles
]);
?>
