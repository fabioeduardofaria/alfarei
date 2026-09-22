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
        Schema::create('machine_maintenances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('status', 20)->default('scheduled');
            $table->date('scheduled_for')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('cost', 14, 2)->default(0);
            $table->unsignedInteger('machine_hours')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['machine_id', 'status', 'scheduled_for']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_maintenances');
    }
};
