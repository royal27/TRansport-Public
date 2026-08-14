<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_settings') {
    $api_key = $_POST['tpbi_api_key'] ?? '';

    $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('tpbi_api_key', ?)");
    $stmt->execute([$api_key]);
    $success = "Setările au fost salvate.";
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Preia setarile curente
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - București Transport</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: #2c3e50; color: white; padding-top: 20px; }
        .sidebar h3 { text-align: center; margin-bottom: 30px; font-weight: 300; }
        .sidebar a { display: block; color: white; text-decoration: none; padding: 15px 20px; transition: background 0.2s; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; border-left: 4px solid #3498db; }
        .main-content { flex: 1; padding: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        h1 { color: #333; margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #555; font-weight: 600; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; max-width: 500px; }
        button { padding: 10px 20px; background-color: #2ecc71; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        button:hover { background-color: #27ae60; }
        .success { color: #155724; background-color: #d4edda; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>Admin Panel</h3>
    <a href="index.php" class="active">Dashboard & Setări</a>
    <a href="../public/index.php" target="_blank">Vezi site-ul</a>
</div>

<div class="main-content">
    <div class="topbar">
        <h1>Dashboard</h1>
        <div>
            Salut, <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong> |
            <a href="?action=logout" style="color: #e74c3c; text-decoration: none; font-weight: bold;">Logout</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Setări TPBI (mo-bi.ro)</h2>
        <p style="color: #7f8c8d; font-size: 14px;">Introdu cheia API pentru a accesa datele reale GTFS-RT.</p>

        <form method="POST" action="">
            <input type="hidden" name="action" value="save_settings">
            <div class="form-group">
                <label>Cheie API mo-bi.ro (Opțional pt MVP local)</label>
                <input type="text" name="tpbi_api_key" value="<?= htmlspecialchars($settings['tpbi_api_key'] ?? '') ?>" placeholder="ex: 12345-abcde...">
            </div>
            <button type="submit">Salvează Setările</button>
        </form>
    </div>

    <div class="card">
        <h2>Status Sistem</h2>
        <p>✅ Baza de date conectată cu succes.</p>
        <p>✅ Harta Live este activă (API Mock activ).</p>
    </div>
</div>

</body>
</html>