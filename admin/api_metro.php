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

    $stmt = $db->query("SELECT * FROM metro_decorations ORDER BY id ASC");
    $decorations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'lines' => $lines, 'decorations' => $decorations]);
    die();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action == 'save_line') {
        $start_time = $data['start_time'] ?? '05:00';
        $end_time = $data['end_time'] ?? '23:30';
        $interval = $data['interval_minutes'] ?? 6;
        $is_dashed = isset($data['is_dashed']) && $data['is_dashed'] ? 1 : 0;

        if (isset($data['id'])) {
            $stmt = $db->prepare("UPDATE metro_lines SET name = ?, color = ?, start_time = ?, end_time = ?, interval_minutes = ?, is_dashed = ? WHERE id = ?");
            $stmt->execute([$data['name'], $data['color'], $start_time, $end_time, $interval, $is_dashed, $data['id']]);
            $id = $data['id'];
        } else {
            $stmt = $db->prepare("INSERT INTO metro_lines (name, color, start_time, end_time, interval_minutes, is_dashed) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$data['name'], $data['color'], $start_time, $end_time, $interval, $is_dashed]);
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

        $stmt = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx, text_offset_x, text_offset_y, is_waypoint, font_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($stations as $idx => $s) {
            $ox = $s['text_offset_x'] ?? 12;
            $oy = $s['text_offset_y'] ?? 4;
            $is_wp = isset($s['is_waypoint']) && $s['is_waypoint'] ? 1 : 0;
            $fw = $s['font_weight'] ?? 'bold';
            $stmt->execute([$line_id, $s['name'], $s['x'], $s['y'], $idx, $ox, $oy, $is_wp, $fw]);
        }
        echo json_encode(['success' => true]);
        die();
    }

    if ($action == 'save_decorations') {
        $decorations = $data['decorations'];
        $db->exec("DELETE FROM metro_decorations");

        $stmt = $db->prepare("INSERT INTO metro_decorations (type, x, y, width, height, content, color, font_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($decorations as $d) {
            $stmt->execute([
                $d['type'], $d['x'], $d['y'],
                $d['width'] ?? 0, $d['height'] ?? 0,
                $d['content'] ?? '', $d['color'] ?? '#000000',
                $d['font_weight'] ?? 'normal'
            ]);
        }
        echo json_encode(['success' => true]);
        die();
    }
}
echo json_encode(['success' => false, 'error' => 'Invalid action']);
