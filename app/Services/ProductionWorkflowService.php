<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\Order;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\DB;

class ProductionWorkflowService
{
    public const STEPS = ['awaiting', 'preparation', 'cutting', 'finishing', 'quality', 'ready'];

    public function __construct(
        private readonly InventoryReservationService $inventory,
        private readonly CustomerNotificationService $notifications,
    ) {}

    public function createFromOrder(Order $order, int $userId): ProductionOrder
    {
        return DB::transaction(function () use ($order, $userId) {
            $existing = ProductionOrder::where('order_id', $order->id)->first();
            if ($existing) {
                return $existing;
            }
            $order->loadMissing('items');
            $plannedLaserMinutes = $order->items->sum(fn ($item) => $item->configuration_snapshot ? (float) ($item->configuration_snapshot['laser_minutes'] ?? 0) * (float) $item->quantity : 0);
            $machine = Machine::where('active', true)->where('status', 'available')->orderBy('id')->first();
            $production = ProductionOrder::create([
                'number' => sprintf('OP-%s-%04d', now()->format('Y'), ProductionOrder::count() + 1),
                'order_id' => $order->id, 'machine_id' => $machine?->id, 'created_by' => $userId,
                'status' => 'awaiting', 'planned_minutes' => max(30, $order->items->count() * 45, (int) ceil($plannedLaserMinutes)),
            ]);
            $production->events()->create(['user_id' => $userId, 'type' => 'created', 'to_status' => 'awaiting', 'notes' => 'OP criada a partir do pedido '.$order->number]);
            $this->inventory->reserveFor($production);
            $order->update(['status' => 'in_production']);
            $this->notifications->queue($order->fresh(), 'production_started');

            return $production;
        });
    }

    public function advance(ProductionOrder $production, int $userId, int $minutes = 0): ProductionOrder
    {
        $from = $production->status;
        $index = array_search($from, self::STEPS, true);
        if ($index === false || $index === count(self::STEPS) - 1) {
            return $production;
        }
        $to = self::STEPS[$index + 1];
        if ($to === 'cutting') {
            $this->inventory->consumeFor($production);
        }
        $production->update([
            'status' => $to,
            'actual_minutes' => $production->actual_minutes + $minutes,
            'started_at' => $production->started_at ?? now(),
            'finished_at' => $to === 'ready' ? now() : null,
        ]);
        $production->events()->create(['user_id' => $userId, 'type' => 'status', 'from_status' => $from, 'to_status' => $to, 'minutes' => $minutes]);
        if ($to === 'ready') {
            $production->order()->update(['status' => 'quality']);
            $this->notifications->queue($production->order()->firstOrFail(), 'ready');
        }

        return $production->fresh();
    }
}
