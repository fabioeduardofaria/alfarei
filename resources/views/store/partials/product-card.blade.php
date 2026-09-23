@php($offer = $pricing->offer($product, $storeCustomer))
<article class="store-product">
    <a href="{{ route('loja.product', $product) }}" class="product-visual" aria-label="Ver {{ $product->name }}" @if($product->image_url) style="background-image:url('{{ $product->image_url }}')" @endif><span>{{ $product->allow_personalization ? 'Personalizável' : 'Produção Alfarei' }}</span>@unless($product->image_url)<b>{{ strtoupper(substr($product->name, 0, 1)) }}</b>@endunless</a>
    <div>
        <p>{{ $product->made_to_order ? 'Sob encomenda' : 'Disponível' }}</p><h3>{{ $product->name }}</h3><small>{{ Str::limit($product->description, 82) }}</small>
        @if(isset($displayConfigurator) && $displayConfigurator?->product_id === $product->id)
            <strong>A partir de R$ {{ number_format($displayStartingPrice, 2, ',', '.') }}</strong>
        @elseif($offer['tier_price'] !== null)
            <span class="normal-price">Preço normal R$ {{ number_format($offer['base_price'], 2, ',', '.') }}</span>
            <strong class="tier-card-price">R$ {{ number_format($offer['tier_price'], 2, ',', '.') }} <small>/ un.</small></strong>
            <span class="tier-minimum">{{ $offer['label'] }} · a partir de {{ $offer['min_quantity'] }} {{ $offer['min_quantity'] === 1 ? 'unidade' : 'unidades' }}</span>
        @else
            <strong>R$ {{ number_format($offer['base_price'], 2, ',', '.') }}</strong>
        @endif
        <a href="{{ route('loja.product', $product) }}">Conhecer a peça <span aria-hidden="true">→</span></a>
    </div>
</article>
