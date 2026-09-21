@extends('store.layout')
@section('content')
<section class="success-store"><span>✓</span><p class="store-eyebrow">PEDIDO RECEBIDO</p><h1>Seu pedido {{ $number }} chegou até nós.</h1><p>Para iniciar a produção, confirme a entrada de <b>R$ {{ number_format($order->deposit_amount, 2, ',', '.') }}</b>. A equipe também confirmará {{ $order->delivery_method === 'shipping' ? 'o frete e o endereço de entrega' : 'os detalhes para retirada' }}.</p>@if($settings->pix_key)<div class="pix-box"><b>Pagamento por PIX</b><span>Chave: {{ $settings->pix_key }}</span><small>Envie o comprovante pelo atendimento, junto com o número {{ $number }}.</small></div>@endif
@if($settings->whatsapp)<a class="store-primary" href="https://wa.me/{{ preg_replace('/\D/', '', $settings->whatsapp) }}" target="_blank">Enviar comprovante no WhatsApp →</a>@endif<a class="back-store" href="{{ route('loja.tracking') }}">Acompanhar pedido</a></section>
@endsection
