<?php

namespace Database\Factories;

use App\Models\Machine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Machine>
 */
class MachineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Máquina '.fake()->word(),
            'code' => fake()->unique()->bothify('MAQ-###'),
            'type' => 'laser_co2',
            'hourly_cost' => 0,
            'status' => 'available',
            'active' => true,
        ];
    }
}
