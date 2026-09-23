<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Material;
use App\Models\TextCutoutConfigurator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TextCutoutPricingService
{
    public function eligibleMaterials(): Collection
    {
        return Material::where('active', true)->orderBy('name')->get()->filter(fn (Material $material) => $this->validMaterial($material))->values();
    }

    public function missingRequirements(TextCutoutConfigurator $configurator): array
    {
        $configurator->loadMissing(['product', 'laserMachine']);
        $missing = [];
        if (! $configurator->product?->active || ! $configurator->product?->store_visible || ! $configurator->product?->made_to_order) {
            $missing[] = 'Selecione um produto sob encomenda, ativo e visível na loja.';
        }
        if (! $configurator->laserMachine?->active || $this->machineRate($configurator) <= 0) {
            $missing[] = 'Selecione uma máquina laser ativa com custo-hora positivo.';
        }
        if ((float) $configurator->max_width_cm <= 0 || (float) $configurator->max_height_cm <= 0) {
            $missing[] = 'Defina os limites máximos de largura e altura.';
        }
        if ((float) $configurator->laser_minutes_per_character_10cm <= 0 || (float) $configurator->minimum_laser_minutes <= 0) {
            $missing[] = 'Defina o tempo de corte por caractere e o tempo mínimo de laser.';
        }
        if ($configurator->white_finish_cost_per_m2 === null || $configurator->painted_finish_cost_per_m2 === null) {
            $missing[] = 'Defina os custos por m² dos acabamentos branco e pintado (zero é permitido).';
        }
        if ($this->eligibleMaterials()->isEmpty()) {
            $missing[] = 'Cadastre MDF ou acrílico ativo com espessura, dimensões da chapa e custo.';
        }
        if ((float) $configurator->selling_fee_percent + (float) $configurator->target_margin_percent >= 95) {
            $missing[] = 'A soma das taxas e da margem deve ser inferior a 95%.';
        }
        foreach (['reseller', 'wholesale'] as $group) {
            if ($configurator->{$group.'_margin_percent'} !== null && (float) $configurator->selling_fee_percent + (float) $configurator->{$group.'_margin_percent'} >= 95) {
                $missing[] = 'A soma das taxas e da margem do grupo '.$group.' deve ser inferior a 95%.';
            }
        }

        return $missing;
    }

    public function quote(TextCutoutConfigurator $configurator, Material $material, string $text, float $heightCm, ?float $widthCm, string $finish, ?string $color, int $quantity, ?Customer $customer = null): array
    {
        $text = preg_replace('/\s+/u', ' ', trim($text));
        $color = $color === null ? null : preg_replace('/\s+/u', ' ', trim($color));
        $letters = mb_strlen(preg_replace('/\s/u', '', $text));
        if ($this->missingRequirements($configurator) !== [] || ! $this->validMaterial($material) || $letters < 1 || mb_strlen($text) > 100 ||
            $quantity < 1 || $quantity > 100 || $heightCm < 1 || $heightCm > (float) $configurator->max_height_cm || round($heightCm, 1) !== $heightCm ||
            ! in_array($finish, ['natural', 'white', 'painted'], true) || ($finish === 'painted' && (! $color || mb_strlen($color) > 50))) {
            throw new InvalidArgumentException('A configuração do nome ou texto não é válida.');
        }
        $estimatedWidth = $widthCm === null;
        $autoWidth = round(mb_strlen($text) * $heightCm * (float) $configurator->average_character_width_ratio, 1);
        $widthCm ??= $autoWidth;
        if ($widthCm < 1 || $widthCm > (float) $configurator->max_width_cm || round($widthCm, 1) !== $widthCm ||
            $widthCm < $autoWidth * (float) $configurator->minimum_width_percent / 100 ||
            ! (($widthCm * 10 <= (float) $material->width_mm && $heightCm * 10 <= (float) $material->height_mm) ||
                ($heightCm * 10 <= (float) $material->width_mm && $widthCm * 10 <= (float) $material->height_mm))) {
            throw new InvalidArgumentException('As medidas não cabem nos limites ou na chapa selecionada.');
        }

        $areaM2 = $widthCm * $heightCm / 10000;
        $materialQuantity = (Str::lower(trim($material->unit)) === 'm²' || Str::lower(trim($material->unit)) === 'm2')
            ? $areaM2 : $areaM2 / (((float) $material->width_mm * (float) $material->height_mm) / 1000000);
        $materialQuantity *= 1 + (float) $configurator->material_loss_percent / 100;
        $materialCost = $materialQuantity * (float) $material->cost_per_unit;
        $laserMinutes = max((float) $configurator->minimum_laser_minutes, $letters * (float) $configurator->laser_minutes_per_character_10cm * $heightCm / 10);
        $laserCost = $this->machineRate($configurator) * $laserMinutes / 60;
        $finishCost = $areaM2 * match ($finish) {
            'white' => (float) $configurator->white_finish_cost_per_m2,
            'painted' => (float) $configurator->painted_finish_cost_per_m2,
            default => 0,
        };
        $base = (float) $configurator->base_cost_per_unit;
        $packaging = (float) $configurator->packaging_cost_per_unit;
        $setup = (float) $configurator->setup_cost_per_order / $quantity;
        $cost = $materialCost + $laserCost + $finishCost + $base + $packaging + $setup;
        $group = 'final';
        if ($customer?->hasResellerPricing() && $configurator->reseller_margin_percent !== null && $quantity >= $configurator->reseller_min_quantity) {
            $group = 'reseller';
        } elseif ($customer?->active && $customer->customer_group === 'wholesale' && $configurator->wholesale_margin_percent !== null && $quantity >= $configurator->wholesale_min_quantity) {
            $group = 'wholesale';
        }
        $margin = (float) ($group === 'final' ? $configurator->target_margin_percent : $configurator->{$group.'_margin_percent'});
        $unitPrice = ceil($cost / (1 - ((float) $configurator->selling_fee_percent + $margin) / 100) * 100) / 100;
        $finishLabel = match ($finish) {
            'white' => 'branco', 'painted' => 'pintado em '.$color, default => 'sem pintura'
        };

        return [
            'text' => $text, 'material_id' => $material->id, 'material_name' => $material->name,
            'material_category' => $material->category, 'thickness_mm' => (float) $material->thickness_mm,
            'finish' => $finish, 'finish_label' => $finishLabel, 'color' => $finish === 'painted' ? $color : null,
            'width_cm' => $widthCm, 'height_cm' => $heightCm, 'width_estimated' => $estimatedWidth,
            'size_label' => '"'.$text.'" · '.$material->category.' '.$material->thickness_mm.' mm · '.$finishLabel,
            'quantity' => $quantity, 'laser_minutes' => round($laserMinutes, 2),
            'unit_cost' => round($cost, 2), 'unit_price' => $unitPrice, 'total' => round($unitPrice * $quantity, 2),
            'customer_group' => $group, 'margin_percent' => $margin,
            'material_usage' => [['material_id' => $material->id, 'quantity_per_unit' => round($materialQuantity, 6)]],
            'breakdown' => array_map(fn ($value) => round($value, 2), compact('materialCost', 'laserCost', 'finishCost', 'base', 'packaging', 'setup')),
        ];
    }

    private function validMaterial(Material $material): bool
    {
        $category = Str::lower(Str::ascii($material->category));

        return $material->active && (str_contains($category, 'mdf') || str_contains($category, 'acril')) &&
            (float) $material->thickness_mm > 0 && (float) $material->width_mm > 0 && (float) $material->height_mm > 0 &&
            (float) $material->cost_per_unit > 0;
    }

    private function machineRate(TextCutoutConfigurator $configurator): float
    {
        if (! $configurator->laserMachine) {
            return 0;
        }

        return $configurator->laserMachine->calculatedHourlyCost() ?: (float) $configurator->laserMachine->hourly_cost;
    }
}
