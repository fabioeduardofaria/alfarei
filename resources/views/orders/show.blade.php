@extends('layouts.app', ['title' => $order->number.' · Alfarei CNC'])
@section('content')
@php($onlyDigital = $order->delivery_method === 'digital')
<div class="heading"><div><p class="eyebrow">PEDIDOS · {{ $order->number }}</p><h1>{{ $onlyDigital ? 'Pedido digital' : 'Pedido' }} {{ $order->number }}</h1><p class="muted">{{ $order->customer->name }} · {{ $order->created_at->format('d/m/Y H:i') }}</p></div><a class="secondary" href="{{ route('pedidos.index') }}">← Pedidos</a></div>
<section class="order-layout">
    <div>
        <article class="panel order-journey">
            <div class="panel-head"><div><h2>{{ $onlyDigital ? 'Liberação do arquivo' : 'Jornada do pedido' }}</h2><p>{{ $onlyDigital ? 'O download só é liberado após confirmar o pagamento integral.' : 'A produção só é liberada depois da entrada e da arte, quando aplicável.' }}</p></div><span class="status {{ $order->status }}">{{ ['awaiting_deposit' => $onlyDigital ? 'Aguardando pagamento' : 'Aguardando entrada', 'awaiting_art' => 'Aguardando arte', 'ready_for_production' => 'Liberado para produção', 'in_production' => 'Em produção', 'quality' => 'Conferência', 'ready' => 'Pronto', 'delivered' => $onlyDigital ? 'Arquivo liberado' : 'Entregue', 'cancelled' => 'Cancelado'][$order->status] ?? $order->status }}</span></div>
            @if($onlyDigital)
                <ol class="order-steps"><li class="done"><i>✓</i><span>Pedido criado<small>{{ $order->created_at->format('d/m H:i') }}</small></span></li><li class="{{ $order->deposit_paid_at ? 'done' : 'current' }}"><i>{{ $order->deposit_paid_at ? '✓' : '2' }}</i><span>Pagamento integral<small>{{ $order->deposit_paid_at ? 'Confirmado' : 'Pendente' }}</small></span></li><li class="{{ $order->digitalDownloadReady() ? 'done' : '' }}"><i>{{ $order->digitalDownloadReady() ? '✓' : '3' }}</i><span>Download na conta<small>{{ $order->digitalDownloadReady() ? 'Liberado' : 'Aguardando' }}</small></span></li></ol>
            @else
                <ol class="order-steps"><li class="done"><i>✓</i><span>Pedido criado<small>{{ $order->created_at->format('d/m H:i') }}</small></span></li><li class="{{ $order->deposit_paid_at ? 'done' : ($order->status === 'awaiting_deposit' ? 'current' : '') }}"><i>{{ $order->deposit_paid_at ? '✓' : '2' }}</i><span>Entrada<small>{{ $order->deposit_paid_at ? 'Confirmada' : 'Pendente' }}</small></span></li><li class="{{ $order->art_approved_at ? 'done' : ($order->status === 'awaiting_art' ? 'current' : '') }}"><i>{{ $order->art_approved_at ? '✓' : '3' }}</i><span>Aprovação de arte<small>{{ $order->art_approved_at ? 'Aprovada' : 'Quando aplicável' }}</small></span></li><li class="{{ $order->status === 'ready_for_production' ? 'current' : '' }}"><i>4</i><span>Produção<small>Aguardando OP</small></span></li><li><i>5</i><span>Conferência<small>Aguardando</small></span></li><li><i>6</i><span>Entrega<small>Aguardando</small></span></li></ol>
            @endif
        </article>
        <article class="panel table-panel order-items">
            <div class="panel-head"><div><h2>Itens do pedido</h2><p>Valores e custos preservados no fechamento do pedido.</p></div></div>
            <table><thead><tr><th>ITEM</th><th>QTD.</th><th>CUSTO</th><th>TOTAL</th></tr></thead><tbody>
                @foreach($order->items as $item)
                    <tr><td><b>{{ $item->description }}</b><small>{{ $item->type === 'virtual' ? 'Arquivo digital' : ($item->made_to_order ? 'Sob encomenda' : 'Estoque') }}</small>
                        @if($item->type === 'virtual')<small>{{ $item->configuration_snapshot['digital_file_name'] ?? 'Arquivo' }} · versão {{ $item->configuration_snapshot['digital_version'] ?? '—' }}</small>
                        @elseif($item->configuration_snapshot && isset($item->configuration_snapshot['laser_minutes']))<small>Configurado · laser: {{ number_format($item->configuration_snapshot['laser_minutes'], 2, ',', '.') }} min/un.</small>@endif
                    </td><td>{{ number_format($item->quantity, 3, ',', '.') }}</td><td>R$ {{ number_format($item->total_cost, 2, ',', '.') }}</td><td><b>R$ {{ number_format($item->total, 2, ',', '.') }}</b></td></tr>
                @endforeach
            </tbody></table>
        </article>
    </div>
    <aside class="panel order-actions">
        <p class="eyebrow">PRÓXIMA AÇÃO</p>
        @if($order->status === 'awaiting_deposit')
            <h2>{{ $onlyDigital ? 'Confirmar pagamento integral' : 'Confirmar entrada' }}</h2><p>{{ $onlyDigital ? 'Libere o arquivo somente após verificar o recebimento do valor total.' : 'Registre o recebimento da entrada para avançar o pedido.' }}</p><strong>R$ {{ number_format($order->deposit_amount, 2, ',', '.') }}</strong><form method="POST" action="{{ route('pedidos.confirm-deposit', $order) }}">@csrf<button class="primary full">Confirmar recebimento →</button></form>
        @elseif($order->status === 'awaiting_art')
            <h2>Aprovar arte</h2><p>Confirme o aceite da prévia pelo cliente antes de iniciar a produção.</p><form method="POST" action="{{ route('pedidos.approve-art', $order) }}">@csrf<button class="primary full">Registrar aprovação →</button></form>
        @elseif($order->status === 'ready_for_production')
            <h2>Pronto para produção</h2><p>Entrada e arte confirmadas. A próxima etapa criará a Ordem de Produção.</p><span class="status ready_for_production">Liberado</span>
        @elseif($onlyDigital && $order->digitalDownloadReady())
            <h2>Arquivo liberado</h2><p>O cliente pode baixar o arquivo em Minha conta. Uma notificação de liberação foi preparada.</p>
        @else
            <h2>Pedido em andamento</h2><p>Este pedido está seguindo o fluxo operacional.</p>
        @endif
        <div class="order-values"><span>Total do pedido <b>R$ {{ number_format($order->total, 2, ',', '.') }}</b></span><span>Custo estimado <b>R$ {{ number_format($order->cost_total, 2, ',', '.') }}</b></span><span>Margem <b>{{ $order->total > 0 ? number_format((($order->total - $order->cost_total) / $order->total) * 100, 1, ',', '.') : '0,0' }}%</b></span></div>
    </aside>
</section>
@endsection
