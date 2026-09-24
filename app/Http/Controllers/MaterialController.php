<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $query = Material::query()->latest();
        if ($search = $request->string('busca')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
        }

        return view('materials.index', ['materials' => $query->paginate(12)->withQueryString()]);
    }

    public function create(): View
    {
        return view('materials.form', ['material' => new Material, 'categories' => MaterialCategory::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Material::create($this->validated($request));

        return redirect()->route('materiais.index')->with('success', 'Material cadastrado com sucesso.');
    }

    public function edit(Material $materiai): View
    {
        return view('materials.form', ['material' => $materiai, 'categories' => MaterialCategory::orderBy('name')->get()]);
    }

    public function update(Request $request, Material $materiai): RedirectResponse
    {
        $materiai->update($this->validated($request, $materiai));

        return redirect()->route('materiais.index')->with('success', 'Material atualizado com sucesso.');
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', 'regex:/[\pL\pN]/u']]);
        $name = MaterialCategory::cleanName($data['name']);
        $category = MaterialCategory::firstOrCreate(['key' => MaterialCategory::keyFor($name)], ['name' => $name]);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'already_exists' => ! $category->wasRecentlyCreated,
        ], $category->wasRecentlyCreated ? 201 : 200);
    }

    public function updateCategory(Request $request, MaterialCategory $category): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', 'regex:/[\pL\pN]/u']]);
        $name = MaterialCategory::cleanName($data['name']);
        $key = MaterialCategory::keyFor($name);

        DB::transaction(function () use ($category, $name, $key): void {
            $category = MaterialCategory::query()->lockForUpdate()->findOrFail($category->id);
            if (MaterialCategory::where('key', $key)->whereKeyNot($category->id)->exists()) {
                throw ValidationException::withMessages(['name' => 'Essa categoria já existe. Selecione-a na lista.']);
            }

            $oldName = $category->name;
            $category->update(['name' => $name, 'key' => $key]);
            Material::where('category', $oldName)->update(['category' => $name]);
        });

        return response()->json(['id' => $category->id, 'name' => $name]);
    }

    private function validated(Request $request, ?Material $material = null): array
    {
        $codeRule = 'unique:materials,code'.($material ? ','.$material->id : '');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', $codeRule], 'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:material_categories,id'], 'unit' => ['required', 'string', 'max:12'],
            'thickness_mm' => ['nullable', 'numeric', 'min:0'], 'width_mm' => ['nullable', 'numeric', 'min:0'],
            'height_mm' => ['nullable', 'numeric', 'min:0'], 'cost_per_unit' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'numeric', 'min:0'], 'minimum_stock' => ['required', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:80'], 'active' => ['boolean'],
        ]);
        $data['category'] = MaterialCategory::findOrFail($data['category_id'])->name;
        unset($data['category_id']);

        return $data;
    }
}
