@extends('layouts.app', ['title' => 'Configurador de displays · Alfarei CNC'])

@section('content')
<div class="heading">
    <div><p class="eyebrow">E-COMMERCE · PREÇOS</p><h1>Configurador de displays</h1><p class="muted">Defina o limite das medidas e as operações. MDF, adesivo e máquina usam os custos dos respectivos cadastros.</p></div>
    <a class="secondary" href="{{ route('loja.settings.edit') }}">← Configurar loja</a>
</div>

@if($missing)
<div class="display-notice"><b>Loja protegida: configurador indisponível até concluir o cadastro.</b><ul>@foreach($missing as $requirement)<li>{{ $requirement }}</li>@endforeach</ul></div>
@endif

<form class="panel display-admin-form" method="POST" action="{{ route('displays.config.update') }}">
    @csrf @method('PUT')
    <div class="display-section-title"><h2>1. Origens dos custos</h2><p>Selecione os cadastros existentes. O preço será lido diretamente deles.</p></div>
    <div class="display-fields">
        <label>Produto da loja<select name="product_id"><option value="">Selecione</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(old('product_id', $configurator->product_id) == $product->id)>{{ $product->name }}</option>@endforeach</select></label>
        <label>MDF 3 mm<select name="mdf_material_id"><option value="">Selecione</option>@foreach($materials as $material)<option value="{{ $material->id }}" @selected(old('mdf_material_id', $configurator->mdf_material_id) == $material->id)>{{ $material->name }} · {{ $material->thickness_mm ?: '?' }} mm · R$ {{ number_format($material->cost_per_unit, 2, ',', '.') }}/{{ $material->unit }}</option>@endforeach</select></label>
        <label>Adesivo<select name="adhesive_material_id"><option value="">Selecione</option>@foreach($materials as $material)<option value="{{ $material->id }}" @selected(old('adhesive_material_id', $configurator->adhesive_material_id) == $material->id)>{{ $material->name }} · R$ {{ number_format($material->cost_per_unit, 2, ',', '.') }}/{{ $material->unit }}</option>@endforeach</select></label>
        <label>Máquina laser<select name="laser_machine_id"><option value="">Selecione</option>@foreach($machines as $machine)<option value="{{ $machine->id }}" @selected(old('laser_machine_id', $configurator->laser_machine_id) == $machine->id)>{{ $machine->name }} · R$ {{ number_format($machine->calculatedHourlyCost() ?: $machine->hourly_cost, 2, ',', '.') }}/h</option>@endforeach</select></label>
    </div>

    <div class="display-section-title"><h2>2. Limites de medida</h2><p>Você define apenas o máximo; cada display pode ter qualquer medida menor, em passos de 0,1 cm. O mesmo limite vale na loja e na simulação de orçamentos.</p></div>
    <div class="display-fields">
        <label>Largura máxima (cm)<input type="number" name="max_width_cm" value="{{ old('max_width_cm', $configurator->max_width_cm) }}" min="1" max="250" step="0.1" required></label>
        <label>Altura máxima (cm)<input type="number" name="max_height_cm" value="{{ old('max_height_cm', $configurator->max_height_cm) }}" min="1" max="250" step="0.1" required></label>
    </div>

    <div class="display-section-title"><h2>3. Produção por display</h2><p>O cliente não escolhe dificuldade do contorno. Os 3 minutos cobrem o corte padrão e podem ser ajustados aqui.</p></div>
    <div class="display-fields display-fields-three">
        @foreach([
            'laser_minutes_per_unit' => ['Laser por unidade (min)', '0.01'],
            'mdf_loss_percent' => ['Perda MDF (%)', '0.01'],
            'adhesive_loss_percent' => ['Perda adesivo (%)', '0.01'],
            'printing_cost_per_m2' => ['Impressão por m² (R$)', '0.01'],
            'application_cost_per_m2' => ['Aplicação por m² (R$)', '0.01'],
            'base_cost_per_unit' => ['Base por unidade (R$)', '0.01'],
            'assembly_cost_per_unit' => ['Montagem por unidade (R$)', '0.01'],
            'packaging_cost_per_unit' => ['Embalagem por unidade (R$)', '0.01'],
            'artwork_setup_cost_per_order' => ['Preparação da arte por pedido (R$)', '0.01'],
        ] as $field => [$label, $step])
            <label>{{ $label }}<input type="number" name="{{ $field }}" value="{{ old($field, $configurator->{$field}) }}" min="0" step="{{ $step }}" required></label>
        @endforeach
    </div>

    <div class="display-section-title"><h2>4. Preço de venda</h2><p>Taxas e margem incidem sobre o preço final; a preparação da arte é dividida pela quantidade.</p></div>
    <div class="display-fields">
        <label>Taxas de venda (%)<input type="number" name="selling_fee_percent" value="{{ old('selling_fee_percent', $configurator->selling_fee_percent) }}" min="0" max="94" step="0.01" required></label>
        <label>Margem desejada (%)<input type="number" name="target_margin_percent" value="{{ old('target_margin_percent', $configurator->target_margin_percent) }}" min="0" max="94" step="0.01" required></label>
        <label>Margem revendedor (%) <small>opcional</small><input type="number" name="reseller_margin_percent" value="{{ old('reseller_margin_percent', $configurator->reseller_margin_percent) }}" min="0" max="94" step="0.01" placeholder="Sem preço especial"></label>
        <label>Quantidade mínima revendedor<input type="number" name="reseller_min_quantity" value="{{ old('reseller_min_quantity', $configurator->reseller_min_quantity) }}" min="1" max="100" required></label>
        <label>Margem atacado (%) <small>opcional</small><input type="number" name="wholesale_margin_percent" value="{{ old('wholesale_margin_percent', $configurator->wholesale_margin_percent) }}" min="0" max="94" step="0.01" placeholder="Sem preço especial"></label>
        <label>Quantidade mínima atacado<input type="number" name="wholesale_min_quantity" value="{{ old('wholesale_min_quantity', $configurator->wholesale_min_quantity) }}" min="1" max="100" required></label>
    </div>
    <label class="display-enable"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $configurator->enabled))> Publicar o configurador na loja quando todos os dados estiverem completos</label>
    @if($errors->any())<div class="display-errors">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="form-actions"><button class="primary" type="submit">Salvar parâmetros →</button></div>
