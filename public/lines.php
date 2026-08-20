<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/translations.php';

$lang = $_GET['lang'] ?? 'ro';
if (!in_array($lang, ['ro', 'en', 'fr'])) $lang = 'ro';

$db = getDB();
$stmt = $db->query("SELECT * FROM custom_lines ORDER BY name ASC");
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" data-theme="green">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orar și Linii Curente</title>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { display: flex; height: 100vh; overflow: hidden; background-color: #f4f7f6; }
        .page-content { max-width: 1000px; margin: 30px auto; padding: 0 20px; flex: 1; width: 100%; box-sizing: border-box; display: flex; flex-direction: column; }
        .page-title { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid var(--primary); padding-bottom: 10px; }
        .lines-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .line-card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 5px solid #ccc; transition: transform 0.2s; cursor: pointer; text-decoration: none; color: inherit; display: block; }
        .line-card:hover { transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0,0,0,0.1); }
        .line-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .line-name { font-size: 1.2em; font-weight: bold; }
        .line-desc { color: #666; font-size: 0.9em; }
    </style>
</head>
<body style="display: flex; flex-direction: row; height: 100vh; margin: 0; overflow: hidden; background-color: #f4f7f6;">

    <nav class="left-nav">
        <div class="nav-top">
            <a href="index.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_map', $lang) ?>"><i class="fas fa-map-marker-alt"></i></a>
            <a href="schedules.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_schedules', $lang) ?>"><i class="fas fa-clock"></i></a>
            <a href="lines.php?lang=<?= $lang ?>" class="nav-item active" title="Orar și Linii Curente"><i class="fas fa-route"></i></a>
            <a href="flights.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_flights', $lang) ?>"><i class="fas fa-plane"></i></a>
            <a href="metro.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_metro', $lang) ?>"><i class="fas fa-subway"></i></a>
            <a href="route.php?lang=<?= $lang ?>" class="nav-item" title="Organizează rută"><i class="fas fa-directions"></i></a>
        </div>
        <div class="nav-bottom">
            <div class="lang-selector-nav">
                <a href="?lang=ro" class="<?= $lang=='ro'?'active':'' ?>">RO</a>
                <a href="?lang=en" class="<?= $lang=='en'?'active':'' ?>">EN</a>
                <a href="?lang=fr" class="<?= $lang=='fr'?'active':'' ?>">FR</a>
            </div>
            <a href="account.php?lang=<?= $lang ?>" class="nav-item" title="Contul meu"><i class="fas fa-user-circle"></i></a>
            <a href="/admin/index.php" class="nav-item" title="Admin"><i class="fas fa-cog"></i></a>
        </div>
    </nav>

    <div class="page-content">
        <h1 class="page-title"><i class="fas fa-route"></i> Orar și Linii Curente (Live STB / Custom)</h1>
        <p style="color: #555; margin-bottom: 30px;">Alege o linie pentru a vedea traseul pe hartă și a începe urmărirea live (Live Trace).</p>

        <div class="lines-grid">
            <?php foreach($lines as $line): ?>
                <a href="index.php?custom_line_id=<?= $line['id'] ?>" class="line-card" style="border-left-color: <?= htmlspecialchars($line['color']) ?>;">
                    <div class="line-header">
                        <span class="line-name"><?= htmlspecialchars($line['name']) ?></span>
                        <i class="fas fa-bus-alt" style="color: <?= htmlspecialchars($line['color']) ?>;"></i>
                    </div>
                    <div class="line-desc"><?= htmlspecialchars($line['description']) ?></div>
                </a>
            <?php endforeach; ?>

            <?php if(count($lines) === 0): ?>
                <p>Nicio linie customizată nu a fost creată încă din panoul de admin.</p>
            <?php endif; ?>
        </div>
    </div>

</div>
</body>
</html>
