<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    protected $fillable = ['number', 'order_id', 'machine_id', 'created_by', 'status', 'planned_minutes', 'actual_minutes', 'material_loss_cost', 'rework_cost', 'started_at', 'finished_at'];
    protected function casts(): array { return ['planned_minutes' => 'integer', 'actual_minutes' => 'integer', 'material_loss_cost' => 'decimal:2', 'rework_cost' => 'decimal:2', 'started_at' => 'datetime', 'finished_at' => 'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function machine(): BelongsTo { return $this->belongsTo(Machine::class); }
    public function events(): HasMany { return $this->hasMany(ProductionEvent::class)->latest(); }
}
