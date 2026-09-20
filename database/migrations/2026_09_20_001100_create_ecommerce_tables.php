<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('store_visible')->default(false)->after('active');
            $table->string('store_slug', 180)->nullable()->unique()->after('store_visible');
            $table->string('image_url', 1000)->nullable()->after('store_slug');
            $table->boolean('allow_personalization')->default(false)->after('image_url');
        });
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name', 100)->default('Alfarei');
            $table->string('hero_title', 180)->default('Peças que fazem sua ideia existir.');
            $table->text('hero_subtitle')->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('support_email', 150)->nullable();
            $table->string('pix_key', 180)->nullable();
            $table->string('delivery_message', 240)->nullable();
            $table->boolean('store_open')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('store_settings'); Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['store_visible', 'store_slug', 'image_url', 'allow_personalization'])); }
};
