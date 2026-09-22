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
        </section>

        <section class="form-section">
            <div class="section-intro"><div><span>3</span><h2>Fotos da loja</h2></div><p>Uma boa foto torna o produto mais fácil de escolher no e-commerce.</p></div>
            <div class="form-grid">
                <label class="span-2 image-upload-field">Foto principal<input type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG ou WebP, até 5 MB.</small>@error('image')<small class="field-error">{{ $message }}</small>@enderror</label>
                @if($product->image_url)<div class="product-image-preview span-2"><img src="{{ $product->image_url }}" alt="Foto principal atual de {{ $product->name }}"><span>Foto principal atual</span></div>@endif
                <label class="span-2 gallery-dropzone">Adicionar fotos à galeria <small>(opcional, até 8)</small><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple><span>Selecione várias imagens de uma só vez</span></label>
                @if($product->exists && $product->images->isNotEmpty())
                    <div class="product-gallery-manager span-2">
                        @foreach($product->images as $image)
                            <div class="gallery-card"><img src="{{ $image->path }}" alt="Foto de {{ $product->name }}"><form method="POST" action="{{ route('produtos.images.destroy', [$product, $image]) }}">@csrf @method('DELETE')<button type="submit" class="remove-gallery-image" aria-label="Remover foto">×</button></form></div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="form-section">
            <div class="section-intro"><div><span>4</span><h2>Disponibilidade</h2></div><p>Escolha como este item será tratado pela operação e pela loja.</p></div>
            <div class="product-switches">
                <label class="option-card"><input type="hidden" name="made_to_order" value="0"><input type="checkbox" name="made_to_order" value="1" @checked(old('made_to_order', $product->exists ? $product->made_to_order : true))><span><b>Produzido sob encomenda</b><small>Cria produção quando o pedido for liberado.</small></span></label>
                <label class="option-card"><input type="hidden" name="store_visible" value="0"><input type="checkbox" name="store_visible" value="1" @checked(old('store_visible', $product->store_visible))><span><b>Mostrar na loja virtual</b><small>Permite que clientes encontrem este item no catálogo.</small></span></label>
                <label class="option-card"><input type="hidden" name="allow_personalization" value="0"><input type="checkbox" name="allow_personalization" value="1" @checked(old('allow_personalization', $product->allow_personalization))><span><b>Aceita personalização</b><small>Exibe um campo para o cliente descrever o pedido.</small></span></label>
                <label class="option-card"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked(old('active', $product->exists ? $product->active : true))><span><b>Produto ativo</b><small>Deixe desmarcado apenas quando não quiser mais utilizá-lo.</small></span></label>
            </div>
        </section>

        <details class="product-advanced"><summary>Opções avançadas</summary><div class="form-grid"><label class="span-2">URL externa da imagem <small>(use somente se a foto estiver hospedada em outro site)</small><input type="text" name="image_url" value="{{ old('image_url', $product->image_url) }}" placeholder="https://..."></label><label>URL amigável da loja <small>(opcional)</small><input name="store_slug" value="{{ old('store_slug', $product->store_slug) }}" placeholder="placa-decorativa-personalizada"></label></div></details>
        <div class="form-actions"><a class="secondary" href="{{ route('produtos.index') }}">Cancelar</a><button class="primary" type="submit">{{ $product->exists ? 'Salvar alterações' : 'Salvar produto e continuar' }} →</button></div>
    </form>
@endsection
