<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('type', 2);
            $table->string('name', 150);
            $table->string('document', 20)->nullable()->unique();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 2)->nullable();
            $table->string('customer_group', 30)->default('final');
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('customers'); }
};
