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
        if (isset($data['id'])) {
            $stmt = $db->prepare("UPDATE metro_lines SET name = ?, color = ? WHERE id = ?");
            $stmt->execute([$data['name'], $data['color'], $data['id']]);
            $id = $data['id'];
        } else {
            $stmt = $db->prepare("INSERT INTO metro_lines (name, color) VALUES (?, ?)");
            $stmt->execute([$data['name'], $data['color']]);
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

        $stmt = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx) VALUES (?, ?, ?, ?, ?)");
        foreach ($stations as $idx => $s) {
            $stmt->execute([$line_id, $s['name'], $s['x'], $s['y'], $idx]);
        }
        echo json_encode(['success' => true]);
        die();
    }
}
echo json_encode(['success' => false, 'error' => 'Invalid action']);
