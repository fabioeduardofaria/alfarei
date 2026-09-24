@extends('layouts.app', ['title' => 'Configurador de nomes e textos · Alfarei CNC'])

@section('content')
<div class="heading"><div><p class="eyebrow">E-COMMERCE · PREÇOS</p><h1>Configurador de nomes e textos</h1><p class="muted">Corte em MDF ou acrílico, com acabamento original, branco ou pintado.</p></div><a class="secondary" href="{{ route('displays.config.edit') }}">← Configurador de displays</a></div>
@if($missing)<div class="display-notice"><b>Configuração incompleta: ainda não será oferecida na loja nem no orçamento automático.</b><ul>@foreach($missing as $requirement)<li>{{ $requirement }}</li>@endforeach</ul></div>@endif
@if($errors->any())<div class="display-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="panel display-admin-form text-config-form" method="POST" action="{{ route('texts.config.update') }}">@csrf @method('PUT')
    <div class="display-section-title"><h2>1. Produto e máquina</h2><p>Este configurador precisa de um produto próprio. O produto usado pelos displays não aparece na lista. Os materiais são lidos automaticamente do cadastro.</p></div>
    <div class="display-fields">
        <label>Produto da loja<x-field-tip text="Produto exclusivo que representará nomes e textos na vitrine e nos pedidos. Produtos já usados pelo configurador de displays não podem ser escolhidos."/><select id="textProductSelect" name="product_id"><option value="">Selecione um produto existente</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(old('product_id', $configurator->product_id) == $product->id)>{{ $product->name }}</option>@endforeach</select></label>
        <label>Máquina laser<x-field-tip text="Máquina cujo custo por hora será usado no cálculo do corte. Cadastre e atualize seus custos em Máquinas e custos."/><select name="laser_machine_id"><option value="">Selecione</option>@foreach($machines as $machine)<option value="{{ $machine->id }}" @selected(old('laser_machine_id', $configurator->laser_machine_id) == $machine->id)>{{ $machine->name }} · R$ {{ number_format($machine->calculatedHourlyCost() ?: $machine->hourly_cost, 2, ',', '.') }}/h</option>@endforeach</select></label>
    </div>
    @unless($configurator->product_id)
    <div class="text-config-create-product">
        <label class="text-config-create-option"><input id="textCreateProduct" type="checkbox" name="create_product" value="1" @checked(old('create_product'))> <span>Criar um produto exclusivo para nomes e textos</span> <x-field-tip text="Marque esta opção se ainda não tiver um produto próprio. Ao salvar, o sistema cria um produto sob encomenda, ativo e visível na loja, e o vincula automaticamente a este configurador."/></label>
        <label class="text-config-new-name" id="textNewProductField">Nome do novo produto <x-field-tip text="Nome que o cliente verá na loja, no carrinho e no pedido. Exemplo: Nome ou texto personalizado."/><input id="textNewProductName" type="text" name="new_product_name" value="{{ old('new_product_name', 'Nome ou texto personalizado') }}" maxlength="150" placeholder="Ex.: Nome ou texto personalizado"></label>
        <small>Se marcar a criação, o novo produto será usado no lugar do selecionado acima. Ele só será criado se todos os dados forem salvos com sucesso.</small>
    </div>
    @endunless
    <div class="text-config-materials"><b>Materiais elegíveis</b><p>@forelse($materials as $material)<span>{{ $material->name }} · {{ $material->thickness_mm }} mm · R$ {{ number_format($material->cost_per_unit, 2, ',', '.') }}/{{ $material->unit }}</span>@empty Nenhum MDF ou acrílico elegível. @endforelse</p></div>

    <div class="display-section-title"><h2>2. Medidas e corte</h2><p>Largura e altura formam a área de material. Se a largura não for informada, será estimada pelo comprimento do texto e a proporção média abaixo. O tempo de corte é uma estimativa configurável.</p></div>
    <div class="display-fields display-fields-three">
        <label>Largura máxima (cm)<x-field-tip text="Maior largura final permitida para um nome ou texto. O orçamento também verifica se a peça cabe na chapa do material escolhido."/><input type="number" name="max_width_cm" value="{{ old('max_width_cm', $configurator->max_width_cm) }}" min="1" max="250" step="0.1" required></label>
        <label>Altura máxima (cm)<x-field-tip text="Maior altura final permitida. Use um limite compatível com a área útil do laser e com as chapas disponíveis."/><input type="number" name="max_height_cm" value="{{ old('max_height_cm', $configurator->max_height_cm) }}" min="1" max="250" step="0.1" required></label>
        <label>Proporção média da largura por caractere<x-field-tip text="Usada apenas quando a largura não é informada. Exemplo: 0,60 estima cada caractere com largura de 60% da altura; 6 caracteres de 10 cm ocupam cerca de 36 cm."/><input type="number" name="average_character_width_ratio" value="{{ old('average_character_width_ratio', $configurator->average_character_width_ratio) }}" min="0.2" max="1.5" step="0.01" required></label>
        <label>Largura mínima aceita (% da estimativa)<x-field-tip text="Impede informar largura irreal para reduzir o preço. Com 50%, a largura digitada não pode ser menor que metade da largura estimada; ajuste conforme suas fontes."/><input type="number" name="minimum_width_percent" value="{{ old('minimum_width_percent', $configurator->minimum_width_percent) }}" min="20" max="100" step="0.01" required></label>
        <label>Laser por caractere a 10 cm (min)<x-field-tip text="Tempo estimado para cortar um caractere de 10 cm de altura. O sistema multiplica pela quantidade de caracteres sem espaços e ajusta proporcionalmente à altura escolhida."/><input type="number" name="laser_minutes_per_character_10cm" value="{{ old('laser_minutes_per_character_10cm', $configurator->laser_minutes_per_character_10cm) }}" min="0.01" max="60" step="0.01" required></label>
        <label>Tempo mínimo de laser (min)<x-field-tip text="Piso de tempo cobrado por unidade, mesmo quando a estimativa por caractere for menor. Evita subprecificar peças muito curtas ou pequenas."/><input type="number" name="minimum_laser_minutes" value="{{ old('minimum_laser_minutes', $configurator->minimum_laser_minutes) }}" min="0.01" max="60" step="0.01" required></label>
        <label>Perda de material (%)<x-field-tip text="Acrescenta uma reserva ao consumo calculado de MDF ou acrílico para compensar recortes, sobras e perdas de produção."/><input type="number" name="material_loss_percent" value="{{ old('material_loss_percent', $configurator->material_loss_percent) }}" min="0" max="100" step="0.01" required></label>
    </div>

    <div class="display-section-title"><h2>3. Acabamento e custos</h2><p>“Sem pintura” usa a cor original da chapa. Branco e pintado usam os custos de acabamento informados abaixo. Se usar uma chapa branca pronta, selecione-a como material e escolha “Sem pintura”.</p></div>
    <div class="display-fields display-fields-three">
        <label>Acabamento branco por m² (R$)<x-field-tip text="Custo de pintar de branco um metro quadrado de peça. Só entra no preço quando o acabamento Branco pintado é escolhido; chapa branca pronta usa Sem pintura."/><input type="number" name="white_finish_cost_per_m2" value="{{ old('white_finish_cost_per_m2', $configurator->white_finish_cost_per_m2) }}" min="0" step="0.01" required></label>
        <label>Pintura colorida por m² (R$)<x-field-tip text="Custo de pintar um metro quadrado na cor escolhida pelo cliente. Inclua tinta, insumos e mão de obra de pintura."/><input type="number" name="painted_finish_cost_per_m2" value="{{ old('painted_finish_cost_per_m2', $configurator->painted_finish_cost_per_m2) }}" min="0" step="0.01" required></label>
        <label>Base por unidade (R$)<x-field-tip text="Custo fixo adicional por peça, como montagem, acabamento manual ou insumos que não variam com a área. Não duplique custos já lançados em outros campos."/><input type="number" name="base_cost_per_unit" value="{{ old('base_cost_per_unit', $configurator->base_cost_per_unit) }}" min="0" step="0.01" required></label>
        <label>Embalagem por unidade (R$)<x-field-tip text="Custo de proteção e embalagem de cada unidade produzida. Não inclui o frete cobrado separadamente."/><input type="number" name="packaging_cost_per_unit" value="{{ old('packaging_cost_per_unit', $configurator->packaging_cost_per_unit) }}" min="0" step="0.01" required></label>
        <label>Preparação da arte por pedido (R$)<x-field-tip text="Custo único para preparar ou ajustar o arquivo de corte. É dividido pela quantidade de peças do mesmo item no cálculo do preço unitário."/><input type="number" name="setup_cost_per_order" value="{{ old('setup_cost_per_order', $configurator->setup_cost_per_order) }}" min="0" step="0.01" required></label>
    </div>

    <div class="display-section-title"><h2>4. Preço de venda</h2><p>O custo inclui material, laser, acabamento, base, embalagem e preparação da arte. Taxas e margem são aplicadas ao final.</p></div>
    <div class="display-fields display-fields-three">
        <label>Taxas de venda (%)<x-field-tip text="Percentual sobre o preço vendido para cobrir taxas de pagamento, comissões ou outras despesas de venda. É somado à margem escolhida no cálculo."/><input type="number" name="selling_fee_percent" value="{{ old('selling_fee_percent', $configurator->selling_fee_percent) }}" min="0" max="94" step="0.01" required></label>
        <label>Margem cliente final (%)<x-field-tip text="Margem desejada sobre o preço de venda para compras comuns. A soma desta margem com as taxas deve ficar abaixo de 95%."/><input type="number" name="target_margin_percent" value="{{ old('target_margin_percent', $configurator->target_margin_percent) }}" min="0" max="94" step="0.01" required></label>
        <label>Margem revendedor (%)<x-field-tip text="Margem aplicada a revendedores elegíveis quando atingem a quantidade mínima. Deixe vazio para não oferecer preço especial de revenda neste configurador."/><input type="number" name="reseller_margin_percent" value="{{ old('reseller_margin_percent', $configurator->reseller_margin_percent) }}" min="0" max="94" step="0.01" placeholder="Opcional"></label>
        <label>Quantidade mínima revendedor<x-field-tip text="Número mínimo de unidades do mesmo nome ou texto para aplicar a margem de revendedor. Abaixo disso, vale o preço de cliente final."/><input type="number" name="reseller_min_quantity" value="{{ old('reseller_min_quantity', $configurator->reseller_min_quantity) }}" min="1" max="100" required></label>
        <label>Margem atacado (%)<x-field-tip text="Margem aplicada a clientes do grupo atacado quando atingem a quantidade mínima. Deixe vazio para não oferecer preço especial de atacado neste configurador."/><input type="number" name="wholesale_margin_percent" value="{{ old('wholesale_margin_percent', $configurator->wholesale_margin_percent) }}" min="0" max="94" step="0.01" placeholder="Opcional"></label>
        <label>Quantidade mínima atacado<x-field-tip text="Número mínimo de unidades do mesmo nome ou texto para aplicar a margem de atacado. Abaixo disso, vale o preço de cliente final."/><input type="number" name="wholesale_min_quantity" value="{{ old('wholesale_min_quantity', $configurator->wholesale_min_quantity) }}" min="1" max="100" required></label>
    </div>
    <label class="display-enable"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $configurator->enabled))> Publicar o configurador na loja quando estiver completo <x-field-tip text="Quando ativado e com todos os parâmetros válidos, o cliente poderá montar e comprar nomes e textos na loja. Desativado, o configurador não aparece na vitrine."/></label>
    <div class="form-actions"><button class="primary" type="submit">Salvar parâmetros →</button></div>
