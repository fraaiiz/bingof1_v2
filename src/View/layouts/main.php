<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Florian Teindas">
    <link rel="icon" href="/assets/images/favicon.ico"/>
    <title><?= htmlspecialchars($title ?? 'BingoF1') ?></title>
    <?php $stylePage = ($page ?? '') === 'register' ? 'login' : ($page ?? ''); ?>
    <?php $globalStylePath = __DIR__ . '/../../../public/assets/css/style.css'; ?>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime($globalStylePath) ?>-<?= filesize($globalStylePath) ?>">
    <link rel="stylesheet" href="/assets/fonts/fonts.css">
    <?php $pageStylePath = __DIR__ . '/../../../public/assets/css/pages/' . $stylePage . '.css'; ?>
    <link rel="stylesheet" href="/assets/css/pages/<?= htmlspecialchars($stylePage) ?>.css?v=<?= file_exists($pageStylePath) ? filemtime($pageStylePath) : 0 ?>">
</head>
<body>
    <header>
        <img src="/assets/images/logo_bingof1.png" alt="logo_site">
    </header>

    <nav aria-label="Navigation principale">
        <button
            type="button"
            class="nav-toggle"
            aria-expanded="false"
            aria-controls="primary-navigation"
            aria-label="Ouvrir le menu"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>
        <?php $isAdmin = (($_SESSION['user_role'] ?? '') === 'admin'); ?>
        <ul id="primary-navigation"<?= $isAdmin ? ' class="has-admin"' : '' ?>>
            <li class="nav-item nav-link" data-href="/" tabindex="0"><span>ACCUEIL</span></li>

            <li class="nav-item menu-item" tabindex="0">
                <button type="button" aria-expanded="false">BINGO</button>
                <ul>
                    <li><a href="/mon-bingo">MON BINGO</a></li>
                    <li><a href="/les-bingos">LES BINGOS</a></li>
                </ul>
            </li>

            <li class="nav-item nav-link" data-href="/infos" tabindex="0"><span>INFOS</span></li>
            <li class="nav-item nav-link" data-href="/calendrier" tabindex="0"><span>CALENDRIER</span></li>
            <li class="nav-item nav-link" data-href="/classement" tabindex="0"><span>CLASSEMENT</span></li>

            <li class="nav-item menu-item" tabindex="0">
                <button type="button" aria-expanded="false">SAISONS</button>
                <ul>
                    <?php if ($availableSeasons === []): ?>
                        <li><span>Aucune saison disponible</span></li>
                    <?php else: ?>
                        <?php foreach ($availableSeasons as $seasonYear): ?>
                            <?php $seasonPath = '/saisons/' . rawurlencode((string) $seasonYear); ?>
                            <li class="nav-item submenu-item menu-item" tabindex="0">
                                <button type="button" aria-expanded="false"><?= htmlspecialchars((string) $seasonYear, ENT_QUOTES, 'UTF-8') ?></button>
                                <ul>
                                    <li><a href="<?= $seasonPath ?>/infos">INFOS</a></li>
                                    <li><a href="<?= $seasonPath ?>/calendrier">CALENDRIER</a></li>
                                    <li><a href="<?= $seasonPath ?>/classement">CLASSEMENT</a></li>
                                </ul>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </li>

            <?php if ($isAdmin): ?>
                <li class="nav-item nav-link" data-href="/admin" tabindex="0"><span>ADMIN</span></li>
            <?php endif; ?>

            <?php if (!empty($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <form method="POST" action="/logout">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Service\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">DÉCONNEXION</button>
                    </form>
                </li>
            <?php else: ?>
                <li class="nav-item nav-link" data-href="/login" tabindex="0"><span>CONNEXION</span></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main>
        <div class="page-content">
            <?= $content ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 BingoF1. Tous droits réservés.</p>
    </footer>
    <script src="/assets/js/nav.js"></script>
</body>
</html>