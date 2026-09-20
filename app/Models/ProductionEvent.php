<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionEvent extends Model
{
    protected $fillable = ['production_order_id', 'user_id', 'type', 'from_status', 'to_status', 'minutes', 'loss_cost', 'rework_cost', 'notes'];
    protected function casts(): array { return ['minutes' => 'integer', 'loss_cost' => 'decimal:2', 'rework_cost' => 'decimal:2']; }
    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
