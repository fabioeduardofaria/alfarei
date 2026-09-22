@extends('layouts.app', ['title' => 'Financeiro · Alfarei CNC'])
@section('content')
<div class="heading">
    <div><p class="eyebrow">GESTÃO FINANCEIRA</p><h1>Contas a pagar e receber</h1><p class="muted">Contas avulsas e lançamentos de compras e pedidos no mesmo lugar.</p></div>
    <a class="primary" href="{{ route('financeiro.create') }}">＋ Nova conta</a>
</div>
<section class="stats finance-stats">
    <article><span>A RECEBER</span><b>R$ {{ number_format($pendingReceivable, 2, ',', '.') }}</b><small>Saldo em aberto</small></article>
    <article><span>A PAGAR</span><b>R$ {{ number_format($pendingPayable, 2, ',', '.') }}</b><small>Saldo em aberto</small></article>
    <article><span>SALDO PREVISTO</span><b class="{{ $pendingReceivable - $pendingPayable < 0 ? 'negative' : '' }}">R$ {{ number_format($pendingReceivable - $pendingPayable, 2, ',', '.') }}</b><small>Entradas menos saídas</small></article>
    <article><span>CAIXA REALIZADO</span><b>R$ {{ number_format($paidIn - $paidOut, 2, ',', '.') }}</b><small>{{ $overdueCount }} {{ $overdueCount === 1 ? 'conta vencida' : 'contas vencidas' }}</small></article>
</section>
<form class="panel finance-filters" method="GET">
    <label>Buscar<input name="busca" value="{{ request('busca') }}" placeholder="Descrição, pessoa ou documento"></label>
    <label>Natureza<select name="tipo"><option value="">Todas</option><option value="payable" @selected(request('tipo') === 'payable')>A pagar</option><option value="receivable" @selected(request('tipo') === 'receivable')>A receber</option></select></label>
    <label>Situação<select name="status"><option value="">Todas</option><option value="pending" @selected(request('status') === 'pending')>Em aberto</option><option value="partial" @selected(request('status') === 'partial')>Parcial</option><option value="paid" @selected(request('status') === 'paid')>Quitada</option><option value="cancelled" @selected(request('status') === 'cancelled')>Cancelada</option></select></label>
    <label>Categoria<input name="categoria" value="{{ request('categoria') }}" placeholder="Todas"></label>
    <label>De<input type="date" name="de" value="{{ request('de') }}"></label>
    <label>Até<input type="date" name="ate" value="{{ request('ate') }}"></label>
    <button class="secondary">Filtrar</button>
</form>
<article class="panel table-panel finance-table">
    <table><thead><tr><th>CONTA</th><th>VENCIMENTO</th><th>NATUREZA</th><th>VALOR</th><th>SALDO</th><th>SITUAÇÃO</th><th></th></tr></thead><tbody>
    @forelse($entries as $entry)
        <tr>
            <td><b>{{ $entry->description }}</b><small>{{ $entry->counterparty ?: 'Sem contraparte' }} @if($entry->installment_count > 1) · {{ $entry->installment_number }}/{{ $entry->installment_count }} @endif</small><small>{{ $entry->source_type === 'manual' ? 'Lançamento manual' : 'Gerado automaticamente' }}{{ $entry->category ? ' · '.$entry->category : '' }}</small></td>
            <td>{{ $entry->due_date?->format('d/m/Y') ?? '—' }}@if(in_array($entry->status, ['pending','partial']) && $entry->due_date?->isBefore(today()))<small class="danger-text">Em atraso</small>@endif</td>
            <td><span class="tag {{ $entry->type }}">{{ $entry->type === 'payable' ? 'A pagar' : 'A receber' }}</span></td>
            <td>R$ {{ number_format($entry->amount, 2, ',', '.') }}</td>
            <td><b>R$ {{ number_format($entry->remaining_amount, 2, ',', '.') }}</b></td>
            <td><span class="status {{ $entry->status }}">{{ ['pending'=>'Em aberto','partial'=>'Parcial','paid'=>'Quitada','cancelled'=>'Cancelada'][$entry->status] ?? $entry->status }}</span></td>
            <td><a class="edit" href="{{ route('financeiro.show', $entry) }}">Abrir →</a></td>
        </tr>
    @empty
        <tr><td colspan="7" class="empty">Nenhuma conta encontrada. Use “Nova conta” para cadastrar uma despesa ou receita.</td></tr>
    @endforelse
    </tbody></table>
    <div class="pagination">{{ $entries->links() }}</div>
</article>
@endsection
