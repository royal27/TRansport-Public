<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    die();
}

$db = getDB();

$stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('openai_api_key', 'openai_model')");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$api_key = $settings['openai_api_key'] ?? '';
$model = $settings['openai_model'] ?? 'gpt-4o';

if (empty($api_key)) {
    echo json_encode(['success' => false, 'error' => 'Nu este setată o cheie API OpenAI. Te rugăm să o adaugi din Setări Generale.']);
    die();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['image_url'])) {
    echo json_encode(['success' => false, 'error' => 'URL-ul imaginii lipsește.']);
    die();
}

$image_url = $data['image_url'];

// Remove css url() wrapper if present
if (strpos($image_url, 'url(') === 0) {
    $image_url = substr($image_url, 5, -2);
    // Remove quotes
    $image_url = trim($image_url, '"\'');
}

if (strpos($image_url, 'data:image') === 0) {
    $base64_image = $image_url;
} else {
    // Determine path based on how image was uploaded (could be relative or absolute)
    if (strpos($image_url, 'uploads/') !== false) {
        // extract just the uploads part
        $parts = explode('uploads/', $image_url);
        $clean_path = 'uploads/' . end($parts);
        $image_path = __DIR__ . '/../public/' . $clean_path;
    } else {
        $image_path = __DIR__ . '/../public/' . ltrim($image_url, '/');
    }

    if (!file_exists($image_path)) {
        echo json_encode(['success' => false, 'error' => 'Fișierul imaginii nu a fost găsit pe server: ' . $image_path]);
        die();
    }

    $mime_type = mime_content_type($image_path);
    $file_content = file_get_contents($image_path);
    $base64_content = base64_encode($file_content);
    $base64_image = "data:$mime_type;base64,$base64_content";
}

$prompt = <<<TEXT
You are an expert GIS and Data Extraction AI. You are given an image containing a metro or public transport map.
Your task is to detect the metro lines and stations and output ONLY a valid JSON object matching the exact format required by the database seeding mechanism.

The JSON format MUST be exactly:
{
  "lines": [
    {
      "name": "M2",
      "color": "#3b5998",
      "start_time": "05:00",
      "end_time": "23:30",
      "interval_minutes": 6,
      "is_dashed": 0,
      "stations": [
        {
          "name": "Pipera",
          "x": 450,
          "y": 150,
          "text_offset_x": 10,
          "text_offset_y": -5,
          "is_waypoint": 0,
          "font_weight": "bold"
        }
      ]
    }
  ],
  "decorations": []
}

Instructions:
1. Estimate X and Y coordinates (from 0 to 800) based on their relative position in the image (top left is 0,0, bottom right is 800,800).
2. Group stations by line color/name.
3. If a station acts as a curve, you can create a waypoint station (is_waypoint: 1).
4. DO NOT OUTPUT ANY MARKDOWN, ONLY RAW JSON TEXT.
TEXT;

$payload = [
    "model" => $model,
    "messages" => [
        [
            "role" => "system",
            "content" => "You must reply ONLY with valid JSON. Do not use markdown code blocks."
        ],
        [
            "role" => "user",
            "content" => [
                [
                    "type" => "text",
                    "text" => $prompt
                ],
                [
                    "type" => "image_url",
                    "image_url" => [
                        "url" => $base64_image
                    ]
                ]
            ]
        ]
    ],
    "max_tokens" => 3000,
    "temperature" => 0.1
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode !== 200) {
    echo json_encode(['success' => false, 'error' => 'Eroare de la OpenAI API (Code: ' . $httpcode . '): ' . $response]);
    die();
}

$result = json_decode($response, true);
if (!isset($result['choices'][0]['message']['content'])) {
    echo json_encode(['success' => false, 'error' => 'Răspuns invalid de la OpenAI.']);
    die();
}

$json_content = trim($result['choices'][0]['message']['content']);

// Remove markdown code blocks if the AI disobeyed
if (strpos($json_content, '```json') === 0) {
    $json_content = substr($json_content, 7);
    $json_content = substr($json_content, 0, strrpos($json_content, '```'));
} elseif (strpos($json_content, '```') === 0) {
    $json_content = substr($json_content, 3);
    $json_content = substr($json_content, 0, strrpos($json_content, '```'));
}

$json_content = trim($json_content);

$parsed_data = json_decode($json_content, true);

if ($parsed_data === null || !isset($parsed_data['lines'])) {
    echo json_encode(['success' => false, 'error' => 'Formatul JSON returnat de AI este invalid.', 'raw_response' => $json_content]);
    die();
}

// Everything looks good, return the map data so the client can import it
echo json_encode(['success' => true, 'map_data' => $parsed_data]);
