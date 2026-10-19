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
        <ul id="primary-navigation">
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

    <script>
        const navItems = document.querySelectorAll('.nav-item[data-href]');

        navItems.forEach((item) => {
            item.addEventListener('click', (event) => {
                if (event.target.closest('button')) {
                    return;
                }
                const href = item.dataset.href;
                if (href) {
                    window.location.href = href;
                }
            });

            item.addEventListener('keydown', (event) => {
                if ((event.key === 'Enter' || event.key === ' ') && !event.target.closest('button')) {
                    event.preventDefault();
                    const href = item.dataset.href;
                    if (href) {
                        window.location.href = href;
                    }
                }
            });
        });

        const menuButtons = document.querySelectorAll('.menu-item > button');
        const nav = document.querySelector('nav');
        const navToggle = document.querySelector('.nav-toggle');
        const mobileNavigation = window.matchMedia('(max-width: 700px)');
        const mobileMenuStack = [];

        function updateMobileNavigationPosition() {
            if (!mobileNavigation.matches || !nav.classList.contains('open')) {
                return;
            }
            const headerBottom = document.querySelector('header').getBoundingClientRect().bottom;
            nav.style.setProperty('--mobile-navigation-top', `${Math.max(0, headerBottom)}px`);
        }

        nav.querySelectorAll('.menu-item > ul').forEach((submenu) => {
            const backItem = document.createElement('li');
            backItem.className = 'nav-back-item';
            const backButton = document.createElement('button');
            backButton.type = 'button';
            backButton.dataset.navBack = '';
            backButton.textContent = 'Retour';
            backItem.append(backButton);
            submenu.append(backItem);
        });

        function resetMobilePanels() {
            mobileMenuStack.forEach(({ item, panel, parentPanel }) => {
                item.classList.remove('mobile-current');
                panel.classList.remove('mobile-panel');
                parentPanel.classList.remove('mobile-covered');
            });
            mobileMenuStack.length = 0;
        }

        function closeNavigation() {
            nav.classList.remove('open');
            navToggle.setAttribute('aria-expanded', 'false');
            navToggle.setAttribute('aria-label', 'Ouvrir le menu');
            document.body.classList.remove('mobile-navigation-open');
            nav.style.removeProperty('--mobile-navigation-top');
            resetMobilePanels();
            closeMenus();
        }

        function closeMenus(except = null) {
            document.querySelectorAll('.menu-item').forEach((menuItem) => {
                if (except === null || (menuItem !== except && !menuItem.contains(except))) {
                    menuItem.classList.remove('open');
                    const button = menuItem.querySelector(':scope > button');
                    if (button) button.setAttribute('aria-expanded', 'false');
                }
            });
        }

        navToggle.addEventListener('click', () => {
            const isOpen = nav.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', String(isOpen));
            navToggle.setAttribute('aria-label', isOpen ? 'Fermer le menu' : 'Ouvrir le menu');
            document.body.classList.toggle('mobile-navigation-open', isOpen && mobileNavigation.matches);
            updateMobileNavigationPosition();
            if (!isOpen) {
                resetMobilePanels();
                closeMenus();
            }
        });

        menuButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                const menuItem = button.closest('.menu-item');

                if (mobileNavigation.matches) {
                    const panel = menuItem.querySelector(':scope > ul');
                    if (!panel) {
                        return;
                    }

                    const parentPanel = menuItem.parentElement;
                    menuItem.classList.add('mobile-current');
                    parentPanel.classList.add('mobile-covered');
                    panel.classList.add('mobile-panel');
                    button.setAttribute('aria-expanded', 'true');
                    mobileMenuStack.push({ item: menuItem, panel, parentPanel, button });
                    return;
                }

                const isOpen = menuItem.classList.contains('open');

                closeMenus(isOpen ? null : menuItem);

                if (!isOpen) {
                    menuItem.classList.add('open');
                    button.setAttribute('aria-expanded', 'true');
                }
            });
        });

        nav.addEventListener('click', (event) => {
            if (!event.target.closest('[data-nav-back]')) {
                return;
            }
            event.stopPropagation();
            const current = mobileMenuStack.pop();
            if (!current) {
                return;
            }
            current.item.classList.remove('mobile-current');
            current.panel.classList.remove('mobile-panel');
            current.parentPanel.classList.remove('mobile-covered');
            current.button.setAttribute('aria-expanded', 'false');
        });

        document.addEventListener('click', (event) => {
            if (mobileNavigation.matches && nav.classList.contains('open') &&
                (event.target === nav || event.target === nav.querySelector('#primary-navigation'))) {
                closeNavigation();
                return;
            }
            if (!event.target.closest('.menu-item')) {
                closeMenus();
            }
            if (!event.target.closest('nav')) {
                closeNavigation();
            }
        });

        nav.querySelectorAll('a, .nav-link').forEach((link) => {
            link.addEventListener('click', closeNavigation);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && nav.classList.contains('open')) {
                closeNavigation();
                navToggle.focus();
            }
        });

        window.matchMedia('(min-width: 701px)').addEventListener('change', (event) => {
            if (event.matches) {
                closeNavigation();
            }
        });

        window.addEventListener('resize', updateMobileNavigationPosition);
    </script>

</body>
</html>