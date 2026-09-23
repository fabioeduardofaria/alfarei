@extends('store.layout')

@section('content')
<section class="display-store-page">
    <div class="display-store-visual" @if($configurator->product->image_url) style="background-image:url('{{ $configurator->product->image_url }}')" @endif>
        @unless($configurator->product->image_url)<span>SEU<br>DISPLAY<br>COMEÇA<br>AQUI.</span>@endunless
    </div>
    <div class="display-store-builder">
        <a class="back-store" href="{{ route('loja.index') }}#catalogo">← Voltar à loja</a>
        <p class="store-eyebrow">FEITO SOB MEDIDA PARA SUA IDEIA</p>
        <h1>{{ $configurator->product->name }}</h1>
        <p>{{ $configurator->product->description ?: 'MDF 3 mm com adesivo aplicado e corte de contorno a laser. Escolha o tamanho e veja o preço antes de comprar.' }}</p>
        <form id="displayBuilder" method="POST" action="{{ route('loja.display.add') }}" data-preview="{{ route('loja.display.preview') }}">
            @csrf
            <label>Tamanho
                <select name="size_id" id="displaySize" required>
                    @foreach($sizes as $size)<option value="{{ $size->id }}">{{ $size->label }} · {{ $size->width_cm }} × {{ $size->height_cm }} cm</option>@endforeach
                </select>
            </label>
            <label>Quantidade
                <input type="number" name="quantity" id="displayQuantity" min="1" max="100" value="1" required>
            </label>
            <label>Conte sua ideia <small>(opcional)</small>
                <textarea name="personalization" maxlength="500" rows="3" placeholder="Ex.: personagem, tema ou texto. A arte será conferida após o pedido.">{{ old('personalization') }}</textarea>
            </label>
            <input type="hidden" name="quote_token" id="displayQuoteToken">
            <div class="display-store-price" aria-live="polite"><span>Preço por unidade</span><strong id="displayUnitPrice">Calculando...</strong><span>Total para <b id="displayQuantityLabel">1</b> unidade(s)</span><strong id="displayTotal">—</strong></div>
            <p class="display-store-status" id="displayPriceStatus">Calculando o preço desta configuração...</p>
            @error('quote_token')<small class="account-error">{{ $message }}</small>@enderror
            <button class="store-primary" id="displayAddButton" type="submit" disabled>Adicionar ao carrinho →</button>
        </form>
        <small class="delivery-note">Preço fechado para o tamanho e quantidade escolhidos. A arte pode ser definida depois, respeitando as especificações do produto.</small>
    </div>
</section>
<script>
(() => {
    const form = document.getElementById('displayBuilder');
    const size = document.getElementById('displaySize');
    const quantity = document.getElementById('displayQuantity');
    const token = document.getElementById('displayQuoteToken');
    const button = document.getElementById('displayAddButton');
    const status = document.getElementById('displayPriceStatus');
    const currency = value => Number(value).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
    let requestId = 0;
    let timer;
    async function refreshPrice() {
        const current = ++requestId;
        token.value = '';
        button.disabled = true;
        status.textContent = 'Calculando o preço desta configuração...';
        const count = Number(quantity.value);
        if (!Number.isInteger(count) || count < 1 || count > 100) { status.textContent = 'Escolha de 1 a 100 unidades.'; return; }
        try {
            const response = await fetch(form.dataset.preview, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value}, body: JSON.stringify({size_id: Number(size.value), quantity: count})});
            if (!response.ok) throw new Error('Preço indisponível. Tente novamente.');
            const price = await response.json();
            if (current !== requestId) return;
            token.value = price.token;
            document.getElementById('displayUnitPrice').textContent = currency(price.unit_price);
            document.getElementById('displayTotal').textContent = currency(price.total);
            document.getElementById('displayQuantityLabel').textContent = count;
            status.textContent = 'Valor confirmado para esta configuração por 15 minutos.';
            button.disabled = false;
        } catch (error) { if (current === requestId) status.textContent = error.message; }
    }
    [size, quantity].forEach(field => field.addEventListener('input', () => { requestId++; token.value = ''; button.disabled = true; status.textContent = 'Atualizando o preço...'; clearTimeout(timer); timer = setTimeout(refreshPrice, 250); }));
    form.addEventListener('submit', event => { if (!token.value) event.preventDefault(); });
    refreshPrice();
})();
</script>
@endsection
