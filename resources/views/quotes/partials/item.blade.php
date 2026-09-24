@php($kind = $item['kind'] ?? (($item['type'] ?? null) === 'text_cutout' ? 'text_cutout' : (($item['type'] ?? null) === 'display' ? 'display' : (!empty($item['product_id']) ? 'product' : 'custom'))))
<div class="quote-item" data-kind="{{ $kind }}">
    <div class="item-line quote-item-heading">
        <label>Tipo de item
            <select class="item-kind" name="items[{{ $index }}][kind]">
                <option value="custom" @selected($kind === 'custom')>Item personalizado</option>
                <option value="product" @selected($kind === 'product')>Produto / serviço cadastrado</option>
                <option value="display" @selected($kind === 'display') @disabled(!$displayConfigurator)>Display adesivado sob medida</option>
                <option value="text_cutout" @selected($kind === 'text_cutout') @disabled(!$textConfigurator)>Nome ou texto recortado</option>
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
        <p>Adesivo aplicado em MDF {{ $displayConfigurator?->mdfMaterial?->thickness_mm ? rtrim(rtrim(number_format((float) $displayConfigurator->mdfMaterial->thickness_mm, 1, ',', ''), '0'), ',') : '3' }} mm, com corte de contorno. O preço é calculado pelo configurador e confirmado ao salvar.</p>
    </div>
    <div class="quote-text-fields" @if($kind !== 'text_cutout') hidden @endif>
        @php($textWithBase = (bool) ($item['text_with_base'] ?? ($item['configuration_snapshot']['with_base'] ?? !empty($item['configuration_snapshot']))))
        <label class="quote-text-content">Nome ou texto<input class="text-content" name="items[{{ $index }}][text_content]" maxlength="100" value="{{ $item['text_content'] ?? ($item['configuration_snapshot']['text'] ?? '') }}" placeholder="Ex.: Aurora"></label>
        <label>Material<select class="text-material" name="items[{{ $index }}][text_material_id]"><option value="">Selecione MDF ou acrílico</option>@foreach($textMaterials as $material)<option value="{{ $material->id }}" @selected(($item['text_material_id'] ?? ($item['configuration_snapshot']['material_id'] ?? null)) == $material->id)>{{ $material->name }} · {{ $material->thickness_mm }} mm</option>@endforeach</select></label>
        <label>Acabamento<select class="text-finish" name="items[{{ $index }}][text_finish]"><option value="natural" @selected(($item['text_finish'] ?? ($item['configuration_snapshot']['finish'] ?? 'natural')) === 'natural')>Sem pintura (cor da chapa)</option><option value="white" @selected(($item['text_finish'] ?? ($item['configuration_snapshot']['finish'] ?? '')) === 'white')>Branco pintado</option><option value="painted" @selected(($item['text_finish'] ?? ($item['configuration_snapshot']['finish'] ?? '')) === 'painted')>Pintado na cor escolhida</option></select></label>
        <label class="text-color-field">Cor da pintura<input class="text-color" name="items[{{ $index }}][text_color]" maxlength="50" value="{{ $item['text_color'] ?? ($item['configuration_snapshot']['color'] ?? '') }}" placeholder="Ex.: rosa claro"></label>
        <label>Altura final (cm)<input class="text-height" type="number" name="items[{{ $index }}][text_height_cm]" min="1" max="{{ $textConfigurator?->max_height_cm ?? 250 }}" step="0.1" value="{{ $item['text_height_cm'] ?? ($item['configuration_snapshot']['height_cm'] ?? '') }}"></label>
        <label>Largura final (cm) <small>opcional</small><input class="text-width" type="number" name="items[{{ $index }}][text_width_cm]" min="1" max="{{ $textConfigurator?->max_width_cm ?? 250 }}" step="0.1" value="{{ $item['text_width_cm'] ?? (!($item['configuration_snapshot']['width_estimated'] ?? false) ? ($item['configuration_snapshot']['width_cm'] ?? '') : '') }}" placeholder="Estimada se ficar vazio"></label>
        <div class="text-base-choice"><span>Como será usado?</span><label><input class="text-base-option" type="radio" name="items[{{ $index }}][text_with_base]" value="0" @checked(!$textWithBase)> Sem base · para colar no painel</label><label><input class="text-base-option" type="radio" name="items[{{ $index }}][text_with_base]" value="1" @checked($textWithBase)> Com base · para apoiar na mesa</label></div>
        <p>O preço considera a área ocupada, o material cadastrado, o tempo estimado de corte e o acabamento. A arte final deve respeitar as medidas cotadas.</p>
    </div>
    <div class="item-values">
        <label>Quantidade<input class="calc-input item-quantity" type="number" step="{{ in_array($kind, ['display', 'text_cutout']) ? '1' : '0.001' }}" min="{{ in_array($kind, ['display', 'text_cutout']) ? '1' : '0.001' }}" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required></label>
        <label>Preço unitário<input class="calc-input item-price" type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}" @readonly(in_array($kind, ['display', 'text_cutout']))></label>
        <label>Custo unitário<input class="calc-input item-cost" type="number" step="0.01" min="0" name="items[{{ $index }}][unit_cost]" value="{{ $item['unit_cost'] ?? 0 }}" @readonly(in_array($kind, ['display', 'text_cutout']))></label>
        <span class="line-total">R$ 0,00</span><button class="remove-item" type="button" title="Remover item">×</button>
    </div>
    <p class="quote-display-status" aria-live="polite" @if($kind !== 'display') hidden @endif>Informe as medidas para calcular.</p>
    <p class="quote-text-status" aria-live="polite" @if($kind !== 'text_cutout') hidden @endif>Informe texto, material e altura para calcular.</p>
</div>
