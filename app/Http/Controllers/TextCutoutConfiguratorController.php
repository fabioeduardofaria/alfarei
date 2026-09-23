<?php

namespace App\Http\Controllers;

use App\Models\DisplayConfigurator;
use App\Models\Machine;
use App\Models\Product;
use App\Models\TextCutoutConfigurator;
use App\Services\TextCutoutPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TextCutoutConfiguratorController extends Controller
{
    public function __construct(private readonly TextCutoutPricingService $pricing) {}

    public function edit(): View
    {
        $configurator = TextCutoutConfigurator::firstOrCreate([]);
        $configurator->load(['product', 'laserMachine']);

        return view('text-cutout-configurator.edit', [
            'configurator' => $configurator,
            'products' => Product::where('active', true)->where('store_visible', true)->orderBy('name')->get(),
            'machines' => Machine::where('active', true)->orderBy('name')->get(),
            'materials' => $this->pricing->eligibleMaterials(),
            'missing' => $this->pricing->missingRequirements($configurator),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
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
        if (! empty($data['product_id']) && DisplayConfigurator::where('product_id', $data['product_id'])->exists()) {
            throw ValidationException::withMessages(['product_id' => 'Este produto já está vinculado ao configurador de displays.']);
        }
        DB::transaction(function () use ($data, $request): void {
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
}
