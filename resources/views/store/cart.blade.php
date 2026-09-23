@extends('store.layout')

@section('content')
<section class="store-section cart-page">
    <p class="store-eyebrow">SEU PEDIDO</p><h1>Carrinho</h1>
    @if($lines->isNotEmpty())
        <div class="cart-layout">
            <article class="cart-items">
                @foreach($lines as $line)
                    <div>
                        <span class="cart-thumb">{{ strtoupper(substr($line->product->name, 0, 1)) }}</span>
                        <p><b>{{ $line->product->name }}</b><small>{{ $line->quantity }} × R$ {{ number_format($line->unitPrice, 2, ',', '.') }}
                            @if($line->personalization)<br>{{ $line->personalization }}@endif
                        </small>@if($line->unitPrice < (float) $line->product->base_price)<small class="tier-applied">Preço {{ $storeCustomer?->customer_group === 'reseller' ? 'revendedor' : 'atacado' }} ativado · economia de R$ {{ number_format(((float) $line->product->base_price - $line->unitPrice) * $line->quantity, 2, ',', '.') }}</small>@endif</p>
                        <strong>R$ {{ number_format($line->total, 2, ',', '.') }}</strong>
                        <form method="POST" action="{{ route('loja.remove', $line->key) }}">@csrf<button>Remover</button></form>
                    </div>
                @endforeach
            </article>
            <aside class="cart-summary"><span>Resumo do pedido</span><p><b>Total</b><strong>R$ {{ number_format($lines->sum('total'), 2, ',', '.') }}</strong></p><small>A entrada de {{ number_format($settings->deposit_percent, 0, ',', '.') }}% é solicitada após a confirmação comercial.</small><a class="store-primary" href="{{ route('loja.checkout') }}">Ir para checkout →</a></aside>
        </div>
    @else
        <div class="empty-store"><p>Seu carrinho está vazio.</p><a class="store-primary" href="{{ route('loja.index') }}">Explorar produtos</a></div>
    @endif
</section>
@endsection
