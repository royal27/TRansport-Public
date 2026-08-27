<?php
if (!file_exists(__DIR__ . '/config.php')) {
    // If config doesn't exist, redirect to install if we are not already on install.php
    if (basename($_SERVER['PHP_SELF']) !== 'install.php') {
        // Find base path based on depth
        $depth = substr_count(dirname($_SERVER['PHP_SELF']), '/');
        // A simple way to try redirecting to root install.php
        $redirectUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $path = dirname($_SERVER['PHP_SELF']);

        // Make it robust by redirecting to absolute web path root install.php
        $base_dir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');

        if (strpos($path, 'admin') !== false) {
            header("Location: " . $base_dir . "/install.php");
            exit;
        } else if (strpos($path, 'public') !== false || strpos($path, 'api') !== false) {
            // If in public folder, install.php is usually one level up
            header("Location: " . $base_dir . "/install.php");
            exit;
        } else {
            header("Location: install.php");
            exit;
        }
    }
} else {
    require_once __DIR__ . '/config.php';
}

function getDB() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Asigurare automată că tabelele necesare există (pentru update-uri fără reinstall)

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS app_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(100),
                phone VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(50) PRIMARY KEY,
                setting_value TEXT
            )
        ");


        $default_metro_html = <<<EOT
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Harta Metrou Bucuresti 2026 - schema retelei Metrorex</title>
<meta name="description" content="Harta metrou Bucuresti: schema retelei metroului bucurestean  Metrorex cu cele 6 magistrale in exploatare si 64 de statii, total 80 km de retea.">
<meta name="keywords" content="harta metrou bucuresti, metrou bucuresti, statii metrou, magistrale metrou">
<meta name="author" content="GhidBucureștean">
<meta name="robots" content="index, follow">
<meta name="google-site-verification" content="9Cx7PD6SqCQRb_R9zg6LHEudw4TsWXPeU6UuK6QFQaM" />

<meta name="theme-color" content="#649B1C">

<link rel="canonical" href="https://www.ghidbucurestean.ro/harta-metrou-bucurti">
<meta property="og:type" content="website">
<meta property="og:title" content="Harta Metrou Bucuresti 2026 - schema retelei Metrorex">
<meta property="og:description" content="Harta metrou Bucuresti: schema retelei metroului bucurestean  Metrorex cu cele 6 magistrale in exploatare si 64 de statii, total 80 km de retea.">
<meta property="og:url" content="https://www.ghidbucurestean.ro/harta-metrou-bucuresti">
<meta property="og:image" content="https://www.ghidbucurestean.ro/img/og-ghidbucurestean.png">
<link rel="preload" href="https://www.ghidbucurestean.ro/css/figtree-var-latin-ext.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="https://www.ghidbucurestean.ro/leaflet/leaflet.css?v=1785881326">
<link rel="stylesheet" href="https://www.ghidbucurestean.ro/css/style.css?v=1786759045">

</head>
<body data-ga="G-B30JWWV9RK">
<svg class="svg-sprite" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">




            <button class="buton-meniu" id="buton-meniu" type="button" aria-expanded="false" a







