<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: login.php");
    exit;
}

require_once '../includes/db.php';
$db = getDB();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = $_POST['name'] ?? '';
        $color = $_POST['color'] ?? '#000000';
        $desc = $_POST['description'] ?? '';

        if (!empty($name)) {
            $stmt = $db->prepare("INSERT INTO custom_lines (name, color, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $color, $desc]);
            $success = "Linia a fost adăugată cu succes!";
        } else {
            $error = "Numele liniei este obligatoriu.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $color = $_POST['color'] ?? '#000000';
        $desc = $_POST['description'] ?? '';

        if ($id && !empty($name)) {
            $stmt = $db->prepare("UPDATE custom_lines SET name = ?, color = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $color, $desc, $id]);
            $success = "Linia a fost modificată cu succes!";
        } else {
            $error = "Date invalide pentru editare.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $stmt = $db->prepare("DELETE FROM custom_lines WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Linia a fost ștearsă.";
        }
    }
}

$stmt = $db->query("SELECT * FROM custom_lines ORDER BY id DESC");
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Creează Linii - București Transport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body class="<?= (isset($is_responsive) && $is_responsive) ? 'is-responsive' : '' ?>">

<header class="admin-header">
    <div class="header-left"><button class="menu-toggle" id="menuToggle"><i class="fas fa-bars" style="color:white;"></i></button> <span><i class="fas fa-bus"></i> Admin Panel</span></div>
    <div><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['admin_user']) ?></div>
</header>

<div class="wrapper">
    <div class="sidebar">
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard & Setări</a>
        <a href="schedules.php"><i class="fas fa-clock"></i> Gestiune Orar & Linii</a>
        <a href="create_lines.php" class="active"><i class="fas fa-route"></i> Creează Linii</a>
        <a href="draw_lines.php"><i class="fas fa-draw-polygon"></i> Desenează Linii</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Administrează Utilizatori</a>
        <a href="manage_tickets.php"><i class="fas fa-ticket-alt"></i> Plăți prin SMS</a>
        <a href="../public/index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Vezi site-ul</a>
        <a href="index.php?action=logout" style="color: #e74c3c; margin-top: 50px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <h1>Creează Linii</h1>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Adaugă o linie nouă</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Nume Linie (ex: Expres 1, 335 VIP, Turistic):</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Culoare Linie pe hartă:</label>
                    <input type="color" name="color" value="#e74c3c">
                </div>
                <div class="form-group">
                    <label>Descriere/Detalii:</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <button type="submit"><i class="fas fa-plus"></i> Adaugă Linie</button>
            </form>
        </div>

        <div class="card">
            <h2>Linii Existente</h2>
            <?php if (count($lines) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nume</th>
                            <th>Culoare</th>
                            <th>Descriere</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lines as $line): ?>
                            <tr>
                                <td><?= htmlspecialchars($line['id']) ?></td>
                                <td><strong><?= htmlspecialchars($line['name']) ?></strong></td>
                                <td>
                                    <span class="color-box" style="background-color: <?= htmlspecialchars($line['color']) ?>;"></span>
                                    <?= htmlspecialchars($line['color']) ?>
                                </td>
                                <td><?= htmlspecialchars($line['description']) ?></td>
                                <td>
                                    <button type="button" class="btn-edit" onclick="openEditModal(<?= $line['id'] ?>, '<?= htmlspecialchars(addslashes($line['name'])) ?>', '<?= htmlspecialchars(addslashes($line['color'])) ?>', '<?= htmlspecialchars(addslashes($line['description'])) ?>')"><i class="fas fa-edit"></i> Editează</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Sigur ștergi această linie? Toate datele desenate pe ea vor fi pierdute.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $line['id'] ?>">
                                        <button type="submit" class="btn-delete"><i class="fas fa-trash"></i> Șterge</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nu există nicio linie creată. Folosește formularul de mai sus pentru a adăuga prima linie.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>Editează Linia</h2>
    <form method="POST" action="">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id" value="">
        <div class="form-group">
            <label>Nume Linie:</label>
            <input type="text" name="name" id="edit_name" required>
        </div>
        <div class="form-group">
            <label>Culoare Linie pe hartă:</label>
            <input type="color" name="color" id="edit_color">
        </div>
        <div class="form-group">
            <label>Descriere/Detalii:</label>
            <textarea name="description" id="edit_description" rows="3"></textarea>
        </div>
        <button type="submit"><i class="fas fa-save"></i> Salvează Modificările</button>
    </form>
  </div>
</div>

<footer class="admin-footer">
    CopyRight Transport 2026 By Stoian rudolf
</footer>

<script>
function openEditModal(id, name, color, desc) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_color').value = color;
    document.getElementById('edit_description').value = desc;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal if clicked outside
window.onclick = function(event) {
    let modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeEditModal();
    }
}
</script>


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
<script>document.getElementById("menuToggle")?.addEventListener("click", function(){ document.querySelector(".sidebar").classList.toggle("open"); });</script>
