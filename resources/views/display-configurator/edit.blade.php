@extends('layouts.app', ['title' => 'Configurador de displays · Alfarei CNC'])

@section('content')
<div class="heading">
    <div><p class="eyebrow">E-COMMERCE · PREÇOS</p><h1>Configurador de displays</h1><p class="muted">Defina tamanhos e operações. MDF, adesivo e máquina usam os custos dos respectivos cadastros.</p></div>
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

    <div class="display-section-title"><h2>2. Produção por display</h2><p>O cliente não escolhe dificuldade do contorno. Os 3 minutos cobrem o corte padrão.</p></div>
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

    <div class="display-section-title"><h2>3. Preço de venda</h2><p>Taxas e margem incidem sobre o preço final; a preparação da arte é dividida pela quantidade.</p></div>
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

<section class="panel display-admin-form">
    <div class="display-section-title"><h2>4. Tamanhos disponíveis</h2><p>Somente tamanhos ativos aparecem para o cliente.</p></div>
    <div class="display-size-list">
        @forelse($configurator->sizes->sortBy('width_cm') as $size)
            <div><b>{{ $size->label }}</b><span>{{ $size->width_cm }} × {{ $size->height_cm }} cm</span><form class="display-size-minutes" method="POST" action="{{ route('displays.config.size.update', $size) }}">@csrf @method('PATCH')<label>Laser (min)<input type="number" name="laser_minutes_override" min="0.01" max="60" step="0.01" value="{{ $size->laser_minutes_override }}" placeholder="{{ $configurator->laser_minutes_per_unit }}"></label><button class="secondary" type="submit">Salvar</button></form><span class="status {{ $size->active ? 'on' : 'off' }}">{{ $size->active ? 'Ativo' : 'Inativo' }}</span><form method="POST" action="{{ route('displays.config.size.toggle', $size) }}">@csrf @method('PATCH')<button class="secondary" type="submit">{{ $size->active ? 'Desativar' : 'Ativar' }}</button></form></div>
        @empty<p class="muted">Nenhum tamanho cadastrado.</p>@endforelse
    </div>
    <form class="display-size-form" method="POST" action="{{ route('displays.config.size.store') }}">@csrf
        <label>Nome<input name="label" maxlength="80" placeholder="Ex.: Display 30 × 40 cm" required></label>
        <label>Largura (cm)<input type="number" name="width_cm" min="1" max="250" required></label>
        <label>Altura (cm)<input type="number" name="height_cm" min="1" max="250" required></label>
        <label>Laser (min) <small>opcional</small><input type="number" name="laser_minutes_override" min="0.01" max="60" step="0.01" placeholder="Padrão"></label>
        <button class="secondary" type="submit">+ Adicionar tamanho</button>
    </form>
</section>

@if($missing === [])
<section class="panel display-admin-form"><div class="display-section-title"><h2>Simulação de preços</h2><p>Confira valores para 1, 10 e 50 unidades antes de publicar.</p></div><div class="table-panel"><table><thead><tr><th>TAMANHO</th><th>QTD.</th><th>CUSTO/UN.</th><th>PREÇO/UN.</th><th>TOTAL</th></tr></thead><tbody>@foreach($configurator->sizes->where('active', true) as $size)@foreach([1, 10, 50] as $quantity)@php($quote = app(\App\Services\DisplayPricingService::class)->quote($configurator, $size, $quantity))<tr><td>{{ $size->label }}</td><td>{{ $quantity }}</td><td>R$ {{ number_format($quote['unit_cost'], 2, ',', '.') }}</td><td><b>R$ {{ number_format($quote['unit_price'], 2, ',', '.') }}</b></td><td>R$ {{ number_format($quote['total'], 2, ',', '.') }}</td></tr>@endforeach @endforeach</tbody></table></div></section>
@endif
@endsection
