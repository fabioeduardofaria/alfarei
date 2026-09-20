<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->string('category', 80);
            $table->string('unit', 12);
            $table->decimal('thickness_mm', 10, 2)->nullable();
            $table->decimal('width_mm', 12, 2)->nullable();
            $table->decimal('height_mm', 12, 2)->nullable();
            $table->decimal('cost_per_unit', 12, 2)->default(0);
            $table->decimal('stock_quantity', 14, 3)->default(0);
            $table->decimal('minimum_stock', 14, 3)->default(0);
            $table->string('location', 80)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('materials'); }
};
