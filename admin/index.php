<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/translations.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$success = '';

// Preia setarile curente
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$logo_path = $settings['app_logo'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_settings') {
        $api_key = $_POST['tpbi_api_key'] ?? '';
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('tpbi_api_key', ?)");
        $stmt->execute([$api_key]);
        $success = "Setările TPBI au fost salvate.";
    } elseif ($_POST['action'] == 'upload_logo') {
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == 0) {

            // Verificare tip MIME suportata pe orice server (inclusiv shared hosting)
            $image_info = getimagesize($_FILES['logo_file']['tmp_name']);
            $mime = $image_info !== false ? $image_info['mime'] : '';

            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
            $file_ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

            if ($image_info !== false && in_array($mime, $allowed_mimes) && in_array($file_ext, $allowed_exts)) {
                $new_filename = 'logo_' . time() . '.' . $file_ext;

                $upload_dir = '../public/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_path)) {
                    $logo_url = 'uploads/' . $new_filename;
                    $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('app_logo', ?)");
                    $stmt->execute([$logo_url]);
                    $logo_path = $logo_url;
                    $success = "Logo a fost încărcat cu succes.";
                } else {
                    $success = "Eroare la mutarea fișierului.";
                }
            } else {
                $success = "Format invalid. Doar JPG, PNG, GIF.";
            }
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - București Transport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .admin-header { background-color: #34495e; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .admin-footer { background-color: #2c3e50; color: white; text-align: center; padding: 15px; margin-top: auto; font-size: 14px; }
        .wrapper { display: flex; flex: 1; }
        .sidebar { width: 250px; background-color: #2c3e50; color: white; padding-top: 20px; }
        .sidebar h3 { text-align: center; margin-bottom: 30px; font-weight: 300; }
        .sidebar a { display: block; color: white; text-decoration: none; padding: 15px 20px; transition: background 0.2s; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; border-left: 4px solid #3498db; }
        .main-content { flex: 1; padding: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        h1, h2 { color: #333; margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #555; font-weight: 600; }
        input[type="text"], input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; max-width: 500px; }
        button { padding: 10px 20px; background-color: #2ecc71; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        button:hover { background-color: #27ae60; }
        .success { color: #155724; background-color: #d4edda; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        .logo-preview { margin-top: 10px; max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 5px; }
    </style>
</head>
<body>

<header class="admin-header">
    <div>
        <?php if($logo_path): ?>
            <img src="../public/<?= htmlspecialchars($logo_path) ?>" alt="Logo" style="height: 40px; vertical-align: middle;">
        <?php else: ?>
            <i class="fas fa-bus"></i> Admin Panel
        <?php endif; ?>
    </div>
    <div>
        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['admin_user']) ?>
    </div>
</header>

<div class="wrapper">
    <div class="sidebar">
        <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard & Setări</a>
        <a href="schedules.php"><i class="fas fa-clock"></i> Gestiune Orar & Linii</a>
        <a href="../public/index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Vezi site-ul</a>
        <a href="?action=logout" style="color: #e74c3c; margin-top: 50px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h1>Dashboard</h1>
        </div>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Logo Site</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_logo">
                <div class="form-group">
                    <label>Încarcă imagine logo (PNG/JPG):</label>
                    <input type="file" name="logo_file" required>
                </div>
                <?php if ($logo_path): ?>
                    <p>Logo curent:</p>
                    <img src="../public/<?= htmlspecialchars($logo_path) ?>" class="logo-preview">
                <?php endif; ?>
                <br><br>
                <button type="submit">Salvează Logo</button>
            </form>
        </div>

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
    </div>
</div>

<footer class="admin-footer">
    CopyRight Transport 2026 By Stoian rudolf
</footer>

</body>
</html>