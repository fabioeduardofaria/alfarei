<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreSettingsController extends Controller
{
    public function edit(): View
    {
        return view('store-settings.form', ['settings' => StoreSetting::firstOrCreate([])]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:100'], 'hero_title' => ['required', 'string', 'max:180'], 'hero_subtitle' => ['nullable', 'string', 'max:1000'],
            'hero_image_url' => ['nullable', 'url', 'max:1000'], 'hero_cta_label' => ['required', 'string', 'max:60'], 'hero_cta_url' => ['nullable', 'string', 'max:500'],
            'announcement_text' => ['nullable', 'string', 'max:240'], 'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'whatsapp' => ['nullable', 'string', 'max:30'], 'whatsapp_message' => ['nullable', 'string', 'max:500'], 'support_email' => ['nullable', 'email', 'max:150'],
            'instagram_url' => ['nullable', 'url', 'max:500'], 'facebook_url' => ['nullable', 'url', 'max:500'], 'address' => ['nullable', 'string', 'max:300'], 'business_hours' => ['nullable', 'string', 'max:300'],
            'pix_key' => ['nullable', 'string', 'max:180'], 'deposit_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'pickup_address' => ['nullable', 'string', 'max:300'], 'shipping_message' => ['nullable', 'string', 'max:500'], 'free_shipping_minimum' => ['nullable', 'numeric', 'min:0'], 'production_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'seo_title' => ['nullable', 'string', 'max:180'], 'seo_description' => ['nullable', 'string', 'max:300'], 'share_image_url' => ['nullable', 'url', 'max:1000'], 'google_analytics_id' => ['nullable', 'string', 'max:80'], 'meta_pixel_id' => ['nullable', 'string', 'max:80'],
            'privacy_policy' => ['nullable', 'string', 'max:10000'], 'return_policy' => ['nullable', 'string', 'max:10000'],
        ]);
        foreach (['announcement_active', 'pickup_enabled', 'shipping_enabled', 'pix_enabled', 'mercado_pago_enabled', 'card_enabled', 'store_open'] as $field) {
            $data[$field] = $request->boolean($field);
        }
        StoreSetting::firstOrCreate([])->update($data);

        return back()->with('success', 'Configurações da loja salvas e publicadas.');
    }
}
