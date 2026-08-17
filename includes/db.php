<?php
if (!file_exists(__DIR__ . '/config.php')) {
    // If config doesn't exist, redirect to install if we are not already on install.php
    if (basename($_SERVER['PHP_SELF']) !== 'install.php') {
        // Find base path based on depth
        $depth = substr_count(dirname($_SERVER['PHP_SELF']), '/');
        // A simple way to try redirecting to root install.php
        $redirectUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $path = dirname($_SERVER['PHP_SELF']);

        if (strpos($path, 'admin') !== false) {
             $redirectUrl .= str_replace('/admin', '', $path) . '/install.php';
        } else if (strpos($path, 'public') !== false || strpos($path, 'api') !== false) {
            // Need to adjust depending on structure
             header("Location: ../install.php");
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
            CREATE TABLE IF NOT EXISTS schedules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                line_name VARCHAR(100) NOT NULL,
                schedule_details TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        return $pdo;
    } catch (PDOException $e) {
        die("Eroare conexiune: " . $e->getMessage());
    }
}
?>