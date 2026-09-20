<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = ['name', 'document', 'email', 'phone', 'contact_name', 'notes', 'active'];
    protected function casts(): array { return ['active' => 'boolean']; }
    public function purchaseOrders(): HasMany { return $this->hasMany(PurchaseOrder::class); }
}
