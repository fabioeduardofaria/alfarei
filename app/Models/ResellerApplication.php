<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResellerApplication extends Model
{
    protected $fillable = ['customer_id', 'status', 'business_name', 'sales_channel', 'sales_url', 'description', 'decision_note', 'decided_by', 'decided_at'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ResellerEvent::class, 'application_id');
    }
}
