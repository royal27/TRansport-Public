<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
$db = getDB();

$stmt = $db->query("SELECT * FROM metro_lines WHERE active = 1 ORDER BY id ASC");
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($lines as &$line) {
    $st = $db->prepare("SELECT * FROM metro_stations WHERE line_id = ? ORDER BY order_idx ASC");
    $st->execute([$line['id']]);
    $line['stations'] = $st->fetchAll(PDO::FETCH_ASSOC);
}

$stmt = $db->query("SELECT * FROM metro_decorations ORDER BY id ASC");
$decorations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'lines' => $lines, 'decorations' => $decorations]);
