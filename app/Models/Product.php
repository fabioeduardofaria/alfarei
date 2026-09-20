<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['sku', 'name', 'type', 'base_price', 'production_cost', 'made_to_order', 'active', 'description', 'store_visible', 'store_slug', 'image_url', 'allow_personalization'];

    protected function casts(): array
    {
        return ['base_price' => 'decimal:2', 'production_cost' => 'decimal:2', 'made_to_order' => 'boolean', 'active' => 'boolean', 'store_visible' => 'boolean', 'allow_personalization' => 'boolean'];
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'product_materials')->withPivot(['quantity', 'loss_percent'])->withTimestamps();
    }
}
