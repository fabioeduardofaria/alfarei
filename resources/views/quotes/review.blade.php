@extends('layouts.app', ['title' => $quote->number.' · Alfarei CNC'])
@section('content')
@php($statuses = ['sent' => 'Publicada', 'negotiation' => 'Em negociação', 'approved' => 'Aprovado', 'rejected' => 'Recusado', 'expired' => 'Expirado', 'cancelled' => 'Cancelado', 'superseded' => 'Substituído por nova versão'])
<div class="heading">
    <div>
        <p class="eyebrow">COMERCIAL · {{ $quote->number }} · V{{ $quote->version }}</p>
        <h1>{{ $quote->status === 'sent' ? 'Proposta publicada' : 'Proposta · '.($statuses[$quote->status] ?? $quote->status) }}</h1>
        <p class="muted">Esta versão foi publicada e preservada. Compartilhe o link com o cliente; para alterar valores ou arquivos, crie uma nova versão.</p>
    </div>
    <a class="secondary" href="{{ route('orcamentos.index') }}">← Orçamentos</a>
</div>
<div class="quote-review-workspace">
    <div>
        <section class="panel quote-review-card">
            <div class="quote-review-head">
                <div><p class="eyebrow">VERSÃO PUBLICADA</p><h2>{{ $quote->customer->name }}</h2></div>
                <span class="status {{ $quote->status }}">{{ $statuses[$quote->status] ?? $quote->status }}</span>
            </div>
            <div class="quote-review-facts">
                <div><small>Versão</small><b>V{{ $quote->version }}</b></div>
                <div><small>Publicada em</small><b>{{ $quote->sent_at?->format('d/m/Y H:i') ?? '—' }}</b></div>
                <div><small>Validade</small><b>{{ $quote->valid_until?->format('d/m/Y') ?? '—' }}</b></div>
                <div><small>Responsável</small><b>{{ $quote->creator?->name ?? '—' }}</b></div>
                <div><small>Produção</small><b>{{ $quote->production_lead_days !== null ? $quote->production_lead_days.' dias' : 'A combinar' }}</b></div>
                <div><small>Entrega/despacho</small><b>{{ $quote->delivery_lead_days !== null ? $quote->delivery_lead_days.' dias' : 'A combinar' }}</b></div>
            </div>
            <h3>Itens desta versão</h3>
            <div class="quote-review-items">
                @foreach($quote->items as $item)
                    <div><span>{{ $item->description }}<small>{{ number_format($item->quantity, 3, ',', '.') }} × R$ {{ number_format($item->unit_price, 2, ',', '.') }}</small></span><b>R$ {{ number_format($item->total, 2, ',', '.') }}</b></div>
                @endforeach
            </div>
            <div class="quote-review-total"><span>Total da proposta</span><strong>R$ {{ number_format($quote->total, 2, ',', '.') }}</strong></div>
            <div class="quote-review-terms">
                <div><h3>Condições de pagamento</h3><p>{{ $quote->payment_terms ?: 'A combinar' }}</p><small>Entrada: {{ number_format($quote->deposit_percent, 0, ',', '.') }}%</small></div>
                <div><h3>Observações</h3><p>{{ $quote->notes ?: 'Nenhuma observação.' }}</p></div>
            </div>
            @if($quote->customer_response)
                <div class="quote-review-response"><b>Resposta do cliente</b><p>{{ $quote->customer_response }}</p></div>
            @endif
        </section>
        @include('quotes.attachments', ['readonly' => true])
        @if($quote->approval_token)
            <section class="panel proposal-link-panel">
                <div><p class="eyebrow">LINK DA VERSÃO V{{ $quote->version }}</p><h2>Proposta para o cliente</h2><p>O conteúdo deste link corresponde à versão preservada acima.</p></div>
                <div class="proposal-link-actions">
                    <input readonly aria-label="Link público da proposta" value="{{ route('proposta.public', $quote->approval_token) }}">
                    <a class="secondary" target="_blank" href="{{ route('proposta.public', $quote->approval_token) }}">Abrir proposta ↗</a>
                    <a class="primary" target="_blank" href="{{ route('proposta.pdf', $quote->approval_token) }}">Baixar PDF</a>
                </div>
            </section>
        @endif
    </div>
    <aside class="panel quote-review-actions">
        <p class="eyebrow">PRÓXIMO PASSO</p>
        @if($quote->status === 'approved')
            <h2>Cliente aprovou a proposta</h2>
            <p>A versão aprovada pode ser convertida em pedido.</p>
            <form method="POST" action="{{ route('orcamentos.convert', $quote) }}">@csrf<button class="primary full">Converter em pedido →</button></form>
        @else
            <h2>{{ $quote->status === 'sent' ? 'Aguardando resposta' : 'Versão preservada' }}</h2>
            <p>Alterações de preço, prazo ou arquivos exigem uma nova versão.</p>
        @endif
        <form method="POST" action="{{ route('orcamentos.revision', $quote) }}">@csrf<button class="secondary full">Criar nova versão</button></form>
        <div class="quote-review-versions">
            <h3>Histórico de versões</h3>
            @foreach($versions as $version)
                <a class="{{ $version->id === $quote->id ? 'current' : '' }}" href="{{ route('orcamentos.edit', $version) }}">V{{ $version->version }} · {{ $version->number }} <span>→</span></a>
            @endforeach
        </div>
    </aside>
</div>
@endsection
