document.querySelectorAll('.password-toggle').forEach((button) => {
    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    const field = button.closest('.password-field');
    const input = field instanceof HTMLElement ? field.querySelector('input') : null;
    if (!(input instanceof HTMLInputElement)) {
        return;
    }

    button.addEventListener('click', () => {
        const revealed = input.type === 'password';
        input.type = revealed ? 'text' : 'password';
        button.classList.toggle('is-revealed', revealed);
        button.setAttribute('aria-pressed', revealed ? 'true' : 'false');
        button.setAttribute('aria-label', revealed ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    });
});
