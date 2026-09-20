<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id(); $table->string('number', 30)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('version')->default(1); $table->string('status', 25)->default('draft');
            $table->date('valid_until')->nullable(); $table->text('notes')->nullable();
            $table->decimal('discount', 12, 2)->default(0); $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('cost_total', 12, 2)->default(0); $table->decimal('total', 12, 2)->default(0); $table->timestamps();
        });
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 200); $table->string('type', 20)->default('custom');
            $table->decimal('quantity', 12, 3); $table->decimal('unit_price', 12, 2); $table->decimal('unit_cost', 12, 2);
            $table->decimal('total', 12, 2); $table->decimal('total_cost', 12, 2); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('quote_items'); Schema::dropIfExists('quotes'); }
};
