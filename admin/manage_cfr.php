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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        $train_number = trim($_POST['train_number']);
        $type = trim($_POST['type']);
        $route = trim($_POST['route']);
        $time = trim($_POST['time']);
        $direction = trim($_POST['direction']);
        $default_status = trim($_POST['default_status']);
        $default_platform = trim($_POST['default_platform']);

        if (empty($train_number) || empty($route) || empty($time)) {
            $error = "Toate câmpurile (Număr tren, Rută, Oră) sunt obligatorii!";
        } else {
            if ($_POST['action'] === 'add') {
                $stmt = $db->prepare("INSERT INTO cfr_trains (train_number, type, route, time, direction, default_status, default_platform) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$train_number, $type, $route, $time, $direction, $default_status, $default_platform])) {
                    $success = "Trenul a fost adăugat.";
                } else {
                    $error = "Eroare la adăugarea trenului.";
                }
            } else if ($_POST['action'] === 'edit') {
                $id = (int)$_POST['id'];
                $stmt = $db->prepare("UPDATE cfr_trains SET train_number = ?, type = ?, route = ?, time = ?, direction = ?, default_status = ?, default_platform = ? WHERE id = ?");
                if ($stmt->execute([$train_number, $type, $route, $time, $direction, $default_status, $default_platform, $id])) {
                    $success = "Trenul a fost actualizat.";
                } else {
                    $error = "Eroare la actualizarea trenului.";
                }
            }
        }
    } else if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM cfr_trains WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = "Trenul a fost șters.";
        } else {
            $error = "Eroare la ștergerea trenului.";
        }
    }
}

