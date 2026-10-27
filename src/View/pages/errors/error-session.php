<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BingoF1 - Session indisponible</title>
    <link rel="icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/fonts/fonts.css">
    <?php $maintenanceStyle = __DIR__ . '/../../../../public/assets/css/pages/maintenance.css'; ?>
    <link rel="stylesheet" href="/assets/css/pages/maintenance.css?v=<?= filemtime($maintenanceStyle) ?>">
</head>
<body class="maintenance">
    <header>
        <img src="/assets/images/logo_bingof1.png" alt="BingoF1">
    </header>
    <main class="maintenance-page">
        <h1>Session indisponible</h1>
        <p>La session n’a pas pu être vérifiée.<br> Réessayez dans un instant.</p>
    </main>
</body>
</html>
