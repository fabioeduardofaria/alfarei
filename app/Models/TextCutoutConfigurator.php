<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TextCutoutConfigurator extends Model
{
    protected $fillable = [
        'product_id', 'laser_machine_id', 'max_width_cm', 'max_height_cm',
        'average_character_width_ratio', 'laser_minutes_per_character_10cm',
        'minimum_width_percent',
        'minimum_laser_minutes', 'material_loss_percent', 'white_finish_cost_per_m2',
        'painted_finish_cost_per_m2', 'base_cost_per_unit', 'packaging_cost_per_unit',
        'setup_cost_per_order', 'selling_fee_percent', 'target_margin_percent', 'enabled',
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

    public function laserMachine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'laser_machine_id');
    }
}
