@extends('layouts.app', ['title' => $entry->description.' · Financeiro'])
@section('content')
<div class="heading">
    <div><p class="eyebrow">FINANCEIRO · {{ $entry->type === 'payable' ? 'CONTA A PAGAR' : 'CONTA A RECEBER' }}</p><h1>{{ $entry->description }}</h1><p class="muted">{{ $entry->counterparty ?: 'Sem contraparte' }} @if($entry->installment_count > 1) · Parcela {{ $entry->installment_number }}/{{ $entry->installment_count }} @endif</p></div>
    <a class="secondary" href="{{ route('financeiro.index') }}">← Todas as contas</a>
</div>
@if($errors->any())<div class="flash error"><b>Não foi possível registrar:</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="finance-detail-layout">
    <div>
        <section class="panel finance-detail-card">
            <div class="finance-detail-title"><h2>Dados da conta</h2><span class="status {{ $entry->status }}">{{ ['pending'=>'Em aberto','partial'=>'Parcial','paid'=>'Quitada','cancelled'=>'Cancelada'][$entry->status] ?? $entry->status }}</span></div>
            <div class="finance-facts">
                <div><small>Valor original</small><b>R$ {{ number_format($entry->amount, 2, ',', '.') }}</b></div>
                <div><small>Já {{ $entry->type === 'payable' ? 'pago' : 'recebido' }}</small><b>R$ {{ number_format($entry->status === 'paid' && (float)$entry->paid_amount === 0.0 ? $entry->amount : $entry->paid_amount, 2, ',', '.') }}</b></div>
                <div><small>Saldo em aberto</small><b>R$ {{ number_format(in_array($entry->status, ['paid','cancelled']) ? 0 : $entry->remaining_amount, 2, ',', '.') }}</b></div>
                <div><small>Vencimento</small><b>{{ $entry->due_date?->format('d/m/Y') ?? '—' }}</b></div>
                <div><small>Categoria</small><b>{{ $entry->category ?: 'Não informada' }}</b></div>
                <div><small>Origem</small><b>{{ ['manual'=>'Conta manual','purchase'=>'Pedido de compra','payment'=>'Entrada de pedido','order_balance'=>'Saldo de pedido'][$entry->source_type] ?? 'Integração' }}</b></div>
                @if($entry->document_number)<div><small>Documento</small><b>{{ $entry->document_number }}</b></div>@endif
            </div>
            @if($entry->notes)<p class="finance-entry-note">{{ $entry->notes }}</p>@endif
            @if($entry->source_type === 'manual' && $entry->status === 'pending' && (float)$entry->paid_amount === 0.0)
                <div class="finance-detail-actions"><a class="secondary" href="{{ route('financeiro.edit', $entry) }}">Editar conta</a><form method="POST" action="{{ route('financeiro.cancel', $entry) }}" onsubmit="return confirm('Cancelar esta conta? O registro permanecerá no histórico.');">@csrf<button class="secondary danger-text">Cancelar conta</button></form></div>
            @endif
        </section>
        @if($siblings->count() > 1)
            <section class="panel finance-detail-card"><h2>Parcelas desta conta</h2><div class="finance-siblings">@foreach($siblings as $sibling)<a class="{{ $sibling->id === $entry->id ? 'current' : '' }}" href="{{ route('financeiro.show', $sibling) }}"><span>{{ $sibling->installment_number }}/{{ $sibling->installment_count }} · {{ $sibling->due_date?->format('d/m/Y') }}</span><b>R$ {{ number_format($sibling->amount, 2, ',', '.') }}</b></a>@endforeach</div></section>
        @endif
        <section class="panel finance-detail-card"><h2>Histórico de {{ $entry->type === 'payable' ? 'pagamentos' : 'recebimentos' }}</h2>
            @forelse($entry->payments as $payment)
                <div class="finance-payment-row"><div><b>R$ {{ number_format($payment->amount, 2, ',', '.') }}</b><small>{{ $payment->paid_at->format('d/m/Y') }} · {{ ucfirst($payment->payment_method) }} · {{ $payment->creator?->name ?? 'Sistema' }}</small>@if($payment->notes)<small>{{ $payment->notes }}</small>@endif</div>@if($payment->receipt_path)<a class="edit" href="{{ route('financeiro.receipt', [$entry, $payment]) }}">Baixar comprovante →</a>@endif</div>
            @empty
                <p class="muted">Nenhuma baixa registrada nesta conta.</p>
            @endforelse
        </section>
    </div>
    <aside class="panel finance-settle-card">
        <p class="eyebrow">BAIXA FINANCEIRA</p>
        @if(in_array($entry->status, ['pending','partial']))
            <h2>{{ $entry->type === 'payable' ? 'Registrar pagamento' : 'Registrar recebimento' }}</h2>
            <p class="muted">Você pode informar um valor parcial. O restante continuará em aberto.</p>
            <form method="POST" action="{{ route('financeiro.settle', $entry) }}" enctype="multipart/form-data">@csrf
                <label>Valor (R$)<input type="number" name="amount" min="0.01" max="{{ $entry->remaining_amount }}" step="0.01" value="{{ old('amount', number_format($entry->remaining_amount, 2, '.', '')) }}" required></label>
                <label>Data<input type="date" name="paid_at" value="{{ old('paid_at', today()->toDateString()) }}" required></label>
                <label>Forma<select name="payment_method"><option value="pix">PIX</option><option value="transfer">Transferência</option><option value="boleto">Boleto</option><option value="card">Cartão</option><option value="cash">Dinheiro</option><option value="manual">Outro</option></select></label>
                <label>Comprovante (opcional)<input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png,.webp"><small>PDF ou imagem · até 10 MB · acesso restrito</small></label>
                <label>Observação (opcional)<textarea name="notes" rows="2">{{ old('notes') }}</textarea></label>
                <button class="primary full">Registrar baixa →</button>
            </form>
        @else
            <h2>{{ $entry->status === 'paid' ? 'Conta quitada' : 'Conta cancelada' }}</h2><p class="muted">Não há saldo disponível para nova baixa.</p>
        @endif
    </aside>
</div>
@endsection
