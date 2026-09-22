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
        Schema::table('quotes', function (Blueprint $table): void {
            $table->foreignId('parent_quote_id')->nullable()->after('id')->constrained('quotes')->nullOnDelete();
            $table->text('payment_terms')->nullable()->after('notes');
            $table->unsignedSmallInteger('production_lead_days')->nullable()->after('payment_terms');
            $table->decimal('deposit_percent', 5, 2)->default(50)->after('production_lead_days');
            $table->string('approval_token', 80)->nullable()->unique()->after('status');
            $table->timestamp('sent_at')->nullable()->after('approval_token');
            $table->timestamp('approved_at')->nullable()->after('sent_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->text('customer_response')->nullable()->after('rejected_at');
            $table->index(['parent_quote_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropForeign(['parent_quote_id']);
            $table->dropUnique(['approval_token']);
            $table->dropIndex(['parent_quote_id', 'version']);
            $table->dropColumn(['parent_quote_id', 'payment_terms', 'production_lead_days', 'deposit_percent', 'approval_token', 'sent_at', 'approved_at', 'rejected_at', 'customer_response']);
        });
    }
};
