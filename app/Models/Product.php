<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['sku', 'name', 'type', 'base_price', 'production_cost', 'made_to_order', 'active', 'description', 'store_visible', 'store_slug', 'image_url', 'allow_personalization', 'store_occasions', 'store_featured', 'reseller_price', 'reseller_min_quantity', 'wholesale_price', 'wholesale_min_quantity'];

    protected function casts(): array
    {
        return ['base_price' => 'decimal:2', 'production_cost' => 'decimal:2', 'made_to_order' => 'boolean', 'active' => 'boolean', 'store_visible' => 'boolean', 'allow_personalization' => 'boolean', 'store_occasions' => 'array', 'store_featured' => 'boolean', 'reseller_price' => 'decimal:2', 'wholesale_price' => 'decimal:2', 'reseller_min_quantity' => 'integer', 'wholesale_min_quantity' => 'integer'];
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'product_materials')->withPivot(['quantity', 'loss_percent'])->withTimestamps();
    }

    public function machineOperations(): BelongsToMany
    {
        return $this->belongsToMany(Machine::class, 'product_machine_operations')
            ->withPivot(['operation_name', 'minutes_per_unit', 'setup_minutes'])
            ->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function calculatedProductionCost(): float
    {
        $this->loadMissing('materials', 'machineOperations');

        if ($this->materials->isEmpty() && $this->machineOperations->isEmpty()) {
            return (float) $this->production_cost;
        }

        $materialCost = $this->materials->sum(function (Material $material): float {
            $quantity = (float) $material->pivot->quantity;
            $loss = 1 + ((float) $material->pivot->loss_percent / 100);

            return (float) $material->cost_per_unit * $quantity * $loss;
        });
        $machineCost = $this->machineOperations->sum(function (Machine $machine): float {
            $minutes = (float) $machine->pivot->minutes_per_unit + (float) $machine->pivot->setup_minutes;

            return $machine->calculatedHourlyCost() * ($minutes / 60);
        });

        return round($materialCost + $machineCost, 2);
    }

    public function refreshProductionCost(): void
    {
        $this->updateQuietly(['production_cost' => $this->calculatedProductionCost()]);
    }
}
