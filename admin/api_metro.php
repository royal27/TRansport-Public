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

    if ($action == 'import_map') {
        try {
            $db->beginTransaction();
            $db->exec("DELETE FROM metro_stations");
            $db->exec("DELETE FROM metro_lines");
            $db->exec("DELETE FROM metro_decorations");

            if (isset($data['lines']) && is_array($data['lines'])) {
                $stmtLine = $db->prepare("INSERT INTO metro_lines (id, name, color, active, start_time, end_time, interval_minutes, is_dashed) VALUES (?, ?, ?, 1, ?, ?, ?, ?)");
                $stmtStation = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx, text_offset_x, text_offset_y, is_waypoint, font_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

                foreach ($data['lines'] as $line) {
                    $stmtLine->execute([
                        $line['id'], $line['name'], $line['color'],
                        $line['start_time'] ?? '05:00', $line['end_time'] ?? '23:30',
                        $line['interval_minutes'] ?? 6, $line['is_dashed'] ?? 0
                    ]);

                    if (isset($line['stations']) && is_array($line['stations'])) {
                        foreach ($line['stations'] as $idx => $st) {
                            $stmtStation->execute([
                                $line['id'], $st['name'], $st['x'], $st['y'], $idx,
                                $st['text_offset_x'] ?? 12, $st['text_offset_y'] ?? 4,
                                $st['is_waypoint'] ?? 0, $st['font_weight'] ?? 'bold'
                            ]);
                        }
                    }
                }
            }

            if (isset($data['decorations']) && is_array($data['decorations'])) {
                $stmtDec = $db->prepare("INSERT INTO metro_decorations (type, x, y, width, height, content, color, font_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['decorations'] as $d) {
                    $stmtDec->execute([
                        $d['type'], $d['x'], $d['y'],
                        $d['width'] ?? 0, $d['height'] ?? 0,
                        $d['content'] ?? '', $d['color'] ?? '#000000',
                        $d['font_weight'] ?? 'normal'
                    ]);
                }
            }

            $db->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        die();
    }

    if ($action == 'upload_image') {
        if (!isset($_FILES['image'])) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded']);
            die();
        }
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type']);
            die();
        }

        if (!getimagesize($file['tmp_name']) && $ext !== 'svg') {
            echo json_encode(['success' => false, 'error' => 'Invalid image content']);
            die();
        }

        $upload_dir = __DIR__ . '/../public/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = 'metro_' . uniqid() . '.' . $ext;
        $target = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $url = 'uploads/' . $filename; // Relative to public/
            echo json_encode(['success' => true, 'url' => $url]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save file']);
        }
        die();
    }
}
echo json_encode(['success' => false, 'error' => 'Invalid action']);
