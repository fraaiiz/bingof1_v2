document.querySelectorAll('[data-image-input]').forEach((input) => {
    const preview = document.querySelector(`[data-image-preview="${input.dataset.imageInput}"]`);
    if (!preview) {
        return;
    }

    const image = preview.querySelector('img');
    const name = preview.querySelector('[data-image-name]');

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file || !image || !name) {
            preview.hidden = true;
            if (image) {
                image.removeAttribute('src');
            }
            if (name) {
                name.textContent = '';
            }
            return;
        }

        image.src = URL.createObjectURL(file);
        image.alt = 'Aperçu du fichier sélectionné';
        name.textContent = file.name;
        preview.hidden = false;
    });
});
