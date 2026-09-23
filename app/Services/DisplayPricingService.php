<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DisplayConfigurator;
use App\Models\Material;
use InvalidArgumentException;

class DisplayPricingService
{
    public function missingRequirements(DisplayConfigurator $configurator): array
    {
        $configurator->loadMissing(['product', 'mdfMaterial', 'adhesiveMaterial', 'laserMachine']);
        $missing = [];
        if (! $configurator->product?->active || ! $configurator->product?->store_visible || ! $configurator->product?->made_to_order) {
            $missing[] = 'Selecione um produto sob encomenda, ativo e visível na loja.';
        }
        if (! $this->validMaterial($configurator->mdfMaterial) || abs((float) ($configurator->mdfMaterial?->thickness_mm ?? 0) - 3) > 0.2 || (float) $configurator->mdfMaterial?->width_mm <= 0 || (float) $configurator->mdfMaterial?->height_mm <= 0) {
            $missing[] = 'Selecione MDF 3 mm ativo, com custo e unidade de medida válidos.';
        }
        if (! $this->validMaterial($configurator->adhesiveMaterial)) {
            $missing[] = 'Selecione adesivo ativo, com custo e unidade de medida válidos.';
        }
        $machineRate = $configurator->laserMachine ? $this->machineRate($configurator) : 0;
        if (! $configurator->laserMachine?->active || $machineRate <= 0) {
            $missing[] = 'Selecione uma máquina laser ativa com custo-hora positivo.';
        }
        if ((float) $configurator->max_width_cm <= 0 || (float) $configurator->max_height_cm <= 0) {
            $missing[] = 'Defina a largura e a altura máximas permitidas.';
        } elseif ($configurator->mdfMaterial && (float) $configurator->mdfMaterial->width_mm > 0 && (float) $configurator->mdfMaterial->height_mm > 0) {
            $sheetWidth = (float) $configurator->mdfMaterial->width_mm;
            $sheetHeight = (float) $configurator->mdfMaterial->height_mm;
            $width = (float) $configurator->max_width_cm * 10;
            $height = (float) $configurator->max_height_cm * 10;
            if (! (($width <= $sheetWidth && $height <= $sheetHeight) || ($height <= $sheetWidth && $width <= $sheetHeight))) {
                $missing[] = 'As dimensões máximas não cabem na chapa de MDF selecionada.';
            }
        }
        if ((float) $configurator->selling_fee_percent + (float) $configurator->target_margin_percent >= 95) {
            $missing[] = 'A soma de taxas e margem deve ser inferior a 95%.';
        }
        foreach (['reseller', 'wholesale'] as $group) {
            if ($configurator->{$group.'_margin_percent'} !== null && (float) $configurator->selling_fee_percent + (float) $configurator->{$group.'_margin_percent'} >= 95) {
                $missing[] = 'A soma de taxas e margem do grupo '.$group.' deve ser inferior a 95%.';
            }
        }

        return $missing;
    }

    public function quote(DisplayConfigurator $configurator, float $widthCm, float $heightCm, int $quantity, ?Customer $customer = null): array
    {
        if ($quantity < 1 || $quantity > 100 || $this->missingRequirements($configurator) !== [] ||
            $widthCm < 1 || $heightCm < 1 || $widthCm > (float) $configurator->max_width_cm ||
            $heightCm > (float) $configurator->max_height_cm ||
            round($widthCm, 1) !== $widthCm || round($heightCm, 1) !== $heightCm) {
            throw new InvalidArgumentException('O display não está disponível nesta configuração.');
        }

        $areaM2 = ($widthCm * $heightCm) / 10000;
        $mdfQuantity = $this->materialAreaQuantity($configurator->mdfMaterial, $areaM2) * (1 + (float) $configurator->mdf_loss_percent / 100);
        $adhesiveQuantity = $this->materialAreaQuantity($configurator->adhesiveMaterial, $areaM2) * (1 + (float) $configurator->adhesive_loss_percent / 100);
        $mdf = $mdfQuantity * (float) $configurator->mdfMaterial->cost_per_unit;
        $adhesive = $adhesiveQuantity * (float) $configurator->adhesiveMaterial->cost_per_unit;
        $laserMinutes = (float) $configurator->laser_minutes_per_unit;
        $laser = $this->machineRate($configurator) * ($laserMinutes / 60);
        $printing = $areaM2 * (float) $configurator->printing_cost_per_m2;
        $application = $areaM2 * (float) $configurator->application_cost_per_m2;
        $base = (float) $configurator->base_cost_per_unit;
        $assembly = (float) $configurator->assembly_cost_per_unit;
        $packaging = (float) $configurator->packaging_cost_per_unit;
        $setupPerUnit = (float) $configurator->artwork_setup_cost_per_order / $quantity;
        $cost = $mdf + $adhesive + $laser + $printing + $application + $base + $assembly + $packaging + $setupPerUnit;
        $group = 'final';
        if ($customer?->hasResellerPricing() && $configurator->reseller_margin_percent !== null && $quantity >= $configurator->reseller_min_quantity) {
            $group = 'reseller';
        } elseif ($customer?->active && $customer->customer_group === 'wholesale' && $configurator->wholesale_margin_percent !== null && $quantity >= $configurator->wholesale_min_quantity) {
            $group = 'wholesale';
        }
        $margin = (float) ($group === 'final' ? $configurator->target_margin_percent : $configurator->{$group.'_margin_percent'});
        $denominator = 1 - ((float) $configurator->selling_fee_percent + $margin) / 100;
        $price = ceil(($cost / $denominator) * 100) / 100;

        return [
            'size_label' => $this->formatDimension($widthCm).' × '.$this->formatDimension($heightCm).' cm',
            'width_cm' => $widthCm, 'height_cm' => $heightCm, 'laser_minutes' => $laserMinutes,
            'quantity' => $quantity, 'unit_cost' => round($cost, 2),
            'unit_price' => $price, 'total' => round($price * $quantity, 2),
            'customer_group' => $group, 'margin_percent' => $margin,
            'material_usage' => [
                ['material_id' => $configurator->mdf_material_id, 'quantity_per_unit' => round($mdfQuantity, 6)],
                ['material_id' => $configurator->adhesive_material_id, 'quantity_per_unit' => round($adhesiveQuantity, 6)],
            ],
            'breakdown' => array_map(fn ($value) => round($value, 2), compact('mdf', 'adhesive', 'laser', 'printing', 'application', 'base', 'assembly', 'packaging', 'setupPerUnit')),
        ];
    }

    private function formatDimension(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, ',', ''), '0'), ',');
    }

    private function validMaterial(?Material $material): bool
    {
        return $material && $material->active && (float) $material->cost_per_unit > 0 && (
            in_array(mb_strtolower(trim($material->unit)), ['m2', 'm²'], true) ||
            ((float) $material->width_mm > 0 && (float) $material->height_mm > 0)
        );
    }

    private function materialAreaQuantity(Material $material, float $areaM2): float
    {
        if (in_array(mb_strtolower(trim($material->unit)), ['m2', 'm²'], true)) {
            return $areaM2;
        }

        $sheetAreaM2 = ((float) $material->width_mm * (float) $material->height_mm) / 1000000;

        return $areaM2 / $sheetAreaM2;
    }

    private function machineRate(DisplayConfigurator $configurator): float
    {
        $calculated = $configurator->laserMachine->calculatedHourlyCost();

        return $calculated > 0 ? $calculated : (float) $configurator->laserMachine->hourly_cost;
    }
}