$stmt = $db->query("SELECT * FROM cfr_trains ORDER BY direction DESC, time ASC");
$trains = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <?php
    $is_responsive = true;
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
    <title>Administrează Plecări CFR - București Transport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .table-responsive { overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        .btn { padding: 8px 12px; cursor: pointer; border: none; border-radius: 4px; color: #fff; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-success { background: #28a745; }
        .btn-primary { background: #007bff; }
        .btn-danger { background: #dc3545; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .flex-container { display: flex; gap: 10px; }
        .flex-container > div { flex: 1; }

        /* Modal styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 8px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <div id="sidebar" class="sidebar">
        <a href="index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="manage_tickets.php"><i class="fa-solid fa-ticket"></i> Bilete SMS</a>
        <a href="create_lines.php"><i class="fa-solid fa-route"></i> Linii Custom</a>
        <a href="draw_lines.php"><i class="fa-solid fa-pen-nib"></i> Desenează Trasee</a>
        <a href="schedules.php"><i class="fa-solid fa-clock"></i> Orare</a>
        <a href="manage_schedule_stations.php"><i class="fa-solid fa-map-pin"></i> Stații Orare</a>
        <a href="metro_editor.php"><i class="fa-solid fa-train-subway"></i> Hartă Metrou</a>
        <a href="manage_users.php"><i class="fa-solid fa-users"></i> Utilizatori</a>
        <a href="manage_cfr.php" class="active"><i class="fa-solid fa-train"></i> Plecări CFR</a>
        <a href="backup.php"><i class="fa-solid fa-download"></i> Backup Sistem</a>
        <a href="index.php?logout=1" style="margin-top: auto; background: #c0392b;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <button id="menuToggle" class="btn btn-primary" style="display:none;"><i class="fa-solid fa-bars"></i></button>
            <h2>Plecări CFR București (Gara de Nord)</h2>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="card">
            <button class="btn btn-success" onclick="openModal('addModal')"><i class="fa-solid fa-plus"></i> Adaugă Tren Nou</button>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Tip</th>
                            <th>Număr</th>
                            <th>Rută (Destinație/Origine)</th>
                            <th>Ora</th>
                            <th>Direcție</th>
                            <th>Status Implicit</th>
                            <th>Peron Implicit</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trains as $train): ?>
                        <tr>
                            <td><?= htmlspecialchars($train['type']) ?></td>
                            <td><?= htmlspecialchars($train['train_number']) ?></td>
                            <td><?= htmlspecialchars($train['route']) ?></td>
                            <td><?= htmlspecialchars($train['time']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($train['direction'])) ?></td>
                            <td><?= htmlspecialchars($train['default_status']) ?></td>
                            <td><?= htmlspecialchars($train['default_platform']) ?></td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick='editTrain(<?= htmlspecialchars(json_encode($train), ENT_QUOTES, "UTF-8") ?>)'><i class="fa-solid fa-pen"></i></button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Ești sigur că vrei să ștergi acest tren?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $train['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($trains) === 0): ?>
                        <tr><td colspan="8" style="text-align: center;">Nu există trenuri adăugate.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adăugare -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addModal')">&times;</span>
        <h2>Adaugă Tren</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">

            <div class="flex-container">
                <div class="form-group">
                    <label>Tip (ex: IR, R, IC)</label>
                    <input type="text" name="type" class="form-control" value="IR" required>
                </div>
                <div class="form-group">
                    <label>Număr Tren (ex: 1932)</label>
                    <input type="text" name="train_number" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label>Rută (ex: București Nord - Brașov sau Constanța - București Nord)</label>
                <input type="text" name="route" class="form-control" required>
            </div>

            <div class="flex-container">
                <div class="form-group">
                    <label>Ora (ex: 14:30)</label>
                    <input type="time" name="time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Direcție</label>
                    <select name="direction" class="form-control" required>
                        <option value="plecare">Plecare</option>
                        <option value="sosire">Sosire</option>
                    </select>
                </div>
            </div>

            <div class="flex-container">
                <div class="form-group">
                    <label>Status Implicit</label>
                    <input type="text" name="default_status" class="form-control" value="La timp">
                </div>
                <div class="form-group">
                    <label>Peron Implicit (opțional)</label>
                    <input type="text" name="default_platform" class="form-control">
                </div>
            </div>

            <button type="submit" class="btn btn-success">Salvează Tren</button>
        </form>
    </div>
</div>

<!-- Modal Editare -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editModal')">&times;</span>
        <h2>Editează Tren</h2>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="flex-container">
                <div class="form-group">
                    <label>Tip</label>
                    <input type="text" name="type" id="edit_type" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Număr Tren</label>
                    <input type="text" name="train_number" id="edit_train_number" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label>Rută</label>
                <input type="text" name="route" id="edit_route" class="form-control" required>
            </div>

            <div class="flex-container">
                <div class="form-group">
                    <label>Ora</label>
                    <input type="time" name="time" id="edit_time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Direcție</label>
                    <select name="direction" id="edit_direction" class="form-control" required>
                        <option value="plecare">Plecare</option>
                        <option value="sosire">Sosire</option>
                    </select>
                </div>
            </div>

            <div class="flex-container">
                <div class="form-group">
                    <label>Status Implicit</label>
                    <input type="text" name="default_status" id="edit_default_status" class="form-control">
                </div>
                <div class="form-group">
                    <label>Peron Implicit</label>
                    <input type="text" name="default_platform" id="edit_default_platform" class="form-control">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Actualizează Tren</button>
        </form>
    </div>
</div>

<script>
    // Responsive sidebar toggle
    if (window.innerWidth <= 768) {
        document.getElementById('menuToggle').style.display = 'inline-block';
    }
    document.getElementById('menuToggle').addEventListener('click', function() {
        var sidebar = document.getElementById('sidebar');
        if (sidebar.style.left === '0px') {
            sidebar.style.left = '-250px';
        } else {
            sidebar.style.left = '0px';
        }
    });

    // Modals
    function openModal(id) {
        document.getElementById(id).style.display = "block";
    }
    function closeModal(id) {
        document.getElementById(id).style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = "none";
        }
    }

    function editTrain(train) {
        document.getElementById('edit_id').value = train.id;
        document.getElementById('edit_type').value = train.type;
        document.getElementById('edit_train_number').value = train.train_number;
        document.getElementById('edit_route').value = train.route;
        document.getElementById('edit_time').value = train.time;
        document.getElementById('edit_direction').value = train.direction;
        document.getElementById('edit_default_status').value = train.default_status;
        document.getElementById('edit_default_platform').value = train.default_platform;
        openModal('editModal');
    }
</script>
</body>
</html>
