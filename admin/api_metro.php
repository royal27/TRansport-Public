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

    $zoom_stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'metro_map_zoom'");
    $zoom_row = $zoom_stmt->fetch(PDO::FETCH_ASSOC);
    $zoom = $zoom_row ? $zoom_row['setting_value'] : '1';

    $var_stmt = $db->query("SELECT id, name, created_at FROM metro_map_variants ORDER BY created_at DESC");
    $variants = $var_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'lines' => $lines, 'decorations' => $decorations, 'zoom' => $zoom, 'variants' => $variants]);
    die();
}

if ($action == 'load_variant' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT data_json FROM metro_map_variants WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $variant = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($variant) {
        echo $variant['data_json'];
    } else {
        echo json_encode(['success' => false, 'error' => 'Variant not found']);
    }
    die();
}

if ($action == 'delete_variant' && isset($_GET['id'])) {
    $stmt = $db->prepare("DELETE FROM metro_map_variants WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    echo json_encode(['success' => true]);
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

    if ($action == 'save_zoom') {
        $zoom = $data['zoom'] ?? '1';
        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'metro_map_zoom'");
        $stmt->execute([$zoom]);
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

    if ($action == 'save_variant') {
        $variant_name = $data['variant_name'] ?? 'Varianta Noua';
        $data_json = json_encode($data['map_data']);
        $stmt = $db->prepare("INSERT INTO metro_map_variants (name, data_json) VALUES (?, ?)");
        $stmt->execute([$variant_name, $data_json]);
        echo json_encode(['success' => true]);
        die();
    }

    if ($action == 'rename_variant') {
        $stmt = $db->prepare("UPDATE metro_map_variants SET name = ? WHERE id = ?");
        $stmt->execute([$data['name'], $data['id']]);
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
                $stmtStation = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx, text_offset_x, text_offset_y, is_waypoint, font_weight, is_under_construction) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

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
                                $st['is_waypoint'] ?? 0, $st['font_weight'] ?? 'bold', $st['is_under_construction'] ?? 0
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


    if ($action == 'activate_bucharest_map') {
        $mapType = $_POST['map_type'] ?? 'default';

        try {
            $db->beginTransaction();
$db->exec("DELETE FROM metro_stations");
$db->exec("DELETE FROM metro_lines");
$db->exec("DELETE FROM metro_decorations");

if ($mapType == 'future') {
    // A simplified or modified version of the map
    $stmt = $db->prepare("INSERT INTO metro_lines (name, color, start_time, end_time, interval_minutes, is_dashed) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['M1', '#ffe100', '05:00', '23:30', 6, 0]);
    $lineId = $db->lastInsertId();
    $stmtSt = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx, text_offset_x, text_offset_y, is_waypoint, font_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtSt->execute([$lineId, 'Pantelimon Nou', 750, 430, 0, 15, -5, 0, 'bold']);
    $stmtSt->execute([$lineId, 'Dristor Nou', 550, 490, 1, -20, 20, 0, 'bold']);
    $stmtSt->execute([$lineId, 'Piața Unirii', 400, 450, 2, 15, -5, 0, 'bold']);
    $stmtSt->execute([$lineId, 'Gara de Nord Noua', 360, 310, 3, -80, 15, 0, 'bold']);
} else if ($mapType == 'minimal') {
    $stmt = $db->prepare("INSERT INTO metro_lines (name, color, start_time, end_time, interval_minutes, is_dashed) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['M2', '#3b5998', '05:00', '23:30', 6, 0]);
    $lineId = $db->lastInsertId();
    $stmtSt = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx, text_offset_x, text_offset_y, is_waypoint, font_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtSt->execute([$lineId, 'Pipera', 450, 150, 0, 10, -5, 0, 'bold']);
    $stmtSt->execute([$lineId, 'Piața Unirii', 400, 450, 1, 15, 15, 0, 'bold']);
    $stmtSt->execute([$lineId, 'Berceni', 600, 770, 2, 15, 5, 0, 'bold']);
} else {
    // Default Bucharest M1-M7 Map


$stmt = $db->prepare("INSERT INTO metro_lines (name, color, start_time, end_time, interval_minutes, is_dashed) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute(['M2', '#3b5998', '05:00', '23:30', 6, 0]);
$lineId = $db->lastInsertId();
$stmtSt = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx, text_offset_x, text_offset_y, is_waypoint, font_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtSt->execute([$lineId, 'Pipera', 450, 150, 0, 10, -5, 0, 'bold']);
$stmtSt->execute([$lineId, 'wp', 400, 150, 1, 12, 4, 1, 'bold']);
$stmtSt->execute([$lineId, 'Aurel Vlaicu', 400, 180, 2, -80, 0, 0, 'bold']);
$stmtSt->execute([$lineId, 'Aviatorilor', 400, 220, 3, -70, 0, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Victoriei', 400, 270, 4, -90, 0, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Romană', 400, 330, 5, -90, 0, 0, 'bold']);
$stmtSt->execute([$lineId, 'Universitate', 400, 390, 6, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Unirii', 400, 450, 7, 15, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Tineretului', 400, 510, 8, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Eroii Revoluției', 400, 570, 9, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Constantin Brâncoveanu', 440, 610, 10, 10, -5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Sudului', 480, 650, 11, 10, -5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Apărătorii Patriei', 520, 690, 12, 10, -5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Dimitrie Leonida', 560, 730, 13, 10, -5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Berceni', 600, 770, 14, 15, 5, 0, 'bold']);

$stmt = $db->prepare("INSERT INTO metro_lines (name, color, start_time, end_time, interval_minutes, is_dashed) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute(['M1', '#ffe100', '05:00', '23:30', 6, 0]);
$lineId = $db->lastInsertId();
$stmtSt = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx, text_offset_x, text_offset_y, is_waypoint, font_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtSt->execute([$lineId, 'Pantelimon', 750, 450, 0, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Republica', 700, 450, 1, -20, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Titan', 650, 450, 2, -15, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Nicolae Grigorescu', 600, 490, 3, 10, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Dristor 2', 550, 490, 4, -20, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Muncii', 500, 450, 5, 10, 0, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Iancului', 500, 400, 6, 10, 0, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Obor', 500, 350, 7, 10, 0, 0, 'bold']);
$stmtSt->execute([$lineId, 'Ștefan cel Mare', 460, 310, 8, 10, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Victoriei', 400, 270, 9, -30, -15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Gara de Nord', 340, 270, 10, -30, -15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Basarab', 280, 270, 11, -30, -15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Crângași', 260, 290, 12, -60, -5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Petrache Poenaru', 260, 340, 13, -110, 0, 0, 'bold']);
$stmtSt->execute([$lineId, 'Grozăvești', 260, 390, 14, -80, 0, 0, 'bold']);
$stmtSt->execute([$lineId, 'Eroilor', 300, 430, 15, -50, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Izvor', 350, 430, 16, 0, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Unirii', 400, 450, 17, -80, -15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Timpuri Noi', 450, 500, 18, 10, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Mihai Bravu', 500, 500, 19, -20, 20, 0, 'bold']);
$stmtSt->execute([$lineId, 'Dristor 1', 550, 490, 20, -20, 20, 0, 'bold']);

$stmt = $db->prepare("INSERT INTO metro_lines (name, color, start_time, end_time, interval_minutes, is_dashed) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute(['M3', '#e74c3c', '05:00', '23:30', 6, 0]);
$lineId = $db->lastInsertId();
$stmtSt = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx, text_offset_x, text_offset_y, is_waypoint, font_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtSt->execute([$lineId, 'Preciziei', 150, 400, 0, -20, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Păcii', 200, 400, 1, -15, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Gorjului', 240, 400, 2, -15, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Lujerului', 280, 400, 3, -15, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Politehnica', 320, 400, 4, -15, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Eroilor', 360, 400, 5, -15, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Izvor', 380, 420, 6, 10, 10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Unirii', 400, 450, 7, 15, -5, 0, 'bold']);
$stmtSt->execute([$lineId, 'wp1', 410, 470, 8, 12, 4, 1, 'bold']);
$stmtSt->execute([$lineId, 'Timpuri Noi', 450, 500, 9, 10, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Mihai Bravu', 500, 500, 10, -20, 20, 0, 'bold']);
$stmtSt->execute([$lineId, 'Dristor 1', 550, 490, 11, -20, 20, 0, 'bold']);
$stmtSt->execute([$lineId, 'Nicolae Grigorescu', 600, 490, 12, 10, -10, 0, 'bold']);
$stmtSt->execute([$lineId, '1 Decembrie 1918', 650, 490, 13, 10, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Nicolae Teclu', 700, 490, 14, 10, 20, 0, 'bold']);
$stmtSt->execute([$lineId, 'Anghel Saligny', 750, 490, 15, 15, 5, 0, 'bold']);

$stmt = $db->prepare("INSERT INTO metro_lines (name, color, start_time, end_time, interval_minutes, is_dashed) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute(['M4', '#2ecc71', '05:00', '23:30', 6, 0]);
$lineId = $db->lastInsertId();
$stmtSt = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx, text_offset_x, text_offset_y, is_waypoint, font_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtSt->execute([$lineId, 'Lac Străulești', 150, 100, 0, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Laminorului', 200, 150, 1, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Parc Bazilescu', 240, 190, 2, -90, 0, 0, 'bold']);
$stmtSt->execute([$lineId, 'Jiului', 280, 230, 3, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, '1 Mai', 300, 250, 4, -40, 0, 0, 'bold']);
$stmtSt->execute([$lineId, 'Grivița', 320, 270, 5, -50, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Basarab', 340, 290, 6, -50, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Gara de Nord', 360, 310, 7, -80, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Eroilor', 360, 400, 8, 15, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Academia Militară', 360, 460, 9, -110, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Trafic Greu', 360, 500, 10, -80, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Eroii Revoluției', 400, 570, 11, -100, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Bacovia', 360, 610, 12, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Progresul', 320, 650, 13, -110, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Toporan', 320, 700, 14, -60, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Grădiștea', 320, 740, 15, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Luica', 320, 780, 16, -40, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Alexandru Moldoveanu', 320, 820, 17, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Gara Progresul', 320, 860, 18, 15, 5, 0, 'bold']);

$stmt = $db->prepare("INSERT INTO metro_lines (name, color, start_time, end_time, interval_minutes, is_dashed) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute(['M5', '#e67e22', '05:00', '23:30', 6, 0]);
$lineId = $db->lastInsertId();
$stmtSt = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx, text_offset_x, text_offset_y, is_waypoint, font_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtSt->execute([$lineId, 'Ghencea', 200, 550, 0, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Brâncuși', 150, 500, 1, -60, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Valea Ialomiței', 150, 450, 2, -90, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Romancierilor', 200, 450, 3, -90, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Parc Drumul Taberei', 250, 450, 4, -50, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Tudor Vladimirescu', 250, 500, 5, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Favorit', 300, 450, 6, -20, 20, 0, 'bold']);
$stmtSt->execute([$lineId, 'Academia Militară', 360, 460, 7, -10, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Eroilor', 360, 400, 8, -10, -20, 0, 'bold']);
$stmtSt->execute([$lineId, 'Universitate', 400, 390, 9, 15, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'wp', 480, 390, 10, 12, 4, 1, 'bold']);
$stmtSt->execute([$lineId, 'Piața Iancului', 500, 400, 11, 15, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'wp2', 520, 410, 12, 12, 4, 1, 'bold']);
$stmtSt->execute([$lineId, 'Delfinului', 550, 410, 13, 10, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Costin Georgian', 620, 410, 14, -50, 20, 0, 'bold']);
$stmtSt->execute([$lineId, 'Spital Fundeni', 670, 410, 15, 10, -15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Granitul', 720, 410, 16, 10, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Pantelimon', 750, 430, 17, 15, -5, 0, 'bold']);

$stmt = $db->prepare("INSERT INTO metro_lines (name, color, start_time, end_time, interval_minutes, is_dashed) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute(['M6', '#800000', '05:00', '23:30', 6, 0]);
$lineId = $db->lastInsertId();
$stmtSt = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx, text_offset_x, text_offset_y, is_waypoint, font_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtSt->execute([$lineId, '1 Mai', 300, 250, 0, -40, 20, 0, 'bold']);
$stmtSt->execute([$lineId, 'Pajura', 320, 230, 1, -50, -5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Expoziției', 340, 210, 2, -60, -5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Montreal', 360, 190, 3, -80, -5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Gara Băneasa', 380, 170, 4, -80, -5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Aeroport Băneasa', 400, 150, 5, -110, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Institut Băneasa', 400, 100, 6, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Meteo Băneasa', 400, 50, 7, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Privighetorilor', 400, 0, 8, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Padina', 400, -50, 9, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Aeroport Henri Coandă', 400, -100, 10, -150, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Depou Otopeni', 400, -150, 11, 15, 5, 0, 'bold']);

$stmt = $db->prepare("INSERT INTO metro_lines (name, color, start_time, end_time, interval_minutes, is_dashed) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute(['M7', '#9b59b6', '05:00', '23:30', 6, 0]);
$lineId = $db->lastInsertId();
$stmtSt = $db->prepare("INSERT INTO metro_stations (line_id, name, x, y, order_idx, text_offset_x, text_offset_y, is_waypoint, font_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtSt->execute([$lineId, 'Depoul Voluntari', 750, 150, 0, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Bucegi', 680, 150, 1, 10, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Vlahuță', 630, 150, 2, 10, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Colentina', 580, 150, 3, 10, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Sportului', 530, 150, 4, 10, -10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Andronache', 480, 170, 5, 10, -5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Plumbuita', 450, 200, 6, -70, 10, 0, 'bold']);
$stmtSt->execute([$lineId, 'Doamna Ghica', 430, 220, 7, 15, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Obor', 500, 350, 8, 15, 20, 0, 'bold']);
$stmtSt->execute([$lineId, 'Dacia', 450, 350, 9, 15, -5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Universitate', 400, 390, 10, 15, 15, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Unirii', 400, 450, 11, 15, 30, 0, 'bold']);
$stmtSt->execute([$lineId, 'Tineretului', 400, 510, 12, -70, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Sebastian', 350, 560, 13, -70, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Barza', 320, 590, 14, -40, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Piața Rahova', 280, 630, 15, -80, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Autogara Rahova', 240, 670, 16, -110, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Alaska', 200, 710, 17, -50, 5, 0, 'bold']);
$stmtSt->execute([$lineId, 'Independenței 1877', 160, 750, 18, 15, 5, 0, 'bold']);
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
            echo json_encode(['success' => false, 'error' => 'Niciun fișier încărcat']);
            die();
        }
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['png']; // Forced PNG as requested

        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Te rog să încarci doar imagini cu formatul .png']);
            die();
        }

        if (!getimagesize($file['tmp_name'])) {
            echo json_encode(['success' => false, 'error' => 'Fișierul nu este o imagine validă']);
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
            $err = error_get_last();
            echo json_encode(['success' => false, 'error' => 'A apărut o eroare la salvarea fișierului pe server. Verificați permisiunile folderului public/uploads/. Detalii: ' . ($err ? $err['message'] : '')]);
        }
        die();
    }
}
echo json_encode(['success' => false, 'error' => 'Invalid action']);
