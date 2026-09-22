<?php

namespace Database\Factories;

use App\Models\Machine;
use App\Models\MachineMaintenance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MachineMaintenance>
 */
class MachineMaintenanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'machine_id' => Machine::factory(),
            'type' => 'preventive',
            'status' => 'scheduled',
            'scheduled_for' => now()->addMonth(),
            'cost' => 0,
            'machine_hours' => 0,
        ];
    }
}
