<?php

namespace App\Services;

use App\Models\Product;

class QuotePricingService
{
    /** @param array<int, array<string, mixed>> $items */
    public function calculate(array $items, float $discount = 0): array
    {
        $subtotal = 0.0;
        $costTotal = 0.0;
        $normalized = [];

        foreach ($items as $item) {
            $product = ! empty($item['product_id']) ? Product::find($item['product_id']) : null;
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $unitCost = $product ? (float) $product->production_cost : (float) $item['unit_cost'];
            $lineTotal = round($quantity * $unitPrice, 2);
            $lineCost = round($quantity * $unitCost, 2);
            $subtotal += $lineTotal;
            $costTotal += $lineCost;
            $normalized[] = [
                'product_id' => $product?->id,
                'description' => $item['description'],
                'type' => $product?->type ?? 'custom',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit_cost' => $unitCost,
                'total' => $lineTotal,
                'total_cost' => $lineCost,
            ];
        }

        return ['items' => $normalized, 'subtotal' => round($subtotal, 2), 'cost_total' => round($costTotal, 2), 'total' => max(0, round($subtotal - $discount, 2))];
    }
}
