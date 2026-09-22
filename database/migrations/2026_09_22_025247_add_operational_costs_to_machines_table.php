<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table): void {
            $table->decimal('acquisition_value', 14, 2)->default(0)->after('type');
            $table->decimal('residual_value', 14, 2)->default(0)->after('acquisition_value');
            $table->unsignedInteger('useful_life_months')->default(60)->after('residual_value');
            $table->unsignedInteger('planned_hours_month')->default(160)->after('useful_life_months');
            $table->decimal('power_kw', 10, 2)->default(0)->after('planned_hours_month');
            $table->decimal('energy_rate', 10, 2)->default(0)->after('power_kw');
            $table->decimal('labor_cost_hour', 12, 2)->default(0)->after('energy_rate');
            $table->decimal('maintenance_cost_hour', 12, 2)->default(0)->after('labor_cost_hour');
            $table->decimal('consumables_cost_hour', 12, 2)->default(0)->after('maintenance_cost_hour');
            $table->timestamp('next_maintenance_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table): void {
            $table->dropColumn(['acquisition_value', 'residual_value', 'useful_life_months', 'planned_hours_month', 'power_kw', 'energy_rate', 'labor_cost_hour', 'maintenance_cost_hour', 'consumables_cost_hour', 'next_maintenance_at']);
        });
    }
};
