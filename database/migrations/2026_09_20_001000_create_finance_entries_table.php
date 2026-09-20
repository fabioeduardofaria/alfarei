<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('finance_entries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16);
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->string('description', 180);
            $table->string('counterparty', 150)->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('status', 16)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['source_type', 'source_id']);
            $table->index(['type', 'status', 'due_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('finance_entries'); }
};
