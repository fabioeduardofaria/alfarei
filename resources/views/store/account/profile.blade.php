@extends('store.layout')

@section('content')
<section class="store-account-page"><div class="store-account-intro"><p class="store-eyebrow">MINHA CONTA</p><h1>Olá, {{ $customer->name }}.</h1><p>{{ $customer->email }}<br>Perfil: <b>{{ $customer->hasResellerPricing() ? 'Revendedor aprovado' : (['reseller' => 'Revendedor em revisão', 'wholesale' => 'Atacado', 'final' => 'Cliente final'][$customer->customer_group] ?? 'Cliente') }}</b></p><p>As condições comerciais do seu grupo aparecem nos produtos elegíveis. O preço especial é aplicado automaticamente quando a quantidade mínima é alcançada.</p><a class="store-primary" href="{{ route('loja.reseller.show') }}">{{ $customer->hasResellerPricing() ? 'Ver meu perfil de revenda' : 'Solicitar perfil de revendedor' }} →</a><br><a href="{{ route('loja.index') }}#catalogo">← Voltar à loja</a></div><form class="store-account-card" method="POST" action="{{ route('loja.account.password') }}">@csrf @method('PUT')<h2>Alterar senha</h2><label>Senha atual<input type="password" name="current_password" required autocomplete="current-password"></label>@error('current_password')<small class="account-error">{{ $message }}</small>@enderror<label>Nova senha<input type="password" name="password" minlength="8" required autocomplete="new-password"></label>@error('password')<small class="account-error">{{ $message }}</small>@enderror<label>Confirme a nova senha<input type="password" name="password_confirmation" minlength="8" required autocomplete="new-password"></label><button class="store-primary" type="submit">Salvar nova senha →</button></form></section>
@if($digitalOrders->isNotEmpty())
<section class="store-section digital-library"><p class="store-eyebrow">ARQUIVOS COMPRADOS</p><h2>Minha biblioteca digital</h2><p>Os downloads são liberados somente após a confirmação do pagamento integral.</p>
    @foreach($digitalOrders as $order)
        <article class="digital-library-order"><h3>Pedido {{ $order->number }}</h3>
            @foreach($order->items as $item)
                <div><span><b>{{ $item->description }}</b><small>Versão {{ $item->configuration_snapshot['digital_version'] ?? '—' }} · {{ $item->configuration_snapshot['digital_file_name'] ?? 'Arquivo' }}</small><details class="digital-license"><summary>Termos de uso desta compra</summary><p>{{ $item->configuration_snapshot['digital_license_terms'] ?? 'Consulte a Alfarei sobre os termos de uso.' }}</p></details></span>
                    @if($order->digitalDownloadReady())<a class="store-primary" href="{{ route('loja.digital.download', $item) }}">Baixar arquivo ↓</a>@else<span class="digital-pending">Aguardando confirmação do pagamento</span>@endif
                </div>
            @endforeach
        </article>
    @endforeach
</section>
@endif
@endsection
