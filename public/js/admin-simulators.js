(() => {
    const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    const number = new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 1 });
    const minutes = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

    function put(result, selector, value) {
        const element = result.querySelector(selector);
        if (element) element.textContent = value;
    }

    function render(result, mode, quote) {
        if (mode === 'display') {
            put(result, '[data-sim="title"]', `${quote.size_label} · ${quote.quantity} unidade(s)`);
        } else {
            put(result, '[data-sim="title"]', `${quote.text} · ${quote.material_name} ${number.format(quote.thickness_mm)} mm`);
            put(result, '[data-sim="details"]', `${quote.finish_label} · ${number.format(quote.width_cm)} × ${number.format(quote.height_cm)} cm${quote.width_estimated ? ' · largura estimada' : ''} · ${quote.quantity} unidade(s)`);
        }
        put(result, '[data-sim="unit_cost"]', money.format(quote.unit_cost));
        put(result, '[data-sim="unit_price"]', money.format(quote.unit_price));
        put(result, '[data-sim="total"]', money.format(quote.total));
        put(result, '[data-laser-minutes]', `${minutes.format(quote.laser_minutes)} min`);
        result.querySelectorAll('[data-cost]').forEach(element => {
            element.textContent = money.format(quote.breakdown[element.dataset.cost] ?? 0);
        });
        result.hidden = false;
    }

    document.querySelectorAll('[data-admin-simulator]').forEach(form => {
        const result = document.getElementById(form.dataset.result);
        const error = document.getElementById(form.dataset.error);
        const button = form.querySelector('[type="submit"]');
        const toggle = result.querySelector('[data-cost-toggle]');
        const details = document.getElementById(toggle.getAttribute('aria-controls'));
        let requestId = 0;

        toggle.addEventListener('click', () => {
            details.hidden = !details.hidden;
            toggle.setAttribute('aria-expanded', String(!details.hidden));
            toggle.textContent = details.hidden ? 'Ver custos por item' : 'Ocultar custos';
        });

        const stale = () => { requestId++; result.hidden = true; error.hidden = true; };
        form.addEventListener('input', stale);
        form.addEventListener('change', stale);

        form.addEventListener('submit', async event => {
            event.preventDefault();
            error.hidden = true;
            result.hidden = true;
            button.disabled = true;
            const currentRequest = ++requestId;
            const originalLabel = button.textContent;
            button.textContent = 'Calculando...';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.headers.get('content-type')?.includes('application/json')) {
                    throw new Error('A sessão expirou. Atualize a página e tente novamente.');
                }
                const payload = await response.json();
                if (currentRequest !== requestId) return;
                if (!response.ok) {
                    const message = Object.values(payload.errors ?? {}).flat()[0] ?? payload.message ?? 'Não foi possível calcular.';
                    throw new Error(message);
                }
                render(result, form.dataset.adminSimulator, payload.simulation);
                details.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
                toggle.textContent = 'Ver custos por item';
            } catch (exception) {
                if (currentRequest === requestId) {
                    error.textContent = exception.message || 'Não foi possível calcular. Tente novamente.';
                    error.hidden = false;
                }
            } finally {
                button.disabled = false;
                button.textContent = originalLabel;
            }
        });
    });
})();
