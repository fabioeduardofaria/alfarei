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
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->string('hero_image_url', 1000)->nullable()->after('hero_subtitle');
            $table->string('hero_cta_label', 60)->default('Ver produtos')->after('hero_image_url');
            $table->string('hero_cta_url', 500)->nullable()->after('hero_cta_label');
            $table->string('announcement_text', 240)->nullable()->after('hero_cta_url');
            $table->boolean('announcement_active')->default(false)->after('announcement_text');
            $table->string('primary_color', 7)->default('#193128')->after('announcement_active');
            $table->string('accent_color', 7)->default('#d1a943')->after('primary_color');
            $table->string('instagram_url', 500)->nullable()->after('support_email');
            $table->string('facebook_url', 500)->nullable()->after('instagram_url');
            $table->string('address', 300)->nullable()->after('facebook_url');
            $table->string('business_hours', 300)->nullable()->after('address');
            $table->string('whatsapp_message', 500)->nullable()->after('whatsapp');
            $table->boolean('pickup_enabled')->default(true)->after('delivery_message');
            $table->boolean('shipping_enabled')->default(true)->after('pickup_enabled');
            $table->string('pickup_address', 300)->nullable()->after('shipping_enabled');
            $table->string('shipping_message', 500)->nullable()->after('pickup_address');
            $table->decimal('free_shipping_minimum', 12, 2)->nullable()->after('shipping_message');
            $table->unsignedSmallInteger('production_days')->nullable()->after('free_shipping_minimum');
            $table->boolean('pix_enabled')->default(true)->after('pix_key');
            $table->boolean('mercado_pago_enabled')->default(false)->after('pix_enabled');
            $table->boolean('card_enabled')->default(false)->after('mercado_pago_enabled');
            $table->decimal('deposit_percent', 5, 2)->default(50)->after('card_enabled');
            $table->string('seo_title', 180)->nullable()->after('deposit_percent');
            $table->string('seo_description', 300)->nullable()->after('seo_title');
            $table->string('share_image_url', 1000)->nullable()->after('seo_description');
            $table->string('google_analytics_id', 80)->nullable()->after('share_image_url');
            $table->string('meta_pixel_id', 80)->nullable()->after('google_analytics_id');
            $table->text('privacy_policy')->nullable()->after('meta_pixel_id');
            $table->text('return_policy')->nullable()->after('privacy_policy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn(['hero_image_url', 'hero_cta_label', 'hero_cta_url', 'announcement_text', 'announcement_active', 'primary_color', 'accent_color', 'instagram_url', 'facebook_url', 'address', 'business_hours', 'whatsapp_message', 'pickup_enabled', 'shipping_enabled', 'pickup_address', 'shipping_message', 'free_shipping_minimum', 'production_days', 'pix_enabled', 'mercado_pago_enabled', 'card_enabled', 'deposit_percent', 'seo_title', 'seo_description', 'share_image_url', 'google_analytics_id', 'meta_pixel_id', 'privacy_policy', 'return_policy']);
        });
    }
};
