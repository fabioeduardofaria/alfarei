<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->latest();
        if ($search = $request->string('busca')->trim()->toString()) $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        return view('products.index', ['products' => $query->paginate(12)->withQueryString()]);
    }
    public function create(): View { return view('products.form', ['product' => new Product()]); }
    public function store(Request $request): RedirectResponse { Product::create($this->validated($request)); return redirect()->route('produtos.index')->with('success', 'Produto cadastrado com sucesso.'); }
    public function edit(Product $produto): View { return view('products.form', ['product' => $produto]); }
    public function update(Request $request, Product $produto): RedirectResponse { $produto->update($this->validated($request, $produto)); return redirect()->route('produtos.index')->with('success', 'Produto atualizado com sucesso.'); }
    private function validated(Request $request, ?Product $product = null): array
    {
        $skuRule = 'unique:products,sku'.($product ? ','.$product->id : '');
        return $request->validate([
            'sku' => ['nullable', 'string', 'max:60', $skuRule], 'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:product,service'], 'base_price' => ['required', 'numeric', 'min:0'],
            'production_cost' => ['required', 'numeric', 'min:0'], 'made_to_order' => ['boolean'],
            'active' => ['boolean'], 'description' => ['nullable', 'string', 'max:3000'], 'store_visible' => ['boolean'],
            'store_slug' => ['nullable', 'string', 'max:180', 'unique:products,store_slug'.($product ? ','.$product->id : '')], 'image_url' => ['nullable', 'url', 'max:1000'], 'allow_personalization' => ['boolean'],
        ]);
    }
}
