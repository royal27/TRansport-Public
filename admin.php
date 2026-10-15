<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth/login.php");
    die();
}

$pdo = getDB();

// Handle license generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_license') {
    $user_id = $_POST['user_id'];
    $plan_type = $_POST['plan_type'];
    $license_key = implode('-', str_split(strtoupper(bin2hex(random_bytes(10))), 5));

    $stmt = $pdo->prepare("INSERT INTO licenses (user_id, license_key, plan_type) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $license_key, $plan_type]);

    header("Location: admin.php?success=1");
    die();
}

// Handle license status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $license_id = $_POST['license_id'];
    $new_status = $_POST['new_status'];

    $stmt = $pdo->prepare("UPDATE licenses SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $license_id]);

    header("Location: admin.php?success=1");
    die();
}

// Fetch all users
$stmt = $pdo->query("SELECT id, username, email FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all licenses
$stmt = $pdo->query("
    SELECT l.id, l.license_key, l.plan_type, l.status, l.created_at, u.username, u.email
    FROM licenses l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
");
$licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - 3D Tools</title>
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
<body class="bg-bgMain text-textMain font-sans min-h-screen">

    <!-- Admin Navigation -->
    <nav class="bg-bgSec border-b border-borderClr shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-textMain tracking-wider">ADMIN<span class="text-brandBlue">PANEL</span></span>
                </div>
                <div class="flex space-x-4 items-center">
                    <a href="index.php" class="text-textSec hover:text-textMain px-3 py-2 text-sm">View Site</a>
                    <a href="auth/logout.php" class="bg-card hover:bg-borderClr border border-borderClr text-textMain px-3 py-1.5 rounded text-sm transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Generate License Form -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-bgSec border border-borderClr rounded-lg p-6">
                <h2 class="text-lg font-bold text-textMain mb-4 border-b border-borderClr pb-2">Generate License</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="generate_license">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm text-textSec mb-1">Select User</label>
                            <select name="user_id" required class="w-full bg-card border border-borderClr rounded px-3 py-2 text-textMain focus:outline-none focus:border-brandBlue">
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['email']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-textSec mb-1">Plan Type</label>
                            <select name="plan_type" required class="w-full bg-card border border-borderClr rounded px-3 py-2 text-textMain focus:outline-none focus:border-brandBlue">
                                <option value="free">Free</option>
                                <option value="personal">Personal</option>
                                <option value="professional">Professional</option>
                                <option value="lifetime">Lifetime</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-brandBlue hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition">
                            Create License
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- License Management -->
        <div class="lg:col-span-2">
            <div class="bg-bgSec border border-borderClr rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-borderClr bg-card">
                    <h2 class="text-lg font-bold text-textMain">Manage Licenses</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-card/50">
                            <tr class="text-textSec border-b border-borderClr">
                                <th class="px-6 py-3 font-semibold">User</th>
                                <th class="px-6 py-3 font-semibold">Key</th>
                                <th class="px-6 py-3 font-semibold">Plan</th>
                                <th class="px-6 py-3 font-semibold">Status</th>
                                <th class="px-6 py-3 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-borderClr">
                            <?php foreach ($licenses as $lic): ?>
                            <tr class="hover:bg-card/50 transition">
                                <td class="px-6 py-4">
                                    <div class="text-textMain font-medium"><?= htmlspecialchars($lic['username']) ?></div>
                                    <div class="text-xs text-textSec"><?= htmlspecialchars($lic['email']) ?></div>
                                </td>
                                <td class="px-6 py-4 font-mono text-textSec text-xs"><?= htmlspecialchars($lic['license_key']) ?></td>
                                <td class="px-6 py-4 capitalize"><?= htmlspecialchars($lic['plan_type']) ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($lic['status'] === 'active'): ?>
                                        <span class="inline-flex px-2 py-1 rounded text-xs font-medium bg-green-900/30 text-green-400">Active</span>
                                    <?php elseif ($lic['status'] === 'revoked'): ?>
                                        <span class="inline-flex px-2 py-1 rounded text-xs font-medium bg-red-900/30 text-red-400">Revoked</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-1 rounded text-xs font-medium bg-gray-800 text-gray-300"><?= htmlspecialchars($lic['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="license_id" value="<?= $lic['id'] ?>">
                                        <?php if ($lic['status'] === 'active'): ?>
                                            <input type="hidden" name="new_status" value="revoked">
                                            <button type="submit" class="text-red-400 hover:text-red-300 text-xs border border-red-900 px-2 py-1 rounded bg-red-900/20">Revoke</button>
                                        <?php else: ?>
                                            <input type="hidden" name="new_status" value="active">
                                            <button type="submit" class="text-green-400 hover:text-green-300 text-xs border border-green-900 px-2 py-1 rounded bg-green-900/20">Activate</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
