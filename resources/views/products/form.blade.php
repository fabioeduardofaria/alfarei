@extends('layouts.app', ['title' => 'Produto · Alfarei CNC'])

@section('content')
    <div class="heading">
        <div>
            <p class="eyebrow">CADASTROS · PRODUTOS</p>
            <h1>{{ $product->exists ? 'Editar produto' : 'Novo produto' }}</h1>
            <p class="muted">Comece pelo essencial. Os dados técnicos podem ser complementados depois.</p>
        </div>
        <a class="secondary" href="{{ route('produtos.index') }}">← Voltar aos produtos</a>
    </div>

    <form class="panel form-card product-form" method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('produtos.update', $product) : route('produtos.store') }}">
        @csrf
        @if($product->exists) @method('PUT') @endif

        <section class="form-section">
            <div class="section-intro"><div><span>1</span><h2>O que você vai vender?</h2></div><p>Use um nome claro, que a equipe e o cliente reconheçam facilmente.</p></div>
            <div class="form-grid">
                <label class="span-2">Nome do produto ou serviço<input name="name" value="{{ old('name', $product->name) }}" placeholder="Ex.: Placa decorativa personalizada" required autofocus></label>
                <label>Tipo<select name="type"><option value="product" @selected(old('type', $product->type) === 'product')>Produto físico</option><option value="service" @selected(old('type', $product->type) === 'service')>Serviço</option></select></label>
                <label>SKU / código interno <small>(opcional)</small><input name="sku" value="{{ old('sku', $product->sku) }}" placeholder="Ex.: PLA-DEC-01">@error('sku')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="span-2">Descrição para a equipe e loja <small>(opcional)</small><textarea name="description" rows="4" placeholder="Dimensões, material, acabamento, aplicação e demais detalhes.">{{ old('description', $product->description) }}</textarea></label>
            </div>
        </section>

        <section class="form-section">
            <div class="section-intro"><div><span>2</span><h2>Preço e custo</h2></div><p>O custo será recalculado pela ficha técnica quando materiais e operações forem cadastrados.</p></div>
            <div class="form-grid"><label>Preço que o cliente paga (R$)<input type="number" step="0.01" min="0" name="base_price" value="{{ old('base_price', $product->base_price ?? 0) }}" required></label><label>Custo atual de produção (R$)<input type="number" step="0.01" min="0" name="production_cost" value="{{ old('production_cost', $product->production_cost ?? 0) }}" required></label></div>
            <p class="form-tip">Depois de salvar, abra a <b>Ficha técnica</b> para compor o custo com materiais, tempo e máquinas.</p>
            <div class="tier-pricing-admin">
                <h3>Preços para clientes da loja</h3><p>O preço acima vale para visitantes e clientes finais. Cada grupo especial só recebe seu valor quando atingir a quantidade mínima no mesmo produto.</p>
                <div class="form-grid">
                    <label>Preço revendedor (R$)<input type="number" step="0.01" min="0" name="reseller_price" value="{{ old('reseller_price', $product->reseller_price) }}" placeholder="Deixe vazio para não oferecer">@error('reseller_price')<small class="field-error">{{ $message }}</small>@enderror</label>
                    <label>Quantidade mínima revendedor<input type="number" min="1" max="100" name="reseller_min_quantity" value="{{ old('reseller_min_quantity', $product->reseller_min_quantity) }}" placeholder="Ex.: 5">@error('reseller_min_quantity')<small class="field-error">{{ $message }}</small>@enderror</label>
                    <label>Preço atacado (R$)<input type="number" step="0.01" min="0" name="wholesale_price" value="{{ old('wholesale_price', $product->wholesale_price) }}" placeholder="Deixe vazio para não oferecer">@error('wholesale_price')<small class="field-error">{{ $message }}</small>@enderror</label>
                    <label>Quantidade mínima atacado<input type="number" min="1" max="100" name="wholesale_min_quantity" value="{{ old('wholesale_min_quantity', $product->wholesale_min_quantity) }}" placeholder="Ex.: 20">@error('wholesale_min_quantity')<small class="field-error">{{ $message }}</small>@enderror</label>
                </div>
            </div>
        </section>

        <section class="form-section">
            <div class="section-intro"><div><span>3</span><h2>Disponibilidade</h2></div><p>Escolha como este item será tratado pela operação e pela loja.</p></div>
            <div class="product-switches">
                <label class="option-card"><input type="hidden" name="made_to_order" value="0"><input type="checkbox" name="made_to_order" value="1" @checked(old('made_to_order', $product->exists ? $product->made_to_order : true))><span><b>Produzido sob encomenda</b><small>Cria produção quando o pedido for liberado.</small></span></label>
                <label class="option-card"><input type="hidden" name="store_visible" value="0"><input type="checkbox" name="store_visible" value="1" @checked(old('store_visible', $product->store_visible))><span><b>Mostrar na loja virtual</b><small>Permite que clientes encontrem este item no catálogo.</small></span></label>
                <label class="option-card"><input type="hidden" name="allow_personalization" value="0"><input type="checkbox" name="allow_personalization" value="1" @checked(old('allow_personalization', $product->allow_personalization))><span><b>Aceita personalização</b><small>Exibe um campo para o cliente descrever o pedido.</small></span></label>
                <label class="option-card"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked(old('active', $product->exists ? $product->active : true))><span><b>Produto ativo</b><small>Deixe desmarcado apenas quando não quiser mais utilizá-lo.</small></span></label>
            </div>
        </section>

        <section class="form-section">
            <div class="section-intro"><div><span>4</span><h2>Como encontrar este produto na loja?</h2></div><p>Escolha as ocasiões em que faz sentido sugeri-lo ao cliente. Pode marcar mais de uma.</p></div>
            <div class="product-switches">
                @php($occasions = old('store_occasions', $product->store_occasions ?? []))
                <label class="option-card"><input type="checkbox" name="store_occasions[]" value="presente" @checked(in_array('presente', $occasions))><span><b>Para presentear</b><small>Lembranças e presentes personalizados.</small></span></label>
                <label class="option-card"><input type="checkbox" name="store_occasions[]" value="decoracao" @checked(in_array('decoracao', $occasions))><span><b>Para decorar</b><small>Peças para casa, ambientes e eventos.</small></span></label>
                <label class="option-card"><input type="checkbox" name="store_occasions[]" value="empresa" @checked(in_array('empresa', $occasions))><span><b>Para minha empresa</b><small>Identidade, sinalização e brindes.</small></span></label>
                <label class="option-card"><input type="hidden" name="store_featured" value="0"><input type="checkbox" name="store_featured" value="1" @checked(old('store_featured', $product->store_featured))><span><b>Destacar na vitrine</b><small>Mostra este item nas escolhas da Alfarei.</small></span></label>
            </div>
            @error('store_occasions')<small class="field-error">{{ $message }}</small>@enderror
            @error('store_occasions.*')<small class="field-error">{{ $message }}</small>@enderror
        </section>

        <details class="product-advanced"><summary>Opções avançadas</summary><div class="form-grid"><label class="span-2">URL externa da imagem <small>(use somente se a foto estiver hospedada em outro site)</small><input type="text" name="image_url" value="{{ old('image_url', $product->image_url) }}" placeholder="https://..."></label><label>URL amigável da loja <small>(opcional)</small><input name="store_slug" value="{{ old('store_slug', $product->store_slug) }}" placeholder="placa-decorativa-personalizada"></label></div></details>
        <div class="form-actions"><a class="secondary" href="{{ route('produtos.index') }}">Cancelar</a><button class="primary" type="submit">{{ $product->exists ? 'Salvar alterações' : 'Salvar produto e continuar' }} →</button></div>
    </form>

    @if($product->exists)
        <section class="panel product-photo-panel">
            <div class="section-intro"><div><span>5</span><h2>Fotos da loja</h2></div><p>Envie ou remova fotos sem precisar salvar os outros campos do produto.</p></div>
            <form method="POST" enctype="multipart/form-data" action="{{ route('produtos.images.store', $product) }}">
                @csrf
                <div class="form-grid photo-upload-grid">
                    <label class="image-upload-field photo-upload">Trocar foto principal<input type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG ou WebP, até 5 MB.</small></label>
                    <label class="gallery-dropzone photo-upload">Adicionar fotos à galeria <small>(até 8)</small><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple><span>Selecione várias imagens de uma só vez</span></label>
                </div>
                <div class="form-actions"><button class="primary" type="submit">Enviar fotos →</button></div>
            </form>
            @if($product->image_url || $product->images->isNotEmpty())
                <div class="photo-library">
                    <div><b>Fotos cadastradas</b><small>A primeira imagem é a capa exibida na loja.</small></div>
                    <div class="product-gallery-manager">
                        @if($product->image_url)<div class="gallery-card main-gallery-card"><img src="{{ $product->image_url }}" alt="Foto principal de {{ $product->name }}"><span>Principal</span><form method="POST" action="{{ route('produtos.main-image.destroy', $product) }}">@csrf @method('DELETE')<button type="submit" class="remove-gallery-image" aria-label="Remover foto principal">×</button></form></div>@endif
                        @foreach($product->images as $image)
                            <div class="gallery-card"><img src="{{ $image->path }}" alt="Foto de {{ $product->name }}"><form method="POST" action="{{ route('produtos.images.destroy', [$product, $image]) }}">@csrf @method('DELETE')<button type="submit" class="remove-gallery-image" aria-label="Remover foto">×</button></form></div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @else
        <section class="panel product-photo-panel photo-after-save"><b>Fotos vêm em seguida</b><p>Salve primeiro as informações básicas do produto. Na próxima tela, você poderá enviar e remover imagens sem salvar o cadastro inteiro novamente.</p></section>
    @endif
@endsection
