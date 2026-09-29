const carousel = document.querySelector('[data-infos-carousel]');

if (carousel) {
    const layout = carousel.closest('.infos-content');
    const teamData = JSON.parse(carousel.querySelector('[data-team-data]').textContent);
    const teamCard = carousel.querySelector('[data-team-card]');
    const teamLogo = carousel.querySelector('[data-team-logo]');
    const teamCar = carousel.querySelector('[data-team-car]');
    const logoPlaceholder = carousel.querySelector('[data-team-logo-placeholder]');
    const pilotGrid = layout.querySelector('[data-pilote-grid]');
    const pilotCardTemplate = pilotGrid.querySelector('.card-pilote')?.cloneNode(true);
    const reserves = layout.querySelector('[data-team-reserves]');
    const reserveList = layout.querySelector('[data-team-reserves-list]');
    const previousButton = carousel.querySelector('[data-previous]');
    const nextButton = carousel.querySelector('[data-next]');
    const position = carousel.querySelector('[data-position]');
    const teamName = carousel.querySelector('[data-team-name]');
    const teamNationality = carousel.querySelector('[data-team-nationality]');
    const teamCreation = carousel.querySelector('[data-team-creation]');
    const teamEngine = carousel.querySelector('[data-team-engine]');
    const teamTitles = carousel.querySelector('[data-team-titles]');
    let activeIndex = 0;

    function getAssetPath(path) {
        if (!path) {
            return null;
        }

        const normalizedPath = `/${String(path).replace(/^\/+/, '')}`;
        return normalizedPath.startsWith('/assets/') ? normalizedPath : `/assets${normalizedPath}`;
    }

    function createPilotCard(pilot) {
        if (!pilotCardTemplate) {
            return null;
        }

        const card = pilotCardTemplate.cloneNode(true);
        const photoContainer = card.querySelector('.driver-photo');
        const photoPath = getAssetPath(pilot.photo_pilote);
        let image = photoContainer.querySelector('img');
        const photoPlaceholder = photoContainer.querySelector('.driver-photo-placeholder');

        if (!image) {
            image = document.createElement('img');
            image.addEventListener('error', () => {
                image.hidden = true;
                photoPlaceholder.hidden = false;
            });
            photoContainer.prepend(image);
        }

        image.hidden = photoPath === null;
        if (photoPath !== null) {
            image.src = photoPath;
            image.alt = `Photo de ${pilot.prenom_pilote} ${pilot.nom_pilote}`;
        }
        if (photoPlaceholder) {
            photoPlaceholder.hidden = photoPath !== null;
        }

        const details = card.querySelector('.driver-details');
        const name = details.querySelector('h2, h3');
        name.textContent = `${pilot.numero_pilote} - ${pilot.prenom_pilote} ${pilot.nom_pilote}`;
        const values = [
            pilot.nationalite_pilote,
            pilot.date_debut ?? 'Non renseignée',
            pilot.nombre_titre_pilote ?? 0,
        ];
        details.querySelectorAll('p').forEach((field, index) => {
            if (values[index] === undefined) {
                return;
            }

            const label = field.textContent.match(/^[^:]+:\s*/)?.[0] ?? '';
            field.textContent = `${label}${values[index]}`;
        });

        return card;
    }

    function showTeam(index) {
        activeIndex = (index + teamData.length) % teamData.length;
        const team = teamData[activeIndex];
        const logoPath = getAssetPath(team.logo);
        const carPath = getAssetPath(team.voiture);

        teamName.textContent = team.nom_ecurie;
        teamCard.querySelector('.ecurie-title-block h2').textContent = team.nom_ecurie;
        teamNationality.textContent = `Nationalité : ${team.nationalite_ecurie}`;
        teamCreation.textContent = `Création : ${team.annee_creation}`;
        teamEngine.textContent = `Motoriste : ${team.nom_motoriste}`;
        teamTitles.hidden = false;
        teamTitles.textContent = `Titres constructeurs : ${team.nombre_titre ?? 0}`;
        logoPlaceholder.textContent = team.nom_court;
        logoPlaceholder.hidden = logoPath !== null;
        teamLogo.hidden = logoPath === null;
        if (logoPath !== null) {
            teamLogo.src = logoPath;
            teamLogo.alt = `Logo ${team.nom_ecurie}`;
        }
        teamCar.hidden = carPath === null;
        if (carPath !== null) {
            teamCar.src = carPath;
            teamCar.alt = `Voiture ${team.nom_ecurie}`;
        }

        pilotGrid.replaceChildren(...team.pilotes.map(createPilotCard).filter(Boolean));
        reserves.hidden = team.reserves.length === 0;
        reserveList.textContent = team.reserves
            .map((pilot) => `${pilot.prenom_pilote} ${pilot.nom_pilote}`)
            .join(', ');
        position.textContent = `Écurie ${activeIndex + 1} / ${teamData.length}`;
    }

    previousButton.addEventListener('click', () => showTeam(activeIndex - 1));
    nextButton.addEventListener('click', () => showTeam(activeIndex + 1));
    showTeam(activeIndex);
}