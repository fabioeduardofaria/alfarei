<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['number', 'quote_id', 'customer_id', 'created_by', 'status', 'source', 'total', 'cost_total', 'deposit_amount', 'deposit_paid_at', 'art_approved_at', 'notes'];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'cost_total' => 'decimal:2', 'deposit_amount' => 'decimal:2', 'deposit_paid_at' => 'datetime', 'art_approved_at' => 'datetime'];
    }

    public function quote(): BelongsTo { return $this->belongsTo(Quote::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
}
