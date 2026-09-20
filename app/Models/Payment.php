<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['order_id', 'type', 'status', 'amount', 'due_date', 'paid_at', 'method', 'notes'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'due_date' => 'date', 'paid_at' => 'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
