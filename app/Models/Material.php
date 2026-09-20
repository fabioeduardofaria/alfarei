<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'category', 'unit', 'thickness_mm', 'width_mm', 'height_mm', 'cost_per_unit', 'stock_quantity', 'reserved_quantity', 'minimum_stock', 'location', 'active'];

    protected function casts(): array
    {
        return ['thickness_mm' => 'decimal:2', 'width_mm' => 'decimal:2', 'height_mm' => 'decimal:2', 'cost_per_unit' => 'decimal:2', 'stock_quantity' => 'decimal:3', 'reserved_quantity' => 'decimal:3', 'minimum_stock' => 'decimal:3', 'active' => 'boolean'];
    }

    public function movements(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(InventoryMovement::class); }
}
