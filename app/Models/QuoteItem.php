<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = ['quote_id', 'product_id', 'description', 'type', 'quantity', 'unit_price', 'unit_cost', 'total', 'total_cost', 'configuration_snapshot'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'unit_cost' => 'decimal:2', 'total' => 'decimal:2', 'total_cost' => 'decimal:2', 'configuration_snapshot' => 'array'];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
