<section class="admin-settings">
    <p class="admin-settings-back"><a href="/admin">Retour à l’administration</a></p>
    <h1>Paramètres</h1>

    <?php if (!empty($notice)): ?>
        <p class="admin-settings-notice admin-settings-notice--<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8') ?>" role="status">
            <?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <article class="admin-settings-card">
        <h2>Site</h2>
        <p>État : <strong><?= !empty($settings['maintenance']) ? 'En maintenance' : 'En service' ?></strong></p>
        <p>Les visiteurs voient une page d’attente. La connexion reste disponible, et un administrateur connecté garde l’accès au site.</p>
        <form method="post" action="/admin/settings">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="setting" value="maintenance">
            <input type="hidden" name="enabled" value="<?= !empty($settings['maintenance']) ? '0' : '1' ?>">
            <button type="submit"><?= !empty($settings['maintenance']) ? 'Rouvrir le site' : 'Activer la maintenance' ?></button>
        </form>
    </article>

    <article class="admin-settings-card">
        <h2>Chat</h2>
        <p>État : <strong><?= !empty($settings['chat_open']) ? 'Ouvert' : 'Fermé' ?></strong></p>
        <p>Les messages déjà postés restent visibles. L’écriture est coupée pour tout le monde.</p>
        <form method="post" action="/admin/settings">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="setting" value="chat">
            <input type="hidden" name="enabled" value="<?= !empty($settings['chat_open']) ? '0' : '1' ?>">
            <button type="submit"><?= !empty($settings['chat_open']) ? 'Fermer le chat' : 'Ouvrir le chat' ?></button>
        </form>
    </article>
</section>
