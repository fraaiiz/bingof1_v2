<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BingoF1 - Trop de tentatives</title>
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
        <h1>Trop de tentatives</h1>
        <p>Cette adresse a envoyé trop de demandes.<br> Merci de patienter environ <?= (int) $waitMinutes ?> minutes avant de réessayer.</p>
        <img src="/assets/images/erreur.gif" alt="Time Penalty for Esteban OCON">
    </main>
</body>
</html>
