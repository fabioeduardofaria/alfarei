<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->string('number', 30)->unique(); $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('awaiting_deposit'); $table->string('source', 25)->default('quote');
            $table->decimal('total', 12, 2); $table->decimal('cost_total', 12, 2); $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->timestamp('deposit_paid_at')->nullable(); $table->timestamp('art_approved_at')->nullable(); $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 200); $table->string('type', 20); $table->decimal('quantity', 12, 3); $table->decimal('unit_price', 12, 2); $table->decimal('unit_cost', 12, 2); $table->decimal('total', 12, 2); $table->decimal('total_cost', 12, 2); $table->boolean('made_to_order')->default(true); $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete(); $table->string('type', 20); $table->string('status', 20)->default('pending'); $table->decimal('amount', 12, 2); $table->date('due_date')->nullable(); $table->timestamp('paid_at')->nullable(); $table->string('method', 50)->nullable(); $table->text('notes')->nullable(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('payments'); Schema::dropIfExists('order_items'); Schema::dropIfExists('orders'); }
};
