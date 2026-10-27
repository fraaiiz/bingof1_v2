<section class="login-page">
    <div class="login-wrapper">
        <div class="login-header">
            <h1>Nouveau mot de passe</h1>
        </div>

        <?php if (!empty($notice)): ?>
            <p class="<?= $notice['type'] === 'success' ? 'auth-notice' : 'auth-error' ?>" role="status">
                <?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if (empty($notice) || $notice['type'] !== 'error' || ($notice['message'] ?? '') !== 'Ce lien est invalide ou a expiré.'): ?>
            <form method="post" action="/mot-de-passe-oublie/<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>" class="login-form" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-group">
                    <label for="password" class="form-label">Nouveau mot de passe</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" class="form-control" autocomplete="new-password" required>
                        <?php require __DIR__ . '/../partials/password-toggle.php'; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password-confirmation" class="form-label">Confirmer le mot de passe</label>
                    <div class="password-field">
                        <input type="password" id="password-confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                        <?php require __DIR__ . '/../partials/password-toggle.php'; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
            </form>
            <script src="/assets/js/password-rules.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/password-rules.js') ?>" defer></script>
            <script src="/assets/js/password-toggle.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/password-toggle.js') ?>" defer></script>
        <?php endif; ?>
        <p class="login-footer"><a href="/mot-de-passe-oublie">Demander un nouveau lien</a></p>
    </div>
</section>
