<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    die();
}

$input = json_decode(file_get_contents('php://input'), true);
$license_key = $input['license_key'] ?? '';

if (empty($license_key)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'License key is required']);
    die();
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, status, plan_type FROM licenses WHERE license_key = ?");
$stmt->execute([$license_key]);
$license = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$license) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid license key']);
} elseif ($license['status'] !== 'active') {
    echo json_encode(['status' => 'error', 'message' => 'License is ' . $license['status']]);
} else {
    echo json_encode([
        'status' => 'success',
        'message' => 'License is valid',
        'data' => [
            'plan_type' => $license['plan_type']
        ]
    ]);
}
