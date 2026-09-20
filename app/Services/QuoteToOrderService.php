<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Quote;
use App\Models\FinanceEntry;
use Illuminate\Support\Facades\DB;

class QuoteToOrderService
{
    public function convert(Quote $quote, int $userId): Order
    {
        return DB::transaction(function () use ($quote, $userId) {
            $quote->loadMissing('items.product', 'customer');
            $existing = Order::where('quote_id', $quote->id)->first();
            if ($existing) return $existing;
            $number = sprintf('PED-%s-%04d', now()->format('Y'), Order::count() + 1);
            $deposit = round(((float) $quote->total) * .5, 2);
            $order = Order::create([
                'number' => $number, 'quote_id' => $quote->id, 'customer_id' => $quote->customer_id, 'created_by' => $userId,
                'status' => 'awaiting_deposit', 'source' => 'quote', 'total' => $quote->total, 'cost_total' => $quote->cost_total,
                'deposit_amount' => $deposit, 'notes' => $quote->notes,
            ]);
            $order->items()->createMany($quote->items->map(fn ($item) => [
                'product_id' => $item->product_id, 'description' => $item->description, 'type' => $item->type,
                'quantity' => $item->quantity, 'unit_price' => $item->unit_price, 'unit_cost' => $item->unit_cost,
                'total' => $item->total, 'total_cost' => $item->total_cost, 'made_to_order' => $item->product?->made_to_order ?? true,
            ])->all());
            $payment = $order->payments()->create(['type' => 'deposit', 'status' => 'pending', 'amount' => $deposit, 'due_date' => now()->toDateString()]);
            FinanceEntry::create(['type' => 'receivable', 'source_type' => 'payment', 'source_id' => $payment->id, 'description' => 'Entrada do pedido '.$order->number, 'counterparty' => $quote->customer->name, 'due_date' => now()->toDateString(), 'amount' => $deposit]);
            FinanceEntry::create(['type' => 'receivable', 'source_type' => 'order_balance', 'source_id' => $order->id, 'description' => 'Saldo do pedido '.$order->number, 'counterparty' => $quote->customer->name, 'due_date' => now()->addDays(15)->toDateString(), 'amount' => (float) $quote->total - $deposit]);
            $quote->update(['status' => 'approved']);
            return $order;
        });
    }
}
