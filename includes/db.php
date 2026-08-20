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

        $pdo->exec("
            INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
            ('app_name', 'București Transport Live'),
            ('app_logo', ''),
            ('tpbi_api_key', ''),
            ('theme_color', 'green'),
            ('announcement_text', '')
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS schedules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                line_name VARCHAR(100) NOT NULL,
                schedule_details TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

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

        return $pdo;
    } catch (PDOException $e) {
        die("Eroare conexiune: " . $e->getMessage());
    }
}
?>