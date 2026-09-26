<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Florian Teindas">
    <link rel="icon" href="/assets/images/favicon.ico"/>
    <title><?= htmlspecialchars($title ?? 'BingoF1') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/fonts/fonts.css">

<body>
    <header>
        <img src="/assets/images/logo_bingof1.png" alt="logo_site">
    </header>

    <nav>
        <ul>
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
                    <li class="nav-item submenu-item menu-item" tabindex="0">
                        <button type="button" aria-expanded="false">2027</button>
                        <ul>
                            <li><a href="/saisons/2027/infos">INFOS</a></li>
                            <li><a href="/saisons/2027/calendrier">CALENDRIER</a></li>
                            <li><a href="/saisons/2027/classement">CLASSEMENT</a></li>
                        </ul>
                    </li>
                    <li class="nav-item submenu-item menu-item" tabindex="0">
                        <button type="button" aria-expanded="false">2026</button>
                        <ul>
                            <li><a href="/saisons/2026/infos">INFOS</a></li>
                            <li><a href="/saisons/2026/calendrier">CALENDRIER</a></li>
                            <li><a href="/saisons/2026/classement">CLASSEMENT</a></li>
                        </ul>
                    </li>
                </ul>
            </li>

            <li class="nav-item nav-link" data-href="/login" tabindex="0"><span>CONNEXION</span></li>
        </ul>
    </nav>

    <main>
        <?= $content ?>
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

        function closeMenus(except = null) {
            document.querySelectorAll('.menu-item').forEach((menuItem) => {
                if (menuItem !== except) {
                    menuItem.classList.remove('open');
                    const button = menuItem.querySelector(':scope > button');
                    if (button) button.setAttribute('aria-expanded', 'false');
                }
            });
        }

        menuButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                const menuItem = button.closest('.menu-item');
                const isOpen = menuItem.classList.contains('open');

                closeMenus(isOpen ? null : menuItem);

                if (!isOpen) {
                    menuItem.classList.add('open');
                    button.setAttribute('aria-expanded', 'true');
                }
            });
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.menu-item')) {
                closeMenus();
            }
        });
    </script>

</body>
</html>