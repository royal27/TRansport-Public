<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Center - 3D Tools</title>
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
                    <a href="pricing.php" class="text-textSec hover:text-textMain px-3 py-2 text-sm font-medium">Pricing</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Download Section -->
    <div class="flex-grow py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="bg-card border border-borderClr rounded-lg overflow-hidden shadow-2xl">
                <div class="p-8 md:flex md:items-center md:justify-between border-b border-borderClr bg-bgSec">
                    <div>
                        <h1 class="text-3xl font-extrabold text-textMain mb-2">Download Center</h1>
                        <p class="text-brandBlue font-semibold mb-1">Version 1.2.0 <span class="text-textSec font-normal text-sm ml-2">Updated: Oct 15, 2023</span></p>
                    </div>
                    <div class="mt-6 md:mt-0">
                        <button class="w-full md:w-auto bg-brandOrange hover:bg-orange-600 text-white font-bold py-3 px-8 rounded shadow-lg shadow-brandOrange/20 transition flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            DOWNLOAD FOR WINDOWS
                        </button>
                    </div>
                </div>

                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Requirements -->
                    <div>
                        <h3 class="text-lg font-bold text-textMain mb-4 border-b border-borderClr pb-2">System Requirements</h3>
                        <ul class="text-sm text-textSec space-y-3">
                            <li><strong class="text-textMain">OS:</strong> Windows 10 / 11 (64-bit)</li>
                            <li><strong class="text-textMain">Autodesk 3ds Max:</strong> 2021, 2022, 2023, 2024</li>
                            <li><strong class="text-textMain">RAM:</strong> Minimum 8GB (16GB recommended)</li>
                            <li><strong class="text-textMain">Disk Space:</strong> 50MB available space</li>
                        </ul>
                    </div>

                    <!-- Instructions -->
                    <div>
                        <h3 class="text-lg font-bold text-textMain mb-4 border-b border-borderClr pb-2">Installation Guide</h3>
                        <ol class="list-decimal list-inside text-sm text-textSec space-y-2">
                            <li>Download the `.mzp` installation file.</li>
                            <li>Drag and drop the file into your 3ds Max viewport.</li>
                            <li>Follow the on-screen prompt to install.</li>
                            <li>Go to <strong>Customize > Customize User Interface</strong> to add the tool to your toolbar.</li>
                            <li>Enter your License Key when first launched.</li>
                        </ol>
                    </div>
                </div>

                <!-- Release Notes -->
                <div class="p-8 bg-bgMain border-t border-borderClr">
                    <h3 class="text-lg font-bold text-textMain mb-4">Release Notes (v1.2.0)</h3>
                    <ul class="list-disc list-inside text-sm text-textSec space-y-1">
                        <li>Added new smart automation scripts for UV unwrapping.</li>
                        <li>Improved UI responsiveness inside 3ds Max panels.</li>
                        <li>Fixed a bug related to exporting large scenes.</li>
                        <li>Updated licensing verification module for offline stability.</li>
                    </ul>
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
