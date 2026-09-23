@extends('store.layout')

@section('content')
<section class="store-hero">
    <div><p class="store-eyebrow">PRODUÇÃO SOB ENCOMENDA</p><h1>{{ $settings->hero_title }}</h1><p>{{ $settings->hero_subtitle }}</p><a class="store-primary" href="{{ $settings->hero_cta_url ?: '#descobrir' }}">{{ $settings->hero_cta_label ?: 'Encontrar minha peça' }}</a></div>
    <div class="hero-art" @if($settings->hero_image_url) style="background-image:url('{{ $settings->hero_image_url }}');background-size:cover;background-position:center" @endif><span>FEITO<br>PARA<br>VOCÊ</span><i>✦</i></div>
</section>
<section class="trust" aria-label="Vantagens da Alfarei"><span>✦ Produção própria</span><span>◇ Personalização simples</span><span>◌ Acompanhamento do pedido</span></section>

<section class="discovery-section" id="descobrir">
    <div class="discovery-heading"><div><p class="store-eyebrow">COMECE PELA SUA IDEIA</p><h2>O que você quer criar?</h2></div><p>Escolha seu objetivo. Nós mostramos as peças que combinam com ele.</p></div>
    <div class="discovery-grid">
        <a class="discovery-card discovery-gift" href="{{ route('loja.index', ['uso' => 'presente']) }}#catalogo"><span class="discovery-icon" aria-hidden="true">✦</span><small>UM GESTO ESPECIAL</small><h3>Quero presentear</h3><p>Ideias com personalidade para surpreender alguém.</p><b>Explorar presentes <span aria-hidden="true">↗</span></b></a>
        <a class="discovery-card discovery-decor" href="{{ route('loja.index', ['uso' => 'decoracao']) }}#catalogo"><span class="discovery-icon" aria-hidden="true">◇</span><small>ESPAÇOS COM HISTÓRIA</small><h3>Quero decorar</h3><p>Peças para dar um toque único ao seu ambiente.</p><b>Explorar decoração <span aria-hidden="true">↗</span></b></a>
        <a class="discovery-card discovery-business" href="{{ route('loja.index', ['uso' => 'empresa']) }}#catalogo"><span class="discovery-icon" aria-hidden="true">▧</span><small>SUA MARCA EM DESTAQUE</small><h3>É para minha empresa</h3><p>Identidade, sinalização e detalhes profissionais.</p><b>Explorar soluções <span aria-hidden="true">↗</span></b></a>
    </div>
</section>

@if($displayConfigurator)
<section class="display-store-feature"><div><p class="store-eyebrow">MONTE E VEJA O PREÇO</p><h2>Seu display, do seu jeito.</h2><p>Escolha o tamanho e a quantidade. O preço aparece na hora, sem classificar o desenho nem esperar pela arte.</p></div><a class="store-primary" href="{{ route('loja.display.show') }}">Montar meu display →</a></section>
@endif

@if($featured->isNotEmpty() && ! $intent && $search === '')
<section class="featured-section" aria-labelledby="featured-title">
    <div class="section-heading"><div><p class="store-eyebrow">ESCOLHAS DA ALFAREI</p><h2 id="featured-title">Para se inspirar agora.</h2></div><a class="text-link" href="#catalogo">Ver catálogo completo ↗</a></div>
    <div class="product-grid">@foreach($featured as $product) @include('store.partials.product-card', ['product' => $product]) @endforeach</div>
</section>
@endif

<section class="store-section" id="catalogo">
    <div class="section-heading"><div><p class="store-eyebrow">CATÁLOGO</p><h2>Encontre a peça certa.</h2></div><p>Explore no seu ritmo. Personalize quando quiser.</p></div>
    <form class="catalog-toolbar" method="GET" action="{{ route('loja.index') }}#catalogo" role="search">
        @if($intent)<input type="hidden" name="uso" value="{{ $intent }}">@endif
        <label class="catalog-search"><span class="sr-only">Buscar produtos</span><span aria-hidden="true">⌕</span><input type="search" name="busca" value="{{ $search }}" placeholder="Busque por nome, material ou ideia..." maxlength="100"><button type="submit">Buscar</button></label>
        <label class="catalog-sort">Ordenar <select name="ordenar" onchange="this.form.submit()"><option value="destaques" @selected($sort === 'destaques')>Destaques</option><option value="recentes" @selected($sort === 'recentes')>Novidades</option><option value="menor-preco" @selected($sort === 'menor-preco')>Menor preço</option><option value="maior-preco" @selected($sort === 'maior-preco')>Maior preço</option></select></label>
    </form>
    <div class="catalog-filters" aria-label="Filtrar catálogo">
        @foreach(['' => 'Todos', 'presente' => 'Presentes', 'decoracao' => 'Decoração', 'empresa' => 'Empresas', 'personalizavel' => 'Personalizáveis', 'pronta-entrega' => 'Pronta entrega'] as $key => $label)
            <a href="{{ route('loja.index', array_filter(['uso' => $key ?: null, 'busca' => $search ?: null, 'ordenar' => $sort !== 'destaques' ? $sort : null])) }}#catalogo" @class(['active' => ($intent ?? '') === $key]) @if(($intent ?? '') === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </div>
    <div class="catalog-status"><span>{{ $products->total() }} {{ $products->total() === 1 ? 'produto encontrado' : 'produtos encontrados' }}</span>@if($search || $intent)<a href="{{ route('loja.index') }}#catalogo">Limpar filtros ×</a>@endif</div>
    @if($products->isNotEmpty())
        <div class="product-grid">@foreach($products as $product) @include('store.partials.product-card', ['product' => $product]) @endforeach</div>
        @if($products->hasPages())<nav class="catalog-pagination" aria-label="Páginas do catálogo">@if($products->onFirstPage())<span>← Anterior</span>@else<a href="{{ $products->previousPageUrl() }}#catalogo">← Anterior</a>@endif <small>Página {{ $products->currentPage() }} de {{ $products->lastPage() }}</small> @if($products->hasMorePages())<a href="{{ $products->nextPageUrl() }}#catalogo">Próxima →</a>@else<span>Próxima →</span>@endif</nav>@endif
    @else
        <div class="empty-store"><h3>Nenhuma peça por aqui ainda.</h3><p>Tente outra busca ou explore todo o catálogo. Se tiver uma ideia especial, fale com a Alfarei.</p><a class="store-primary" href="{{ route('loja.index') }}#catalogo">Ver todos os produtos</a></div>
    @endif
</section>
<section class="how" id="como-funciona"><div><p class="store-eyebrow">DO SEU JEITO</p><h2>Sem complicar a criação.</h2></div><ol><li><b>01</b><span>Encontre uma peça para sua ideia.</span></li><li><b>02</b><span>Conte como deseja personalizar.</span></li><li><b>03</b><span>Confirme o pedido e acompanhe a produção.</span></li></ol></section>
@endsection
