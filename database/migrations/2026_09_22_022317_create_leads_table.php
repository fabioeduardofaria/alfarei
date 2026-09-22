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
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 150);
            $table->string('company', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('stage', 30)->default('new');
            $table->string('origin', 80)->nullable();
            $table->string('interest', 180)->nullable();
            $table->decimal('estimated_value', 14, 2)->default(0);
            $table->unsignedTinyInteger('probability')->default(0);
            $table->timestamp('next_contact_at')->nullable();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->string('lost_reason', 180)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['stage', 'assigned_to', 'created_at']);
            $table->index('next_contact_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
