@extends('store.layout')
@section('content')
<section class="store-section checkout-page">
    <div><p class="store-eyebrow">FINALIZAR PEDIDO</p><h1>Seus dados para atendimento.</h1>
        @if($hasDigital)
            <p>Este pedido inclui arquivo digital. O pagamento é integral e o download será liberado na sua conta após a confirmação.</p>
        @else
            <p>Após enviar, a equipe confirma a entrada de {{ number_format($settings->deposit_percent, 0, ',', '.') }}% e inicia o acompanhamento do pedido.</p>
            @if($settings->production_days)<p>Prazo médio de produção: até {{ $settings->production_days }} dia(s).</p>@endif
        @endif
    </div>
    <form class="checkout-form" method="POST" action="{{ route('loja.place-order') }}">@csrf
        <label>Nome completo<input name="name" value="{{ old('name', $storeCustomer?->name) }}" required @if($storeCustomer) readonly @endif></label><label>E-mail @if($storeCustomer)<small>(da sua conta)</small>@endif<input type="email" name="email" value="{{ old('email', $storeCustomer?->email) }}" required @if($storeCustomer) readonly @endif>@error('email')<small class="account-error">{{ $message }} <a href="{{ route('loja.account.login') }}">Entrar na loja</a></small>@enderror</label><label>WhatsApp<input name="phone" value="{{ old('phone', $storeCustomer?->phone) }}" required></label>@unless($onlyDigital)<label>Cidade<input name="city" value="{{ old('city', $storeCustomer?->city) }}" required></label><label>UF<input name="state" maxlength="2" value="{{ old('state', $storeCustomer?->state) }}" required></label>@endunless
        @if($onlyDigital)<input type="hidden" name="delivery_method" value="digital"><p class="wide digital-checkout-note">Entrega digital: nenhuma retirada ou frete é necessário. O arquivo ficará em <b>Minha conta</b> após o pagamento.</p>@else<fieldset class="wide delivery-choice"><legend>Como você quer receber?</legend>@if($settings->pickup_enabled)<label><input type="radio" name="delivery_method" value="pickup" @checked(old('delivery_method', 'pickup') === 'pickup')> Retirar com a Alfarei</label>@endif @if($settings->shipping_enabled)<label><input type="radio" name="delivery_method" value="shipping" @checked(old('delivery_method') === 'shipping')> Receber no meu endereço</label>@endif <small>{{ $settings->shipping_message ?: 'Para entrega, o frete será confirmado pela equipe antes do pagamento.' }}</small></fieldset>@endif
        @unless($onlyDigital)<div class="wide delivery-address"><p>Endereço de entrega <small>(preencha somente se escolheu entrega)</small></p><div class="address-grid"><label>CEP<input name="postal_code" value="{{ old('postal_code') }}"></label><label>Rua / avenida<input name="street" value="{{ old('street') }}"></label><label>Número<input name="street_number" value="{{ old('street_number') }}"></label><label>Complemento<input name="complement" value="{{ old('complement') }}"></label><label>Bairro<input name="neighborhood" value="{{ old('neighborhood') }}"></label><label>Cidade<input name="delivery_city" value="{{ old('delivery_city') }}"></label><label>UF<input name="delivery_state" maxlength="2" value="{{ old('delivery_state') }}"></label></div></div>@endunless
        <label class="wide">Observações do pedido<textarea name="notes" placeholder="Entrega, prazo ou detalhes que devemos considerar.">{{ old('notes') }}</textarea></label>
        <div class="checkout-lines wide"><b>Resumo dos produtos</b>@foreach($lines as $line)<div><span>{{ $line->product->name }}<small>@if($line->configurationSnapshot){{ $line->configurationSnapshot['size_label'] }} · {{ $line->configurationSnapshot['width_cm'] }} × {{ $line->configurationSnapshot['height_cm'] }} cm<br>@endif @if($line->digitalSnapshot)Arquivo digital · versão {{ $line->digitalSnapshot['digital_version'] }}<br>@endif{{ $line->quantity }} × R$ {{ number_format($line->unitPrice, 2, ',', '.') }}</small></span><strong>R$ {{ number_format($line->total, 2, ',', '.') }}</strong></div>@endforeach</div>
        @if($hasDigital)
            <div class="wide digital-license-checkout"><b>Licença dos arquivos digitais</b>
                @foreach($lines as $line)
                    @if($line->digitalSnapshot)
                        <details><summary>{{ $line->product->name }} · versão {{ $line->digitalSnapshot['digital_version'] }}</summary><p>{{ $line->digitalSnapshot['digital_license_terms'] }}</p></details>
                    @endif
                @endforeach
                <label><input type="checkbox" name="accept_digital_license" value="1" @checked(old('accept_digital_license')) required> Li e aceito os termos de uso dos arquivos digitais deste pedido.</label>
                @error('accept_digital_license')<small class="account-error">{{ $message }}</small>@enderror
            </div>
        @endif
        <div class="checkout-total"><span>Total dos produtos</span><strong>R$ {{ number_format($lines->sum('total'), 2, ',', '.') }}</strong></div><button class="store-primary wide" type="submit">Enviar pedido →</button>
    </form>
</section>
@endsection
