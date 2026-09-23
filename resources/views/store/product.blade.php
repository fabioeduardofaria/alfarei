@extends('store.layout')

@section('content')
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
        <strong class="product-price">R$ {{ number_format($product->base_price, 2, ',', '.') }}</strong>
        <form method="POST" action="{{ route('loja.add', $product) }}">
            @csrf
            @if($product->allow_personalization)
                <label>Como deseja personalizar?<textarea name="personalization" placeholder="Ex.: nome, frase, medidas, acabamento ou referência visual."></textarea></label>
            @endif
            <label>Quantidade<select name="quantity"><option value="1">1 unidade</option><option value="2">2 unidades</option><option value="3">3 unidades</option><option value="5">5 unidades</option></select></label>
            <button class="store-primary" type="submit">Adicionar ao carrinho →</button>
        </form>
        <small class="delivery-note">{{ $settings->delivery_message }}</small>
    </div>
</section>
@if($related->isNotEmpty())<section class="related-products store-section"><div class="section-heading"><div><p class="store-eyebrow">CONTINUE EXPLORANDO</p><h2>Mais ideias para você.</h2></div><a class="text-link" href="{{ route('loja.index') }}#catalogo">Ver catálogo ↗</a></div><div class="product-grid">@foreach($related as $product) @include('store.partials.product-card', ['product' => $product]) @endforeach</div></section>@endif
@if($images->count() > 1)
<script>document.querySelectorAll('.product-thumbnails button').forEach(button=>button.addEventListener('click',()=>{document.getElementById('mainProductImage').style.backgroundImage=`url("${button.dataset.image}")`;document.querySelectorAll('.product-thumbnails button').forEach(item=>item.classList.toggle('active',item===button));}));</script>
@endif
@endsection
