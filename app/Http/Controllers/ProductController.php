<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->latest();
        if ($search = $request->string('busca')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }

        return view('products.index', ['products' => $query->paginate(12)->withQueryString()]);
    }

    public function create(): View
    {
        return view('products.form', ['product' => new Product]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::create($this->validated($request));
        $this->storeGallery($request, $product);

        return redirect()->route('produtos.index')->with('success', 'Produto cadastrado com sucesso.');
    }

    public function edit(Product $produto): View
    {
        return view('products.form', ['product' => $produto]);
    }

    public function update(Request $request, Product $produto): RedirectResponse
    {
        $produto->update($this->validated($request, $produto));
        $this->storeGallery($request, $produto);

        return redirect()->route('produtos.index')->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroyImage(Product $produto, ProductImage $foto): RedirectResponse
    {
        abort_unless($foto->product_id === $produto->id, 404);
        if (Str::startsWith($foto->path, '/storage/')) {
            Storage::disk('public')->delete(Str::after($foto->path, '/storage/'));
        }
        $foto->delete();

        return back()->with('success', 'Foto removida da galeria.');
    }

    public function destroyMainImage(Product $produto): RedirectResponse
    {
        if (Str::startsWith((string) $produto->image_url, '/storage/')) {
            Storage::disk('public')->delete(Str::after($produto->image_url, '/storage/'));
        }
        $produto->update(['image_url' => null]);

        return back()->with('success', 'Foto principal removida.');
    }

    public function storeImages(Request $request, Product $produto): RedirectResponse
    {
        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            if (Str::startsWith((string) $produto->image_url, '/storage/')) {
                Storage::disk('public')->delete(Str::after($produto->image_url, '/storage/'));
            }
            $produto->update(['image_url' => Storage::url($request->file('image')->store('products', 'public'))]);
        }
        $this->storeGallery($request, $produto);

        return back()->with('success', 'Fotos atualizadas com sucesso.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $skuRule = 'unique:products,sku'.($product ? ','.$product->id : '');
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:60', $skuRule], 'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:product,service'], 'base_price' => ['required', 'numeric', 'min:0'],
            'production_cost' => ['required', 'numeric', 'min:0'], 'made_to_order' => ['boolean'],
            'active' => ['boolean'], 'description' => ['nullable', 'string', 'max:3000'], 'store_visible' => ['boolean'],
            'store_slug' => ['nullable', 'string', 'max:180', 'unique:products,store_slug'.($product ? ','.$product->id : '')],
            'image_url' => ['nullable', 'string', 'max:1000', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! Str::startsWith($value, '/storage/') && filter_var($value, FILTER_VALIDATE_URL) === false) {
                    $fail('Informe uma URL válida para a imagem.');
                }
            }],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'allow_personalization' => ['boolean'],
            'store_featured' => ['boolean'], 'store_occasions' => ['nullable', 'array', 'max:3'],
            'store_occasions.*' => ['in:presente,decoracao,empresa'],
            'images' => ['nullable', 'array', 'max:8'], 'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $data['store_occasions'] = array_values(array_unique($data['store_occasions'] ?? []));
        if ($request->hasFile('image')) {
            if ($product && Str::startsWith((string) $product->image_url, '/storage/')) {
                Storage::disk('public')->delete(Str::after($product->image_url, '/storage/'));
            }
            $data['image_url'] = Storage::url($request->file('image')->store('products', 'public'));
        }
        unset($data['image'], $data['images']);

        return $data;
    }

    private function storeGallery(Request $request, Product $product): void
    {
        $nextOrder = ((int) $product->images()->max('sort_order')) + 1;
        foreach ($request->file('images', []) as $image) {
            $product->images()->create([
                'path' => Storage::url($image->store('products', 'public')),
                'sort_order' => $nextOrder++,
            ]);
        }
    }
}
