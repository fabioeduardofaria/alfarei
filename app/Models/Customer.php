<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['type', 'name', 'document', 'email', 'phone', 'city', 'state', 'customer_group', 'notes', 'active', 'password'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'password' => 'hashed'];
    }
}
