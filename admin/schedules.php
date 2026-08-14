<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_schedule') {
        $line_name = trim($_POST['line_name'] ?? '');
        $schedule_details = trim($_POST['schedule_details'] ?? '');

        if (!empty($line_name) && !empty($schedule_details)) {
            $stmt = $db->prepare("INSERT INTO schedules (line_name, schedule_details) VALUES (?, ?)");
            $stmt->execute([$line_name, $schedule_details]);
            $success = "Orarul a fost adăugat cu succes.";
        }
    } elseif ($_POST['action'] == 'delete_schedule') {
        $id = $_POST['id'] ?? 0;
        $stmt = $db->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Orarul a fost șters.";
    }
}

// Fetch all schedules
$stmt = $db->query("SELECT * FROM schedules ORDER BY id DESC");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preia setarile curente pt logo
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'app_logo'");
$logo_row = $stmt->fetch(PDO::FETCH_ASSOC);
$logo_path = $logo_row ? $logo_row['setting_value'] : '';

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestiune Linii și Orare - București Transport</title>
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
        input[type="text"], textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 20px; background-color: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        button:hover { background-color: #2980b9; }
        .btn-danger { background-color: #e74c3c; padding: 5px 10px; }
        .btn-danger:hover { background-color: #c0392b; }
        .success { color: #155724; background-color: #d4edda; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
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
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard & Setări</a>
        <a href="schedules.php" class="active"><i class="fas fa-clock"></i> Gestiune Orar & Linii</a>
        <a href="../public/index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Vezi site-ul</a>
        <a href="index.php?action=logout" style="color: #e74c3c; margin-top: 50px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h1>Gestiune Linii și Orare</h1>
        </div>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Adaugă Linie Nouă</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_schedule">
                <div class="form-group">
                    <label>Nume Linie (ex: Autobuz 335)</label>
                    <input type="text" name="line_name" required>
                </div>
                <div class="form-group">
                    <label>Detalii Orar (ex: Luni-Vineri 05:00 - 23:00 din 10 în 10 min)</label>
                    <textarea name="schedule_details" rows="4" required></textarea>
                </div>
                <button type="submit">Salvează Linie</button>
            </form>
        </div>

        <div class="card">
            <h2>Linii Existente</h2>
            <?php if (count($schedules) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Linie</th>
                            <th>Detalii Orar</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($schedules as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['line_name']) ?></strong></td>
                            <td><?= nl2br(htmlspecialchars($s['schedule_details'])) ?></td>
                            <td>
                                <form method="POST" action="" onsubmit="return confirm('Sigur dorești să ștergi?');">
                                    <input type="hidden" name="action" value="delete_schedule">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn-danger"><i class="fas fa-trash"></i> Șterge</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nu există linii adăugate încă.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="admin-footer">
    CopyRight Transport 2026 By Stoian rudolf
</footer>

</body>
</html>