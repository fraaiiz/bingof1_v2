<section class="login-page">
    <div class="login-wrapper">
        <div class="login-header">
            <h1>Créer un compte</h1>
        </div>

        <?php if (!empty($authError)): ?>
            <p class="auth-error" role="alert"><?= htmlspecialchars($authError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST" action="/register" class="login-form" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="username" class="form-label">Pseudo</label>
                <input type="text" id="username" name="username" class="form-control" minlength="3" maxlength="100" autocomplete="username" required>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" maxlength="255" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Mot de passe</label>
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

            <button type="submit" class="btn btn-primary w-100">Créer mon compte</button>
            <p class="login-footer mt-3 mb-0"><a href="/login">Déjà un compte ? Se connecter</a></p>
        </form>
        <script src="/assets/js/password-rules.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/password-rules.js') ?>" defer></script>
        <script src="/assets/js/password-toggle.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/password-toggle.js') ?>" defer></script>
    </div>
</section>