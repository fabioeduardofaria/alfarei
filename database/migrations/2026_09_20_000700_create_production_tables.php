<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) { $table->id(); $table->string('name', 100); $table->string('code', 30)->unique(); $table->string('type', 50); $table->decimal('hourly_cost', 12, 2)->default(0); $table->string('status', 30)->default('available'); $table->boolean('active')->default(true); $table->timestamps(); });
        Schema::create('production_orders', function (Blueprint $table) { $table->id(); $table->string('number', 30)->unique(); $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete(); $table->foreignId('machine_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->string('status', 30)->default('awaiting'); $table->unsignedInteger('planned_minutes')->default(0); $table->unsignedInteger('actual_minutes')->default(0); $table->decimal('material_loss_cost', 12, 2)->default(0); $table->decimal('rework_cost', 12, 2)->default(0); $table->timestamp('started_at')->nullable(); $table->timestamp('finished_at')->nullable(); $table->timestamps(); });
        Schema::create('production_events', function (Blueprint $table) { $table->id(); $table->foreignId('production_order_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('type', 30); $table->string('from_status', 30)->nullable(); $table->string('to_status', 30)->nullable(); $table->unsignedInteger('minutes')->default(0); $table->decimal('loss_cost', 12, 2)->default(0); $table->decimal('rework_cost', 12, 2)->default(0); $table->text('notes')->nullable(); $table->timestamps(); });
    }
    public function down(): void { Schema::dropIfExists('production_events'); Schema::dropIfExists('production_orders'); Schema::dropIfExists('machines'); }
};
