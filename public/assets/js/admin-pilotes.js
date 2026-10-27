const engagementForm = document.querySelector('[data-engagement-form]');

if (engagementForm) {
    const seasonSelect = engagementForm.querySelector('[name="saison_id"]');
    const teamSelect = engagementForm.querySelector('[name="ecurie_id"]');
    const emptyMessage = engagementForm.querySelector('[data-teams-empty]');
    const submit = engagementForm.querySelector('[data-engagement-submit]');
    const teams = JSON.parse(engagementForm.dataset.teams || '[]');

    if (seasonSelect && teamSelect) {

    const syncTeams = () => {
        const seasonId = Number(seasonSelect.value);
        const previous = teamSelect.value;
        teamSelect.replaceChildren();
        teams.filter((team) => team.saison_id === seasonId).forEach((team) => {
            const option = document.createElement('option');
            option.value = String(team.id);
            option.textContent = team.nom_ecurie;
            teamSelect.appendChild(option);
        });
        if ([...teamSelect.options].some((option) => option.value === previous)) {
            teamSelect.value = previous;
        }
        const none = teamSelect.options.length === 0;
        if (submit) {
            submit.disabled = none;
        }
        if (emptyMessage) {
            emptyMessage.hidden = !none;
        }
    };

    seasonSelect.addEventListener('change', syncTeams);
    syncTeams();
    }
}

const photoInput = document.querySelector('[data-photo-input]');
const photoPreview = document.querySelector('[data-photo-preview]');

if (photoInput && photoPreview) {
    const image = photoPreview.querySelector('img');
    const name = photoPreview.querySelector('[data-photo-name]');

    photoInput.addEventListener('change', () => {
        const file = photoInput.files && photoInput.files[0];
        if (!file || !image || !name) {
            photoPreview.hidden = true;
            if (image) {
                image.removeAttribute('src');
            }
            if (name) {
                name.textContent = '';
            }
            return;
        }
        image.src = URL.createObjectURL(file);
        image.alt = 'Aperçu de la photo sélectionnée';
        name.textContent = file.name;
        photoPreview.hidden = false;
    });
}
