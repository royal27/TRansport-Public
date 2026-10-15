<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing - 3D Tools</title>
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
    <nav class="bg-bgSec border-b border-borderClr">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-xl font-bold text-textMain tracking-wider">3D<span class="text-brandOrange">TOOLS</span></a>
                </div>
                <div class="flex space-x-4">
                    <a href="index.php" class="text-textSec hover:text-textMain px-3 py-2 text-sm font-medium">Home</a>
                    <a href="download.php" class="text-textSec hover:text-textMain px-3 py-2 text-sm font-medium">Download</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Pricing Section -->
    <div class="flex-grow py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h1 class="text-4xl font-extrabold text-textMain mb-4">Pricing & Licensing</h1>
                <p class="text-textSec text-lg max-w-2xl mx-auto">Alege planul potrivit pentru workflow-ul tău. Simplu, transparent, fără costuri ascunse.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                <!-- Free Plan -->
                <div class="bg-bgSec border border-borderClr rounded-lg p-6 flex flex-col hover:border-brandBlue/50 transition">
                    <h3 class="text-xl font-bold text-textMain mb-2">Free</h3>
                    <p class="text-sm text-textSec mb-4 min-h-[40px]">Pentru utilizatori noi și testare.</p>
                    <div class="text-3xl font-extrabold text-textMain mb-6">$0<span class="text-lg font-normal text-textSec">/mo</span></div>
                    <ul class="text-sm text-textSec space-y-3 flex-grow mb-8">
                        <li class="flex items-center"><span class="text-brandBlue mr-2">✓</span> Funcții de bază</li>
                        <li class="flex items-center"><span class="text-brandBlue mr-2">✓</span> 1 Device</li>
                        <li class="flex items-center text-textSec/50"><span class="mr-2">✗</span> Suport prioritar</li>
                    </ul>
                    <a href="auth/register.php" class="text-center w-full bg-card hover:bg-borderClr border border-borderClr text-textMain font-bold py-2 rounded transition">Get Started</a>
                </div>

                <!-- Personal Plan -->
                <div class="bg-card border border-borderClr rounded-lg p-6 flex flex-col hover:border-brandOrange/50 transition relative transform md:-translate-y-2 shadow-xl shadow-brandOrange/5">
                    <div class="absolute top-0 right-0 bg-brandOrange text-white text-xs font-bold px-3 py-1 rounded-bl-lg rounded-tr-lg">POPULAR</div>
                    <h3 class="text-xl font-bold text-brandOrange mb-2">Personal</h3>
                    <p class="text-sm text-textSec mb-4 min-h-[40px]">Pentru artiști individuali (uz comercial permis).</p>
                    <div class="text-3xl font-extrabold text-textMain mb-6">$15<span class="text-lg font-normal text-textSec">/mo</span></div>
                    <ul class="text-sm text-textSec space-y-3 flex-grow mb-8">
                        <li class="flex items-center"><span class="text-brandOrange mr-2">✓</span> Toate funcțiile deblocate</li>
                        <li class="flex items-center"><span class="text-brandOrange mr-2">✓</span> Până la 2 Devices</li>
                        <li class="flex items-center"><span class="text-brandOrange mr-2">✓</span> Suport standard</li>
                    </ul>
                    <a href="auth/register.php" class="text-center w-full bg-brandOrange hover:bg-orange-600 text-white font-bold py-2 rounded transition">Subscribe</a>
                </div>

                <!-- Professional Plan -->
                <div class="bg-bgSec border border-borderClr rounded-lg p-6 flex flex-col hover:border-brandBlue/50 transition">
                    <h3 class="text-xl font-bold text-textMain mb-2">Professional</h3>
                    <p class="text-sm text-textSec mb-4 min-h-[40px]">Pentru studiouri și utilizatori avansați.</p>
                    <div class="text-3xl font-extrabold text-textMain mb-6">$35<span class="text-lg font-normal text-textSec">/mo</span></div>
                    <ul class="text-sm text-textSec space-y-3 flex-grow mb-8">
                        <li class="flex items-center"><span class="text-brandBlue mr-2">✓</span> Licențe de echipă</li>
                        <li class="flex items-center"><span class="text-brandBlue mr-2">✓</span> 5+ Devices</li>
                        <li class="flex items-center"><span class="text-brandBlue mr-2">✓</span> Suport prioritar (24/7)</li>
                    </ul>
                    <a href="auth/register.php" class="text-center w-full bg-card hover:bg-borderClr border border-borderClr text-textMain font-bold py-2 rounded transition">Subscribe</a>
                </div>

                <!-- Lifetime Plan -->
                <div class="bg-bgSec border border-borderClr rounded-lg p-6 flex flex-col hover:border-textMain/50 transition">
                    <h3 class="text-xl font-bold text-textMain mb-2">Lifetime</h3>
                    <p class="text-sm text-textSec mb-4 min-h-[40px]">Plată unică. Acces nelimitat la versiunea curentă.</p>
                    <div class="text-3xl font-extrabold text-textMain mb-6">$199<span class="text-lg font-normal text-textSec">/once</span></div>
                    <ul class="text-sm text-textSec space-y-3 flex-grow mb-8">
                        <li class="flex items-center"><span class="text-textMain mr-2">✓</span> Plată unică</li>
                        <li class="flex items-center"><span class="text-textMain mr-2">✓</span> Actualizări minore incluse</li>
                        <li class="flex items-center"><span class="text-textMain mr-2">✓</span> Până la 3 Devices</li>
                    </ul>
                    <a href="auth/register.php" class="text-center w-full bg-card hover:bg-borderClr border border-borderClr text-textMain font-bold py-2 rounded transition">Buy Now</a>
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
