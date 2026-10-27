<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BingoF1 - Maintenance</title>
    <link rel="icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/fonts/fonts.css">
    <?php $maintenanceStyle = __DIR__ . '/../../../public/assets/css/pages/maintenance.css'; ?>
    <link rel="stylesheet" href="/assets/css/pages/maintenance.css?v=<?= filemtime($maintenanceStyle) ?>">
</head>
<body class="maintenance">
    <header>
        <img src="/assets/images/logo_bingof1.png" alt="BingoF1">
    </header>
    <main class="maintenance-page">
        <h1>Site en maintenance</h1>
        <p>BingoF1 est momentanément indisponible.<br> Merci de revenir un peu plus tard.</p>
        <?php if (!empty($isLoggedIn)): ?>
            <form method="post" action="/logout">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">Déconnexion</button>
            </form>
        <?php else: ?>
            <p><a href="/login">Connexion</a></p>
        <?php endif; ?>
    </main>
</body>
</html>
