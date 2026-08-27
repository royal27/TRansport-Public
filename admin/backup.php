<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$success = '';
$error = '';

// Check setup
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'export') {
        // Create a ZIP file
        $zip_filename = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zip_filepath = sys_get_temp_dir() . '/' . $zip_filename;

        $zip = new ZipArchive();
        if ($zip->open($zip_filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

            // 1. Export database
            $sql_filename = sys_get_temp_dir() . '/database.sql';
            // We can use mysqldump
            $command = "mysqldump --user=" . escapeshellarg(DB_USER) . " --password=" . escapeshellarg(DB_PASS) . " --host=" . escapeshellarg(DB_HOST) . " " . escapeshellarg(DB_NAME) . " > " . escapeshellarg($sql_filename);
            exec($command, $output, $result_code);

            if ($result_code === 0 && file_exists($sql_filename)) {
                $zip->addFile($sql_filename, 'database.sql');
            }

            // 2. Export uploads folder
            $uploads_dir = __DIR__ . '/../public/uploads/';
            if (is_dir($uploads_dir)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($uploads_dir),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = 'uploads/' . substr($filePath, strlen(realpath($uploads_dir)) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
            }

            $zip->close();

            // Send file to browser
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename='.$zip_filename);
            header('Content-Length: ' . filesize($zip_filepath));
            readfile($zip_filepath);

            // Cleanup
            unlink($zip_filepath);
            if (file_exists($sql_filename)) {
                unlink($sql_filename);
            }
            exit;
        } else {
            $error = "Nu am putut crea fișierul ZIP pentru backup.";
        }
    } elseif ($_POST['action'] == 'import') {
        if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] == 0) {
            $file_ext = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));
            if ($file_ext === 'zip') {
                $zip_path = $_FILES['backup_file']['tmp_name'];
                $zip = new ZipArchive;
                if ($zip->open($zip_path) === TRUE) {
                    $extract_dir = sys_get_temp_dir() . '/backup_extract_' . time();
                    mkdir($extract_dir);
                    $zip->extractTo($extract_dir);
                    $zip->close();

                    // 1. Import Database
                    $sql_file = $extract_dir . '/database.sql';
                    if (file_exists($sql_file)) {
                        $command = "mysql --user=" . escapeshellarg(DB_USER) . " --password=" . escapeshellarg(DB_PASS) . " --host=" . escapeshellarg(DB_HOST) . " " . escapeshellarg(DB_NAME) . " < " . escapeshellarg($sql_file);
                        exec($command, $output, $result_code);
                        if ($result_code !== 0) {
                            $error = "Eroare la importul bazei de date.";
                        }
                    }

                    // 2. Import Uploads
                    $uploads_source = $extract_dir . '/uploads';
                    if (is_dir($uploads_source)) {
                        $uploads_dest = __DIR__ . '/../public/uploads/';
                        if (!is_dir($uploads_dest)) {
                            mkdir($uploads_dest, 0755, true);
                        }

                        $files = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($uploads_source),
                            RecursiveIteratorIterator::LEAVES_ONLY
                        );

                        foreach ($files as $name => $file) {
                            if (!$file->isDir()) {
                                $filePath = $file->getRealPath();
                                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

                                if (in_array($ext, $allowed_exts) || basename($filePath) === '.gitkeep') {
                                    $relativePath = substr($filePath, strlen(realpath($uploads_source)) + 1);
                                    $destPath = $uploads_dest . $relativePath;

                                    $destDir = dirname($destPath);
                                    if (!is_dir($destDir)) {
                                        mkdir($destDir, 0755, true);
                                    }

                                    copy($filePath, $destPath);
                                }
                            }
                        }
                    }

                    // Cleanup
                    $it = new RecursiveDirectoryIterator($extract_dir, RecursiveDirectoryIterator::SKIP_DOTS);
                    $files = new RecursiveIteratorIterator($it,
                                 RecursiveIteratorIterator::CHILD_FIRST);
                    foreach($files as $file) {
                        if ($file->isDir()){
                            rmdir($file->getRealPath());
                        } else {
                            unlink($file->getRealPath());
                        }
                    }
                    rmdir($extract_dir);

                    if (empty($error)) {
                        $success = "Backup-ul a fost importat cu succes!";
                    }
                } else {
                    $error = "Nu am putut deschide fișierul ZIP.";
                }
            } else {
                $error = "Vă rugăm să încărcați un fișier ZIP valid.";
            }
        } else {
            $error = "Eroare la încărcarea fișierului.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <?php
    $is_responsive = true;
    try {
        $resp_stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'responsive_mode'");
        $resp_row = $resp_stmt->fetch(PDO::FETCH_ASSOC);
        if ($resp_row && $resp_row['setting_value'] === '0') {
            $is_responsive = false;
        }
    } catch(Exception $e) { }
    if ($is_responsive): ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php endif; ?>
    <title>Backup & Restore - București Transport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css?v=<?= time() ?>">
    <style>
        .backup-card { margin-top: 20px; }
        .btn-export { background: #27ae60; color: white; padding: 10px 15px; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; }
        .btn-export:hover { background: #2ecc71; }
        .btn-import { background: #2980b9; color: white; padding: 10px 15px; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; margin-top: 10px; }
        .btn-import:hover { background: #3498db; }
    </style>
</head>
<body class="<?= (isset($is_responsive) && $is_responsive) ? 'is-responsive' : '' ?>">

<header class="admin-header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars" style="color:white;"></i></button>
        <i class="fas fa-bus"></i> Admin Panel
    </div>
    <div>
        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['admin_user']) ?>
    </div>
</header>

<div class="wrapper">
    <div class="sidebar" id="sidebar">
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard & Setări</a>
        <a href="schedules.php"><i class="fas fa-clock"></i> Gestiune Orar & Linii</a>
        <a href="create_lines.php"><i class="fas fa-route"></i> Creează Linii</a>
        <a href="draw_lines.php"><i class="fas fa-draw-polygon"></i> Desenează Linii</a>
        <a href="metro_editor.php"><i class="fas fa-subway"></i> Desenează Harta Metrou</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Administrează Utilizatori</a>
        <a href="manage_tickets.php"><i class="fas fa-ticket-alt"></i> Plăți prin SMS</a>
        <a href="backup.php" class="active"><i class="fas fa-save"></i> Backup / Restore</a>
        <a href="../public/index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Vezi site-ul</a>
        <a href="index.php?action=logout" style="color: #e74c3c; margin-top: 50px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h1>Backup și Restaurare</h1>
        </div>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error" style="color:red; margin-bottom:15px; padding:10px; background-color:#ffdddd; border-radius:5px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card backup-card">
            <h2>Exportă Date (Backup)</h2>
            <p style="color: #7f8c8d; font-size: 14px;">Generează un fișier ZIP cu o copie completă a bazei de date și fișierele încărcate (ex: logo).</p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="export">
                <button type="submit" class="btn-export"><i class="fas fa-download"></i> Descarcă Backup</button>
            </form>
        </div>

        <div class="card backup-card">
            <h2>Importă Date (Restore)</h2>
            <p style="color: #7f8c8d; font-size: 14px;">Restaurare sistem folosind un fișier ZIP de backup creat anterior.</p>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import">
                <div class="form-group">
                    <label>Încarcă fișierul ZIP:</label><br>
                    <input type="file" name="backup_file" accept=".zip" required style="margin-bottom: 10px;">
                </div>
                <button type="submit" class="btn-import"><i class="fas fa-upload"></i> Restaurează</button>
            </form>
        </div>

    </div>
</div>

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