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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrează Bilete SMS - București Transport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

<header class="admin-header">
    <div class="header-left"><button class="menu-toggle" id="menuToggle"><i class="fas fa-bars" style="color:white;"></i></button> <span><i class="fas fa-bus"></i> Admin Panel</span></div>
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
<script>document.getElementById("menuToggle")?.addEventListener("click", function(){ document.querySelector(".sidebar").classList.toggle("open"); });</script>
