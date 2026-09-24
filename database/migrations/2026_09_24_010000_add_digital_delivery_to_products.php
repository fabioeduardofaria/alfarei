<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('digital_file_path', 500)->nullable();
            $table->string('digital_file_name', 255)->nullable();
            $table->unsignedBigInteger('digital_file_size')->nullable();
            $table->string('digital_file_sha256', 64)->nullable();
            $table->string('digital_version', 40)->nullable();
            $table->text('digital_license_terms')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['digital_file_path', 'digital_file_name', 'digital_file_size', 'digital_file_sha256', 'digital_version', 'digital_license_terms']);
        });
    }
};
