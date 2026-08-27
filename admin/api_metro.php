<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    die();
}

require_once '../includes/db.php';
$db = getDB();
$action = $_GET['action'] ?? '';

if ($action == 'load') {
    $stmt = $db->query("SELECT * FROM metro_lines ORDER BY id ASC");
    $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lines as &$line) {
        $st = $db->prepare("SELECT * FROM metro_stations WHERE line_id = ? ORDER BY order_idx ASC");
        $st->execute([$line['id']]);
        $line['stations'] = $st->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['success' => true, 'lines' => $lines]);
    die();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action == 'save_line') {
        $start_time = $data['start_time'] ?? '05:00';
        $end_time = $data['end_time'] ?? '23:30';
        $interval = $data['interval_minutes'] ?? 6;

        if (isset($data['id'])) {
            $stmt = $db->prepare("UPDATE metro_lines SET name = ?, color = ?, start_time = ?, end_time = ?, interval_minutes = ? WHERE id = ?");
            $stmt->execute([$data['name'], $data['color'], $start_time, $end_time, $interval, $data['id']]);
            $id = $data['id'];
        } else {
            $stmt = $db->prepare("INSERT INTO metro_lines (name, color, start_time, end_time, interval_minutes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$data['name'], $data['color'], $start_time, $end_time, $interval]);
            $id = $db->lastInsertId();
        }
        echo json_encode(['success' => true, 'id' => $id]);
        die();
    }

    if ($action == 'delete_line') {
        $stmt = $db->prepare("DELETE FROM metro_lines WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
        die();
    }

    if ($action == 'save_stations') {
        $line_id = $data['line_id'];
        $stations = $data['stations'];

        $db->prepare("DELETE FROM metro_stations WHERE line_id = ?")->execute([$line_id]);

        $stmt = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx, text_offset_x, text_offset_y) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($stations as $idx => $s) {
            $ox = $s['text_offset_x'] ?? 12;
            $oy = $s['text_offset_y'] ?? 4;
            $stmt->execute([$line_id, $s['name'], $s['x'], $s['y'], $idx, $ox, $oy]);
        }
        echo json_encode(['success' => true]);
        die();
    }
}
echo json_encode(['success' => false, 'error' => 'Invalid action']);
