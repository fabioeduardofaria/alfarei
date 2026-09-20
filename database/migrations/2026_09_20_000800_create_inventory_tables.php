<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) { $table->decimal('reserved_quantity', 14, 3)->default(0)->after('stock_quantity'); });
        Schema::create('product_materials', function (Blueprint $table) { $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->foreignId('material_id')->constrained()->restrictOnDelete(); $table->decimal('quantity', 14, 3); $table->decimal('loss_percent', 5, 2)->default(0); $table->timestamps(); $table->unique(['product_id', 'material_id']); });
        Schema::create('inventory_movements', function (Blueprint $table) { $table->id(); $table->foreignId('material_id')->constrained()->restrictOnDelete(); $table->foreignId('production_order_id')->nullable()->constrained()->nullOnDelete(); $table->string('type', 20); $table->string('status', 20)->default('posted'); $table->decimal('quantity', 14, 3); $table->decimal('unit_cost', 12, 2)->default(0); $table->text('notes')->nullable(); $table->timestamps(); });
    }
    public function down(): void { Schema::dropIfExists('inventory_movements'); Schema::dropIfExists('product_materials'); Schema::table('materials', function (Blueprint $table) { $table->dropColumn('reserved_quantity'); }); }
};
