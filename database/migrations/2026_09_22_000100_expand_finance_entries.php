<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('source_id')->nullable()->change();
            $table->string('category', 80)->nullable();
            $table->string('document_number', 80)->nullable();
            $table->uuid('installment_group')->nullable()->index();
            $table->unsignedSmallInteger('installment_number')->default(1);
            $table->unsignedSmallInteger('installment_count')->default(1);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
        });

        Schema::create('finance_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_entry_id')->constrained('finance_entries')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('paid_at');
            $table->string('payment_method', 40);
            $table->string('receipt_path')->nullable();
            $table->string('receipt_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->date('payment_due_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payments');
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('payment_due_at');
        });
        Schema::table('finance_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn(['category', 'document_number', 'installment_group', 'installment_number', 'installment_count', 'paid_amount']);
            $table->unsignedBigInteger('source_id')->nullable(false)->change();
        });
    }
};
