<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_method', 20)->default('pickup')->after('source');
            $table->string('postal_code', 12)->nullable()->after('delivery_method');
            $table->string('street', 150)->nullable(); $table->string('street_number', 20)->nullable();
            $table->string('complement', 100)->nullable(); $table->string('neighborhood', 100)->nullable();
            $table->string('delivery_city', 100)->nullable(); $table->string('delivery_state', 2)->nullable();
            $table->string('tracking_code', 100)->nullable(); $table->timestamp('shipped_at')->nullable(); $table->timestamp('delivered_at')->nullable();
        });
    }
    public function down(): void { Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['delivery_method', 'postal_code', 'street', 'street_number', 'complement', 'neighborhood', 'delivery_city', 'delivery_state', 'tracking_code', 'shipped_at', 'delivered_at'])); }
};
