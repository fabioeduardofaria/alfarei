<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('display_configurators', function (Blueprint $table) {
            $table->decimal('max_width_cm', 5, 1)->nullable();
            $table->decimal('max_height_cm', 5, 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('display_configurators', function (Blueprint $table) {
            $table->dropColumn(['max_width_cm', 'max_height_cm']);
        });
    }
};
