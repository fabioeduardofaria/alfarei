@extends('store.layout')

@section('content')
<section class="success-store">
    <span>✓</span><p class="store-eyebrow">PEDIDO RECEBIDO</p>
    <h1>Seu pedido {{ $number }} chegou até nós.</h1>
    @if($order->items()->where('type', 'virtual')->exists())
        <p>O pagamento integral de <b>R$ {{ number_format($order->deposit_amount, 2, ',', '.') }}</b> será confirmado pela equipe. Depois disso, os arquivos estarão disponíveis em <a href="{{ route('loja.account.profile') }}">Minha conta</a>. Não há download antes da confirmação.</p>
        @if($order->delivery_method !== 'digital')
            <p>Os produtos físicos do mesmo pedido seguirão para produção e {{ $order->delivery_method === 'shipping' ? 'envio' : 'retirada' }} após a confirmação.</p>
        @endif
    @else
        <p>Para iniciar a produção, confirme a entrada de <b>R$ {{ number_format($order->deposit_amount, 2, ',', '.') }}</b>. A equipe também confirmará {{ $order->delivery_method === 'shipping' ? 'o frete e o endereço de entrega' : 'os detalhes para retirada' }}.</p>
    @endif
    @if($settings->pix_enabled && $settings->pix_key)
        <div class="pix-box"><b>Pagamento por PIX</b><span>Chave: {{ $settings->pix_key }}</span><small>Envie o comprovante pelo atendimento, junto com o número {{ $number }}.</small></div>
    @endif
    @if($settings->whatsapp)
        <a class="store-primary" href="https://wa.me/{{ preg_replace('/\D/', '', $settings->whatsapp) }}" target="_blank">Enviar comprovante no WhatsApp →</a>
    @endif
    <a class="back-store" href="{{ route('loja.tracking') }}">Acompanhar pedido</a>
</section>
@endsection
