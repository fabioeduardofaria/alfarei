<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Material;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryReservationService
{
    public function reserveFor(ProductionOrder $production): void
    {
        $production->loadMissing('order.items.product.materials');
        foreach ($production->order->items as $item) {
            if ($item->configuration_snapshot && isset($item->configuration_snapshot['material_usage'])) {
                foreach ($item->configuration_snapshot['material_usage'] as $usage) {
                    $quantity = round((float) $item->quantity * (float) $usage['quantity_per_unit'], 3);
                    $this->reserveMaterial($production, (int) $usage['material_id'], $quantity);
                }

                continue;
            }
            if (! $item->product) {
                continue;
            }
            foreach ($item->product->materials as $material) {
                $quantity = round((float) $item->quantity * (float) $material->pivot->quantity * (1 + ((float) $material->pivot->loss_percent / 100)), 3);
                $this->reserveMaterial($production, $material->id, $quantity);
            }
        }
    }

    private function reserveMaterial(ProductionOrder $production, int $materialId, float $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }
        $locked = Material::lockForUpdate()->findOrFail($materialId);
        $available = (float) $locked->stock_quantity - (float) $locked->reserved_quantity;
        if ($available < $quantity) {
            throw ValidationException::withMessages(['stock' => "Estoque insuficiente para {$locked->name}. Disponível: {$available} {$locked->unit}; necessário: {$quantity} {$locked->unit}."]);
        }
        $locked->increment('reserved_quantity', $quantity);
        InventoryMovement::create(['material_id' => $locked->id, 'production_order_id' => $production->id, 'type' => 'reserve', 'status' => 'active', 'quantity' => $quantity, 'unit_cost' => $locked->cost_per_unit, 'notes' => 'Reserva automática para '.$production->number]);
    }

    public function consumeFor(ProductionOrder $production): void
    {
        DB::transaction(function () use ($production) {
            $reservations = InventoryMovement::where('production_order_id', $production->id)->where('type', 'reserve')->where('status', 'active')->get();
            foreach ($reservations as $reservation) {
                $material = Material::lockForUpdate()->findOrFail($reservation->material_id);
                $material->decrement('stock_quantity', $reservation->quantity);
                $material->decrement('reserved_quantity', $reservation->quantity);
                $reservation->update(['status' => 'consumed']);
                InventoryMovement::create(['material_id' => $material->id, 'production_order_id' => $production->id, 'type' => 'consume', 'status' => 'posted', 'quantity' => $reservation->quantity, 'unit_cost' => $reservation->unit_cost, 'notes' => 'Consumo na etapa de corte/usinagem']);
            }
        });
    }
}
