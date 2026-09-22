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
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedSmallInteger('delivery_lead_days')->nullable()->after('production_lead_days');
            $table->decimal('tax_percent', 5, 2)->default(0)->after('discount');
            $table->decimal('commission_percent', 5, 2)->default(0)->after('tax_percent');
            $table->decimal('fee_percent', 5, 2)->default(0)->after('commission_percent');
            $table->decimal('target_margin_percent', 5, 2)->default(20)->after('fee_percent');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('target_margin_percent');
            $table->decimal('suggested_total', 12, 2)->default(0)->after('total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['delivery_lead_days', 'tax_percent', 'commission_percent', 'fee_percent', 'target_margin_percent', 'discount_percent', 'suggested_total']);
        });
    }
};