</form>

@if($missing === [])
<section class="panel display-admin-form">
    <div class="display-section-title"><h2>Simular orçamento sob medida</h2><p>Informe as dimensões reais e a quantidade. O cálculo usa as mesmas regras da loja; depois, use o preço no orçamento comercial.</p></div>
    <form class="display-fields display-simulation-form" method="POST" action="{{ route('displays.config.simulate') }}">@csrf
        <label>Largura (cm)<input type="number" name="width_cm" value="{{ old('width_cm', $configurator->max_width_cm) }}" min="1" max="{{ $configurator->max_width_cm }}" step="0.1" required></label>
        <label>Altura (cm)<input type="number" name="height_cm" value="{{ old('height_cm', $configurator->max_height_cm) }}" min="1" max="{{ $configurator->max_height_cm }}" step="0.1" required></label>
        <label>Quantidade<input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="100" required></label>
        <button class="primary" type="submit">Calcular preço →</button>
    </form>
    @if($simulation = session('display_simulation'))
        <div class="display-simulation-result"><b>{{ $simulation['size_label'] }} · {{ $simulation['quantity'] }} unidade(s)</b><span>Custo por unidade: R$ {{ number_format($simulation['unit_cost'], 2, ',', '.') }}</span><span>Preço por unidade: R$ {{ number_format($simulation['unit_price'], 2, ',', '.') }}</span><strong>Total: R$ {{ number_format($simulation['total'], 2, ',', '.') }}</strong></div>
    @endif
</section>
@endif
@endsection
