@extends('layouts.app', ['title' => 'Notificações · Alfarei CNC'])

@section('content')
<div class="heading">
    <div>
        <p class="eyebrow">RELACIONAMENTO</p>
        <h1>Notificações ao cliente</h1>
        <p class="muted">Mensagens prontas para WhatsApp em cada etapa importante do pedido.</p>
    </div>
    <span class="stage">CENTRAL DE AVISOS</span>
</div>

<article class="panel notification-panel">
    <div class="notification-guide">
        <strong>Como usar</strong>
        <span>Abra o WhatsApp, envie a mensagem e depois marque o aviso como enviado.</span>
    </div>

    <div class="notification-list">
        @forelse($notifications as $notification)
            @php
                $order = $notification->order;
                $customer = $order?->customer;
                $phone = $notification->recipient;
                $whatsAppUrl = $phone ? 'https://wa.me/'.$phone.'?text='.rawurlencode($notification->message) : null;
            @endphp
            <article class="notification-item {{ $notification->status === 'sent' ? 'is-sent' : '' }}">
                <div class="notification-meta">
                    <span class="notification-event">{{ $eventLabels[$notification->event] ?? $notification->event }}</span>
                    <b>{{ $customer?->name ?? 'Cliente não encontrado' }}</b>
                    <small>{{ $order?->number ?? 'Pedido removido' }} · criado em {{ $notification->created_at->format('d/m/Y H:i') }}</small>
                </div>
                <div class="notification-message">{!! nl2br(e($notification->message)) !!}</div>
                <div class="notification-actions">
                    @if($notification->status === 'sent')
                        <span class="status on">ENVIADA {{ $notification->sent_at?->format('d/m H:i') }}</span>
                        <form method="POST" action="{{ route('notificacoes.reopen', $notification) }}">
                            @csrf
                            <button class="secondary" type="submit">Reabrir</button>
                        </form>
                    @else
                        @if($whatsAppUrl)
                            <a class="whatsapp-button" href="{{ $whatsAppUrl }}" target="_blank" rel="noopener">Abrir WhatsApp ↗</a>
                        @else
                            <span class="missing-phone">Cliente sem telefone</span>
                        @endif
                        <form method="POST" action="{{ route('notificacoes.mark-sent', $notification) }}">
                            @csrf
                            <button class="primary" type="submit">Marcar enviada</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <p class="empty">Ainda não há notificações geradas. Elas surgirão ao receber pedidos, iniciar a produção, finalizar ou despachar.</p>
        @endforelse
    </div>

    <div class="pagination">{{ $notifications->links() }}</div>
</article>
@endsection
