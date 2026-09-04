<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

require_once '../includes/db.php';
$db = getDB();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'export_all') {
        $stmtR = $db->query("SELECT line_id, latitude, longitude, order_idx FROM custom_routes ORDER BY line_id ASC, order_idx ASC");
        $routes = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        $stmtM = $db->query("SELECT line_id, latitude, longitude, type, description FROM custom_markers ORDER BY line_id ASC");
        $markers = $stmtM->fetchAll(PDO::FETCH_ASSOC);

        die(json_encode(['routes' => $routes, 'markers' => $markers]));
    }

    if ($action === 'get_routes' && isset($_GET['line_id'])) {
        $stmt = $db->prepare("SELECT latitude, longitude FROM custom_routes WHERE line_id = ? ORDER BY order_idx ASC");
        $stmt->execute([$_GET['line_id']]);
        die(json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)));
    }

    if ($action === 'get_markers' && isset($_GET['line_id'])) {
        $stmt = $db->prepare("SELECT id, latitude, longitude, type, description FROM custom_markers WHERE line_id = ?");
        $stmt->execute([$_GET['line_id']]);
        die(json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)));
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'save_routes') {
        $line_id = $input['line_id'] ?? 0;
        $routes = $input['routes'] ?? [];

        if ($line_id) {
            $db->beginTransaction();
            try {
                $stmt = $db->prepare("DELETE FROM custom_routes WHERE line_id = ?");
                $stmt->execute([$line_id]);

                $stmt = $db->prepare("INSERT INTO custom_routes (line_id, latitude, longitude, order_idx) VALUES (?, ?, ?, ?)");
                foreach ($routes as $idx => $coord) {
                    $stmt->execute([$line_id, $coord['lat'], $coord['lng'], $idx]);
                }

                $db->commit();
                die(json_encode(['success' => true]));
            } catch (Exception $e) {
                $db->rollBack();
                die(json_encode(['error' => $e->getMessage()]));
            }
        } else {
            die(json_encode(['error' => 'Invalid line ID']));
        }
    }

    if ($action === 'add_marker') {
        $line_id = $input['line_id'] ?? 0;
        $lat = $input['lat'] ?? 0;
        $lng = $input['lng'] ?? 0;
        $type = $input['type'] ?? 'station';
        $desc = $input['description'] ?? '';

        if ($line_id) {
            $stmt = $db->prepare("INSERT INTO custom_markers (line_id, latitude, longitude, type, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$line_id, $lat, $lng, $type, $desc]);
            die(json_encode(['success' => true, 'id' => $db->lastInsertId()]));
        } else {
            die(json_encode(['error' => 'Invalid line ID']));
        }
    }

    if ($action === 'delete_marker') {
        $marker_id = $input['marker_id'] ?? 0;
        if ($marker_id) {
            $stmt = $db->prepare("DELETE FROM custom_markers WHERE id = ?");
            $stmt->execute([$marker_id]);
            die(json_encode(['success' => true]));
        } else {
            die(json_encode(['error' => 'Invalid marker ID']));
        }
    }

    if ($action === 'import_all') {
        $routes = $input['routes'] ?? [];
        $markers = $input['markers'] ?? [];

        $db->beginTransaction();
        try {
            $db->exec("DELETE FROM custom_routes");
            $db->exec("DELETE FROM custom_markers");

            $stmtR = $db->prepare("INSERT INTO custom_routes (line_id, latitude, longitude, order_idx) VALUES (?, ?, ?, ?)");
            foreach ($routes as $r) {
                $stmtR->execute([$r['line_id'], $r['latitude'], $r['longitude'], $r['order_idx']]);
            }

            $stmtM = $db->prepare("INSERT INTO custom_markers (line_id, latitude, longitude, type, description) VALUES (?, ?, ?, ?, ?)");
            foreach ($markers as $m) {
                $stmtM->execute([$m['line_id'], $m['latitude'], $m['longitude'], $m['type'], $m['description']]);
            }

            $db->commit();
            die(json_encode(['success' => true]));
        } catch (Exception $e) {
            $db->rollBack();
            die(json_encode(['error' => $e->getMessage()]));
        }
    }
}

die(json_encode(['error' => 'Invalid request']));
