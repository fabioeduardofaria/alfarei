<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\StoreSetting;
use App\Models\TextCutoutConfigurator;
use App\Services\TextCutoutPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class StoreTextCutoutController extends Controller
{
    public function __construct(private readonly TextCutoutPricingService $pricing) {}

    public function show(): View
    {
        return view('store.text-cutout', [
            'settings' => StoreSetting::firstOrCreate([]),
            'cartCount' => collect(session('store_cart', []))->sum('quantity'),
            'configurator' => $this->availableConfigurator(),
            'materials' => $this->pricing->eligibleMaterials(),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:100'],
            'material_id' => ['required', 'exists:materials,id'],
            'finish' => ['required', 'in:natural,white,painted'],
            'with_base' => ['nullable', 'in:0,1'],
            'color' => ['nullable', 'string', 'max:50'],
            'width_cm' => ['nullable', 'numeric', 'min:1', 'decimal:0,1'],
            'height_cm' => ['required', 'numeric', 'min:1', 'decimal:0,1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $configurator = $this->availableConfigurator();
        $customer = Auth::guard('customer')->user();
        $customer = $customer?->active ? $customer : null;
        try {
            $quote = $this->pricing->quote(
                $configurator, Material::findOrFail($data['material_id']), $data['text'],
                (float) $data['height_cm'], isset($data['width_cm']) ? (float) $data['width_cm'] : null,
                $data['finish'], $data['color'] ?? null, (int) $data['quantity'], $customer, (bool) ($data['with_base'] ?? false),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['text' => $exception->getMessage()]);
        }

        $token = Str::random(40);
        $savedQuotes = collect(session('text_cutout_quotes', []))->filter(fn ($entry) => $entry['expires_at'] > now()->timestamp)->take(-19)->all();
        $savedQuotes[$token] = [
            'expires_at' => now()->addMinutes(15)->timestamp,
            'product_id' => $configurator->product_id,
            'customer_id' => $customer?->id,
            'quote' => $quote,
        ];
        session(['text_cutout_quotes' => $savedQuotes]);

        return response()->json(['token' => $token, 'unit_price' => $quote['unit_price'], 'total' => $quote['total'], 'width_cm' => $quote['width_cm'], 'width_estimated' => $quote['width_estimated']]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate(['quote_token' => ['required', 'string', 'size:40']]);
        $savedQuotes = session('text_cutout_quotes', []);
        $entry = $savedQuotes[$data['quote_token']] ?? null;
        if (! $entry || $entry['expires_at'] <= now()->timestamp) {
            return back()->withErrors(['quote_token' => 'O preço expirou. Atualize a simulação.']);
        }
        $customer = Auth::guard('customer')->user();
        $freshCustomer = $customer?->active ? $customer->fresh() : null;
        if (($entry['customer_id'] ?? null) !== $freshCustomer?->id ||
            ($entry['quote']['customer_group'] === 'reseller' && ! $freshCustomer?->hasResellerPricing()) ||
            ($entry['quote']['customer_group'] === 'wholesale' && $freshCustomer?->customer_group !== 'wholesale')) {
            return back()->withErrors(['quote_token' => 'Sua condição comercial mudou. Atualize o preço.']);
        }
        $configurator = $this->availableConfigurator();
        if ((int) $entry['product_id'] !== $configurator->product_id ||
            (float) $entry['quote']['width_cm'] > (float) $configurator->max_width_cm ||
            (float) $entry['quote']['height_cm'] > (float) $configurator->max_height_cm) {
            return back()->withErrors(['quote_token' => 'Os limites ou o produto mudaram. Atualize o preço.']);
        }
        if (! $this->pricing->eligibleMaterials()->contains('id', $entry['quote']['material_id'])) {
            return back()->withErrors(['quote_token' => 'O material não está mais disponível. Atualize a configuração.']);
        }
        $material = Material::find($entry['quote']['material_id']);
        $widthMm = (float) $entry['quote']['width_cm'] * 10;
        $heightMm = (float) $entry['quote']['height_cm'] * 10;
        if (! (($widthMm <= (float) $material->width_mm && $heightMm <= (float) $material->height_mm) ||
            ($heightMm <= (float) $material->width_mm && $widthMm <= (float) $material->height_mm))) {
            return back()->withErrors(['quote_token' => 'A chapa do material mudou. Atualize o preço.']);
        }
        $cart = session('store_cart', []);
        $cart['text_cutout:'.Str::random(16)] = [
            'kind' => 'text_cutout', 'product_id' => $entry['product_id'], 'customer_id' => $entry['customer_id'],
            'quantity' => $entry['quote']['quantity'], 'personalization' => null,
            'unit_price' => $entry['quote']['unit_price'], 'unit_cost' => $entry['quote']['unit_cost'],
            'configuration_snapshot' => $entry['quote'],
        ];
        unset($savedQuotes[$data['quote_token']]);
        session(['store_cart' => $cart, 'text_cutout_quotes' => $savedQuotes]);

        return redirect()->route('loja.cart')->with('success', 'Nome ou texto personalizado adicionado ao carrinho.');
    }

    private function availableConfigurator(): TextCutoutConfigurator
    {
        $configurator = TextCutoutConfigurator::with(['product', 'laserMachine'])->first();
        abort_unless($configurator?->enabled && $this->pricing->missingRequirements($configurator) === [], 404);

        return $configurator;
    }
}
