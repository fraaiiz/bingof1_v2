const form = document.querySelector('[data-course-form]');

if (form) {
    const formatSelect = form.querySelector('[name="format_id"]');
    const sessionsByFormat = JSON.parse(form.dataset.sessions || '{}');

    const syncDates = () => {
        const allowed = new Set(sessionsByFormat[formatSelect.value] || []);
        form.querySelectorAll('[data-session]').forEach((field) => {
            const visible = allowed.has(field.dataset.session);
            field.hidden = !visible;
            field.querySelector('input').disabled = !visible;
            if (!visible) {
                field.querySelector('input').value = '';
            }
        });
    };

    formatSelect.addEventListener('change', syncDates);
    syncDates();

    const circuits = JSON.parse(form.dataset.circuits || '[]');
    const searchInput = form.querySelector('[data-circuit-search]');
    const circuitIdInput = form.querySelector('[data-circuit-id]');
    const results = form.querySelector('[data-circuit-results]');
    const emptyMessage = form.querySelector('[data-circuit-empty]');
    const selected = form.querySelector('[data-circuit-selected]');
    const selectedImage = form.querySelector('[data-circuit-selected-image]');
    const selectedName = form.querySelector('[data-circuit-selected-name]');
    const newCircuit = form.querySelector('[data-new-circuit]');
    const newFields = newCircuit.querySelectorAll('input');

    const normalize = (value) => value.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();

    const placeLabel = (circuit) => circuit.ville ? circuit.pays + ' (' + circuit.ville + ')' : circuit.pays;

    const setNewCircuitOpen = (open) => {
        newCircuit.hidden = !open;
        newFields.forEach((field) => {
            field.disabled = !open;
            if (!open) {
                field.value = '';
            }
        });
        newCircuit.querySelector('[name="circuit_pays"]').required = open;
        const fileInput = newCircuit.querySelector('[data-trace-input]');
        fileInput.required = open;
        if (!open) {
            const preview = newCircuit.querySelector('[data-trace-preview]');
            preview.hidden = true;
            preview.querySelector('img').removeAttribute('src');
            preview.querySelector('[data-trace-name]').textContent = '';
        }
    };

    const clearSelection = () => {
        circuitIdInput.value = '';
        selected.hidden = true;
        selectedImage.removeAttribute('src');
        selectedName.textContent = '';
        searchInput.disabled = false;
    };

    const selectCircuit = (circuit) => {
        circuitIdInput.value = String(circuit.id);
        selectedImage.src = circuit.image;
        selectedImage.alt = 'Tracé de ' + placeLabel(circuit);
        selectedName.textContent = placeLabel(circuit);
        selected.hidden = false;
        results.hidden = true;
        results.replaceChildren();
        emptyMessage.hidden = true;
        searchInput.value = '';
        searchInput.disabled = true;
        setNewCircuitOpen(false);
    };

    const renderResults = () => {
        const query = normalize(searchInput.value.trim());
        results.replaceChildren();
        if (query === '' || circuitIdInput.value !== '') {
            results.hidden = true;
            emptyMessage.hidden = true;
            return;
        }

        const matches = circuits.filter((circuit) => {
            const place = normalize(circuit.pays + ' ' + (circuit.ville || ''));
            return place.includes(query);
        });

        matches.forEach((circuit) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'admin-circuit-result';
            const image = document.createElement('img');
            image.src = circuit.image;
            image.alt = '';
            const name = document.createElement('span');
            name.textContent = placeLabel(circuit);
            button.append(image, name);
            button.addEventListener('click', () => selectCircuit(circuit));
            results.append(button);
        });

        results.hidden = matches.length === 0;
        emptyMessage.hidden = matches.length !== 0;
    };

    searchInput.addEventListener('input', renderResults);
    form.querySelector('[data-circuit-clear]').addEventListener('click', () => {
        clearSelection();
        searchInput.focus();
    });
    form.querySelector('[data-circuit-create]').addEventListener('click', () => {
        clearSelection();
        results.hidden = true;
        results.replaceChildren();
        emptyMessage.hidden = true;
        setNewCircuitOpen(newCircuit.hidden);
        if (!newCircuit.hidden) {
            newCircuit.querySelector('[name="circuit_pays"]').focus();
        }
    });

    const traceInput = form.querySelector('[data-trace-input]');
    const tracePreview = form.querySelector('[data-trace-preview]');
    const traceImage = form.querySelector('[data-trace-image]');
    const traceName = form.querySelector('[data-trace-name]');

    traceInput.addEventListener('change', () => {
        const file = traceInput.files[0];
        if (!file) {
            tracePreview.hidden = true;
            traceImage.removeAttribute('src');
            traceName.textContent = '';
            return;
        }

        traceName.textContent = file.name;
        traceImage.alt = 'Aperçu de ' + file.name;
        const reader = new FileReader();
        reader.addEventListener('load', () => {
            traceImage.src = reader.result;
            tracePreview.hidden = false;
        });
        reader.readAsDataURL(file);
    });

    if (circuitIdInput.value !== '') {
        const current = circuits.find((circuit) => String(circuit.id) === circuitIdInput.value);
        if (current) {
            selectCircuit(current);
        } else {
            circuitIdInput.value = '';
        }
    }
}
