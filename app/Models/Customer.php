<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'name', 'document', 'email', 'phone', 'city', 'state', 'customer_group', 'notes', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
