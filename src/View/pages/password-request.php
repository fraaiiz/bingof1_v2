<section class="login-page">
    <div class="login-wrapper">
        <div class="login-header">
            <h1>Mot de passe oublié</h1>
        </div>

        <?php if (!empty($notice)): ?>
            <p class="<?= $notice['type'] === 'success' ? 'auth-notice' : 'auth-error' ?>" role="status">
                <?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="post" action="/mot-de-passe-oublie" class="login-form" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <p class="login-footer">Indiquez l’adresse email du compte. Si elle est reconnue, vous recevrez un lien valable une heure.</p>
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" maxlength="255" autocomplete="email" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Envoyer le lien</button>
            <p class="login-footer"><a href="/login">Retour à la connexion</a></p>
        </form>
    </div>
</section>
