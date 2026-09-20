<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = ['store_name', 'hero_title', 'hero_subtitle', 'whatsapp', 'support_email', 'pix_key', 'delivery_message', 'store_open'];
    protected function casts(): array { return ['store_open' => 'boolean']; }
}
