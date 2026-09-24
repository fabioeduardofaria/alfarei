(() => {
    const list = document.getElementById('quoteItems');
    if (!list) return;

    const template = document.getElementById('quoteItemTemplate');
    const form = document.querySelector('.quote-form');
    const sendButton = document.querySelector('.quote-send-panel button');
    const sendNote = document.querySelector('.quote-send-panel p:last-child');
    const applyButton = document.getElementById('applySuggested');
    const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    let next = list.children.length;

    const value = id => Number(document.getElementById(id)?.value) || 0;
    const rows = () => [...list.querySelectorAll('.quote-item')];
    const displayRows = () => rows().filter(row => row.querySelector('.item-kind').value === 'display');
    const textRows = () => rows().filter(row => row.querySelector('.item-kind').value === 'text_cutout');
    const markDirty = () => {
        if (!sendButton) return;
        sendButton.disabled = true;
        if (sendNote) sendNote.textContent = 'Há alterações não salvas. Salve o rascunho antes de enviar esta versão.';
    };

    function refresh() {
        let subtotal = 0;
        let cost = 0;
        rows().forEach(row => {
            const quantity = Number(row.querySelector('[name*=quantity]').value) || 0;
            const unitPrice = Number(row.querySelector('.item-price').value) || 0;
            const unitCost = Number(row.querySelector('.item-cost').value) || 0;
            const lineTotal = Math.round(quantity * unitPrice * 100) / 100;
            subtotal += lineTotal;
            cost += Math.round(quantity * unitCost * 100) / 100;
            row.querySelector('.line-total').textContent = money.format(lineTotal);
        });

        const discountPercent = value('discountPercent');
        const fixedDiscount = value('discount');
        const expensePercent = value('tax_percent') + value('commission_percent') + value('fee_percent');
        const targetMargin = value('target_margin_percent');
        const total = Math.max(0, subtotal - fixedDiscount - Math.round(subtotal * discountPercent) / 100);
        const denominator = 1 - (expensePercent + targetMargin) / 100;
        const suggested = denominator > 0 ? cost / denominator : 0;
        const margin = total > 0 ? ((total - cost - total * expensePercent / 100) / total) * 100 : 0;

        document.getElementById('subtotalPreview').textContent = money.format(subtotal);
        document.getElementById('costPreview').textContent = money.format(cost);
        document.getElementById('totalPreview').textContent = money.format(total);
        document.getElementById('suggestedPreview').textContent = denominator > 0 ? money.format(suggested) : 'Inviável';
        const marginEl = document.getElementById('marginPreview');
        marginEl.textContent = margin.toFixed(1).replace('.', ',') + '%';
        marginEl.className = margin + 0.05 < targetMargin ? 'negative' : '';
        document.getElementById('marginNote').textContent = margin + 0.05 < targetMargin
            ? 'Abaixo da margem-alvo de ' + targetMargin.toFixed(1).replace('.', ',') + '%'
            : 'Margem dentro do objetivo';

        applyButton.disabled = cost <= 0 || denominator <= 0 || discountPercent >= 100 || displayRows().length > 0 || textRows().length > 0;
        document.getElementById('suggestedNote').textContent = applyButton.disabled
            ? (displayRows().length > 0 || textRows().length > 0 ? 'Itens configurados usam seus próprios preços; não aplique outro preço sugerido.' : 'Revise o custo e os percentuais para calcular um preço viável.')
            : 'Distribui o preço sugerido entre os itens. Salve o rascunho depois.';
    }

    function setKind(row) {
        const kind = row.querySelector('.item-kind').value;
        row.dataset.kind = kind;
        row.querySelector('.item-product-field').hidden = kind !== 'product';
        row.querySelector('.item-description-field').hidden = kind === 'display' || kind === 'text_cutout';
        row.querySelector('.quote-display-fields').hidden = kind !== 'display';
        row.querySelector('.quote-display-status').hidden = kind !== 'display';
        row.querySelector('.quote-text-fields').hidden = kind !== 'text_cutout';
        row.querySelector('.quote-text-status').hidden = kind !== 'text_cutout';
        row.querySelectorAll('.quote-display-fields input').forEach(input => { input.disabled = kind !== 'display'; });
        row.querySelectorAll('.quote-text-fields input,.quote-text-fields select').forEach(input => { input.disabled = kind !== 'text_cutout'; });
        row.querySelector('.item-description').required = kind !== 'display' && kind !== 'text_cutout';
        row.querySelector('.item-description').disabled = kind === 'display' || kind === 'text_cutout';
        row.querySelector('.product-select').required = kind === 'product';
        row.querySelector('.product-select').disabled = kind !== 'product';
        row.querySelector('.item-price').readOnly = kind === 'display' || kind === 'text_cutout';
        row.querySelector('.item-cost').readOnly = kind === 'display' || kind === 'text_cutout';
        row.querySelector('.item-quantity').step = kind === 'display' || kind === 'text_cutout' ? '1' : '0.001';
        row.querySelector('.item-quantity').min = kind === 'display' || kind === 'text_cutout' ? '1' : '0.001';
        row.querySelector('.item-quantity').max = kind === 'display' || kind === 'text_cutout' ? '100' : '';
        row.querySelector('.display-width').required = kind === 'display';
        row.querySelector('.display-height').required = kind === 'display';
        row.querySelector('.display-reference').required = kind === 'display';
        row.querySelector('.text-content').required = kind === 'text_cutout';
        row.querySelector('.text-material').required = kind === 'text_cutout';
        row.querySelector('.text-height').required = kind === 'text_cutout';
        row.querySelector('.text-color-field').hidden = kind !== 'text_cutout' || row.querySelector('.text-finish').value !== 'painted';
        row.querySelector('.text-color').required = kind === 'text_cutout' && row.querySelector('.text-finish').value === 'painted';
        row.querySelector('.text-color').disabled = kind !== 'text_cutout' || row.querySelector('.text-finish').value !== 'painted';
        if (kind !== 'product') row.querySelector('.product-select').value = '';
        if (kind === 'display') previewDisplay(row);
        if (kind === 'text_cutout') previewText(row);
        refresh();
    }

    async function previewText(row) {
        const requestId = (row._textRequestId || 0) + 1;
        row._textRequestId = requestId;
        if (row.querySelector('.item-kind').value !== 'text_cutout') return;
        const text = row.querySelector('.text-content');
        const material = row.querySelector('.text-material');
        const finish = row.querySelector('.text-finish');
        const color = row.querySelector('.text-color');
        const width = row.querySelector('.text-width');
        const height = row.querySelector('.text-height');
        const quantity = row.querySelector('.item-quantity');
        const withBase = row.querySelector('.text-base-option:checked');
        const status = row.querySelector('.quote-text-status');
        const price = row.querySelector('.item-price');
        const cost = row.querySelector('.item-cost');
        price.value = '0'; cost.value = '0'; refresh();
        if (!text.value.trim() || !material.value || !height.value || !height.checkValidity() || (width.value && !width.checkValidity()) || !quantity.checkValidity() || (finish.value === 'painted' && !color.value.trim())) {
            status.textContent = 'Informe texto, material, acabamento, altura e quantidade válidos.';
            return;
        }
        status.textContent = 'Calculando preço estimado...';
        try {
            const response = await fetch(form.dataset.textPriceUrl, {
                method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
                body: JSON.stringify({text_content: text.value, material_id: Number(material.value), finish: finish.value, color: color.value || null, with_base: Number(withBase?.value || 0), width_cm: width.value ? Number(width.value) : null, height_cm: Number(height.value), quantity: Number(quantity.value), customer_id: document.querySelector('[name="customer_id"]').value || null}),
            });
            if (!response.ok) throw new Error('Não foi possível calcular. Confira os limites e custos no configurador.');
            const result = await response.json();
            if (row._textRequestId !== requestId || row.querySelector('.item-kind').value !== 'text_cutout') return;
            price.value = Number(result.unit_price).toFixed(2);
            cost.value = Number(result.unit_cost).toFixed(2);
            status.textContent = (result.width_estimated ? 'Largura estimada: ' : 'Largura informada: ') + Number(result.width_cm).toLocaleString('pt-BR') + ' cm · preço para ' + quantity.value + ' unidade(s).';
            refresh();
        } catch (error) {
            if (row._textRequestId === requestId) status.textContent = error.message;
        }
    }

    async function previewDisplay(row) {
        const requestId = (row._displayRequestId || 0) + 1;
        row._displayRequestId = requestId;
        if (row.querySelector('.item-kind').value !== 'display') return;
        const width = row.querySelector('.display-width');
        const height = row.querySelector('.display-height');
        const quantity = row.querySelector('.item-quantity');
        const status = row.querySelector('.quote-display-status');
        const price = row.querySelector('.item-price');
        const cost = row.querySelector('.item-cost');
        price.value = '0'; cost.value = '0'; refresh();
        if (!width.value || !height.value || !width.checkValidity() || !height.checkValidity() || !quantity.checkValidity()) {
            status.textContent = 'Informe medidas e quantidade válidas para calcular.';
            return;
        }
        status.textContent = 'Calculando preço...';
        try {
            const response = await fetch(form.dataset.displayPriceUrl, {
                method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
                body: JSON.stringify({width_cm: Number(width.value), height_cm: Number(height.value), quantity: Number(quantity.value), customer_id: document.querySelector('[name="customer_id"]').value || null}),
            });
            if (!response.ok) throw new Error('Não foi possível calcular. Confira os limites no configurador.');
            const result = await response.json();
            if (row._displayRequestId !== requestId || row.querySelector('.item-kind').value !== 'display') return;
            price.value = Number(result.unit_price).toFixed(2);
            cost.value = Number(result.unit_cost).toFixed(2);
            status.textContent = result.size_label + ' · preço calculado para ' + quantity.value + ' unidade(s).';
            refresh();
        } catch (error) {
            if (row._displayRequestId === requestId) status.textContent = error.message;
        }
    }

    function wire(row) {
        row.querySelectorAll('input,select').forEach(input => {
            input.addEventListener('input', () => { refresh(); markDirty(); });
            input.addEventListener('change', markDirty);
        });
        row.querySelector('.product-select').addEventListener('change', event => {
            const option = event.target.selectedOptions[0];
            if (option.value) {
                row.querySelector('.item-description').value = option.dataset.name;
                row.querySelector('.item-price').value = option.dataset.price;
                row.querySelector('.item-cost').value = option.dataset.cost;
            }
            refresh();
        });
        row.querySelector('.item-kind').addEventListener('change', () => { row._displayRequestId = (row._displayRequestId || 0) + 1; row._textRequestId = (row._textRequestId || 0) + 1; setKind(row); markDirty(); });
        [row.querySelector('.display-width'), row.querySelector('.display-height'), row.querySelector('.item-quantity')].forEach(input => {
            input.addEventListener('input', () => {
                if (row.querySelector('.item-kind').value === 'display') {
                    row._displayRequestId = (row._displayRequestId || 0) + 1;
                    row.querySelector('.item-price').value = '0';
                    row.querySelector('.item-cost').value = '0';
                    row.querySelector('.quote-display-status').textContent = 'Atualizando preço...';
                    refresh();
                } else if (row.querySelector('.item-kind').value === 'text_cutout') {
                    row._textRequestId = (row._textRequestId || 0) + 1;
                    row.querySelector('.item-price').value = '0';
                    row.querySelector('.item-cost').value = '0';
                    row.querySelector('.quote-text-status').textContent = 'Atualizando preço...';
                    refresh();
                }
                clearTimeout(row._displayTimer);
                row._displayTimer = setTimeout(() => row.querySelector('.item-kind').value === 'display' ? previewDisplay(row) : previewText(row), 300);
            });
        });
        [row.querySelector('.text-content'), row.querySelector('.text-material'), row.querySelector('.text-finish'), row.querySelector('.text-color'), row.querySelector('.text-width'), row.querySelector('.text-height'), ...row.querySelectorAll('.text-base-option')].forEach(input => {
            input.addEventListener('input', () => {
                row.querySelector('.text-color-field').hidden = row.querySelector('.text-finish').value !== 'painted';
                row.querySelector('.text-color').required = row.querySelector('.text-finish').value === 'painted' && row.querySelector('.item-kind').value === 'text_cutout';
                row.querySelector('.text-color').disabled = row.querySelector('.text-finish').value !== 'painted' || row.querySelector('.item-kind').value !== 'text_cutout';
                row._textRequestId = (row._textRequestId || 0) + 1;
                row.querySelector('.item-price').value = '0'; row.querySelector('.item-cost').value = '0'; refresh();
                clearTimeout(row._textTimer);
                row._textTimer = setTimeout(() => previewText(row), 300);
            });
        });
        row.querySelector('.remove-item').addEventListener('click', () => {
            if (rows().length <= 1) return;
            row.remove();
            refresh();
            markDirty();
        });
        setKind(row);
    }

    rows().forEach(wire);
    form.querySelectorAll('input,select,textarea').forEach(input => {
        input.addEventListener('input', markDirty);
        input.addEventListener('change', markDirty);
    });
    document.querySelectorAll('.pricing-input,#discount').forEach(input => input.addEventListener('input', refresh));
    document.querySelector('[name="customer_id"]').addEventListener('change', () => { displayRows().forEach(previewDisplay); textRows().forEach(previewText); });
    document.getElementById('addItem').addEventListener('click', () => {
        list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', next++));
        wire(list.lastElementChild);
        refresh();
        markDirty();
    });
    applyButton.addEventListener('click', () => {
        const cost = rows().reduce((sum, row) => {
            const quantity = Number(row.querySelector('[name*=quantity]').value) || 0;
            const unitCost = Number(row.querySelector('.item-cost').value) || 0;
            return sum + quantity * unitCost;
        }, 0);
        const commercial = value('tax_percent') + value('commission_percent') + value('fee_percent') + value('target_margin_percent');
        const suggested = cost / (1 - commercial / 100);
        const subtotalNeeded = (suggested + value('discount')) / (1 - value('discountPercent') / 100);
        const factor = subtotalNeeded / cost;
        if (!Number.isFinite(factor) || factor < 0) return;
        rows().forEach(row => {
            const unitCost = Number(row.querySelector('.item-cost').value) || 0;
            row.querySelector('.item-price').value = (Math.round(unitCost * factor * 100) / 100).toFixed(2);
        });
        refresh();
        markDirty();
    });
    refresh();
})();
