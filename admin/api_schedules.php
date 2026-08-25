<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die(json_encode(['error' => 'Unauthorized']));
}

$db = getDB();
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? ($input['action'] ?? '');

if ($action === 'get_stations') {
    $schedule_id = $_GET['schedule_id'] ?? 0;
    $stmt = $db->prepare("SELECT * FROM schedule_stations WHERE schedule_id = ? ORDER BY direction, order_idx ASC");
    $stmt->execute([$schedule_id]);
    die(json_encode(['success' => true, 'stations' => $stmt->fetchAll(PDO::FETCH_ASSOC)]));
}

if ($action === 'save_stations') {
    $schedule_id = $input['schedule_id'] ?? 0;
    $stations = $input['stations'] ?? []; // [{name, direction, lat, lng}]

    if (!$schedule_id) {
        die(json_encode(['error' => 'Invalid schedule ID']));
    }

    try {
        $db->beginTransaction();

        // Delete old stations
        $stmt = $db->prepare("DELETE FROM schedule_stations WHERE schedule_id = ?");
        $stmt->execute([$schedule_id]);

        // Insert new stations
        $stmt = $db->prepare("INSERT INTO schedule_stations (schedule_id, name, direction, latitude, longitude, order_idx) VALUES (?, ?, ?, ?, ?, ?)");

        $order_idx = 0;
        foreach ($stations as $st) {
            $stmt->execute([
                $schedule_id,
                $st['name'],
                $st['direction'],
                $st['lat'],
                $st['lng'],
                $order_idx++
            ]);
        }

        $db->commit();
        die(json_encode(['success' => true]));
    } catch (Exception $e) {
        $db->rollBack();
        die(json_encode(['error' => $e->getMessage()]));
    }
}

die(json_encode(['error' => 'Invalid action']));
