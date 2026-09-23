@extends('layouts.app', ['title' => 'Configurador de nomes e textos · Alfarei CNC'])

@section('content')
<div class="heading"><div><p class="eyebrow">E-COMMERCE · PREÇOS</p><h1>Configurador de nomes e textos</h1><p class="muted">Corte em MDF ou acrílico, com acabamento original, branco ou pintado.</p></div><a class="secondary" href="{{ route('displays.config.edit') }}">← Configurador de displays</a></div>
@if($missing)<div class="display-notice"><b>Configuração incompleta: ainda não será oferecida na loja nem no orçamento automático.</b><ul>@foreach($missing as $requirement)<li>{{ $requirement }}</li>@endforeach</ul></div>@endif
@if($errors->any())<div class="display-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="panel display-admin-form" method="POST" action="{{ route('texts.config.update') }}">@csrf @method('PUT')
    <div class="display-section-title"><h2>1. Produto e máquina</h2><p>Os materiais são lidos automaticamente do cadastro: apenas MDF e acrílico ativos, com espessura, medidas e custo válidos.</p></div>
    <div class="display-fields">
        <label>Produto da loja<select name="product_id"><option value="">Selecione</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(old('product_id', $configurator->product_id) == $product->id)>{{ $product->name }}</option>@endforeach</select></label>
        <label>Máquina laser<select name="laser_machine_id"><option value="">Selecione</option>@foreach($machines as $machine)<option value="{{ $machine->id }}" @selected(old('laser_machine_id', $configurator->laser_machine_id) == $machine->id)>{{ $machine->name }} · R$ {{ number_format($machine->calculatedHourlyCost() ?: $machine->hourly_cost, 2, ',', '.') }}/h</option>@endforeach</select></label>
    </div>
    <div class="text-config-materials"><b>Materiais elegíveis</b><p>@forelse($materials as $material)<span>{{ $material->name }} · {{ $material->thickness_mm }} mm · R$ {{ number_format($material->cost_per_unit, 2, ',', '.') }}/{{ $material->unit }}</span>@empty Nenhum MDF ou acrílico elegível. @endforelse</p></div>

    <div class="display-section-title"><h2>2. Medidas e corte</h2><p>Largura e altura formam a área de material. Se a largura não for informada, será estimada pelo comprimento do texto e a proporção média abaixo. O tempo de corte é uma estimativa configurável.</p></div>
    <div class="display-fields display-fields-three">
        <label>Largura máxima (cm)<input type="number" name="max_width_cm" value="{{ old('max_width_cm', $configurator->max_width_cm) }}" min="1" max="250" step="0.1" required></label>
        <label>Altura máxima (cm)<input type="number" name="max_height_cm" value="{{ old('max_height_cm', $configurator->max_height_cm) }}" min="1" max="250" step="0.1" required></label>
        <label>Proporção média da largura por caractere<input type="number" name="average_character_width_ratio" value="{{ old('average_character_width_ratio', $configurator->average_character_width_ratio) }}" min="0.2" max="1.5" step="0.01" required></label>
        <label>Largura mínima aceita (% da estimativa)<input type="number" name="minimum_width_percent" value="{{ old('minimum_width_percent', $configurator->minimum_width_percent) }}" min="20" max="100" step="0.01" required></label>
        <label>Laser por caractere a 10 cm (min)<input type="number" name="laser_minutes_per_character_10cm" value="{{ old('laser_minutes_per_character_10cm', $configurator->laser_minutes_per_character_10cm) }}" min="0.01" max="60" step="0.01" required></label>
        <label>Tempo mínimo de laser (min)<input type="number" name="minimum_laser_minutes" value="{{ old('minimum_laser_minutes', $configurator->minimum_laser_minutes) }}" min="0.01" max="60" step="0.01" required></label>
        <label>Perda de material (%)<input type="number" name="material_loss_percent" value="{{ old('material_loss_percent', $configurator->material_loss_percent) }}" min="0" max="100" step="0.01" required></label>
    </div>

    <div class="display-section-title"><h2>3. Acabamento e custos</h2><p>“Sem pintura” usa a cor original da chapa. Branco e pintado usam os custos de acabamento informados abaixo. Se usar uma chapa branca pronta, selecione-a como material e escolha “Sem pintura”.</p></div>
    <div class="display-fields display-fields-three">
        <label>Acabamento branco por m² (R$)<input type="number" name="white_finish_cost_per_m2" value="{{ old('white_finish_cost_per_m2', $configurator->white_finish_cost_per_m2) }}" min="0" step="0.01" required></label>
        <label>Pintura colorida por m² (R$)<input type="number" name="painted_finish_cost_per_m2" value="{{ old('painted_finish_cost_per_m2', $configurator->painted_finish_cost_per_m2) }}" min="0" step="0.01" required></label>
        <label>Base por unidade (R$)<input type="number" name="base_cost_per_unit" value="{{ old('base_cost_per_unit', $configurator->base_cost_per_unit) }}" min="0" step="0.01" required></label>
        <label>Embalagem por unidade (R$)<input type="number" name="packaging_cost_per_unit" value="{{ old('packaging_cost_per_unit', $configurator->packaging_cost_per_unit) }}" min="0" step="0.01" required></label>
        <label>Preparação da arte por pedido (R$)<input type="number" name="setup_cost_per_order" value="{{ old('setup_cost_per_order', $configurator->setup_cost_per_order) }}" min="0" step="0.01" required></label>
    </div>

    <div class="display-section-title"><h2>4. Preço de venda</h2><p>O custo inclui material, laser, acabamento, base, embalagem e preparação da arte. Taxas e margem são aplicadas ao final.</p></div>
    <div class="display-fields display-fields-three">
        <label>Taxas de venda (%)<input type="number" name="selling_fee_percent" value="{{ old('selling_fee_percent', $configurator->selling_fee_percent) }}" min="0" max="94" step="0.01" required></label>
        <label>Margem cliente final (%)<input type="number" name="target_margin_percent" value="{{ old('target_margin_percent', $configurator->target_margin_percent) }}" min="0" max="94" step="0.01" required></label>
        <label>Margem revendedor (%)<input type="number" name="reseller_margin_percent" value="{{ old('reseller_margin_percent', $configurator->reseller_margin_percent) }}" min="0" max="94" step="0.01" placeholder="Opcional"></label>
        <label>Quantidade mínima revendedor<input type="number" name="reseller_min_quantity" value="{{ old('reseller_min_quantity', $configurator->reseller_min_quantity) }}" min="1" max="100" required></label>
        <label>Margem atacado (%)<input type="number" name="wholesale_margin_percent" value="{{ old('wholesale_margin_percent', $configurator->wholesale_margin_percent) }}" min="0" max="94" step="0.01" placeholder="Opcional"></label>
        <label>Quantidade mínima atacado<input type="number" name="wholesale_min_quantity" value="{{ old('wholesale_min_quantity', $configurator->wholesale_min_quantity) }}" min="1" max="100" required></label>
    </div>
    <label class="display-enable"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $configurator->enabled))> Publicar o configurador na loja quando estiver completo</label>
    <div class="form-actions"><button class="primary" type="submit">Salvar parâmetros →</button></div>
</form>
@endsection
