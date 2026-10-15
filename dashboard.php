<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    die();
}

$pdo = getDB();
$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch licenses
$stmt = $pdo->prepare("SELECT license_key, plan_type, status, created_at FROM licenses WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - 3D Tools</title>
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
<body class="bg-bgMain text-textMain font-sans overflow-x-hidden min-h-screen flex flex-col">

    <!-- Navigation -->
    <nav class="bg-bgSec border-b border-borderClr sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-xl font-bold text-textMain tracking-wider">3D<span class="text-brandOrange">TOOLS</span></a>
                </div>
                <div class="flex space-x-4 items-center">
                    <span class="text-sm text-textSec">Welcome, <strong class="text-textMain"><?= htmlspecialchars($user['username']) ?></strong></span>
                    <a href="index.php" class="text-textSec hover:text-textMain px-3 py-2 text-sm font-medium">Home</a>
                    <a href="auth/logout.php" class="bg-card hover:bg-borderClr border border-borderClr text-textMain px-3 py-1.5 rounded text-sm transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="flex-grow py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">

            <!-- Sidebar -->
            <div class="md:col-span-1">
                <div class="bg-bgSec border border-borderClr rounded-lg p-4 sticky top-24">
                    <div class="text-xs font-bold text-textSec uppercase tracking-wider mb-4 px-2">Dashboard Menu</div>
                    <nav class="space-y-1">
                        <a href="#licenses" class="bg-card border-l-4 border-brandOrange text-textMain block px-3 py-2 text-sm font-medium rounded-r">
                            My Licenses
                        </a>
                        <a href="#downloads" class="text-textSec hover:bg-card hover:text-textMain block px-3 py-2 text-sm font-medium rounded transition border-l-4 border-transparent hover:border-borderClr">
                            My Downloads
                        </a>
                        <a href="#settings" class="text-textSec hover:bg-card hover:text-textMain block px-3 py-2 text-sm font-medium rounded transition border-l-4 border-transparent hover:border-borderClr">
                            Account Settings
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="md:col-span-3 space-y-8">

                <!-- Licenses Section -->
                <div id="licenses" class="bg-bgSec border border-borderClr rounded-lg overflow-hidden shadow-lg">
                    <div class="px-6 py-4 border-b border-borderClr flex justify-between items-center bg-card">
                        <h2 class="text-lg font-bold text-textMain">My Licenses</h2>
                        <a href="pricing.php" class="text-xs bg-brandOrange hover:bg-orange-600 text-white px-3 py-1.5 rounded transition">Buy New License</a>
                    </div>
                    <div class="p-6">
                        <?php if (empty($licenses)): ?>
                            <div class="text-center py-8 text-textSec border border-dashed border-borderClr rounded">
                                <p>Nu ai nicio licență activă în prezent.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-left text-sm">
                                    <thead>
                                        <tr class="text-textSec border-b border-borderClr">
                                            <th class="pb-3 font-semibold">License Key</th>
                                            <th class="pb-3 font-semibold">Plan</th>
                                            <th class="pb-3 font-semibold">Status</th>
                                            <th class="pb-3 font-semibold">Purchased</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-borderClr">
                                        <?php foreach ($licenses as $lic): ?>
                                        <tr>
                                            <td class="py-4 text-textMain font-mono bg-card px-2 rounded inline-block mt-2 mr-2 border border-borderClr">
                                                <?= htmlspecialchars($lic['license_key']) ?>
                                            </td>
                                            <td class="py-4 text-textMain capitalize"><?= htmlspecialchars($lic['plan_type']) ?></td>
                                            <td class="py-4">
                                                <?php if ($lic['status'] === 'active'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-900/30 text-green-400 border border-green-800">Active</span>
                                                <?php elseif ($lic['status'] === 'revoked'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-900/30 text-red-400 border border-red-800">Revoked</span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-800 text-gray-300 border border-gray-700"><?= htmlspecialchars($lic['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 text-textSec"><?= date('M j, Y', strtotime($lic['created_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Downloads Section -->
                <div id="downloads" class="bg-bgSec border border-borderClr rounded-lg overflow-hidden shadow-lg">
                    <div class="px-6 py-4 border-b border-borderClr bg-card">
                        <h2 class="text-lg font-bold text-textMain">My Downloads</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between p-4 border border-borderClr rounded bg-bgMain hover:border-brandBlue/50 transition">
                            <div>
                                <h4 class="text-textMain font-bold text-base mb-1">3D Tools Installer (Windows)</h4>
                                <p class="text-xs text-textSec">Version 1.2.0 &bull; 15 MB &bull; Requires 3ds Max 2021+</p>
                            </div>
                            <a href="download.php" class="bg-brandBlue hover:bg-blue-600 text-white px-4 py-2 rounded text-sm font-semibold transition">
                                Download
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Settings Section -->
                <div id="settings" class="bg-bgSec border border-borderClr rounded-lg overflow-hidden shadow-lg">
                    <div class="px-6 py-4 border-b border-borderClr bg-card">
                        <h2 class="text-lg font-bold text-textMain">Account Settings</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4 max-w-md">
                            <div>
                                <label class="block text-xs text-textSec mb-1">Username</label>
                                <input type="text" disabled value="<?= htmlspecialchars($user['username']) ?>" class="w-full bg-bgMain border border-borderClr rounded px-3 py-2 text-textSec cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-xs text-textSec mb-1">Email Address</label>
                                <input type="email" disabled value="<?= htmlspecialchars($user['email']) ?>" class="w-full bg-bgMain border border-borderClr rounded px-3 py-2 text-textSec cursor-not-allowed">
                            </div>
                            <div class="text-xs text-textSec pt-2">
                                Account created on: <?= date('F j, Y', strtotime($user['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-bgSec border-t border-borderClr py-6 text-center text-sm text-textSec mt-auto">
        &copy; <?= date('Y') ?> 3D Tools. All rights reserved.
    </footer>

</body>
</html>