</form>
<section class="panel display-admin-form text-simulator" id="simulador">
    <div class="display-section-title"><h2>5. Simular preço de nome ou texto</h2><p>Teste uma peça usando os parâmetros já salvos. A simulação não cria orçamento nem publica o produto na loja.</p></div>
    @if($missing !== [])
        <div class="display-notice">Complete e salve os parâmetros acima para liberar a simulação. Você pode manter a publicação na loja desmarcada enquanto testa os preços.</div>
    @else
        <form method="POST" action="{{ route('texts.config.simulate') }}" data-admin-simulator="text" data-result="textSimulationResult" data-error="textSimulationError">@csrf
            <div class="display-fields display-fields-three">
                <label>Nome ou texto <x-field-tip text="Digite exatamente o texto a produzir. Espaços entram na largura estimada, mas não contam como caracteres de corte."/><input type="text" name="text" maxlength="100" value="{{ old('text', 'Aurora') }}" placeholder="Ex.: Aurora" required></label>
                <label>Material <x-field-tip text="Escolha MDF ou acrílico cadastrado. O custo, a espessura e as dimensões da chapa vêm do cadastro do material."/><select name="material_id" required><option value="">Selecione</option>@foreach($materials as $material)<option value="{{ $material->id }}" @selected(old('material_id') == $material->id)>{{ $material->name }} · {{ $material->thickness_mm }} mm</option>@endforeach</select></label>
                <label>Acabamento <x-field-tip text="Sem pintura mantém a cor da chapa. Branco pintado e pintura colorida usam os custos de acabamento configurados acima."/><select name="finish" id="textSimulationFinish"><option value="natural" @selected(old('finish', 'natural') === 'natural')>Sem pintura (cor da chapa)</option><option value="white" @selected(old('finish') === 'white')>Branco pintado</option><option value="painted" @selected(old('finish') === 'painted')>Pintado na cor escolhida</option></select></label>
                <label id="textSimulationColorField" hidden>Cor da pintura <x-field-tip text="Informe a cor desejada quando escolher pintura colorida. A descrição aparecerá na simulação."/><input type="text" name="color" id="textSimulationColor" maxlength="50" value="{{ old('color') }}" placeholder="Ex.: rosa claro"></label>
                <label>Altura final (cm) <x-field-tip text="Altura da peça pronta. Deve caber no limite salvo e na chapa do material escolhido."/><input type="number" name="height_cm" value="{{ old('height_cm', 10) }}" min="1" max="{{ $configurator->max_height_cm }}" step="0.1" required></label>
                <label>Largura final (cm) · opcional <x-field-tip text="Se deixar vazio, o sistema estima a largura pelo texto e pela altura. Se informar uma largura, ela precisa ser plausível e caber na chapa."/><input type="number" name="width_cm" value="{{ old('width_cm') }}" min="1" max="{{ $configurator->max_width_cm }}" step="0.1" placeholder="Estimar automaticamente"></label>
                <label>Quantidade <x-field-tip text="Número de peças iguais. A preparação da arte por pedido será dividida entre as unidades."/><input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="100" required></label>
            </div>
            <div class="form-actions"><button class="primary" type="submit">Calcular simulação →</button></div>
        </form>
        <p class="simulation-error" id="textSimulationError" role="alert" hidden></p>
        @php($simulation = session('text_simulation'))
        <div class="text-simulation-result" id="textSimulationResult" aria-live="polite" @unless($simulation) hidden @endunless>
            <div class="text-simulation-heading"><div><small>SIMULAÇÃO · CLIENTE FINAL</small><h3 data-sim="title">@if($simulation){{ $simulation['text'] }} · {{ $simulation['material_name'] }} {{ $simulation['thickness_mm'] }} mm @endif</h3><p data-sim="details">@if($simulation){{ $simulation['finish_label'] }} · {{ number_format($simulation['width_cm'], 1, ',', '.') }} × {{ number_format($simulation['height_cm'], 1, ',', '.') }} cm @if($simulation['width_estimated']) · largura estimada @endif · {{ $simulation['quantity'] }} unidade(s)@endif</p></div><strong data-sim="total">@if($simulation)R$ {{ number_format($simulation['total'], 2, ',', '.') }}@endif</strong></div>
            <p class="simulation-totals"><span>Custo por unidade: <b data-sim="unit_cost">@if($simulation)R$ {{ number_format($simulation['unit_cost'], 2, ',', '.') }}@endif</b></span><span>Preço por unidade: <b data-sim="unit_price">@if($simulation)R$ {{ number_format($simulation['unit_price'], 2, ',', '.') }}@endif</b></span></p>
            <button class="secondary simulation-cost-toggle" type="button" aria-expanded="false" aria-controls="textCostDetails" data-cost-toggle>Ver custos por item</button>
            <div class="text-simulation-breakdown simulation-cost-details" id="textCostDetails" hidden>
                @foreach(['materialCost' => 'Material', 'laserCost' => 'Máquina laser', 'finishCost' => 'Acabamento', 'base' => 'Base', 'packaging' => 'Embalagem', 'setup' => 'Preparação da arte'] as $key => $label)
                    <span>{{ $label }} @if($key === 'laserCost')<small data-laser-minutes>@if($simulation){{ number_format($simulation['laser_minutes'], 2, ',', '.') }} min @endif</small>@endif <b data-cost="{{ $key }}">@if($simulation)R$ {{ number_format($simulation['breakdown'][$key], 2, ',', '.') }}@endif</b></span>
                @endforeach
            </div>
            <small>Custos por unidade. Preço estimado com as medidas informadas; a arte final precisa respeitá-las.</small>
        </div>
    @endif
</section>
<script>
(() => {
    const create = document.getElementById('textCreateProduct');
    if (!create) return;
    const product = document.getElementById('textProductSelect');
    const name = document.getElementById('textNewProductName');
    const field = document.getElementById('textNewProductField');
    const update = () => {
        product.disabled = create.checked;
        name.disabled = !create.checked;
        name.required = create.checked;
        field.hidden = !create.checked;
    };
    create.addEventListener('change', update);
    update();
})();
(() => {
    const finish = document.getElementById('textSimulationFinish');
    if (!finish) return;
    const color = document.getElementById('textSimulationColor');
    const field = document.getElementById('textSimulationColorField');
    const update = () => {
        const painted = finish.value === 'painted';
        field.hidden = !painted;
        color.disabled = !painted;
        color.required = painted;
    };
    finish.addEventListener('change', update);
    update();
})();
</script>
<script src="{{ asset('js/admin-simulators.js') }}?v={{ filemtime(public_path('js/admin-simulators.js')) }}" defer></script>
@endsection