<section class="invelis continut-seo">


    <div class="linie-vizual linie-m1">
        <p class="linie-titlu"><span class="insigna-linie insigna-m1">M1</span> Pantelimon – Dristor 2</p>
        <div class="linie-derulare">
            <ol class="linie-statii">
                <li class="capat"><span class="punct"></span><span class="nume">Pantelimon</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Republica</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Costin Georgian</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Titan</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Nicolae Grigorescu</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Dristor 1</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Mihai Bravu</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Timpuri Noi</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Piața Unirii 1</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Izvor</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Eroilor</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Grozăvești</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Petrache Poenaru</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Crângași</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Basarab</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Gara de Nord 1</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Piața Victoriei</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Ștefan cel Mare</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Obor</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Piața Iancului</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Piața Muncii</span></li>
                <li class="capat"><span class="punct"></span><span class="nume">Dristor 2</span></li>
            </ol>
        </div>
    </div>

    <div class="linie-vizual linie-m2">
        <p class="linie-titlu"><span class="insigna-linie insigna-m2">M2</span> Tudor Arghezi – Pipera</p>
        <div class="linie-derulare">
            <ol class="linie-statii">
                <li class="capat"><span class="punct"></span><span class="nume">Tudor Arghezi</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Berceni</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Dimitrie Leonida</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Apărătorii Patriei</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Piața Sudului</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Constantin Brâncoveanu</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Eroii Revoluției</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Tineretului</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Piața Unirii 2</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Universitate</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Piața Romană</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Piața Victoriei</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Aviatorilor</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Aurel Vlaicu</span></li>
                <li class="capat"><span class="punct"></span><span class="nume">Pipera</span></li>
            </ol>
        </div>
    </div>

    <div class="linie-vizual linie-m3">
        <p class="linie-titlu"><span class="insigna-linie insigna-m3">M3</span> Preciziei – Anghel Saligny</p>
        <div class="linie-derulare">
            <ol class="linie-statii">
                <li class="capat"><span class="punct"></span><span class="nume">Preciziei</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Păcii</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Gorjului</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Lujerului</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Politehnica</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Eroilor</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Izvor</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Piața Unirii 1</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Timpuri Noi</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Mihai Bravu</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Dristor 1</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Nicolae Grigorescu</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">1 Decembrie 1918</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Nicolae Teclu</span></li>
                <li class="capat"><span class="punct"></span><span class="nume">Anghel Saligny</span></li>
            </ol>
        </div>
    </div>

    <div class="linie-vizual linie-m4">
        <p class="linie-titlu"><span class="insigna-linie insigna-m4">M4</span> Gara de Nord 2 – Depou Străulești</p>
        <div class="linie-derulare">
            <ol class="linie-statii">
                <li class="capat"><span class="punct"></span><span class="nume">Gara de Nord 2</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Basarab</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Grivița</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">1 Mai</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Jiului</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Parc Bazilescu</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Laminorului</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Lac Străulești</span></li>
                <li class="capat"><span class="punct"></span><span class="nume">Depou Străulești</span></li>
            </ol>
        </div>
    </div>

    <div class="linie-vizual linie-m5">
        <p class="linie-titlu"><span class="insigna-linie insigna-m5">M5</span> Râul Doamnei – Eroilor</p>
        <div class="linie-derulare">
            <ol class="linie-statii">
                <li class="capat"><span class="punct"></span><span class="nume">Râul Doamnei</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Brâncuși</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Valea Ialomiței</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Romancierilor</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Parc Drumul Taberei</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Tudor Vladimirescu</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Favorit</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Orizont</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Academia Militară</span></li>
                <li class="capat"><span class="punct"></span><span class="nume">Eroilor</span></li>
            </ol>
        </div>
    </div>

    <h2>Magistrale metrou în execuție</h2>

    <div class="linie-vizual linie-m6">
        <p class="linie-titlu"><span class="insigna-linie insigna-m6">M6</span> 1 Mai – Aeroportul Henri Coandă Otopeni <span class="insigna-neutra insigna">în execuție</span></p>
        <div class="linie-derulare">
            <ol class="linie-statii">
                <li class="capat"><span class="punct"></span><span class="nume">1 Mai</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Pajura</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Expoziției</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Piața Montreal</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Gara Băneasa</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Aeroport Băneasa</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Tokyo</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Washington</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Paris</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Bruxelles</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Otopeni</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Ion I.C. Brătianu</span></li>
                <li class="capat"><span class="punct"></span><span class="nume">Aeroport Henri Coandă Otopeni</span></li>
            </ol>
        </div>
    </div>

    <h2>Magistrale metrou în pregătire</h2>

    <div class="linie-vizual linie-m5">
        <p class="linie-titlu"><span class="insigna-linie insigna-m5">M5</span> Eroilor  – Pantelimon</p>
        <div class="linie-derulare">
            <ol class="linie-statii">
                <li class="capat"><span class="punct"></span><span class="nume">Eroilor </span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Universitate</span></li>
                <li class="jos"><span class="punct"></span><span class="nume">Piata Iancului</span></li>
                <li class="sus"><span class="punct"></span><span class="nume">Pantelimon</span></li>
            </ol>
        </div>
    </div>
    <br>
