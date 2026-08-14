<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>București Transport Live</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    <!-- FontAwesome pentru iconite -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div id="app-container">
        <!-- Sidebar / Panel principal -->
        <div id="sidebar">
            <div class="sidebar-header">
                <h2>București Transport</h2>
                <p>Vezi instant ce vine la stația ta.</p>
            </div>

            <div id="station-info" class="hidden">
                <div class="station-header">
                    <button id="btn-back" class="btn-icon"><i class="fas fa-arrow-left"></i></button>
                    <h3 id="station-name">Nume Stație</h3>
                </div>
                <div id="arrivals-list">
                    <!-- Aici vor veni datele ajax -->
                    <div class="loading">Se încarcă...</div>
                </div>
            </div>

            <div id="welcome-info">
                <div class="instructions">
                    <i class="fas fa-hand-pointer fa-2x"></i>
                    <p>Apasă pe o stație pe hartă pentru a vedea următoarele sosiri.</p>
                </div>
            </div>
        </div>

        <!-- Harta -->
        <div id="map"></div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script src="js/app.js"></script>
</body>
</html>