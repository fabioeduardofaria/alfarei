<?php

namespace App\Services;

use App\Models\CustomerNotification;
use App\Models\Order;
use App\Models\StoreSetting;

class CustomerNotificationService
{
    public const EVENTS = [
        'order_created' => 'Novo pedido e PIX pendente',
        'production_started' => 'Produção iniciada',
        'ready' => 'Pedido pronto',
        'shipped' => 'Despacho ou retirada',
        'digital_ready' => 'Arquivo digital liberado',
    ];

    public function queue(Order $order, string $event): CustomerNotification
    {
        $order->loadMissing('customer');
        $settings = StoreSetting::firstOrCreate([], [
            'hero_subtitle' => 'Produtos personalizados com acabamento profissional, produzidos pela Alfarei CNC.',
        ]);
        $customer = $order->customer;
        $baseUrl = rtrim((string) config('app.url'), '/');
        $trackingUrl = $baseUrl.'/loja/rastrear';
        $name = $customer?->name ?: 'cliente';
        $pixKey = $settings->pix_key ?: 'consulte a Alfarei para receber a chave PIX';

        $message = match ($event) {
            'order_created' => $order->items()->where('type', 'virtual')->exists()
                ? "Olá, {$name}! Recebemos o pedido {$order->number}.\n\nO pagamento integral de R$ ".number_format((float) $order->deposit_amount, 2, ',', '.')." está pendente via PIX.\nChave PIX: {$pixKey}\n\nOs arquivos digitais serão liberados em sua conta após a confirmação. Acompanhe por aqui: {$trackingUrl}"
                : "Olá, {$name}! Recebemos o pedido {$order->number}.\n\nA entrada para iniciar a produção é de R$ ".number_format((float) $order->deposit_amount, 2, ',', '.').". O pagamento está pendente via PIX.\nChave PIX: {$pixKey}\n\nApós a confirmação, daremos sequência ao seu pedido. Acompanhe por aqui: {$trackingUrl}",
            'digital_ready' => "Olá, {$name}! O pagamento do pedido {$order->number} foi confirmado. Seu arquivo digital já pode ser baixado com segurança em sua conta da loja: {$baseUrl}/loja/minha-conta",
            'production_started' => "Olá, {$name}! A produção do seu pedido {$order->number} foi iniciada. Nossa equipe já está trabalhando nele.\n\nAcompanhe o andamento: {$trackingUrl}",
            'ready' => $order->delivery_method === 'pickup'
                ? "Olá, {$name}! Seu pedido {$order->number} está pronto para retirada na Alfarei. Entre em contato para combinarmos o melhor horário.\n\nAcompanhe: {$trackingUrl}"
                : "Olá, {$name}! Seu pedido {$order->number} está pronto e aguardando despacho. Em breve enviaremos o código de rastreio.\n\nAcompanhe: {$trackingUrl}",
            'shipped' => $order->delivery_method === 'pickup'
                ? "Olá, {$name}! A retirada do pedido {$order->number} foi registrada. Obrigado por escolher a Alfarei!"
                : "Olá, {$name}! Seu pedido {$order->number} foi despachado.".($order->tracking_code ? "\n\nCódigo de rastreio: {$order->tracking_code}" : '')."\nAcompanhe a situação do pedido: {$trackingUrl}",
            default => throw new \InvalidArgumentException('Evento de notificação inválido.'),
        };

        return CustomerNotification::firstOrCreate(
            ['order_id' => $order->id, 'event' => $event],
            [
                'channel' => 'whatsapp',
                'recipient' => preg_replace('/\D+/', '', (string) $customer?->phone) ?: null,
                'message' => $message,
                'status' => 'pending',
                'sent_at' => null,
            ],
        );
    }

    public function whatsAppUrl(CustomerNotification $notification): ?string
    {
        if (! $notification->recipient) {
            return null;
        }

        return 'https://wa.me/'.$notification->recipient.'?text='.rawurlencode($notification->message);
    }
}
