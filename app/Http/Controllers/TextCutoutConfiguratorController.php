<?php

namespace App\Http\Controllers;

use App\Models\DisplayConfigurator;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Product;
use App\Models\TextCutoutConfigurator;
use App\Services\TextCutoutPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class TextCutoutConfiguratorController extends Controller
{
    public function __construct(private readonly TextCutoutPricingService $pricing) {}

    public function edit(): View
    {
        $configurator = TextCutoutConfigurator::firstOrCreate([]);
        $configurator->load(['product', 'laserMachine']);
        $displayProductIds = DisplayConfigurator::query()->whereNotNull('product_id')->pluck('product_id');

        return view('text-cutout-configurator.edit', [
            'configurator' => $configurator,
            'products' => Product::where('active', true)->where('store_visible', true)->where('made_to_order', true)
                ->whereNotIn('id', $displayProductIds)->orderBy('name')->get(),
            'machines' => Machine::where('active', true)->orderBy('name')->get(),
            'materials' => $this->pricing->eligibleMaterials(),
            'missing' => $this->pricing->missingRequirements($configurator),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'create_product' => ['nullable', 'boolean'],
            'new_product_name' => ['required_if:create_product,1', 'nullable', 'string', 'max:150'],
            'laser_machine_id' => ['nullable', 'exists:machines,id'],
            'max_width_cm' => ['required', 'numeric', 'min:1', 'max:250', 'decimal:0,1'],
            'max_height_cm' => ['required', 'numeric', 'min:1', 'max:250', 'decimal:0,1'],
            'average_character_width_ratio' => ['required', 'numeric', 'min:0.2', 'max:1.5'],
            'minimum_width_percent' => ['required', 'numeric', 'min:20', 'max:100'],
            'laser_minutes_per_character_10cm' => ['required', 'numeric', 'gt:0', 'max:60'],
            'minimum_laser_minutes' => ['required', 'numeric', 'gt:0', 'max:60'],
            'material_loss_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'white_finish_cost_per_m2' => ['required', 'numeric', 'min:0'],
            'painted_finish_cost_per_m2' => ['required', 'numeric', 'min:0'],
            'base_cost_per_unit' => ['required', 'numeric', 'min:0'],
            'packaging_cost_per_unit' => ['required', 'numeric', 'min:0'],
            'setup_cost_per_order' => ['required', 'numeric', 'min:0'],
            'selling_fee_percent' => ['required', 'numeric', 'min:0', 'max:94'],
            'target_margin_percent' => ['required', 'numeric', 'min:0', 'max:94'],
            'reseller_margin_percent' => ['nullable', 'numeric', 'min:0', 'max:94'],
            'reseller_min_quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'wholesale_margin_percent' => ['nullable', 'numeric', 'min:0', 'max:94'],
            'wholesale_min_quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $createProduct = $request->boolean('create_product');
        if ($createProduct && TextCutoutConfigurator::whereNotNull('product_id')->exists()) {
            throw ValidationException::withMessages(['create_product' => 'Já existe um produto vinculado a este configurador. Use esse produto ou selecione outro existente.']);
        }
        if (! $createProduct && ! empty($data['product_id'])) {
            if (DisplayConfigurator::where('product_id', $data['product_id'])->exists()) {
                throw ValidationException::withMessages(['product_id' => 'Este produto pertence ao configurador de displays. Escolha outro ou crie um produto exclusivo abaixo.']);
            }
            $product = Product::findOrFail($data['product_id']);
            if (! $product->active || ! $product->store_visible || ! $product->made_to_order) {
                throw ValidationException::withMessages(['product_id' => 'Escolha um produto ativo, visível na loja e produzido sob encomenda.']);
            }
        }
        DB::transaction(function () use ($data, $request, $createProduct): void {
            if ($createProduct) {
                $product = Product::create([
                    'name' => trim($data['new_product_name']),
                    'type' => 'product',
                    'base_price' => 0,
                    'production_cost' => 0,
                    'made_to_order' => true,
                    'active' => true,
                    'store_visible' => true,
                    'allow_personalization' => true,
                    'description' => 'Nome ou texto recortado sob medida em MDF ou acrílico. O preço é calculado pelo configurador.',
                ]);
                $data['product_id'] = $product->id;
            }
            unset($data['create_product'], $data['new_product_name']);
            $configurator = TextCutoutConfigurator::firstOrCreate([]);
            $configurator->update($data + ['enabled' => false]);
            if ($request->boolean('enabled')) {
                $missing = $this->pricing->missingRequirements($configurator->fresh());
                if ($missing !== []) {
                    throw ValidationException::withMessages(['enabled' => implode(' ', $missing)]);
                }
                $configurator->update(['enabled' => true]);
            }
        });

        return back()->with('success', 'Parâmetros de nomes e textos atualizados.');
    }

    public function simulate(Request $request): RedirectResponse|JsonResponse
    {
        $configurator = TextCutoutConfigurator::with(['product', 'laserMachine'])->first();
        if (! $configurator || $this->pricing->missingRequirements($configurator) !== []) {
            throw ValidationException::withMessages(['simulation' => 'Salve todos os parâmetros do configurador antes de simular.']);
        }

        $data = $request->validate([
            'text' => ['required', 'string', 'max:100'],
            'material_id' => ['required', 'exists:materials,id'],
            'finish' => ['required', 'in:natural,white,painted'],
            'color' => ['nullable', 'string', 'max:50'],
            'height_cm' => ['required', 'numeric', 'min:1', 'decimal:0,1'],
            'width_cm' => ['nullable', 'numeric', 'min:1', 'decimal:0,1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $quote = $this->pricing->quote(
                $configurator, Material::findOrFail($data['material_id']), $data['text'],
                (float) $data['height_cm'], isset($data['width_cm']) ? (float) $data['width_cm'] : null,
                $data['finish'], $data['color'] ?? null, (int) $data['quantity'],
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['simulation' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['simulation' => $quote]);
        }

        return back()->withInput($request->only(['text', 'material_id', 'finish', 'color', 'height_cm', 'width_cm', 'quantity']))
            ->with('text_simulation', $quote);
    }
}
