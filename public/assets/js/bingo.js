const bingoForm = document.querySelector('[data-bingo-form]');

if (bingoForm) {
    const overlay = bingoForm.querySelector('[data-bingo-overlay]');
    const labelInput = overlay.querySelector('[data-cell-input]');
    const selectedPositionLabel = overlay.querySelector('[data-selected-position]');
    const cellEditor = overlay.querySelector('[data-cell-editor]');
    const cellPreview = overlay.querySelector('[data-cell-preview]');
    const overlayGrid = overlay.querySelector('.bingo-overlay-grid');
    const progress = document.querySelector('[data-bingo-progress]');
    const cells = Array.from(bingoForm.querySelectorAll('[data-bingo-cell]'));
    let selectedPosition = 1;
    let isMarking = false;

    function labelField(position) {
        return bingoForm.querySelector(`[data-cell-label="${position}"]`);
    }

    function validatedField(position) {
        return bingoForm.querySelector(`[data-cell-validated="${position}"]`);
    }

    function setEditorVisible(visible) {
        cellEditor.hidden = !visible;
        cellEditor.style.display = visible ? '' : 'none';
    }

    function updateCell(position) {
        const label = labelField(position).value.trim();
        const validated = validatedField(position).value === '1';

        cells.filter((cell) => Number(cell.dataset.position) === position).forEach((cell) => {
            cell.textContent = label || `Case ${position}`;
            cell.classList.toggle('checked-cell', validated);
            cell.setAttribute('aria-pressed', String(validated));
            cell.setAttribute('aria-label', `Case ${position}${label ? ` : ${label}` : ', vide'}`);
        });
    }


    function selectCell(position) {
        selectedPosition = position;
        selectedPositionLabel.textContent = String(position);
        labelInput.value = labelField(position).value;
        overlay.querySelector('[data-preview-position]').textContent = String(position);
        overlay.querySelector('[data-preview-content]').textContent =
            labelField(position).value.trim() || 'Cette case est vide.';
        cells.filter((cell) => cell.closest('[data-bingo-overlay]')).forEach((cell) => {
            cell.classList.toggle('selected-cell', Number(cell.dataset.position) === position);
        });
    }

    function setMarking(enabled) {
        isMarking = enabled;
        bingoForm.querySelectorAll('[data-toggle-validation]').forEach((button) => {
            button.setAttribute('aria-pressed', String(enabled));
            button.textContent = enabled ? 'Désactiver la validation' : 'Valider le bingo';
        });
    }

    function toggleCell(position) {
        const field = validatedField(position);
        field.value = field.value === '1' ? '0' : '1';
        updateCell(position);
        if (position === selectedPosition) {
            selectCell(position);
        }
    }

    labelInput.addEventListener('input', () => {
        labelField(selectedPosition).value = labelInput.value;
        updateCell(selectedPosition);
    });

    cells.forEach((cell) => {
        cell.addEventListener('click', () => {
            const position = Number(cell.dataset.position);
            if (overlay.open && overlay.dataset.mode === 'edit') {
                selectCell(position);
                labelInput.focus();
            } else if (isMarking) {
                toggleCell(position);
            } else if (!overlay.open) {
                overlay.dataset.mode = 'view';
                setEditorVisible(false);
                overlayGrid.style.display = 'none';
                cellPreview.hidden = false;
                overlay.querySelector('[data-overlay-title]').textContent = 'Ma grille de bingo';
                overlay.querySelector('[data-overlay-description]').textContent =
                    'Contenu de la case sélectionnée.';
                selectCell(position);
                overlay.showModal();
            }
        });
    });

    bingoForm.querySelectorAll('[data-toggle-validation]').forEach((button) => {
        button.addEventListener('click', () => setMarking(!isMarking));
    });

    bingoForm.querySelectorAll('[data-open-bingo]').forEach((button) => {
        button.addEventListener('click', () => {
            overlay.dataset.mode = 'edit';
            setEditorVisible(true);
            overlayGrid.style.display = '';
            cellPreview.hidden = true;
            overlay.querySelector('[data-overlay-title]').textContent = 'Modifier ma grille';
            overlay.querySelector('[data-overlay-description]').textContent =
                'Sélectionnez une case pour modifier son texte.';
            selectCell(selectedPosition);
            overlay.showModal();
            labelInput.focus();
        });
    });

    overlay.querySelector('[data-close-bingo]').addEventListener('click', () => overlay.close());
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            overlay.close();
        }
    });
    bingoForm.addEventListener('submit', () => {
        labelField(selectedPosition).value = labelInput.value;
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && overlay.open) {
            overlay.close();
        }
    });

    selectCell(selectedPosition);
}

const bingoList = document.querySelector('[data-bingo-list]');

if (bingoList) {
    const userButtons = Array.from(bingoList.querySelectorAll('[data-bingo-user]'));
    const previews = Array.from(bingoList.querySelectorAll('[data-bingo-preview]'));
    const cellDialog = document.querySelector('[data-bingo-cell-dialog]');
    const cellDialogTitle = cellDialog.querySelector('[data-dialog-title]');
    const cellDialogPosition = cellDialog.querySelector('[data-dialog-position]');
    const cellDialogLabel = cellDialog.querySelector('[data-dialog-label]');
    const cellDialogStatus = cellDialog.querySelector('[data-dialog-status]');

    userButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const selectedId = button.dataset.bingoUser;
            userButtons.forEach((userButton) => {
                userButton.setAttribute('aria-pressed', String(userButton === button));
            });
            previews.forEach((preview) => {
                preview.hidden = preview.dataset.bingoPreview !== selectedId;
            });
        });
    });

    bingoList.addEventListener('click', (event) => {
        const cell = event.target.closest('[data-view-cell]');
        if (!cell) {
            return;
        }

        const preview = cell.closest('[data-bingo-preview]');
        const userButton = userButtons.find((button) => button.dataset.bingoUser === preview.dataset.bingoPreview);
        cellDialogTitle.textContent = `Bingo de ${userButton.textContent.trim()}`;
        cellDialogPosition.textContent = cell.dataset.position;
        cellDialogLabel.textContent = cell.dataset.label;
        cellDialogStatus.hidden = cell.dataset.validated !== 'true';
        cellDialog.showModal();
    });

    cellDialog.querySelector('[data-close-cell-dialog]').addEventListener('click', () => cellDialog.close());
    cellDialog.addEventListener('click', (event) => {
        if (event.target === cellDialog) {
            cellDialog.close();
        }
    });
}
