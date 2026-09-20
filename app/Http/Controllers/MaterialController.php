<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $query = Material::query()->latest();
        if ($search = $request->string('busca')->trim()->toString()) $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
        return view('materials.index', ['materials' => $query->paginate(12)->withQueryString()]);
    }
    public function create(): View { return view('materials.form', ['material' => new Material()]); }
    public function store(Request $request): RedirectResponse { Material::create($this->validated($request)); return redirect()->route('materiais.index')->with('success', 'Material cadastrado com sucesso.'); }
    public function edit(Material $materiai): View { return view('materials.form', ['material' => $materiai]); }
    public function update(Request $request, Material $materiai): RedirectResponse { $materiai->update($this->validated($request, $materiai)); return redirect()->route('materiais.index')->with('success', 'Material atualizado com sucesso.'); }
    private function validated(Request $request, ?Material $material = null): array
    {
        $codeRule = 'unique:materials,code'.($material ? ','.$material->id : '');
        return $request->validate([
            'code' => ['required', 'string', 'max:40', $codeRule], 'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:80'], 'unit' => ['required', 'string', 'max:12'],
            'thickness_mm' => ['nullable', 'numeric', 'min:0'], 'width_mm' => ['nullable', 'numeric', 'min:0'],
            'height_mm' => ['nullable', 'numeric', 'min:0'], 'cost_per_unit' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'numeric', 'min:0'], 'minimum_stock' => ['required', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:80'], 'active' => ['boolean'],
        ]);
    }
}
