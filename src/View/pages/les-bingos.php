<section class="bingo-list-page">
    <h1>Les Bingos <?= (int) $year ?></h1>

    <?php if ($grids === []): ?>
        <p class="bingo-list-empty">Aucune grille n’a encore été enregistrée pour cette saison.</p>
    <?php else: ?>
        <?php
        $selectedUserId = (int) $grids[0]['user_id'];
        foreach ($grids as $grid) {
            if ($requestedUserId !== null && (int) $grid['user_id'] === $requestedUserId) {
                $selectedUserId = (int) $grid['user_id'];
                break;
            }
        }
        ?>
        <div class="bingo-list-layout" data-bingo-list>
            <div class="bingo-users-column" aria-label="Joueurs">
                <?php foreach ($grids as $grid): ?>
                    <button
                        type="button"
                        class="bingo-user"
                        data-bingo-user="<?= (int) $grid['user_id'] ?>"
                        aria-pressed="<?= (int) $grid['user_id'] === $selectedUserId ? 'true' : 'false' ?>"
                    ><?= htmlspecialchars($grid['user_login'], ENT_QUOTES, 'UTF-8') ?></button>
                <?php endforeach; ?>
            </div>

            <div class="bingo-preview-column">
                <?php foreach ($grids as $grid): ?>
                    <article class="bingo-preview-card" data-bingo-preview="<?= (int) $grid['user_id'] ?>"<?= (int) $grid['user_id'] !== $selectedUserId ? ' hidden' : '' ?>>
                        <h2>Bingo de <?= htmlspecialchars($grid['user_login'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <div class="mini-bingo-grid" aria-label="Grille de bingo de <?= htmlspecialchars($grid['user_login'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php foreach ($grid['cells'] as $position => $cell): ?>
                                <?php $label = $cell['label'] !== '' ? $cell['label'] : 'Case ' . $position; ?>
                                <button
                                    type="button"
                                    class="mini-bingo-cell<?= $cell['validated'] ? ' checked-cell' : '' ?>"
                                    data-view-cell
                                    data-position="<?= (int) $position ?>"
                                    data-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"
                                    data-validated="<?= $cell['validated'] ? 'true' : 'false' ?>"
                                    aria-label="Case <?= (int) $position ?> : <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?><?= $cell['validated'] ? ', validée' : '' ?>"
                                ><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <dialog class="bingo-cell-dialog" data-bingo-cell-dialog aria-labelledby="bingo-cell-dialog-title">
            <div class="bingo-cell-dialog-content">
                <header class="bingo-cell-dialog-header">
                    <h2 id="bingo-cell-dialog-title" data-dialog-title></h2>
                    <button type="button" class="bingo-cell-dialog-close" data-close-cell-dialog aria-label="Fermer">&times;</button>
                </header>
                <div class="bingo-cell-dialog-preview">
                    <p>Case <span data-dialog-position></span></p>
                    <div data-dialog-label></div>
                    <span class="bingo-cell-dialog-status" data-dialog-status hidden>Case validée</span>
                </div>
            </div>
        </dialog>
    <?php endif; ?>
</section>
<script src="/assets/js/bingo.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/bingo.js') ?>" defer></script>
