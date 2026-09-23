<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerEvent extends Model
{
    protected $fillable = ['customer_id', 'application_id', 'actor_user_id', 'actor_customer_id', 'event', 'note'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ResellerApplication::class, 'application_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
