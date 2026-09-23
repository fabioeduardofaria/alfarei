<?php

namespace App\Services;

use App\Models\Product;

class QuotePricingService
{
    /** @param array<int, array<string, mixed>> $items */
    public function calculate(array $items, float $discount = 0, float $discountPercent = 0, float $taxPercent = 0, float $commissionPercent = 0, float $feePercent = 0, float $targetMarginPercent = 20): array
    {
        $subtotal = 0.0;
        $costTotal = 0.0;
        $normalized = [];

        foreach ($items as $item) {
            $product = ! empty($item['product_id']) ? Product::with('materials', 'machineOperations')->find($item['product_id']) : null;
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $unitCost = $product && ($item['type'] ?? null) !== 'display' ? $product->calculatedProductionCost() : (float) $item['unit_cost'];
            $lineTotal = round($quantity * $unitPrice, 2);
            $lineCost = round($quantity * $unitCost, 2);
            $subtotal += $lineTotal;
            $costTotal += $lineCost;
            $normalized[] = [
                'product_id' => $product?->id,
                'description' => $item['description'],
                'type' => ($item['type'] ?? null) === 'display' ? 'display' : ($product?->type ?? 'custom'),
                'configuration_snapshot' => $item['configuration_snapshot'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit_cost' => $unitCost,
                'total' => $lineTotal,
                'total_cost' => $lineCost,
            ];
        }

        $percentDiscountAmount = round($subtotal * ($discountPercent / 100), 2);
        $total = max(0, round($subtotal - $discount - $percentDiscountAmount, 2));
        $commercialPercent = $taxPercent + $commissionPercent + $feePercent + $targetMarginPercent;
        $suggestedTotal = $commercialPercent >= 100
            ? 0
            : round($costTotal / (1 - ($commercialPercent / 100)), 2);

        return [
            'items' => $normalized,
            'subtotal' => round($subtotal, 2),
            'cost_total' => round($costTotal, 2),
            'total' => $total,
            'suggested_total' => $suggestedTotal,
        ];
    }
}
