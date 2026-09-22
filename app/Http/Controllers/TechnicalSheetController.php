<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Material;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TechnicalSheetController extends Controller
{
    public function edit(Product $produto): View
    {
        return view('products.technical-sheet', [
            'product' => $produto->load('materials', 'machineOperations'),
            'materials' => Material::where('active', true)->orderBy('name')->get(),
            'machines' => Machine::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $produto): RedirectResponse
    {
        $data = $request->validate([
            'materials' => ['nullable', 'array'], 'materials.*.material_id' => ['required', 'distinct', 'exists:materials,id'],
            'materials.*.quantity' => ['required', 'numeric', 'gt:0'], 'materials.*.loss_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'operations' => ['nullable', 'array'], 'operations.*.machine_id' => ['required', 'exists:machines,id'],
            'operations.*.operation_name' => ['required', 'string', 'max:100'], 'operations.*.minutes_per_unit' => ['required', 'numeric', 'gt:0'],
            'operations.*.setup_minutes' => ['required', 'numeric', 'min:0'],
        ]);
        $sync = collect($data['materials'] ?? [])->mapWithKeys(fn ($line) => [$line['material_id'] => ['quantity' => $line['quantity'], 'loss_percent' => $line['loss_percent']]])->all();
        $produto->materials()->sync($sync);
        $operationSync = collect($data['operations'] ?? [])->mapWithKeys(fn ($line) => [
            $line['machine_id'] => [
                'operation_name' => $line['operation_name'],
                'minutes_per_unit' => $line['minutes_per_unit'],
                'setup_minutes' => $line['setup_minutes'],
            ],
        ])->all();
        $produto->machineOperations()->sync($operationSync);
        $produto->refresh()->load('materials', 'machineOperations')->refreshProductionCost();

        return back()->with('success', 'Ficha técnica atualizada e custo de produção recalculado.');
    }
}
