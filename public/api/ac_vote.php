<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/db.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $vehicle_id = $data['vehicle_id'] ?? null;
    $has_ac = isset($data['has_ac']) ? (int)$data['has_ac'] : null;

    if (!$vehicle_id || $has_ac === null) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid data.']);
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO ac_votes (vehicle_id, has_ac) VALUES (?, ?)");
            $stmt->execute([$vehicle_id, $has_ac]);
            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $vehicle_id = $_GET['vehicle_id'] ?? null;
    if (!$vehicle_id) {
         echo json_encode(['status' => 'error']);
    } else {
        try {
            // Support both MySQL and SQLite timestamp date functions for fallback
            $sql = "SELECT has_ac, COUNT(*) as count FROM ac_votes WHERE vehicle_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 4 HOUR) GROUP BY has_ac";
            try {
                $stmt = $db->prepare($sql);
                $stmt->execute([$vehicle_id]);
            } catch (PDOException $e) {
                // SQLite fallback
                $sql = "SELECT has_ac, COUNT(*) as count FROM ac_votes WHERE vehicle_id = ? AND created_at >= datetime('now', '-4 hours') GROUP BY has_ac";
                $stmt = $db->prepare($sql);
                $stmt->execute([$vehicle_id]);
            }
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $yes = 0; $no = 0;
            foreach($results as $r) {
                if ($r['has_ac'] == 1) $yes = $r['count'];
                else $no = $r['count'];
            }

            echo json_encode(['status' => 'success', 'votes_yes' => $yes, 'votes_no' => $no]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error']);
        }
    }
}
