document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('addMaterialCategory');
    const panel = document.getElementById('newMaterialCategory');
    if (!toggle || !panel) return;

    const nameInput = document.getElementById('newMaterialCategoryName');
    const save = document.getElementById('saveMaterialCategory');
    const select = document.getElementById('materialCategory');
    const status = document.getElementById('materialCategoryStatus');
    const csrf = document.querySelector('.form-card [name="_token"]');
    const editToggle = document.getElementById('editMaterialCategory');
    const editPanel = document.getElementById('editMaterialCategoryPanel');
    const editName = document.getElementById('editMaterialCategoryName');
    const editSave = document.getElementById('saveEditedMaterialCategory');
    const editStatus = document.getElementById('editMaterialCategoryStatus');

    function sortOptions() {
        [...select.options].slice(1).sort((a, b) => a.text.localeCompare(b.text, 'pt-BR')).forEach(item => select.add(item));
    }

    function refreshEditSelection() {
        editToggle.disabled = !select.value;
        if (!editPanel.hidden) {
            editName.value = select.selectedOptions[0]?.text || '';
            editStatus.textContent = '';
        }
    }

    toggle.addEventListener('click', () => {
        panel.hidden = !panel.hidden;
        toggle.setAttribute('aria-expanded', String(!panel.hidden));
        if (!panel.hidden) {
            editPanel.hidden = true;
            editToggle.setAttribute('aria-expanded', 'false');
            nameInput.focus();
        }
    });

    editToggle.addEventListener('click', () => {
        if (!select.value) return;
        editPanel.hidden = !editPanel.hidden;
        editToggle.setAttribute('aria-expanded', String(!editPanel.hidden));
        if (!editPanel.hidden) {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            refreshEditSelection();
            editName.focus();
        }
    });
    select.addEventListener('change', refreshEditSelection);

    async function addCategory() {
        const name = nameInput.value.trim();
        status.textContent = '';
        status.classList.remove('is-error');
        if (!name) {
            status.textContent = 'Informe o nome da categoria.';
            status.classList.add('is-error');
            nameInput.focus();
            return;
        }

        save.disabled = true;
        try {
            const response = await fetch(panel.dataset.url, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf.value},
                body: JSON.stringify({name}),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.errors?.name?.[0] || 'Não foi possível salvar a categoria.');

            let option = [...select.options].find(item => item.value === String(result.id));
            if (!option) {
                option = new Option(result.name, result.id);
                select.add(option);
                sortOptions();
            }
            select.value = String(result.id);
            refreshEditSelection();
            nameInput.value = '';
            status.textContent = result.already_exists ? `“${result.name}” já existia e foi selecionada.` : `“${result.name}” foi cadastrada e selecionada.`;
        } catch (error) {
            status.textContent = error.message;
            status.classList.add('is-error');
        } finally {
            save.disabled = false;
        }
    }

    save.addEventListener('click', addCategory);
    nameInput.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            addCategory();
        }
    });

    async function updateCategory() {
        const name = editName.value.trim();
        const id = select.value;
        editStatus.textContent = '';
        editStatus.classList.remove('is-error');
        if (!id || !name) {
            editStatus.textContent = 'Selecione uma categoria e informe o nome corrigido.';
            editStatus.classList.add('is-error');
            return;
        }

        editSave.disabled = true;
        try {
            const response = await fetch(editPanel.dataset.urlTemplate.replace('__CATEGORY__', encodeURIComponent(id)), {
                method: 'PUT',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf.value},
                body: JSON.stringify({name}),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.errors?.name?.[0] || 'Não foi possível corrigir a categoria.');

            select.selectedOptions[0].text = result.name;
            sortOptions();
            select.value = String(result.id);
            editStatus.textContent = `Categoria corrigida para “${result.name}” em todos os materiais vinculados.`;
        } catch (error) {
            editStatus.textContent = error.message;
            editStatus.classList.add('is-error');
        } finally {
            editSave.disabled = false;
        }
    }

    editSave.addEventListener('click', updateCategory);
    editName.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            updateCategory();
        }
    });
});
