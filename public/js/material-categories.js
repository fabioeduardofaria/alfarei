document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('addMaterialCategory');
    const panel = document.getElementById('newMaterialCategory');
    if (!toggle || !panel) return;

    const nameInput = document.getElementById('newMaterialCategoryName');
    const save = document.getElementById('saveMaterialCategory');
    const select = document.getElementById('materialCategory');
    const status = document.getElementById('materialCategoryStatus');
    const csrf = document.querySelector('.form-card [name="_token"]');

    toggle.addEventListener('click', () => {
        panel.hidden = !panel.hidden;
        toggle.setAttribute('aria-expanded', String(!panel.hidden));
        if (!panel.hidden) nameInput.focus();
    });

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
                [...select.options].slice(1).sort((a, b) => a.text.localeCompare(b.text, 'pt-BR')).forEach(item => select.add(item));
            }
            select.value = String(result.id);
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
});
