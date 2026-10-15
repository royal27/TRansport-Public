<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Tools - Professional Workflow</title>
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
                    },
                    backgroundImage: {
                        'grid-pattern': "linear-gradient(to right, #3B3E43 1px, transparent 1px), linear-gradient(to bottom, #3B3E43 1px, transparent 1px)"
                    }
                }
            }
        }
    </script>
    <style>
        .bg-grid {
            background-size: 40px 40px;
            background-image:
                linear-gradient(to right, rgba(59, 62, 67, 0.2) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(59, 62, 67, 0.2) 1px, transparent 1px);
        }
    </style>
</head>
<body class="bg-bgMain text-textMain font-sans overflow-x-hidden">

    <!-- Navigation -->
    <nav class="bg-bgSec border-b border-borderClr sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="index.php" class="text-xl font-bold text-textMain tracking-wider">3D<span class="text-brandOrange">TOOLS</span></a>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <a href="index.php" class="bg-card text-textMain px-3 py-2 rounded-md text-sm font-medium">Home</a>
                            <a href="#features" class="text-textSec hover:text-textMain px-3 py-2 rounded-md text-sm font-medium transition">Features</a>
                            <a href="#showcase" class="text-textSec hover:text-textMain px-3 py-2 rounded-md text-sm font-medium transition">Gallery</a>
                            <a href="pricing.php" class="text-textSec hover:text-textMain px-3 py-2 rounded-md text-sm font-medium transition">Pricing</a>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="text-textSec hover:text-textMain px-3 py-2 text-sm font-medium">Dashboard</a>
                        <a href="auth/logout.php" class="text-textSec hover:text-brandOrange px-3 py-2 text-sm font-medium">Logout</a>
                    <?php else: ?>
                        <a href="auth/login.php" class="text-textSec hover:text-textMain px-3 py-2 text-sm font-medium">Login</a>
                    <?php endif; ?>
                    <a href="download.php" class="bg-brandOrange hover:bg-orange-600 text-white px-4 py-2 rounded-md text-sm font-bold shadow-lg shadow-brandOrange/20 transition">Download</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative bg-bgMain bg-grid">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-24 lg:pt-32 lg:pb-40">
            <div class="lg:grid lg:grid-cols-12 lg:gap-8">
                <div class="sm:text-center md:max-w-2xl md:mx-auto lg:col-span-6 lg:text-left">
                    <div class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold text-brandBlue bg-brandBlue/10 mb-6 border border-brandBlue/20">
                        V1.2.0 is now available
                    </div>
                    <h1 class="text-4xl tracking-tight font-extrabold text-textMain sm:text-5xl md:text-6xl lg:text-5xl xl:text-6xl">
                        <span class="block">YOUR 3DS MAX WORKFLOW.</span>
                        <span class="block text-brandOrange">REIMAGINED.</span>
                    </h1>
                    <p class="mt-3 text-base text-textSec sm:mt-5 sm:text-xl lg:text-lg xl:text-xl">
                        Powerful tools. Smarter workflows. Professional results. Descoperă o nouă modalitate de a lucra în Autodesk 3ds Max, cu instrumente concepute pentru a simplifica procesul de lucru și a-ți accelera proiectele 3D.
                    </p>
                    <div class="mt-8 sm:max-w-lg sm:mx-auto sm:text-center lg:text-left lg:mx-0 flex gap-4">
                        <a href="download.php" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-bold rounded-md text-white bg-brandOrange hover:bg-orange-600 shadow-lg shadow-brandOrange/30 md:py-4 md:text-lg md:px-10 transition">
                            DOWNLOAD NOW
                        </a>
                        <a href="#features" class="inline-flex items-center justify-center px-8 py-3 border border-borderClr text-base font-bold rounded-md text-textMain bg-bgSec hover:bg-card md:py-4 md:text-lg md:px-10 transition">
                            EXPLORE FEATURES
                        </a>
                    </div>
                </div>
                <div class="mt-12 relative sm:max-w-lg sm:mx-auto lg:mt-0 lg:max-w-none lg:mx-0 lg:col-span-6 lg:flex lg:items-center">
                    <div class="relative mx-auto w-full rounded-lg shadow-2xl lg:max-w-md bg-card border border-borderClr overflow-hidden transform hover:-translate-y-1 transition duration-500">
                        <!-- Toolbar Mockup -->
                        <div class="h-8 bg-bgSec border-b border-borderClr flex items-center px-3 space-x-2">
                            <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                            <span class="ml-4 text-xs text-textSec font-semibold">3DTools_Interface.max</span>
                        </div>
                        <!-- App Screenshot Placeholder -->
                        <div class="h-64 bg-bgMain relative flex items-center justify-center p-4">
                            <div class="absolute inset-0 bg-grid opacity-30"></div>
                            <div class="z-10 grid grid-cols-2 gap-4 w-full">
                                <div class="bg-card p-3 border border-borderClr rounded text-xs text-textSec">
                                    <div class="text-brandOrange font-bold mb-1">Automation</div>
                                    <div class="h-2 bg-bgSec rounded mb-1 w-3/4"></div>
                                    <div class="h-2 bg-bgSec rounded w-1/2"></div>
                                </div>
                                <div class="bg-card p-3 border border-borderClr rounded text-xs text-textSec">
                                    <div class="text-brandBlue font-bold mb-1">Smart Tools</div>
                                    <div class="h-2 bg-bgSec rounded mb-1 w-full"></div>
                                    <div class="h-2 bg-bgSec rounded w-2/3"></div>
                                </div>
                                <div class="col-span-2 bg-bgSec h-20 border border-borderClr rounded flex items-center justify-center">
                                    <span class="text-textSec font-bold tracking-widest">[ 3D VIEWPORT ]</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-16 bg-bgSec">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-base text-brandOrange font-semibold tracking-wide uppercase">Core Functionality</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-textMain sm:text-4xl">
                    Professional tools for your workflow
                </p>
            </div>

            <div class="mt-16">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">

                    <div class="bg-card border border-borderClr rounded-lg p-6 hover:border-brandOrange/50 transition duration-300 transform hover:-translate-y-1">
                        <div class="w-12 h-12 bg-bgMain border border-borderClr rounded flex items-center justify-center mb-4 text-brandOrange font-bold text-xl">
                            { }
                        </div>
                        <h3 class="text-lg font-bold text-textMain mb-2">Automation Tools</h3>
                        <p class="text-sm text-textSec">Automatizează sarcinile repetitive din scena ta pentru a economisi timp valoros.</p>
                    </div>

                    <div class="bg-card border border-borderClr rounded-lg p-6 hover:border-brandBlue/50 transition duration-300 transform hover:-translate-y-1">
                        <div class="w-12 h-12 bg-bgMain border border-borderClr rounded flex items-center justify-center mb-4 text-brandBlue font-bold text-xl">
                            R
                        </div>
                        <h3 class="text-lg font-bold text-textMain mb-2">Rendering Workflow</h3>
                        <p class="text-sm text-textSec">Setează și gestionează parametrii de randare mult mai eficient.</p>
                    </div>

                    <div class="bg-card border border-borderClr rounded-lg p-6 hover:border-brandOrange/50 transition duration-300 transform hover:-translate-y-1">
                        <div class="w-12 h-12 bg-bgMain border border-borderClr rounded flex items-center justify-center mb-4 text-brandOrange font-bold text-xl">
                            ->
                        </div>
                        <h3 class="text-lg font-bold text-textMain mb-2">Export Management</h3>
                        <p class="text-sm text-textSec">Exportă modele și scene în multiple formate dintr-un singur clic.</p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Showcase / Gallery -->
    <div id="showcase" class="py-16 bg-bgMain bg-grid">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-textMain">See it in Action</h2>
                <p class="mt-4 text-textSec">Interfață nativă, integrată perfect în mediul tău de lucru.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-card border border-borderClr p-2 rounded-lg">
                    <div class="bg-bgSec aspect-video flex items-center justify-center border border-borderClr border-dashed rounded">
                        <span class="text-textSec font-semibold">[ Gameplay / Tool Demo GIF ]</span>
                    </div>
                </div>
                <div class="bg-card border border-borderClr p-2 rounded-lg">
                    <div class="bg-bgSec aspect-video flex items-center justify-center border border-borderClr border-dashed rounded">
                        <span class="text-textSec font-semibold">[ Settings Interface Screenshot ]</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Download Banner -->
    <div class="bg-bgSec border-t border-b border-borderClr">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
            <h2 class="text-3xl font-extrabold tracking-tight text-textMain sm:text-4xl">
                <span class="block">Ready to upgrade your workflow?</span>
                <span class="block text-brandBlue">Download the latest version today.</span>
            </h2>
            <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                <div class="inline-flex rounded-md shadow">
                    <a href="download.php" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-bold rounded-md text-bgMain bg-textMain hover:bg-gray-200 transition">
                        Download for Windows
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-bgMain border-t border-borderClr pt-12 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <span class="text-xl font-bold text-textMain">3D<span class="text-brandOrange">TOOLS</span></span>
                    <p class="text-sm text-textSec mt-2">Professional software for 3D Artists.</p>
                </div>
                <div class="flex space-x-6 text-sm text-textSec">
                    <a href="#" class="hover:text-brandOrange transition">Documentation</a>
                    <a href="pricing.php" class="hover:text-brandOrange transition">Licenses</a>
                    <a href="#" class="hover:text-brandOrange transition">Support</a>
                    <a href="#" class="hover:text-brandOrange transition">Privacy Policy</a>
                </div>
            </div>
            <div class="mt-8 border-t border-borderClr pt-8 text-center text-xs text-textSec">
                &copy; <?= date('Y') ?> 3D Tools Software. All rights reserved.
            </div>
        </div>
    </footer>

</body>
</html>
