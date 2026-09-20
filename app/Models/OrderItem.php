<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'description', 'type', 'quantity', 'unit_price', 'unit_cost', 'total', 'total_cost', 'made_to_order'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'unit_cost' => 'decimal:2', 'total' => 'decimal:2', 'total_cost' => 'decimal:2', 'made_to_order' => 'boolean']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
