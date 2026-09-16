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

$stmt = $db->query("SELECT * FROM tickets_sms ORDER BY id ASC");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" data-theme="<?= htmlspecialchars($theme_color) ?>">
<head>
    <meta charset="UTF-8">
    <?php
    // Get responsive mode setting
    $is_responsive = true; // Default
    if (isset($db)) {
        try {
            $resp_stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'responsive_mode'");
            $resp_row = $resp_stmt->fetch(PDO::FETCH_ASSOC);
            if ($resp_row && $resp_row['setting_value'] === '0') {
                $is_responsive = false;
            }
        } catch(Exception $e) { }
    }
    if ($is_responsive): ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php endif; ?>
    <title>Cumpără Ticket SMS - <?= htmlspecialchars($app_name) ?></title>

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <style>
        body { display: flex; flex-direction: row; height: 100vh; margin: 0; overflow: hidden; background-color: #f4f7f6; }
        .content-container { flex: 1; overflow-y: auto; padding: 40px; display: flex; flex-direction: column; align-items: center; }

        .page-header { text-align: center; margin-bottom: 40px; }
        .page-header h1 { color: var(--primary-color, #2ecc71); font-size: 2.5em; margin-bottom: 10px; }
        .page-header p { color: #666; font-size: 1.1em; max-width: 600px; margin: 0 auto; }

        .tickets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            width: 100%;
            max-width: 1100px;
        }

        .ticket-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }

        .ticket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px rgba(0,0,0,0.12);
        }

        .ticket-header {
            background-color: var(--primary-color, #2ecc71);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .ticket-header h3 { margin: 0; font-size: 1.4em; }

        .ticket-body {
            padding: 25px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .sms-instruction {
            background: #f8f9fa;
            border: 1px dashed #ccc;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }

        .sms-instruction .send-to {
            font-size: 0.9em;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .sms-instruction .number {
            font-size: 2em;
            font-weight: bold;
            color: #333;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .sms-instruction .text-required {
            font-size: 1.1em;
            color: #555;
        }

        .sms-instruction .text-required strong {
            color: var(--primary-color, #2ecc71);
            font-size: 1.2em;
            background: rgba(46, 204, 113, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
        }

        .ticket-price {
            text-align: center;
            font-size: 1.3em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .ticket-desc {
            color: #666;
            font-size: 0.95em;
            line-height: 1.5;
            text-align: center;
            margin-bottom: 25px;
            flex: 1;
        }

        .btn-buy {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #3498db;
            color: white;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            border-radius: 8px;
            font-size: 1.1em;
            transition: background 0.3s;
        }

        .btn-buy:hover { background-color: #2980b9; }

        .mobile-only-warning {
            text-align: center;
            font-size: 0.85em;
            color: #999;
            margin-top: 10px;
        }

    </style>
</head>
<body class="<?= (isset($is_responsive) && $is_responsive) ? 'is-responsive' : '' ?>">

    <nav class="left-nav">
        <?php if (!empty($logo_path)): ?>
            <div class="sidebar-logo-container" style="text-align: center; padding: 10px 0;">
                <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" style="max-height: 50px; max-width: 100%;">
            </div>
        <?php endif; ?>
        <div class="nav-top">
            <a href="index.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_map', $lang) ?>"><i class="fas fa-map-marker-alt"></i></a>
            <a href="schedules.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_schedules', $lang) ?>"><i class="fas fa-clock"></i></a>
            <a href="lines.php?lang=<?= $lang ?>" class="nav-item" title="Orar și Linii Curente"><i class="fas fa-route"></i></a>
            <a href="flights.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_flights', $lang) ?>"><i class="fas fa-plane"></i></a>
            <a href="metro.php?lang=<?= $lang ?>" class="nav-item" title="<?= getTranslation('btn_metro', $lang) ?>"><i class="fas fa-subway"></i></a>
            <a href="route.php?lang=<?= $lang ?>" class="nav-item" title="Organizează rută"><i class="fas fa-directions"></i></a>
            <a href="tickets.php?lang=<?= $lang ?>" class="nav-item active" title="Cumpără Ticket"><i class="fas fa-ticket-alt"></i></a>
        </div>
        <div class="nav-bottom">
            <div class="lang-selector-nav">
                <a href="?lang=ro" class="<?= $lang=='ro'?'active':'' ?>">🇷🇴</a>
                <a href="?lang=en" class="<?= $lang=='en'?'active':'' ?>">🇬🇧</a>
                <a href="?lang=fr" class="<?= $lang=='fr'?'active':'' ?>">🇫🇷</a>
                <a href="?lang=es" class="<?= $lang=='es'?'active':'' ?>">🇪🇸</a>
            </div>
            <a href="account.php?lang=<?= $lang ?>" class="nav-item" title="Contul meu"><i class="fas fa-user-circle"></i></a>
            <a href="/admin/index.php" class="nav-item" title="Admin"><i class="fas fa-cog"></i></a>
        </div>
    </nav>

    <div class="content-container">

        <div class="page-header">
            <h1><i class="fas fa-mobile-alt"></i> Cumpără Bilet prin SMS</h1>
            <p>Plătește rapid și sigur călătoria ta direct de pe telefonul mobil. Trimite un SMS la numărul scurt și primești confirmarea instant.</p>
        </div>

        <?php if (count($tickets) > 0): ?>
            <div class="tickets-grid">
                <?php foreach ($tickets as $t): ?>
                    <div class="ticket-card">
                        <div class="ticket-header">
                            <h3><?= htmlspecialchars($t['name']) ?></h3>
                        </div>
                        <div class="ticket-body">

                            <div class="sms-instruction">
                                <div class="send-to">Trimite SMS la</div>
                                <div class="number"><?= htmlspecialchars($t['sms_number']) ?></div>
                                <div class="text-required">Text: <strong><?= htmlspecialchars($t['sms_text']) ?></strong></div>
                            </div>

                            <div class="ticket-price">Preț: <?= htmlspecialchars($t['price']) ?></div>

                            <div class="ticket-desc">
                                <?= nl2br(htmlspecialchars($t['description'])) ?>
                            </div>

                            <!-- The sms: link format works on mobile devices to pre-fill the SMS app -->
                            <a href="sms:<?= htmlspecialchars($t['sms_number']) ?>?body=<?= urlencode($t['sms_text']) ?>" class="btn-buy"><i class="fas fa-paper-plane"></i> Trimite SMS Acum</a>
                            <div class="mobile-only-warning">*Butonul funcționează doar de pe telefonul mobil.</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center;">
                <i class="fas fa-ticket-alt" style="font-size: 50px; color: #ccc; margin-bottom: 20px;"></i>
                <h2>Nu există bilete configurate.</h2>
                <p style="color: #777;">Administratorul nu a adăugat încă opțiuni de plată prin SMS.</p>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
