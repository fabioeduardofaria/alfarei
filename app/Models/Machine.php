<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    protected $fillable = ['name', 'code', 'type', 'hourly_cost', 'status', 'active'];
    protected function casts(): array { return ['hourly_cost' => 'decimal:2', 'active' => 'boolean']; }
    public function productionOrders(): HasMany { return $this->hasMany(ProductionOrder::class); }
}
