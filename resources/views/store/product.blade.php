@extends('store.layout')

@section('content')
@php($offer = $pricing->offer($product, $storeCustomer))
<section class="product-page">
    <div class="product-gallery">
        <div class="product-hero" id="mainProductImage" @if($images->isNotEmpty()) style="background-image:url('{{ $images->first() }}')" @endif>
            <span>{{ $product->made_to_order ? 'Produzido sob encomenda' : 'Pronta entrega' }}</span>
            @if($images->isEmpty())<b>{{ strtoupper(substr($product->name, 0, 1)) }}</b>@endif
        </div>
        @if($images->count() > 1)
            <div class="product-thumbnails">
                @foreach($images as $image)
                    <button type="button" class="{{ $loop->first ? 'active' : '' }}" data-image="{{ $image }}"><img src="{{ $image }}" alt="Foto {{ $loop->iteration }} de {{ $product->name }}"></button>
                @endforeach
            </div>
        @endif
    </div>
    <div class="product-info">
        <a class="back-store" href="{{ route('loja.index') }}#catalogo">← Voltar ao catálogo</a>
        <p class="store-eyebrow">{{ $product->type === 'service' ? 'SERVIÇO' : 'PRODUTO ALFAREI' }}</p>
        <h1>{{ $product->name }}</h1>
        <p class="product-description">{{ $product->description }}</p>
        <div class="product-pricing" id="productPricing" data-base="{{ $offer['base_price'] }}" data-tier="{{ $offer['tier_price'] }}" data-minimum="{{ $offer['min_quantity'] }}">
            @if($offer['tier_price'] !== null)<span class="normal-price">Preço normal R$ {{ number_format($offer['base_price'], 2, ',', '.') }}</span>@endif
            <strong class="product-price" id="currentPrice">R$ {{ number_format($pricing->unitPrice($product, $storeCustomer, 1), 2, ',', '.') }}</strong>
            @if($offer['tier_price'] !== null)<span class="tier-minimum" id="tierMessage">Preço {{ strtolower($offer['label']) }} de R$ {{ number_format($offer['tier_price'], 2, ',', '.') }} por unidade a partir de {{ $offer['min_quantity'] }} {{ $offer['min_quantity'] === 1 ? 'unidade' : 'unidades' }}.</span>@endif
        </div>
        <form method="POST" action="{{ route('loja.add', $product) }}">
            @csrf
            @if($product->allow_personalization)
                <label>Como deseja personalizar?<textarea name="personalization" placeholder="Ex.: nome, frase, medidas, acabamento ou referência visual."></textarea></label>
            @endif
            <label>Quantidade<input id="productQuantity" type="number" name="quantity" min="1" max="100" value="1" required></label>
            @error('quantity')<small class="account-error">{{ $message }}</small>@enderror
            <button class="store-primary" type="submit">Adicionar ao carrinho →</button>
        </form>
        <small class="delivery-note">{{ $settings->delivery_message }}</small>
    </div>
</section>
@if($related->isNotEmpty())<section class="related-products store-section"><div class="section-heading"><div><p class="store-eyebrow">CONTINUE EXPLORANDO</p><h2>Mais ideias para você.</h2></div><a class="text-link" href="{{ route('loja.index') }}#catalogo">Ver catálogo ↗</a></div><div class="product-grid">@foreach($related as $product) @include('store.partials.product-card', ['product' => $product]) @endforeach</div></section>@endif
@if($images->count() > 1)
<script>document.querySelectorAll('.product-thumbnails button').forEach(button=>button.addEventListener('click',()=>{document.getElementById('mainProductImage').style.backgroundImage=`url("${button.dataset.image}")`;document.querySelectorAll('.product-thumbnails button').forEach(item=>item.classList.toggle('active',item===button));}));</script>
@endif
<script>
const quantityInput = document.getElementById('productQuantity');
const pricingBox = document.getElementById('productPricing');
quantityInput?.addEventListener('input', () => {
    const quantity = Number(quantityInput.value) || 1;
    const tier = pricingBox.dataset.tier === '' ? null : Number(pricingBox.dataset.tier);
    const minimum = Number(pricingBox.dataset.minimum);
    const price = tier !== null && quantity >= minimum ? tier : Number(pricingBox.dataset.base);
    document.getElementById('currentPrice').textContent = price.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
    pricingBox.classList.toggle('tier-active', tier !== null && quantity >= minimum);
});
</script>
@endsection
