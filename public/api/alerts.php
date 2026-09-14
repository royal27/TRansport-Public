<?php
header('Content-Type: application/json; charset=utf-8');

$cacheFile = sys_get_temp_dir() . '/stb_alerts_cache.json';
$userInfoCacheFile = sys_get_temp_dir() . '/stb_user_info.txt';

$commonHeaders = [
    "Accept: application/json, text/plain, */*",
    "App-Id: buca1aafb0c-e130-41b7-92bc-5e7dd03f0c96",
    "App-Version: 0.0.0",
    "App-key: gcALgRyZHC,qFonZ=Jde",
    "Connection: keep-alive",
    "Device-Name: Chrome",
    "Host: info.stbsa.ro",
    "Lang: ro",
    "OS-Type: Web",
    "OS-Version: 5.0 (Windows NT 10.0; Win64; x64)",
    "Referer: https://info.stbsa.ro/",
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36"
];

function fetchUserInfo() {
    global $commonHeaders, $userInfoCacheFile;
    $url = "https://info.stbsa.ro/v2/api/web/user/auth";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $commonHeaders);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && $response) {
        // The API returns the token directly wrapped in JSON {"user_info":"..."} but sometimes it comes without quotes or inside headers. Let's be safe:
        $data = json_decode($response, true);
        if ($data && isset($data['user_info'])) {
            $token = $data['user_info'];
            file_put_contents($userInfoCacheFile, $token);
            return $token;
        } else if (strpos($response, '"user_info"') !== false) {
             preg_match('/"user_info"\s*:\s*"([^"]+)"/', $response, $matches);
             if (isset($matches[1])) {
                  file_put_contents($userInfoCacheFile, $matches[1]);
                  return $matches[1];
             }
        }
    }
    return null;
}

function fetchAlerts($userInfo) {
    global $commonHeaders;
    $url = "https://info.stbsa.ro/v2/api/web/notifications";

    $headers = $commonHeaders;
    $headers[] = "User-Info: " . $userInfo;
    $headers[] = "Content-Type: application/json";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 412) {
        return 412; // Needs new auth
    }

    if ($httpCode == 200 && $response) {
        return json_decode($response, true);
    }

    return null;
}

// Cache logic (60 seconds)
$canUseCache = false;
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 60) {
    $canUseCache = true;
    echo file_get_contents($cacheFile);
}

if (!$canUseCache) {
    $userInfo = file_exists($userInfoCacheFile) ? file_get_contents($userInfoCacheFile) : fetchUserInfo();

    if (!$userInfo) {
        echo json_encode(["error" => "Could not authenticate with STB API"]);
    } else {
        $alerts = fetchAlerts($userInfo);

        if ($alerts === 412) {
            $userInfo = fetchUserInfo();
            if ($userInfo) {
                $alerts = fetchAlerts($userInfo);
            }
        }

        if (is_array($alerts)) {
            $jsonResponse = json_encode($alerts);
            file_put_contents($cacheFile, $jsonResponse);
            echo $jsonResponse;
        } else {
            // Return old cache if new fetch fails, but touch it to prevent spamming the server
            if (file_exists($cacheFile)) {
                touch($cacheFile);
                echo file_get_contents($cacheFile);
            } else {
                echo json_encode(["error" => "Could not fetch alerts"]);
            }
        }
    }
}
