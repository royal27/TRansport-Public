<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../includes/db.php';

$url = "https://mo-bi.ro/api/v1/routes";
if (isset($_GET['id'])) {
    $url .= "/" . urlencode($_GET['id']);
}

try {
    $db = getDB();
    $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'tpbi_api_key'");
    $keyRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $apiKey = $keyRow ? trim($keyRow['setting_value']) : '';
} catch (Exception $e) {
    $apiKey = '';
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$headers = ["Accept: application/json"];
if (!empty($apiKey)) {
    $headers[] = "Authorization: Bearer " . $apiKey;
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200 && $response) {
    echo $response;
} else {
    // Trimitem mock data
    if (isset($_GET['id'])) {
        // mock shape
        echo json_encode([
            "data" => [
                "route_id" => $_GET['id'],
                "route_short_name" => $_GET['id'],
                "route_type" => 3,
                "shape" => [
                    ["lat" => 44.4323, "lng" => 26.1063],
                    ["lat" => 44.4400, "lng" => 26.1100],
                    ["lat" => 44.4500, "lng" => 26.1200]
                ]
            ]
        ]);
    } else {
        // mock rute list
        echo json_encode([
            "data" => [
                ["route_id" => "32", "route_short_name" => "32", "route_type" => 0, "route_long_name" => "Piata Unirii - Depoul Alexandria"],
                ["route_id" => "1", "route_short_name" => "1", "route_type" => 0, "route_long_name" => "Sura Mare - Bd. Banu Manta"],
                ["route_id" => "335", "route_short_name" => "335", "route_type" => 3, "route_long_name" => "Faur - Complex Comercial Baneasa"],
                ["route_id" => "381", "route_short_name" => "381", "route_type" => 3, "route_long_name" => "Piata Resita - Clabucet"],
                ["route_id" => "79", "route_short_name" => "79", "route_type" => 11, "route_long_name" => "Bd. Basarabia - Gara de Nord"]
            ]
        ]);
    }
}
?>
