<?php
require_once __DIR__ . '/../../includes/db.php';
header('Content-Type: application/json');

$db = getDB();
$action = $_GET['action'] ?? '';

if ($action === 'get_settings') {
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('route_data_source', 'snap_threshold_meters', 'theme_color')");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // Default values if not set
    if (!isset($settings['route_data_source'])) $settings['route_data_source'] = 'api';
    if (!isset($settings['snap_threshold_meters'])) $settings['snap_threshold_meters'] = '20';
    if (!isset($settings['theme_color'])) $settings['theme_color'] = 'green';

    die(json_encode($settings));
}

if ($action === 'search_line') {
    $q = trim($_GET['q'] ?? '');
    if (empty($q)) {
        die(json_encode(['error' => 'Empty query']));
    }

    $stmt = $db->prepare("SELECT id, name, color, description FROM custom_lines WHERE name = ? LIMIT 1");
    $stmt->execute([$q]);
    $line = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($line) {
        die(json_encode(['status' => 'success', 'data' => $line]));
    } else {
        die(json_encode(['status' => 'error', 'message' => 'Line not found']));
    }
}

// Below actions require line_id
$line_id = $_GET['line_id'] ?? 0;

if (!$line_id) {
    die(json_encode(['error' => 'Invalid line ID']));
}

if ($action === 'get_routes') {
    $stmt = $db->prepare("SELECT latitude, longitude FROM custom_routes WHERE line_id = ? ORDER BY order_idx ASC");
    $stmt->execute([$line_id]);
    die(json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)));
}

if ($action === 'get_markers') {
    $stmt = $db->prepare("SELECT id, latitude, longitude, type, description FROM custom_markers WHERE line_id = ?");
    $stmt->execute([$line_id]);
    die(json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)));
}

if ($action === 'get_info') {
    $stmt = $db->prepare("SELECT id, name, color, description FROM custom_lines WHERE id = ?");
    $stmt->execute([$line_id]);
    die(json_encode($stmt->fetch(PDO::FETCH_ASSOC)));
}

die(json_encode(['error' => 'Invalid action']));
