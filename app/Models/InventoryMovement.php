<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = ['material_id', 'production_order_id', 'type', 'status', 'quantity', 'unit_cost', 'notes'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:2']; }
    public function material(): BelongsTo { return $this->belongsTo(Material::class); }
    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
}
