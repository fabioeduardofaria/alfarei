<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceEntry extends Model
{
    protected $fillable = ['type', 'source_type', 'source_id', 'description', 'counterparty', 'due_date', 'amount', 'status', 'paid_at', 'payment_method', 'notes'];
    protected function casts(): array { return ['due_date' => 'date', 'amount' => 'decimal:2', 'paid_at' => 'datetime']; }
}
