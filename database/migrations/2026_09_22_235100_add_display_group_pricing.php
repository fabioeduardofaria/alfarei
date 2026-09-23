<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('display_configurators', function (Blueprint $table) {
            $table->decimal('reseller_margin_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('reseller_min_quantity')->default(10);
            $table->decimal('wholesale_margin_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('wholesale_min_quantity')->default(50);
        });
    }

    public function down(): void
    {
        Schema::table('display_configurators', function (Blueprint $table) {
            $table->dropColumn(['reseller_margin_percent', 'reseller_min_quantity', 'wholesale_margin_percent', 'wholesale_min_quantity']);
        });
    }
};
