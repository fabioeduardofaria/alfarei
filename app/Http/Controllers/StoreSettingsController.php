<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreSettingsController extends Controller
{
    public function edit(): View { return view('store-settings.form', ['settings' => StoreSetting::firstOrCreate([])]); }
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['store_name' => ['required', 'string', 'max:100'], 'hero_title' => ['required', 'string', 'max:180'], 'hero_subtitle' => ['nullable', 'string', 'max:1000'], 'whatsapp' => ['nullable', 'string', 'max:30'], 'support_email' => ['nullable', 'email', 'max:150'], 'pix_key' => ['nullable', 'string', 'max:180'], 'delivery_message' => ['nullable', 'string', 'max:240'], 'store_open' => ['boolean']]);
        StoreSetting::firstOrCreate([])->update($data);
        return back()->with('success', 'Configurações da loja salvas.');
    }
}
