<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.php");
    die();
}

require_once '../includes/db.php';
$db = getDB();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = $_POST['name'] ?? '';
        $sms_number = $_POST['sms_number'] ?? '';
        $price = $_POST['price'] ?? '';
        $sms_text = $_POST['sms_text'] ?? '';
        $desc = $_POST['description'] ?? '';

        if (!empty($name) && !empty($sms_number) && !empty($price) && !empty($sms_text)) {
            $stmt = $db->prepare("INSERT INTO tickets_sms (name, sms_number, price, sms_text, description) VALUES (?, ?, ?, ?, ?)");
            if($stmt->execute([$name, $sms_number, $price, $sms_text, $desc])) {
                $success = "Tipul de bilet a fost adăugat cu succes!";
            } else {
                $error = "Eroare la adăugarea biletului.";
            }
        } else {
            $error = "Toate câmpurile cu excepția descrierii sunt obligatorii.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $stmt = $db->prepare("DELETE FROM tickets_sms WHERE id = ?");
            if($stmt->execute([$id])) {
                $success = "Tipul de bilet a fost șters.";
            }
        }
    }
}

$stmt = $db->query("SELECT * FROM tickets_sms ORDER BY id DESC");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Administrează Bilete SMS - București Transport</title>
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
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input[type="text"], textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 20px; background-color: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #2980b9; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f9f9f9; }
        .success { color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .error { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .btn-delete { background-color: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
        .btn-delete:hover { background-color: #c0392b; }
    </style>
</head>
<body class="<?= (isset($is_responsive) && $is_responsive) ? 'is-responsive' : '' ?>">

<header class="admin-header">
    <div><i class="fas fa-bus"></i> Admin Panel</div>
    <div><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['admin_user']) ?></div>
</header>

<div class="wrapper">
    <div class="sidebar">
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard & Setări</a>
        <a href="schedules.php"><i class="fas fa-clock"></i> Gestiune Orar & Linii</a>
        <a href="create_lines.php"><i class="fas fa-route"></i> Creează Linii</a>
        <a href="draw_lines.php"><i class="fas fa-draw-polygon"></i> Desenează Linii</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Administrează Utilizatori</a>
        <a href="manage_tickets.php" class="active"><i class="fas fa-ticket-alt"></i> Plăți prin SMS</a>
        <a href="../public/index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Vezi site-ul</a>
        <a href="index.php?action=logout" style="color: #e74c3c; margin-top: 50px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <h1>Administrează Bilete & Plăți prin SMS</h1>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Adaugă Opțiune Nouă Bilet SMS</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Denumire bilet (ex: Călătorie 90 minute):</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Număr SMS destinatar (ex: 7458):</label>
                    <input type="text" name="sms_number" required>
                </div>
                <div class="form-group">
                    <label>Preț (ex: 0.60 € + TVA):</label>
                    <input type="text" name="price" required>
                </div>
                <div class="form-group">
                    <label>Text necesar SMS (ex: C):</label>
                    <input type="text" name="sms_text" required>
                </div>
                <div class="form-group">
                    <label>Informații suplimentare/Descriere (Opțional):</label>
                    <textarea name="description" rows="2"></textarea>
                </div>
                <button type="submit"><i class="fas fa-plus"></i> Adaugă Opțiune</button>
            </form>
        </div>

        <div class="card">
            <h2>Opțiuni Existente</h2>
            <?php if (count($tickets) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Denumire</th>
                            <th>Număr SMS</th>
                            <th>Text SMS</th>
                            <th>Preț</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['id']) ?></td>
                                <td><strong><?= htmlspecialchars($t['name']) ?></strong></td>
                                <td><span style="background:#eee; padding:3px 6px; border-radius:3px; font-weight:bold;"><?= htmlspecialchars($t['sms_number']) ?></span></td>
                                <td><span style="background:#eef7ff; padding:3px 6px; border-radius:3px; font-weight:bold; color:#0056b3;"><?= htmlspecialchars($t['sms_text']) ?></span></td>
                                <td><?= htmlspecialchars($t['price']) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Sigur ștergi această opțiune?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                        <button type="submit" class="btn-delete"><i class="fas fa-trash"></i> Șterge</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nu există nicio opțiune de bilet adăugată. Folosește formularul de mai sus.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="admin-footer">
    CopyRight Transport 2026 By Stoian rudolf
</footer>

</body>
</html>
