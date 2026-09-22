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
        Schema::create('quote_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->string('category', 30)->default('technical');
            $table->unsignedSmallInteger('version')->default(1);
            $table->boolean('approved')->default(false);
            $table->timestamps();
            $table->index(['quote_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_attachments');
    }
};
