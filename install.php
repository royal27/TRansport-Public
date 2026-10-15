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
            // Test connection and potentially create db
            try {
                $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
                $pdo = new PDO($dsn, $db_user, $db_pass);
            } catch (PDOException $e) {
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
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('admin', 'user') DEFAULT 'user',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS licenses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    license_key VARCHAR(100) NOT NULL UNIQUE,
                    plan_type VARCHAR(50),
                    status ENUM('active', 'inactive', 'revoked') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    setting_key VARCHAR(50) PRIMARY KEY,
                    setting_value TEXT
                )
            ");

            // Insert default settings
            $pdo->exec("
                INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
                ('app_name', '3ds Max Plugin'),
                ('contact_email', 'support@example.com')
            ");

            // Check if admin user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$admin_user]);
            if ($stmt->rowCount() == 0) {
                // Inserare admin
                $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
                $stmt->execute([$admin_user, 'admin@localhost', $hashed_pass]);
            }

            // Salvare fisier config
            $config_content = "<?php\n";
            $config_content .= "define('DB_HOST', " . var_export($db_host, true) . ");\n";
            $config_content .= "define('DB_NAME', " . var_export($db_name, true) . ");\n";
            $config_content .= "define('DB_USER', " . var_export($db_user, true) . ");\n";
            $config_content .= "define('DB_PASS', " . var_export($db_pass, true) . ");\n";

            file_put_contents('includes/config.php', $config_content);

            $success = "Instalare realizată cu succes! Din motive de securitate, te rugăm să ștergi fișierul install.php.";

        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            $error = "Eroare conexiune bază de date: " . $errorMsg;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalare Platformă</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bgMain: '#18191C',
                        bgSec: '#222428',
                        card: '#292C31',
                        brandOrange: '#F59A23',
                        brandBlue: '#3997FF',
                        textMain: '#F5F5F5',
                        textSec: '#A6A9AF',
                        borderClr: '#3B3E43'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-bgMain text-textMain min-h-screen flex items-center justify-center font-sans p-4">

<div class="bg-bgSec p-8 rounded-lg shadow-2xl w-full max-w-lg border border-borderClr">
    <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-textMain tracking-tight">System Installer</h2>
        <p class="text-textSec mt-2">Configurarea inițială a platformei software</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500 text-red-500 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-500/10 border border-green-500 text-green-500 px-4 py-3 rounded mb-6 text-center">
            <p class="mb-4"><?= htmlspecialchars($success) ?></p>
            <a href="index.php" class="inline-block bg-brandOrange hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded transition">Mergi la Site</a>
        </div>
    <?php else: ?>
        <form method="POST" action="" class="space-y-6">
            <div>
                <h3 class="text-xl font-semibold mb-4 text-brandBlue border-b border-borderClr pb-2">Configurare Bază de Date</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-textSec mb-1 text-sm">Host</label>
                        <input type="text" name="db_host" value="localhost" required class="w-full bg-card border border-borderClr rounded px-3 py-2 text-textMain focus:outline-none focus:border-brandOrange focus:ring-1 focus:ring-brandOrange transition">
                    </div>
                    <div>
                        <label class="block text-textSec mb-1 text-sm">Nume Bază de Date</label>
                        <input type="text" name="db_name" value="software_db" required class="w-full bg-card border border-borderClr rounded px-3 py-2 text-textMain focus:outline-none focus:border-brandOrange focus:ring-1 focus:ring-brandOrange transition">
                    </div>
                    <div>
                        <label class="block text-textSec mb-1 text-sm">Utilizator DB</label>
                        <input type="text" name="db_user" value="root" required class="w-full bg-card border border-borderClr rounded px-3 py-2 text-textMain focus:outline-none focus:border-brandOrange focus:ring-1 focus:ring-brandOrange transition">
                    </div>
                    <div>
                        <label class="block text-textSec mb-1 text-sm">Parolă DB</label>
                        <input type="password" name="db_pass" class="w-full bg-card border border-borderClr rounded px-3 py-2 text-textMain focus:outline-none focus:border-brandOrange focus:ring-1 focus:ring-brandOrange transition">
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-xl font-semibold mb-4 text-brandBlue border-b border-borderClr pb-2">Cont Administrator</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-textSec mb-1 text-sm">Nume Utilizator</label>
                        <input type="text" name="admin_user" value="admin" required class="w-full bg-card border border-borderClr rounded px-3 py-2 text-textMain focus:outline-none focus:border-brandOrange focus:ring-1 focus:ring-brandOrange transition">
                    </div>
                    <div>
                        <label class="block text-textSec mb-1 text-sm">Parolă Admin</label>
                        <input type="password" name="admin_pass" required class="w-full bg-card border border-borderClr rounded px-3 py-2 text-textMain focus:outline-none focus:border-brandOrange focus:ring-1 focus:ring-brandOrange transition">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-brandOrange hover:bg-orange-600 text-white font-bold py-3 px-4 rounded transition duration-200 mt-4 shadow-lg shadow-brandOrange/20">
                Instalează Platforma
            </button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
