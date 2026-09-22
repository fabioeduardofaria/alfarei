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
        Schema::create('product_machine_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained()->restrictOnDelete();
            $table->string('operation_name', 100);
            $table->decimal('minutes_per_unit', 10, 2);
            $table->decimal('setup_minutes', 10, 2)->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'machine_id', 'operation_name'], 'product_machine_operation_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_machine_operations');
    }
};
