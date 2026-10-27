const form = document.querySelector('form.login-form');
const password = document.getElementById('password');
const confirmation = document.getElementById('password-confirmation');

const complexityMessage = 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
const mismatchMessage = 'Les deux mots de passe sont différents.';

const isComplex = (value) => Array.from(value).length >= 8
    && /\p{Ll}/u.test(value)
    && /\p{Lu}/u.test(value)
    && /\p{N}/u.test(value)
    && /[^\p{L}\p{N}]/u.test(value);

if (form instanceof HTMLFormElement && password instanceof HTMLInputElement && confirmation instanceof HTMLInputElement) {
    const refresh = () => {
        if (password.value === '' || isComplex(password.value)) {
            password.setCustomValidity('');
        } else {
            password.setCustomValidity(complexityMessage);
        }

        if (confirmation.value === '' || password.value === confirmation.value) {
            confirmation.setCustomValidity('');
        } else {
            confirmation.setCustomValidity(mismatchMessage);
        }
    };

    password.addEventListener('input', () => {
        refresh();
        if (password.value !== '') {
            password.reportValidity();
        }
    });

    confirmation.addEventListener('input', () => {
        refresh();
        if (confirmation.value !== '') {
            confirmation.reportValidity();
        }
    });
}
