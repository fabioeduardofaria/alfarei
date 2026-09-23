@extends('store.layout')

@section('content')
<section class="display-store-page">
    <div class="display-store-visual" @if($configurator->product->image_url) style="background-image:url('{{ $configurator->product->image_url }}')" @endif>
        @unless($configurator->product->image_url)<span>SEU NOME<br>GANHA<br>FORMA.</span>@endunless
    </div>
    <div class="display-store-builder">
        <a class="back-store" href="{{ route('loja.index') }}#catalogo">← Voltar à loja</a>
        <p class="store-eyebrow">NOME OU TEXTO PERSONALIZADO</p>
        <h1>{{ $configurator->product->name }}</h1>
        <p>Escreva o texto, escolha MDF ou acrílico e o acabamento. Veja o preço antes de comprar.</p>
        <form id="textBuilder" method="POST" action="{{ route('loja.text.add') }}" data-preview="{{ route('loja.text.preview') }}">@csrf
            <label>Nome ou texto<input name="text" id="textContent" maxlength="100" placeholder="Ex.: Aurora" required></label>
            <label>Material<select name="material_id" id="textMaterial" required><option value="">Escolha o material</option>@foreach($materials as $material)<option value="{{ $material->id }}">{{ $material->name }} · {{ $material->thickness_mm }} mm</option>@endforeach</select></label>
            <label>Acabamento<select name="finish" id="textFinish" required><option value="natural">Sem pintura (cor da chapa)</option><option value="white">Branco pintado</option><option value="painted">Pintado na cor escolhida</option></select></label>
            <label id="textColorField" hidden>Cor da pintura<input name="color" id="textColor" maxlength="50" placeholder="Ex.: rosa claro"></label>
            <div class="display-store-dimensions">
                <label>Altura final (cm)<input type="number" name="height_cm" id="textHeight" min="1" max="{{ $configurator->max_height_cm }}" step="0.1" inputmode="decimal" placeholder="Até {{ number_format($configurator->max_height_cm, 1, ',', '.') }}" required></label>
                <label>Largura final (cm) <small>opcional</small><input type="number" name="width_cm" id="textWidth" min="1" max="{{ $configurator->max_width_cm }}" step="0.1" inputmode="decimal" placeholder="Estimada se ficar vazio"></label>
            </div>
            <small class="display-store-limit">Até {{ number_format($configurator->max_width_cm, 1, ',', '.') }} × {{ number_format($configurator->max_height_cm, 1, ',', '.') }} cm. Sem largura informada, o sistema faz uma estimativa pela quantidade de caracteres.</small>
            <label>Quantidade<input type="number" name="quantity" id="textQuantity" min="1" max="100" value="1" required></label>
            <input type="hidden" name="quote_token" id="textQuoteToken">
            <div class="display-store-price" aria-live="polite"><span>Preço por unidade</span><strong id="textUnitPrice">—</strong><span>Total</span><strong id="textTotal">—</strong></div>
            <p class="display-store-status" id="textPriceStatus">Informe o texto, material e altura para calcular.</p>
            @error('quote_token')<small class="account-error">{{ $message }}</small>@enderror
            <button class="store-primary" id="textAddButton" type="submit" disabled>Adicionar ao carrinho →</button>
        </form>
        <small class="delivery-note">O preço considera as medidas cotadas e um tempo de corte estimado. A arte final deverá respeitar essas medidas; divergências exigem uma nova cotação. Para chapa branca pronta, escolha o material branco e “Sem pintura”.</small>
    </div>
</section>
<script>
(() => {
    const form = document.getElementById('textBuilder');
    const fields = ['textContent','textMaterial','textFinish','textColor','textHeight','textWidth','textQuantity'].map(id => document.getElementById(id));
    const [content, material, finish, color, height, width, quantity] = fields;
    const token = document.getElementById('textQuoteToken');
    const button = document.getElementById('textAddButton');
    const status = document.getElementById('textPriceStatus');
    const money = value => Number(value).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
    let requestId = 0;
    let timer;
    async function refresh() {
        const current = ++requestId;
        token.value = ''; button.disabled = true;
        document.getElementById('textUnitPrice').textContent = '—';
        document.getElementById('textTotal').textContent = '—';
        if (!content.value.trim() || !material.value || !height.value || !height.checkValidity() || (width.value && !width.checkValidity()) || !quantity.checkValidity() || (finish.value === 'painted' && !color.value.trim())) {
            status.textContent = 'Preencha texto, material, acabamento, altura e quantidade válidos.';
            return;
        }
        status.textContent = 'Calculando preço estimado...';
        try {
            const response = await fetch(form.dataset.preview, {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':form.querySelector('[name="_token"]').value},body:JSON.stringify({text:content.value,material_id:Number(material.value),finish:finish.value,color:color.value||null,height_cm:Number(height.value),width_cm:width.value?Number(width.value):null,quantity:Number(quantity.value)})});
            if (!response.ok) throw new Error('Não foi possível calcular. Confira as medidas e tente novamente.');
            const result = await response.json();
            if (current !== requestId) return;
            token.value = result.token;
            document.getElementById('textUnitPrice').textContent = money(result.unit_price);
            document.getElementById('textTotal').textContent = money(result.total);
            status.textContent = (result.width_estimated ? 'Largura estimada: ' : 'Largura informada: ') + Number(result.width_cm).toLocaleString('pt-BR') + ' cm · preço válido por 15 minutos.';
            button.disabled = false;
        } catch (error) { if (current === requestId) status.textContent = error.message; }
    }
    fields.forEach(field => field.addEventListener('input', () => {
        requestId++; token.value = ''; button.disabled = true;
        document.getElementById('textUnitPrice').textContent = '—';
        document.getElementById('textTotal').textContent = '—';
        document.getElementById('textColorField').hidden = finish.value !== 'painted';
        color.required = finish.value === 'painted';
        clearTimeout(timer); timer = setTimeout(refresh, 300);
    }));
    form.addEventListener('submit', event => { if (!token.value) event.preventDefault(); });
})()
</script>
@endsection
