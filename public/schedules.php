<?php
require_once '../includes/db.php';
require_once '../includes/translations.php';

// Limba
$lang = $_GET['lang'] ?? 'ro';
if (!in_array($lang, ['ro', 'en', 'fr'])) $lang = 'ro';

// Fetch schedules
$db = getDB();
$stmt = $db->query("SELECT * FROM schedules ORDER BY line_name ASC");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Logo pt Header
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'app_logo'");
$logo_row = $stmt->fetch(PDO::FETCH_ASSOC);
$logo_path = $logo_row ? $logo_row['setting_value'] : '';

$current_date = date('d.m.Y');
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getTranslation('schedules_title', $lang) ?> - <?= getTranslation('app_name', $lang) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { display: flex; flex-direction: column; min-height: 100vh; overflow-y: auto; background-color: #f4f7f6; }
        .front-header {
            background-color: var(--primary-dark);
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .header-logo { height: 40px; }
        .header-nav a {
            color: white; text-decoration: none; margin-right: 15px; font-weight: 500; padding: 5px 10px; border-radius: 4px; transition: background 0.2s;
        }
        .header-nav a:hover, .header-nav a.active { background-color: rgba(255,255,255,0.2); }
        .header-right { display: flex; align-items: center; gap: 15px; font-size: 14px; }

        .front-footer { background-color: #2c3e50; color: white; text-align: center; padding: 10px; margin-top: auto; font-size: 13px; }

        .page-content { max-width: 1000px; margin: 30px auto; padding: 0 20px; flex: 1; width: 100%; box-sizing: border-box; }
        .page-title { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid var(--primary); padding-bottom: 10px; }

        .schedule-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .schedule-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 4px solid var(--primary); }
        .schedule-card h3 { margin-top: 0; color: var(--primary-dark); font-size: 20px; display: flex; align-items: center; gap: 10px; }
        .schedule-card p { color: #555; line-height: 1.6; white-space: pre-line; margin-bottom: 0; }

        @media (max-width: 768px) {
            .front-header { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>

    <header class="front-header">
        <div class="header-left">
            <?php if($logo_path): ?>
                <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" class="header-logo">
            <?php else: ?>
                <h2><i class="fas fa-bus-alt"></i> <?= getTranslation('app_name', $lang) ?></h2>
            <?php endif; ?>
        </div>

        <div class="header-nav">
            <a href="index.php?lang=<?= $lang ?>"><i class="fas fa-map"></i> <?= getTranslation('btn_map', $lang) ?></a>
            <a href="schedules.php?lang=<?= $lang ?>" class="active"><i class="fas fa-clock"></i> <?= getTranslation('btn_schedules', $lang) ?></a>
            <a href="flights.php?lang=<?= $lang ?>"><i class="fas fa-plane"></i> <?= getTranslation('btn_flights', $lang) ?></a>
        </div>

        <div class="header-right">
            <div class="time-info"><i class="fas fa-calendar-alt"></i> <?= $current_date ?></div>
            <div class="lang-selector">
                <a href="?lang=ro" style="color: white; text-decoration: <?= $lang=='ro'?'underline':'none' ?>">RO</a> |
                <a href="?lang=en" style="color: white; text-decoration: <?= $lang=='en'?'underline':'none' ?>">EN</a> |
                <a href="?lang=fr" style="color: white; text-decoration: <?= $lang=='fr'?'underline':'none' ?>">FR</a>
            </div>
        </div>
    </header>

    <div class="page-content">
        <h1 class="page-title"><i class="fas fa-list"></i> <?= getTranslation('schedules_title', $lang) ?></h1>

        <?php if(count($schedules) > 0): ?>
            <div class="schedule-grid">
                <?php foreach($schedules as $s): ?>
                    <div class="schedule-card">
                        <h3><i class="fas fa-route"></i> <?= htmlspecialchars($s['line_name']) ?></h3>
                        <p><?= htmlspecialchars($s['schedule_details']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #7f8c8d; padding: 40px; background: white; border-radius: 8px;">
                <i class="fas fa-info-circle fa-2x"></i><br><br>
                Nu există orare adăugate momentan. (Adaugă din Admin)
            </p>
        <?php endif; ?>
    </div>

    <footer class="front-footer">
        <?= getTranslation('footer_text', $lang) ?>
    </footer>

</body>
</html>