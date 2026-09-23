<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;

class StorePricingService
{
    public function effectiveGroup(?Customer $customer): string
    {
        if (! $customer?->active) {
            return 'final';
        }
        if ($customer->customer_group === 'reseller') {
            return $customer->hasResellerPricing() ? 'reseller' : 'final';
        }

        return $customer->customer_group === 'wholesale' ? 'wholesale' : 'final';
    }

    public function offer(Product $product, ?Customer $customer): array
    {
        $group = $this->effectiveGroup($customer);
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
