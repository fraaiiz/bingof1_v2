<section class="login-page">
    <div class="login-wrapper">
        <div class="login-header">
            <h1>Connexion</h1>
        </div>

        <?php if (!empty($authError)): ?>
            <p class="auth-error" role="alert"><?= htmlspecialchars($authError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST" action="/login" id="login-form" class="login-form needs-validation" autocomplete="on" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="username" class="form-label">Pseudo/email</label>
                <input type="text" id="username" name="username" class="form-control" autocomplete="username" placeholder="Votre pseudo/email" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Se connecter
            </button>

            <p class="login-footer mt-3 mb-0">
                Pas encore de compte ?
                <a href="/register">Créer un compte</a>
            </p>
        </form>
    </div>
</section>