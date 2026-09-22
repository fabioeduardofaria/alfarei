<?php

namespace App\Models;

use Database\Factories\MachineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    /** @use HasFactory<MachineFactory> */
    use HasFactory;

    protected $fillable = ['name', 'code', 'type', 'hourly_cost', 'acquisition_value', 'residual_value', 'useful_life_months', 'planned_hours_month', 'power_kw', 'energy_rate', 'labor_cost_hour', 'maintenance_cost_hour', 'consumables_cost_hour', 'next_maintenance_at', 'status', 'active'];

    protected function casts(): array
    {
        return ['hourly_cost' => 'decimal:2', 'acquisition_value' => 'decimal:2', 'residual_value' => 'decimal:2', 'power_kw' => 'decimal:2', 'energy_rate' => 'decimal:2', 'labor_cost_hour' => 'decimal:2', 'maintenance_cost_hour' => 'decimal:2', 'consumables_cost_hour' => 'decimal:2', 'next_maintenance_at' => 'datetime', 'active' => 'boolean'];
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(MachineMaintenance::class);
    }

    public function monthlyDepreciation(): float
    {
        return $this->useful_life_months > 0 ? max(0, ((float) $this->acquisition_value - (float) $this->residual_value) / $this->useful_life_months) : 0;
    }

    public function calculatedHourlyCost(): float
    {
        return round($this->monthlyDepreciation() / max(1, (int) $this->planned_hours_month) + (float) $this->power_kw * (float) $this->energy_rate + (float) $this->labor_cost_hour + (float) $this->maintenance_cost_hour + (float) $this->consumables_cost_hour, 2);
    }
}
