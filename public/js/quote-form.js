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

        applyButton.disabled = cost <= 0 || denominator <= 0 || discountPercent >= 100;
        document.getElementById('suggestedNote').textContent = applyButton.disabled
            ? 'Revise o custo e os percentuais para calcular um preço viável.'
            : 'Distribui o preço sugerido entre os itens. Salve o rascunho depois.';
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
        row.querySelector('.remove-item').addEventListener('click', () => {
            if (rows().length <= 1) return;
            row.remove();
            refresh();
            markDirty();
        });
    }

    rows().forEach(wire);
    form.querySelectorAll('input,select,textarea').forEach(input => {
        input.addEventListener('input', markDirty);
        input.addEventListener('change', markDirty);
    });
    document.querySelectorAll('.pricing-input,#discount').forEach(input => input.addEventListener('input', refresh));
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
