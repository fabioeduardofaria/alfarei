<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceEntry extends Model
{
    protected $fillable = ['type', 'source_type', 'source_id', 'description', 'counterparty', 'due_date', 'amount', 'status', 'paid_at', 'payment_method', 'notes', 'category', 'document_number', 'installment_group', 'installment_number', 'installment_count', 'paid_amount', 'created_by', 'supplier_id', 'customer_id'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FinancePayment::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        if (in_array($this->status, ['paid', 'cancelled'], true)) {
            return 0;
        }

        return max(0, round((float) $this->amount - (float) $this->paid_amount, 2));
    }
}
