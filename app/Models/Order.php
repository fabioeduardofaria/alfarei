<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['number', 'quote_id', 'customer_id', 'created_by', 'status', 'source', 'delivery_method', 'postal_code', 'street', 'street_number', 'complement', 'neighborhood', 'delivery_city', 'delivery_state', 'tracking_code', 'shipped_at', 'delivered_at', 'total', 'cost_total', 'deposit_amount', 'deposit_paid_at', 'art_approved_at', 'notes'];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'cost_total' => 'decimal:2', 'deposit_amount' => 'decimal:2', 'deposit_paid_at' => 'datetime', 'art_approved_at' => 'datetime', 'shipped_at' => 'datetime', 'delivered_at' => 'datetime'];
    }

    public function quote(): BelongsTo { return $this->belongsTo(Quote::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function notifications(): HasMany { return $this->hasMany(CustomerNotification::class); }
}
