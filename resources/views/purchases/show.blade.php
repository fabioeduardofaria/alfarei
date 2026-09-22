@extends('layouts.app', ['title' => $purchase->number.' · Compras'])
@section('content')
<div class="heading"><div><p class="eyebrow">SUPRIMENTOS · PEDIDO DE COMPRA</p><h1>{{ $purchase->number }}</h1><p class="muted">{{ $purchase->supplier->name }} · previsão {{ $purchase->expected_at?->format('d/m/Y') ?? 'não informada' }}</p></div><a class="secondary" href="{{ route('compras.index') }}">← Voltar</a></div>
<div class="purchase-layout">
    <article class="panel table-panel"><table><thead><tr><th>MATERIAL</th><th>QUANTIDADE</th><th>CUSTO UNIT.</th><th>TOTAL</th></tr></thead><tbody>@foreach($purchase->items as $item)<tr><td><b>{{ $item->material->name }}</b><small>{{ $item->material->code }}</small></td><td>{{ number_format($item->quantity, 3, ',', '.') }} {{ $item->material->unit }}</td><td>R$ {{ number_format($item->unit_cost, 2, ',', '.') }}</td><td><b>R$ {{ number_format($item->total, 2, ',', '.') }}</b></td></tr>@endforeach</tbody></table></article>
    <aside class="panel order-actions">
        <span class="status {{ $purchase->status }}">{{ ['draft' => 'Rascunho', 'ordered' => 'Confirmado', 'received' => 'Recebido'][$purchase->status] }}</span>
        @if($purchase->status === 'draft')
            <h2>Confirmar compra</h2><p>A confirmação criará a conta a pagar. O estoque será atualizado somente quando os materiais forem recebidos.</p>
            <form method="POST" action="{{ route('compras.confirm', $purchase) }}">@csrf<button class="primary" type="submit">Confirmar compra →</button></form>
        @elseif($purchase->status === 'ordered')
            <h2>Recebimento</h2><p>Ao confirmar, as quantidades serão inseridas no estoque e o custo unitário será atualizado.</p>
            <form method="POST" action="{{ route('compras.receive', $purchase) }}">@csrf<button class="primary" type="submit">Confirmar recebimento →</button></form>
        @else
            <h2>Materiais recebidos</h2><p class="received-note">Recebido em {{ $purchase->received_at?->format('d/m/Y H:i') }}.</p>
        @endif
        <strong>R$ {{ number_format($purchase->total, 2, ',', '.') }}</strong>
    </aside>
</div>
@if($payable && auth()->user()->canAccess('finance'))<article class="panel note-panel"><h2>Conta a pagar</h2><p>Vencimento: {{ $payable->due_date?->format('d/m/Y') ?? 'não informado' }} · Saldo: R$ {{ number_format($payable->remaining_amount, 2, ',', '.') }}</p><a class="edit" href="{{ route('financeiro.show', $payable) }}">Abrir no Financeiro →</a></article>@endif
@if($purchase->notes)<article class="panel note-panel"><h2>Observações</h2><p>{{ $purchase->notes }}</p></article>@endif
@endsection
