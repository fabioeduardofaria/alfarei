<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('display_configurators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mdf_material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('adhesive_material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('laser_machine_id')->nullable()->constrained('machines')->nullOnDelete();
            $table->decimal('laser_minutes_per_unit', 6, 2)->default(3);
            $table->decimal('mdf_loss_percent', 5, 2)->default(10);
            $table->decimal('adhesive_loss_percent', 5, 2)->default(10);
            $table->decimal('printing_cost_per_m2', 12, 2)->default(0);
            $table->decimal('application_cost_per_m2', 12, 2)->default(0);
            $table->decimal('base_cost_per_unit', 12, 2)->default(0);
            $table->decimal('assembly_cost_per_unit', 12, 2)->default(0);
            $table->decimal('packaging_cost_per_unit', 12, 2)->default(0);
            $table->decimal('artwork_setup_cost_per_order', 12, 2)->default(0);
            $table->decimal('selling_fee_percent', 5, 2)->default(0);
            $table->decimal('target_margin_percent', 5, 2)->default(25);
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('display_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('display_configurator_id')->constrained()->cascadeOnDelete();
            $table->string('label', 80);
            $table->unsignedSmallInteger('width_cm');
            $table->unsignedSmallInteger('height_cm');
            $table->decimal('laser_minutes_override', 6, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['display_configurator_id', 'width_cm', 'height_cm']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('configuration_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('configuration_snapshot');
        });
        Schema::dropIfExists('display_sizes');
        Schema::dropIfExists('display_configurators');
    }
};
