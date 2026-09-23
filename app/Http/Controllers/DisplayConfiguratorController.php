<?php

namespace App\Http\Controllers;

use App\Models\DisplayConfigurator;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Product;
use App\Services\DisplayPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DisplayConfiguratorController extends Controller
{
    public function __construct(private readonly DisplayPricingService $pricing) {}

    public function edit(): View
    {
        $configurator = DisplayConfigurator::firstOrCreate([]);
        $configurator->load(['product', 'mdfMaterial', 'adhesiveMaterial', 'laserMachine']);

        return view('display-configurator.edit', [
            'configurator' => $configurator,
            'products' => Product::where('active', true)->where('store_visible', true)->orderBy('name')->get(),
            'materials' => Material::where('active', true)->orderBy('name')->get(),
            'machines' => Machine::where('active', true)->orderBy('name')->get(),
            'missing' => $this->pricing->missingRequirements($configurator),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'mdf_material_id' => ['nullable', 'exists:materials,id'],
            'adhesive_material_id' => ['nullable', 'exists:materials,id'],
            'laser_machine_id' => ['nullable', 'exists:machines,id'],
            'max_width_cm' => ['required', 'numeric', 'min:1', 'max:250', 'decimal:0,1'],
            'max_height_cm' => ['required', 'numeric', 'min:1', 'max:250', 'decimal:0,1'],
            'laser_minutes_per_unit' => ['required', 'numeric', 'gt:0', 'max:60'],
            'mdf_loss_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'adhesive_loss_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'printing_cost_per_m2' => ['required', 'numeric', 'min:0'],
            'application_cost_per_m2' => ['required', 'numeric', 'min:0'],
            'base_cost_per_unit' => ['required', 'numeric', 'min:0'],
            'assembly_cost_per_unit' => ['required', 'numeric', 'min:0'],
            'packaging_cost_per_unit' => ['required', 'numeric', 'min:0'],
            'artwork_setup_cost_per_order' => ['required', 'numeric', 'min:0'],
            'selling_fee_percent' => ['required', 'numeric', 'min:0', 'max:94'],
            'target_margin_percent' => ['required', 'numeric', 'min:0', 'max:94'],
            'reseller_margin_percent' => ['nullable', 'numeric', 'min:0', 'max:94'],
            'reseller_min_quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'wholesale_margin_percent' => ['nullable', 'numeric', 'min:0', 'max:94'],
            'wholesale_min_quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        if ((float) $data['selling_fee_percent'] + (float) $data['target_margin_percent'] >= 95) {
            throw ValidationException::withMessages(['target_margin_percent' => 'Taxas e margem juntas devem ficar abaixo de 95%.']);
        }
        foreach (['reseller', 'wholesale'] as $group) {
            if (isset($data[$group.'_margin_percent']) && (float) $data['selling_fee_percent'] + (float) $data[$group.'_margin_percent'] >= 95) {
                throw ValidationException::withMessages([$group.'_margin_percent' => 'Taxas e margem do grupo devem ficar abaixo de 95%.']);
            }
        }

        DB::transaction(function () use ($data, $request): void {
            $configurator = DisplayConfigurator::firstOrCreate([]);
            $configurator->update($data + ['enabled' => false]);
            $configurator->refresh();
            if ($request->boolean('enabled')) {
                $missing = $this->pricing->missingRequirements($configurator);
                if ($missing !== []) {
                    throw ValidationException::withMessages(['enabled' => implode(' ', $missing)]);
                }
                $configurator->update(['enabled' => true]);
            }
        });

        return back()->with('success', 'Parâmetros de display atualizados.');
    }

    public function simulate(Request $request): RedirectResponse
    {
        $configurator = DisplayConfigurator::firstOrCreate([]);
        $data = $request->validate([
            'width_cm' => ['required', 'numeric', 'min:1', 'max:'.$configurator->max_width_cm, 'decimal:0,1'],
            'height_cm' => ['required', 'numeric', 'min:1', 'max:'.$configurator->max_height_cm, 'decimal:0,1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        if ($this->pricing->missingRequirements($configurator) !== []) {
            throw ValidationException::withMessages(['width_cm' => 'Complete os parâmetros antes de simular.']);
        }
        $quote = $this->pricing->quote($configurator, (float) $data['width_cm'], (float) $data['height_cm'], (int) $data['quantity']);

        return back()->with('display_simulation', $quote);
    }
}
