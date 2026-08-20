<?php
session_start();

$error = '';
$success = '';

// Verificam daca e deja instalat
if (file_exists('includes/config.php')) {
    die("Aplicația este deja instalată. Pentru a reinstala, șterge fișierul includes/config.php.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';

    $admin_user = $_POST['admin_user'] ?? '';
    $admin_pass = $_POST['admin_pass'] ?? '';

    if (empty($db_host) || empty($db_name) || empty($db_user) || empty($admin_user) || empty($admin_pass)) {
        $error = "Toate câmpurile sunt obligatorii (în afară de parola DB dacă e goală).";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $db_name)) {
        $error = "Numele bazei de date poate conține doar litere, cifre și underscore.";
    } else {
        try {
            // Incercam intai conexiunea directa cu baza de date (pentru cPanel/Shared Hosting)
            try {
                $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
                $pdo = new PDO($dsn, $db_user, $db_pass);
            } catch (PDOException $e) {
                // Daca esueaza, incercam fara dbname si incercam sa o cream noi (pentru localhost)
                $dsn = "mysql:host=$db_host";
                $pdo = new PDO($dsn, $db_user, $db_pass);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$db_name`");
            }

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Creare tabele
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    setting_key VARCHAR(50) PRIMARY KEY,
                    setting_value TEXT
                )
            ");

            // Insert default settings if they don't exist
            $pdo->exec("
                INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
                ('app_name', 'București Transport Live'),
                ('app_logo', ''),
                ('tpbi_api_key', ''),
                ('theme_color', 'green'),
                ('announcement_text', '')
            ");

            // Check if admin user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$admin_user]);
            if ($stmt->rowCount() == 0) {
                // Inserare admin
                $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->execute([$admin_user, $hashed_pass]);
            }

            // Salvare fisier config folosind var_export pt a preveni RCE
            $config_content = "<?php\n";
            $config_content .= "define('DB_HOST', " . var_export($db_host, true) . ");\n";
            $config_content .= "define('DB_NAME', " . var_export($db_name, true) . ");\n";
            $config_content .= "define('DB_USER', " . var_export($db_user, true) . ");\n";
            $config_content .= "define('DB_PASS', " . var_export($db_pass, true) . ");\n";

            file_put_contents('includes/config.php', $config_content);

            $success = "Instalare realizată cu succes! Din motive de securitate, te rugăm să ștergi fișierul install.php.";

        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, '1045') !== false) {
                $error = "Eroare autentificare (1045): Utilizatorul sau parola pentru baza de date sunt greșite, SAU utilizatorul nu a fost asociat cu baza de date în cPanel (Check 'Add User to Database').";
            } else {
                $error = "Eroare conexiune bază de date: " . $errorMsg;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <?php
    // Get responsive mode setting
    $is_responsive = true; // Default
    if (isset($db)) {
        try {
            $resp_stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'responsive_mode'");
            $resp_row = $resp_stmt->fetch(PDO::FETCH_ASSOC);
            if ($resp_row && $resp_row['setting_value'] === '0') {
                $is_responsive = false;
            }
        } catch(Exception $e) { }
    }
    if ($is_responsive): ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php endif; ?>
    <title>Instalare București Transport Live</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .install-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background-color: #0056b3; }
        .error { color: #dc3545; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .success { color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .divider { border-top: 1px solid #eee; margin: 20px 0; }
    </style>
</head>
<body class="<?= (isset($is_responsive) && $is_responsive) ? 'is-responsive' : '' ?>">

<div class="install-container">
    <h2>Instalare Aplicație</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success">
            <?= htmlspecialchars($success) ?><br><br>
            <a href="admin/login.php" style="color: #155724; font-weight: bold;">Mergi la Panoul de Admin</a>
        </div>
    <?php else: ?>
        <form method="POST" action="">
            <h3>Bază de date</h3>
            <div class="form-group">
                <label>Host Bază de date</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>
            <div class="form-group">
                <label>Nume Bază de date</label>
                <input type="text" name="db_name" value="bucuresti_transport" required>
            </div>
            <div class="form-group">
                <label>Utilizator DB</label>
                <input type="text" name="db_user" value="admin" required>
            </div>
            <div class="form-group">
                <label>Parolă DB</label>
                <input type="password" name="db_pass" value="admin123">
            </div>

            <div class="divider"></div>

            <h3>Cont Administrator</h3>
            <div class="form-group">
                <label>Utilizator Admin</label>
                <input type="text" name="admin_user" required>
            </div>
            <div class="form-group">
                <label>Parolă Admin</label>
                <input type="password" name="admin_pass" required>
            </div>

            <button type="submit">Instalează</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>