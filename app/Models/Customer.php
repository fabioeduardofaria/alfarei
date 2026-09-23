<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['type', 'name', 'document', 'email', 'phone', 'city', 'state', 'customer_group', 'notes', 'active', 'password', 'reseller_status', 'reseller_approved_at', 'reseller_reviewed_at'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'password' => 'hashed', 'reseller_approved_at' => 'datetime', 'reseller_reviewed_at' => 'datetime'];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function resellerApplications(): HasMany
    {
        return $this->hasMany(ResellerApplication::class);
    }

    public function latestResellerApplication(): HasOne
    {
        return $this->hasOne(ResellerApplication::class)->latestOfMany();
    }

    public function resellerEvents(): HasMany
    {
        return $this->hasMany(ResellerEvent::class);
    }

    public function hasResellerPricing(): bool
    {
        return $this->active && $this->customer_group === 'reseller' && in_array($this->reseller_status, [null, 'approved'], true);
    }
}
