<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_schedule') {
        $line_name = trim($_POST['line_name'] ?? '');
        $category = $_POST['category'] ?? 'BUS';
        $schedule_details = trim($_POST['schedule_details'] ?? '');

        if (!empty($line_name) && !empty($schedule_details)) {
            $stmt = $db->prepare("INSERT INTO schedules (line_name, category, schedule_details) VALUES (?, ?, ?)");
            $stmt->execute([$line_name, $category, $schedule_details]);
            $success = "Linia a fost adăugată cu succes.";
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
    <title>Gestiune Linii și Orare - București Transport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/admin_style.css?v=<?= time() ?>">
</head>
<body class="<?= (isset($is_responsive) && $is_responsive) ? 'is-responsive' : '' ?>">

<header class="admin-header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars" style="color:white;"></i></button>
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
    <div class="sidebar" id="sidebar">
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard & Setări</a>
        <a href="schedules.php" class="active"><i class="fas fa-clock"></i> Gestiune Orar & Linii</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Administrează Utilizatori</a>
        <a href="manage_tickets.php"><i class="fas fa-ticket-alt"></i> Plăți prin SMS</a>
        <a href="backup.php"><i class="fas fa-save"></i> Backup / Restore</a>
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
                    <label>Nume Linie (ex: 335)</label>
                    <input type="text" name="line_name" required>
                </div>
                <div class="form-group">
                    <label>Categorie Linie</label>
                    <select name="category" required>
                        <option value="BUS">Autobuz</option>
                        <option value="TRAM">Tramvai</option>
                        <option value="TROLLEYBUS">Troleibuz</option>
                    </select>
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
                            <th>Categorie</th>
                            <th>Detalii Orar</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($schedules as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['line_name']) ?></strong></td>
                            <td><?= htmlspecialchars($s['category'] ?? 'BUS') ?></td>
                            <td><?= nl2br(htmlspecialchars($s['schedule_details'])) ?></td>
                            <td>
                                <a href="manage_schedule_stations.php?id=<?= $s['id'] ?>" class="btn-edit" style="text-decoration:none; display:inline-block; margin-bottom:5px;"><i class="fas fa-map-marked-alt"></i> Traseu & Stații</a>
                                <form method="POST" action="" style="display:inline-block;" onsubmit="return confirm('Sigur dorești să ștergi?');">
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


<script>
document.addEventListener("DOMContentLoaded", function() {
    var menuToggle = document.getElementById("menuToggle");
    var sidebar = document.getElementById("sidebar");
    if(menuToggle && sidebar) {
        menuToggle.addEventListener("click", function() {
            sidebar.classList.toggle("open");
        });
    }
});
</script>
</body>
</html>
