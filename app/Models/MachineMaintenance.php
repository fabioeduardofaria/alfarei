<?php

namespace App\Models;

use Database\Factories\MachineMaintenanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineMaintenance extends Model
{
    /** @use HasFactory<MachineMaintenanceFactory> */
    use HasFactory;

    protected $fillable = ['machine_id', 'type', 'status', 'scheduled_for', 'completed_at', 'cost', 'machine_hours', 'description'];

    protected function casts(): array
    {
        return ['scheduled_for' => 'date', 'completed_at' => 'datetime', 'cost' => 'decimal:2', 'machine_hours' => 'integer'];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
