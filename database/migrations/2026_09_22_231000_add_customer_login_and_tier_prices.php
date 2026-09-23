<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('password')->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('reseller_price', 12, 2)->nullable();
            $table->unsignedInteger('reseller_min_quantity')->nullable();
            $table->decimal('wholesale_price', 12, 2)->nullable();
            $table->unsignedInteger('wholesale_min_quantity')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['reseller_price', 'reseller_min_quantity', 'wholesale_price', 'wholesale_min_quantity']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
