<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TechnicalSheetController extends Controller
{
    public function edit(Product $produto): View
    {
        return view('products.technical-sheet', ['product' => $produto->load('materials'), 'materials' => Material::where('active', true)->orderBy('name')->get()]);
    }

    public function update(Request $request, Product $produto): RedirectResponse
    {
        $data = $request->validate([
            'materials' => ['nullable', 'array'], 'materials.*.material_id' => ['required', 'distinct', 'exists:materials,id'],
            'materials.*.quantity' => ['required', 'numeric', 'gt:0'], 'materials.*.loss_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        $sync = collect($data['materials'] ?? [])->mapWithKeys(fn ($line) => [$line['material_id'] => ['quantity' => $line['quantity'], 'loss_percent' => $line['loss_percent']]])->all();
        $produto->materials()->sync($sync);
        return back()->with('success', 'Ficha técnica atualizada.');
    }
}
