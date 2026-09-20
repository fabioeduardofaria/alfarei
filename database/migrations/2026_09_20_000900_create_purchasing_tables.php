<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('document', 24)->nullable()->unique();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('contact_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('draft');
            $table->date('expected_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->decimal('total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('purchase_order_items'); Schema::dropIfExists('purchase_orders'); Schema::dropIfExists('suppliers'); }
};