EOT;

        $pdo->exec("
            INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
            ('app_name', 'București Transport Live'),
            ('app_logo', ''),
            ('tpbi_api_key', ''),
            ('theme_color', 'green'),
            ('announcement_text', ''),
            ('announcement_speed', '15'),
            ('responsive_mode', '1')
        ");

        $stmt_metro = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('metro_map_html', :html)");
        $stmt_metro->execute(['html' => $default_metro_html]);


        $pdo->exec("
            CREATE TABLE IF NOT EXISTS schedules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                line_name VARCHAR(100) NOT NULL,
                schedule_details TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        try {
            $pdo->exec("ALTER TABLE schedules ADD COLUMN category VARCHAR(20) DEFAULT 'BUS'");
        } catch (PDOException $e) {}

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS schedule_stations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                schedule_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                direction VARCHAR(10) NOT NULL DEFAULT 'dus',
                latitude DECIMAL(10, 8) NOT NULL,
                longitude DECIMAL(11, 8) NOT NULL,
                order_idx INT NOT NULL,
                FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE
            )
        ");

        // Auto-seed active STB tram lines if missing (must be done after 'schedules' table is created)
        $trams = ['1', '3', '5', '7', '10', '11', '14', '16', '19', '21', '23', '24', '25', '27', '32', '36', '40', '41', '44', '45', '47', '55'];
        foreach ($trams as $line) {
            $check = $pdo->prepare("SELECT id FROM schedules WHERE category = 'TRAM' AND line_name = ?");
            $check->execute([$line]);
            if (!$check->fetch()) {
                $insert = $pdo->prepare("INSERT INTO schedules (category, line_name, schedule_details) VALUES ('TRAM', ?, 'Orarele nu sunt setate')");
                $insert->execute([$line]);
            }
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS custom_lines (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                color VARCHAR(20) DEFAULT '#000000',
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS custom_routes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                line_id INT NOT NULL,
                latitude DECIMAL(10, 8) NOT NULL,
                longitude DECIMAL(11, 8) NOT NULL,
                order_idx INT NOT NULL,
                FOREIGN KEY (line_id) REFERENCES custom_lines(id) ON DELETE CASCADE
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS custom_markers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                line_id INT NOT NULL,
                latitude DECIMAL(10, 8) NOT NULL,
                longitude DECIMAL(11, 8) NOT NULL,
                type VARCHAR(50) NOT NULL,
                description TEXT,
                FOREIGN KEY (line_id) REFERENCES custom_lines(id) ON DELETE CASCADE
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tickets_sms (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                sms_number VARCHAR(20) NOT NULL,
                price VARCHAR(50) NOT NULL,
                sms_text VARCHAR(50) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Metro Editor Tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS metro_lines (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                color VARCHAR(20) DEFAULT '#ff0000',
                active TINYINT(1) DEFAULT 1
            )
        ");

        // Ensure new columns exist for existing deployments
        try { $pdo->exec("ALTER TABLE metro_lines ADD COLUMN start_time VARCHAR(10) DEFAULT '05:00'"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE metro_lines ADD COLUMN end_time VARCHAR(10) DEFAULT '23:30'"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE metro_lines ADD COLUMN interval_minutes INT DEFAULT 6"); } catch (PDOException $e) {}

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS metro_stations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                line_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                x INT NOT NULL,
                y INT NOT NULL,
                order_idx INT NOT NULL,
                FOREIGN KEY (line_id) REFERENCES metro_lines(id) ON DELETE CASCADE
            )
        ");

        try { $pdo->exec("ALTER TABLE metro_stations ADD COLUMN text_offset_x INT DEFAULT 12"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE metro_stations ADD COLUMN text_offset_y INT DEFAULT 4"); } catch (PDOException $e) {}

        return $pdo;
    } catch (PDOException $e) {
        die("Eroare conexiune: " . $e->getMessage());
    }
}
?>