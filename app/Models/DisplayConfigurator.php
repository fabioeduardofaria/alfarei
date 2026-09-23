<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisplayConfigurator extends Model
{
    protected $fillable = [
        'product_id', 'mdf_material_id', 'adhesive_material_id', 'laser_machine_id',
        'laser_minutes_per_unit', 'mdf_loss_percent', 'adhesive_loss_percent',
        'printing_cost_per_m2', 'application_cost_per_m2', 'base_cost_per_unit', 'assembly_cost_per_unit',
        'packaging_cost_per_unit', 'artwork_setup_cost_per_order', 'selling_fee_percent',
        'target_margin_percent', 'enabled',
        'reseller_margin_percent', 'reseller_min_quantity', 'wholesale_margin_percent', 'wholesale_min_quantity',
    ];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function mdfMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'mdf_material_id');
    }

    public function adhesiveMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'adhesive_material_id');
    }

    public function laserMachine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'laser_machine_id');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(DisplaySize::class);
    }
}
