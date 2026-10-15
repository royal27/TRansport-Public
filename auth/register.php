<?php
session_start();
require_once '../includes/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    die();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Vă rugăm să completați toate câmpurile.';
    } else {
        $pdo = getDB();

        // Check if email or username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->rowCount() > 0) {
            $error = 'Email-ul sau numele de utilizator există deja.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed])) {
                $success = 'Cont creat cu succes! Vă puteți autentifica.';
            } else {
                $error = 'A apărut o eroare la crearea contului.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - 3D Tools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bgMain: '#18191C',
                        bgSec: '#222428',
                        card: '#292C31',
                        brandOrange: '#F59A23',
                        brandBlue: '#3997FF',
                        textMain: '#F5F5F5',
                        textSec: '#A6A9AF',
                        borderClr: '#3B3E43'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-bgMain text-textMain min-h-screen flex items-center justify-center font-sans">

<div class="bg-bgSec p-8 rounded-lg shadow-xl w-full max-w-md border border-borderClr">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold">Create an Account</h2>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500 text-red-500 px-4 py-3 rounded mb-4 text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-500/10 border border-green-500 text-green-500 px-4 py-3 rounded mb-4 text-sm text-center">
            <?= htmlspecialchars($success) ?><br><br>
            <a href="login.php" class="text-brandBlue hover:underline">Go to Login</a>
        </div>
    <?php else: ?>

    <form method="POST" action="" class="space-y-4">
        <div>
            <label class="block text-textSec mb-1 text-sm">Username</label>
            <input type="text" name="username" required class="w-full bg-card border border-borderClr rounded px-3 py-2 text-textMain focus:outline-none focus:border-brandOrange transition">
        </div>
        <div>
            <label class="block text-textSec mb-1 text-sm">Email Address</label>
            <input type="email" name="email" required class="w-full bg-card border border-borderClr rounded px-3 py-2 text-textMain focus:outline-none focus:border-brandOrange transition">
        </div>
        <div>
            <label class="block text-textSec mb-1 text-sm">Password</label>
            <input type="password" name="password" required class="w-full bg-card border border-borderClr rounded px-3 py-2 text-textMain focus:outline-none focus:border-brandOrange transition">
        </div>

        <button type="submit" class="w-full bg-brandOrange hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded transition mt-4">
            Sign Up
        </button>
    </form>

    <?php endif; ?>

    <div class="mt-6 text-center text-sm text-textSec">
        Already have an account? <a href="login.php" class="text-brandBlue hover:underline">Sign in</a>
    </div>
</div>

</body>
</html>
