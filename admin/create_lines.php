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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creează Linii - București Transport</title>
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
        input[type="text"], input[type="color"], textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 20px; background-color: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #2980b9; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f9f9f9; }
        .success { color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .error { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .btn-delete { background-color: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
        .btn-delete:hover { background-color: #c0392b; }
        .btn-edit { background-color: #f39c12; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin-right: 5px;}
        .btn-edit:hover { background-color: #d68910; }
        .color-box { display: inline-block; width: 20px; height: 20px; border: 1px solid #000; vertical-align: middle; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 20px; border-radius: 8px; width: 500px; max-width: 90%; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover, .close:focus { color: black; text-decoration: none; }
    </style>
</head>
<body>

<header class="admin-header">
    <div><i class="fas fa-bus"></i> Admin Panel</div>
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

</body>
</html>
