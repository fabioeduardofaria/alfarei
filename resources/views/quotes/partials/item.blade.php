@php($kind = $item['kind'] ?? (!empty($item['configuration_snapshot']) || ($item['type'] ?? null) === 'display' ? 'display' : (!empty($item['product_id']) ? 'product' : 'custom')))
<div class="quote-item" data-kind="{{ $kind }}">
    <div class="item-line quote-item-heading">
        <label>Tipo de item
            <select class="item-kind" name="items[{{ $index }}][kind]">
                <option value="custom" @selected($kind === 'custom')>Item personalizado</option>
                <option value="product" @selected($kind === 'product')>Produto / serviço cadastrado</option>
                <option value="display" @selected($kind === 'display') @disabled(!$displayConfigurator)>Display adesivado sob medida</option>
            </select>
        </label>
        <label class="item-product-field">Produto / serviço
            <select class="product-select" name="items[{{ $index }}][product_id]">
                <option value="">Selecione</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->base_price }}" data-cost="{{ $product->calculatedProductionCost() }}" @selected(($item['product_id'] ?? null) == $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="item-description-field">Descrição
            <input class="item-description" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" maxlength="200">
        </label>
    </div>
    <div class="quote-display-fields" @if($kind !== 'display') hidden @endif>
        <label class="quote-display-reference">Referência / tema do display<input class="display-reference" type="text" name="items[{{ $index }}][reference]" maxlength="100" value="{{ $item['reference'] ?? ($item['configuration_snapshot']['reference'] ?? '') }}" placeholder="Ex.: Turma da Mônica"></label>
        <label>Largura (cm)<input class="display-width" type="number" name="items[{{ $index }}][width_cm]" min="1" max="{{ $displayConfigurator?->max_width_cm ?? 250 }}" step="0.1" value="{{ $item['width_cm'] ?? ($item['configuration_snapshot']['width_cm'] ?? '') }}" placeholder="Até {{ $displayConfigurator?->max_width_cm ?? '—' }}"></label>
        <label>Altura (cm)<input class="display-height" type="number" name="items[{{ $index }}][height_cm]" min="1" max="{{ $displayConfigurator?->max_height_cm ?? 250 }}" step="0.1" value="{{ $item['height_cm'] ?? ($item['configuration_snapshot']['height_cm'] ?? '') }}" placeholder="Até {{ $displayConfigurator?->max_height_cm ?? '—' }}"></label>
        <p>Adesivo aplicado em MDF 3 mm, com corte de contorno. O preço é calculado pelo configurador e confirmado ao salvar.</p>
    </div>
    <div class="item-values">
        <label>Quantidade<input class="calc-input item-quantity" type="number" step="{{ $kind === 'display' ? '1' : '0.001' }}" min="{{ $kind === 'display' ? '1' : '0.001' }}" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required></label>
        <label>Preço unitário<input class="calc-input item-price" type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}" @readonly($kind === 'display')></label>
        <label>Custo unitário<input class="calc-input item-cost" type="number" step="0.01" min="0" name="items[{{ $index }}][unit_cost]" value="{{ $item['unit_cost'] ?? 0 }}" @readonly($kind === 'display')></label>
        <span class="line-total">R$ 0,00</span><button class="remove-item" type="button" title="Remover item">×</button>
    </div>
    <p class="quote-display-status" aria-live="polite" @if($kind !== 'display') hidden @endif>Informe as medidas para calcular.</p>
</div>
