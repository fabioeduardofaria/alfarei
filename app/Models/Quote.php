<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'parent_quote_id', 'customer_id', 'created_by', 'version', 'status', 'valid_until', 'notes', 'payment_terms', 'production_lead_days', 'delivery_lead_days', 'deposit_percent', 'approval_token', 'sent_at', 'approved_at', 'rejected_at', 'customer_response', 'discount', 'tax_percent', 'commission_percent', 'fee_percent', 'target_margin_percent', 'discount_percent', 'suggested_total', 'subtotal', 'cost_total', 'total'];

    protected function casts(): array
    {
        return ['valid_until' => 'date', 'deposit_percent' => 'decimal:2', 'sent_at' => 'datetime', 'approved_at' => 'datetime', 'rejected_at' => 'datetime', 'discount' => 'decimal:2', 'tax_percent' => 'decimal:2', 'commission_percent' => 'decimal:2', 'fee_percent' => 'decimal:2', 'target_margin_percent' => 'decimal:2', 'discount_percent' => 'decimal:2', 'suggested_total' => 'decimal:2', 'subtotal' => 'decimal:2', 'cost_total' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_quote_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_quote_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(QuoteAttachment::class);
    }
}
