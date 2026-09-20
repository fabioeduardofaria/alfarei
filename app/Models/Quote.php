<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'customer_id', 'created_by', 'version', 'status', 'valid_until', 'notes', 'discount', 'subtotal', 'cost_total', 'total'];

    protected function casts(): array
    {
        return ['valid_until' => 'date', 'discount' => 'decimal:2', 'subtotal' => 'decimal:2', 'cost_total' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany { return $this->hasMany(QuoteItem::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
