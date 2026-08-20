<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/translations.php';

$lang = $_GET['lang'] ?? 'ro';
if (!in_array($lang, ['ro', 'en', 'fr'])) $lang = 'ro';

// Fetch settings
$db = getDB();
$stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('theme_color', 'app_name')");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$theme_color = $settings['theme_color'] ?? 'green';
$app_name = $settings['app_name'] ?? 'București Transport Live';

$error = '';
$success = '';

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';

    if (empty($email) || empty($password) || empty($name)) {
        $error = "Toate câmpurile sunt obligatorii.";
    } else {
        // Check if exists
        $chk = $db->prepare("SELECT id FROM app_users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->rowCount() > 0) {
            $error = "Există deja un cont cu acest email.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $db->prepare("INSERT INTO app_users (email, password, name, phone) VALUES (?, ?, ?, ?)");
            if ($ins->execute([$email, $hashed, $name, $phone])) {
                $success = "Cont creat cu succes. Vă puteți autentifica.";
            } else {
                $error = "Eroare la crearea contului.";
            }
        }
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Introduceți email-ul și parola.";
    } else {
        $sel = $db->prepare("SELECT * FROM app_users WHERE email = ?");
        $sel->execute([$email]);
        $user = $sel->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['app_user_id'] = $user['id'];
            $_SESSION['app_user_name'] = $user['name'];
            $_SESSION['app_user_email'] = $user['email'];
            header("Location: account.php?lang=" . $lang);
            die();
        } else {
            $error = "Email sau parolă incorecte.";
        }
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['app_user_id']);
    unset($_SESSION['app_user_name']);
    unset($_SESSION['app_user_email']);
    header("Location: account.php?lang=" . $lang);
    die();
}

$is_logged_in = isset($_SESSION['app_user_id']);

?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" data-theme="<?= htmlspecialchars($theme_color) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contul meu - <?= htmlspecialchars($app_name) ?></title>

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">
    <style>
        body { display: flex; flex-direction: row; height: 100vh; margin: 0; overflow: hidden; background-color: #f4f7f6; }
        .content-container { flex: 1; overflow-y: auto; padding: 40px; display: flex; justify-content: center; align-items: flex-start; }

        .account-box {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 800px;
        }

        .auth-forms {
            display: flex;
            gap: 40px;
            margin-top: 20px;
        }

        .auth-form {
            flex: 1;
            background: #fafafa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .auth-form h3 { margin-top: 0; color: var(--primary-color, #2ecc71); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #555; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { width: 100%; padding: 10px; background-color: var(--primary-color, #2ecc71); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { opacity: 0.9; }

        .error-msg { color: #dc3545; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success-msg { color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; }

        .user-dashboard {
            text-align: center;
        }

        .user-dashboard i {
            font-size: 60px;
            color: var(--primary-color, #2ecc71);
            margin-bottom: 20px;
        }

        .btn-logout {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .btn-logout:hover { background-color: #c0392b; }

        /* Responsive */
        @media (max-width: 768px) {
            .auth-forms { flex-direction: column; }
        }
    </style>
</head>
<body>

    <nav class="left-nav">
        <div class="nav-top">
            <a href="index.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_map', $lang) ?>"><i class="fas fa-map-marker-alt"></i></a>
            <a href="schedules.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_schedules', $lang) ?>"><i class="fas fa-clock"></i></a>
            <a href="lines.php?lang=<?= $lang ?>" class="nav-item" title="Orar și Linii Curente"><i class="fas fa-route"></i></a>
            <a href="flights.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_flights', $lang) ?>"><i class="fas fa-plane"></i></a>
            <a href="metro.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_metro', $lang) ?>"><i class="fas fa-subway"></i></a>
            <a href="route.php?lang=<?= $lang ?>" class="nav-item" title="Organizează rută"><i class="fas fa-directions"></i></a>
            <a href="tickets.php?lang=<?= $lang ?>" class="nav-item" title="Cumpără Ticket"><i class="fas fa-ticket-alt"></i></a>
        </div>
        <div class="nav-bottom">
            <div class="lang-selector-nav">
                <a href="?lang=ro" class="<?= $lang=='ro'?'active':'' ?>">RO</a>
                <a href="?lang=en" class="<?= $lang=='en'?'active':'' ?>">EN</a>
                <a href="?lang=fr" class="<?= $lang=='fr'?'active':'' ?>">FR</a>
            </div>
            <a href="account.php?lang=<?= $lang ?>" class="nav-item active" title="Contul meu"><i class="fas fa-user-circle"></i></a>
            <a href="/admin/index.php" class="nav-item" title="Admin"><i class="fas fa-cog"></i></a>
        </div>
    </nav>

    <div class="content-container">
        <div class="account-box">
            <h2><i class="fas fa-user-circle"></i> Contul meu</h2>

            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-msg"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($is_logged_in): ?>
                <div class="user-dashboard">
                    <i class="fas fa-user"></i>
                    <h3>Salut, <?= htmlspecialchars($_SESSION['app_user_name']) ?>!</h3>
                    <p>Email: <?= htmlspecialchars($_SESSION['app_user_email']) ?></p>
                    <p>Bine ai venit în aplicația <?= htmlspecialchars($app_name) ?>. În curând vei putea salva liniile favorite și rutele preferate!</p>

                    <a href="account.php?lang=<?= $lang ?>&logout=1" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Deconectare</a>
                </div>
            <?php else: ?>
                <p>Pentru a salva liniile favorite și a primi notificări, autentifică-te sau creează un cont nou.</p>
                <div class="auth-forms">

                    <div class="auth-form">
                        <h3>Autentificare</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="login">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label>Parolă</label>
                                <input type="password" name="password" required>
                            </div>
                            <button type="submit" class="btn-submit">Log in</button>
                        </form>
                    </div>

                    <div class="auth-form">
                        <h3>Creare cont nou</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="register">
                            <div class="form-group">
                                <label>Nume complet</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>Nr. Telefon (Opțional)</label>
                                <input type="text" name="phone">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label>Parolă</label>
                                <input type="password" name="password" required>
                            </div>
                            <button type="submit" class="btn-submit">Înregistrare</button>
                        </form>
                    </div>

                </div>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>
