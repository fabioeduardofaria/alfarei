<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('text_cutout_configurators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('laser_machine_id')->nullable()->constrained('machines')->nullOnDelete();
            $table->decimal('max_width_cm', 5, 1)->nullable();
            $table->decimal('max_height_cm', 5, 1)->nullable();
            $table->decimal('average_character_width_ratio', 4, 2)->default(0.60);
            $table->decimal('minimum_width_percent', 5, 2)->default(50);
            $table->decimal('laser_minutes_per_character_10cm', 6, 2)->nullable();
            $table->decimal('minimum_laser_minutes', 6, 2)->nullable();
            $table->decimal('material_loss_percent', 5, 2)->default(15);
            $table->decimal('white_finish_cost_per_m2', 12, 2)->nullable();
            $table->decimal('painted_finish_cost_per_m2', 12, 2)->nullable();
            $table->decimal('base_cost_per_unit', 12, 2)->default(0);
            $table->decimal('packaging_cost_per_unit', 12, 2)->default(0);
            $table->decimal('setup_cost_per_order', 12, 2)->default(0);
            $table->decimal('selling_fee_percent', 5, 2)->default(0);
            $table->decimal('target_margin_percent', 5, 2)->default(25);
            $table->decimal('reseller_margin_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('reseller_min_quantity')->default(10);
            $table->decimal('wholesale_margin_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('wholesale_min_quantity')->default(50);
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('text_cutout_configurators');
    }
};
