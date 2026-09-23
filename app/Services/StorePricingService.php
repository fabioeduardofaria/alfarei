<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;

class StorePricingService
{
    public function offer(Product $product, ?Customer $customer): array
    {
        $group = $customer?->active ? $customer->customer_group : 'final';
        $prefix = match ($group) {
            'reseller' => 'reseller',
            'wholesale' => 'wholesale',
            default => null,
        };
        $tierPrice = $prefix ? $product->{$prefix.'_price'} : null;
        $minimum = $prefix ? $product->{$prefix.'_min_quantity'} : null;

        return [
            'base_price' => (float) $product->base_price,
            'tier_price' => $tierPrice !== null && $minimum !== null ? (float) $tierPrice : null,
            'min_quantity' => $tierPrice !== null && $minimum !== null ? (int) $minimum : null,
            'label' => $prefix === 'reseller' ? 'Revendedor' : ($prefix === 'wholesale' ? 'Atacado' : 'Cliente final'),
        ];
    }

    public function unitPrice(Product $product, ?Customer $customer, int $quantity): float
    {
        $offer = $this->offer($product, $customer);

        return $offer['tier_price'] !== null && $quantity >= $offer['min_quantity']
            ? $offer['tier_price']
            : $offer['base_price'];
    }
}
