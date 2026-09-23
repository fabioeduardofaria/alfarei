<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('reseller_status', 30)->nullable();
            $table->timestamp('reseller_approved_at')->nullable();
            $table->timestamp('reseller_reviewed_at')->nullable();
        });

        DB::table('customers')->where('customer_group', 'reseller')->update(['reseller_status' => 'approved']);

        Schema::create('reseller_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('pending');
            $table->string('business_name', 150)->nullable();
            $table->string('sales_channel', 30);
            $table->string('sales_url', 500)->nullable();
            $table->text('description');
            $table->text('decision_note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('reseller_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('reseller_applications')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('event', 40);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_events');
        Schema::dropIfExists('reseller_applications');
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['reseller_status', 'reseller_approved_at', 'reseller_reviewed_at']);
        });
    }
};
