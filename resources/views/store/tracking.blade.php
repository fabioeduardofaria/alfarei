@extends('store.layout')
@section('content')
<section class="tracking-page">
    <div class="tracking-intro"><p class="store-eyebrow">ACOMPANHE SUA ENCOMENDA</p><h1>Seu pedido, sem mistério.</h1><p>Informe o número do pedido e o mesmo e-mail usado no checkout para acompanhar cada etapa.</p></div>
    <form class="tracking-form" method="POST" action="{{ route('loja.tracking.search') }}">@csrf<label>Número do pedido<input name="number" value="{{ old('number', $order?->number) }}" placeholder="PED-2026-0001" required></label><label>E-mail usado no pedido<input type="email" name="email" value="{{ old('email') }}" required></label><button class="store-primary" type="submit">Acompanhar pedido →</button></form>
    @error('tracking')<p class="tracking-error">{{ $message }}</p>@enderror
    @if($order)
        <article class="tracking-result">
            <div><p class="store-eyebrow">{{ $order->number }}</p><h2>Olá, {{ $order->customer->name }}.</h2><span class="order-state {{ $order->status }}">{{ ['awaiting_deposit' => $order->delivery_method === 'digital' ? 'Aguardando pagamento' : 'Aguardando entrada', 'awaiting_art' => 'Aguardando sua aprovação de arte', 'ready_for_production' => 'Pronto para produção', 'in_production' => 'Em produção', 'quality' => 'Em conferência', 'ready' => 'Pronto para entrega', 'delivered' => $order->delivery_method === 'digital' ? 'Arquivo liberado' : 'Entregue', 'cancelled' => 'Cancelado'][$order->status] ?? $order->status }}</span></div>
            @if($order->delivery_method === 'digital')
                <ol class="tracking-steps"><li class="done"><i>✓</i><span>Pedido recebido</span></li><li class="{{ $order->deposit_paid_at ? 'done' : 'current' }}"><i>{{ $order->deposit_paid_at ? '✓' : '2' }}</i><span>Pagamento confirmado</span></li><li class="{{ $order->digitalDownloadReady() ? 'done' : '' }}"><i>{{ $order->digitalDownloadReady() ? '✓' : '3' }}</i><span>Arquivo na conta</span></li></ol>
                <div class="delivery-status"><b>Entrega digital</b><span>Entre em sua conta da loja para baixar o arquivo após a confirmação do pagamento.</span></div>
            @else
                <ol class="tracking-steps"><li class="done"><i>✓</i><span>Pedido recebido</span></li><li class="{{ $order->deposit_paid_at ? 'done' : ($order->status === 'awaiting_deposit' ? 'current' : '') }}"><i>{{ $order->deposit_paid_at ? '✓' : '2' }}</i><span>Entrada confirmada</span></li><li class="{{ in_array($order->status, ['in_production', 'quality', 'ready', 'delivered']) ? 'done' : (in_array($order->status, ['awaiting_art', 'ready_for_production']) ? 'current' : '') }}"><i>3</i><span>Produção</span></li><li class="{{ in_array($order->status, ['ready', 'delivered']) ? 'done' : '' }}"><i>4</i><span>Pronto / entregue</span></li></ol>
                <div class="delivery-status"><b>{{ $order->delivery_method === 'shipping' ? 'Entrega no endereço' : 'Retirada na Alfarei' }}</b>@if($order->delivery_method === 'shipping')<span>{{ $order->street }}, {{ $order->street_number }} · {{ $order->delivery_city }}/{{ $order->delivery_state }}</span>@endif @if($order->items->contains('type', 'virtual'))<small>Arquivos digitais do pedido: acesse Minha conta após a confirmação do pagamento integral.</small>@endif @if($order->tracking_code)<small>Código de rastreio: {{ $order->tracking_code }}</small>@endif</div>
            @endif
            <div class="tracking-items">@foreach($order->items as $item)<p><b>{{ $item->description }}</b><span>{{ number_format($item->quantity, 0) }} un.</span></p>@endforeach</div>
        </article>
    @endif
</section>
@endsection
