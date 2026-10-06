(() => {
    const pilotsData = document.getElementById('edit-resultat-pilotes');
    let pilots = [];
    if (pilotsData) {
        try {
            pilots = JSON.parse(pilotsData.textContent);
        } catch (error) {
            console.error('Impossible de charger la liste des pilotes.', error);
        }
    }

    const normalize = (value) => value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase();

    const inputs = document.querySelectorAll('[data-pilot-search]');
    let openInput = null;
    let repositionOpenListbox = null;

    const closeSuggestions = (input) => {
        const listbox = document.getElementById(input.getAttribute('aria-controls'));
        if (!listbox) {
            return;
        }

        listbox.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        if (openInput === input) {
            openInput = null;
            repositionOpenListbox = null;
        }
    };

    inputs.forEach((input) => {
        const listbox = document.getElementById(input.getAttribute('aria-controls'));
        const pilotIdField = input.parentElement.querySelector('[data-pilot-id]');
        const teamIdField = input.parentElement.querySelector('[data-team-id]');
        let matchingPilots = [];
        let activeIndex = -1;

        if (!listbox || !pilotIdField || !teamIdField) {
            return;
        }

        const positionListbox = () => {
            const inputBounds = input.getBoundingClientRect();
            const spaceBelow = window.innerHeight - inputBounds.bottom - 8;
            const spaceAbove = inputBounds.top - 8;
            const placeAbove = spaceBelow < 180 && spaceAbove > spaceBelow;
            const availableHeight = Math.max(100, placeAbove ? spaceAbove : spaceBelow);
            const listHeight = Math.min(260, availableHeight);

            listbox.style.left = `${inputBounds.left}px`;
            listbox.style.width = `${inputBounds.width}px`;
            listbox.style.maxHeight = `${listHeight}px`;
            listbox.style.top = placeAbove
                ? `${Math.max(8, inputBounds.top - listHeight - 4)}px`
                : `${inputBounds.bottom + 4}px`;
        };

        const selectPilot = (pilot) => {
            input.value = pilot.label;
            pilotIdField.value = pilot.id;
            teamIdField.value = pilot.teamId;
            closeSuggestions(input);
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const setActiveOption = (nextIndex) => {
            const options = listbox.querySelectorAll('[role="option"]:not([aria-disabled="true"])');
            if (options.length === 0) {
                return;
            }

            activeIndex = (nextIndex + options.length) % options.length;
            options.forEach((option, index) => {
                const isActive = index === activeIndex;
                option.classList.toggle('is-active', isActive);
                option.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            input.setAttribute('aria-activedescendant', options[activeIndex].id);
            options[activeIndex].scrollIntoView({ block: 'nearest' });
        };

        const showSuggestions = () => {
            const query = normalize(input.value.trim());
            listbox.replaceChildren();
            activeIndex = -1;
            input.removeAttribute('aria-activedescendant');

            if (!query) {
                closeSuggestions(input);
                return;
            }

            if (openInput && openInput !== input) {
                closeSuggestions(openInput);
            }

            matchingPilots = pilots
                .filter((pilot) => normalize(pilot.label).includes(query))
                .slice(0, 8);

            if (matchingPilots.length === 0) {
                const emptyMessage = document.createElement('div');
                emptyMessage.className = 'edit-resultat-no-suggestion';
                emptyMessage.textContent = 'Aucun pilote correspondant';
                listbox.append(emptyMessage);
            } else {
                matchingPilots.forEach((pilot, index) => {
                    const option = document.createElement('div');
                    option.id = `${listbox.id}-option-${index}`;
                    option.className = 'edit-resultat-suggestion';
                    option.setAttribute('role', 'option');
                    option.setAttribute('aria-selected', 'false');
                    option.textContent = pilot.label;
                    option.addEventListener('click', () => selectPilot(pilot));
                    listbox.append(option);
                });
            }

            listbox.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            openInput = input;
            repositionOpenListbox = positionListbox;
            positionListbox();
        };

        input.addEventListener('input', () => {
            pilotIdField.value = '';
            teamIdField.value = '';
            showSuggestions();
        });
        input.addEventListener('focus', showSuggestions);
        input.addEventListener('blur', () => {
            window.setTimeout(() => closeSuggestions(input), 120);
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeSuggestions(input);
            } else if (event.key === 'ArrowDown' && !listbox.hidden) {
                event.preventDefault();
                setActiveOption(activeIndex + 1);
            } else if (event.key === 'ArrowUp' && !listbox.hidden) {
                event.preventDefault();
                setActiveOption(activeIndex - 1);
            } else if (event.key === 'Enter' && !listbox.hidden && activeIndex >= 0) {
                event.preventDefault();
                selectPilot(matchingPilots[activeIndex]);
            }
        });
        listbox.addEventListener('pointerdown', (event) => {
            if (event.pointerType === 'mouse') {
                event.preventDefault();
            }
        });
    });

    const parseMilliseconds = (value) => {
        const time = value.trim();
        if (!time) {
            return null;
        }

        const parts = time.split(':');
        let totalSeconds;
        let secondsPart;
        if (parts.length === 2) {
            const match = parts[1].match(/^(\d{1,2})(?:\.(\d{1,3}))?$/);
            if (!/^\d+$/.test(parts[0]) || !match || Number(match[1]) > 59) {
                return null;
            }
            totalSeconds = Number(parts[0]) * 60 + Number(match[1]);
            secondsPart = match[2] || '';
        } else if (parts.length === 3) {
            const match = parts[2].match(/^(\d{1,2})(?:\.(\d{1,3}))?$/);
            if (!/^\d+$/.test(parts[0]) || !/^\d{1,2}$/.test(parts[1])
                || Number(parts[1]) > 59 || !match || Number(match[1]) > 59) {
                return null;
            }
            totalSeconds = (Number(parts[0]) * 60 + Number(parts[1])) * 60 + Number(match[1]);
            secondsPart = match[2] || '';
        } else {
            return null;
        }

        return totalSeconds * 1000 + Number(secondsPart.padEnd(3, '0'));
    };

    const formatMilliseconds = (milliseconds) => {
        const totalSeconds = Math.floor(milliseconds / 1000);
        const seconds = totalSeconds % 60;
        const totalMinutes = Math.floor(totalSeconds / 60);
        const fraction = String(milliseconds % 1000).padStart(3, '0');

        if (totalMinutes >= 60) {
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}.${fraction}`;
        }

        return `${String(totalMinutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}.${fraction}`;
    };

    const updateRaceGaps = (form) => {
        const rows = Array.from(form.querySelectorAll('tbody tr')).map((row) => ({
            element: row,
            pilotId: row.querySelector('[data-pilot-id]')?.value || '',
            time: parseMilliseconds(row.querySelector('[data-result-time]')?.value || ''),
            laps: row.querySelector('[data-result-laps]')?.value === ''
                ? null
                : Number(row.querySelector('[data-result-laps]')?.value),
            status: row.querySelector('[data-result-status]')?.value || '',
            output: row.querySelector('[data-result-gap]'),
        }));
        const leader = rows.find((row) => row.pilotId && !row.status && row.time !== null);

        rows.forEach((row) => {
            if (!row.output) {
                return;
            }
            if (!row.pilotId) {
                row.output.value = '--';
            } else if (row.status) {
                row.output.value = '-';
            } else if (row.time === null) {
                row.output.value = 'NO TIME';
            } else if (!leader) {
                row.output.value = '--';
            } else if (row === leader) {
                row.output.value = 'Leader';
            } else if (row.laps !== null && leader.laps !== null && row.laps < leader.laps) {
                const lapDifference = leader.laps - row.laps;
                row.output.value = `+${lapDifference} tour${lapDifference > 1 ? 's' : ''}`;
            } else {
                row.output.value = `+${formatMilliseconds(Math.max(0, row.time - leader.time))}`;
            }
        });
    };

    const updateRacePoints = (form) => {
        const pointsByPosition = form.dataset.resultSession === 'sprint'
            ? [0, 8, 7, 6, 5, 4, 3, 2, 1]
            : [0, 25, 18, 15, 12, 10, 8, 6, 4, 2, 1];
        const halfPoints = Boolean(form.querySelector('[data-half-points-toggle]')?.checked);

        form.querySelectorAll('[data-result-points]').forEach((output) => {
            const row = output.closest('tr');
            const position = Number(output.dataset.position);
            const status = row?.querySelector('[data-result-status]')?.value || '';
            const points = status === 'dsq' || status === 'np'
                ? 0
                : (pointsByPosition[position] || 0) * (halfPoints ? 0.5 : 1);

            output.textContent = Number.isInteger(points)
                ? String(points)
                : points.toLocaleString('fr-FR', { maximumFractionDigits: 1 });
        });
    };

    document.querySelectorAll('form[data-result-session="sprint"], form[data-result-session="course"]').forEach((form) => {
        form.querySelector('[data-half-points-toggle]')?.addEventListener('change', () => updateRacePoints(form));
        form.addEventListener('input', () => {
            updateRaceGaps(form);
            updateRacePoints(form);
        });
        form.addEventListener('change', () => {
            updateRaceGaps(form);
            updateRacePoints(form);
        });
        updateRaceGaps(form);
        updateRacePoints(form);
    });

    const firstInvalidField = document.querySelector('[aria-invalid="true"]');
    if (firstInvalidField) {
        const invalidSession = firstInvalidField.closest('details');
        if (invalidSession) {
            invalidSession.open = true;
        }
        firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
        firstInvalidField.focus({ preventScroll: true });
    }

    window.addEventListener('resize', () => {
        if (repositionOpenListbox) {
            repositionOpenListbox();
        }
    });

    window.addEventListener('scroll', () => {
        if (repositionOpenListbox) {
            repositionOpenListbox();
        }
    }, true);
})();