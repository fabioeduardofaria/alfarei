<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisplaySize extends Model
{
    protected $fillable = ['display_configurator_id', 'label', 'width_cm', 'height_cm', 'laser_minutes_override', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function configurator(): BelongsTo
    {
        return $this->belongsTo(DisplayConfigurator::class, 'display_configurator_id');
    }
}
