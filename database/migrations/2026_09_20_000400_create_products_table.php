<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 60)->nullable()->unique();
            $table->string('name', 150);
            $table->string('type', 20);
            $table->decimal('base_price', 12, 2)->default(0);
            $table->decimal('production_cost', 12, 2)->default(0);
            $table->boolean('made_to_order')->default(true);
            $table->boolean('active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('products'); }
};
