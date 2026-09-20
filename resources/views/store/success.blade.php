@extends('store.layout')
@section('content')
<section class="success-store"><span>✓</span><p class="store-eyebrow">PEDIDO RECEBIDO</p><h1>Seu pedido {{ $number }} chegou até nós.</h1><p>Vamos confirmar os detalhes e orientar o pagamento de entrada para iniciar a produção.</p>@if($settings->whatsapp)<a class="store-primary" href="https://wa.me/{{ preg_replace('/\D/', '', $settings->whatsapp) }}" target="_blank">Falar no WhatsApp →</a>@endif<a class="back-store" href="{{ route('loja.index') }}">Voltar à loja</a></section>
@endsection
