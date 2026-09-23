<?php

namespace App\Http\Controllers;

use App\Models\DisplayConfigurator;
use App\Models\DisplaySize;
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
        $configurator->load(['product', 'mdfMaterial', 'adhesiveMaterial', 'laserMachine', 'sizes']);

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

    public function addSize(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'width_cm' => ['required', 'integer', 'min:1', 'max:250'],
            'height_cm' => ['required', 'integer', 'min:1', 'max:250'],
            'laser_minutes_override' => ['nullable', 'numeric', 'gt:0', 'max:60'],
        ]);
        $configurator = DisplayConfigurator::firstOrCreate([]);
        if ($configurator->sizes()->where('width_cm', $data['width_cm'])->where('height_cm', $data['height_cm'])->exists()) {
            throw ValidationException::withMessages(['width_cm' => 'Este tamanho já está cadastrado.']);
        }
        $this->assertFitsMdf($configurator, (int) $data['width_cm'], (int) $data['height_cm']);
        $configurator->sizes()->create($data + ['active' => true]);

        return back()->with('success', 'Tamanho adicionado.');
    }

    public function toggleSize(DisplaySize $size): RedirectResponse
    {
        $configurator = DisplayConfigurator::firstOrCreate([]);
        abort_unless($size->display_configurator_id === $configurator->id, 404);
        if (! $size->active) {
            $this->assertFitsMdf($configurator, $size->width_cm, $size->height_cm);
        }
        $size->update(['active' => ! $size->active]);
        if ($configurator->enabled && $this->pricing->missingRequirements($configurator->fresh()) !== []) {
            $configurator->update(['enabled' => false]);
        }

        return back()->with('success', 'Disponibilidade do tamanho atualizada.');
    }

    public function updateSize(Request $request, DisplaySize $size): RedirectResponse
    {
        $configurator = DisplayConfigurator::firstOrCreate([]);
        abort_unless($size->display_configurator_id === $configurator->id, 404);
        $data = $request->validate(['laser_minutes_override' => ['nullable', 'numeric', 'gt:0', 'max:60']]);
        $size->update($data);

        return back()->with('success', 'Tempo de laser deste tamanho atualizado.');
    }

    private function assertFitsMdf(DisplayConfigurator $configurator, int $widthCm, int $heightCm): void
    {
        $material = $configurator->mdfMaterial;
        if (! $material || ! $material->width_mm || ! $material->height_mm) {
            return;
        }
        $width = $widthCm * 10;
        $height = $heightCm * 10;
        if (! (($width <= $material->width_mm && $height <= $material->height_mm) || ($height <= $material->width_mm && $width <= $material->height_mm))) {
            throw ValidationException::withMessages(['width_cm' => 'Este tamanho não cabe na chapa de MDF selecionada.']);
        }
    }
}
