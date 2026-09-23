<?php

namespace App\Http\Controllers;

use App\Models\DisplayConfigurator;
use App\Models\DisplaySize;
use App\Models\StoreSetting;
use App\Services\DisplayPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StoreDisplayController extends Controller
{
    public function __construct(private readonly DisplayPricingService $pricing) {}

    public function show(): View
    {
        $configurator = $this->availableConfigurator();

        return view('store.display', [
            'settings' => StoreSetting::firstOrCreate([]),
            'cartCount' => collect(session('store_cart', []))->sum('quantity'),
            'configurator' => $configurator,
            'sizes' => $configurator->sizes->where('active', true)->sortBy('width_cm'),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'size_id' => ['required', 'integer', 'exists:display_sizes,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $configurator = $this->availableConfigurator();
        $size = DisplaySize::findOrFail($data['size_id']);
        abort_unless($size->display_configurator_id === $configurator->id && $size->active, 404);
        $customer = Auth::guard('customer')->user();
        $customer = $customer?->active ? $customer : null;
        $quote = $this->pricing->quote($configurator, $size, (int) $data['quantity'], $customer);
        $token = Str::random(40);
        $savedQuotes = collect(session('display_quotes', []))->filter(fn ($entry) => $entry['expires_at'] > now()->timestamp)->take(-19)->all();
        $savedQuotes[$token] = [
            'expires_at' => now()->addMinutes(15)->timestamp,
            'product_id' => $configurator->product_id,
            'customer_id' => $customer?->id,
            'quote' => $quote,
            'sources' => [
                'mdf_material_id' => $configurator->mdf_material_id,
                'mdf_cost_per_unit' => $configurator->mdfMaterial->cost_per_unit,
                'adhesive_material_id' => $configurator->adhesive_material_id,
                'adhesive_cost_per_unit' => $configurator->adhesiveMaterial->cost_per_unit,
                'laser_machine_id' => $configurator->laser_machine_id,
                'laser_hourly_cost' => $configurator->laserMachine->calculatedHourlyCost() ?: $configurator->laserMachine->hourly_cost,
                'selling_fee_percent' => $configurator->selling_fee_percent,
                'target_margin_percent' => $configurator->target_margin_percent,
            ],
        ];
        session(['display_quotes' => $savedQuotes]);

        return response()->json(['token' => $token, 'unit_price' => $quote['unit_price'], 'total' => $quote['total'], 'expires_in_seconds' => 900]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'quote_token' => ['required', 'string', 'size:40'],
            'personalization' => ['nullable', 'string', 'max:500'],
        ]);
        $savedQuotes = session('display_quotes', []);
        $entry = $savedQuotes[$data['quote_token']] ?? null;
        if (! $entry || $entry['expires_at'] <= now()->timestamp) {
            return back()->withErrors(['quote_token' => 'O preço expirou. Atualize a simulação e tente novamente.']);
        }
        $customer = Auth::guard('customer')->user();
        if (($entry['customer_id'] ?? null) !== ($customer?->active ? $customer->id : null)) {
            return back()->withErrors(['quote_token' => 'Sua conta mudou. Atualize o preço antes de adicionar.']);
        }
        $freshCustomer = $customer?->fresh();
        if (($entry['quote']['customer_group'] === 'reseller' && ! $freshCustomer?->hasResellerPricing()) ||
            ($entry['quote']['customer_group'] === 'wholesale' && (! $freshCustomer?->active || $freshCustomer->customer_group !== 'wholesale'))) {
            return back()->withErrors(['quote_token' => 'Sua condição comercial mudou. Atualize o preço antes de adicionar.']);
        }
        $configurator = $this->availableConfigurator();
        if ((int) $entry['product_id'] !== $configurator->product_id) {
            return back()->withErrors(['quote_token' => 'Esta configuração não está mais disponível.']);
        }

        $cart = session('store_cart', []);
        $cart['display:'.Str::random(16)] = [
            'kind' => 'display', 'product_id' => $entry['product_id'],
            'customer_id' => $entry['customer_id'],
            'quantity' => $entry['quote']['quantity'],
            'personalization' => $data['personalization'] ?? null,
            'unit_price' => $entry['quote']['unit_price'],
            'unit_cost' => $entry['quote']['unit_cost'],
            'configuration_snapshot' => $entry['quote'] + ['sources' => $entry['sources']],
        ];
        unset($savedQuotes[$data['quote_token']]);
        session(['store_cart' => $cart, 'display_quotes' => $savedQuotes]);

        return redirect()->route('loja.cart')->with('success', 'Display configurado adicionado ao carrinho.');
    }

    private function availableConfigurator(): DisplayConfigurator
    {
        $configurator = DisplayConfigurator::with(['product', 'mdfMaterial', 'adhesiveMaterial', 'laserMachine', 'sizes'])->first();
        abort_unless($configurator?->enabled && $this->pricing->missingRequirements($configurator) === [], 404);

        return $configurator;
    }
}
