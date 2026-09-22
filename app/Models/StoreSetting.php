<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = ['store_name', 'hero_title', 'hero_subtitle', 'hero_image_url', 'hero_cta_label', 'hero_cta_url', 'announcement_text', 'announcement_active', 'primary_color', 'accent_color', 'whatsapp', 'whatsapp_message', 'support_email', 'instagram_url', 'facebook_url', 'address', 'business_hours', 'pix_key', 'pix_enabled', 'mercado_pago_enabled', 'card_enabled', 'deposit_percent', 'delivery_message', 'pickup_enabled', 'shipping_enabled', 'pickup_address', 'shipping_message', 'free_shipping_minimum', 'production_days', 'seo_title', 'seo_description', 'share_image_url', 'google_analytics_id', 'meta_pixel_id', 'privacy_policy', 'return_policy', 'store_open'];

    protected function casts(): array
    {
        return ['store_open' => 'boolean', 'announcement_active' => 'boolean', 'pickup_enabled' => 'boolean', 'shipping_enabled' => 'boolean', 'pix_enabled' => 'boolean', 'mercado_pago_enabled' => 'boolean', 'card_enabled' => 'boolean', 'free_shipping_minimum' => 'decimal:2', 'deposit_percent' => 'decimal:2'];
    }
}
