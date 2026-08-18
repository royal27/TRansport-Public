<?php
require_once __DIR__ . '/../../includes/db.php';
header('Content-Type: application/json');

$db = getDB();
$action = $_GET['action'] ?? '';
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
