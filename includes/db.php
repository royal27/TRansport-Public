<?php
require_once __DIR__ . '/config.php';

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